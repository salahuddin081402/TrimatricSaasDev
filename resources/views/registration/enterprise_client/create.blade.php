@extends('backend.layouts.master')
{{-- TMX-ENTC | resources/views/registration/enterprise_client/create.blade.php | v1.0.0
   Enterprise Client Registration (Step-1 & Step-2 only, based on TMX-ENTR v6.4.6)
--}}

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/buttons.css') }}">

<style>
:root{
  --ink:#17212e; --sub:#5b6a7f; --muted:#8f9ab0;
  --brand:#1f3a8a; --accent:#0ea5e9; --gold:#b58900;
  --track:#e7ecf6; --glow:rgba(31,58,138,.18);
  --card-br:#e5e9f2;
  --card-soft-1:#f8fbff; --card-soft-2:#f9fafb; --card-soft-3:#f6f9ff;
  --inner-soft:#fcfdff; --edge:linear-gradient(180deg,#b9c8ea,#1f3a8a);
  --fs-title:clamp(16px,2.1vw,20px); --fs-step:clamp(11px,1.5vw,13px);
  --fs-body:clamp(13px,1.55vw,14px); --fs-sub:clamp(12px,1.35vw,13px);
  --pad:clamp(12px,2.5vw,20px); --ctrlH:44px; --ok:#22a06b; --warn:#d97706; --bad:#d32f2f;
  --pill:#eef4ff; --pill-br:#d9e6ff; --pill-on:#1f3a8a; --soft-bg:#f7fbff; --soft-br:#e5eefc;
}
*{box-sizing:border-box;min-width:0}
html{scroll-behavior:smooth}
body{margin:0;color:var(--ink);font:var(--fs-body)/1.5 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,"Helvetica Neue",Arial}
.container-slim{max-width:1200px;margin:0 auto;padding:var(--pad)}
.card-x{border:1px solid var(--card-br);border-radius:18px;background:var(--card-soft-1)!important;box-shadow:0 18px 40px rgba(23,33,46,.07);padding:16px;margin:14px 0}
.card-x:nth-of-type(2){background:var(--card-soft-2)!important}
.card-x:nth-of-type(3){background:var(--card-soft-3)!important}
.card-inner{background:var(--inner-soft)!important;border:1px solid #e5e9f2;border-radius:14px;padding:14px;box-shadow:0 12px 26px rgba(23,33,46,.06);position:relative}
.card-inner::before{content:"";position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--edge);border-radius:14px 0 0 14px}
.sticky-wrap{position:sticky;top:0;background:#fff;z-index:50;padding:12px 0 10px;border-bottom:1px solid #eff2f8}
.gg-hdr{display:flex;justify-content:space-between;align-items:flex-end;gap:12px}
.gg-title{font-weight:700;font-size:var(--fs-title)}
.gg-hint{color:var(--muted);font-size:var(--fs-sub)}
.gg{margin-top:8px}
.gg-rail{position:relative;padding:18px 0 8px}
.gg-track{position:absolute;left:0;right:0;top:20px;height:8px;border-radius:999px;background:var(--track);overflow:hidden}
.gg-fill{height:100%;width:0;background:linear-gradient(90deg,#6fb1ff 0%,#4aa0d5 40%,#1f3a8a 100%);transition:width .35s ease}
.gg-list{display:flex;gap:12px;align-items:center;list-style:none;margin:0;padding:0 4px;position:relative;z-index:1;overflow-x:auto;scroll-snap-type:x mandatory}
.gg-step{display:flex;flex-direction:column;align-items:center;gap:6px;min-width:92px;scroll-snap-align:center}
.gg-dot{width:24px;height:24px;border-radius:50%;background:#cbd5e1;border:2px solid #cbd5e1}
.gg-step.current .gg-dot{background:var(--gold);border-color:var(--gold);box-shadow:0 0 0 8px var(--glow)}
.gg-name{font-size:var(--fs-step);text-align:center;max-width:120px;font-weight:700;background:linear-gradient(90deg,#0ea5a5,#1f3a8a);-webkit-background-clip:text;background-clip:text;color:transparent}
.section-hdr{display:flex;align-items:center;gap:12px;margin-bottom:10px}
.icon-edge{flex:0 0 46px;height:46px;border-radius:12px;background:#f3f7ff;border:1px solid #dbe4ff;display:grid;place-items:center}
.icon-edge svg{width:22px;height:22px;fill:#2a53c6}
.h-title{margin:0;font-size:clamp(16px,2vw,18px)}
.h-sub{margin:2px 0 0;color:var(--sub);font-size:var(--fs-sub)}
.badge{margin-left:auto;font-size:11px;padding:4px 10px;border-radius:999px;background:#f3f6ff;color:#1f3a8a;border:1px solid #e0e7ff}
.grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:14px}
.col-12{grid-column:1/-1}.col-6{grid-column:span 6}.col-4{grid-column:span 4}.col-3{grid-column:span 3}
@media (max-width:1024px){.col-6,.col-4,.col-3{grid-column:1/-1}}

/* Step-3 specific layout (unused here but kept harmlessly) */
.grid-edu{grid-template-columns:repeat(12,minmax(0,1fr));gap:14px}
@media (min-width:768px){
  .grid-edu .col-6{grid-column:span 6}
  .grid-edu .col-3{grid-column:span 3;min-width:180px}
}

/* form controls */
.fld{display:flex;flex-direction:column;gap:6px}
.lbl{font-weight:700;color:#273a56;font-size:13px}
.lbl-row{display:flex;align-items:center;gap:10px}
.lbl-row .lbl{flex:1}
.err-note{color:var(--bad);font-size:12px}
.req{color:var(--bad);margin-left:4px}
.inp,.sel{display:block;width:100%!important;max-width:100%!important;height:var(--ctrlH);padding:10px 12px;border:1px solid #dbe2f4;border-radius:10px;background:#fff;font-size:14px;color:#17212e;line-height:22px}
.fld > .ts-wrapper{display:block;width:100%!important;max-width:100%!important;margin:0!important;padding:0!important;border:1px solid #dbe2f4;border-radius:10px;background:#fff;line-height:22px;vertical-align:middle}
.ts-wrapper.focus{box-shadow:0 0 0 2px #9cc3ff33}
.ts-wrapper .ts-control{border:0!important;background:transparent!important;box-shadow:none!important;min-height:var(--ctrlH)!important;height:var(--ctrlH)!important;padding:0 12px!important;display:flex;align-items:center;gap:6px}
.ts-wrapper .ts-control input{min-width:0!important;height:calc(var(--ctrlH) - 2px)!important}
.ts-dropdown{max-height:300px;overflow:auto;border-radius:10px;border:1px solid #dbe2f4}
.inp:focus,.sel:focus,.ts-wrapper.focus{outline:2px solid #9cc3ff;outline-offset:1px}
.is-invalid{border-color:var(--bad)!important}
.ts-invalid .ts-wrapper{border-color:var(--bad)!important}
.ts-wrapper.is-invalid{border-color:var(--bad)!important}
.invalid-feedback{color:var(--bad);font-size:12px}
.hidden{display:none}
.radio-set{display:flex;flex-wrap:wrap;gap:10px}
.radio-pill,.pill{
  display:inline-flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid var(--pill-br);
  border-radius:999px;cursor:pointer;min-height:40px; background:var(--pill); user-select:none
}
.pill.selected{background:#e8f0ff;border-color:#bcd3ff;color:var(--pill-on);font-weight:700}
.pill-grid{display:flex;flex-wrap:wrap;gap:10px}
.help{font-size:12px;color:#6b7280}
.photo-box{display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap}
.preview{width:96px;height:96px;border:1px dashed #cbd5e1;border-radius:10px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#f8fafc}
.preview img{max-width:100%;max-height:100%}
.file-ui{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
.file-name{flex:1 1 260px}

/* Buttons */
.btn-x{
  display:inline-flex;align-items:center;gap:8px;
  border-radius:999px;padding:8px 16px;
  font-size:13px;font-weight:600;
  border:1px solid #d1d5db;background:#f3f4f6;
  color:#111827;text-decoration:none;cursor:pointer;
  transition:background .15s.ease,box-shadow .15s.ease,transform .05s.ease;
}
.btn-x svg{flex:0 0 auto}
.btn-x:hover{background:#e5e7eb;box-shadow:0 8px 18px rgba(15,23,42,.08);transform:translateY(-1px)}
.btn-x:active{transform:translateY(0);box-shadow:none}

/* Context-specific buttons */
.btn-back{
  background:#eef2ff;border-color:#c7d2fe;color:#1d4ed8;
}
.btn-back:hover{background:#e0e7ff}

.btn-save{
  background:linear-gradient(90deg,#059669,#16a34a);border-color:#16a34a;color:#f9fafb;
}
.btn-save:hover{background:linear-gradient(90deg,#047857,#15803d)}

.btn-next{
  background:linear-gradient(90deg,#2563eb,#1d4ed8);border-color:#1d4ed8;color:#f9fafb;
}
.btn-next:hover{background:linear-gradient(90deg,#1d4ed8,#1e40af)}

.btn-cancel{
  background:#f9fafb;border-color:#e5e7eb;color:#374151;
}
.btn-cancel:hover{background:#f3f4f6}

/* Step footer */
.form-actions{display:flex;gap:10px;justify-content:space-between;padding:12px 0;flex-wrap:wrap}

/* Blocks kept for compatibility (education/training styles) */
.rec-block{
  border:1px solid var(--soft-br); background:var(--soft-bg);
  border-radius:14px; padding:12px 12px 10px; margin:10px 0;
}
.rec-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:8px;align-items:center}
.sub-card{border:1px dashed var(--soft-br); background:#fff; padding:12px;border-radius:12px}
.soft-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:10px}
.soft-name{grid-column:span 8}
.soft-years{grid-column:span 4}
.note-ghost{opacity:.75;font-size:12px}

/* calendar portal */
#dob-cal-portal{position:fixed; z-index:99999; display:none;background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 12px 28px rgba(23,33,46,.18)}
#dob-cal-portal.open{display:block}
.calendar-card{max-width:320px}
.err-list{margin:6px 0 0 18px;color:var(--bad)}
</style>
@endpush

@section('content')
@php
  $currentStep = (int)($step ?? 1);
  $fv = fn($k, $def = '') => old($k, $form[$k] ?? $def);

  // Resolve registration id once for navigation / job preload
  $rawRegId = null;

  if (isset($reg) && is_object($reg) && isset($reg->id)) {
      $rawRegId = $reg->id;
  } elseif (isset($reg) && is_numeric($reg)) {
      $rawRegId = $reg;
  } elseif (isset($existing) && is_object($existing) && isset($existing->id)) {
      $rawRegId = $existing->id;
  } elseif (!empty($form['id'] ?? null)) {
      $rawRegId = $form['id'];
  } elseif (!empty($form['reg_id'] ?? null)) {
      $rawRegId = $form['reg_id'];
  } elseif (isset($regId) && is_numeric($regId)) {
      $rawRegId = $regId;
  } else {
      $rq = request();
      if ($rq) {
          $rawRegId = $rq->input('reg', $rq->query('reg', 0));
      }
  }

  $regId = (int)($rawRegId ?? 0);

  $photoTemp = $tempUploads['temp_photo'] ?? '';
  $photoUrl  = $photoTemp ?: ($form['photo_url'] ?? '');
  $photoName = $photoTemp ? basename(parse_url($photoTemp, PHP_URL_PATH)) : ($photoUrl ? basename(parse_url($photoUrl, PHP_URL_PATH)) : '');
@endphp

<div class="container-slim"
     data-company-slug="{{ $company->slug }}"
     data-route-districts="{{ route('registration.enterprise_client.api.geo.districts', ['company'=>$company->slug]) }}"
     data-route-upazilas="{{ route('registration.enterprise_client.api.geo.upazilas', ['company'=>$company->slug]) }}"
     data-route-thanas="{{ route('registration.enterprise_client.api.geo.thanas', ['company'=>$company->slug]) }}"
     data-route-temp-upload="{{ route('registration.enterprise_client.api.temp_upload', ['company'=>$company->slug]) }}"
     data-csrf="{{ csrf_token() }}">

  <header class="sticky-wrap" role="navigation" aria-label="Registration progress">
    <div class="gg-hdr">
      <div class="gg-title">Enterprise Client Registration</div>
      <small class="gg-hint">
        @if($currentStep===1) Step 1 of 2
        @elseif($currentStep===2) Step 2 of 2
        @endif
      </small>
    </div>
    <div class="gg">
      <div class="gg-rail">
        <div class="gg-track">
          @php
            $fillPct = [1=>'0%', 2=>'100%'][$currentStep] ?? '0%';
          @endphp
          <div class="gg-fill" id="ggFill" style="width:{{ $fillPct }}"></div>
        </div>
        <ol class="gg-list">
          <li class="gg-step {{ $currentStep===1 ? 'current' : '' }}">
            <div class="gg-dot"></div>
            <div class="gg-name">Basic Info</div>
          </li>
          <li class="gg-step {{ $currentStep===2 ? 'current' : '' }}">
            <div class="gg-dot"></div>
            <div class="gg-name">Present Job</div>
          </li>
        </ol>
      </div>
    </div>
  </header>

  @if(session('success'))
    <div class="card-x" style="border-color:#22a06b">
      <div class="h-title" style="color:#15803d;margin:0 0 4px">Success</div>
      <div class="h-sub">{{ session('success') }}</div>
    </div>
  @endif

  @if($errors->any())
    <div class="card-x" id="err-panel" style="border-color:#d32f2f">
      <div class="h-title" style="color:#d32f2f;margin:0 0 4px">Submission failed</div>
      <div class="h-sub">Fix the issues below and resubmit.</div>
      <ul class="err-list">
        @foreach ($errors->all() as $message)
          <li>{{ $message }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- ========================== STEP 1 ========================== --}}
  @if($currentStep===1)
  <form id="enterprise-client-step1"
        method="POST"
        action="{{ route('registration.enterprise_client.step1.store', ['company'=>$company->slug]) }}"
        enctype="multipart/form-data" novalidate>
    @csrf
    <input type="hidden" name="registration_type" value="enterprise_client">

    {{-- Basic Info --}}
    <div class="card-x">
      <div class="section-hdr">
        <div class="icon-edge" aria-hidden="true">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-5 0-9 2.5-9 5.5A1.5 1.5 0 0 0 4.5 21h15A1.5 1.5 0 0 0 21 19.5C21 16.5 17 14 12 14Z"/></svg>
        </div>
        <div>
          <h3 class="h-title">Basic Info</h3>
          <p class="h-sub">Required fields marked</p>
        </div>
        <span class="badge">Phase 1</span>
      </div>

      <div class="card-inner">
        <div class="grid">
          <div class="col-6">
            <div class="fld">
              <label class="lbl">Registration Type</label>
              <div class="inp" style="background:#f8fafc">Enterprise Client</div>
              <div class="help">Saved as <code>enterprise_client</code></div>
            </div>
          </div>

          <div class="col-6">
            <div class="fld">
              <label class="lbl">Full Name</label>
              @php $nameVal = old('full_name', $form['full_name'] ?? ($ctx['name'] ?? '')); @endphp
              <input type="text"
                     name="full_name"
                     value="{{ $nameVal }}"
                     class="inp @error('full_name') is-invalid @enderror"
                     {{ $nameVal ? 'readonly' : '' }}
                     placeholder="Enter your name"
                     autocomplete="name">
              @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="col-6">
            <div class="fld">
              <div class="lbl-row">
                <label class="lbl">Gender<span class="req">*</span></label>
                <span class="err-note hidden" data-radio-err="gender">Select one option</span>
              </div>
              @php $gVal = old('gender', $form['gender'] ?? ''); @endphp
              <div class="radio-set @error('gender') ts-invalid @enderror"
                   data-required-radio="gender">
                @foreach(['male'=>'Male','female'=>'Female','other'=>'Other'] as $gKey=>$gText)
                  <label class="radio-pill">
                    <input type="radio" name="gender" value="{{ $gKey }}" {{ $gVal===$gKey?'checked':'' }}>
                    {{ $gText }}
                  </label>
                @endforeach
              </div>
              @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="col-6">
            <div class="fld" style="position:relative">
              <label class="lbl">Date of Birth</label>
              <div style="position:relative">
                <input type="date"
                       id="date_of_birth"
                       name="date_of_birth"
                       value="{{ $fv('date_of_birth') }}"
                       class="inp @error('date_of_birth') is-invalid @enderror"
                       placeholder="YYYY-MM-DD"
                       autocomplete="off"
                       max="{{ date('Y-m-d') }}">
                <span id="dob-icon"
                      style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer"
                      title="Open calendar"
                      aria-hidden="true">📅</span>
              </div>
              @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div id="dob-future-err" class="invalid-feedback" style="display:none">Future date not allowed.</div>
            </div>
          </div>

          <div class="col-6">
            <div class="fld">
              <label class="lbl">Mobile<span class="req">*</span></label>
              <input required
                     aria-invalid="@error('phone')true @enderror"
                     type="text"
                     name="phone"
                     value="{{ $fv('phone') }}"
                     class="inp @error('phone') is-invalid @enderror"
                     placeholder="01XXXXXXXXX"
                     autocomplete="tel">
              @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="invalid-feedback hidden" data-client-error="phone">Mobile is required.</div>
              <div class="help">Bangladesh format. Example: 017XXXXXXXX</div>
            </div>
          </div>

          <div class="col-6">
            <div class="fld">
              <label class="lbl">Email</label>
              @php $emailVal = old('email', $form['email'] ?? ($ctx['email'] ?? '')); @endphp
              <input type="email"
                     name="email"
                     value="{{ $emailVal }}"
                     class="inp @error('email') is-invalid @enderror"
                     {{ $emailVal ? 'readonly' : '' }}
                     placeholder="you@example.com"
                     autocomplete="email">
              @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          {{-- Division + District --}}
          <div class="col-6">
            <div class="fld">
              <label class="lbl">Division<span class="req">*</span></label>
              @php $divOld = (string)old('division_id', (string)($form['division_id'] ?? '')); @endphp
              <select required
                      aria-invalid="@error('division_id')true @enderror"
                      id="division_id"
                      name="division_id"
                      class="sel @error('division_id') is-invalid @enderror"
                      data-old-val="{{ $divOld }}">
                <option value="">Select Division</option>
                @foreach($divisions as $d)
                  <option value="{{ $d->id }}" {{ $divOld===(string)$d->id?'selected':'' }}>
                    {{ $d->name }}
                  </option>
                @endforeach
              </select>
              @error('division_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="invalid-feedback hidden" data-client-error="division_id">Division is required.</div>
            </div>
          </div>

          <div class="col-6">
            <div class="fld">
              <label class="lbl">District<span class="req">*</span></label>
              @php $disOld = (string)old('district_id', (string)($form['district_id'] ?? '')); @endphp
              <select required
                      aria-invalid="@error('district_id')true @enderror"
                      id="district_id"
                      name="district_id"
                      class="sel @error('district_id') is-invalid @enderror"
                      data-old-val="{{ $disOld }}">
                <option value="">{{ $disOld ? 'Loading...' : 'Select District' }}</option>
              </select>
              @error('district_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="invalid-feedback hidden" data-client-error="district_id">District is required.</div>
            </div>
          </div>

          {{-- Upazila + Thana --}}
          <div class="col-6">
            <div class="fld">
              <label class="lbl">Upazila<span class="req">*</span></label>
              @php $upaOld = (string)old('upazila_id', (string)($form['upazila_id'] ?? '')); @endphp
              <select required
                      aria-invalid="@error('upazila_id')true @enderror"
                      id="upazila_id"
                      name="upazila_id"
                      class="sel @error('upazila_id') is-invalid @enderror"
                      data-old-val="{{ $upaOld }}">
                <option value="">{{ $upaOld ? 'Loading...' : 'Select Upazila' }}</option>
              </select>
              @error('upazila_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="invalid-feedback hidden" data-client-error="upazila_id">Upazila is required.</div>
            </div>
          </div>

          <div class="col-6">
            <div class="fld">
              <label class="lbl">Thana<span class="req">*</span></label>
              @php $thaOld = (string)old('thana_id', (string)($form['thana_id'] ?? '')); @endphp
              <select required
                      aria-invalid="@error('thana_id')true @enderror"
                      id="thana_id"
                      name="thana_id"
                      class="sel @error('thana_id') is-invalid @enderror"
                      data-old-val="{{ $thaOld }}">
                <option value="">{{ $thaOld ? 'Loading...' : 'Select Thana' }}</option>
              </select>
              @error('thana_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="invalid-feedback hidden" data-client-error="thana_id">Thana is required.</div>
            </div>
          </div>

          {{-- Person Type --}}
          <div class="col-12">
            <div class="fld">
              <div class="lbl-row">
                <label class="lbl">Person Type<span class="req">*</span></label>
                <span class="err-note hidden" data-radio-err="person_type">Select one option</span>
              </div>
              @php $ptVal = (string)old('person_type', (string)($form['person_type'] ?? '')); @endphp
              <div class="radio-set @error('person_type') ts-invalid @enderror"
                   data-required-radio="person_type"
                   id="person_type_group">
                @php
                  $ptMap = [
                    'J'=>'Job Seeker',
                    'B'=>'Business man',
                    'H'=>'Housewife',
                    'S'=>'Student',
                    'P'=>'Professional',
                    'O'=>'Other'
                  ];
                @endphp
                @foreach($ptMap as $key=>$text)
                  <label class="radio-pill">
                    <input type="radio" name="person_type" value="{{ $key }}" {{ $ptVal===$key?'checked':'' }}>
                    {{ $text }}
                  </label>
                @endforeach
              </div>
              @error('person_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="col-12">
            <div class="fld">
              <label class="lbl">Present Address<span class="req">*</span></label>
              <input required
                     aria-invalid="@error('present_address')true @enderror"
                     type="text"
                     name="present_address"
                     value="{{ $fv('present_address') }}"
                     class="inp @error('present_address') is-invalid @enderror"
                     placeholder="House, road, area, city"
                     autocomplete="street-address">
              @error('present_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="invalid-feedback hidden" data-client-error="present_address">Present address is required.</div>
            </div>
          </div>

          {{-- Photo --}}
          <div class="col-12">
            <div class="fld">
              <label class="lbl">Photo<span class="req">*</span></label>
              <div class="photo-box">
                <div class="preview" id="photo-preview">
                  @if($photoUrl)
                    <img src="{{ $photoUrl }}" alt="photo preview">
                  @else
                    <span>Preview</span>
                  @endif
                </div>
                <div style="flex:1;min-width:260px">
                  <div class="file-ui">
                    <input type="text" id="photo_name" class="inp file-name" value="{{ $photoName }}" placeholder="No file chosen" readonly>
                    <button type="button" id="photo_btn" class="btn-x" style="white-space:nowrap">Choose File</button>
                  </div>
                  <input type="file" id="photo" name="photo" accept="image/*" class="@error('photo') is-invalid @enderror" style="display:none">
                  <input type="hidden" name="temp_photo" id="temp_photo" value="{{ $photoTemp }}">
                  {{-- tells JS there is already a persisted photo in DB --}}
                  <input type="hidden" id="existing_photo" value="{{ $photoUrl ? 1 : '' }}">
                  <div class="help">
                    Max {{ config('registration.max_image_kb', 1024) }}KB.
                    Allowed: jpg, jpeg, png, webp, gif, jfif, bmp.
                  </div>
                  @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <div class="invalid-feedback hidden" data-client-error="photo">Photo is required.</div>
                </div>
              </div>
            </div>
          </div>

          {{-- Profession --}}
          <div class="col-12">
            <div class="fld">
              <div class="lbl-row">
                <label class="lbl">Profession<span class="req">*</span></label>
                <span class="err-note hidden" data-radio-err="profession">Select one option</span>
              </div>
              @php $profVal = (string)old('profession', (string)($form['profession'] ?? '')); @endphp
              <div class="radio-set @error('profession') ts-invalid @enderror"
                   id="profession-set"
                   data-required-radio="profession">
                @foreach($professions as $p)
                  <label class="radio-pill">
                    <input type="radio" name="profession" value="{{ $p->id }}" {{ $profVal===(string)$p->id?'checked':'' }}>
                    {{ $p->profession ?? $p->Profession ?? $p->profession_name ?? $p->Profession_Name ?? $p->name ?? $p->title ?? ('#'.$p->id) }}
                  </label>
                @endforeach
              </div>
              @error('profession')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

        </div>
      </div>
    </div>

    {{-- Business Info --}}
    @php
      $bizVal = (string)old('do_you_have_business', (string)($form['do_you_have_business'] ?? '0'));
      $btVal  = (string)old('business_type_id', (string)($form['business_type_id'] ?? ''));
      $cnVal  = old('company_name', $form['company_name'] ?? '');
      $yrVal  = (string)old('company_establishment_year', (string)($form['company_establishment_year'] ?? ''));
      $caVal  = old('company_address', $form['company_address'] ?? '');
      $ccVal  = old('company_contact_no', $form['company_contact_no'] ?? '');
      $toVal  = old('turn_over', $form['turn_over'] ?? '');
    @endphp

    <div class="card-x">
      <div class="section-hdr">
        <div class="icon-edge" aria-hidden="true">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 4h6a2 2 0 0 1 2 2v2h2.5A1.5 1.5 0 0 1 21 9.5v9A1.5 1.5 0 0 1 19.5 20h-15A1.5 1.5 0 0 1 3 18.5v-9A1.5 1.5 0 0 1 4.5 8H7V6a2 2 0 0 1 2-2Zm1 4h4V6h-4Z"/></svg>
        </div>
        <div>
          <h3 class="h-title">Business Info</h3>
          <p class="h-sub">Your Business Details (if you are business man)</p>
        </div>
        <span class="badge">Phase 1 · Business</span>
      </div>

      <div class="card-inner">
        <div class="grid">
          <div class="col-12">
            <div class="fld">
              <label class="lbl">Do you have business?<span class="req">*</span></label>
              <div class="radio-set" id="biz_group">
                <label class="radio-pill">
                  <input type="radio" name="do_you_have_business" value="1" {{ $bizVal==='1'?'checked':'' }}>
                  Yes
                </label>
                <label class="radio-pill">
                  <input type="radio" name="do_you_have_business" value="0" {{ $bizVal!=='1'?'checked':'' }}>
                  No
                </label>
              </div>
              <div class="help" id="biz-lock-msg" style="display:none;color:#b45309">
                In Personal Info, you selected Business man.
              </div>
              @error('do_you_have_business')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div id="biz-fields" class="col-12">
            <div class="grid">
              <div class="col-6">
                <div class="fld">
                  <label class="lbl">Business Type<span class="req">*</span></label>
                  <select id="business_type_id"
                          name="business_type_id"
                          class="sel @error('business_type_id') is-invalid @enderror">
                    <option value="">Select Business Type</option>
                    @foreach($businessTypes as $bt)
                      <option value="{{ $bt->id }}" {{ $btVal===(string)$bt->id?'selected':'' }}>
                        {{ $bt->name ?? $bt->business_type ?? $bt->Business_Type ?? ('#'.$bt->id) }}
                      </option>
                    @endforeach
                  </select>
                  @error('business_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="col-6">
                <div class="fld">
                  <label class="lbl">Company Name<span class="req">*</span></label>
                  <input type="text"
                         id="company_name"
                         name="company_name"
                         value="{{ $cnVal }}"
                         class="inp @error('company_name') is-invalid @enderror"
                         placeholder="Registered name"
                         autocomplete="organization">
                  @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="col-6">
                <div class="fld">
                  <label class="lbl">Establishment Year<span class="req">*</span></label>
                  <select id="company_establishment_year"
                          name="company_establishment_year"
                          class="sel @error('company_establishment_year') is-invalid @enderror">
                    <option value="">Select Year</option>
                    @for($y=1950; $y<= (int)date('Y'); $y++)
                      <option value="{{ $y }}" {{ $yrVal===(string)$y?'selected':'' }}>
                        {{ $y }}
                      </option>
                    @endfor
                  </select>
                  @error('company_establishment_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="col-6">
                <div class="fld">
                  <label class="lbl">Company Address<span class="req">*</span></label>
                  <input type="text"
                         name="company_address"
                         value="{{ $caVal }}"
                         class="inp @error('company_address') is-invalid @enderror"
                         placeholder="Address"
                         autocomplete="street-address">
                  @error('company_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="col-6">
                <div class="fld">
                  <label class="lbl">Company Contact No<span class="req">*</span></label>
                  <input type="text"
                         name="company_contact_no"
                         value="{{ $ccVal }}"
                         class="inp @error('company_contact_no') is-invalid @enderror"
                         placeholder="01XXXXXXXXX"
                         autocomplete="tel">
                  @error('company_contact_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="col-6">
                <div class="fld">
                  <label class="lbl">Turnover<span class="req">*</span></label>
                  <div class="radio-set @error('turn_over') ts-invalid @enderror"
                       data-required-radio="turn_over"
                       id="turnover_group">
                    @foreach(['0 - 50k','50k - 1 lac','1 lac - 5 lac','5 lac - 10 lac','10 lac - 50 lac','50 lac - 1 Cr','1 Cr - Above'] as $rng)
                      <label class="radio-pill">
                        <input type="radio" name="turn_over" value="{{ $rng }}" {{ $toVal===$rng?'checked':'' }}>
                        {{ $rng }}
                      </label>
                    @endforeach
                  </div>
                  @error('turn_over')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <span class="err-note hidden" data-radio-err="turn_over">Select one option</span>
                </div>
              </div>

            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="form-actions">
      <a href="{{ route('backend.company.dashboard.index', ['company'=>$company->slug]) }}" class="btn-x btn-back">
        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
          <path d="M10 19l-7-7 7-7v4h8v6h-8v4z"/>
        </svg>
        Back
      </a>

      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <button type="submit" class="btn-x btn-save">
          <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
            <path d="M17 3H5a2 2 0 0 0-2 2v14l4-4h10a2 2 0 0 0 2-2V5a2 2 0 0 0 2-2z"/>
          </svg>
          Save
        </button>

        <button type="submit" class="btn-x btn-next" data-role="next">
          <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
            <path d="M10 17l5-5-5-5v10z"/>
          </svg>
          Next
        </button>

        <a href="{{ route('backend.company.dashboard.index', ['company'=>$company->slug]) }}" class="btn-x btn-cancel">
          <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
            <path d="M18.3 5.71 12 12.01l-6.3-6.3-1.4 1.41 6.29 6.3-6.3 6.3 1.41 1.41 6.3-6.29 6.29 6.29 1.41-1.41-6.29-6.3z"/>
          </svg>
          Cancel
        </a>
      </div>
    </div>
  </form>
  @endif

  {{-- ========================== STEP 2 ========================== --}}
  @if($currentStep===2)
  @php
    $job = $job ?? ($jobRow ?? ($jobData ?? null));
    $jobVal = function(string $key) use ($job) {
        $old = old($key, null);
        if(!is_null($old)) return $old;
        if(is_array($job) && array_key_exists($key, $job)) return $job[$key];
        if(is_object($job) && isset($job->$key)) return $job->$key;
        return '';
    };
    $regIdForNav = $regId;
  @endphp

  <form id="enterprise-client-step2"
        method="POST"
        action="{{ route('registration.enterprise_client.step2.store', ['company'=>$company->slug]) }}"
        novalidate>
    @csrf
    <input type="hidden" name="reg" value="{{ $regIdForNav }}">

    <div class="card-x">
      <div class="section-hdr">
        <div class="icon-edge" aria-hidden="true">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v2H4zM4 11h16v2H4zM4 15h10v2H4z"/></svg>
        </div>
        <div>
          <h3 class="h-title">Step 2: Present Job</h3>
          <p class="h-sub">Optional: leave all fields empty to skip; Save will complete this step.</p>
        </div>
        <span class="badge">Phase 2</span>
      </div>

      <div class="card-inner">
        <div class="grid">
          <div class="col-6">
            <div class="fld">
              <label class="lbl">Employer</label>
              <input type="text"
                     name="employer"
                     value="{{ $jobVal('employer') }}"
                     class="inp @error('employer') is-invalid @enderror"
                     placeholder="Company or organization">
              @error('employer')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="col-6">
            <div class="fld">
              <label class="lbl">Job Title</label>
              <input type="text"
                     name="job_title"
                     value="{{ $jobVal('job_title') }}"
                     class="inp @error('job_title') is-invalid @enderror"
                     placeholder="e.g., Software Engineer">
              @error('job_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="col-6">
            <div class="fld">
              <label class="lbl">Department</label>
              <input type="text"
                     name="department"
                     value="{{ $jobVal('department') }}"
                     class="inp @error('department') is-invalid @enderror"
                     placeholder="e.g., IT">
              @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="col-6">
            <div class="fld" style="position:relative">
              <label class="lbl">Joining Date</label>
              <input type="date"
                     name="joining_date"
                     value="{{ $jobVal('joining_date') }}"
                     class="inp @error('joining_date') is-invalid @enderror"
                     max="{{ date('Y-m-d') }}">
              @error('joining_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="help">Must not be in the future.</div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="form-actions">
      <a href="{{ route('registration.enterprise_client.step1.create', ['company'=>$company->slug, 'reg'=>$regIdForNav]) }}" class="btn-x btn-back">
        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
          <path d="M10 19l-7-7 7-7v4h8v6h-8v4z"/>
        </svg>
        Back to Step-1
      </a>

      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <button type="submit" class="btn-x btn-save">
          <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
            <path d="M17 3H5a2 2 0 0 0-2 2v14l4-4h10a2 2 0 0 0 2-2V5a2 2 0 0 0 2-2z"/>
          </svg>
          Save
        </button>

        {{-- No Next button here: Step-2 is final --}}
        <a href="{{ route('backend.company.dashboard.index', ['company'=>$company->slug]) }}" class="btn-x btn-cancel">
          <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
            <path d="M18.3 5.71 12 12.01l-6.3-6.3-1.4 1.41 6.29 6.3-6.3 6.3 1.41 1.41 6.3-6.29 6.29 6.29 1.41-1.41-6.29-6.3z"/>
          </svg>
          Cancel
        </a>
      </div>
    </div>
  </form>
  @endif

  <div id="dob-cal-portal" class="calendar-card" aria-hidden="true"></div>
</div>
@endsection

@push('scripts')
@verbatim
<script>
document.addEventListener('DOMContentLoaded', function(){
  var root = document.querySelector('.container-slim');

  function dataOr(el, key, d){
    if(!el || !el.dataset) return d;
    return el.dataset[key] !== undefined ? el.dataset[key] : d;
  }
  function tsInstance(el){
    if(!window.TomSelect) return null;
    if(!el) return null;
    return el.tomselect || null;
  }
  function currentVal(el){
    var t = tsInstance(el);
    if(t) return t.getValue();
    return el ? el.value : '';
  }

  // -------- Server-side error focus ----------
  if(document.getElementById('err-panel')){
    setTimeout(function(){
      var el = document.querySelector('.is-invalid, .ts-invalid .ts-control, .ts-wrapper.is-invalid .ts-control, .ts-invalid input');
      if(el){
        try{ el.focus({preventScroll:false}); }catch(e){}
        el.scrollIntoView({behavior:'smooth', block:'center'});
      }
    }, 60);
  }

  // =========================================================
  // Step-1: Geo cascade, Business lock, Photo temp-upload, validation
  // =========================================================
  var form1 = document.getElementById('enterprise-client-step1');

  function getJSON(url){
    var bust = (url.indexOf('?')>-1? '&':'?') + 't=' + Date.now();
    return fetch(url + bust, {headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){ return r.ok ? r.json() : []; })
      .then(function(j){
        if(!j || typeof j.length==='undefined') return [];
        var out=[], i, x;
        for(i=0;i<j.length;i++){
          x=j[i];
          out.push({id:String(x.id||x.value||x.Id||''), name:String(x.name||x.text||x.Name||'')});
        }
        return out;
      })
      .catch(function(){ return []; });
  }

  function setSelectOptions(el, rows, selected, placeholder){
    if(!el) return;
    var p = placeholder || 'Select';
    var selPref = selected || el.getAttribute('data-old-val') || '';
    var html = '<option value="">' + p + '</option>';
    var i, r, sel;
    for(i=0;i<rows.length;i++){
      r=rows[i];
      sel=(String(selPref)===String(r.id))?' selected':'';
      html += '<option value="'+r.id+'"'+sel+'>'+r.name+'</option>';
    }
    el.innerHTML = html;

    var t = tsInstance(el);
    if(t){
      t.clear(true);
      t.clearOptions();
      t.addOption({value:"", text:p});
      for(i=0;i<rows.length;i++){
        r=rows[i];
        t.addOption({value:String(r.id), text:String(r.name)});
      }
      t.refreshOptions(false);
      if(selPref){
        t.setValue(String(selPref), true);
      }
    }else{
      if(selPref){
        el.value = String(selPref);
      }
    }
  }

  function wireStep1(){
    if(!form1) return;

    var cfg = {
      rDistricts: dataOr(root,'routeDistricts',''),
      rUpazilas : dataOr(root,'routeUpazilas',''),
      rThanas   : dataOr(root,'routeThanas',''),
      tempUp    : dataOr(root,'routeTempUpload',''),
      csrf      : dataOr(root,'csrf','')
    };

    var selDiv = document.getElementById('division_id');
    var selDis = document.getElementById('district_id');
    var selUpa = document.getElementById('upazila_id');
    var selTha = document.getElementById('thana_id');

    function onDivisionChange(useVal){
      if(!selDiv || !selDis || !selUpa || !selTha) return;
      var divisionId = typeof useVal!=='undefined' ? useVal : currentVal(selDiv);
      var disPref = selDis.getAttribute('data-old-val') || '';
      var upaPref = selUpa.getAttribute('data-old-val') || '';
      var thaPref = selTha.getAttribute('data-old-val') || '';
      setSelectOptions(selDis, [], disPref, 'Select District');
      setSelectOptions(selUpa, [], upaPref, 'Select Upazila');
      setSelectOptions(selTha, [], thaPref, 'Select Thana');
      if(!divisionId) return;
      getJSON(cfg.rDistricts+'?division_id='+encodeURIComponent(divisionId)).then(function(rows){
        setSelectOptions(selDis, rows, disPref, 'Select District');
        if(disPref){
          onDistrictChange(disPref);
        }
      });
    }

    function onDistrictChange(useVal){
      if(!selDis || !selUpa || !selTha) return;
      var districtId = (typeof useVal!=='undefined' && useVal!==null && useVal!=='') ? useVal : currentVal(selDis);
      var upaPref = selUpa.getAttribute('data-old-val') || '';
      var thaPref = selTha.getAttribute('data-old-val') || '';
      setSelectOptions(selUpa, [], upaPref, 'Select Upazila');
      setSelectOptions(selTha, [], thaPref, 'Select Thana');
      if(!districtId) return;
      var u = cfg.rUpazilas+'?district_id='+encodeURIComponent(districtId);
      var t = cfg.rThanas  +'?district_id='+encodeURIComponent(districtId);
      Promise.all([getJSON(u), getJSON(t)]).then(function(arr){
        var upas = arr[0]||[], thas = arr[1]||[];
        setSelectOptions(selUpa, upas, upaPref, 'Select Upazila');
        setSelectOptions(selTha, thas, thaPref, 'Select Thana');
      });
    }

    form1._onDivisionChange = onDivisionChange;
    form1._onDistrictChange = onDistrictChange;

    function bindChange(el, fn){
      if(!el || el._bound) return;
      el._bound = true;
      var t = tsInstance(el);
      if(t){
        t.on('change', fn);
      } else {
        el.addEventListener('change', fn);
      }
    }
    bindChange(selDiv, function(){ onDivisionChange(); });
    bindChange(selDis, function(){ onDistrictChange(); });

    function hasBusiness(){
      var v = (document.querySelector('input[name="do_you_have_business"]:checked')||{}).value;
      var pt = (document.querySelector('input[name="person_type"]:checked')||{}).value;
      return v==='1' || pt==='B';
    }
    function toggleBizFields(){
      var block = document.getElementById('biz-fields');
      if(block){ block.style.display = hasBusiness() ? 'block' : 'none'; }
    }
    function enforceBusinessLock(){
      var pt = (document.querySelector('input[name="person_type"]:checked')||{}).value;
      var yes = document.querySelector('input[name="do_you_have_business"][value="1"]');
      var no  = document.querySelector('input[name="do_you_have_business"][value="0"]');
      var msg = document.getElementById('biz-lock-msg');
      if(pt==='B'){
        if(yes){ yes.checked=true; yes.dispatchEvent(new Event('change')); }
        if(no){ no.disabled=true; }
        if(msg){ msg.style.display='block'; }
      } else {
        if(no){ no.disabled=false; }
        if(msg){ msg.style.display='none'; }
      }
      toggleBizFields();
    }
    form1._enforceBusinessLock = enforceBusinessLock;

    var ptGrp = document.getElementById('person_type_group');
    if(ptGrp){ ptGrp.addEventListener('change', enforceBusinessLock); }
    var bizGrp = document.getElementById('biz_group');
    if(bizGrp){ bizGrp.addEventListener('change', toggleBizFields); }
    enforceBusinessLock();

    // Photo chooser / temp upload
    (function bindPhotoChooser(){
      var btn = document.getElementById('photo_btn');
      var inp = document.getElementById('photo');
      var nameBox = document.getElementById('photo_name');
      var prev = document.getElementById('photo-preview');
      var hidden = document.getElementById('temp_photo');
      if(btn){
        btn.addEventListener('click', function(){
          if(inp) inp.click();
        });
      }
      if(inp){
        inp.addEventListener('change', function(){
          var f = (inp.files && inp.files[0]) ? inp.files[0] : null;
          if(!f) return;
          if(nameBox) nameBox.value = f.name;
          if(prev){
            var url = URL.createObjectURL(f);
            prev.innerHTML = '<img src="'+url+'" alt="photo preview">';
          }
          var fd = new FormData();
          fd.append('file', f);
          fd.append('field', 'photo');
          fetch(dataOr(root,'routeTempUpload',''), {
            method:'POST',
            headers:{'X-CSRF-TOKEN': dataOr(root,'csrf','')},
            body:fd
          })
          .then(function(r){ return r.json(); })
          .then(function(j){
            if(j && j.ok && j.url && hidden){
              hidden.value = j.url;
            }
          })
          .catch(function(){});
        });
      }
    })();

    // Client-side validation for Step-1
    function markRadioGroup(name){
      var wrap = document.querySelector('[data-required-radio="'+name+'"]');
      var note = document.querySelector('[data-radio-err="'+name+'"]');
      var checked = document.querySelector('input[name="'+name+'"]:checked');
      var err = !checked;
      if(wrap){
        if(err){ wrap.classList.add('ts-invalid'); }
        else{ wrap.classList.remove('ts-invalid'); }
      }
      if(note){
        if(err){ note.classList.remove('hidden'); }
        else{ note.classList.add('hidden'); }
      }
      return err;
    }
    function markRequiredInput(el, key){
      if(!el) return false;
      var v = (el.value||'').trim();
      var err = !v;
      var note;
      if(err){
        el.classList.add('is-invalid');
        note = document.querySelector('[data-client-error="'+key+'"]');
        if(note) note.classList.remove('hidden');
      }
      return err;
    }
    function reqSelect(id, key){
      var s = document.getElementById(id);
      if(!s) return false;
      var val = currentVal(s);
      var note;
      if(!val){
        var t = tsInstance(s);
        if(t && t.wrapper){
          t.wrapper.classList.add('is-invalid');
        }else{
          s.classList.add('is-invalid');
        }
        note = document.querySelector('[data-client-error="'+key+'"]');
        if(note) note.classList.remove('hidden');
        return true;
      }
      return false;
    }
    function firstInvalidFocus(){
      var el = document.querySelector('.is-invalid, .ts-invalid .ts-control, .ts-wrapper.is-invalid .ts-control, .ts-invalid input');
      if(el){
        try{ el.focus({preventScroll:false}); }catch(e){}
        el.scrollIntoView({behavior:'smooth', block:'center'});
        return;
      }
      var c = document.querySelector('[aria-invalid="true"], .ts-invalid, .ts-wrapper.is-invalid');
      if(c){
        try{ c.focus({preventScroll:false}); }catch(e){}
        c.scrollIntoView({behavior:'smooth', block:'center'});
      }
    }

    form1.addEventListener('submit', function(ev){
      var hasErr = false, i, n;

      n = document.querySelectorAll('.is-invalid');
      for(i=0;i<n.length;i++){ n[i].classList.remove('is-invalid'); }
      n = document.querySelectorAll('.ts-wrapper.is-invalid');
      for(i=0;i<n.length;i++){ n[i].classList.remove('is-invalid'); }
      n = document.querySelectorAll('.invalid-feedback[data-client-error]');
      for(i=0;i<n.length;i++){ n[i].classList.add('hidden'); }
      n = document.querySelectorAll('.ts-invalid');
      for(i=0;i<n.length;i++){ n[i].classList.remove('ts-invalid'); }

      hasErr = markRequiredInput(document.querySelector('input[name="phone"]'),'phone') || hasErr;
      hasErr = markRequiredInput(document.querySelector('input[name="present_address"]'),'present_address') || hasErr;

      hasErr = markRadioGroup('gender') || hasErr;
      hasErr = markRadioGroup('person_type') || hasErr;
      hasErr = markRadioGroup('profession') || hasErr;

      hasErr = reqSelect('division_id','division_id') || hasErr;
      hasErr = reqSelect('district_id','district_id') || hasErr;
      hasErr = reqSelect('upazila_id','upazila_id') || hasErr;
      hasErr = reqSelect('thana_id','thana_id') || hasErr;

      // photo validation respecting existing DB photo
      var tempPhoto = document.getElementById('temp_photo');
      var filePhoto = document.getElementById('photo');
      var existingPhoto = document.getElementById('existing_photo');
      var hasExisting = !!(existingPhoto && existingPhoto.value);
      var needPhoto = !hasExisting &&
                      !(tempPhoto && tempPhoto.value) &&
                      !(filePhoto && filePhoto.files && filePhoto.files.length>0);

      if(needPhoto){
        var noteP = document.querySelector('[data-client-error="photo"]');
        if(noteP) noteP.classList.remove('hidden');
        var nameBox = document.getElementById('photo_name');
        if(nameBox) nameBox.classList.add('is-invalid');
        hasErr = true;
      }

      if(document.getElementById('biz_group')){
        var v = (document.querySelector('input[name="do_you_have_business"]:checked')||{}).value;
        var pt = (document.querySelector('input[name="person_type"]:checked')||{}).value;
        var must = (v==='1' || pt==='B');
        if(must){
          hasErr = reqSelect('business_type_id','business_type_id') || hasErr;
          hasErr = reqSelect('company_establishment_year','company_establishment_year') || hasErr;
          hasErr = markRequiredInput(document.getElementById('company_name'),'company_name') || hasErr;
          hasErr = markRequiredInput(document.querySelector('input[name="company_address"]'),'company_address') || hasErr;
          hasErr = markRequiredInput(document.querySelector('input[name="company_contact_no"]'),'company_contact_no') || hasErr;
          var wrap = document.querySelector('[data-required-radio="turn_over"]');
          if(wrap){
            var checked = document.querySelector('input[name="turn_over"]:checked');
            if(!checked){
              wrap.classList.add('ts-invalid');
              hasErr = true;
            }
          }
        }
      }

      if(hasErr){
        ev.preventDefault();
        firstInvalidFocus();
      }
    });

    var divPref = selDiv && selDiv.getAttribute ? selDiv.getAttribute('data-old-val') : '';
    if(selDiv && (currentVal(selDiv) || divPref)){
      form1._onDivisionChange(currentVal(selDiv) || divPref);
    }
  }
  wireStep1();

  window.addEventListener('pageshow', function(e){
    if(e.persisted){
      try{
        if(form1 && typeof form1._enforceBusinessLock==='function') form1._enforceBusinessLock();
      }catch(_){}
      try{
        if(form1 && typeof form1._onDivisionChange==='function'){
          var d = document.getElementById('division_id');
          var divPref = d && d.getAttribute ? d.getAttribute('data-old-val') : '';
          var cur = (d && d.value) || divPref;
          if(cur){ form1._onDivisionChange(cur); }
        }
      }catch(_){}
    }
  });

  // =========================================================
  // DOB: native date picker + future-date guard
  // =========================================================
  (function(){
    var dob = document.getElementById('date_of_birth');
    var icon = document.getElementById('dob-icon');
    var err = document.getElementById('dob-future-err');

    if(icon && dob){
      icon.addEventListener('click', function(e){
        e.preventDefault();
        if(typeof dob.showPicker === 'function'){
          try{ dob.showPicker(); return; }catch(_){}
        }
        dob.focus();
      });
    }

    if(dob){
      dob.addEventListener('change', function(){
        if(!dob.value){
          if(err) err.style.display='none';
          return;
        }
        var v = dob.value;
        var d = new Date(v+'T00:00:00');
        var today = new Date();
        today.setHours(0,0,0,0);
        if(d > today){
          if(err) err.style.display='block';
          dob.value = '';
        }else{
          if(err) err.style.display='none';
        }
      });
    }
  })();

  // =========================================================
  // Step-2: Optional job "all-or-none" guard + persistent restore
  // =========================================================
  var form2 = document.getElementById('enterprise-client-step2');
  function wireStep2(){
    if(!form2) return;

    // restore Present Job from localStorage (module-scoped key)
    var companySlug = dataOr(root,'companySlug','');
    var regInput = form2.querySelector('input[name="reg"]');
    var regId = regInput ? regInput.value : '';
    var storageKey = (companySlug && regId) ? ('tmx_entc_s2_'+companySlug+'_'+regId) : '';

    function loadStep2FromStorage(){
      if(!storageKey) return;
      try{
        var raw = window.localStorage ? localStorage.getItem(storageKey) : null;
        if(!raw) return;
        var data = JSON.parse(raw);
        if(!data || typeof data!=='object') return;
        ['employer','job_title','department','joining_date'].forEach(function(k){
          var el = form2.querySelector('input[name="'+k+'"]');
          if(!el) return;
          if(!el.value && data[k]){
            el.value = data[k];
          }
        });
      }catch(e){}
    }

    function saveStep2ToStorage(){
      if(!storageKey) return;
      var data = {};
      ['employer','job_title','department','joining_date'].forEach(function(k){
        var el = form2.querySelector('input[name="'+k+'"]');
        data[k] = el ? el.value : '';
      });
      try{
        if(window.localStorage){
          localStorage.setItem(storageKey, JSON.stringify(data));
        }
      }catch(e){}
    }

    loadStep2FromStorage();

    form2.addEventListener('submit', function(ev){
      // snapshot latest values before validation
      saveStep2ToStorage();

      var fEmp = (form2.querySelector('input[name="employer"]').value||'').trim();
      var fJob = (form2.querySelector('input[name="job_title"]').value||'').trim();
      var fDep = (form2.querySelector('input[name="department"]').value||'').trim();
      var fJoin= (form2.querySelector('input[name="joining_date"]').value||'').trim();
      var allEmpty = (!fEmp && !fJob && !fDep && !fJoin);
      if(allEmpty){ return; }
      var errs = [];
      if(!fEmp){ errs.push('input[name="employer"]'); }
      if(!fJob){ errs.push('input[name="job_title"]'); }
      if(!fDep){ errs.push('input[name="department"]'); }
      if(!fJoin){ errs.push('input[name="joining_date"]'); }
      if(errs.length){
        ev.preventDefault();
        errs.forEach(function(sel){
          var el = form2.querySelector(sel);
          if(el){ el.classList.add('is-invalid'); }
        });
        var focusEl = form2.querySelector(errs[0]);
        if(focusEl){
          try{ focusEl.focus({preventScroll:false}); }catch(e){}
          focusEl.scrollIntoView({behavior:'smooth', block:'center'});
        }
      }
    });
  }
  wireStep2();

});
</script>
@endverbatim
@endpush
