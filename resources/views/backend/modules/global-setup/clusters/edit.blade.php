@extends('backend.layouts.master')

@push('styles')
<style>
  /* ===== Mobile-first responsive rules (scoped to this page) ===== */
  html { font-size: clamp(14px, 1.2vw + 10px, 16px); }

  h1.h4 { font-size: clamp(18px, 1.6vw + 12px, 22px); }
  .text-muted, .form-text { font-size: clamp(12px, .4vw + 10px, 14px); }

  /* Card look & spacing */
  .container .bg-white.rounded-3.border { border-radius: 14px; }
  .container .bg-white.rounded-3.border .p-3 { padding: 1rem !important; }

  /* Inputs/selects */
  .form-control, .form-select { min-height: 42px; font-size: 1rem; }
  select[multiple]#upazila_ids { min-height: 160px; }

  /* Disabled options get subtle style for clarity */
  #upazila_ids option:disabled { color: #94a3b8; background: #f8fafc; }

  /* Labels & spacing */
  .mb-3 { margin-bottom: 0.9rem !important; }
  label.form-label { margin-bottom: .35rem; font-weight: 600; }

  /* Footer buttons */
  .d-flex.justify-content-end.gap-2 { flex-wrap: wrap; }
  .d-flex.justify-content-end.gap-2 .btn { min-width: 120px; }

  /* Breakpoints */
  @media (min-width: 576px) { /* sm */
    select[multiple]#upazila_ids { min-height: 200px; }
  }
  @media (min-width: 768px) { /* md */
    .container .bg-white.rounded-3.border { padding: 1.2rem; }
    .form-control, .form-select { min-height: 44px; }
    .d-flex.justify-content-between.align-items-center { gap: .75rem; }
  }
  @media (min-width: 992px) { /* lg */
    .container .bg-white.rounded-3.border .p-3 { padding: 1.25rem !important; }
    select[multiple]#upazila_ids { min-height: 260px; }
    .d-flex.justify-content-end.gap-2 { flex-wrap: nowrap; }
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
                <h1 class="h4 mb-1">Edit Cluster</h1>
                <div class="text-muted">Update cluster info and upazila mappings.</div>
            </div>
            <a href="{{ route('superadmin.globalsetup.clusters.index') }}" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Back
            </a>
        </div>

        <form method="POST" action="{{ route('superadmin.globalsetup.clusters.update', $row->id) }}">
            @csrf @method('PUT')

            {{-- Division --}}
            <div class="mb-3">
                <label class="form-label">Division</label>
                <select name="division_id" id="division_id" class="form-select" required>
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
                    @foreach($districts as $d)
                        <option value="{{ $d->id }}" {{ (int)$row->district_id === (int)$d->id ? 'selected' : '' }}>
                            {{ $d->short_code }} – {{ $d->name }}
                        </option>
                    @endforeach
                </select>
                @error('district_id') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            {{-- Cluster Name --}}
            <div class="mb-3">
                <label class="form-label">Cluster Name</label>
                <input type="text" name="cluster_name" class="form-control" value="{{ old('cluster_name',$row->cluster_name) }}" required>
                @error('cluster_name') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            {{-- Supervisor (display only) --}}
            <div class="mb-3">
                <label class="form-label">Supervisor</label>
                <input type="text" class="form-control" value="{{ $supervisorName }}" readonly>
            </div>

            {{-- Upazilas (multi-select). Items disabled if taken by other cluster. --}}
            <div class="mb-3">
                <label class="form-label">Upazilas</label>
                <select name="upazila_ids[]" id="upazila_ids" class="form-select" multiple>
                    @foreach($upazilas as $u)
                        <option value="{{ $u->id }}" {{ $u->checked ? 'selected' : '' }} {{ $u->disabled ? 'disabled' : '' }}>
                            {{ $u->short_code }} – {{ $u->name }} {{ $u->disabled && !$u->checked ? '(taken)' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('upazila_ids') <div class="text-danger small">{{ $message }}</div> @enderror
                <div class="form-text">Upazilas assigned to other clusters are disabled.</div>
            </div>

            {{-- Status --}}
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    <option value="1" {{ (int)$row->status===1 ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ (int)$row->status===0 ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status') <div class="text-danger small">{{ $message }}</div> @enderror
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save me-1"></i> Update</button>
                <a href="{{ route('superadmin.globalsetup.clusters.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  // When Division changes, reload districts list via redirect to keep server data consistent
  const $div = document.getElementById('division_id');
  const $dis = document.getElementById('district_id');

  $div?.addEventListener('change', function(){
    const id = this.value;
    const url = new URL(window.location.href);
    url.searchParams.set('division_id', id);
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
