@extends('backend.layouts.master')

@php $onlyTable = $onlyTable ?? false; @endphp

@if(!$onlyTable)
@push('styles')
<style>
  /* ===== Global, mobile-first responsive rules (scoped to this page) ===== */

  /* Fluid readable type */
  html { font-size: clamp(14px, 1.2vw + 10px, 16px); }
  h1.h4 { font-size: clamp(18px, 1.6vw + 12px, 22px); }
  .text-muted { font-size: clamp(12px, .4vw + 10px, 14px); }

  /* Controls wrap nicely on small screens */
  #searchForm .d-flex.gap-2 { flex-wrap: wrap; }
  #searchForm .form-select, #searchForm .form-control { min-height: 38px; }

  /* Table readability */
  .table td, .table th { font-size: clamp(12px, .35vw + 10px, 14px); }
  .table td { vertical-align: middle; }
  .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }

  /* Pagination tap targets */
  .pagination .page-link { padding: .45rem .7rem; }

  /* Breakpoints (Bootstrap-like widths for familiarity) */
  @media (min-width: 576px) { /* sm */ }
  @media (min-width: 768px) { /* md */
    #searchForm .d-flex.gap-2 { flex-wrap: nowrap; }
  }
  @media (min-width: 992px) { /* lg */
    .table td, .table th { font-size: 14px; }
  }
  @media (min-width: 1200px) { /* xl */ }
  @media (min-width: 1400px) { /* xxl */ }

  /* ===== Custom modal responsiveness polish (no Bootstrap) ===== */
  @media (max-width: 575.98px) {
    .custom-modal .dialog { border-radius: 12px; width: min(92vw, 520px); }
    .custom-modal .head h5 { font-size: 15px; }
    .custom-modal .body { font-size: 14px; }
    .btn-plain { padding: 9px 14px; font-size: 14px; }
  }
  @media (min-width: 992px) {
    .custom-modal .body { font-size: 16px; }
    .btn-plain { padding: 10px 18px; }
  }

  /* Respect user prefs */
  @media (prefers-reduced-motion: reduce) {
    * { animation-duration: .001ms !important; animation-iteration-count: 1 !important; transition-duration: .001ms !important; }
  }
</style>
@endpush
@endif

@section('content')

@if(!$onlyTable)
<div class="container py-3 py-md-4">

    {{-- Timed toaster --}}
    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show" role="alert" id="toastMessage">
            <i class="fa fa-check-circle me-2"></i> {{ session('status') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert" id="toastMessage">
            <i class="fa fa-triangle-exclamation me-2"></i> {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-3 border p-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div>
                <h1 class="h4 mb-1">Clusters</h1>
                <div class="text-muted">Manage clusters (Super Admin)</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if(!empty($can['create']))
                    <a href="{{ route('superadmin.globalsetup.clusters.create') }}"
                       class="btn btn-primary">
                        <i class="fa fa-plus"></i> Add Cluster
                    </a>
                @endif
            </div>
        </div>

        {{-- Filterbar --}}
        @include('backend.layouts.partials.filterbar')

        {{-- Search & controls --}}
        <form method="GET" action="{{ route('superadmin.globalsetup.clusters.index') }}" class="mt-3" id="searchForm">
            <input type="hidden" name="division_id" id="hf_division_id" value="{{ $divisionId }}">
            <input type="hidden" name="district_id" id="hf_district_id" value="{{ $districtId }}">
            <input type="hidden" name="cluster_id"  id="hf_cluster_id"  value="{{ $clusterId }}">

            <div class="d-flex flex-column flex-md-row align-items-stretch gap-2">
                <div class="ms-md-auto flex-grow-1">
                    <input type="search" name="search" class="form-control"
                           value="{{ $search }}" placeholder="Search cluster, district, division..." />
                </div>
                <div class="d-flex gap-2">
                    <select name="sort" class="form-select">
                        <option value="id"          {{ $sort==='id' ? 'selected' : '' }}>ID</option>
                        <option value="short_code"  {{ $sort==='short_code' ? 'selected' : '' }}>Short Code</option>
                        <option value="cluster_name"{{ $sort==='cluster_name' ? 'selected' : '' }}>Name</option>
                        <option value="status"      {{ $sort==='status' ? 'selected' : '' }}>Status</option>
                        <option value="created_at"  {{ $sort==='created_at' ? 'selected' : '' }}>Created</option>
                    </select>
                    <select name="dir" class="form-select">
                        <option value="asc"  {{ $dir==='asc' ? 'selected' : '' }}>Asc</option>
                        <option value="desc" {{ $dir==='desc' ? 'selected' : '' }}>Desc</option>
                    </select>
                </div>
                <div>
                    <select name="limit" class="form-select">
                        @foreach ([10,25,50,100] as $opt)
                            <option value="{{ $opt }}" {{ (int)$limit === $opt ? 'selected' : '' }}>{{ $opt }}/page</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary"><i class="fa fa-search"></i> Go</button>
                    @if($search !== '')
                        <a href="{{ route('superadmin.globalsetup.clusters.index') }}" class="btn btn-outline-secondary" id="btnReset">Reset</a>
                    @endif
                </div>
            </div>
        </form>

        {{-- Table wrapper (AJAX target) --}}
        <div id="clusterTableWrap" class="mt-3">
@endif

            {{-- === Table + Pagination (also returned for AJAX) === --}}
            <div class="border rounded overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="th-vibrant">
                            <tr>
                                <th class="text-center" style="width:70px;">ID</th>
                                <th>Short Code</th>
                                <th>Name</th>
                                <th>Division</th>
                                <th>District</th>
                                <th>Supervisor</th>
                                <th>Upazilas</th>
                                <th class="text-center">Status</th>
                                <th>Created</th>
                                <th class="text-center" style="width:260px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr id="row-{{ $row->id }}">
                                    <td class="text-center">#{{ $row->id }}</td>
                                    <td>{{ $row->short_code }}</td>
                                    <td>{{ $row->cluster_name }}</td>
                                    <td>{{ $row->division_code }} – {{ $row->division_name }}</td>
                                    <td>{{ $row->district_code }} – {{ $row->district_name }}</td>
                                    <td>{{ $row->supervisor_name }}</td>
                                    <td style="max-width:360px; word-break:break-word;">{{ $row->upazila_names ?: '—' }}</td>
                                    <td class="text-center">
                                        @if((int)$row->status === 1)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ \Illuminate\Support\Carbon::parse($row->created_at)->format('Y-m-d H:i') }}</td>
                                    <td class="text-center">
                                        <div class="d-flex gap-2 flex-wrap justify-content-center">
                                            @if(!empty($can['view']))
                                                <a href="{{ route('superadmin.globalsetup.clusters.show', $row->id) }}" class="btn btn-sm btn-info">
                                                    <i class="fa fa-eye"></i> View
                                                </a>
                                            @endif
                                            @if(!empty($can['edit']))
                                                <a href="{{ route('superadmin.globalsetup.clusters.edit', $row->id) }}" class="btn btn-sm btn-warning">
                                                    <i class="fa fa-pen"></i> Edit
                                                </a>
                                            @endif
                                            @if(!empty($can['delete']))
                                                <button type="button" class="btn btn-sm btn-danger btn-delete"
                                                        data-id="{{ $row->id }}"
                                                        data-name="{{ $row->cluster_name }}"
                                                        data-url="{{ route('superadmin.globalsetup.clusters.destroy', $row->id) }}">
                                                    <i class="fa fa-trash"></i> Delete
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="text-center text-muted py-4">No records found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <nav class="mt-3">
                <ul class="pagination justify-content-center">
                    <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $page <= 1 ? '#' : route('superadmin.globalsetup.clusters.index', array_merge(request()->except('page'), ['page' => $page-1])) }}">Previous</a>
                    </li>
                    @for($i = $winStart; $i <= $winEnd; $i++)
                        <li class="page-item {{ $i === $page ? 'active' : '' }}">
                            <a class="page-link" href="{{ route('superadmin.globalsetup.clusters.index', array_merge(request()->except('page'), ['page' => $i])) }}">{{ $i }}</a>
                        </li>
                    @endfor
                    <li class="page-item {{ $page >= $totalPages ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ $page >= $totalPages ? '#' : route('superadmin.globalsetup.clusters.index', array_merge(request()->except('page'), ['page' => $page+1])) }}">Next</a>
                    </li>
                </ul>
                <div class="text-center text-muted mt-1">
                    Showing page <strong>{{ $page }}</strong> of <strong>{{ $totalPages }}</strong>,
                    total <strong>{{ number_format($total) }}</strong> record(s).
                </div>
            </nav>
            {{-- === /Table + Pagination === --}}

@if(!$onlyTable)
        </div> {{-- #clusterTableWrap --}}
    </div>
</div>

{{-- ======= Minimal, modern, vanilla-CSS Delete Modal (NO Bootstrap) ======= --}}
<style>
  :root{
    --modal-bg:#ffffff;
    --modal-overlay:rgba(17,24,39,.60);
    --modal-danger:#e11d48;
    --modal-text:#0f172a;
    --modal-muted:#64748b;
    --modal-warn:#b45309;
  }
  .custom-modal{position:fixed; inset:0; display:none; align-items:center; justify-content:center; z-index:1050; backdrop-filter:saturate(160%) blur(2px);}
  .custom-modal.is-open{display:flex;}
  .custom-modal .overlay{position:absolute; inset:0; background:var(--modal-overlay);}
  .custom-modal .dialog{
    position:relative; width:min(640px,92vw); background:var(--modal-bg);
    border-radius:14px; box-shadow:0 20px 40px rgba(0,0,0,.20); overflow:hidden;
    transform:translateY(10px) scale(.98); opacity:0; transition:transform .18s ease, opacity .18s ease;
  }
  .custom-modal.is-open .dialog{transform:translateY(0) scale(1); opacity:1;}
  .custom-modal .head{
    display:flex; align-items:center; gap:.6rem; padding:14px 16px;
    background:linear-gradient(180deg,#ef4444,#dc2626); color:#fff; font-weight:600;
  }
  .custom-modal .head h5{margin:0; font-size:16px;}
  .custom-modal .head .close{
    margin-left:auto; border:none; background:transparent; color:#fff; cursor:pointer;
    font-size:20px; opacity:.85; line-height:1;
  }
  .custom-modal .head .close:hover{opacity:1;}
  .custom-modal .body{padding:18px 16px; color:var(--modal-text); font-size:15px; line-height:1.5;}
  .custom-modal .body .warn{
    margin-top:10px; font-size:13px; color:var(--modal-warn);
    background:#fffbeb; border:1px solid #fde68a; padding:8px 10px; border-radius:10px;
  }
  .custom-modal .foot{display:flex; justify-content:flex-end; gap:10px; padding:14px 16px; background:#f8fafc;}
  .btn-plain{
    appearance:none; border:1px solid #cbd5e1; background:#fff; color:#0f172a;
    padding:10px 16px; border-radius:10px; font-weight:600; cursor:pointer;
    transition:transform .06s ease, box-shadow .2s ease, background .2s ease;
  }
  .btn-plain:hover{box-shadow:0 4px 16px rgba(2,6,23,.08);}
  .btn-plain:active{transform:translateY(1px);}
  .btn-danger{border:none; background:var(--modal-danger); color:#fff;}
  .btn-danger:hover{box-shadow:0 6px 20px rgba(225,29,72,.35);}
  .btn-danger i{margin-right:.4rem;}
</style>

<div id="deleteModal" class="custom-modal" aria-hidden="true">
  <div class="overlay" data-close="true"></div>
  <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="delTitle">
    <div class="head">
      <i class="fa fa-triangle-exclamation"></i>
      <h5 id="delTitle">Confirm Delete</h5>
      <button class="close" type="button" aria-label="Close" data-close="true">&times;</button>
    </div>
    <div class="body">
      <div>Are you sure you want to permanently delete <strong id="deleteName">this record</strong>?</div>
      <div class="warn">This is a <b>hard delete</b>. The record cannot be restored later.</div>
    </div>
    <div class="foot">
      <button type="button" class="btn-plain" data-close="true">Cancel</button>
      <button type="button" class="btn-plain btn-danger" id="confirmDeleteBtn">
        <i class="fa fa-trash"></i> Delete
      </button>
    </div>
  </div>
</div>
{{-- ======= /Modal ======= --}}
@endif
@endsection

@push('scripts')
@if(!$onlyTable)
<script>
$.ajaxSetup({
  headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
});

(function () {
  const $wrap = $('#clusterTableWrap');
  const $form = $('#searchForm');
  const $div  = document.getElementById('division_id');
  const $dis  = document.getElementById('district_id');
  const $clu  = document.getElementById('cluster_id');

  // Toaster auto-dismiss
  setTimeout(() => {
    if (window.bootstrap) {
      const toast = document.getElementById('toastMessage');
      if (toast) new bootstrap.Alert(toast).close();
    } else {
      const el = document.getElementById('toastMessage');
      if (el) el.remove();
    }
  }, 3000);

  // ---------- AJAX table loader ----------
  function buildUrlFromState(extra = {}) {
    const url = new URL("{{ route('superadmin.globalsetup.clusters.index') }}", window.location.origin);
    const formData = new FormData($form[0]);
    for (const [k,v] of formData.entries()) {
      if (v !== '') url.searchParams.set(k, v);
    }
    if ($div?.value) url.searchParams.set('division_id', $div.value);
    if ($dis?.value) url.searchParams.set('district_id', $dis.value);
    if ($clu?.value) url.searchParams.set('cluster_id',  $clu.value);
    for (const k in extra) {
      if (extra[k] === null) url.searchParams.delete(k);
      else url.searchParams.set(k, extra[k]);
    }
    return url;
  }

  let inflight = null;
  function loadTable(url, push = true) {
    if (inflight) inflight.abort?.();
    const ctrl = new AbortController();
    inflight = ctrl;

    $wrap.addClass('opacity-50');
    return fetch(url.toString(), { signal: ctrl.signal, headers: {'X-Requested-With':'XMLHttpRequest'} })
      .then(res => res.text())
      .then(html => {
        $wrap.html(html);
        $wrap.removeClass('opacity-50');
        if (push) window.history.pushState({ ajax: true }, '', url.toString());
        wireTableEvents();
      })
      .catch(() => { $wrap.removeClass('opacity-50'); })
      .finally(() => { inflight = null; });
  }

  function wireTableEvents() {
    $wrap.find('.pagination a.page-link').on('click', function (e) {
      const href = $(this).attr('href');
      if (!href || href === '#') return;
      e.preventDefault();
      loadTable(new URL(href, window.location.origin), true);
    });
  }
  wireTableEvents();

  $form.on('submit', function (e) {
    e.preventDefault();
    const url = buildUrlFromState({ page: null });
    loadTable(url, true);
  });

  $form.find('select[name="sort"], select[name="dir"], select[name="limit"]').on('change', function () {
    const url = buildUrlFromState({ page: null });
    loadTable(url, true);
  });

  $('#btnReset').on('click', function (e) {
    e.preventDefault();
    const base = new URL("{{ route('superadmin.globalsetup.clusters.index') }}", window.location.origin);
    loadTable(base, true);
  });

  function mirrorHidden() {
    $('#hf_division_id').val($div?.value || '');
    $('#hf_district_id').val($dis?.value || '');
    $('#hf_cluster_id').val($clu?.value || '');
  }
  function reloadForFilter() {
    mirrorHidden();
    const url = buildUrlFromState({ page: null });
    loadTable(url, true);
  }
  $div?.addEventListener('change', reloadForFilter);
  $dis?.addEventListener('change', reloadForFilter);
  $clu?.addEventListener('change', reloadForFilter);

  window.addEventListener('popstate', function () {
    const url = new URL(window.location.href);
    loadTable(url, false);
  });

  // ---------- Custom Modal (no Bootstrap) ----------
  const $deleteModal = document.getElementById('deleteModal');
  const $deleteName  = document.getElementById('deleteName');
  let deleteTarget = { id: null, url: null };

  function openDeleteModal() {
    $deleteModal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }
  function closeDeleteModal() {
    $deleteModal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  // open modal on delete buttons
  $(document).on('click', '.btn-delete', function () {
    deleteTarget.id  = $(this).data('id');
    deleteTarget.url = $(this).data('url');
    $deleteName.textContent = $(this).data('name');
    openDeleteModal();
  });

  // close modal (X, overlay, Cancel)
  $deleteModal.addEventListener('click', function(e){
    if (e.target.dataset.close === 'true') closeDeleteModal();
  });
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') closeDeleteModal();
  });

  // confirm delete
  document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
    $.ajax({
      url: deleteTarget.url,
      type: 'POST',
      data: { _method: 'DELETE' },
      dataType: 'json',
      success: function () {
        closeDeleteModal();
        const url = new URL(window.location.href);
        loadTable(url, false);
      },
      error: function () {
        alert('Error while deleting.');
        closeDeleteModal();
      }
    });
  });
})();
</script>
@endif
@endpush
