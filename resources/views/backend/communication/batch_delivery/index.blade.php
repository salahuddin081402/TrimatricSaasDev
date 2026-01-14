{{-- resources/views/backend/communication/batch-delivery/index.blade.php --}}
@extends('backend.layouts.master')

@push('styles')
    {{-- TomSelect (CDN for now; replace with your project asset pipeline if you already have it) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">

    <style>
        :root{
            --ink:#0f172a; --muted:#6b7280; --line:#e5e7eb;
            --brand:#1e3a8a; --brand2:#2563eb;
            --panel:#ffffff; --bg:#f1f5f9;
            --good:#16a34a; --bad:#dc2626; --warn:#f59e0b;
        }
        .bd-wrap{ background:var(--bg); color:var(--ink); }
        .bd-shell{ max-width:1400px; margin:0 auto; padding:16px; }
        .bd-card{ background:var(--panel); border:1px solid var(--line); border-radius:14px; box-shadow:0 10px 28px rgba(15,23,42,.06); overflow:hidden; }
        .bd-tiny{ font-size:.92rem; color:var(--muted); }
        .bd-pill{
            display:inline-flex; align-items:center; gap:8px;
            padding:6px 10px; border-radius:999px;
            background:#eef2ff; border:1px solid #e0e7ff;
            font-size:.9rem;
        }
        .bd-kpi{ border:1px dashed #cbd5e1; border-radius:12px; padding:10px 12px; background:#f8fafc; }
        .bd-required:after{ content:" *"; color:#dc2626; }
        .bd-status{
            padding:6px 10px; border-radius:999px; font-weight:600; font-size:.85rem;
            border:1px solid var(--line); background:#fff;
        }
        .bd-status.running{ color:#1d4ed8; }
        .bd-status.completed{ color:var(--good); }
        .bd-status.failed{ color:var(--bad); }
        .bd-status.pending{ color:#0f172a; }

        .bd-left .nav-link{ border-radius:10px; padding:10px 12px; color:var(--ink); border:1px solid transparent; }
        .bd-left .nav-link.active{
            background: linear-gradient(135deg, var(--brand), var(--brand2));
            color:#fff; border-color: rgba(255,255,255,.25);
        }

        .bd-btn{ border-radius:10px; }
        .bd-date-range{ display:flex; gap:.5rem; flex-wrap:wrap; }
        .bd-date-range .form-control{ min-width:0; flex:1 1 160px; }

        .ts-control{ border-radius:.5rem !important; }
        .ts-wrapper.single .ts-control{ padding:.375rem .75rem; }
        .ts-wrapper.multi .ts-control{ padding:.25rem .5rem; }
        .ts-wrapper .item{ border-radius:999px !important; }

        .bd-badge{
            display:inline-flex; align-items:center;
            padding:4px 10px; border-radius:999px;
            font-size:.82rem; font-weight:600;
            border:1px solid #dbeafe; background:#eff6ff; color:#1e40af;
        }

        .bd-table td, .bd-table th{ vertical-align:middle; }
        .bd-mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    </style>
@endpush

@section('content')
<div class="bd-wrap">
    <div class="bd-shell">

        {{-- Header --}}
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h4 class="mb-1">Batch Email &amp; SMS Delivery</h4>
                <div class="bd-tiny">
                    Sends run in background via queue worker. UI stays responsive. Use History to find any batch later.
                </div>
            </div>
            <div class="d-flex gap-2">
                <span class="bd-pill">Queue: database</span>
                <span class="bd-pill">Retries: 2</span>
            </div>
        </div>

        <div class="row g-3">
            {{-- Left menu --}}
            <div class="col-12 col-lg-3">
                <div class="bd-card p-3 bd-left">
                    <div class="fw-semibold mb-2">Target Groups</div>

                    <nav class="nav flex-column gap-2" id="bdLeftNav">
                        <a class="nav-link active" href="#" data-target="bdPaneClientEntre">Client &amp; Entrepreneur</a>
                        <a class="nav-link disabled" href="javascript:void(0)" aria-disabled="true" style="opacity:.55;">Cluster (later)</a>
                        <a class="nav-link disabled" href="javascript:void(0)" aria-disabled="true" style="opacity:.55;">Division Admin (later)</a>
                        <a class="nav-link disabled" href="javascript:void(0)" aria-disabled="true" style="opacity:.55;">District Admin (later)</a>
                        <a class="nav-link disabled" href="javascript:void(0)" aria-disabled="true" style="opacity:.55;">All Users (later)</a>

                        <hr class="my-2">

                        <a class="nav-link" href="#" data-target="bdPaneHistory">Batch History</a>
                    </nav>

                    <div class="bd-tiny mt-3">
                        Phase-1 includes only <b>Client &amp; Entrepreneur</b> and <b>Batch History</b>.
                    </div>
                </div>
            </div>

            {{-- Right panel --}}
            <div class="col-12 col-lg-9">
                <div class="bd-card p-3">

                    {{-- Shared right header --}}
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <div class="fw-semibold" id="bdPaneTitle">Client &amp; Entrepreneur</div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="bd-status pending" id="bdStatusBadge">Status: Not Started</span>
                            <span class="bd-pill bd-mono" id="bdActiveBatch">Batch: —</span>
                        </div>
                    </div>

                    {{-- Progress --}}
                    <div class="mb-3">
                        <div class="d-flex justify-content-between bd-tiny mb-1">
                            <span id="bdProgressText">No active batch selected.</span>
                            <span id="bdProgressPct">—</span>
                        </div>
                        <div class="progress" style="height:10px;">
                            <div class="progress-bar" id="bdProgressBar" style="width:0%"></div>
                        </div>

                        <div class="row g-2 mt-2">
                            <div class="col-6 col-md-3">
                                <div class="bd-kpi">
                                    <div class="bd-tiny">Recipients</div>
                                    <div class="fw-semibold" id="bdKpiRecipients">—</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="bd-kpi">
                                    <div class="bd-tiny">Processed</div>
                                    <div class="fw-semibold" id="bdKpiProcessed">—</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="bd-kpi">
                                    <div class="bd-tiny">Success</div>
                                    <div class="fw-semibold" id="bdKpiSuccess">—</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="bd-kpi">
                                    <div class="bd-tiny">Failed</div>
                                    <div class="fw-semibold" id="bdKpiFailed">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- PANES --}}
                    <div id="bdPanes">

                        {{-- Pane: Client & Entrepreneur --}}
                        <div id="bdPaneClientEntre">

                            {{-- Filters --}}
                            <div class="row g-2">
                                <div class="col-12 col-md-4">
                                    <label class="form-label bd-required">Division</label>
                                    <select id="bdDivision" class="form-select"></select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label bd-required">District</label>
                                    <select id="bdDistrict" class="form-select"></select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label bd-required">Cluster</label>
                                    <select id="bdCluster" class="form-select"></select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label bd-required">Registration Type</label>
                                    <select id="bdRegType" class="form-select">
                                        <option value="">ALL</option>
                                        <option value="client">Client</option>
                                        <option value="company_officer">Company Officer</option>
                                        <option value="professional">Professional</option>
                                        <option value="entrepreneur">Entrepreneur</option>
                                        <option value="enterprise_client">Enterprise Client</option>
                                    </select>
                                    <div class="bd-tiny mt-1">Values are from <span class="bd-mono">registration_master.registration_type</span>.</div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label">Status</label>
                                    <select id="bdStatus" class="form-select">
                                        <option value="">ALL</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label">Skill</label>
                                    <select id="bdSkill" class="form-select">
                                        <option value="">All</option>
                                        @foreach(($skills ?? []) as $sk)
                                            <option value="{{ $sk->id }}">{{ $sk->skill }}</option>
                                        @endforeach
                                    </select>
                                </div>
            <div class="col-12 col-md-4">
                <label class="form-label">Age Range (DOB)</label>
                <select id="bdAgeRange" class="form-select">
                    <option value="ALL" selected>All</option>
                    <option value="15-20">15 - 20</option>
                    <option value="21-30">21 - 30</option>
                    <option value="31-40">31 - 40</option>
                    <option value="41-50">41 - 50</option>
                    <option value="51-60">51 - 60</option>
                    <option value="61-75">61 - 75</option>
                    <option value="75+">75+</option>
                </select>
            </div>


                                <div class="col-12 col-md-4">
                                    <label class="form-label">Created Date Range</label>
                                    <div class="bd-date-range">
                                        <input id="bdCreatedFrom" type="date" class="form-control">
                                        <input id="bdCreatedTo" type="date" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3">

                            {{-- Compose --}}
                            <div class="row g-2">
                                <div class="col-12 col-md-6">
                                    <label class="form-label bd-required">Channels</label>
                                    <div class="d-flex gap-3 mt-1">
                                        <label class="form-check">
                                            <input id="bdSendSms" class="form-check-input" type="checkbox" checked>
                                            <span class="form-check-label">SMS</span>
                                        </label>
                                        <label class="form-check">
                                            <input id="bdSendEmail" class="form-check-input" type="checkbox" checked>
                                            <span class="form-check-label">Email</span>
                                        </label>
                                    </div>
                                    <div class="bd-tiny mt-1">
                                        Missing required phone/email will log that recipient as <b>FAILED</b> for that channel.
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label bd-required">Subject / Title</label>
                                    <input id="bdSubject" class="form-control" placeholder="e.g., Important Notice">
                                </div>

                                <div class="col-12">
                                    <label class="form-label bd-required">Message</label>
                                    <textarea id="bdMessage" class="form-control" rows="4" placeholder="Write the main message here..."></textarea>
                                    <div class="bd-tiny mt-1">
                                        Tokens supported (recommended): <span class="bd-mono">{FULL_NAME} {PHONE} {EMAIL} {COMPANY} {BATCH_NO}</span>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Extra Message (optional)</label>
                                    <textarea id="bdExtraMessage" class="form-control" rows="3" placeholder="Optional additional paragraph..."></textarea>
                                </div>
                            </div>

                            <hr class="my-3">

                            {{-- Actions --}}
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <button id="bdBtnEstimate" class="btn btn-outline-primary bd-btn">Estimate Recipients</button>
                                <button id="bdBtnSend" class="btn btn-primary bd-btn">Send Message</button>
                                <button id="bdBtnCancel" class="btn btn-outline-danger bd-btn" disabled>Cancel Batch</button>
                                <span class="ms-auto bd-tiny" id="bdActionHint"></span>
                            </div>

                            <hr class="my-3">

                            {{-- Dataset --}}
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="fw-semibold">Filtered Recipients</div>
                                <div class="bd-tiny">Total: <span class="fw-semibold" id="bdTotalRecipients">—</span></div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle bd-table">
                                    <thead class="table-light">
                                    <tr>
                                        <th style="width:60px;">#</th>
                                        <th>Name</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>Reg Type</th>
                                        <th>Cluster</th>
                                    </tr>
                                    </thead>
                                    <tbody id="bdRecipientsTbody">
                                        <tr>
                                            <td colspan="6" class="bd-tiny text-center py-4">
                                                Click <b>Estimate Recipients</b> to load preview rows.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Pane: History --}}
                        <div class="d-none" id="bdPaneHistory">

                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-md-4">
                                    <label class="form-label">Batch No</label>
                                    <input id="bdHistBatchNo" class="form-control" placeholder="BATCH-20260106-0001">
                                </div>

                                <div class="col-12 col-md-3">
                                    <label class="form-label">Status</label>
                                    <select id="bdHistStatus" class="form-select">
                                        <option value="">All</option>
                                        <option value="PENDING">PENDING</option>
                                        <option value="RUNNING">RUNNING</option>
                                        <option value="COMPLETED">COMPLETED</option>
                                        <option value="FAILED">FAILED</option>
                                        <option value="CANCELLED">CANCELLED</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-3">
                                    <label class="form-label">Target Group</label>
                                    <select id="bdHistTarget" class="form-select">
                                        <option value="">All</option>
                                        <option value="client_entrepreneur">Client &amp; Entrepreneur</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-2 d-grid">
                                    <button id="bdBtnHistSearch" class="btn btn-outline-primary bd-btn">Search</button>
                                </div>
                            </div>

                            <hr class="my-3">

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="fw-semibold">Batch Results</div>
                                <div class="bd-tiny">Tip: click <b>View</b> to load details and also resume live progress.</div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle bd-table">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Batch No</th>
                                        <th>Status</th>
                                        <th>Channels</th>
                                        <th>Recipients</th>
                                        <th>Processed</th>
                                        <th>Success</th>
                                        <th>Failed</th>
                                        <th>Last Update</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody id="bdHistoryTbody">
                                        <tr>
                                            <td colspan="9" class="bd-tiny text-center py-4">
                                                Search to load batches.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="bd-tiny mt-2">
                                Details view shows per-recipient failures from <span class="bd-mono">communication_message_logs</span>.
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Details Modal --}}
    <div class="modal fade" id="bdDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="fw-semibold">Batch Details</div>
                        <div class="bd-tiny bd-mono" id="bdDetailsBatchNo">—</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-6">
                            <div class="bd-kpi">
                                <div class="bd-tiny">Message Snapshot</div>
                                <div class="fw-semibold" id="bdDetailsSubject">—</div>
                                <div class="bd-tiny mt-1" id="bdDetailsChannels">—</div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="bd-kpi">
                                <div class="bd-tiny">Counters</div>
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    <span class="bd-badge">Recipients: <span class="ms-1 bd-mono" id="bdD_total">—</span></span>
                                    <span class="bd-badge">Processed: <span class="ms-1 bd-mono" id="bdD_proc">—</span></span>
                                    <span class="bd-badge">Success: <span class="ms-1 bd-mono" id="bdD_succ">—</span></span>
                                    <span class="bd-badge">Failed: <span class="ms-1 bd-mono" id="bdD_fail">—</span></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="fw-semibold mb-2">Failures / Logs (preview)</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle bd-table">
                            <thead class="table-light">
                            <tr>
                                <th style="width:70px;">#</th>
                                <th>Type</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Reason</th>
                                <th>Sent At</th>
                            </tr>
                            </thead>
                            <tbody id="bdDetailsTbody">
                                <tr><td colspan="8" class="bd-tiny text-center py-4">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="bd-tiny">
                        This is a preview. You can later add server-side pagination/export.
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-secondary bd-btn" data-bs-dismiss="modal">Close</button>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

    <script>
        (function(){
            const companySlug = @json($companyRow->slug ?? '');
            const baseUrl = @json(url('backend/communication/'.($companyRow->slug ?? 'company').'/batch-delivery'));
            const csrf = @json(csrf_token());

            // ---------- Helpers ----------
            function jget(url){
                return fetch(url, {headers:{'Accept':'application/json'}}).then(r=>r.json());
            }
            function jpost(url, payload){
                return fetch(url, {
                    method:'POST',
                    headers:{
                        'Content-Type':'application/json',
                        'Accept':'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify(payload || {})
                }).then(async r => {
                    const data = await r.json().catch(()=>null);
                    if (!r.ok) {
                        const msg = (data && (data.message || data.error)) ? (data.message || data.error) : 'Request failed';
                        throw new Error(msg);
                    }
                    return data;
                });
            }
            function esc(s){ return (s ?? '').toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])); }

            // ---------- Pane switching ----------
            const nav = document.getElementById('bdLeftNav');
            const paneTitle = document.getElementById('bdPaneTitle');
            const paneClient = document.getElementById('bdPaneClientEntre');
            const paneHistory = document.getElementById('bdPaneHistory');

            nav.querySelectorAll('.nav-link:not(.disabled)').forEach(a => {
                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    nav.querySelectorAll('.nav-link').forEach(x => x.classList.remove('active'));
                    a.classList.add('active');
                    paneTitle.textContent = a.textContent.trim();

                    const target = a.getAttribute('data-target');
                    paneClient.classList.toggle('d-none', target !== 'bdPaneClientEntre');
                    paneHistory.classList.toggle('d-none', target !== 'bdPaneHistory');
                });
            });

            // ---------- Progress UI ----------
            const statusBadge = document.getElementById('bdStatusBadge');
            const activeBatchEl = document.getElementById('bdActiveBatch');
            const progressText = document.getElementById('bdProgressText');
            const progressPct = document.getElementById('bdProgressPct');
            const progressBar = document.getElementById('bdProgressBar');

            const kRecipients = document.getElementById('bdKpiRecipients');
            const kProcessed = document.getElementById('bdKpiProcessed');
            const kSuccess = document.getElementById('bdKpiSuccess');
            const kFailed = document.getElementById('bdKpiFailed');

            const btnCancel = document.getElementById('bdBtnCancel');

            let pollTimer = null;
            let activeBatchNo = null;

            function setStatus(label, css){
                statusBadge.classList.remove('pending','running','completed','failed');
                statusBadge.classList.add(css);
                statusBadge.textContent = 'Status: ' + label;
            }

            function stopPolling(){
                if (pollTimer) clearInterval(pollTimer);
                pollTimer = null;
            }

            function startPolling(batchNo){
                stopPolling();
                activeBatchNo = batchNo;
                btnCancel.disabled = false;

                pollTimer = setInterval(async () => {
                    try{
                        const data = await jget(`${baseUrl}/batch/${encodeURIComponent(batchNo)}/status`);
                        applyStatusPayload(data);
                    }catch(e){
                        // ignore transient UI errors; keep polling
                    }
                }, 3000);
            }

            function applyStatusPayload(data){
                // Expected (controller will implement): { ok, batch:{...}, progress:{percent}, can_cancel }
                if (!data || !data.batch) return;

                const b = data.batch;
                activeBatchEl.textContent = 'Batch: ' + (b.batch_no || '—');

                const st = (b.status || '').toUpperCase();
                if (st === 'RUNNING') setStatus('RUNNING', 'running');
                else if (st === 'COMPLETED') setStatus('COMPLETED', 'completed');
                else if (st === 'FAILED') setStatus('FAILED', 'failed');
                else if (st === 'CANCELLED') setStatus('CANCELLED', 'failed');
                else setStatus(st || 'PENDING', 'pending');

                const total = Number(b.total_recipients || 0);
                const processed = Number(b.processed_count || 0);
                const succ = Number(b.success_count || 0);
                const fail = Number(b.failed_count || 0);

                kRecipients.textContent = total ? total.toLocaleString() : '—';
                kProcessed.textContent = processed ? processed.toLocaleString() : (total ? '0' : '—');
                kSuccess.textContent = succ ? succ.toLocaleString() : (total ? '0' : '—');
                kFailed.textContent = fail ? fail.toLocaleString() : (total ? '0' : '—');

                const pct = total ? Math.floor((processed / total) * 100) : 0;
                progressBar.style.width = pct + '%';
                progressPct.textContent = total ? (pct + '%') : '—';
                progressText.textContent = total ? `${processed.toLocaleString()} processed out of ${total.toLocaleString()}` : 'No active batch selected.';

                const canCancel = (data.can_cancel !== undefined) ? !!data.can_cancel : (st === 'RUNNING');
                btnCancel.disabled = !canCancel;

                if (st === 'COMPLETED' || st === 'FAILED' || st === 'CANCELLED') {
                    stopPolling();
                    btnCancel.disabled = true;
                }
            }

            // ---------- TomSelect: Geo ----------
            const divisionSel = document.getElementById('bdDivision');
            const districtSel = document.getElementById('bdDistrict');
            const clusterSel = document.getElementById('bdCluster');
            const skillSel = document.getElementById('bdSkill');

            let tsDivision, tsDistrict, tsCluster, tsSkill;

            function tsInit(){
                tsDivision = new TomSelect(divisionSel, {
                    create:false, maxItems:1, allowEmptyOption:true,
                    sortField:{field:'text',direction:'asc'}
                });
                tsDistrict = new TomSelect(districtSel, {
                    create:false, maxItems:1, allowEmptyOption:true,
                    sortField:{field:'text',direction:'asc'}
                });
                tsCluster = new TomSelect(clusterSel, {
                    create:false, maxItems:1, allowEmptyOption:true,
                    sortField:{field:'text',direction:'asc'}
                });
            
                tsSkill = new TomSelect(skillSel, {
                    create:false, maxItems:1, allowEmptyOption:true
                });
}

            function tsSetOptions(ts, opts, selectedValue){
                ts.clear(true);
                ts.clearOptions();
                opts.forEach(o => ts.addOption(o));
                ts.refreshOptions(false);
                if (selectedValue !== undefined && selectedValue !== null) ts.setValue(selectedValue, true);
            }

            async function loadDivisions(){
                // API returns: { ok: true, items: [{id, text}] }
                const payload = await jget(`${baseUrl}/api/geo/divisions`);
                const rows = (payload && payload.items) ? payload.items : [];
                const opts = [{value:'', text:'ALL'}].concat(rows.map(r => ({value:String(r.id), text:String(r.text ?? r.name ?? r.label ?? r.id)})));
                tsSetOptions(tsDivision, opts, '');
                await loadDistricts(''); // reset
            }

            async function loadDistricts(divisionId){
                // If no specific division selected -> only ALL should exist in District list
                if (!divisionId) {
                    tsSetOptions(tsDistrict, [{value:'', text:'ALL'}], '');
                    await loadClusters('');
                    return;
                }
                const q = `?division_id=${encodeURIComponent(divisionId)}`;
                const payload = await jget(`${baseUrl}/api/geo/districts${q}`);
                const rows = (payload && payload.items) ? payload.items : [];
                const opts = [{value:'', text:'ALL'}].concat(rows.map(r => ({value:String(r.id), text:String(r.text ?? r.name ?? r.label ?? r.id)})));
                tsSetOptions(tsDistrict, opts, '');
                await loadClusters('');
            }

            async function loadClusters(districtId){
                // If no specific district selected -> only ALL should exist in Cluster list
                if (!districtId) {
                    tsSetOptions(tsCluster, [{value:'', text:'ALL'}], '');
                    return;
                }
                const q = `?district_id=${encodeURIComponent(districtId)}`;
                // API returns: { ok: true, items: [{id, text}] } where text = "{id} - {cluster_name}"
                const payload = await jget(`${baseUrl}/api/geo/clusters${q}`);
                const rows = (payload && payload.items) ? payload.items : [];
                const opts = [{value:'', text:'ALL'}].concat(rows.map(r => ({value:String(r.id), text:String(r.text ?? r.label ?? r.name ?? r.id)})));
                tsSetOptions(tsCluster, opts, '');
            }

            // ---------- Client & Entrepreneur: Estimate + Dispatch ----------
            const btnEstimate = document.getElementById('bdBtnEstimate');
            const btnSend = document.getElementById('bdBtnSend');
            const actionHint = document.getElementById('bdActionHint');

            const totalRecipientsEl = document.getElementById('bdTotalRecipients');
            const tbodyRecipients = document.getElementById('bdRecipientsTbody');

            function readClientEntrePayload(){
                return {
                    division_id: tsDivision ? tsDivision.getValue() : '',
                    district_id: tsDistrict ? tsDistrict.getValue() : '',
                    cluster_id: tsCluster ? tsCluster.getValue() : '',
                    registration_type: document.getElementById('bdRegType').value || '',
                    status: document.getElementById('bdStatus').value || '',
                    age_range: document.getElementById('bdAgeRange') ? (document.getElementById('bdAgeRange').value || '') : '',
                    skill_id: tsSkill ? tsSkill.getValue() : '',
                    created_from: document.getElementById('bdCreatedFrom').value || '',
                    created_to: document.getElementById('bdCreatedTo').value || '',

                    send_sms: !!document.getElementById('bdSendSms').checked,
                    send_email: !!document.getElementById('bdSendEmail').checked,
                    subject: document.getElementById('bdSubject').value || '',
                    message: document.getElementById('bdMessage').value || '',
                    extra_message: document.getElementById('bdExtraMessage').value || ''
                };
            }

            function renderRecipientPreview(rows){
                if (!rows || !rows.length){
                    tbodyRecipients.innerHTML = `<tr><td colspan="6" class="bd-tiny text-center py-4">No recipients found for the selected filters.</td></tr>`;
                    return;
                }
                let html = '';
                rows.forEach((r, idx) => {
                    html += `<tr>
                        <td>${idx+1}</td>
                        <td>${esc(r.full_name || r.name || '')}</td>
                        <td class="bd-mono">${esc(r.phone || '')}</td>
                        <td class="bd-mono">${esc(r.email || '')}</td>
                        <td><span class="bd-badge">${esc(r.registration_type || '')}</span></td>
                        <td>${esc(r.cluster_label || '')}</td>
                    </tr>`;
                });
                tbodyRecipients.innerHTML = html;
            }

            btnEstimate.addEventListener('click', async () => {
                try{
                    actionHint.textContent = 'Estimating…';
                    const payload = readClientEntrePayload();
                    const data = await jpost(`${baseUrl}/client-entrepreneur/estimate`, payload);

                    // Expected: { ok:true, total:int, preview:[...] }
                    totalRecipientsEl.textContent = (data.total ?? 0).toLocaleString();
                    renderRecipientPreview(data.preview || []);
                    actionHint.textContent = 'Estimate complete.';
                }catch(e){
                    actionHint.textContent = e.message || 'Estimate failed.';
                }
            });

            btnSend.addEventListener('click', async () => {
                try{
                    actionHint.textContent = 'Dispatching…';
                    const payload = readClientEntrePayload();
                    const data = await jpost(`${baseUrl}/client-entrepreneur/dispatch`, payload);

                    // Expected: { ok:true, batch_no:"...", batch:{...} }
                    if (!data.batch_no) throw new Error('Batch number not returned');
                    activeBatchEl.textContent = 'Batch: ' + data.batch_no;
                    setStatus('PENDING', 'pending');

                    if (data.batch) applyStatusPayload({batch:data.batch, can_cancel:true});

                    startPolling(data.batch_no);
                    actionHint.textContent = 'Batch dispatched. You can leave this page.';
                }catch(e){
                    actionHint.textContent = e.message || 'Dispatch failed.';
                }
            });

            btnCancel.addEventListener('click', async () => {
                if (!activeBatchNo) return;
                try{
                    btnCancel.disabled = true;
                    actionHint.textContent = 'Cancelling…';
                    const data = await jpost(`${baseUrl}/batch/${encodeURIComponent(activeBatchNo)}/cancel`, {});
                    // Expected: { ok:true, batch:{...} }
                    if (data.batch) applyStatusPayload({batch:data.batch, can_cancel:false});
                    actionHint.textContent = 'Cancel requested.';
                }catch(e){
                    actionHint.textContent = e.message || 'Cancel failed.';
                    btnCancel.disabled = false;
                }
            });

            // ---------- History ----------
            const btnHistSearch = document.getElementById('bdBtnHistSearch');
            const histTbody = document.getElementById('bdHistoryTbody');

            function renderHistoryRows(rows){
                if (!rows || !rows.length){
                    histTbody.innerHTML = `<tr><td colspan="9" class="bd-tiny text-center py-4">No batches found.</td></tr>`;
                    return;
                }

                let html = '';
                rows.forEach(r => {
                    const ch = (r.send_sms && r.send_email) ? 'BOTH' : (r.send_sms ? 'SMS' : (r.send_email ? 'EMAIL' : '—'));
                    html += `<tr>
                        <td class="bd-mono">${esc(r.batch_no)}</td>
                        <td><span class="bd-badge">${esc(r.status || '')}</span></td>
                        <td>${esc(ch)}</td>
                        <td class="bd-mono">${Number(r.total_recipients||0).toLocaleString()}</td>
                        <td class="bd-mono">${Number(r.processed_count||0).toLocaleString()}</td>
                        <td class="bd-mono">${Number(r.success_count||0).toLocaleString()}</td>
                        <td class="bd-mono">${Number(r.failed_count||0).toLocaleString()}</td>
                        <td class="bd-mono">${esc(r.updated_at || r.created_at || '')}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-primary bd-btn" data-batch="${esc(r.batch_no)}">View</button>
                        </td>
                    </tr>`;
                });
                histTbody.innerHTML = html;

                histTbody.querySelectorAll('button[data-batch]').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        const batchNo = btn.getAttribute('data-batch');
                        await openDetails(batchNo);
                        startPolling(batchNo); // also resume live updates
                    });
                });
            }

            btnHistSearch.addEventListener('click', async () => {
                try{
                    histTbody.innerHTML = `<tr><td colspan="9" class="bd-tiny text-center py-4">Loading…</td></tr>`;
                    const q = new URLSearchParams({
                        batch_no: document.getElementById('bdHistBatchNo').value || '',
                        status: document.getElementById('bdHistStatus').value || '',
                        target_group: document.getElementById('bdHistTarget').value || ''
                    });
                    const data = await jget(`${baseUrl}/history/data?${q.toString()}`);
                    // Expected: { ok:true, rows:[...] }
                    renderHistoryRows(data.rows || []);
                }catch(e){
                    histTbody.innerHTML = `<tr><td colspan="9" class="bd-tiny text-center py-4">Failed to load history.</td></tr>`;
                }
            });

            async function openDetails(batchNo){
                const modalEl = document.getElementById('bdDetailsModal');
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

                document.getElementById('bdDetailsBatchNo').textContent = batchNo;
                document.getElementById('bdDetailsTbody').innerHTML = `<tr><td colspan="8" class="bd-tiny text-center py-4">Loading…</td></tr>`;

                try{
                    const data = await jget(`${baseUrl}/history/${encodeURIComponent(batchNo)}/details`);
                    // Expected: { ok:true, batch:{...}, logs:[...], subject, channels }
                    if (data.batch){
                        document.getElementById('bdDetailsSubject').textContent = data.batch.subject || '—';
                        const ch = (data.batch.send_sms && data.batch.send_email) ? 'BOTH' : (data.batch.send_sms ? 'SMS' : (data.batch.send_email ? 'EMAIL' : '—'));
                        document.getElementById('bdDetailsChannels').textContent = 'Channels: ' + ch;

                        document.getElementById('bdD_total').textContent = Number(data.batch.total_recipients||0).toLocaleString();
                        document.getElementById('bdD_proc').textContent = Number(data.batch.processed_count||0).toLocaleString();
                        document.getElementById('bdD_succ').textContent = Number(data.batch.success_count||0).toLocaleString();
                        document.getElementById('bdD_fail').textContent = Number(data.batch.failed_count||0).toLocaleString();
                    }

                    const logs = data.logs || [];
                    if (!logs.length){
                        document.getElementById('bdDetailsTbody').innerHTML =
                            `<tr><td colspan="8" class="bd-tiny text-center py-4">No log rows returned.</td></tr>`;
                    } else {
                        let html = '';
                        logs.forEach((r, idx) => {
                            html += `<tr>
                                <td>${idx+1}</td>
                                <td><span class="bd-badge">${esc(r.message_type || '')}</span></td>
                                <td>${esc(r.recipient_name || '')}</td>
                                <td class="bd-mono">${esc(r.recipient_phone || '')}</td>
                                <td class="bd-mono">${esc(r.recipient_email || '')}</td>
                                <td><span class="bd-badge">${esc(r.status || '')}</span></td>
                                <td>${esc(r.failure_reason || '')}</td>
                                <td class="bd-mono">${esc(r.sent_at || '')}</td>
                            </tr>`;
                        });
                        document.getElementById('bdDetailsTbody').innerHTML = html;
                    }

                    modal.show();
                }catch(e){
                    document.getElementById('bdDetailsTbody').innerHTML =
                        `<tr><td colspan="8" class="bd-tiny text-center py-4">Failed to load details.</td></tr>`;
                    modal.show();
                }
            }

            // ---------- Wire geo change ----------
            function wireGeoChange(){
                tsDivision.on('change', async (val) => { await loadDistricts(val || ''); });
                tsDistrict.on('change', async (val) => { await loadClusters(val || ''); });
            }

            // ---------- Boot ----------
            (async function boot(){
                tsInit();
                wireGeoChange();
                try{
                    await loadDivisions();
                }catch(e){
                    // If geo APIs not ready yet, keep selects usable
                    if (tsDivision) tsSetOptions(tsDivision, [{value:'',text:'ALL'}], '');
                    if (tsDistrict) tsSetOptions(tsDistrict, [{value:'',text:'ALL'}], '');
                    if (tsCluster) tsSetOptions(tsCluster, [{value:'',text:'ALL'}], '');
                }
            })();

        })();
    </script>
@endpush
