@extends('backend.layouts.master')

@section('content')
<div class="container py-3 py-md-4">
    <div class="bg-white rounded-3 border p-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
            <h1 class="h4 mb-0">View Company <small class="text-muted">#{{ $company->id }}</small></h1>
            <a href="{{ route('superadmin.globalsetup.companies.index') }}" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Back to List
            </a>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="border rounded p-3 bg-light">
                    <div class="mb-2"><strong>Name:</strong> {{ $company->name }}</div>
                    <div class="mb-2"><strong>Country:</strong> {{ $country }}</div>
                    <div class="mb-2">
                        <strong>Status:</strong>
                        @if((int)$company->status === 1)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </div>
                    <div class="mb-2"><strong>Contact No:</strong> {{ $company->contact_no ?? '—' }}</div>
                    <div class="mb-2"><strong>Address:</strong> {{ $company->address ?? '—' }}</div>
                    <div class="mb-2"><strong>Description:</strong> {{ $company->description ?? '—' }}</div>
                    <div class="mb-2"><strong>Created:</strong> {{ $company->created_at?->format('Y-m-d H:i') ?? '—' }}</div>
                    <div class="mb-2"><strong>Updated:</strong> {{ $company->updated_at?->format('Y-m-d H:i') ?? '—' }}</div>
                </div>
            </div>
            <div class="col-md-6">
                @if($company->logo)
                    <div class="border rounded p-3 h-100 d-flex align-items-center justify-content-center">
                        <img src="{{ asset($company->logo) }}" alt="Logo" class="img-fluid" style="max-height:160px">
                    </div>
                @else
                    <div class="border rounded p-3 h-100 d-flex align-items-center justify-content-center text-muted">No logo</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
