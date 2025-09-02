@extends('backend.layouts.master')

@section('content')
<div class="container py-3 py-md-4">

    {{-- Flash toasts (keep simple; master may also auto-show toasts) --}}
    @if(session('status'))
        <div class="alert alert-success d-flex align-items-center" role="alert">
            <i class="fa fa-check-circle me-2"></i> {{ session('status') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="fa fa-triangle-exclamation me-2"></i> {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-3 border p-3">
        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <div>
                <h1 class="h4 mb-1">Companies</h1>
                <div class="text-muted">Manage companies (Super Admin)</div>
            </div>

            <div class="d-flex align-items-center gap-2">
                @if(!empty($can['create']))
                    <a href="{{ route('superadmin.globalsetup.companies.create') }}" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Add Company
                    </a>
                @endif
            </div>
        </div>

        {{-- Controls --}}
        @php $s = $sort ?? 'id'; $d = $dir ?? 'desc'; @endphp
        <form method="GET" action="{{ route('superadmin.globalsetup.companies.index') }}" class="mt-3">
            <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2">
                <div class="ms-md-auto flex-grow-1">
                    <input type="search" name="search" class="form-control"
                           value="{{ $search }}" placeholder="Search company or country..." />
                </div>

                <div class="d-flex gap-2">
                    <select name="sort" class="form-select" onchange="this.form.submit()">
                        <option value="id"         {{ $s==='id' ? 'selected' : '' }}>ID</option>
                        <option value="name"       {{ $s==='name' ? 'selected' : '' }}>Name</option>
                        <option value="status"     {{ $s==='status' ? 'selected' : '' }}>Status</option>
                        <option value="created_at" {{ $s==='created_at' ? 'selected' : '' }}>Created</option>
                    </select>
                    <select name="dir" class="form-select" onchange="this.form.submit()">
                        <option value="asc"  {{ $d==='asc'  ? 'selected' : '' }}>Asc</option>
                        <option value="desc" {{ $d==='desc' ? 'selected' : '' }}>Desc</option>
                    </select>
                </div>

                <div>
                    <select name="limit" class="form-select" onchange="this.form.submit()">
                        @foreach ([10,25,50,100] as $opt)
                            <option value="{{ $opt }}" {{ (int)$limit === $opt ? 'selected' : '' }}>{{ $opt }} / page</option>
                        @endforeach
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary"><i class="fa fa-search"></i> Go</button>
                    @if($search !== '')
                        <a href="{{ route('superadmin.globalsetup.companies.index', ['limit' => $limit, 'sort' => $s, 'dir' => $d]) }}"
                           class="btn btn-outline-secondary">Reset</a>
                    @endif
                </div>
            </div>
        </form>

        {{-- Table --}}
        <div class="mt-3 border rounded overflow-hidden">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width:70px;">ID</th>
                            <th>Logo</th>
                            <th>Name</th>
                            <th>Country</th>
                            <th class="text-center">Status</th>
                            <th>Created</th>
                            <th class="text-center" style="width:240px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($rows as $row)
                        <tr id="row-{{ $row->id }}">
                            <td class="text-center">#{{ $row->id }}</td>
                            <td>
                                @if(!empty($row->logo))
                                    <img src="{{ asset($row->logo) }}" alt="Logo" class="img-thumbnail" style="max-height:48px">
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->country_name }}</td>
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
                                        <a href="{{ route('superadmin.globalsetup.companies.show', $row->id) }}" class="btn btn-sm btn-info">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                    @endif
                                    @if(!empty($can['edit']))
                                        <a href="{{ route('superadmin.globalsetup.companies.edit', $row->id) }}" class="btn btn-sm btn-warning">
                                            <i class="fa fa-pen"></i> Edit
                                        </a>
                                    @endif
                                    @if(!empty($can['delete']))
                                        <button type="button" class="btn btn-sm btn-danger btn-delete"
                                                data-id="{{ $row->id }}"
                                                data-name="{{ $row->name }}"
                                                data-url="{{ route('superadmin.globalsetup.companies.destroy', $row->id) }}">
                                            <i class="fa fa-trash"></i> Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No records found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        @php
          $q = ['search'=>$search,'limit'=>$limit,'sort'=>$sort ?? 'id','dir'=>$dir ?? 'desc'];
        @endphp
        <nav class="mt-3">
            <ul class="pagination justify-content-center">
                <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ $page <= 1 ? '#' : route('superadmin.globalsetup.companies.index', array_merge($q, ['page' => $page-1])) }}">Previous</a>
                </li>

                @if($winStart > 1)
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                @endif

                @for($i = $winStart; $i <= $winEnd; $i++)
                    <li class="page-item {{ $i === $page ? 'active' : '' }}">
                        <a class="page-link" href="{{ route('superadmin.globalsetup.companies.index', array_merge($q, ['page' => $i])) }}">{{ $i }}</a>
                    </li>
                @endfor

                @if($winEnd < $totalPages)
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                @endif

                <li class="page-item {{ $page >= $totalPages ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ $page >= $totalPages ? '#' : route('superadmin.globalsetup.companies.index', array_merge($q, ['page' => $page+1])) }}">Next</a>
                </li>
            </ul>

            <div class="text-center text-muted mt-1">
                Showing page <strong>{{ $page }}</strong> of <strong>{{ $totalPages }}</strong>,
                total <strong>{{ number_format($total) }}</strong> record(s).
            </div>
        </nav>
    </div>
</div>

{{-- Delete modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
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
  </div></div>
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
