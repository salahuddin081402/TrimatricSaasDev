@extends('backend.layouts.master')

@section('content')
<div class="container py-3 py-md-4">
    <div class="bg-white rounded-3 border p-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
            <h1 class="h4 mb-0">Edit Company <small class="text-muted">#{{ $company->id }}</small></h1>
            <a href="{{ route('superadmin.globalsetup.companies.index') }}" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Back to List
            </a>
        </div>

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

        <form method="POST" action="{{ route('superadmin.globalsetup.companies.update', $company->id) }}" class="row g-3" enctype="multipart/form-data">
            @csrf @method('PUT')

            <div class="col-md-4">
                <label class="form-label">Country <span class="text-danger">*</span></label>
                <select name="country_id" class="form-select @error('country_id') is-invalid @enderror" required>
                    <option value="">-- Select --</option>
                    @foreach($countries as $c)
                        <option value="{{ $c->id }}" {{ old('country_id', $company->country_id)==$c->id?'selected':'' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
                @error('country_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Company Name <span class="text-danger">*</span></label>
                <input name="name" type="text" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $company->name) }}" maxlength="190" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-2">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                    <option value="1" {{ old('status', (string)$company->status)=='1'?'selected':'' }}>Active</option>
                    <option value="0" {{ old('status', (string)$company->status)=='0'?'selected':'' }}>Inactive</option>
                </select>
                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Logo</label>
                <input name="logo" type="file" class="form-control @error('logo') is-invalid @enderror" accept=".jpg,.jpeg,.png,.webp">
                <div class="form-text">JPG/PNG/WEBP, max 2MB.</div>
                @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror

                @if($company->logo)
                    <div class="mt-2">
                        <img src="{{ asset($company->logo) }}" alt="Logo" class="img-thumbnail" style="max-height:80px">
                    </div>
                @endif
            </div>

            <div class="col-md-6">
                <label class="form-label">Contact No</label>
                <input name="contact_no" type="text" class="form-control @error('contact_no') is-invalid @enderror"
                       value="{{ old('contact_no', $company->contact_no) }}" maxlength="50">
                @error('contact_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Address</label>
                <input name="address" type="text" class="form-control @error('address') is-invalid @enderror"
                       value="{{ old('address', $company->address) }}" maxlength="500">
                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3" maxlength="1000">{{ old('description', $company->description) }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 mt-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> Save
                </button>
                <a href="{{ route('superadmin.globalsetup.companies.index') }}" class="btn btn-secondary">Return</a>
            </div>
        </form>
    </div>
</div>
@endsection
