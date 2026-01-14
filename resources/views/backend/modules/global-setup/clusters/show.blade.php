@extends('backend.layouts.master')

@push('styles')
<style>
  /* ========= Mobile-first responsive rules (scoped to this page) ========= */
  html { font-size: clamp(14px, 1.1vw + 10px, 16px); }

  h1.h4 { font-size: clamp(18px, 1.6vw + 12px, 22px); }
  .text-muted { font-size: clamp(12px, .4vw + 10px, 14px); }

  /* Card look & spacing */
  .container .bg-white.rounded-3.border { border-radius: 14px; }
  .container .bg-white.rounded-3.border .p-3 { padding: 1rem !important; }

  /* Info blocks */
  .mb-2 { margin-bottom: 0.6rem !important; }
  .mb-2 strong { color: #0f172a; font-weight: 700; }

  /* Status badges – keep Bootstrap classes but tune size */
  .badge { font-size: .85rem; padding: .4em .6em; border-radius: .5rem; }

  /* Table density & readability */
  .table { font-size: .95rem; }
  .table th, .table td { vertical-align: middle; }
  .table thead th { white-space: nowrap; }
  .table-responsive { border-radius: 10px; overflow: hidden; }

  /* Headings */
  h5 { font-size: clamp(16px, 1vw + 12px, 18px); margin-bottom: .5rem; }

  /* Breakpoints */
  @media (min-width: 576px) { /* sm */
    .container .bg-white.rounded-3.border { padding: 1.1rem; }
  }
  @media (min-width: 768px) { /* md */
    .container .bg-white.rounded-3.border { padding: 1.25rem; }
    .row.g-3 { row-gap: 1rem; }
    .table { font-size: 1rem; }
  }
  @media (min-width: 992px) { /* lg */
    .container .bg-white.rounded-3.border { padding: 1.35rem; }
    .mb-2 { margin-bottom: .7rem !important; }
    .table th, .table td { padding-top: .75rem; padding-bottom: .75rem; }
  }

  /* Respect reduced motion */
  @media (prefers-reduced-motion: reduce) {
    * { animation-duration: .001ms !important; animation-iteration-count: 1 !important; transition-duration: .001ms !important; }
  }
</style>
@endpush

@section('content')
<div class="container py-3 py-md-4">
    <div class="bg-white rounded-3 border p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h1 class="h4 mb-1">View Cluster</h1>
                <div class="text-muted">Details of the selected cluster.</div>
            </div>
            <a href="{{ route('superadmin.globalsetup.clusters.index') }}" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Back
            </a>
        </div>

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <div class="mb-2"><strong>Short Code:</strong> {{ $row->short_code }}</div>
                <div class="mb-2"><strong>Cluster Name:</strong> {{ $row->cluster_name }}</div>
                <div class="mb-2"><strong>Status:</strong>
                    @if((int)$row->status===1)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-secondary">Inactive</span>
                    @endif
                </div>
                <div class="mb-2"><strong>Supervisor:</strong> {{ $supervisorName }}</div>
            </div>
            <div class="col-12 col-md-6">
                <div class="mb-2"><strong>Division:</strong> {{ $row->division_code }} – {{ $row->division_name }}</div>
                <div class="mb-2"><strong>District:</strong> {{ $row->district_code }} – {{ $row->district_name }}</div>
                <div class="mb-2"><strong>Created:</strong> {{ \Illuminate\Support\Carbon::parse($row->created_at)->format('Y-m-d H:i') }}</div>
                <div class="mb-2"><strong>Updated:</strong> {{ \Illuminate\Support\Carbon::parse($row->updated_at)->format('Y-m-d H:i') }}</div>
            </div>
        </div>

        <hr>

        <h5 class="mt-2">Upazilas under this Cluster</h5>
        <div class="table-responsive border rounded">
            <table class="table table-bordered mb-0">
                <thead class="th-vibrant">
                    <tr>
                        <th style="width:120px;">Short Code</th>
                        <th>Name</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($upazilas as $u)
                    <tr>
                        <td>{{ $u->short_code }}</td>
                        <td>{{ $u->name }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-center text-muted">No upazila mapped.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>
@endsection
