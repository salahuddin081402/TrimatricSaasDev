<?php

namespace App\Http\Controllers\Backend\Interior;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class InteriorParameterController extends Controller
{
    /* =========================================================
     |  Entity map (single-source of truth)
     |========================================================= */

    /**
     * Keys MUST match the {entity} route parameter.
     */
    private const ENTITIES = [
        'project_type' => [
            'table'        => 'cr_project_types',
            'label'        => 'Project Types',
            'code_column'  => 'code',
            'name_column'  => 'name',
            'image_column' => 'card_image_path',
            'code_max'     => 30,
            'name_max'     => 150,
        ],
        'project_subtype' => [
            'table'        => 'cr_project_subtypes',
            'label'        => 'Project Sub-Types',
            'code_column'  => 'code',
            'name_column'  => 'name',
            'image_column' => 'card_image_path',
            'code_max'     => 50,
            'name_max'     => 150,
        ],
        'space' => [
            'table'        => 'cr_spaces',
            'label'        => 'Spaces',
            'code_column'  => 'code',
            'name_column'  => 'name',
            'image_column' => 'card_image_path',
            'code_max'     => 50,
            'name_max'     => 150,
        ],
        'category' => [
            'table'        => 'cr_item_categories',
            'label'        => 'Item Categories',
            'code_column'  => 'code',
            'name_column'  => 'name',
            'image_column' => 'card_image_path',
            'code_max'     => 50,
            'name_max'     => 150,
        ],
        'subcategory' => [
            'table'        => 'cr_item_subcategories',
            'label'        => 'Item Sub-Categories',
            'code_column'  => 'code',
            'name_column'  => 'name',
            'image_column' => 'card_image_path',
            'code_max'     => 80,
            'name_max'     => 180,
        ],
        'product' => [
            'table'        => 'cr_products',
            'label'        => 'Products',
            'code_column'  => 'sku',
            'name_column'  => 'name',
            'image_column' => 'main_image_url',
            'code_max'     => 80,
            'name_max'     => 255,
        ],

        'space_category_mapping' => [
            'table'        => 'cr_space_category_mappings',
            'label'        => 'Space Categories Mappings',
            'code_column'  => 'space_id',
            'name_column'  => 'category_id',
            'image_column' => null,
            'code_max'     => 0,
            'name_max'     => 0,
        ],
    ];

    /* ===================== UTILITIES ===================== */

    private function currentUserId(): ?int
    {
        $forced = config('header.dev_force_user_id');

        // If dev forced user id is set, ALWAYS use it for this module.
        if (is_numeric($forced)) {
            return (int) $forced;
        }

        return Auth::id();
    }

    /** Resolve company by {company} slug | id | model or fallback from user */
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

        if (is_string($routeCompany) && $routeCompany !== '') {
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

    private function imageDisk(): string
    {
        return (string) config('interior_parameters.image_disk', 'public');
    }

    private function maxImageKB(): int
    {
        return (int) config('interior_parameters.max_image_kb', 2048);
    }

    private function allowedImageMimes(): array
    {
        $mimes = config('interior_parameters.image_mimes', ['jpg', 'jpeg', 'png', 'webp']);
        return is_array($mimes) && count($mimes) ? $mimes : ['jpg', 'jpeg', 'png', 'webp'];
    }

    /** Country record for a company (name, short_code). Neutral fallbacks if missing. */
    private function companyCountry(object $companyRow): object
    {
        $c = DB::table('countries')
            ->where('id', $companyRow->country_id)
            ->first(['name', 'short_code']);

        $name  = $c->name ?? 'global';
        $short = strtoupper($c->short_code ?? 'XX');

        return (object) ['name' => $name, 'short_code' => $short];
    }

    private function entityMeta(string $entity): array
    {
        $meta = self::ENTITIES[$entity] ?? null;
        abort_if(!$meta, 404, 'Unknown entity');
        $meta['key'] = $entity;

        return $meta;
    }

    /**
     * Resolve correct named route considering AppServiceProvider prefixing.
     */
    private function parametersRoute(string $suffix): string
    {
        $candidates = [
            'backend.interior.interior.parameters.' . $suffix,
            'backend.interior.parameters.' . $suffix,
            'interior.parameters.' . $suffix,
        ];

        foreach ($candidates as $name) {
            if (Route::has($name)) return $name;
        }

        return 'backend.interior.interior.parameters.' . $suffix;
    }

    /**
     * Final PUBLIC URL pattern:
     *   /storage/{country}/{company-slug}/images/parameter/interior/{table_name}/{file.ext}
     * DB stores disk-relative path only (no leading "/storage").
     */
    private function imageFolder(object $companyRow, string $tableName): string
    {
        $country     = $this->companyCountry($companyRow);
        $countrySlug = Str::slug(strtolower((string) $country->name));
        $base        = trim((string) config('interior_parameters.image_base_path', 'images/parameter/interior'), '/');

        $tableMap = config('interior_parameters.tables', []);
        $tableDir = is_array($tableMap) && isset($tableMap[$tableName]) ? (string) $tableMap[$tableName] : $tableName;

        return $countrySlug . '/' . $companyRow->slug . '/' . $base . '/' . $tableDir;
    }

    private function normalizeBool(Request $request, string $key): int
    {
        return $request->boolean($key) ? 1 : 0;
    }

    private function normalizeStoredPath(?string $path): ?string
    {
        $path = is_string($path) ? trim($path) : null;
        if (!$path) return null;

        $path = preg_replace('/\?.*$/', '', $path);

        if (Str::startsWith($path, '/storage/')) {
            return ltrim(Str::after($path, '/storage/'), '/');
        }
        if (Str::startsWith($path, 'storage/')) {
            return ltrim(Str::after($path, 'storage/'), '/');
        }

        return ltrim($path, '/');
    }

    /**
     * @return array [int $userId, object $companyRow, int $companyId]
     */
    private function ensureUserAndCompany($companyParam): array
    {
        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Login required');

        $companyRow = $this->resolveCompany($companyParam);
        abort_if(!$companyRow, 404, 'Company not found');

        $companyId = (int) $companyRow->id;

        return [$uid, $companyRow, $companyId];
    }

    private function nowTs(): string
    {
        return now()->toDateTimeString();
    }

    /* =========================================================
     |  IMPORTANT: Blade → Controller binding safety
     |========================================================= */

    /**
     * Normalizes common variants into canonical keys the controller validates on.
     */
    private function normalizeIncomingFields(Request $request, string $entity): void
    {
        if ($entity === 'space_category_mapping') { return; }

        $meta    = $this->entityMeta($entity);
        $codeCol = $meta['code_column'] ?? 'code';
        $nameCol = $meta['name_column'] ?? 'name';

        // Helpers
        $firstNonEmpty = function (array $candidates) use ($request) {
            foreach ($candidates as $k) {
                $v = $request->input($k);
                if (is_string($v)) {
                    $v = trim($v);
                    if ($v !== '') return $v;
                } elseif ($v !== null && $v !== '') {
                    return $v;
                }
            }
            return null;
        };

        $snake  = $entity;              // e.g. project_type
        $camel  = Str::camel($entity);  // projectType
        $studly = Str::studly($entity); // ProjectType

        // Canonical code/name
        $canonCode = $request->input($codeCol);
        $canonName = $request->input($nameCol);

        if (!is_string($canonCode) || trim($canonCode) === '') {
            $picked = $firstNonEmpty([
                $codeCol,
                'code',
                'sku',
                $snake . '.' . $codeCol,
                $snake . '.code',
                $snake . '.sku',
                $snake . '_' . $codeCol,
                $snake . '_code',
                $snake . '_sku',
                $camel . '.' . $codeCol,
                $camel . '.code',
                $camel . '.sku',
                $studly . '.' . $codeCol,
                $studly . '.code',
                $studly . '.sku',
                'pt_code', 'ps_code', 'sp_code', 'cat_code', 'subcat_code', 'prod_sku',
                'code_input', 'txt_code',
            ]);
            if ($picked !== null) {
                $request->merge([$codeCol => $picked]);
            }

            // For product: if UI sends 'code', map into 'sku' too
            if ($entity === 'product' && $request->input('sku') === null && $request->input('code') !== null) {
                $request->merge(['sku' => $request->input('code')]);
            }
        }

        if (!is_string($canonName) || trim($canonName) === '') {
            $picked = $firstNonEmpty([
                $nameCol,
                'name',
                $snake . '.' . $nameCol,
                $snake . '.name',
                $snake . '_' . $nameCol,
                $snake . '_name',
                $camel . '.' . $nameCol,
                $camel . '.name',
                $studly . '.' . $nameCol,
                $studly . '.name',
                'name_input', 'txt_name',
            ]);
            if ($picked !== null) {
                $request->merge([$nameCol => $picked]);
            }
        }

        // Common FK keys
        foreach (['project_type_id', 'project_subtype_id', 'category_id', 'subcategory_id'] as $fk) {
            $v       = $request->input($fk);
            $isBlank = ($v === null) || (is_string($v) && trim($v) === '');
            if ($isBlank) {
                $picked = $firstNonEmpty([
                    $fk,
                    $snake . '.' . $fk,
                    $snake . '_' . $fk,
                    $camel . '.' . $fk,
                    $camel . '_' . $fk,
                ]);
                if ($picked !== null) {
                    $request->merge([$fk => $picked]);
                }
            }
        }

        // Description
        if ($request->input('description') === null) {
            $picked = $firstNonEmpty([
                'description',
                $snake . '.description',
                $snake . '_description',
                $camel . '.description',
            ]);
            if ($picked !== null) {
                $request->merge(['description' => $picked]);
            }
        }

        // sort_order
        if ($request->input('sort_order') === null) {
            $picked = $firstNonEmpty([
                'sort_order',
                $snake . '.sort_order',
                $snake . '_sort_order',
                $camel . '.sort_order',
            ]);
            if ($picked !== null) {
                $request->merge(['sort_order' => $picked]);
            }
        }

        // product-specific fields
        if ($entity === 'product') {
            foreach (['short_description', 'specification', 'origin_country', 'default_tag', 'default_qty'] as $k) {
                if ($request->input($k) === null) {
                    $picked = $firstNonEmpty([
                        $k,
                        $snake . '.' . $k,
                        $snake . '_' . $k,
                        $camel . '.' . $k,
                    ]);
                    if ($picked !== null) {
                        $request->merge([$k => $picked]);
                    }
                }
            }
        }

        // space-specific
        if ($entity === 'space') {
            foreach (['default_quantity', 'default_area_sqft'] as $k) {
                if ($request->input($k) === null) {
                    $picked = $firstNonEmpty([
                        $k,
                        $snake . '.' . $k,
                        $snake . '_' . $k,
                        $camel . '.' . $k,
                    ]);
                    if ($picked !== null) {
                        $request->merge([$k => $picked]);
                    }
                }
            }
        }

        // is_active (checkbox variants)
        if ($request->input('is_active') === null) {
            $picked = $firstNonEmpty([
                'is_active',
                $snake . '.is_active',
                $snake . '_is_active',
                $camel . '.is_active',
            ]);
            if ($picked !== null) {
                $request->merge(['is_active' => $picked]);
            }
        }
    }

    /* =========================================================
     |  Index / view context
     |========================================================= */

    public function index(Request $request, $company)
    {
        [$uid, $companyRow, $companyId] = $this->ensureUserAndCompany($company);

        $activeEntity = (string) ($request->get('entity') ?? $request->get('_entity') ?? 'project_type');
        if (!isset(self::ENTITIES[$activeEntity])) {
            $activeEntity = 'project_type';
        }

        $lists = [];
        foreach (self::ENTITIES as $key => $meta) {
            $q = DB::table($meta['table'])->where('company_id', $companyId);
            if ($key === 'space_category_mapping') {
                $q = DB::table($meta['table'] . ' as m')
                    ->join('cr_spaces as sp', 'sp.id', '=', 'm.space_id')
                    ->join('cr_item_categories as cat', 'cat.id', '=', 'm.category_id')
                    ->where('m.company_id', $companyId)
                    ->select([
                        'm.*',
                        'sp.code as space_code',
                        'sp.name as space_name',
                        'cat.code as category_code',
                        'cat.name as category_name',
                    ]);
            }


            if (Schema::hasColumn($meta['table'], 'is_active')) {
                $q->orderByDesc('is_active');
            }
            if (Schema::hasColumn($meta['table'], 'sort_order')) {
                $q->orderBy('sort_order');
            }
            if (Schema::hasColumn($meta['table'], $meta['name_column'])) {
                $q->orderBy($meta['name_column']);
            }

            $lists[$key] = $q->limit(500)->get();
        }

        // Lookup datasets for FK dropdowns
        $projectTypes = DB::table('cr_project_types')
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $projectSubtypes = DB::table('cr_project_subtypes')
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $categories = DB::table('cr_item_categories')
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $subcategories = DB::table('cr_item_subcategories')
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('backend.interior.parameters.index', [
            'userId'          => $uid,
            'company'         => $companyRow,
            'entities'        => self::ENTITIES,
            'activeEntity'    => $activeEntity,
            'lists'           => $lists,
            'projectTypes'    => $projectTypes,
            'projectSubtypes' => $projectSubtypes,
            'categories'      => $categories,
            'subcategories'   => $subcategories,
            'maxImageKB'      => $this->maxImageKB(),
            'allowedMimes'    => $this->allowedImageMimes(),
            'editEntity'      => null,
            'editRow'         => null,
        ]);
    }

    public function data(Request $request, $company, string $entity)
    {
        [, , $companyId] = $this->ensureUserAndCompany($company);
        $meta   = $this->entityMeta($entity);
        $search = trim((string) $request->query('q', ''));
        $table  = $meta['table'];

        $query = DB::table($table)->where('company_id', $companyId);

        if ($search !== '') {
            $like    = '%' . $search . '%';
            $codeCol = $meta['code_column'] ?? 'code';
            $nameCol = $meta['name_column'] ?? 'name';

            $query->where(function ($q) use ($like, $table, $codeCol, $nameCol) {
                if (Schema::hasColumn($table, $codeCol)) {
                    $q->where($codeCol, 'like', $like);
                }
                if (Schema::hasColumn($table, $nameCol)) {
                    $q->orWhere($nameCol, 'like', $like);
                }
            });
        }

        if (Schema::hasColumn($table, 'is_active')) {
            $query->orderByDesc('is_active');
        }
        if (Schema::hasColumn($table, 'sort_order')) {
            $query->orderBy('sort_order');
        }
        if (Schema::hasColumn($table, $meta['name_column'])) {
            $query->orderBy($meta['name_column']);
        }

        $rows = $query->limit(500)->get();

        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function create(Request $request, $company, string $entity)
    {
        $request->merge(['entity' => $entity]);
        return $this->index($request, $company);
    }

    public function edit(Request $request, $company, string $entity, int $id)
    {
        [, , $companyId] = $this->ensureUserAndCompany($company);
        $meta = $this->entityMeta($entity);

        $row = null;

        if ($entity === 'space_category_mapping') {
            $row = DB::table($meta['table'] . ' as m')
                ->join('cr_spaces as sp', 'sp.id', '=', 'm.space_id')
                ->join('cr_item_categories as cat', 'cat.id', '=', 'm.category_id')
                ->where('m.company_id', $companyId)
                ->where('m.id', $id)
                ->select([
                    'm.*',
                    'sp.code as space_code',
                    'sp.name as space_name',
                    'cat.code as category_code',
                    'cat.name as category_name',
                ])
                ->first();
        } else {
            $row = DB::table($meta['table'])
                ->where('company_id', $companyId)
                ->where('id', $id)
                ->first();
        };

        abort_if(!$row, 404, 'Record not found');

        $imgCol = $meta['image_column'] ?? null;
        if ($imgCol && isset($row->{$imgCol})) {
            $row->{$imgCol} = $this->normalizeStoredPath((string) $row->{$imgCol});
        }

        $request->merge(['entity' => $entity]);

        $view = $this->index($request, $company);
        $view->with('editEntity', $entity);
        $view->with('editRow', $row);

        return $view;
    }

    /* =========================================================
     |  Store / Update
     |========================================================= */

    public function store(Request $request, $company, string $entity)
    {
        [, $companyRow, $companyId] = $this->ensureUserAndCompany($company);
        $meta = $this->entityMeta($entity);

        // Normalize incoming keys
        $this->normalizeIncomingFields($request, $entity);

        $rules    = $this->validationRules($request, $entity, null, $companyId, null);
        $messages = $this->validationMessages($entity);

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $imageColumn = $meta['image_column'] ?? null;

        try {
            DB::beginTransaction();

            $payload               = $this->buildPayload($entity, $request, $companyId, null);
            $payload['company_id'] = $companyId;
            $payload['created_at'] = $this->nowTs();
            $payload['updated_at'] = $this->nowTs();

            if ($imageColumn) {
                $payload[$imageColumn] = $this->persistImageValue(
                    $request,
                    $companyRow,
                    $meta['table'],
                    $imageColumn,
                    null
                );
            }

            $id = DB::table($meta['table'])->insertGetId($payload);

            DB::commit();

            return redirect()
                ->route($this->parametersRoute('index'), [
                    'company' => $companyRow->slug,
                    'entity'  => $entity,
                ])
                ->with('success', $meta['label'] . ' record created successfully.')
                ->with('created_id', $id);
        } catch (QueryException $e) {
            DB::rollBack();

            $msg = $this->translateQueryException($e, $entity, 'create');
            return back()->withInput()->with('error', $msg);
        }
    }

    public function update(Request $request, $company, string $entity, int $id)
    {  //  dd('HIT InteriorParameterController::update', $entity, $id);
        [, $companyRow, $companyId] = $this->ensureUserAndCompany($company);
        $meta = $this->entityMeta($entity);

        $existing = DB::table($meta['table'])
            ->where('company_id', $companyId)
            ->where('id', $id)
            ->first();

        abort_if(!$existing, 404, 'Record not found');

        // Normalize existing image path
        $imageColumn = $meta['image_column'] ?? null;
        $oldPath     = $imageColumn ? $this->normalizeStoredPath($existing->{$imageColumn} ?? null) : null;
        if ($imageColumn && $oldPath !== null) {
            $existing->{$imageColumn} = $oldPath;
        }

        // Always carry existing image path forward if no new file is uploaded.
        if ($imageColumn && !$request->hasFile($imageColumn)) {
            $request->merge([
                $imageColumn => $existing->{$imageColumn} ?? null,
            ]);
        }

        // Normalize incoming fields (code/name/FK etc.)
        $this->normalizeIncomingFields($request, $entity);

        // Build UPDATE rules (no unique for code/SKU on update)
        $rules    = $this->validationRules($request, $entity, $id, $companyId, $existing);
        $messages = $this->validationMessages($entity);

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $payload               = $this->buildPayload($entity, $request, $companyId, $existing);
            $payload['updated_at'] = $this->nowTs();

            if ($imageColumn) {
                $payload[$imageColumn] = $this->persistImageValue(
                    $request,
                    $companyRow,
                    $meta['table'],
                    $imageColumn,
                    $existing->{$imageColumn} ?? null
                );
            }

            DB::table($meta['table'])
                ->where('company_id', $companyId)
                ->where('id', $id)
                ->update($payload);

            DB::commit();

            return redirect()
                ->route($this->parametersRoute('index'), [
                    'company' => $companyRow->slug,
                    'entity'  => $entity,
                ])
                ->with('success', $meta['label'] . ' record updated successfully.')
                ->with('updated_id', $id);
        } catch (QueryException $e) {
            DB::rollBack();

            $msg = $this->translateQueryException($e, $entity, 'update');
            return back()->withInput()->with('error', $msg);
        }
    }

    /* =========================================================
     |  Delete
     |========================================================= */

    public function destroy(Request $request, $company, string $entity, int $id)
    {
        [, , $companyId] = $this->ensureUserAndCompany($company);
        $meta = $this->entityMeta($entity);

        $row = null;

        if ($entity === 'space_category_mapping') {
            $row = DB::table($meta['table'] . ' as m')
                ->join('cr_spaces as sp', 'sp.id', '=', 'm.space_id')
                ->join('cr_item_categories as cat', 'cat.id', '=', 'm.category_id')
                ->where('m.company_id', $companyId)
                ->where('m.id', $id)
                ->select([
                    'm.*',
                    'sp.code as space_code',
                    'sp.name as space_name',
                    'cat.code as category_code',
                    'cat.name as category_name',
                ])
                ->first();
        } else {
            $row = DB::table($meta['table'])
                ->where('company_id', $companyId)
                ->where('id', $id)
                ->first();
        };

        if (!$row) {
            return response()->json(['ok' => false, 'message' => 'Record not found.'], 404);
        }

        $disk        = $this->imageDisk();
        $imageColumn = $meta['image_column'] ?? null;
        $oldPath     = $imageColumn ? $this->normalizeStoredPath($row->{$imageColumn} ?? null) : null;

        try {
            DB::beginTransaction();

            DB::table($meta['table'])
                ->where('company_id', $companyId)
                ->where('id', $id)
                ->delete();

            if ($oldPath) {
                Storage::disk($disk)->delete($oldPath);
            }

            DB::commit();

            return response()->json(['ok' => true]);
        } catch (QueryException $e) {
            DB::rollBack();

            if ((string) $e->getCode() === '23000') {
                return response()->json([
                    'ok'      => false,
                    'message' => 'This record is already used by another module. Cannot be deleted.',
                ], 409);
            }

            return response()->json(['ok' => false, 'message' => 'Failed to delete record.'], 500);
        }
    }

    /* =========================================================
     |  Standalone image upload (AJAX preview)
     |========================================================= */

    public function uploadImage(Request $request, $company, string $entity)
    {
        [, $companyRow] = $this->ensureUserAndCompany($company);
        $meta        = $this->entityMeta($entity);
        $imageColumn = $meta['image_column'] ?? null;

        abort_if(!$imageColumn, 400, 'Image not supported for this entity.');

        $mimes = implode(',', $this->allowedImageMimes());
        $maxKB = $this->maxImageKB();

        $request->validate([
            $imageColumn => ['required', 'file', 'mimes:' . $mimes, 'max:' . $maxKB],
        ], [
            $imageColumn . '.required' => 'Please choose an image file.',
            $imageColumn . '.mimes'    => 'Allowed image types: ' . strtoupper(str_replace(',', ', ', $mimes)) . '.',
            $imageColumn . '.max'      => 'Image size must be within ' . $maxKB . ' KB.',
        ]);

        $path = $this->persistImageFileOnly($request, $companyRow, $meta['table'], $imageColumn);

        return response()->json([
            'ok'   => true,
            'path' => $path,
            'url'  => Storage::disk($this->imageDisk())->url($path),
        ]);
    }

    /* =========================================================
     |  Validation + payload builders per entity
     |========================================================= */

    private function validationRules(
        Request $request,
        string  $entity,
        ?int    $id,
        int     $companyId,
        ?object $existing = null
    ): array {
        $meta  = $this->entityMeta($entity);
        $table = $meta['table'];

        $mimes = implode(',', $this->allowedImageMimes());
        $maxKB = $this->maxImageKB();

        $codeCol = $meta['code_column'] ?? 'code';
        $nameCol = $meta['name_column'] ?? 'name';

        $isUpdate = $id !== null;

        switch ($entity) {
            case 'project_type':
                if ($isUpdate) {
                    // UPDATE: no unique on code
                    $codeRules = ['required', 'string', 'max:' . $meta['code_max']];
                } else {
                    $codeRules = ['required', 'string', 'max:' . $meta['code_max']];
                    $codeRules[] = Rule::unique($table, $codeCol)
                        ->where(function ($q) use ($companyId) {
                            $q->where('company_id', $companyId);
                        });
                }

                $rules = [
                    $codeCol      => $codeRules,
                    $nameCol      => ['required', 'string', 'max:' . $meta['name_max']],
                    'description' => ['nullable', 'string'],
                    'sort_order'  => ['nullable', 'integer', 'min:0', 'max:100000'],
                    'is_active'   => ['nullable'],
                ];
                break;

            case 'project_subtype':
                if ($isUpdate) {
                    $codeRules = ['required', 'string', 'max:' . $meta['code_max']];
                } else {
                    $codeRules = ['required', 'string', 'max:' . $meta['code_max']];
                    $codeRules[] = Rule::unique($table, $codeCol)
                        ->where(function ($q) use ($companyId, $request) {
                            $q->where('company_id', $companyId)
                              ->where('project_type_id', (int) $request->input('project_type_id'));
                        });
                }

                $rules = [
                    'project_type_id' => [
                        'required', 'integer', 'min:1',
                        Rule::exists('cr_project_types', 'id')
                            ->where(function ($q) use ($companyId) {
                                $q->where('company_id', $companyId);
                            }),
                    ],
                    $codeCol          => $codeRules,
                    $nameCol          => ['required', 'string', 'max:' . $meta['name_max']],
                    'description'     => ['nullable', 'string'],
                    'sort_order'      => ['nullable', 'integer', 'min:0', 'max:100000'],
                    'is_active'       => ['nullable'],
                ];
                break;

            case 'space':
                if ($isUpdate) {
                    $codeRules = ['required', 'string', 'max:' . $meta['code_max']];
                } else {
                    $codeRules = ['required', 'string', 'max:' . $meta['code_max']];
                    $codeRules[] = Rule::unique($table, $codeCol)
                        ->where(function ($q) use ($companyId, $request) {
                            $q->where('company_id', $companyId)
                              ->where('project_subtype_id', (int) $request->input('project_subtype_id'));
                        });
                }

                $rules = [
                    'project_subtype_id' => [
                        'required', 'integer', 'min:1',
                        Rule::exists('cr_project_subtypes', 'id')
                            ->where(function ($q) use ($companyId) {
                                $q->where('company_id', $companyId);
                            }),
                    ],
                    $codeCol             => $codeRules,
                    $nameCol             => ['required', 'string', 'max:' . $meta['name_max']],
                    'description'        => ['nullable', 'string'],
                    'default_quantity'   => ['nullable', 'integer', 'min:0', 'max:100000'],
                    'default_area_sqft'  => ['nullable', 'numeric', 'min:0'],
                    'sort_order'         => ['nullable', 'integer', 'min:0', 'max:100000'],
                    'is_active'          => ['nullable'],
                ];
                break;

            case 'category':
                if ($isUpdate) {
                    $codeRules = ['required', 'string', 'max:' . $meta['code_max']];
                } else {
                    $codeRules = ['required', 'string', 'max:' . $meta['code_max']];
                    $codeRules[] = Rule::unique($table, $codeCol)
                        ->where(function ($q) use ($companyId) {
                            $q->where('company_id', $companyId);
                        });
                }

                $rules = [
                    $codeCol      => $codeRules,
                    $nameCol      => ['required', 'string', 'max:' . $meta['name_max']],
                    'description' => ['nullable', 'string'],
                    'sort_order'  => ['nullable', 'integer', 'min:0', 'max:100000'],
                    'is_active'   => ['nullable'],
                ];
                break;

            case 'subcategory':
                if ($isUpdate) {
                    $codeRules = ['required', 'string', 'max:' . $meta['code_max']];
                } else {
                    $codeRules = ['required', 'string', 'max:' . $meta['code_max']];
                    $codeRules[] = Rule::unique($table, $codeCol)
                        ->where(function ($q) use ($companyId, $request) {
                            $q->where('company_id', $companyId)
                              ->where('category_id', (int) $request->input('category_id'));
                        });
                }

                $rules = [
                    'category_id' => [
                        'required', 'integer', 'min:1',
                        Rule::exists('cr_item_categories', 'id')
                            ->where(function ($q) use ($companyId) {
                                $q->where('company_id', $companyId);
                            }),
                    ],
                    $codeCol      => $codeRules,
                    $nameCol      => ['required', 'string', 'max:' . $meta['name_max']],
                    'description' => ['nullable', 'string'],
                    'sort_order'  => ['nullable', 'integer', 'min:0', 'max:100000'],
                    'is_active'   => ['nullable'],
                ];
                break;

            
            case 'space_category_mapping':
                $rules = [
                    'space_id' => [
                        'required','integer','min:1',
                        Rule::exists('cr_spaces', 'id')
                            ->where(function ($q) use ($companyId) { $q->where('company_id', $companyId); }),
                    ],
                    'category_id' => [
                        'required','integer','min:1',
                        Rule::exists('cr_item_categories', 'id')
                            ->where(function ($q) use ($companyId) { $q->where('company_id', $companyId); }),
                    ],
                    'is_active'  => ['nullable','boolean'],
                    'sort_order' => ['nullable','integer','min:0'],
                ];

                // prevent duplicates per tenant
                $unique = Rule::unique('cr_space_category_mappings', 'category_id')
                    ->where(function ($q) use ($companyId, $request) {
                        $q->where('company_id', $companyId)
                          ->where('space_id', (int) $request->input('space_id'));
                    });

                if ($isUpdate && $id) {
                    $unique->ignore($id, 'id');
                }

                $rules['category_id'][] = $unique;

                return $rules;

case 'product':
                if ($isUpdate) {
                    $codeRules = ['required', 'string', 'max:' . $meta['code_max']];
                } else {
                    $codeRules = ['required', 'string', 'max:' . $meta['code_max']];
                    $codeRules[] = Rule::unique($table, $codeCol)
                        ->where(function ($q) use ($companyId) {
                            $q->where('company_id', $companyId);
                        });
                }

                $rules = [
                    'subcategory_id'    => [
                        'required', 'integer', 'min:1',
                        Rule::exists('cr_item_subcategories', 'id')
                            ->where(function ($q) use ($companyId) {
                                $q->where('company_id', $companyId);
                            }),
                    ],
                    $codeCol            => $codeRules,
                    $nameCol            => ['required', 'string', 'max:' . $meta['name_max']],
                    'short_description' => ['nullable', 'string', 'max:500'],
                    'specification'     => ['nullable', 'string'],
                    'origin_country'    => ['nullable', 'string', 'max:120'],
                    'default_tag'       => ['nullable', Rule::in(['preferred', 'standard', 'value', 'premium'])],
                    'default_qty'       => ['nullable', 'integer', 'min:1', 'max:100000'],
                    'sort_order'        => ['nullable', 'integer', 'min:0', 'max:100000'],
                    'is_active'         => ['nullable'],
                ];
                break;

            default:
                $rules = [];
        }

        // Image rule: create vs update
        $imageColumn = $meta['image_column'] ?? null;
        if ($imageColumn) {
            if ($request->hasFile($imageColumn)) {
                $rules[$imageColumn] = ['nullable', 'file', 'mimes:' . $mimes, 'max:' . $maxKB];
            } else {
                if ($isUpdate) {
                    // UPDATE: only type/length check, do NOT verify disk existence
                    $rules[$imageColumn] = ['nullable', 'string', 'max:500'];
                } else {
                    // CREATE: allow path but verify file exists
                    $rules[$imageColumn] = [
                        'nullable', 'string', 'max:500',
                        function ($attribute, $value, $fail) {
                            $value = $this->normalizeStoredPath(is_string($value) ? $value : null);
                            if (!$value) {
                                return;
                            }

                            if (!Storage::disk($this->imageDisk())->exists($value)) {
                                $fail('The selected image reference is invalid. Please upload/select the image again.');
                            }
                        },
                    ];
                }
            }
        }

        return $rules;
    }

    private function validationMessages(string $entity): array
    {
        $meta    = $this->entityMeta($entity);
        $codeCol = $meta['code_column'] ?? 'code';
        $nameCol = $meta['name_column'] ?? 'name';

        $m = [
            $codeCol . '.required' => ($codeCol === 'sku') ? 'SKU is required.' : 'Code is required.',
            $codeCol . '.unique'   => ($codeCol === 'sku') ? 'This SKU is already in use.' : 'This code is already in use.',
            $nameCol . '.required' => 'Name is required.',
        ];

        // Legacy keys for blades using plain "code"/"name"
        $m['code.required'] = 'Code is required.';
        $m['code.unique']   = 'This code is already in use.';
        $m['name.required'] = 'Name is required.';

        $m['project_type_id.required']    = 'Project type is required.';
        $m['project_type_id.exists']      = 'Selected project type is invalid for this company.';
        $m['project_subtype_id.required'] = 'Project sub-type is required.';
        $m['project_subtype_id.exists']   = 'Selected project sub-type is invalid for this company.';
        $m['category_id.required']        = 'Category is required.';
        $m['category_id.exists']          = 'Selected category is invalid for this company.';
        $m['subcategory_id.required']     = 'Sub-category is required.';
        $m['subcategory_id.exists']       = 'Selected sub-category is invalid for this company.';
        $m['default_tag.in']              = 'Default tag must be one of: preferred, standard, value, premium.';

        return $m;
    }

    private function buildPayload(string $entity, Request $request, int $companyId, ?object $existing): array
    {
        $sort      = $request->input('sort_order');
        $sortOrder = ($sort !== null && $sort !== '') ? (int) $sort : 0;

        $isUpdate = $existing !== null;

        $code = trim((string) ($request->input('code') ?? ''));
        $name = trim((string) ($request->input('name') ?? ''));
        $sku  = trim((string) ($request->input('sku') ?? ''));

        // For update: keep existing code/sku if not posted / blank
        if ($isUpdate) {
            if ($code === '' && isset($existing->code)) {
                $code = trim((string) $existing->code);
            }
            if ($sku === '' && isset($existing->sku)) {
                $sku = trim((string) $existing->sku);
            }
        }

        switch ($entity) {
            case 'project_type':
                return [
                    'code'        => $code,
                    'name'        => $name,
                    'description' => $request->input('description'),
                    'is_active'   => $this->normalizeBool($request, 'is_active'),
                    'sort_order'  => $sortOrder,
                ];

            case 'project_subtype':
                return [
                    'project_type_id' => (int) $request->input('project_type_id'),
                    'code'            => $code,
                    'name'            => $name,
                    'description'     => $request->input('description'),
                    'is_active'       => $this->normalizeBool($request, 'is_active'),
                    'sort_order'      => $sortOrder,
                ];

            case 'space':
                return [
                    'project_subtype_id' => (int) $request->input('project_subtype_id'),
                    'code'               => $code,
                    'name'               => $name,
                    'description'        => $request->input('description'),
                    'default_quantity'   => ($request->input('default_quantity') !== null && $request->input('default_quantity') !== '')
                        ? (int) $request->input('default_quantity')
                        : null,
                    'default_area_sqft'  => ($request->input('default_area_sqft') !== null && $request->input('default_area_sqft') !== '')
                        ? (float) $request->input('default_area_sqft')
                        : null,
                    'is_active'          => $this->normalizeBool($request, 'is_active'),
                    'sort_order'         => $sortOrder,
                ];

            case 'category':
                return [
                    'code'        => $code,
                    'name'        => $name,
                    'description' => $request->input('description'),
                    'is_active'   => $this->normalizeBool($request, 'is_active'),
                    'sort_order'  => $sortOrder,
                ];

            case 'subcategory':
                return [
                    'category_id' => (int) $request->input('category_id'),
                    'code'        => $code,
                    'name'        => $name,
                    'description' => $request->input('description'),
                    'is_active'   => $this->normalizeBool($request, 'is_active'),
                    'sort_order'  => $sortOrder,
                ];

            
            case 'space_category_mapping':
                return [
                    'space_id'    => (int) $request->input('space_id'),
                    'category_id' => (int) $request->input('category_id'),
                    'is_active'   => $this->normalizeBool($request, 'is_active'),
                    'sort_order'  => $sortOrder,
                ];

case 'product':
                $qty        = $request->input('default_qty');
                $defaultQty = ($qty !== null && $qty !== '') ? (int) $qty : 1;

                return [
                    'subcategory_id'    => (int) $request->input('subcategory_id'),
                    'sku'               => ($sku !== '' ? $sku : $code),
                    'name'              => $name,
                    'short_description' => $request->input('short_description'),
                    'specification'     => $request->input('specification'),
                    'origin_country'    => $request->input('origin_country'),
                    'default_tag'       => $request->input('default_tag'),
                    'default_qty'       => $defaultQty,
                    'is_active'         => $this->normalizeBool($request, 'is_active'),
                    'sort_order'        => $sortOrder,
                ];

            default:
                return [];
        }
    }

    /* =========================================================
     |  Image persistence
     |========================================================= */

    private function persistImageValue(
        Request $request,
        object  $companyRow,
        string  $table,
        string  $imageColumn,
        ?string $existingPath
    ): ?string {
        $disk         = $this->imageDisk();
        $existingPath = $this->normalizeStoredPath($existingPath);

        if ($request->hasFile($imageColumn)) {
            return $this->persistUploadedFileReplacingOld($request, $companyRow, $table, $imageColumn, $existingPath);
        }

        $posted = $this->normalizeStoredPath($request->input($imageColumn));
        if ($posted && $posted !== $existingPath) {
            $expectedPrefix = rtrim($this->imageFolder($companyRow, $table), '/') . '/';
            if (!Str::startsWith($posted, $expectedPrefix)) {
                return $existingPath;
            }
            if (!Storage::disk($disk)->exists($posted)) {
                return $existingPath;
            }

            if ($existingPath && $existingPath !== $posted) {
                Storage::disk($disk)->delete($existingPath);
            }

            return $posted;
        }

        return $existingPath;
    }

    private function persistUploadedFileReplacingOld(
        Request $request,
        object  $companyRow,
        string  $table,
        string  $imageColumn,
        ?string $existingPath
    ): ?string {
        $disk = $this->imageDisk();

        $file = $request->file($imageColumn);
        if (!$file || !$file->isValid()) {
            return $existingPath;
        }

        if ($existingPath) {
            Storage::disk($disk)->delete($existingPath);
        }

        $folder = $this->imageFolder($companyRow, $table);
        Storage::disk($disk)->makeDirectory($folder);

        $ext      = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $origName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: $table;

        $base     = Str::slug($origName) ?: ($table . '-' . time());
        $filename = $base . '-' . date('YmdHis') . '-' . Str::random(6) . '.' . $ext;

        return $file->storeAs($folder, $filename, $disk);
    }

    private function persistImageFileOnly(
        Request $request,
        object  $companyRow,
        string  $table,
        string  $imageColumn
    ): string {
        $disk = $this->imageDisk();

        $file = $request->file($imageColumn);
        abort_if(!$file || !$file->isValid(), 422, 'Invalid image upload.');

        $folder = $this->imageFolder($companyRow, $table);
        Storage::disk($disk)->makeDirectory($folder);

        $ext      = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $origName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: $table;

        $base     = Str::slug($origName) ?: ($table . '-' . time());
        $filename = $base . '-' . date('YmdHis') . '-' . Str::random(6) . '.' . $ext;

        return $file->storeAs($folder, $filename, $disk);
    }

    /* =========================================================
     |  DB error translation
     |========================================================= */

    private function translateQueryException(QueryException $e, string $entity, string $action): string
    {
        if ((string) $e->getCode() === '23000') {
            $meta    = $this->entityMeta($entity);
            $codeCol = $meta['code_column'] ?? 'code';
            if ($codeCol === 'sku') {
                return 'Operation failed: duplicate SKU or the record is in use.';
            }
            return 'Operation failed: duplicate Code or the record is in use.';
        }

        $label = $this->entityMeta($entity)['label'] ?? 'record';
        return 'Failed to ' . $action . ' ' . $label . '.';
    }
}
