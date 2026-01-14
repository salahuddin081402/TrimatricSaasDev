<?php
// app/Http/Controllers/Backend/Interior/InteriorRequisitionController.php

namespace App\Http\Controllers\Backend\Interior;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InteriorRequisitionController extends Controller
{
    /* ============================================================
       REQUIRED UTILITY FUNCTIONS (DO NOT CHANGE)
       ============================================================ */

    private function currentUserId(): ?int
    {
        $forced = config('header.dev_force_user_id');

        // If dev forced user id is set, ALWAYS use it for this module.
        if (is_numeric($forced)) {
            return (int) $forced;
        }

        return Auth::id();
    }

    private function resolveCompany($routeCompany): ?object
    {
        if ($routeCompany instanceof \App\Models\SuperAdmin\GlobalSetup\Company) {
            return DB::table('companies')
                ->where('id', $routeCompany->id)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->first();
        }
        if (is_numeric($routeCompany)) {
            return DB::table('companies')
                ->where('id', $routeCompany)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->first();
        }
        if (is_string($routeCompany)) {
            $c = DB::table('companies')
                ->where('slug', $routeCompany)
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->first();
            if ($c) return $c;
        }

        $uid = $this->currentUserId();
        if ($uid) {
            $user = DB::table('users')->where('id', $uid)->first();
            if ($user && $user->company_id) {
                return DB::table('companies')
                    ->where('id', $user->company_id)
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->first();
            }
            if ($user && $user->role_id) {
                return DB::table('companies')
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->orderBy('id')
                    ->first();
            }
        }

        return null;
    }

    private function companyCountry(object $companyRow): object
    {
        $c = DB::table('countries')
            ->where('id', $companyRow->country_id)
            ->first(['name', 'short_code']);
        $name = $c->name ?? 'global';
        $short = strtoupper($c->short_code ?? 'XX');
        return (object) ['name' => $name, 'short_code' => $short];
    }

    private function maxImageKB(): int
    {
        return (int) config('interior_requisition.max_image_kb', 10240); // 10MB default
    }

    /* ============================================================
       ROUTE NAME RESOLUTION (LIKE InteriorParameterController)
       ============================================================ */

    private function requisitionsRoute(string $suffix): string
    {
        $candidates = [
            'backend.interior.interior.requisitions.' . $suffix,
            'backend.interior.requisitions.' . $suffix,
            'interior.requisitions.' . $suffix,
        ];

        foreach ($candidates as $name) {
            if (Route::has($name)) return $name;
        }

        return 'backend.interior.interior.requisitions.' . $suffix;
    }

    /* ============================================================
       CONTEXT / LOOKUPS
       ============================================================ */

    private function userRoleIdForCompany(int $companyId, int $userId): ?int
    {
        $u = DB::table('users')
            ->where('id', $userId)
            ->where('company_id', $companyId)
            ->first(['role_id']);
        return $u?->role_id ? (int) $u->role_id : null;
    }

    /**
     * Build "top strip" context:
     * - reg_id, client_user_id display
     * - cluster id + name display
     * - cluster admin phone for the help text
     */
    private function buildClientCtx(int $companyId, int $userId): array
    {
        $reg = DB::table('registration_master')
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->first(['id', 'user_id', 'full_name', 'phone', 'email', 'upazila_id', 'registration_type']);

        $ctx = [
            'reg_id' => $reg?->id, // display
            'client_user_id' => $reg ? (int) $reg->user_id : null, // display or null
            'client_name' => $reg?->full_name,
            'client_phone' => $reg?->phone,
            'client_email' => $reg?->email,
            'registration_type' => $reg?->registration_type,
            'cluster_id' => null,
            'cluster_name' => null,
            'cluster_admin_phone' => null,
        ];

         $upazilaId = (int) ($reg->upazila_id ?? $reg->UPAZILA_ID ?? $reg->Upazila_ID ?? 0);

        if (!$reg || $upazilaId <= 0) return $ctx;

        // Primary (strict) lookup: scoped by company + active flags.
        $map = DB::table('cluster_upazila_mappings as m')
            ->join('cluster_masters as c', 'c.id', '=', 'm.cluster_id')
            ->where('m.company_id', $companyId)
            ->where('c.company_id', $companyId)
            ->where('m.upazila_id', $upazilaId)
            ->where('m.status', 1)
            ->where('c.status', 1)
            ->select([
                'c.id as cluster_id',
                'c.cluster_name',
                'c.cluster_supervisor_id',
            ])
            ->first();

        if (!$map) {
            // Fallback (development resilience): if company/status flags are not set consistently,
            // still resolve by upazila_id and prefer matches for the current company.
            $map = DB::table('cluster_upazila_mappings as m')
                ->join('cluster_masters as c', 'c.id', '=', 'm.cluster_id')
                ->where('m.upazila_id', $upazilaId)
                ->orderByRaw('CASE WHEN m.company_id = ? THEN 0 ELSE 1 END', [$companyId])
                ->orderByRaw('CASE WHEN c.company_id = ? THEN 0 ELSE 1 END', [$companyId])
                ->orderByDesc('m.status')
                ->orderByDesc('c.status')
                ->select([
                    'c.id as cluster_id',
                    'c.cluster_name',
                    'c.cluster_supervisor_id',
                ])
                ->first();
        }

        // Extra fallback: avoid JOIN sensitivity by resolving in two steps.
        // (Keeps behavior deterministic even if one side has unexpected flags.)
        if (!$map) {
            $clusterId = DB::table('cluster_upazila_mappings')
                ->where('upazila_id', $upazilaId)
                ->where('company_id', $companyId)
                ->orderByDesc('status')
                ->value('cluster_id');

            if (!$clusterId) {
                $clusterId = DB::table('cluster_upazila_mappings')
                    ->where('upazila_id', $upazilaId)
                    ->orderByRaw('CASE WHEN company_id = ? THEN 0 ELSE 1 END', [$companyId])
                    ->orderByDesc('status')
                    ->value('cluster_id');
            }

            if ($clusterId) {
                $cluster = DB::table('cluster_masters')
                    ->where('id', $clusterId)
                    ->first(['id as cluster_id', 'cluster_name', 'cluster_supervisor_id']);
                if ($cluster) {
                    $map = $cluster;
                }
            }
        }

        if (!$map) return $ctx;

        $ctx['cluster_id'] = (int) $map->cluster_id;
        $ctx['cluster_name'] = (string) $map->cluster_name;

        // Keep a stable display string for the Blade top info card.
        $clusterIdVal = (int) ($ctx['cluster_id'] ?? 0);
        $clusterNameVal = (string) ($ctx['cluster_name'] ?? '');
        $ctx['cluster_display'] = $clusterIdVal
            ? ($clusterIdVal . ($clusterNameVal !== '' ? (' - ' . $clusterNameVal) : ''))
            : '';

        if ($map->cluster_supervisor_id) {
            $superReg = DB::table('registration_master')
                ->where('company_id', $companyId)
                ->where('user_id', $map->cluster_supervisor_id)
                ->whereNull('deleted_at')
                ->first(['phone']);
            if ($superReg?->phone) {
                $ctx['cluster_admin_phone'] = (string) $superReg->phone;
            }
        }

        return $ctx;
    }

    private function requireClientOrEnterprise(array $ctx): void
    {
        $type = $ctx['registration_type'] ?? null;
        abort_if(!in_array($type, ['client', 'enterprise_client'], true), 403, 'Only client or enterprise_client can create requisition');
    }

    private function isLockedStatus(?string $status): bool
    {
        return in_array((string) $status, ['Closed', 'Declined'], true);
    }

    /* ============================================================
       FILE PATHS (MATCH YOUR PATTERN)
       ============================================================ */

    private function attachmentBaseDir(object $companyRow, int $clientUserId, string $clientName, int $requisitionId): string
    {
        $country = $this->companyCountry($companyRow);
        $countrySeg = Str::slug(strtolower((string) ($country->name ?? 'global')));
        if ($countrySeg === '') $countrySeg = 'global';

        $clientSeg = trim((string) $clientName);
        $clientSeg = $clientSeg !== '' ? Str::slug($clientSeg) : 'client';

        // {Client_User_ID}_{Client_Name}/{Requisition_Id}
        return $countrySeg
            . '/' . $companyRow->slug
            . '/images/Interior_Requisition/'
            . $clientUserId . '_' . $clientSeg
            . '/' . $requisitionId;
    }

    private function makeAttachmentFileName(int $clientUserId, int $requisitionId, int $serial, string $originalName): string
    {
        $orig = trim($originalName);
        $orig = $orig !== '' ? $orig : 'image';
        $orig = preg_replace('/\s+/', '-', $orig);
        $orig = preg_replace('/[^A-Za-z0-9\.\-\_]/', '', $orig);

        $ts = now()->format('YmdHis');
        return $clientUserId . '_' . $requisitionId . '_' . $ts . '_' . str_pad((string) $serial, 3, '0', STR_PAD_LEFT) . '_' . $orig;
    }

    private function validateImages(?array $files, string $fieldLabel = 'Attachments'): void
    {
        if (!$files) return;

        $maxKb = $this->maxImageKB();

        foreach ($files as $f) {
            if (!$f) continue;

            if (!$f->isValid()) {
                throw ValidationException::withMessages([
                    'client_attachments' => [$fieldLabel . ': one of the uploaded files is invalid.'],
                ]);
            }

            $mime = (string) $f->getMimeType();
            if (strpos($mime, 'image/') !== 0) {
                throw ValidationException::withMessages([
                    'client_attachments' => [$fieldLabel . ': only image files are allowed.'],
                ]);
            }

            $sizeKb = (int) ceil($f->getSize() / 1024);
            if ($sizeKb > $maxKb) {
                throw ValidationException::withMessages([
                    'client_attachments' => [$fieldLabel . ': each image must be <= ' . $maxKb . ' KB.'],
                ]);
            }
        }
    }

    private function persistAttachments(
        object $companyRow,
        int $companyId,
        int $requisitionId,
        int $clientUserId,
        int $uploadedByRegId,
        string $clientName,
        ?array $newFiles,
        array $keepIds,
        array $removeIds,
        int $stepNo = 1
    ): void {
        // normalize ids
        $keepIds = array_values(array_filter(array_map('intval', $keepIds), fn($v) => $v > 0));
        $removeIds = array_values(array_filter(array_map('intval', $removeIds), fn($v) => $v > 0));

        // Remove requested
        if (!empty($removeIds)) {
            $rows = DB::table('interior_requisition_attachments')
                ->where('company_id', $companyId)
                ->where('requisition_id', $requisitionId)
                ->whereIn('id', $removeIds)
                ->get(['id', 'file_path']);

            foreach ($rows as $r) {
                if ($r->file_path) {
                    Storage::disk('public')->delete($r->file_path);
                }
            }

            DB::table('interior_requisition_attachments')
                ->where('company_id', $companyId)
                ->where('requisition_id', $requisitionId)
                ->whereIn('id', $removeIds)
                ->delete();
        }

        // If keepIds provided, delete others not in keepIds (useful when UI sends full list)
        if (!empty($keepIds)) {
            $toDelete = DB::table('interior_requisition_attachments')
                ->where('company_id', $companyId)
                ->where('requisition_id', $requisitionId)
                ->whereNotIn('id', $keepIds)
                ->get(['id', 'file_path']);

            foreach ($toDelete as $r) {
                if ($r->file_path) {
                    Storage::disk('public')->delete($r->file_path);
                }
            }

            DB::table('interior_requisition_attachments')
                ->where('company_id', $companyId)
                ->where('requisition_id', $requisitionId)
                ->whereNotIn('id', $keepIds)
                ->delete();
        }

        // Add new files
        if (!$newFiles) return;

        $baseDir = $this->attachmentBaseDir($companyRow, $clientUserId, $clientName, $requisitionId);

        $serial = 1;
        foreach ($newFiles as $f) {
            if (!$f) continue;

            $filename = $this->makeAttachmentFileName($clientUserId, $requisitionId, $serial, (string) $f->getClientOriginalName());
            $rel = $baseDir . '/' . $filename;

            // ensure folder exists and store
            Storage::disk('public')->putFileAs($baseDir, $f, $filename);

            DB::table('interior_requisition_attachments')->insert([
                'company_id' => $companyId,
                'requisition_id' => $requisitionId,
                'uploaded_by_user_id' => $clientUserId,
                'uploaded_by_reg_id' => $uploadedByRegId ?: null,
                'step_no' => $stepNo,
                'note' => null,
                'original_name' => (string) $f->getClientOriginalName(),
                'file_path' => $rel, // relative path only
                'mime_type' => (string) $f->getMimeType(),
                'file_size_kb' => (int) ceil($f->getSize() / 1024),
                'sort_order' => 0,
                'created_at' => now(),
            ]);

            $serial++;
        }
    }

    /* ============================================================
       STATE PERSISTENCE (TREE)
       Accepts one JSON field: state_json (recommended)
       ============================================================ */

    private function decodeState(Request $request): ?array
    {
        $raw = $request->input('state_json');
        if (!$raw) return null;

        if (is_array($raw)) return $raw; // already decoded upstream
        $raw = (string) $raw;

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'state_json' => ['Invalid state payload (JSON).'],
            ]);
        }
    }

    private function persistTreeFromState(int $companyId, int $requisitionId, array $state): void
    {
        // Expected shape (minimum):
        // {
        //   "project_type_id": 1,
        //   "project_subtype_id": 10,
        //   "spaces": {
        //      "12": { "qty": 1, "sqft": 120.5, "categories": { "5": { "subcategories": { "9": { "products": { "101": 2 } } } } } }
        //   }
        // }

        $spaces = $state['spaces'] ?? [];
        if (!is_array($spaces)) $spaces = [];

        // SPACE LINES: delete+insert (lines are immutable in DB)
        DB::table('interior_requisition_space_lines')
            ->where('company_id', $companyId)
            ->where('requisition_id', $requisitionId)
            ->delete();

        $spaceRows = [];
        foreach ($spaces as $spaceId => $s) {
            $spaceId = (int) $spaceId;
            if ($spaceId <= 0) continue;

            $qty = (int) ($s['qty'] ?? 1);
            if ($qty < 1) $qty = 1;

            $sqft = (float) ($s['sqft'] ?? 0);

            $spaceRows[] = [
                'company_id' => $companyId,
                'requisition_id' => $requisitionId,
                'space_id' => $spaceId,
                'space_qty' => $qty,
                'space_total_sqft' => $sqft,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($spaceRows)) {
            DB::table('interior_requisition_space_lines')->insert($spaceRows);
        }

        // CATEGORY / SUBCATEGORY / PRODUCT LINES: delete+insert (immutables)
        DB::table('interior_requisition_category_lines')
            ->where('company_id', $companyId)
            ->where('requisition_id', $requisitionId)
            ->delete();

        DB::table('interior_requisition_subcategory_lines')
            ->where('company_id', $companyId)
            ->where('requisition_id', $requisitionId)
            ->delete();

        DB::table('interior_requisition_product_lines')
            ->where('company_id', $companyId)
            ->where('requisition_id', $requisitionId)
            ->delete();

        $catRows = [];
        $subcatRows = [];
        $prodRows = [];

        foreach ($spaces as $spaceId => $s) {
            $spaceId = (int) $spaceId;
            if ($spaceId <= 0) continue;

            $categories = $s['categories'] ?? [];
            if (!is_array($categories)) $categories = [];

            foreach ($categories as $catId => $c) {
                $catId = (int) $catId;
                if ($catId <= 0) continue;

                $catRows[] = [
                    'company_id' => $companyId,
                    'requisition_id' => $requisitionId,
                    'space_id' => $spaceId,
                    'category_id' => $catId,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $subcats = $c['subcategories'] ?? [];
                if (!is_array($subcats)) $subcats = [];

                foreach ($subcats as $subcatId => $sc) {
                    $subcatId = (int) $subcatId;
                    if ($subcatId <= 0) continue;

                    $subcatRows[] = [
                        'company_id' => $companyId,
                        'requisition_id' => $requisitionId,
                        'space_id' => $spaceId,
                        'category_id' => $catId,
                        'subcategory_id' => $subcatId,
                        'sort_order' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $products = $sc['products'] ?? [];
                    if (!is_array($products)) $products = [];

                    foreach ($products as $productId => $qty) {
                        $productId = (int) $productId;
                        if ($productId <= 0) continue;

                        $q = (int) $qty;
                        if ($q < 1) $q = 1;

                        $prodRows[] = [
                            'company_id' => $companyId,
                            'requisition_id' => $requisitionId,
                            'space_id' => $spaceId,
                            'category_id' => $catId,
                            'subcategory_id' => $subcatId,
                            'product_id' => $productId,
                            'qty' => $q,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
        }

        if (!empty($catRows)) DB::table('interior_requisition_category_lines')->insert($catRows);
        if (!empty($subcatRows)) DB::table('interior_requisition_subcategory_lines')->insert($subcatRows);
        if (!empty($prodRows)) DB::table('interior_requisition_product_lines')->insert($prodRows);
    }

    /* ============================================================
       CRUD
       Single Blade: resources/views/backend/interior/requisitions/index.blade.php
       ============================================================ */

    public function create(string $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        $ctx = $this->buildClientCtx($companyId, $uid);
        abort_if(!$ctx['reg_id'], 403, 'Registration not found');
        $this->requireClientOrEnterprise($ctx);

        $roleId = $this->userRoleIdForCompany($companyId, $uid);
        $canClusterRemark = in_array((int) $roleId, [7, 8], true);
        $canHeadOfficeRemark = in_array((int) $roleId, [2, 3, 4, 5, 6], true);

        // Parameter datasets (active, tenant-aware/global)
        $projectTypes = DB::table('cr_project_types')
            ->where('is_active', 1)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // NOTE: subtypes/spaces/categories/subcategories/products can be fetched lazily by AJAX;
        // for now we provide them for initial rendering (can be optimized later).
        $projectSubtypes = DB::table('cr_project_subtypes')
            ->where('is_active', 1)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $spaces = DB::table('cr_spaces')
            ->where('is_active', 1)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $categories = DB::table('cr_item_categories')
            ->where('is_active', 1)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $subcategories = DB::table('cr_item_subcategories')
            ->where('is_active', 1)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $products = DB::table('cr_products')
            ->where('is_active', 1)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $spaceCategoryMap = DB::table('cr_space_category_mappings')
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->get();

        return view('backend.interior.requisitions.index', [
            'mode' => 'create',
            'companyRow' => $companyRow,
            'companyId' => $companyId,
            'ctx' => $ctx,
            'regId' => $ctx['reg_id'] ?? null,
            'clientUserId' => $ctx['user_id'] ?? null,
            'clientName' => $ctx['full_name'] ?? null,
            'clientPhone' => $ctx['phone'] ?? null,
            'clusterAdminPhone' => $ctx['cluster_admin_phone'] ?? null,
            'clusterDisplay' => (isset($ctx['cluster_id']) && (int)$ctx['cluster_id'] > 0)
                ? (
                    (isset($ctx['cluster_name']) && trim((string)$ctx['cluster_name']) !== '')
                        ? ((string)$ctx['cluster_id'] . ' - ' . (string)$ctx['cluster_name'])
                        : (string)$ctx['cluster_id']
                )
                : null,

            'roleId' => $roleId,
            'canClusterRemark' => $canClusterRemark,
            'canHeadOfficeRemark' => $canHeadOfficeRemark,
            'requisition' => null,
            'state' => null,
            'attachments' => collect([]),
            'projectTypes' => $projectTypes,
            'projectSubtypes' => $projectSubtypes,
            'spaces' => $spaces,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'products' => $products,
            'spaceCategoryMap' => $spaceCategoryMap,
        ]);
    }

    public function store(Request $request, string $company)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        $ctx = $this->buildClientCtx($companyId, $uid);
        abort_if(!$ctx['reg_id'], 403, 'Registration not found');
        $this->requireClientOrEnterprise($ctx);

        // Strict mandatory step-1 + core selections
        $state = $this->decodeState($request);

        // if state_json present, use it for validation of mandatory selections too
        $projectTypeId = (int) ($request->input('project_type_id') ?? ($state['project_type_id'] ?? 0));
        $projectSubtypeId = (int) ($request->input('project_subtype_id') ?? ($state['project_subtype_id'] ?? 0));

        $validator = validator($request->all(), [
            'project_address' => ['required', 'string', 'max:600'],
            'project_budget' => ['required', 'numeric', 'min:0'],
            'project_eta' => ['required', 'date'],
        ], [
            'project_address.required' => 'Project Location / Address is required.',
            'project_budget.required' => 'Budget (Approximate) is required.',
            'project_eta.required' => 'Expected Time of Delivery is required.',
        ]);

        $validator->after(function ($v) use ($projectTypeId, $projectSubtypeId, $state) {
            if ($projectTypeId <= 0) {
                $v->errors()->add('project_type_id', 'Project Type is required.');
            }
            if ($projectSubtypeId <= 0) {
                $v->errors()->add('project_subtype_id', 'Project Sub-Type is required.');
            }

            // Spaces at least one
            $hasSpaces = false;
            if (is_array($state) && !empty($state['spaces']) && is_array($state['spaces'])) $hasSpaces = true;

            $spaces = request()->input('spaces');
            if (!$hasSpaces && (!is_array($spaces) || empty($spaces))) {
                $v->errors()->add('spaces', 'Spaces to be Included: select at least one.');
            }
        });

        $validator->validate();

        // attachments validation
        $files = $request->file('client_attachments', []);
        if (!is_array($files)) $files = [$files];
        $this->validateImages($files, 'Client Attachments');

        $roleId = $this->userRoleIdForCompany($companyId, $uid);
        $canClusterRemark = in_array((int) $roleId, [7, 8], true);
        $canHeadOfficeRemark = in_array((int) $roleId, [2, 3, 4, 5, 6], true);

        $clusterRemark = $canClusterRemark ? (string) $request->input('cluster_member_remark') : null;
        $headRemark = $canHeadOfficeRemark ? (string) $request->input('head_office_remark') : null;

        $requisitionId = null;

        DB::transaction(function () use (
            $companyRow, $companyId, $uid, $ctx, $request, $state,
            $projectTypeId, $projectSubtypeId,
            $clusterRemark, $headRemark,
            $files, &$requisitionId
        ) {
            $now = now();

            $requisitionId = DB::table('interior_requisition_master')->insertGetId([
                'company_id' => $companyId,
                'reg_id' => (int) $ctx['reg_id'],
                'client_user_id' => $uid,
                'project_address' => (string) $request->input('project_address'),
                'project_note' => $request->input('project_note'),
                'project_budget' => $request->input('project_budget'),
                'project_eta' => $request->input('project_eta'),
                'cluster_member_remark' => $clusterRemark,
                'head_office_remark' => $headRemark,
                'project_type_id' => $projectTypeId,
                'project_subtype_id' => $projectSubtypeId,
                'status' => 'Submitted',
                'submitted_at' => $now,
                'created_by' => $uid,
                'updated_by' => $uid,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Persist tree selections
            if (is_array($state)) {
                $this->persistTreeFromState($companyId, $requisitionId, $state);
            } else {
                // fallback: spaces[] expected as array of rows [{space_id, qty, sqft}]
                $spaces = $request->input('spaces', []);
                if (is_array($spaces)) {
                    DB::table('interior_requisition_space_lines')
                        ->where('company_id', $companyId)
                        ->where('requisition_id', $requisitionId)
                        ->delete();

                    $rows = [];
                    foreach ($spaces as $row) {
                        $sid = (int) ($row['space_id'] ?? 0);
                        if ($sid <= 0) continue;
                        $rows[] = [
                            'company_id' => $companyId,
                            'requisition_id' => $requisitionId,
                            'space_id' => $sid,
                            'space_qty' => max(1, (int) ($row['qty'] ?? 1)),
                            'space_total_sqft' => (float) ($row['sqft'] ?? 0),
                            'sort_order' => 0,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    if (!empty($rows)) DB::table('interior_requisition_space_lines')->insert($rows);
                }
            }

            // Attachments
            $this->persistAttachments(
                $companyRow,
                $companyId,
                $requisitionId,
                $uid,
                (int) $ctx['reg_id'],
                (string) ($ctx['client_name'] ?? 'client'),
                $files,
                [], // keep
                [], // remove
                1
            );

            // Status log
            DB::table('interior_requisition_status_logs')->insert([
                'company_id' => $companyId,
                'requisition_id' => $requisitionId,
                'from_status' => null,
                'to_status' => 'Submitted',
                'note' => 'Created',
                'changed_by_user_id' => $uid,
                'changed_at' => $now,
            ]);
        });

        return redirect()
            ->route($this->requisitionsRoute('edit'), ['company' => $companyRow->slug, 'requisition' => $requisitionId])
            ->with('success', 'Requisition submitted successfully.');
    }

    public function edit(string $company, int $requisition)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        $ctx = $this->buildClientCtx($companyId, $uid);
        abort_if(!$ctx['reg_id'], 403, 'Registration not found');

        $row = DB::table('interior_requisition_master')
            ->where('company_id', $companyId)
            ->where('id', $requisition)
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$row, 404, 'Requisition not found');

        $roleId = $this->userRoleIdForCompany($companyId, $uid);
        $canClusterRemark = in_array((int) $roleId, [7, 8], true);
        $canHeadOfficeRemark = in_array((int) $roleId, [2, 3, 4, 5, 6], true);

        // Hydrate existing state from DB (for the single Blade)
        $spaces = DB::table('interior_requisition_space_lines')
            ->where('company_id', $companyId)
            ->where('requisition_id', $requisition)
            ->get();

        $cats = DB::table('interior_requisition_category_lines')
            ->where('company_id', $companyId)
            ->where('requisition_id', $requisition)
            ->get();

        $subcats = DB::table('interior_requisition_subcategory_lines')
            ->where('company_id', $companyId)
            ->where('requisition_id', $requisition)
            ->get();

        $prods = DB::table('interior_requisition_product_lines')
            ->where('company_id', $companyId)
            ->where('requisition_id', $requisition)
            ->get();

        $state = [
            'project_type_id' => $row->project_type_id,
            'project_subtype_id' => $row->project_subtype_id,
            'spaces' => [],
        ];

        foreach ($spaces as $s) {
            $sid = (int) $s->space_id;
            $state['spaces'][(string) $sid] = [
                'qty' => (int) $s->space_qty,
                'sqft' => (float) $s->space_total_sqft,
                'categories' => [],
            ];
        }

        foreach ($cats as $c) {
            $sid = (int) $c->space_id;
            $cid = (int) $c->category_id;
            if (!isset($state['spaces'][(string) $sid])) continue;
            $state['spaces'][(string) $sid]['categories'][(string) $cid] = [
                'subcategories' => [],
            ];
        }

        foreach ($subcats as $sc) {
            $sid = (int) $sc->space_id;
            $cid = (int) $sc->category_id;
            $scid = (int) $sc->subcategory_id;
            if (!isset($state['spaces'][(string) $sid]['categories'][(string) $cid])) continue;
            $state['spaces'][(string) $sid]['categories'][(string) $cid]['subcategories'][(string) $scid] = [
                'products' => [],
            ];
        }

        foreach ($prods as $p) {
            $sid = (int) $p->space_id;
            $cid = (int) $p->category_id;
            $scid = (int) $p->subcategory_id;
            $pid = (int) $p->product_id;
            if (!isset($state['spaces'][(string) $sid]['categories'][(string) $cid]['subcategories'][(string) $scid])) continue;
            $state['spaces'][(string) $sid]['categories'][(string) $cid]['subcategories'][(string) $scid]['products'][(string) $pid] = (int) $p->qty;
        }

        $attachments = DB::table('interior_requisition_attachments')
            ->where('company_id', $companyId)
            ->where('requisition_id', $requisition)
            ->orderBy('id')
            ->get();

        // Parameter datasets
        $projectTypes = DB::table('cr_project_types')
            ->where('is_active', 1)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $projectSubtypes = DB::table('cr_project_subtypes')
            ->where('is_active', 1)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $spacesAll = DB::table('cr_spaces')
            ->where('is_active', 1)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $categories = DB::table('cr_item_categories')
            ->where('is_active', 1)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $subcategoriesAll = DB::table('cr_item_subcategories')
            ->where('is_active', 1)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $productsAll = DB::table('cr_products')
            ->where('is_active', 1)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $spaceCategoryMap = DB::table('cr_space_category_mappings')
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->get();

        return view('backend.interior.requisitions.index', [
            'mode' => 'edit',
            'companyRow' => $companyRow,
            'companyId' => $companyId,
            'ctx' => $ctx,
            'roleId' => $roleId,
            'canClusterRemark' => $canClusterRemark,
            'canHeadOfficeRemark' => $canHeadOfficeRemark,
            'requisition' => $row,
            'state' => $state,
            'attachments' => $attachments,
            'projectTypes' => $projectTypes,
            'projectSubtypes' => $projectSubtypes,
            'spaces' => $spacesAll,
            'categories' => $categories,
            'subcategories' => $subcategoriesAll,
            'products' => $productsAll,
            'spaceCategoryMap' => $spaceCategoryMap,
        ]);
    }

    public function update(Request $request, string $company, int $requisition)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        $ctx = $this->buildClientCtx($companyId, $uid);
        abort_if(!$ctx['reg_id'], 403, 'Registration not found');

        $row = DB::table('interior_requisition_master')
            ->where('company_id', $companyId)
            ->where('id', $requisition)
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$row, 404, 'Requisition not found');
        abort_if($this->isLockedStatus($row->status), 403, 'Requisition is locked (Closed/Declined)');

        $state = $this->decodeState($request);

        $projectTypeId = (int) ($request->input('project_type_id') ?? ($state['project_type_id'] ?? 0));
        $projectSubtypeId = (int) ($request->input('project_subtype_id') ?? ($state['project_subtype_id'] ?? 0));

        // Mandatory fields still required
        $validator = validator($request->all(), [
            'project_address' => ['required', 'string', 'max:600'],
            'project_budget' => ['required', 'numeric', 'min:0'],
            'project_eta' => ['required', 'date'],
        ], [
            'project_address.required' => 'Project Location / Address is required.',
            'project_budget.required' => 'Budget (Approximate) is required.',
            'project_eta.required' => 'Expected Time of Delivery is required.',
        ]);

        $validator->after(function ($v) use ($projectTypeId, $projectSubtypeId, $state) {
            if ($projectTypeId <= 0) {
                $v->errors()->add('project_type_id', 'Project Type is required.');
            }
            if ($projectSubtypeId <= 0) {
                $v->errors()->add('project_subtype_id', 'Project Sub-Type is required.');
            }

            $hasSpaces = false;
            if (is_array($state) && !empty($state['spaces']) && is_array($state['spaces'])) $hasSpaces = true;

            $spaces = request()->input('spaces');
            if (!$hasSpaces && (!is_array($spaces) || empty($spaces))) {
                $v->errors()->add('spaces', 'Spaces to be Included: select at least one.');
            }
        });

        $validator->validate();

        // attachments validation
        $files = $request->file('client_attachments', []);
        if (!is_array($files)) $files = [$files];
        $this->validateImages($files, 'Client Attachments');

        $keepIds = $request->input('attachments_keep', []);
        if (!is_array($keepIds)) $keepIds = [$keepIds];

        $removeIds = $request->input('attachments_remove', []);
        if (!is_array($removeIds)) $removeIds = [$removeIds];

        $roleId = $this->userRoleIdForCompany($companyId, $uid);
        $canClusterRemark = in_array((int) $roleId, [7, 8], true);
        $canHeadOfficeRemark = in_array((int) $roleId, [2, 3, 4, 5, 6], true);

        DB::transaction(function () use (
            $companyRow, $companyId, $uid, $ctx, $request, $state,
            $projectTypeId, $projectSubtypeId, $requisition,
            $canClusterRemark, $canHeadOfficeRemark,
            $files, $keepIds, $removeIds
        ) {
            $now = now();

            $update = [
                'project_address' => (string) $request->input('project_address'),
                'project_note' => $request->input('project_note'),
                'project_budget' => $request->input('project_budget'),
                'project_eta' => $request->input('project_eta'),
                'project_type_id' => $projectTypeId,
                'project_subtype_id' => $projectSubtypeId,
                'updated_by' => $uid,
                'updated_at' => $now,
            ];

            if ($canClusterRemark) {
                $update['cluster_member_remark'] = $request->input('cluster_member_remark');
            }
            if ($canHeadOfficeRemark) {
                $update['head_office_remark'] = $request->input('head_office_remark');
            }

            DB::table('interior_requisition_master')
                ->where('company_id', $companyId)
                ->where('id', $requisition)
                ->update($update);

            // Persist tree selections
            if (is_array($state)) {
                $this->persistTreeFromState($companyId, $requisition, $state);
            } else {
                $spaces = $request->input('spaces', []);
                if (is_array($spaces)) {
                    DB::table('interior_requisition_space_lines')
                        ->where('company_id', $companyId)
                        ->where('requisition_id', $requisition)
                        ->delete();

                    $rows = [];
                    foreach ($spaces as $row) {
                        $sid = (int) ($row['space_id'] ?? 0);
                        if ($sid <= 0) continue;
                        $rows[] = [
                            'company_id' => $companyId,
                            'requisition_id' => $requisition,
                            'space_id' => $sid,
                            'space_qty' => max(1, (int) ($row['qty'] ?? 1)),
                            'space_total_sqft' => (float) ($row['sqft'] ?? 0),
                            'sort_order' => 0,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    if (!empty($rows)) DB::table('interior_requisition_space_lines')->insert($rows);
                }
            }

            // Attachments: keep/remove + add new
            $this->persistAttachments(
                $companyRow,
                $companyId,
                $requisition,
                $uid,
                (int) $ctx['reg_id'],
                (string) ($ctx['client_name'] ?? 'client'),
                $files,
                $keepIds,
                $removeIds,
                1
            );
        });

        return redirect()
            ->route($this->requisitionsRoute('edit'), ['company' => $companyRow->slug, 'requisition' => $requisition])
            ->with('success', 'Requisition updated successfully.');
    }

    /* ============================================================
       OPTIONAL: SILENT AUTOSAVE (Parameter-style baseUrl endpoints)
       ============================================================ */

    public function autosaveStep1(Request $request, string $company, int $requisition)
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        $row = DB::table('interior_requisition_master')
            ->where('company_id', $companyId)
            ->where('id', $requisition)
            ->whereNull('deleted_at')
            ->first(['id', 'status']);

        abort_if(!$row, 404, 'Requisition not found');
        abort_if($this->isLockedStatus($row->status), 403, 'Requisition is locked (Closed/Declined)');

        $state = $this->decodeState($request);

        // light validation (for autosave)
        $projectTypeId = (int) ($request->input('project_type_id') ?? ($state['project_type_id'] ?? 0));
        $projectSubtypeId = (int) ($request->input('project_subtype_id') ?? ($state['project_subtype_id'] ?? 0));

        DB::transaction(function () use ($companyId, $uid, $request, $state, $projectTypeId, $projectSubtypeId, $requisition) {
            DB::table('interior_requisition_master')
                ->where('company_id', $companyId)
                ->where('id', $requisition)
                ->update([
                    'project_address' => $request->input('project_address'),
                    'project_note' => $request->input('project_note'),
                    'project_budget' => $request->input('project_budget'),
                    'project_eta' => $request->input('project_eta'),
                    'project_type_id' => $projectTypeId ?: null,
                    'project_subtype_id' => $projectSubtypeId ?: null,
                    'updated_by' => $uid,
                    'updated_at' => now(),
                ]);

            if (is_array($state)) {
                $this->persistTreeFromState($companyId, $requisition, $state);
            }
        });

        return response()->json(['ok' => true]);
    }
}
