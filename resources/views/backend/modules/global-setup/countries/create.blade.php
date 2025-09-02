{{-- resources/views/backend/modules/global-setup/countries/create.blade.php --}}
@extends('backend.layouts.master')

@push('styles')
    <style>
        body { background: #f6f8fa; }
        .page-card { background:#fff; border-radius:14px; box-shadow:0 6px 28px rgba(0,0,0,.06); padding:1rem 1rem 1.25rem; border:1px solid #e6ecf5; }
        .page-title { font-size:1.35rem; font-weight:700; margin:0; letter-spacing:.2px; }
        .alert ul { margin-bottom: 0; }
    </style>
@endpush

@section('content')
<div class="container py-3 py-md-4">
    <div class="page-card">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <h1 class="page-title mb-0">Add Country</h1>
            <a href="{{ route('superadmin.globalsetup.countries.index') }}" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Back to List
            </a>
        </div>

        {{-- Top-level validation summary --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Please fix the following:</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('superadmin.globalsetup.countries.store') }}" class="row g-3">
            @csrf

            <div class="col-md-6">
                <label class="form-label">Country Name <span class="text-danger">*</span></label>
                <input name="name" type="text" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name') }}" maxlength="150" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-3">
                <label class="form-label">Short Code</label>
                <input name="short_code" type="text" class="form-control @error('short_code') is-invalid @enderror"
                       value="{{ old('short_code') }}" maxlength="10" placeholder="e.g., BD">
                @error('short_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 mt-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> Save
                </button>
                <a href="{{ route('superadmin.globalsetup.countries.index') }}" class="btn btn-secondary">
                    Return
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

