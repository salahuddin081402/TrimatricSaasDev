{{-- resources/views/backend/modules/global-setup/countries/show.blade.php --}}
@extends('backend.layouts.master')

@push('styles')
    <style>
        body { background: #f6f8fa; }
        .page-card { background:#fff; border-radius:14px; box-shadow:0 6px 28px rgba(0,0,0,.06); padding:1rem 1rem 1.25rem; border:1px solid #e6ecf5; }
        .page-title { font-size:1.35rem; font-weight:700; margin:0; letter-spacing:.2px; }
    </style>
@endpush

@section('content')
<div class="container py-3 py-md-4">
    <div class="page-card">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h1 class="page-title mb-0">View Country <small class="text-muted">#{{ $country->id }}</small></h1>
            <a href="{{ route('superadmin.globalsetup.countries.index') }}" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Back to List
            </a>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="border rounded p-3 bg-light">
                    <div class="mb-2"><strong>Name:</strong> {{ $country->name ?? '—' }}</div>
                    <div class="mb-2"><strong>Short Code:</strong> {{ $country->short_code ?? '—' }}</div>
                    <div class="mb-2"><strong>Created At:</strong> {{ $country->created_at ? $country->created_at->format('Y-m-d H:i') : '—' }}</div>
                    <div class="mb-2"><strong>Updated At:</strong> {{ $country->updated_at ? $country->updated_at->format('Y-m-d H:i') : '—' }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
