@extends('backend.layouts.master')

@push('styles')
<style>
  /* ===== Mobile-first responsive rules (scoped to this page) ===== */
  html { font-size: clamp(14px, 1.2vw + 10px, 16px); }

  /* Headings & helper text */
  h1.h4 { font-size: clamp(18px, 1.6vw + 12px, 22px); }
  .text-muted, .form-text { font-size: clamp(12px, .4vw + 10px, 14px); }

  /* Card spacing/edges */
  .container .bg-white.rounded-3.border { border-radius: 14px; }
  .container .bg-white.rounded-3.border .p-3 { padding: 1rem !important; }

  /* Inputs/selects */
  .form-control, .form-select { min-height: 42px; font-size: 1rem; }
  /* Multi-select viewport height hint */
  select[multiple]#upazila_ids { min-height: 160px; }

  /* Buttons row: wrap on small, align end on wide */
  .d-flex.justify-content-end.gap-2 { flex-wrap: wrap; }
  .d-flex.justify-content-end.gap-2 .btn { min-width: 120px; }

  /* Compact spacing for stacked fields on small screens */
  .mb-3 { margin-bottom: 0.9rem !important; }
  label.form-label { margin-bottom: .35rem; font-weight: 600; }

  /* Breakpoints (Bootstrap-like widths for familiarity) */
  @media (min-width: 576px) { /* sm */
    select[multiple]#upazila_ids { min-height: 200px; }
  }
  @media (min-width: 768px) { /* md */
    /* Give the form comfortable max width & larger inputs */
    .container .bg-white.rounded-3.border { padding: 1.2rem; }
    .form-control, .form-select { min-height: 44px; }
    /* Make header actions align center nicely */
    .d-flex.justify-content-between.align-items-center { gap: .75rem; }
  }
  @media (min-width: 992px) { /* lg */
    .container .bg-white.rounded-3.border .p-3 { padding: 1.25rem !important; }
    /* Increase multi-select height for productivity on desktop */
    select[multiple]#upazila_ids { min-height: 260px; }
    /* Buttons stay on one line on large screens */
    .d-flex.justify-content-end.gap-2 { flex-wrap: nowrap; }
  }
  @media (min-width: 1200px) { /* xl */ }
  @media (min-width: 1400px) { /* xxl */ }

  /* Respect user motion preferences */
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
                <h1 class="h4 mb-1">Add Cluster</h1>
                <div class="text-muted">Create a new cluster and tag available upazilas.</div>
            </div>
            <a href="{{ route('superadmin.globalsetup.clusters.index', request()->only('division_id','district_id')) }}" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Back
            </a>
        </div>

        <form method="POST" action="{{ route('superadmin.globalsetup.clusters.store') }}">
            @csrf

            {{-- Division --}}
            <div class="mb-3">
                <label class="form-label">Division</label>
                <select name="division_id" id="division_id" class="form-select" required>
                    <option value="">— Select Division —</option>
                    @foreach($divisions as $d)
                        <option value="{{ $d->id }}" {{ (int)$divisionId === (int)$d->id ? 'selected' : '' }}>
                            {{ $d->short_code }} – {{ $d->name }}
                        </option>
                    @endforeach
                </select>
                @error('division_id') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            {{-- District --}}
            <div class="mb-3">
                <label class="form-label">District</label>
                <select name="district_id" id="district_id" class="form-select" required>
                    <option value="">— Select District —</option>
                    @foreach($districts as $d)
                        <option value="{{ $d->id }}" {{ (int)$districtId === (int)$d->id ? 'selected' : '' }}>
                            {{ $d->short_code }} – {{ $d->name }}
                        </option>
                    @endforeach
                </select>
                @error('district_id') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            {{-- Cluster Name --}}
            <div class="mb-3">
                <label class="form-label">Cluster Name</label>
                <input type="text" name="cluster_name" class="form-control" value="{{ old('cluster_name') }}" required>
                @error('cluster_name') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            {{-- Supervisor (display only, blank on create) --}}
            <div class="mb-3">
                <label class="form-label">Supervisor</label>
                <input type="text" class="form-control" value="—" readonly>
                <div class="form-text">Assigned later during registration approval (read-only).</div>
            </div>

            {{-- Upazilas (multi-select) --}}
            <div class="mb-3">
                <label class="form-label">Upazilas (available only)</label>
                <select name="upazila_ids[]" id="upazila_ids" class="form-select" multiple>
                    @foreach($upazilas as $u)
                        <option value="{{ $u->id }}">{{ $u->short_code }} – {{ $u->name }}</option>
                    @endforeach
                </select>
                <div class="form-text">Only upazilas in the selected district that are not assigned to any cluster are listed.</div>
                @error('upazila_ids') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            {{-- Status --}}
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    <option value="1" selected>Active</option>
                    <option value="0">Inactive</option>
                </select>
                @error('status') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i> Save</button>
                <a href="{{ route('superadmin.globalsetup.clusters.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const $div = document.getElementById('division_id');
  const $dis = document.getElementById('district_id');

  $div?.addEventListener('change', function(){
    const id = this.value;
    // simple redirect to keep server-side lists consistent
    const url = new URL(window.location.href);
    url.searchParams.set('division_id', id);
    url.searchParams.delete('district_id');
    window.location.href = url.toString();
  });

  $dis?.addEventListener('change', function(){
    const id = this.value;
    const url = new URL(window.location.href);
    url.searchParams.set('district_id', id);
    window.location.href = url.toString();
  });

  if (window.TomSelect && document.getElementById('upazila_ids')) {
    new TomSelect('#upazila_ids', {
      plugins: ['remove_button'],
      maxOptions: 1000,
      create: false,
      allowEmptyOption: true,
      sortField: [{field:'$order'}],
      placeholder: '— Select Upazilas —'
    });
  }
})();
</script>
@endpush
