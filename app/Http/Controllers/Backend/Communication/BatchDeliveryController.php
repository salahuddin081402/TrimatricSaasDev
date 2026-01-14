<?php
// app/Http/Controllers/Backend/Communication/BatchDeliveryController.php

namespace App\Http\Controllers\Backend\Communication;

use App\Http\Controllers\Controller;
use App\Jobs\Communication\SendBatchMessageToRecipientJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Bus\Batch;
use Throwable;

class BatchDeliveryController extends Controller
{
    /* ===================== UTILITIES (DO NOT CHANGE) ===================== */

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

    /* ===================== PAGES ===================== */

    public function index(Request $request, $company)
    {
        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');

                $skills = DB::table('skills')
            ->where('status', 1)
            ->orderBy('skill')
            ->get(['id', 'skill']);

        return view('backend.communication.batch_delivery.index', [
            'companyRow' => $companyRow,
            'skills' => $skills,
        ]);
    }

    public function history(Request $request, $company)
    {
        // Same single screen; blade can default to History pane via JS if you want.
        return $this->index($request, $company);
    }

    /* ===================== GEO APIs ===================== */

    public function geoDivisions(Request $request, $company)
    {
        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');

        $rows = DB::table('divisions')
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'ok' => true,
            'items' => $rows->map(fn($r) => [
                'id' => (int) $r->id,
                'text' => (string) $r->name,
            ])->values(),
        ]);
    }

    public function geoDistricts(Request $request, $company)
    {
        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');

        $divisionId = $request->query('division_id');

        $q = DB::table('districts')
            ->where('status', 1);

        if (is_numeric($divisionId)) {
            $q->where('division_id', (int) $divisionId);
        }

        $rows = $q->orderBy('name')->get(['id', 'division_id', 'name']);

        return response()->json([
            'ok' => true,
            'items' => $rows->map(fn($r) => [
                'id' => (int) $r->id,
                'division_id' => (int) $r->division_id,
                'text' => (string) $r->name,
            ])->values(),
        ]);
    }

    public function geoClusters(Request $request, $company)
    {
        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');

        $districtId = $request->query('district_id');

        $q = DB::table('cluster_masters')
            ->where('company_id', (int) $companyRow->id)
            ->where('status', 1);

        if (is_numeric($districtId)) {
            $q->where('district_id', (int) $districtId);
        }

        $rows = $q->orderBy('cluster_name')->get(['id', 'cluster_name']);

        return response()->json([
            'ok' => true,
            'items' => $rows->map(fn($r) => [
                'id' => (int) $r->id,
                // UI label: "ID - NAME", value: id
                'text' => ((int) $r->id) . ' - ' . (string) $r->cluster_name,
            ])->values(),
        ]);
    }

    /* ===================== CLIENT & ENTREPRENEUR (PHASE-1) ===================== */

    public function estimateClientEntrepreneur(Request $request, $company)
    {
        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');

        $v = Validator::make($request->all(), [
            'division_id' => 'nullable',
            'district_id' => 'nullable',
            'cluster_id'  => 'nullable',
            'registration_type' => 'nullable|string', // client/company_officer/professional/entrepreneur/enterprise_client or null/ALL
            'created_from' => 'nullable|date',
            'created_to'   => 'nullable|date',
            'age_range'    => 'nullable|string|max:10',
            'status'       => 'nullable|string',
            'skill_id'     => 'nullable', // your UI can use: active/inactive/all
            'skill_id'     => 'nullable',
        ]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'errors' => $v->errors()], 422);
        }

        $q = $this->buildRecipientsQuery($companyRow->id, $request);

        $total = (clone $q)->count();

        $rows = (clone $q)
            ->orderByDesc('rm.id')
            ->limit(50)
            ->get();

        $preview = $rows->map(fn($r) => [
                'user_id' => (int) ($r->user_id ?? 0),
                'name' => (string) ($r->full_name ?? ''),
                'phone' => (string) ($r->phone ?? ''),
                'email' => (string) ($r->email ?? ''),
                'registration_type' => (string) ($r->registration_type ?? ''),
                'cluster_label' => (string) ($r->cluster_label ?? ''),
            ])->values();

        return response()->json([
            'ok' => true,
            'total' => (int) $total,
            'preview' => $preview,
            'rows' => $preview,
        ]);
    }

    public function dispatchClientEntrepreneur(Request $request, $company)
    {
        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');

        $uid = $this->currentUserId();
        abort_if(!$uid, 403, 'Unauthorized');

        $v = Validator::make($request->all(), [
            'division_id' => 'nullable',
            'district_id' => 'nullable',
            'cluster_id'  => 'nullable',
            'registration_type' => 'nullable|string',

            'created_from' => 'nullable|date',
            'created_to'   => 'nullable|date',
            'age_range'    => 'nullable|string|max:10',
            'status'       => 'nullable|string',
            'skill_id'     => 'nullable',

            'send_sms'   => 'required|boolean',
            'send_email' => 'required|boolean',

            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
            'extra_message' => 'nullable|string',
        ]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'errors' => $v->errors()], 422);
        }

        $sendSms = (bool) $request->boolean('send_sms');
        $sendEmail = (bool) $request->boolean('send_email');
        if (!$sendSms && !$sendEmail) {
            return response()->json(['ok' => false, 'message' => 'Select at least one channel (SMS/Email).'], 422);
        }
        if ($sendEmail && trim((string) $request->input('subject', '')) === '') {
            return response()->json(['ok' => false, 'message' => 'Subject is required for Email.'], 422);
        }

        $companyId = (int) $companyRow->id;

        // Build recipients
        $recipients = $this->buildRecipientsQuery($companyId, $request)->get();
        $total = $recipients->count();

        if ($total <= 0) {
            return response()->json(['ok' => false, 'message' => 'No recipients found for the selected filters.'], 422);
        }
        if ($total > 10000) {
            return response()->json(['ok' => false, 'message' => 'Too many recipients. Max allowed is 10,000.'], 422);
        }

        $batchNo = (string) Str::uuid();

        $payload = [
            'division_id' => $request->input('division_id'),
            'district_id' => $request->input('district_id'),
            'cluster_id'  => $request->input('cluster_id'),
            'registration_type' => $request->input('registration_type'),
            'created_from' => $request->input('created_from'),
            'created_to'   => $request->input('created_to'),
            'status'       => $request->input('status'),
        ];

        DB::transaction(function () use ($companyId, $uid, $batchNo, $sendSms, $sendEmail, $total, $payload, $request) {
            DB::table('communication_batches')->insert([
                'company_id' => $companyId,
                'batch_no' => $batchNo,
                'target_group' => 'client_entrepreneur',
                'send_sms' => $sendSms ? 1 : 0,
                'send_email' => $sendEmail ? 1 : 0,
                'subject' => $request->input('subject'),
                'message' => $request->input('message'),
                'extra_message' => $request->input('extra_message'),

                'total_recipients' => $total,
                'processed_count' => 0,
                'success_count' => 0,
                'failed_count' => 0,

                'status' => 'PENDING',
                'started_at' => null,
                'completed_at' => null,
                'is_cancel_requested' => 0,

                'created_by' => $uid,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        // Jobs: one per recipient (job will send SMS/Email based on batch flags)
        $jobs = [];
        foreach ($recipients as $r) {
            $jobs[] = new SendBatchMessageToRecipientJob(
                $companyId,
                $batchNo,
                (int) ($r->user_id ?? 0),
                (string) ($r->full_name ?? ''),
                (string) ($r->phone ?? ''),
                (string) ($r->email ?? '')
            );
        }

        $queueName = 'batch_delivery';

        Bus::batch($jobs)
            ->onQueue($queueName)
            ->name("BatchDelivery {$batchNo}")
            ->allowFailures()
            ->then(function (Batch $batch) use ($companyId, $batchNo) {
                // Mark complete (if not cancelled)
                DB::table('communication_batches')
                    ->where('company_id', $companyId)
                    ->where('batch_no', $batchNo)
                    ->whereNotIn('status', ['CANCELLED'])
                    ->update([
                        'status' => 'COMPLETED',
                        'completed_at' => now(),
                        'updated_at' => now(),
                    ]);
            })
            ->catch(function (Batch $batch, Throwable $e) use ($companyId, $batchNo) {
                DB::table('communication_batches')
                    ->where('company_id', $companyId)
                    ->where('batch_no', $batchNo)
                    ->update([
                        'status' => 'FAILED',
                        'completed_at' => now(),
                        'updated_at' => now(),
                    ]);
            })
            ->finally(function (Batch $batch) use ($companyId, $batchNo) {
                // If still RUNNING/PENDING and processed == total, make it COMPLETED (safety)
                $row = DB::table('communication_batches')
                    ->where('company_id', $companyId)
                    ->where('batch_no', $batchNo)
                    ->first();

                if ($row && in_array($row->status, ['PENDING', 'RUNNING'], true) && (int)$row->processed_count >= (int)$row->total_recipients) {
                    DB::table('communication_batches')
                        ->where('company_id', $companyId)
                        ->where('batch_no', $batchNo)
                        ->update([
                            'status' => 'COMPLETED',
                            'completed_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            })
            ->dispatch();

        // set RUNNING + started_at immediately (UX)
        DB::table('communication_batches')
            ->where('company_id', $companyId)
            ->where('batch_no', $batchNo)
            ->update([
                'status' => 'RUNNING',
                'started_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json([
            'ok' => true,
            'batch_no' => $batchNo,
            'total_recipients' => $total,
        ]);
    }

    public function batchStatus(Request $request, $company, $batch_no)
    {
        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');

        $row = DB::table('communication_batches')
            ->where('company_id', (int) $companyRow->id)
            ->where('batch_no', $batch_no)
            ->first();

        if (!$row) {
            return response()->json(['ok' => false, 'message' => 'Batch not found'], 404);
        }

        $total = (int) $row->total_recipients;
        $processed = (int) $row->processed_count;
        $pct = $total > 0 ? (int) floor(($processed / $total) * 100) : 0;

        return response()->json([
            'ok' => true,
            'batch' => [
                'batch_no' => $row->batch_no,
                'target_group' => $row->target_group,
                'send_sms' => (bool) $row->send_sms,
                'send_email' => (bool) $row->send_email,
                'status' => $row->status,
                'is_cancel_requested' => (bool) $row->is_cancel_requested,
                'total_recipients' => $total,
                'processed_count' => $processed,
                'success_count' => (int) $row->success_count,
                'failed_count' => (int) $row->failed_count,
                'started_at' => $row->started_at,
                'completed_at' => $row->completed_at,
            ],
            'progress' => [
                'percent' => $pct,
            ],
        ]);
    }

    public function cancelBatch(Request $request, $company, $batch_no)
    {
        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');

        $companyId = (int) $companyRow->id;

        $row = DB::table('communication_batches')
            ->where('company_id', $companyId)
            ->where('batch_no', $batch_no)
            ->first();

        if (!$row) {
            return response()->json(['ok' => false, 'message' => 'Batch not found'], 404);
        }

        // Mark cancelled; jobs will stop immediately
        DB::table('communication_batches')
            ->where('company_id', $companyId)
            ->where('batch_no', $batch_no)
            ->update([
                'is_cancel_requested' => 1,
                'status' => 'CANCELLED',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);

        return response()->json(['ok' => true, 'batch_no' => $batch_no]);
    }

    /* ===================== HISTORY (PHASE-1) ===================== */

    public function historyData(Request $request, $company)
    {
        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        $q = DB::table('communication_batches')
            ->where('company_id', $companyId);

        if ($request->filled('batch_no')) {
            $q->where('batch_no', 'like', '%' . trim((string)$request->input('batch_no')) . '%');
        }
        if ($request->filled('status')) {
            $q->where('status', (string) $request->input('status'));
        }
        if ($request->filled('type')) {
            // type: SMS/EMAIL/BOTH
            $t = strtoupper((string) $request->input('type'));
            if ($t === 'SMS') $q->where('send_sms', 1)->where('send_email', 0);
            if ($t === 'EMAIL') $q->where('send_sms', 0)->where('send_email', 1);
            if ($t === 'BOTH') $q->where('send_sms', 1)->where('send_email', 1);
        }

        $rows = $q->orderByDesc('created_at')->limit(200)->get();

        return response()->json([
            'ok' => true,
            'rows' => $rows->map(function ($r) {
                $type = ($r->send_sms && $r->send_email) ? 'BOTH' : (($r->send_sms) ? 'SMS' : 'EMAIL');
                return [
                    'batch_no' => $r->batch_no,
                    'type' => $type,
                    'target_group' => $r->target_group,
                    'total' => (int) $r->total_recipients,
                    'processed' => (int) $r->processed_count,
                    'success' => (int) $r->success_count,
                    'failed' => (int) $r->failed_count,
                    'status' => $r->status,
                    'created_at' => $r->created_at,
                    'updated_at' => $r->updated_at,
                ];
            })->values(),
        ]);
    }

    public function historyDetails(Request $request, $company, $batch_no)
    {
        $companyRow = $this->resolveCompany($company);
        abort_if(!$companyRow, 404, 'Company not found');
        $companyId = (int) $companyRow->id;

        $batch = DB::table('communication_batches')
            ->where('company_id', $companyId)
            ->where('batch_no', $batch_no)
            ->first();

        if (!$batch) {
            return response()->json(['ok' => false, 'message' => 'Batch not found'], 404);
        }

        $logsQ = DB::table('communication_message_logs')
            ->where('company_id', $companyId)
            ->where('batch_no', $batch_no);

        if ($request->filled('message_type')) {
            $logsQ->where('message_type', strtoupper((string) $request->input('message_type')));
        }
        if ($request->filled('status')) {
            $logsQ->where('status', strtoupper((string) $request->input('status')));
        }

        $logs = $logsQ->orderByDesc('id')->limit(500)->get([
            'id', 'message_type', 'recipient_name', 'recipient_phone', 'recipient_email',
            'status', 'failure_reason', 'gateway_name', 'sent_at', 'created_at',
        ]);

        return response()->json([
            'ok' => true,
            'batch' => $batch,
            'logs' => $logs,
        ]);
    }

    /* ===================== INTERNAL HELPERS ===================== */

    private function buildRecipientsQuery(int $companyId, Request $request)
    {
        // registration_master alias rm
        // cluster label built from mapping->cluster_masters
        $q = DB::table('registration_master as rm')
            ->leftJoin('cluster_upazila_mappings as cum', function ($j) use ($companyId) {
                $j->on('cum.upazila_id', '=', 'rm.upazila_id')
                  ->where('cum.company_id', '=', $companyId)
                  ->where('cum.status', '=', 1);
            })
            ->leftJoin('cluster_masters as cm', function ($j) use ($companyId) {
                $j->on('cm.id', '=', 'cum.cluster_id')
                  ->where('cm.company_id', '=', $companyId)
                  ->where('cm.status', '=', 1);
            })
            ->where('rm.company_id', $companyId)
            ->whereNull('rm.deleted_at')
            ->select([
                'rm.id',
                'rm.user_id',
                'rm.full_name',
                'rm.phone',
                'rm.email',
                'rm.registration_type',
                'rm.division_id',
                'rm.district_id',
                'rm.upazila_id',
                DB::raw("CONCAT(IFNULL(cm.id,''),' - ',IFNULL(cm.cluster_name,'')) as cluster_label"),
            ]);

        // Filters
        $divisionId = $request->input('division_id');
        if (is_numeric($divisionId)) $q->where('rm.division_id', (int) $divisionId);

        $districtId = $request->input('district_id');
        if (is_numeric($districtId)) $q->where('rm.district_id', (int) $districtId);

        $clusterId = $request->input('cluster_id');
        if (is_numeric($clusterId)) $q->where('cm.id', (int) $clusterId);

        $rt = (string) $request->input('registration_type', '');
        if ($rt !== '' && strtoupper($rt) !== 'ALL') {
            $q->where('rm.registration_type', $rt);
        }

        if ($request->filled('created_from')) {
            $q->whereDate('rm.created_at', '>=', $request->input('created_from'));
        }
        if ($request->filled('created_to')) {
            $q->whereDate('rm.created_at', '<=', $request->input('created_to'));
        }

        // Age range filter (based on registration_master.date_of_birth; slab in years)
        $ageRange = trim((string) ($request->input('age_range') ?? 'ALL'));
        if ($ageRange !== '' && strtoupper($ageRange) !== 'ALL') {
            // Supported slabs: 15-20, 21-30, 31-40, 41-50, 51-60, 61-75, 75+
            $slabs = [
                '15-20' => [15, 20],
                '21-30' => [21, 30],
                '31-40' => [31, 40],
                '41-50' => [41, 50],
                '51-60' => [51, 60],
                '61-75' => [61, 75],
                '75+'   => [75, null],
            ];

            if (isset($slabs[$ageRange])) {
                [$minAge, $maxAge] = $slabs[$ageRange];

                // Exclude null DOBs when age filter is applied
                $q->whereNotNull('rm.date_of_birth')
                  ->whereRaw('TIMESTAMPDIFF(YEAR, rm.date_of_birth, CURDATE()) >= ?', [(int) $minAge]);

                if ($maxAge !== null) {
                    $q->whereRaw('TIMESTAMPDIFF(YEAR, rm.date_of_birth, CURDATE()) <= ?', [(int) $maxAge]);
                }
            }
        }

        // Skill filter (skills.id via person_skills.skill)
        $skillId = (int) ($request->input('skill_id') ?? 0);
        if ($skillId > 0) {
            $q->whereExists(function ($sq) use ($companyId, $skillId) {
                $sq->select(DB::raw(1))
                    ->from('person_skills as ps')
                    ->whereColumn('ps.registration_id', 'rm.id')
                    ->where('ps.Company_id', '=', $companyId)
                    ->where('ps.status', '=', 1)
                    ->where('ps.skill', '=', $skillId);
            });
        }

        // Optional: status filter if your registration_master has status column
        if ($request->filled('status')) {
            $s = strtolower((string) $request->input('status'));
            if (in_array($s, ['active', 'inactive'], true) && $this->columnExists('registration_master', 'status')) {
                $q->where('rm.status', $s === 'active' ? 1 : 0);
            }
        }

        return $q;
    }

    private function columnExists(string $table, string $column): bool
    {
        static $cache = [];
        $k = $table . '.' . $column;
        if (array_key_exists($k, $cache)) return $cache[$k];

        try {
            $db = DB::getDatabaseName();
            $exists = DB::table('information_schema.COLUMNS')
                ->where('TABLE_SCHEMA', $db)
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', $column)
                ->exists();
            return $cache[$k] = $exists;
        } catch (Throwable $e) {
            return $cache[$k] = false;
        }
    }
}
