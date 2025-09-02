{{-- resources/views/backend/modules/global-setup/countries/index.blade.php --}}
@extends('backend.layouts.master')

@push('styles')
    <style>
        body { background: #f6f8fa; color: #12203a; }
        .page-card { background:#fff; border-radius:14px; box-shadow:0 6px 28px rgba(0,0,0,.06); padding:1rem 1rem 1.25rem; border:1px solid #e6ecf5; }
        .page-title { font-size:1.35rem; font-weight:700; margin:0; letter-spacing:.2px; }
        .subtle { font-size:.9rem; color:#5a6a85; }
        .table-wrap { margin-top:.8rem; border:1px solid #e6ecf5; border-radius:12px; overflow:hidden; }
        .table-responsive { max-height:62vh; overflow:auto; }
        .table-striped tbody tr:nth-of-type(odd){ background-color:#f7fbff !important; }
        .table-hover tbody tr:hover{ background-color:#eaf2ff !important; }
        .table td, .table th { vertical-align:middle; }
        .pagination .page-link{ border-radius:.5rem; }
        .pagination .page-item.active .page-link{ background-color:#2e6dda; border-color:#2e6dda; }
        .toast-container{ position:fixed; top:1rem; right:1rem; z-index:1060; }
    </style>
@endpush

@section('content')
<div class="container py-3 py-md-4">
    {{-- Toasts --}}
    <div class="toast-container">
        @if(session('status'))
            <div class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="2500">
                <div class="d-flex">
                    <div class="toast-body"><i class="fa fa-check-circle me-1"></i> {{ session('status') }}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        @endif
        @if(session('error'))
            <div class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
                <div class="d-flex">
                    <div class="toast-body"><i class="fa fa-triangle-exclamation me-1"></i> {{ session('error') }}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        @endif
    </div>

    <div class="page-card">
        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h1 class="page-title">Countries</h1>
                <div class="subtle">Manage country records (Super Admin)</div>
            </div>

            <div class="d-flex gap-2 align-items-center">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fa fa-ellipsis"></i> More
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><button class="dropdown-item" type="button" disabled>Export CSV (coming)</button></li>
                        <li><button class="dropdown-item" type="button" disabled>Column visibility (coming)</button></li>
                        <li><hr class="dropdown-divider"/></li>
                        <li><button class="dropdown-item text-danger" type="button" disabled>Bulk Delete (coming)</button></li>
                    </ul>
                </div>

                @if(!empty($can['create']))
                    <a href="{{ route('superadmin.globalsetup.countries.create') }}" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Add Country
                    </a>
                @endif
            </div>
        </div>

        {{-- Controls (Bootstrap-only layout) --}}
        <form method="GET" action="{{ route('superadmin.globalsetup.countries.index') }}" class="mt-3">
            @php $s = $sort ?? 'id'; $d = $dir ?? 'desc'; @endphp
            <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2">

                {{-- Search (full width on mobile) --}}
                <div class="flex-grow-1">
                    <input type="search" name="search" class="form-control"
                           value="{{ $search }}" placeholder="Search country name or short code..." />
                </div>

                {{-- Sort & direction --}}
                <div class="d-flex gap-2">
                    <select name="sort" class="form-select" onchange="this.form.submit()">
                        <option value="id"         {{ $s==='id' ? 'selected' : '' }}>ID</option>
                        <option value="name"       {{ $s==='name' ? 'selected' : '' }}>Name</option>
                        <option value="short_code" {{ $s==='short_code' ? 'selected' : '' }}>Short Code</option>
                        <option value="created_at" {{ $s==='created_at' ? 'selected' : '' }}>Created</option>
                    </select>

                    <select name="dir" class="form-select" onchange="this.form.submit()">
                        <option value="asc"  {{ $d==='asc'  ? 'selected' : '' }}>Asc</option>
                        <option value="desc" {{ $d==='desc' ? 'selected' : '' }}>Desc</option>
                    </select>
                </div>

                {{-- Limit --}}
                <div>
                    <select name="limit" class="form-select" onchange="this.form.submit()">
                        @foreach ([10,25,50,100] as $opt)
                            <option value="{{ $opt }}" {{ (int)$limit === $opt ? 'selected' : '' }}>{{ $opt }} / page</option>
                        @endforeach
                    </select>
                </div>

                {{-- Buttons --}}
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fa fa-search"></i> Go
                    </button>
                    @if($search !== '')
                        <a href="{{ route('superadmin.globalsetup.countries.index', ['limit' => $limit, 'sort' => $s, 'dir' => $d]) }}"
                           class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </div>
        </form>

        {{-- Table --}}
        <div class="table-wrap">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center">ID</th>
                            <th>Name</th>
                            <th class="text-center">Short Code</th>
                            <th>Created</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($rows as $row)
                        <tr id="row-{{ $row->id }}">
                            <td data-label="ID" class="text-center">#{{ $row->id }}</td>
                            <td data-label="Name">{{ $row->name }}</td>
                            <td data-label="Short Code" class="text-center">{{ $row->short_code ?? '—' }}</td>
                            <td data-label="Created">{{ \Illuminate\Support\Carbon::parse($row->created_at)->format('Y-m-d H:i') }}</td>
                            <td class="row-actions text-center" data-label="Actions">
                                <div class="d-flex gap-2 flex-wrap justify-content-center">
                                    @if(!empty($can['view']))
                                        <a href="{{ route('superadmin.globalsetup.countries.show', $row->id) }}" class="btn btn-sm btn-info">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                    @endif
                                    @if(!empty($can['edit']))
                                        <a href="{{ route('superadmin.globalsetup.countries.edit', $row->id) }}" class="btn btn-sm btn-warning">
                                            <i class="fa fa-pen"></i> Edit
                                        </a>
                                    @endif
                                    @if(!empty($can['delete']))
                                        <button type="button" class="btn btn-sm btn-danger btn-delete"
                                                data-id="{{ $row->id }}"
                                                data-name="{{ $row->name }}"
                                                data-url="{{ route('superadmin.globalsetup.countries.destroy', $row->id) }}">
                                            <i class="fa fa-trash"></i> Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No records found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        @php
          $q = [
            'search' => $search,
            'limit'  => $limit,
            'sort'   => $sort ?? 'id',
            'dir'    => $dir  ?? 'desc'
          ];
        @endphp
        <nav class="mt-3">
            <ul class="pagination justify-content-center">
                <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ $page <= 1 ? '#' : route('superadmin.globalsetup.countries.index', array_merge($q, ['page' => $page-1])) }}">Previous</a>
                </li>

                @if($winStart > 1)
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                @endif

                @for($i = $winStart; $i <= $winEnd; $i++)
                    <li class="page-item {{ $i === $page ? 'active' : '' }}">
                        <a class="page-link" href="{{ route('superadmin.globalsetup.countries.index', array_merge($q, ['page' => $i])) }}">{{ $i }}</a>
                    </li>
                @endfor

                @if($winEnd < $totalPages)
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                @endif

                <li class="page-item {{ $page >= $totalPages ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ $page >= $totalPages ? '#' : route('superadmin.globalsetup.countries.index', array_merge($q, ['page' => $page+1])) }}">Next</a>
                </li>
            </ul>

            <div class="text-center subtle mt-1">
                Showing page <strong>{{ $page }}</strong> of <strong>{{ $totalPages }}</strong>,
                total <strong>{{ number_format($total) }}</strong> record(s).
            </div>
        </nav>
    </div>
</div>

{{-- Delete Confirm Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteModalLabel"><i class="fa fa-triangle-exclamation me-1"></i> Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete <strong id="deleteName">this record</strong>?
        <div class="small text-muted mt-1">This is a soft delete; you can restore later.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
          <i class="fa fa-trash"></i> Delete
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
$.ajaxSetup({
  headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
});

let deleteTarget = { id: null, url: null };

$(function () {
  const $deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
  const $deleteName  = $('#deleteName');

  $(document).on('click', '.btn-delete', function () {
    deleteTarget.id  = $(this).data('id');
    deleteTarget.url = $(this).data('url');
    const name       = $(this).data('name') || ('#' + deleteTarget.id);
    $deleteName.text(name);
    $deleteModal.show();
  });

  $('#confirmDeleteBtn').on('click', function () {
    if (!deleteTarget.url) return;

    $.ajax({
      url: deleteTarget.url,
      type: 'POST',
      data: { _method: 'DELETE' },
      dataType: 'json',
      success: function (res) {
        if (res && res.ok) {
          $('#row-' + deleteTarget.id).fadeOut(200, function(){ $(this).remove(); });
        } else {
          alert(res?.message || 'Delete failed. Please try again.');
        }
        deleteTarget = { id: null, url: null };
        $deleteModal.hide();
      },
      error: function (xhr) {
        if (xhr.status === 403)      alert('Forbidden: you do not have permission.');
        else if (xhr.status === 404) alert('Record not found or already deleted.');
        else                         alert('Server error while deleting.');
        deleteTarget = { id: null, url: null };
        $deleteModal.hide();
      }
    });
  });
});
</script>
@endpush
