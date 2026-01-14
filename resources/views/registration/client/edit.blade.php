@extends('backend.layouts.master')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/calendar.css') }}">
<style>
  .title { color:#0a2240; }
  .form-label { color:#0a2240; }

  .card-soft{ background:#f6fbff; border:1px solid #dbeafe; border-radius:14px; box-shadow:0 6px 16px rgba(15,23,42,.06); padding:16px; }
  .muted{ color:#64748b; }
  .badge-ok{ display:inline-block; background:#eafff1; border:1px solid #bbf7d0; color:#16a34a; font-weight:800; border-radius:9999px; padding:.25rem .6rem; font-size:.85rem; }
  .err{ color:#dc2626; font-size:.875rem; margin-top:6px; }

  .radio-group { border:1px solid #e5e7eb; border-radius:12px; padding:10px; }
  .radio-row { display:flex; gap:12px; flex-wrap:wrap; }
  .radio-item { display:flex; align-items:center; gap:8px; padding:8px 10px; border:1px solid #e5e7eb; border-radius:10px; }
  .radio-item input[type="radio"] { accent-color:#2563eb; width:18px; height:18px; }
  .radio-item span { font-weight:600; color:#0a2240; }
  .radio-item:hover { background:#eef2ff; border-color:#c7d2fe; }

  /* DOB input + icon */
  .dob-wrap { position:relative; }
  .dob-wrap i {
    position:absolute; right:12px; top:50%; transform:translateY(-50%);
    cursor:pointer; font-size:1.1rem; color:#0a2240;
  }
  .dob-wrap input.form-control { padding-right:2.25rem; }

  /* Hide native picker icon */
  .dob-wrap input[type="date"] { -webkit-appearance:none; appearance:none; }
  .dob-wrap input[type="date"]::-webkit-calendar-picker-indicator { display:none; opacity:0; pointer-events:none; }

  .form-control.is-invalid { background-image:none !important; padding-right:.75rem; }

  .calendar-modal { position:fixed; inset:0; background:rgba(0,0,0,.5); display:none; justify-content:center; align-items:center; z-index:1050; }
  .calendar-box { background:#fff; padding:16px; border-radius:12px; max-width:500px; width:95%; box-shadow:0 6px 24px rgba(0,0,0,.25); }

  .is-invalid { border-color:#dc2626 !important; }
  .is-invalid:focus { outline:0; box-shadow:0 0 0 .15rem rgba(220,38,38,.20); }

  @media (max-width: 576px){
    .form-label{ font-size:.95rem; }
  }
</style>
@endpush

@section('content')
@php
  $brand = $company->name ?? 'ArchReach';
  $companyParam = $company->slug ?? $company->id;
@endphp

<div class="container py-3">
  <div class="card-soft">
    <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
      <div>
        <h1 class="title mb-0">{{ $brand }}</h1>
        <div class="muted">Edit Client Registration</div>
      </div>
      <a href="{{ route('backend.company.dashboard.public', ['company'=>$companyParam]) }}" class="btn-x btn-view">
        <i class="fa fa-arrow-left me-1"></i> Back
      </a>
    </div>

    <div class="mb-2">
      Status: <span class="badge-ok">{{ (int)$row->status===1 ? 'Active / Approved' : 'Inactive' }}</span>
    </div>

    <form id="clientRegEditForm" method="POST" action="{{ route('registration.client.update', ['company'=>$companyParam]) }}" novalidate>
      @csrf @method('PUT')

      <div class="row g-3">
        <div class="col-12 col-md-6">
          <label class="form-label fw-bold">Full Name *</label>
          <input type="text" name="full_name" class="form-control" value="{{ old('full_name',$row->full_name) }}" required>
          @error('full_name') <div class="err">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label fw-bold">Email</label>
          <input type="email" name="email" class="form-control" value="{{ old('email',$row->email) }}">
          @error('email') <div class="err">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
          <label class="form-label fw-bold d-block mb-1">Gender *</label>
          <div class="radio-group">
            <div class="radio-row">
              @foreach (['male'=>'Male','female'=>'Female','other'=>'Other'] as $k=>$v)
                <label class="radio-item">
                  <input type="radio" name="gender" value="{{ $k }}" {{ old('gender',$row->gender)===$k ? 'checked':'' }}>
                  <span>{{ $v }}</span>
                </label>
              @endforeach
            </div>
          </div>
          @error('gender') <div class="err">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label fw-bold">Date of Birth</label>
          <div class="dob-wrap">
            <input type="date" id="dob_field_edit" name="date_of_birth" class="form-control"
                   value="{{ old('date_of_birth', $row->date_of_birth) }}">
            <i class="fa-regular fa-calendar-days" id="dob_icon_edit" aria-hidden="true"></i>
          </div>
          @error('date_of_birth') <div class="err">{{ $message }}</div> @enderror
        </div>
      </div>

      {{-- Calendar Modal --}}
      <div class="calendar-modal" id="dob_modal_edit">
        <div class="calendar-box">
          <div id="calendar-container-edit"></div>
          <div class="text-end mt-2">
            <button type="button" class="btn-x btn-view" id="close_calendar_edit">Close</button>
          </div>
        </div>
      </div>

      <div class="row g-3 mt-1">
        <div class="col-12 col-md-4">
          <label class="form-label fw-bold">Mobile (BD) *</label>
          <input type="text" name="phone" class="form-control" value="{{ old('phone',$row->phone) }}" required>
          @error('phone') <div class="err">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-md-4">
          <label class="form-label fw-bold">Division (locked)</label>
          <input type="text" class="form-control" value="{{ ($division->short_code ?? '') . ' — ' . ($division->name ?? '') }}" disabled>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label fw-bold">District (locked)</label>
          <input type="text" class="form-control" value="{{ ($district->short_code ?? '') . ' — ' . ($district->name ?? '') }}" disabled>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label fw-bold">Upazila (locked)</label>
          <input type="text" class="form-control" value="{{ ($upazila->short_code ?? '') . ' — ' . ($upazila->name ?? '') }}" disabled>
        </div>

        {{-- REQUIRED: Thana (depends on District) --}}
        <div class="col-12 col-md-4">
          <label class="form-label fw-bold">Thana *</label>
          <select id="thana_id" name="thana_id" class="form-select" required>
            <option value="" selected disabled hidden>— Select —</option>
          </select>
          @error('thana_id') <div class="err">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label fw-bold d-block mb-1">Person Type *</label>
          @php $types=['J'=>'Service Holder','B'=>'Business Person','H'=>'Housewife','S'=>'Student','P'=>'Professional','O'=>'Other']; @endphp
          <div class="radio-group">
            <div class="radio-row">
              @foreach ($types as $k=>$v)
                <label class="radio-item">
                  <input type="radio" name="person_type" value="{{ $k }}" {{ old('person_type',$row->person_type)===$k ? 'checked':'' }}>
                  <span>{{ $v }}</span>
                </label>
              @endforeach
            </div>
          </div>
          @error('person_type') <div class="err">{{ $message }}</div> @enderror
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label fw-bold">Present Address</label>
          <input type="text" name="present_address" class="form-control" value="{{ old('present_address',$row->present_address) }}">
          @error('present_address') <div class="err">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
          <label class="form-label fw-bold">Notes</label>
          <textarea name="notes" rows="2" class="form-control">{{ old('notes',$row->notes) }}</textarea>
          @error('notes') <div class="err">{{ $message }}</div> @enderror
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="submit" class="btn-x btn-save"><i class="fa fa-save me-1"></i> Update</button>
        <a href="{{ route('backend.company.dashboard.public', ['company'=>$companyParam]) }}" class="btn-x btn-view"><i class="fa fa-arrow-left me-1"></i> Back</a>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/calendar.js') }}"></script>
<script src="{{ asset('assets/js/validation.js') }}"></script>
<script>
(function(){
  /* ---- DOB modal + calendar ---- */
  const modal   = document.getElementById('dob_modal_edit');
  const icon    = document.getElementById('dob_icon_edit');
  const closeBt = document.getElementById('close_calendar_edit');
  const dobIn   = document.getElementById('dob_field_edit');

  icon.addEventListener('click', ()=> modal.style.display='flex');
  closeBt.addEventListener('click', ()=> modal.style.display='none');

  const picker = new DatePicker('#calendar-container-edit', '#dob_field_edit');
  picker.onSelect = function(dateISO){
    dobIn.value = dateISO;
    modal.style.display='none';
    const form = document.getElementById('clientRegEditForm');
    if (!validateDateNotFuture(dobIn.value)) {
      setFieldError(form, 'date_of_birth', 'Date of birth cannot be in the future');
    } else {
      clearFieldError(form, 'date_of_birth');
    }
  };

  /* ---- Thana select population (by District) ---- */
  const companySeg = @json($companyParam);
  const base = `/registration/${companySeg}`;
  const thanaEl = document.getElementById('thana_id');

  function resetSelect(el){
    el.innerHTML = '<option value="" selected disabled hidden>— Select —</option>';
  }

  async function fetchJSON(url){
    try { const r = await fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}});
      return r.ok ? r.json() : [];
    } catch(e){ return []; }
  }

  async function loadThanas(districtId, preselect=null){
    resetSelect(thanaEl);
    if (!districtId) return;
    const rows = await fetchJSON(`${base}/api/geo/thanas?district_id=${encodeURIComponent(districtId)}`);
    rows.forEach(r=>{
      const label = (r.short_code ? (r.short_code + ' — ') : '') + r.name;
      thanaEl.insertAdjacentHTML('beforeend', `<option value="${r.id}">${label}</option>`);
    });
    if (preselect) thanaEl.value = String(preselect);
  }

  document.addEventListener('DOMContentLoaded', function(){
    const currentDistrictId = {{ (int)($row->district_id ?? 0) }};
    const selectedThanaId   = @json(old('thana_id', $row->thana_id ?? ''));
    loadThanas(currentDistrictId, selectedThanaId || null);
  });

  /* ---- Client-side validation ---- */
  const form = document.getElementById('clientRegEditForm');

  attachLiveValidation(form, {
    full_name: () => {
      if (!validateRequired(form.full_name.value)) return 'Full name is required';
      if (!validateMinLen(form.full_name.value, 5)) return 'Full name must be at least 5 characters';
      return null;
    },
    email: () => form.email.value && !validateEmail(form.email.value) ? 'Invalid email address' : null,
    phone: () => !validateBDPhone(form.phone.value) ? 'Enter a valid BD mobile number (01XXXXXXXXX)' : null,
    thana_id: () => !validateRequired(form.thana_id.value) ? 'Thana is required' : null,
    date_of_birth: () => !validateDateNotFuture(form.date_of_birth.value) ? 'Date of birth cannot be in the future' : null
  });

  document.querySelectorAll('input[name="gender"]').forEach(r => r.addEventListener('change', ()=> clearFieldError(form,'gender')));
  document.querySelectorAll('input[name="person_type"]').forEach(r => r.addEventListener('change', ()=> clearFieldError(form,'person_type')));

  form.addEventListener('submit', function(e){
    const errs = [];

    if (!validateRequired(form.full_name.value)) errs.push(['full_name','Full name is required']);
    else if (!validateMinLen(form.full_name.value, 5)) errs.push(['full_name','Full name must be at least 5 characters']);

    if (!document.querySelector('input[name="gender"]:checked')) errs.push(['gender','Gender is required']);

    if (!validateBDPhone(form.phone.value)) errs.push(['phone','Enter a valid BD mobile number (01XXXXXXXXX)']);

    if (form.email.value && !validateEmail(form.email.value)) errs.push(['email','Invalid email address']);

    if (!validateRequired(form.thana_id.value)) errs.push(['thana_id','Thana is required']);

    if (!document.querySelector('input[name="person_type"]:checked')) errs.push(['person_type','Person type is required']);

    if (form.date_of_birth.value && !validateDateNotFuture(form.date_of_birth.value)) errs.push(['date_of_birth','Date of birth cannot be in the future']);

    if (errs.length){
      e.preventDefault();
      renderFieldErrors(form, errs);
    }
  });
})();
</script>
@endpush
