{{-- TMX-RMGM | resources/views/registration/Registration_Management/registration_mgmt.blade.php | v9.0 — full blade with per-row De-assign (5–8) --}}
@extends('backend.layouts.master')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/buttons.css') }}">
<style>
:root{
  --ink:#273a56; --sub:#5a6b85; --hl:#2d5be3;
  --ok:#22a06b; --warn:#d97706; --bad:#d32f2f; --muted:#7b8794;
  --card:#ffffff; --bg:#f4f7fc; --br:#e5e7eb; --row1:#f7fbff; --row2:#eef6ff;
  --controls-bg1:#f8fbff; --controls-bg2:#ecf3ff;
  --metric-bg:#edf4ff; --metric-box:#e6efff; --metric-shadow:0 16px 36px -18px rgba(45,91,227,.45);
}
html, body{ color:var(--ink); background:var(--bg); }

.tmx-title{ color:#36507a; font-weight:800; font-size:1.05rem; letter-spacing:.12px }
.topbar{ position:sticky; top:0; background:var(--bg); z-index:8; border-bottom:1px solid var(--br) }

.metrics-card{ background:var(--metric-bg); border:0; border-radius:12px; padding:8px 10px; box-shadow:var(--metric-shadow) }
.metric-grid{ display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:8px }
.metric{ background:var(--metric-box); border-radius:10px; padding:8px 10px; display:flex; justify-content:space-between; align-items:center; color:#274060; font-weight:600 }
.metric small{ color:#3f5d7d; font-weight:500 }

.controls{
  position:sticky; top:64px; z-index:7;
  background:linear-gradient(180deg, var(--controls-bg1), var(--controls-bg2));
  border:0; border-radius:12px; padding:10px 12px;
  box-shadow:0 14px 30px -22px rgba(0,0,0,.35);
}
.controls .form-select, .controls .form-control{ height:38px; padding:6px 10px }
.controls .col-auto{ position:relative; z-index:1 }

.ts-wrapper{ min-width:220px }
.ts-dropdown{ z-index:1085 !important }

.tmx-card{ background:var(--card); border:1px solid var(--br); border-radius:14px; padding:12px }

.table thead th{
  background:#eaf1ff !important; position:sticky; top:0; z-index:4; color:#3a4a68; font-weight:700
}
.table tbody tr:nth-child(odd) td{ background:var(--row1) }
.table tbody tr:nth-child(even) td{ background:var(--row2) }

.scroll-y{ max-height:58vh; overflow:auto }

.btn-x:disabled,.btn-x[disabled]{ opacity:.45; filter:grayscale(.35); cursor:not-allowed; box-shadow:none }
.btn-back{ --bg:#334155 } .btn-export{ --bg:#0ea5e9 } .btn-refresh{ --bg:#16a34a }

.toast.tmx{ border:0; color:#fff } .toast.tmx.ok{ background:#16a34a }
.toast.tmx.warn{ background:#d97706 } .toast.tmx.err{ background:#dc2626 }
#toastHost{ position:fixed; top:10px; left:50%; transform:translateX(-50%); z-index:1080 }

.toast.tmx.promo-err{
  background: linear-gradient(135deg,#ef4444,#b91c1c);
  color:#fff; border:0; border-radius:14px;
  box-shadow:0 18px 40px -16px rgba(220,38,38,.55);
}
.toast.tmx .promo-icon{
  display:inline-flex; align-items:center; justify-content:center;
  width:22px; height:22px; margin-right:8px;
}
@media (max-width:576px){
  #toastHost{ left:50%; transform:translateX(-50%); width:92%; }
  .toast.tmx .toast-body{ font-size:.92rem; }
}

#confirmModal .modal-content{ border-left:6px solid var(--bad); background:#fff7f7 }
#confirmBody{ color:#7f1d1d }

#assignBox .table thead th{ background:#eef2ff !important; color:#3a4a68 }

.pgn-wrap{ background:linear-gradient(180deg,#f7fbff,#eef6ff); border:1px solid var(--br); border-radius:12px; padding:6px 10px }
.pagination .page-link{ background:var(--hl); color:#fff; border-color:#1d4ed8; box-shadow:0 6px 14px -8px rgba(37,99,235,.4) }
.pagination .page-item.active .page-link{ background:#1d4ed8; border-color:#1d4ed8 }
.pagination .page-link:hover{ filter:brightness(1.05); color:#fff }
.pagination .page-item.disabled .page-link{ background:#93c5fd; border-color:#60a5fa }

#infoModal .modal-content{ background:#fff4e5; border-left:6px solid var(--warn) }
#infoBody{ color:#7a4b00 }

@media (max-width: 992px){
  .metric-grid{ grid-template-columns:repeat(3,minmax(0,1fr)) }
  .topbar .d-flex{ flex-wrap:wrap }
}
@media (max-width: 576px){
  .metric-grid{ grid-template-columns:repeat(2,minmax(0,1fr)) }
  .ts-wrapper{ min-width:180px }
  .controls .row > .col-auto{ width:100% }
  .controls .form-select, .controls .form-control{ width:100% }
  td:nth-child(3) .small{ display:block }
  td:last-child .btn-x{ margin-bottom:6px }
}

@media print{ .no-print{ display:none !important } }
</style>
@endpush

@section('content')
<div class="container-fluid">

  <div class="topbar py-2 mb-2">
    <div class="d-flex align-items-center gap-2 gap-md-3">
      <h1 class="tmx-title flex-grow-1 mb-0">Registration & Enrollment Management</h1>

      <div class="d-flex align-items-stretch gap-2 order-3 order-md-2">
        <a id="btnBack" class="btn-x btn-back btn-xs" href="javascript:history.back()">Back</a>
        <a id="btnExport" class="btn-x btn-export btn-xs" href="#">Export CSV</a>
        <button id="btnRefresh" class="btn-x btn-refresh btn-xs">Refresh</button>
      </div>

      <div class="metrics-card order-2 order-md-3" style="min-width:520px">
        <div class="metric-grid">
          <div class="metric"><small>Approved</small><span id="m_approved">0</span></div>
          <div class="metric"><small>Pending</small><span id="m_pending">0</span></div>
          <div class="metric"><small>Declined</small><span id="m_declined">0</span></div>
          <div class="metric"><small>Division Admin</small><span id="m_r5">0</span></div>
          <div class="metric"><small>District Admin</small><span id="m_r6">0</span></div>
          <div class="metric"><small>Cluster Admin</small><span id="m_r7">0</span></div>
        </div>
      </div>
    </div>
  </div>

  <div class="controls mb-2">
    <div class="row g-3 align-items-end" style="overflow:visible">
      <div class="col-auto">
        <label class="small" style="color:var(--sub)">Page size</label>
        <select id="size" class="form-select form-select-sm">
          <option>10</option><option selected>25</option><option>50</option><option>100</option>
        </select>
      </div>

      <div class="col-auto">
        <label class="small" style="color:var(--sub)">Search</label>
        <input id="q" class="form-control form-control-sm" placeholder="ID, UID, name, phone, area, role, approval">
      </div>

      <div class="col-auto">
        <label class="small" style="color:var(--sub)">Sort</label>
        <select id="sort_by" class="form-select form-select-sm">
          <option value="id">ID</option><option value="user">User</option><option value="phone">Phone</option>
          <option value="reg_type">Registration Type</option><option value="division">Division</option>
          <option value="district">District</option><option value="upazila">Upazila</option>
          <option value="approval_status">Approval</option><option value="role">Role</option>
        </select>
      </div>

      <div class="col-auto">
        <label class="small" style="color:var(--sub)">Order</label>
        <select id="sort_dir" class="form-select form-select-sm">
          <option value="">—</option><option value="asc">ASC</option><option value="desc" selected>DESC</option>
        </select>
      </div>

      <div class="col-auto" style="z-index:9">
        <label class="small" style="color:var(--sub)">Special Column</label>
        <select id="sp_col" autocomplete="off">
          <option value="">Select column</option>
          <option value="reg_type">Registration Type</option>
          <option value="role">Role</option>
          <option value="approval_status">Approval Status</option>
        </select>
      </div>

      <div class="col-auto" style="z-index:9">
        <label class="small" style="color:var(--sub)">Special Value</label>
        <select id="sp_val" autocomplete="off">
          <option value="">Select value</option>
        </select>
      </div>

      <div class="col-auto">
        <label class="small" style="color:transparent">.</label><br>
        <button id="sp_search" class="btn-x btn-input btn-xs">Special Filter Search</button>
      </div>

      <div class="col-auto">
        <label class="small" style="color:var(--sub)">Search User</label>
        <input id="user_id" class="form-control form-control-sm" maxlength="10" style="width:100px" placeholder="Search User" inputmode="numeric" pattern="[0-9]*">
      </div>
    </div>
  </div>

  <div class="tmx-card">
    <div class="table-responsive scroll-y">
      <table class="table table-sm align-middle mb-0" id="tbl">
        <thead>
          <tr>
            <th style="width:60px">ID</th>
            <th style="width:80px">User ID</th>
            <th>User</th>
            <th>Registration Type</th>
            <th>Division</th>
            <th>District</th>
            <th>Upazila</th>
            <th>Approval</th>
            <th>Role</th>
            <th style="min-width:480px">Actions</th>
          </tr>
        </thead>
        <tbody id="tbody">
          <tr><td colspan="10" class="text-center" style="color:var(--sub);padding:22px">Loading…</td></tr>
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-center mt-3">
      <div class="pgn-wrap">
        <nav><ul class="pagination pagination-sm mb-0" id="pgn"></ul></nav>
      </div>
    </div>
  </div>

  {{-- Role Assignment / Promotion Modal --}}
  <div class="modal fade" id="roleAssignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="ra_title">Role Assignment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="roleAssignForm">
            <input type="hidden" id="ra_reg_id">
            <input type="hidden" id="ra_user_id">
            <input type="hidden" id="ra_cur_role_id">
            <div class="mb-2">
              <label class="form-label fw-bold">Role</label>
              <div id="rolesBox" class="d-flex flex-wrap gap-3 small" style="color:var(--sub)">Loading roles…</div>
              <div id="coreDeassignWrap" class="mt-2" hidden>
                <button type="button" id="btnCoreDeassign" class="btn-x btn-decline btn-xs">De-assign</button>
              </div>
            </div>
            <div id="dynamicArea" class="tmx-card mb-3">
              <div class="small" style="color:var(--sub)">Geo selectors appear here when required by the role.</div>
            </div>
            <div id="assignBox" class="tmx-card" hidden>
              <div class="fw-bold mb-2" style="color:var(--ink)">Existing assignments for this role</div>
              <div class="table-responsive">
                <table class="table table-sm" id="assignTbl">
                  <thead id="assignHead"></thead>
                  <tbody id="assignBody"><tr><td class="text-muted">No records</td></tr></tbody>
                </table>
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <span id="raBusy" class="small text-muted me-auto" hidden>Processing…</span>
          <button type="button" class="btn-x btn-cancel btn-xs" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn-x btn-save btn-xs" id="btnRoleAssignGo">Save</button>
        </div>
      </div>
    </div>
  </div>

  {{-- PDF Modal --}}
  <div class="modal fade" id="pdfModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Registration Preview</h5>
          <button type="button" class="btn-x btn-view btn-xs no-print me-2" id="btnPdfDownload">Download</button>
          <button type="button" class="btn-x btn-view btn-xs no-print" id="btnPdfPrint">Print</button>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-0">
          <iframe id="pdfFrame" src="about:blank" style="width:100%;height:75vh;border:0"></iframe>
        </div>
      </div>
    </div>
  </div>

  {{-- Notice Modal --}}
  <div class="modal fade" id="infoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
      <div class="modal-content">
        <div class="modal-header"><h6 class="modal-title">Notice</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body" id="infoBody">Message</div>
        <div class="modal-footer"><button type="button" class="btn-x btn-add btn-xs" data-bs-dismiss="modal">OK</button></div>
      </div>
    </div>
  </div>

  {{-- Confirm Modal --}}
  <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
      <div class="modal-content" id="confirmCard">
        <div class="modal-header"><h6 class="modal-title" id="confirmTitle">Confirm</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body" id="confirmBody">Are you sure?</div>
        <div class="modal-footer">
          <button type="button" class="btn-x btn-view btn-xs" data-bs-dismiss="modal">No</button>
          <button type="button" class="btn-x btn-escalate btn-xs" id="confirmYes">Yes</button>
        </div>
      </div>
    </div>
  </div>

  <div id="toastHost" class="p-3">
    <div id="tmxToast" class="toast tmx ok" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-body" id="toastBody">Done</div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
/* ===== Notifier ===== */
function notify(type, msg){
  if (window.toastr) {
    const m = String(msg || 'Done');
    if (type === 'err' || type === 'error') return toastr.error(m);
    if (type === 'warn' || type === 'warning') return toastr.warning(m);
    if (type === 'info') return toastr.info(m);
    return toastr.success(m);
  }
  const t = document.getElementById('tmxToast');
  if (t) {
    const body = document.getElementById('toastBody');
    body.textContent = msg || 'Done';
    t.className = 'toast tmx ' + (type==='err'?'err':type==='warn'?'warn':'ok');
    new bootstrap.Toast(t, { delay: 2600 }).show();
    return;
  }
  alert(msg || (type==='err' ? 'Error' : 'Notice'));
}
function toast(msg, type='ok'){ const t=document.getElementById('tmxToast'); t.className='toast tmx '+type; document.getElementById('toastBody').textContent=msg||'OK'; new bootstrap.Toast(t,{delay:2400}).show(); }
function showInfo(msg){ document.getElementById('infoBody').innerHTML = msg; new bootstrap.Modal('#infoModal').show(); }

/* === explicit alert for promotion errors === */
function showPromotionError(msg){
  const host = document.getElementById('toastHost') || document.body;
  const el   = document.createElement('div');
  el.className = 'toast tmx promo-err';
  el.setAttribute('role','alert'); el.setAttribute('aria-live','assertive'); el.setAttribute('aria-atomic','true');
  const safe = (s)=>String(s||'Promotion failed.').replace(/[&<>"]/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[m]));
  el.innerHTML =
    `<div class="toast-body d-flex align-items-center">
       <span class="promo-icon" aria-hidden="true">
         <svg viewBox="0 0 24 24" width="22" height="22" fill="none" xmlns="http://www.w3.org/2000/svg">
           <circle cx="12" cy="12" r="11" stroke="rgba(255,255,255,.9)" stroke-width="2"/>
           <path d="M12 6v7" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
           <circle cx="12" cy="17" r="1.6" fill="#fff"/>
         </svg>
       </span>
       <div>${safe(msg)}</div>
     </div>`;
  host.appendChild(el);
  const t = new bootstrap.Toast(el, { delay: 3600, autohide: true });
  el.addEventListener('hidden.bs.toast', ()=> el.remove(), { once:true });
  t.show();
}

/* ===== Global 422 handlers ===== */
window.rmgmHandle422 = function (payload) {
  const code = payload?.code || '';
  const uid   = payload.admin_user_id ?? payload.user_id ?? payload.assigned_user_id ?? '—';
  const name  = payload.admin_name ?? payload.name ?? payload.user_name ?? 'Unknown';
  const role  = payload.role_name ?? payload.role ?? '';

  if (code === 'SUPERADMIN_TAKEN') return notify('err', `Super Admin already assigned: ${name} (User ID: ${uid})`);
  if (code === 'CEO_TAKEN')       return notify('err', `CEO already assigned: ${name} (User ID: ${uid})`);
  if (code === 'DIVISION_TAKEN')  return notify('err', `Division Admin already assigned: ${name} (User ID: ${uid})`);
  if (code === 'DISTRICT_TAKEN')  return notify('err', `District Admin already assigned: ${name} (User ID: ${uid})`);
  if (code === 'CLUSTER_TAKEN' || code === 'CLUSTER_ADMIN_TAKEN' || code === 'SUPERVISOR_TAKEN')
    return notify('err', `Cluster Admin already assigned: ${name} (User ID: ${uid})`);

  if (code === 'ROLE_TAKEN' || code === 'SINGLE_ROLE_TAKEN') {
    const rlabel = role ? role : 'This role';
    return notify('err', `${rlabel} already assigned: ${name} (User ID: ${uid})`);
  }

  if (code === 'ROLE_LOCKED')     return notify('warn', payload?.msg || 'Role is locked for approved users.');
  if (code === 'ALREADY_MEMBER')  return notify('info', payload?.msg || 'Already a member of this cluster.');
  return notify('err', payload?.msg || 'Validation/constraint error.');
};
window.rmgmOnAjaxError = async function (res) {
  if (res.status === 422) {
    try { const j = await res.json(); return window.rmgmHandle422(j); } catch (e) {}
    return notify('err', 'Validation/constraint error.');
  }
  notify('err', 'Server error.');
};

/* ===== Page logic ===== */
(() => {
  const base = location.pathname.replace(/\/manage.*$/, '');
  const csrf = '{{ csrf_token() }}';
  const q = (id)=>document.getElementById(id);
  const tbody = q('tbody'); const pgn = q('pgn');

  let state = {page:1,size:25,search:'',sort_by:'id',sort_dir:'desc',sp_col:'',sp_val:'',user_id:''};

  const titleCase = (s)=> (s||'-').toString().toLowerCase().replace(/\b\w/g,m=>m.toUpperCase());

  function allowedTargetsForRole(cur){
    switch(Number(cur)){
      case 8: return [7];
      case 7: return [6];
      case 6: return [5];
      case 5: return [4];
      case 4: return [3,2];
      case 3: return [2];
      default: return [];
    }
  }

  function actionButtons(r){
    const approved = r.approval_status === 'approved';
    const isCompanyOfficerReg = (r.registration_type === 'company_officer');
    const curRole = Number(r.role_id || 0);

    const targets = allowedTargetsForRole(curRole);
    const canPromote = approved && isCompanyOfficerReg && targets.length > 0;

    const roleType = (r.role_type_name || '').toLowerCase();
    const disType = (r.registration_type==='client' || r.registration_type==='professional');
    const canTerminate = approved && !disType && !(roleType==='client' || roleType==='professional');

    const B = (act, cls, dis=false, label=null) =>
      `<button class="btn-x ${cls} btn-xs me-1 mb-1" data-act="${act}" data-id="${r.id}" ${dis?'disabled':''}>${label||titleCase(act)}</button>`;
    return [
      B('pdf','btn-view', false, 'View Reg'),
      B('approve','btn-approve', disType || approved, 'Approve'),
      B('decline','btn-decline', disType || !r.approval_status || r.approval_status!=='pending', 'Decline'),
      B('role','btn-edit', disType || !approved, 'Role Assign/De-Assign'),
      B('promote','btn-add', !canPromote, 'Promote'),
      B('terminate','btn-escalate', !canTerminate, 'Terminate')
    ].join('');
  }

  function renderRows(rows){
    if(!rows.length){ tbody.innerHTML = `<tr><td colspan="10" class="text-center" style="color:var(--sub);padding:22px">No records</td></tr>`; return; }
    tbody.innerHTML = rows.map(r=>`
      <tr>
        <td>${r.id}</td>
        <td>${r.user_id ?? '-'}</td>
        <td>
          <div class="fw-bold" style="color:var(--ink)">${r.user_name ?? '-'}</div>
          <div class="small" style="color:var(--sub)">UID: ${r.user_id ?? '-'} • ${r.phone ?? '-'}</div>
        </td>
        <td>${titleCase(r.registration_type)}</td>
        <td>${r.division_name ?? '-'}</td>
        <td>${r.district_name ?? '-'}</td>
        <td>${r.upazila_name ?? '-'}</td>
        <td>${titleCase(r.approval_status)}</td>
        <td>${r.role_name ?? '-'}</td>
        <td>${actionButtons(r)}</td>
      </tr>
    `).join('');
  }

  function renderPgn(total,size,page){
    const pages = Math.max(1, Math.ceil(total/size));
    const mk = (p,txt,dis=false,act='page') => `<li class="page-item ${dis?'disabled':''}${p===page?' active':''}"><a class="page-link" href="#" data-${act}="${p}">${txt}</a></li>`;
    let html = mk(1,'First', page===1) + mk(Math.max(1,page-1),'Prev', page===1);
    const left = Math.max(1, page-2), right = Math.min(pages, page+2);
    for(let i=left;i<=right;i++) html += mk(i, String(i), false);
    html += mk(Math.min(pages,page+1),'Next', page===pages) + mk(pages,'Last', page===pages);
    pgn.innerHTML = html;
  }

  async function fetchData(params){
    const url = new URL(base + '/manage/data', location.origin);
    Object.entries(params).forEach(([k,v])=>{ if(v!=='' && v!=null) url.searchParams.set(k,v); });
    const r = await fetch(url);
    return r.json().catch(()=>({ok:false}));
  }

  async function load(){
    const p = {...state}; if(!p.sort_dir) delete p.sort_dir;
    const j = await fetchData(p);
    if(!j.ok){ toast(j.msg||'Load failed','err'); return; }
    renderRows(j.rows || []);
    renderPgn(j.total||0, j.size||25, j.page||1);
    q('m_approved').textContent = j.counts?.approved ?? 0;
    q('m_pending').textContent  = j.counts?.pending ?? 0;
    q('m_declined').textContent = j.counts?.declined ?? 0;
    await loadRoleTotals();
  }

  async function loadRoleTotals(){
    const names = {5:'Division Admin',6:'District Admin',7:'Cluster Admin'};
    for(const [rid, rname] of Object.entries(names)){
      const j = await fetchData({page:1,size:1,sp_col:'role',sp_val:rname});
      q('m_r'+rid).textContent = j.ok ? (j.total||0) : 0;
    }
  }

  pgn.addEventListener('click', (e)=>{
    const a = e.target.closest('a[data-page]'); if(!a) return;
    e.preventDefault(); state.page = parseInt(a.getAttribute('data-page'),10); load();
  });

  /* ===== Role modal ===== */
  let roleCache=null;
  let currentRoleAtOpen = 0;
  let lastAssignRoleId = 0; // remembers which role's assignments are shown

  async function ensureRoles(){
    if(roleCache) return roleCache;
    const r = await fetch(base + '/manage/roles'); const j = await r.json().catch(()=>({ok:false,roles:[]}));
    roleCache = j.ok? j.roles: [];
    return roleCache;
  }
  function radioGroup(name, items, checkedId=null, locked=false){
    return items.map(ro => {
      const checked = checkedId && Number(checkedId)===Number(ro.id) ? 'checked' : '';
      const disabled = locked ? 'disabled' : '';
      return `<div class="form-check me-3">
        <input class="form-check-input" type="radio" name="${name}" id="${name}_${ro.id}" value="${ro.id}" ${checked} ${disabled}>
        <label class="form-check-label" for="${name}_${ro.id}">${ro.name} (${ro.id})</label>
      </div>`;
    }).join('');
  }

  async function openRoleModal(regId){
    promoMode = false;
    q('ra_title').textContent = 'Role Assignment';
    await buildRoleModal(regId, {mode:'assign'});
    bootstrap.Modal.getOrCreateInstance('#roleAssignModal').show();
  }

  async function buildRoleModal(regId, {mode}){
    q('roleAssignForm').reset();
    q('ra_reg_id').value = regId;
    q('ra_user_id').value = '';
    q('ra_cur_role_id').value = '';
    q('rolesBox').innerHTML = 'Loading roles…';
    q('dynamicArea').innerHTML = `<div class="small" style="color:var(--sub)">Geo selectors appear here when required by the role.</div>`;
    q('assignBox').hidden = true; q('assignHead').innerHTML=''; q('assignBody').innerHTML='';
    q('coreDeassignWrap').hidden = true; q('btnRoleAssignGo').hidden = false;
    lastAssignRoleId = 0;

    const jrow = await fetchData({page:1,size:1,sp_col:'id',sp_val:regId});
    const row = (jrow.rows||[])[0] || {};
    if(row.user_id) q('ra_user_id').value = row.user_id;
    if(row.role_id) { q('ra_cur_role_id').value = row.role_id; currentRoleAtOpen = Number(row.role_id); }

    const roles = await ensureRoles();

    if(mode==='assign'){
      const currentRoleId = Number(row?.role_id || 0);
      const lockRole = (row?.approval_status==='approved' && currentRoleId>=1 && currentRoleId<=8);
      if(lockRole && currentRoleId){
        const cur = roles.find(r=>Number(r.id)===currentRoleId);
        q('rolesBox').innerHTML = radioGroup('role_id', [cur], currentRoleId, true);
        const isCore = [1,2,3,4].includes(currentRoleId);
        q('btnRoleAssignGo').hidden = isCore;
        q('coreDeassignWrap').hidden = !isCore;
        if(!isCore){ await renderRoleExtras(currentRoleId, Number(q('ra_user_id').value||0), regId); }
      }else{
        q('rolesBox').innerHTML = radioGroup('role_id', roles, null, false);
        q('rolesBox').addEventListener('change', onRoleChange);
      }
    }

    if(mode==='promote'){
      const cur = Number(row?.role_id || 0);
      currentRoleAtOpen = cur;
      const targets = allowedTargetsForRole(cur);
      const showRoles = roles.filter(r=>targets.includes(Number(r.id)));
      q('rolesBox').innerHTML = showRoles.length ? radioGroup('target_role_id', showRoles, null, false)
                                                : `<div class="text-muted">No higher roles available.</div>`;
      q('rolesBox').addEventListener('change', async ()=>{
        const sel = document.querySelector('input[name="target_role_id"]:checked');
        if(!sel){ q('dynamicArea').innerHTML = `<div class="small" style="color:var(--sub)">Pick a role to continue.</div>`; return; }
        await renderPromoExtras(Number(sel.value), Number(q('ra_user_id').value||0), regId);
      });
      q('dynamicArea').innerHTML = `<div class="small" style="color:var(--sub)">Pick a higher role. Division/District/Cluster selectors will appear if needed.</div>`;
      q('assignBox').hidden = true;
    }

    q('btnCoreDeassign').onclick = async ()=>{
      const ok = await confirmAction('De-assign role?','This will set status=0 and approval to pending, and demote to Guest.');
      if(!ok) return;
      const r = await fetch(`${base}/manage/${regId}/deassign-core`, {method:'POST', headers:{'X-CSRF-TOKEN':csrf}});
      if (r.status === 422) { return window.rmgmOnAjaxError(r); }
      const j = await r.json(); toast(j.msg||'Done', j.ok?'ok':'err');
      if(j.ok){ bootstrap.Modal.getOrCreateInstance('#roleAssignModal').hide(); await load(); }
    };
  }

  async function onRoleChange(){
    const fd = new FormData(q('roleAssignForm'));
    const rid = Number(fd.get('role_id'));
    await renderRoleExtras(rid, Number(q('ra_user_id').value||0), Number(q('ra_reg_id').value||0));
  }

  async function renderRoleExtras(rid, uid, regId){
    const el = q('dynamicArea');
    const selectHtml = (id, label, opts)=>
      `<label class="form-label fw-bold">${label}</label>
       <select id="${id}" class="form-select form-select-sm"><option value="">Select ${label.toLowerCase()}</option>${opts}</select>`;

    if([1,2,3,4].includes(rid)){ el.innerHTML = `<div class="small" style="color:var(--sub)">No extra inputs needed for Head Office roles.</div>`; q('assignBox').hidden=true; lastAssignRoleId = 0; return; }

    if(rid===5){
      const ds = await fetch(`${base}/manage/geo/divisions?exclude_user_id=${uid}`).then(r=>r.json()).catch(()=>({items:[]}));
      const opts = (ds.items||[]).map(x=>`<option value="${x.id}">${x.short_code??''} ${x.name}</option>`).join('');
      el.innerHTML = selectHtml('in_division','Division', opts);
      lastAssignRoleId = 5;
      await loadAssignments(regId, 5);
    } else if(rid===6){
      const divs = await fetch(`${base}/manage/geo/divisions`).then(r=>r.json()).catch(()=>({items:[]}));
      el.innerHTML = `
        <div class="row g-2">
          <div class="col-12 col-md-6">${selectHtml('in_division','Division', (divs.items||[]).map(x=>`<option value=\"${x.id}\">${x.short_code??''} ${x.name}</option>`).join(''))}</div>
          <div class="col-12 col-md-6">${selectHtml('in_district','District','')}</div>
        </div>`;
      q('in_division').addEventListener('change', async e=>{
        const vid = Number(e.target.value||0);
        const dst = await fetch(`${base}/manage/geo/districts?division_id=${vid}&exclude_user_id=${uid}`).then(r=>r.json()).catch(()=>({items:[]}));
        q('in_district').innerHTML = `<option value="">Select district</option>` + (dst.items||[]).map(x=>`<option value=\"${x.id}\">${x.short_code??''} ${x.name}</option>`).join('');
      });
      lastAssignRoleId = 6;
      await loadAssignments(regId, 6);
    } else if(rid===8){
      const divs = await fetch(`${base}/manage/geo/divisions`).then(r=>r.json()).catch(()=>({items:[]}));
      el.innerHTML = `
        <div class="row g-2">
          <div class="col-12 col-md-4">${selectHtml('in_division','Division', (divs.items||[]).map(x=>`<option value=\"${x.id}\">${x.short_code??''} ${x.name}</option>`).join(''))}</div>
          <div class="col-12 col-md-4">${selectHtml('in_district','District','')}</div>
          <div class="col-12 col-md-4">${selectHtml('in_cluster','Cluster','')}</div>
        </div>`;
      q('in_division').addEventListener('change', async e=>{
        const vid = Number(e.target.value||0);
        const dst = await fetch(`${base}/manage/geo/districts?division_id=${vid}`).then(r=>r.json()).catch(()=>({items:[]}));
        q('in_district').innerHTML = `<option value="">Select district</option>` + (dst.items||[]).map(x=>`<option value=\"${x.id}\">${x.short_code??''} ${x.name}</option>`).join('');
        q('in_cluster').innerHTML = `<option value="">Select cluster</option>`;
      });
      q('in_district').addEventListener('change', async e=>{
        const did = Number(e.target.value||0);
        const cl = await fetch(`${base}/manage/geo/clusters?district_id=${did}&exclude_member_user_id=${uid}`).then(r=>r.json()).catch(()=>({items:[]}));
        q('in_cluster').innerHTML = `<option value="">Select cluster</option>` + (cl.items||[]).map(x=>`<option value=\"${x.id}\">${x.short_code??''} ${x.cluster_name}</option>`).join('');
      });
      lastAssignRoleId = 8;
      await loadAssignments(regId, 8);
    } else if(rid===7){
      const divs = await fetch(`${base}/manage/geo/divisions`).then(r=>r.json()).catch(()=>({items:[]}));
      el.innerHTML = `
        <div class="row g-2">
          <div class="col-12 col-md-4">${selectHtml('in_division','Division', (divs.items||[]).map(x=>`<option value=\"${x.id}\">${x.short_code??''} ${x.name}</option>`).join(''))}</div>
          <div class="col-12 col-md-4">${selectHtml('in_district','District','')}</div>
          <div class="col-12 col-md-4">${selectHtml('in_cluster','Cluster','')}</div>
        </div>`;
      q('in_division').addEventListener('change', async e=>{
        const vid = Number(e.target.value||0);
        const dst = await fetch(`${base}/manage/geo/districts?division_id=${vid}`).then(r=>r.json()).catch(()=>({items:[]}));
        q('in_district').innerHTML = `<option value="">Select district</option>` + (dst.items||[]).map(x=>`<option value=\"${x.id}\">${x.short_code??''} ${x.name}</option>`).join('');
        q('in_cluster').innerHTML = `<option value="">Select cluster</option>`;
      });
      q('in_district').addEventListener('change', async e=>{
        const did = Number(e.target.value||0);
        const cl = await fetch(`${base}/manage/geo/clusters?district_id=${did}&exclude_supervisor_user_id=${uid}`).then(r=>r.json()).catch(()=>({items:[]}));
        q('in_cluster').innerHTML = `<option value="">Select cluster</option>` + (cl.items||[]).map(x=>`<option value=\"${x.id}\">${x.short_code??''} ${x.cluster_name}</option>`).join('');
      });
      lastAssignRoleId = 7;
      await loadAssignments(regId, 7);
    }
  }

  async function renderPromoExtras(targetRid, uid, regId){
    return renderRoleExtras(targetRid, uid, regId);
  }

  // ===== Assignments list with per-row De-assign buttons
  async function loadAssignments(regId, roleId){
    const elHead = document.getElementById('assignHead');
    const elBody = document.getElementById('assignBody');
    const box    = document.getElementById('assignBox');

    const url = new URL(location.pathname.replace(/\/manage.*$/, '') + `/manage/${regId}/modal-info`, location.origin);
    url.searchParams.set('role_id', roleId);
    const j = await fetch(url).then(r=>r.json()).catch(()=>({ok:false}));

    if(!j.ok){ box.hidden = true; elHead.innerHTML=''; elBody.innerHTML='<tr><td class="text-muted">No records</td></tr>'; return; }

    lastAssignRoleId = roleId;

    if (roleId === 5){
      elHead.innerHTML = '<tr><th>User</th><th>Division</th><th>Assigned At</th><th style="width:120px">Actions</th></tr>';
      elBody.innerHTML = (j.rows||[]).length
        ? j.rows.map(r=>`<tr>
            <td>${r.name||'-'}</td>
            <td>${r.division||'-'}</td>
            <td>${r.assigned_at||'-'}</td>
            <td>
              <button class="btn-x btn-decline btn-xs" data-deassign="division"
                      data-user_id="${r.user_id||''}" data-division_id="${r.division_id||''}">
                De-assign
              </button>
            </td>
          </tr>`).join('')
        : '<tr><td colspan="4" class="text-muted">No records</td></tr>';
    } else if (roleId === 6){
      elHead.innerHTML = '<tr><th>User</th><th>Division</th><th>District</th><th>Assigned At</th><th style="width:120px">Actions</th></tr>';
      elBody.innerHTML = (j.rows||[]).length
        ? j.rows.map(r=>`<tr>
            <td>${r.name||'-'}</td>
            <td>${r.division||'-'}</td>
            <td>${r.district||'-'}</td>
            <td>${r.assigned_at||'-'}</td>
            <td>
              <button class="btn-x btn-decline btn-xs" data-deassign="district"
                      data-user_id="${r.user_id||''}" data-district_id="${r.district_id||''}">
                De-assign
              </button>
            </td>
          </tr>`).join('')
        : '<tr><td colspan="5" class="text-muted">No records</td></tr>';
    } else if (roleId === 7){
      elHead.innerHTML = '<tr><th>User</th><th>Division</th><th>District</th><th>Cluster</th><th style="width:120px">Actions</th></tr>';
      elBody.innerHTML = (j.rows||[]).length
        ? j.rows.map(r=>`<tr>
            <td>${r.name||'-'}</td>
            <td>${r.division||'-'}</td>
            <td>${r.district||'-'}</td>
            <td>${r.cluster_name||'-'}</td>
            <td>
              <button class="btn-x btn-decline btn-xs" data-deassign="cluster_admin"
                      data-cluster_id="${r.cluster_id||''}">
                De-assign
              </button>
            </td>
          </tr>`).join('')
        : '<tr><td colspan="5" class="text-muted">No records</td></tr>';
    } else if (roleId === 8){
      elHead.innerHTML = '<tr><th>User</th><th>Division</th><th>District</th><th>Cluster</th><th style="width:120px">Actions</th></tr>';
      elBody.innerHTML = (j.rows||[]).length
        ? j.rows.map(r=>`<tr>
            <td>${r.name||'-'}</td>
            <td>${r.division||'-'}</td>
            <td>${r.district||'-'}</td>
            <td>${r.cluster_name||'-'}</td>
            <td>
              <button class="btn-x btn-decline btn-xs" data-deassign="cluster_member"
                      data-user_id="${r.user_id||''}" data-cluster_id="${r.cluster_id||''}">
                De-assign
              </button>
            </td>
          </tr>`).join('')
        : '<tr><td colspan="5" class="text-muted">No records</td></tr>';
    } else {
      elHead.innerHTML = ''; elBody.innerHTML = '<tr><td class="text-muted">No records</td></tr>';
    }
    box.hidden = false;
  }

  function confirmAction(title, body){
    q('confirmTitle').textContent = title||'Confirm'; q('confirmBody').textContent = body||'Are you sure?';
    return new Promise(resolve=>{
      const modal = new bootstrap.Modal('#confirmModal'); const yes=q('confirmYes');
      const done=()=>{ yes.removeEventListener('click',handler); resolve(false); };
      const handler=()=>{ yes.removeEventListener('click',handler); modal.hide(); resolve(true); };
      yes.addEventListener('click',handler,{once:true});
      document.getElementById('confirmModal').addEventListener('hidden.bs.modal', done, {once:true});
      modal.show();
    });
  }

  // Main table actions
  q('tbl').addEventListener('click', async (e)=>{
    const b = e.target.closest('button[data-act]'); if(!b) return;
    const id = b.getAttribute('data-id'); const act = b.getAttribute('data-act');

    if(act==='pdf'){ q('pdfFrame').src = `${base}/manage/${id}/pdf`; new bootstrap.Modal('#pdfModal').show(); return; }
    if(act==='role'){ openRoleModal(id); return; }

    if(act==='promote'){
      await openPromoteModal(id);
      return;
    }

    if(act==='terminate' || act==='decline'){
      const ok = await confirmAction(
        act==='terminate'?'Terminate user?':'Decline registration?',
        act==='terminate'?'This will remove all active assignments and demote to Guest. Continue?':'This will mark as declined. Continue?'
      );
      if(!ok) return;
    }

    try{
      const r = await fetch(`${base}/manage/${id}/${act}`, { method:'POST', headers:{'X-CSRF-TOKEN':csrf} });
      if (r.status === 422) { return window.rmgmOnAjaxError(r); }
      const j = await r.json();
      if(!j.ok && j.code){ window.rmgmHandle422(j); }
      else { toast(j.msg || (j.ok?'OK':'Error'), j.ok ? 'ok' : 'err'); }
      await load();
    }catch(err){ toast('Request failed','err'); }
  });

  let promoMode = false;
  async function openPromoteModal(regId){
    promoMode = true;
    q('ra_title').textContent = 'Promote to Higher Role';
    await buildRoleModal(regId, {mode:'promote'});
    bootstrap.Modal.getOrCreateInstance('#roleAssignModal').show();
  }

  q('btnRoleAssignGo').addEventListener('click', async ()=>{
    const regId = q('ra_reg_id').value;

    if(promoMode){
      const target = document.querySelector('input[name="target_role_id"]:checked');
      if(!target){ return toast('Select a target role','warn'); }
      const target_role_id = Number(target.value);

      const cur = Number(currentRoleAtOpen || q('ra_cur_role_id').value || 0);
      const allowed = allowedTargetsForRole(cur);
      if(!allowed.includes(target_role_id)){
        return showPromotionError('Invalid target: target role must be higher than current (numerically smaller).');
      }

      const payload = { target_role_id };
      const div = q('in_division')?.value || '';
      const dst = q('in_district')?.value || '';
      const clu = q('in_cluster')?.value || '';
      if(target_role_id===5){
        if(!div) return toast('Select Division', 'warn');
        payload.division_id = Number(div);
      }else if(target_role_id===6){
        if(!div || !dst) return toast('Select Division & District', 'warn');
        payload.division_id = Number(div);
        payload.district_id = Number(dst);
      }else if(target_role_id===7 || target_role_id===8){
        if(!dst || !clu) return toast('Select District & Cluster', 'warn');
        payload.district_id = Number(dst);
        payload.cluster_id  = Number(clu);
      }

      q('raBusy').hidden = false;
      try{
        const r = await fetch(`${base}/manage/${regId}/promote`, {
          method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf},
          body: JSON.stringify(payload)
        });

        if (r.status === 422){
          try{
            const j = await r.json();
            showPromotionError(j?.msg || 'Promotion failed due to validation/constraint.');
          }catch(e){
            showPromotionError('Promotion failed due to validation/constraint.');
          }
          q('raBusy').hidden = true; return;
        }

        const j = await r.json();
        if(!j.ok){
          showPromotionError(j?.msg || 'Promotion failed.');
        }else{
          toast('Promoted Successfully !', 'ok');
          bootstrap.Modal.getOrCreateInstance('#roleAssignModal').hide();
          await load();
        }
      }catch(e){
        showPromotionError('Request failed.');
      }
      q('raBusy').hidden = true;
      promoMode = false;
      return;
    }

    const fd2 = new FormData(q('roleAssignForm'));
    let role_id = Number(fd2.get('role_id') || 0);
    if(!role_id){
      const cur = Number(q('ra_cur_role_id').value||0);
      if(cur) role_id = cur;
    }
    if(!role_id) return toast('Pick a role','warn');

    const payload = { role_id };
    if([5,6,7,8].includes(role_id)){
      const div = q('in_division')?.value||'';
      const dst = q('in_district')?.value||'';
      const clu = q('in_cluster')?.value||'';
      if(role_id===5 && !div) return toast('Select Division', 'warn');
      if(role_id===6 && (!div || !dst)) return toast('Select Division & District', 'warn');
      if(role_id===8 && (!dst || !clu)) return toast('Select District & Cluster', 'warn');
      if(role_id===7 && (!dst || !clu)) return toast('Select District & Cluster', 'warn');
      if(div) payload.division_id = Number(div);
      if(dst) payload.district_id = Number(dst);
      if(clu) payload.cluster_id  = Number(clu);
    }
    q('raBusy').hidden = false;
    try{
      const r = await fetch(`${base}/manage/${regId}/role-assign`, {
        method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf}, body: JSON.stringify(payload)
      });
      if (r.status === 422) { q('raBusy').hidden = true; return window.rmgmOnAjaxError(r); }
      const j = await r.json();

      if(!j.ok && j.code){
        window.rmgmHandle422(j);
      } else {
        toast(j.msg || (j.ok?'Saved':'Error'), j.ok ? 'ok' : 'err');
        if(j.ok){ bootstrap.Modal.getOrCreateInstance('#roleAssignModal').hide(); await load(); }
      }
    }catch(e){ toast('Request failed','err'); }
    q('raBusy').hidden = true;
  });

  /* ===== Per-row De-assign handler inside assignments table ===== */
  document.getElementById('assignTbl').addEventListener('click', async (e)=>{
    const btn = e.target.closest('button[data-deassign]'); if(!btn) return;
    const regId = q('ra_reg_id').value;
    const type  = btn.getAttribute('data-deassign');

    const payload = { type };
    if(type === 'division'){
      payload.user_id = Number(btn.getAttribute('data-user_id')||0);
      payload.division_id = Number(btn.getAttribute('data-division_id')||0);
      if(!payload.user_id || !payload.division_id) return toast('Bad parameters','err');
    } else if(type === 'district'){
      payload.user_id = Number(btn.getAttribute('data-user_id')||0);
      payload.district_id = Number(btn.getAttribute('data-district_id')||0);
      if(!payload.user_id || !payload.district_id) return toast('Bad parameters','err');
    } else if(type === 'cluster_admin'){
      payload.cluster_id = Number(btn.getAttribute('data-cluster_id')||0);
      if(!payload.cluster_id) return toast('Bad parameters','err');
    } else if(type === 'cluster_member'){
      payload.user_id = Number(btn.getAttribute('data-user_id')||0);
      payload.cluster_id = Number(btn.getAttribute('data-cluster_id')||0);
      if(!payload.user_id || !payload.cluster_id) return toast('Bad parameters','err');
    } else {
      return;
    }

    const ok = await confirmAction('De-assign?','This will remove the selected assignment.');
    if(!ok) return;

    try{
      const r = await fetch(`${base}/manage/${regId}/role-deassign`, {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf},
        body: JSON.stringify(payload)
      });
      if (r.status === 422) { return window.rmgmOnAjaxError(r); }
      const j = await r.json();
      toast(j.msg || (j.ok?'De-assigned':'Error'), j.ok ? 'ok' : 'err');

      // Refresh assignments panel and counters
      if(lastAssignRoleId){ await loadAssignments(Number(regId), Number(lastAssignRoleId)); }
      await load();
    }catch(err){ toast('Request failed','err'); }
  });

  /* ===== Filters and init ===== */
  let spCol=null, spVal=null;
  function ensureTomSelect(){
    if(!window.TomSelect) return;
    if(!spCol){ spCol = new TomSelect('#sp_col', {maxItems:1, create:false, closeAfterSelect:true, allowEmptyOption:true, dropdownParent:'body'}); }
    if(!spVal){ spVal = new TomSelect('#sp_val', {maxItems:1, create:false, closeAfterSelect:true, allowEmptyOption:true, dropdownParent:'body'}); }
  }
  ensureTomSelect();

  q('sp_col').addEventListener('change', async ()=>{
    const col = spCol ? spCol.getValue() : q('sp_col').value;
    if(spVal){ spVal.clear(true); spVal.clearOptions(); }
    const addVal = (v,t)=> spVal ? spVal.addOption({value:v,text:t??v}) : (q('sp_val').insertAdjacentHTML('beforeend', `<option value="${v}">${t??v}</option>`));

    if(!col){ if(spVal) spVal.refreshOptions(false); else q('sp_val').innerHTML='<option value="">Select value</option>'; return; }

    if(col==='reg_type'){
      [
        ['company_officer','Company Officer'],
        ['client','Client'],
        ['professional','Professional']
      ].forEach(([v,t])=>addVal(v,t));
      if(spVal) spVal.refreshOptions(false);
      return;
    }

    if(col==='approval_status'){ ['approved','pending','declined'].forEach(v=>addVal(v,titleCase(v))); if(spVal) spVal.refreshOptions(false); return; }

    if(col==='role'){
      try{
        const url = new URL(base + '/manage/distinct', location.origin);
        url.searchParams.set('col','role');
        const dj = await fetch(url).then(r=>r.json()).catch(()=>({ok:false,values:[]}));
        const vals = (dj.ok? dj.values: []).filter(Boolean);
        if(!vals.length){ if(!spVal) q('sp_val').innerHTML='<option value="">Select value</option>'; if(spVal) spVal.refreshOptions(false); return; }
        vals.forEach(v=>addVal(v, v));
        if(spVal) spVal.refreshOptions(false);
      }catch(e){ /* ignore */ }
      return;
    }
  });

  q('sp_search').addEventListener('click', ()=>{
    state.sp_col = spCol ? spCol.getValue() : q('sp_col').value;
    state.sp_val = spVal ? spVal.getValue() : q('sp_val').value;
    state.page = 1; load();
  });

  q('size').addEventListener('change', e=>{ state.size = parseInt(e.target.value||25,10); state.page=1; load(); });
  q('q').addEventListener('input', e=>{ state.search = e.target.value; state.page=1; load(); });
  q('sort_by').addEventListener('change', e=>{ state.sort_by = e.target.value; load(); });
  q('sort_dir').addEventListener('change', e=>{ state.sort_dir = e.target.value || 'desc'; load(); });
  q('btnExport').addEventListener('click', (e)=>{ e.preventDefault(); location.href = base + '/manage/export/csv'; });

  q('btnRefresh').addEventListener('click', ()=>{
    state={...state,search:'',sp_col:'',sp_val:'',user_id:'',page:1};
    if(spCol) spCol.clear(true); if(spVal) spVal.clear(true);
    q('q').value=''; q('user_id').value='';
    load();
  });

  q('btnPdfPrint').addEventListener('click',()=>{ const f=q('pdfFrame'); if(f && f.contentWindow) f.contentWindow.print(); });
  q('btnPdfDownload').addEventListener('click',()=>{
    try{ const f=q('pdfFrame'); const a = document.createElement('a'); a.href = f.src; a.download = 'registration.pdf'; a.click();
    }catch(e){ toast('Download not available','warn'); }
  });

  q('user_id').addEventListener('keydown', (e)=>{
    if(e.key !== 'Enter') return;
    e.preventDefault();
    const v = (e.target.value||'').trim();
    state.user_id = v;
    state.page = 1;
    load();
  });

  q('size').value = state.size; q('q').value = state.search; q('sort_by').value = state.sort_by; q('sort_dir').value = state.sort_dir;
  load();
})();
</script>
@endpush
