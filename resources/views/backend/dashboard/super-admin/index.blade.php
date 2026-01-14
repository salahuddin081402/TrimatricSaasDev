@extends('backend.layouts.master')

@section('content')
<div class="container py-4">
    <h1 class="h4 mb-3">Super Admin Dashboard</h1>
    <p class="text-muted mb-4">Role type: {{ $roleType ?? 'N/A' }}</p>

    <div class="row g-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="mb-1">Tenants</h6>
                    <div class="display-6">12</div>
                </div>
            </div>
        </div>
        {{-- Add more cards/widgets as needed --}}
    </div>
</div>
@endsection
