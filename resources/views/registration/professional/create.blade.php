{{-- resources/views/registration/professional/create.blade.php --}}
@extends('backend.layouts.master')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/buttons.css') }}">
<style>
  :root{
    --ink:#2b2ba1cf; --sub:#6b7280; --iconbox:#eef1ff;
    --card-grad-1:#faf7ff; --card-grad-2:#f4f7ff; --card-border:#e6e7f2; --card-shadow:0 12px 28px rgba(31,42,68,.08);
    --rec-grad-1:#f6f9ff; --rec-grad-2:#f9f6ff; --rec-border:#dbe2ff;
    --static-bg:#f7f6f9; --static-br:#e6e1f0;
    --scroll-bg:#fbfbfe; --scroll-br:#e7e9f5;
    --pill-ok:#dcfce7; --pill-ok-br:#bbf7d0; --pill-bad:#fee2e2; --pill-bad-br:#fecaca;
    --label: var(--ink); --soft-bg:#f8fafc; --rec-bg:#f6f9ff;
  }
  .tmx-title{color:var(--ink);} .tmx-label{color:var(--ink);font-weight:700;} .tmx-muted{color:var(--sub);}
  .tmx-card{background:linear-gradient(180deg,var(--card-grad-1),var(--card-grad-2));border:1px solid var(--card-border);border-radius:14px;box-shadow:var(--card-shadow);overflow:hidden;}
  .tmx-card-hd{padding:14px 16px;border-bottom:1px solid var(--card-border);display:flex;align-items:center;gap:10px;background:linear-gradient(180deg,rgba(255,255,255,.6),rgba(255,255,255,.1));backdrop-filter:saturate(120%) blur(2px);}
  .tmx-card-bd{padding:16px;}
  .tmx-iconbox{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;background:var(--iconbox);border:1px solid var(--card-border);border-radius:10px;}
  .tmx-card-hd .h-name{font-weight:800;color:var(--ink);} .tmx-card-hd .h-note{margin-left:8px;font-size:.95rem;color:#64748b;}
  .tmx-static{background:var(--static-bg);border:1px solid var(--static-br);border-radius:10px;padding:10px 12px;display:flex;align-items:center;gap:8px;}
  .scroll-box{border:1px solid var(--scroll-br);border-radius:12px;padding:16px 18px;max-height:260px;overflow:auto;background:linear-gradient(180deg,#fff 0%,var(--scroll-bg) 100%);}
  .scroll-box .form-check{padding:12px 8px;border-bottom:1px dashed #eef2f7;display:flex;align-items:center;gap:12px;}
  .scroll-box .form-check:last-child{border-bottom:none;}
  .rec{background:linear-gradient(180deg,var(--rec-grad-1),var(--rec-grad-2));border:1px solid var(--rec-border);border-radius:14px;padding:12px;margin-bottom:10px;box-shadow:0 6px 14px rgba(31,42,68,.05);}
  .rec .rec-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:8px;}
  .date-wrap{position:relative;} .date-wrap input.form-control{padding-right:2.25rem;}
  .date-wrap .fa-regular.fa-calendar-days{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#2563eb;cursor:pointer;}
  .tmx-req{color:#dc2626;font-size:.875rem;margin-top:6px;}
  .c-row{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px dashed #e5e7eb;} .c-row:last-child{border-bottom:none;}
  .c-pill{padding:4px 10px;border-radius:999px;font-weight:700;font-size:.85rem;} .c-bad{background:var(--pill-bad);color:#991b1b;border:1px solid var(--pill-bad-br);} .c-ok{background:var(--pill-ok);color:#166534;border:1px solid var(--pill-ok-br);}
  .preview{display:flex;gap:12px;align-items:center;margin-top:6px;flex-wrap:wrap}
  .preview .thumb{position:relative;display:inline-block}
  .preview img{width:100%;max-width:220px;max-height:160px;object-fit:contain;border-radius:10px;border:1px solid var(--card-border);cursor:zoom-in;background:#fff;box-shadow:0 8px 16px rgba(31,42,68,.06);}
  .preview .rmv{position:absolute;top:6px;right:6px;background:rgba(0,0,0,.6);color:#fff;border:none;border-radius:6px;font-size:.75rem;padding:2px 6px;cursor:pointer}
  .btn-x, .btn-x:hover, .btn-x:focus, .btn-x:active { background: var(--bg) !important; color: var(--fg) !important; } .btn, .btn:hover, .btn:focus, .btn:active { text-decoration:none; }
  .v-alert{display:none;border:1px solid #fecaca;background:#fef2f2;color:#991b1b;border-radius:12px;padding:10px 12px;margin-bottom:12px;box-shadow:0 10px 20px rgba(153,27,27,.05);}
  @media (max-width:576px){ .tmx-card-bd{padding:12px} .tmx-card-hd{padding:12px} }
</style>
@endpush

@section('content')
@php
  $companyParam = $company->slug ?? $company->id;
  $nidLengths   = config('registration.allowed_nid_lengths', [10,13,17]);
  $temps        = $tempUploads ?? session('_temp_uploads', []);
  $maxKB        = (int) config('registration.max_image_kb', 1024);
@endphp

<div class="container py-3">
  <h1 class="h4 tmx-title mb-3 d-flex justify-content-between align-items-center">
    <span><i class="fa-solid fa-user-tie me-2"></i> Professional Registration — {{ $company->name }}</span>
    <span class="d-flex gap-2">
      <button type="button" id="btnGoDown" class="btn btn-x btn-add"><i class="fa-solid fa-angles-down"></i> Go Down</button>
      <a href="{{ url()->previous() }}" class="btn btn-x btn-view"><i class="fa-solid fa-arrow-left-long"></i> Back</a>
    </span>
  </h1>

  @if ($errors->any())
    <div class="alert alert-danger">
      <strong>There were problems with your submission:</strong>
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div id="valAlert" class="v-alert">
    <i class="fa-solid fa-circle-exclamation me-1"></i>
    <strong>Some fields are missing.</strong> Please correct the highlighted inputs below. You’ll be scrolled to the first issue.
  </div>

  <form id="proCreateForm" method="POST"
        action="{{ route('registration.professional.store', ['company' => $companyParam]) }}"
        enctype="multipart/form-data" novalidate autocomplete="off">
    @csrf

    {{-- carry temp uploads across submits --}}
    <input type="hidden" name="temp_photo"             value="{{ old('temp_photo', $temps['temp_photo'] ?? '') }}">
    <input type="hidden" name="temp_nid_front"         value="{{ old('temp_nid_front', $temps['temp_nid_front'] ?? '') }}">
    <input type="hidden" name="temp_nid_back"          value="{{ old('temp_nid_back', $temps['temp_nid_back'] ?? '') }}">
    <input type="hidden" name="temp_birth_certificate" value="{{ old('temp_birth_certificate', $temps['temp_birth_certificate'] ?? '') }}">

    {{-- ===== Card: Personal Info ===== --}}
    <div class="tmx-card mb-3" id="card-personal">
      <div class="tmx-card-hd">
        <div class="tmx-iconbox"><i class="fa-solid fa-id-card-clip"></i></div>
        <div class="h-name">Personal Info</div>
        <div class="h-note">Mandatory: provide accurate details and documents.</div>
      </div>

      <div class="tmx-card-bd">
        <div class="row g-3 mb-1">
          <div class="col-12 col-lg-4">
            <label class="tmx-label d-block mb-1">Full Name (locked)</label>
            <div class="tmx-static"><i class="fa-solid fa-user"></i><span>{{ $ctx['name'] }}</span></div>
            <input type="hidden" name="full_name" value="{{ old('full_name', $ctx['name']) }}">
          </div>
          <div class="col-12 col-lg-4">
            <label class="tmx-label d-block mb-1">Registration Type</label>
            <div class="tmx-static"><i class="fa-solid fa-briefcase"></i><span>professional</span></div>
          </div>
          <div class="col-12 col-lg-4">
            <label class="tmx-label d-block mb-1">Official Email (locked)</label>
            <div class="tmx-static"><i class="fa-solid fa-envelope"></i><span>{{ $ctx['email'] }}</span></div>
            <input type="hidden" name="email" value="{{ old('email', $ctx['email']) }}">
          </div>
        </div>

        <div class="row g-3">
          <div class="col-12 col-md-4">
            <label class="tmx-label d-block mb-1">Gender *</label>
            <div class="d-flex flex-wrap gap-2">
              @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $k => $v)
                <label class="form-check form-check-inline">
                  <input class="form-check-input @error('gender') is-invalid @enderror" type="radio" name="gender"
                         value="{{ $k }}" {{ old('gender') === $k ? 'checked' : '' }}>
                  <span class="form-check-label">{{ $v }}</span>
                </label>
              @endforeach
            </div>
            @error('gender') <div class="tmx-req srv-err">{{ $message }}</div> @enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="tmx-label">Date of Birth *</label>
            <div class="date-wrap">
              <input type="text" id="dob_field" name="date_of_birth"
                     class="form-control @error('date_of_birth') is-invalid @enderror"
                     value="{{ old('date_of_birth') }}" placeholder="YYYY-MM-DD" inputmode="none" autocomplete="off">
              <i class="fa-regular fa-calendar-days" id="dob_icon" title="Open calendar"></i>
            </div>
            @error('date_of_birth') <div class="tmx-req srv-err">{{ $message }}</div> @enderror
          </div>

          <div class="col-12 col-md-4">
            <label class="tmx-label">Mobile (BD) *</label>
            <input type="text" name="fake_phone" autocomplete="off" style="display:none">
            <input type="text" class="form-control @error('phone') is-invalid @enderror" name="phone"
                   value="{{ old('phone') }}" placeholder="01XXXXXXXXX" inputmode="numeric" autocomplete="tel">
            @error('phone') <div class="tmx-req srv-err">{{ $message }}</div> @enderror
          </div>

          <div class="col-12 col-lg-3">
            <label class="tmx-label">Division *</label>
            <select id="division_id" name="division_id" class="form-select @error('division_id') is-invalid @enderror">
              <option value="" selected hidden>— Select —</option>
              @foreach($divisions as $d)
                <option value="{{ $d->id }}" {{ old('division_id') == $d->id ? 'selected' : '' }}>
                  {{ $d->short_code }} — {{ $d->name }}
                </option>
              @endforeach
            </select>
            @error('division_id') <div class="tmx-req srv-err">{{ $message }}</div> @enderror
          </div>
          <div class="col-12 col-lg-3">
            <label class="tmx-label">District *</label>
            <select id="district_id" name="district_id" class="form-select @error('district_id') is-invalid @enderror">
              <option value="" selected hidden>— Select —</option>
            </select>
            @error('district_id') <div class="tmx-req srv-err">{{ $message }}</div> @enderror
          </div>
          <div class="col-12 col-lg-3">
            <label class="tmx-label">Upazila *</label>
            <select id="upazila_id" name="upazila_id" class="form-select @error('upazila_id') is-invalid @enderror">
              <option value="" selected hidden>— Select —</option>
            </select>
            @error('upazila_id') <div class="tmx-req srv-err">{{ $message }}</div> @enderror
          </div>
          <div class="col-12 col-lg-3">
            <label class="tmx-label">Thana *</label>
            <select id="thana_id" name="thana_id" class="form-select @error('thana_id') is-invalid @enderror">
              <option value="" selected hidden>— Select —</option>
            </select>
            @error('thana_id') <div class="tmx-req srv-err">{{ $message }}</div> @enderror
          </div>

          <div class="col-12 col-md-6">
            <label class="tmx-label d-block mb-1">Person Type *</label>
            @php $types = ['J'=>'Service Holder','B'=>'Business Person','H'=>'Housewife','S'=>'Student','P'=>'Professional','O'=>'Other']; @endphp
            <div class="d-flex flex-wrap gap-2">
              @foreach ($types as $k => $v)
                <label class="form-check form-check-inline">
                  <input class="form-check-input @error('person_type') is-invalid @enderror" type="radio"
                         name="person_type" value="{{ $k }}" {{ old('person_type') == $k ? 'checked' : '' }}>
                  <span class="form-check-label">{{ $v }}</span>
                </label>
              @endforeach
            </div>
            @error('person_type') <div class="tmx-req srv-err">{{ $message }}</div> @enderror
          </div>

          <div class="col-12 col-md-6">
            <label class="tmx-label d-block mb-1">Profession *</label>
            <div class="d-flex flex-wrap gap-3">
              @foreach ($professions as $p)
                <label class="form-check form-check-inline">
                  <input class="form-check-input @error('profession') is-invalid @enderror" type="radio"
                         name="profession" value="{{ $p->id }}" {{ old('profession') == $p->id ? 'checked' : '' }}>
                  <span class="form-check-label">{{ $p->profession }}</span>
                </label>
              @endforeach
            </div>
            @error('profession') <div class="tmx-req srv-err">{{ $message }}</div> @enderror
            <div id="prof_msg" class="tmx-req client-msg" style="display:none;">Please select a profession.</div>
          </div>

          <div class="col-12 col-md-12">
            <label class="tmx-label">Present Address *</label>
            <input type="text" class="form-control @error('present_address') is-invalid @enderror"
                   name="present_address" value="{{ old('present_address') }}" autocomplete="off">
            @error('present_address') <div class="tmx-req srv-err">{{ $message }}</div> @enderror
          </div>

          {{-- Photo / NID / Birth certificate --}}
          <div class="col-12 col-lg-4">
            <label class="tmx-label">Photo *</label>
            <input type="file" name="photo" id="photo" class="form-control @error('photo') is-invalid @enderror"
                   accept=".jpg,.jpeg,.png,.webp,.gif,.jfif,.bmp">
            <div class="form-text">Allowed: jpg, jpeg, png, webp, gif, jfif, bmp. Max: {{ $maxKB }}KB.</div>
            <div class="preview" id="preview_photo" @if(empty(old('temp_photo', $temps['temp_photo'] ?? ''))) hidden @endif>
              @php $p = old('temp_photo', $temps['temp_photo'] ?? ''); @endphp
              @if($p)
                <span class="thumb">
                  <img src="{{ $p }}" alt="photo">
                  <button type="button" class="rmv" data-clear="photo">Remove</button>
                </span>
              @endif
            </div>
            @error('photo') <div class="tmx-req srv-err">{{ $message }}</div> @enderror
          </div>

          <div class="col-12 col-lg-4">
            <label class="tmx-label">NID Number (optional)</label>
            <input type="text" name="nid_number" id="nid_number"
                   class="form-control @error('nid_number') is-invalid @enderror" value="{{ old('nid_number') }}"
                   placeholder="If provided, front & back images required" autocomplete="off">
            <div id="nid_len_msg" class="tmx-req" style="display:none;">NID length incorrect.</div>
            <div class="row g-2 mt-1">
              <div class="col-6">
                <input type="file" name="nid_front" id="nid_front"
                       class="form-control @error('nid_front') is-invalid @enderror"
                       accept=".jpg,.jpeg,.png,.webp,.gif,.jfif,.bmp">
                <div class="form-text">NID Front (≤{{ $maxKB }}KB)</div>
                <div class="preview" id="preview_nid_front" @if(empty(old('temp_nid_front', $temps['temp_nid_front'] ?? ''))) hidden @endif>
                  @php $nf = old('temp_nid_front', $temps['temp_nid_front'] ?? ''); @endphp
                  @if($nf)
                    <span class="thumb">
                      <img src="{{ $nf }}" alt="nid_front">
                      <button type="button" class="rmv" data-clear="nid_front">Remove</button>
                    </span>
                  @endif
                </div>
                @error('nid_front') <div class="tmx-req srv-err">{{ $message }}</div> @enderror
              </div>
              <div class="col-6">
                <input type="file" name="nid_back" id="nid_back"
                       class="form-control @error('nid_back') is-invalid @enderror"
                       accept=".jpg,.jpeg,.png,.webp,.gif,.jfif,.bmp">
                <div class="form-text">NID Back (≤{{ $maxKB }}KB)</div>
                <div class="preview" id="preview_nid_back" @if(empty(old('temp_nid_back', $temps['temp_nid_back'] ?? ''))) hidden @endif>
                  @php $nb = old('temp_nid_back', $temps['temp_nid_back'] ?? ''); @endphp
                  @if($nb)
                    <span class="thumb">
                      <img src="{{ $nb }}" alt="nid_back">
                      <button type="button" class="rmv" data-clear="nid_back">Remove</button>
                    </span>
                  @endif
                </div>
                @error('nid_back') <div class="tmx-req srv-err">{{ $message }}</div> @enderror
              </div>
            </div>
            <div class="form-text mt-1">Accepted NID lengths: {{ implode(', ', $nidLengths) }}.</div>
          </div>

          <div class="col-12 col-lg-4">
            <label class="tmx-label">Birth Certificate (required if no NID)</label>
            <input type="file" name="birth_certificate" id="birth_certificate"
                   class="form-control @error('birth_certificate') is-invalid @enderror"
                   accept=".jpg,.jpeg,.png,.webp,.gif,.jfif,.bmp">
            <div class="form-text">If NID not provided, upload birth certificate (≤{{ $maxKB }}KB).</div>
            <div class="preview" id="preview_birth" @if(empty(old('temp_birth_certificate', $temps['temp_birth_certificate'] ?? ''))) hidden @endif>
              @php $bc = old('temp_birth_certificate', $temps['temp_birth_certificate'] ?? ''); @endphp
              @if($bc)
                <span class="thumb">
                  <img src="{{ $bc }}" alt="birth">
                  <button type="button" class="rmv" data-clear="birth_certificate">Remove</button>
                </span>
              @endif
            </div>
            @error('birth_certificate') <div class="tmx-req srv-err">{{ $message }}</div> @enderror
          </div>
        </div>
      </div>
    </div>

    {{-- ===== Educational Background (required) ===== --}}
    <div class="tmx-card mb-3" id="card-edu">
      <div class="tmx-card-hd">
        <div class="tmx-iconbox"><i class="fa-solid fa-user-graduate"></i></div>
        <div class="h-name">Educational Background</div>
        <div class="h-note">Required: add at least one complete education record.</div>
      </div>
      <div class="tmx-card-bd">
        <div id="eduRows"></div>
        @error('edu.0.degree_id') <div class="tmx-req srv-err mt-1">{{ $message }}</div> @enderror
      </div>
    </div>

    {{-- ===== Job Experience (optional) ===== --}}
    <div class="tmx-card mb-3" id="card-job">
      <div class="tmx-card-hd">
        <div class="tmx-iconbox"><i class="fa-solid fa-briefcase"></i></div>
        <div class="h-name">Job Experience</div>
        <div class="h-note">Optional: if you add a row, complete all marked fields.</div>
      </div>
      <div class="tmx-card-bd"><div id="jobRows"></div></div>
    </div>

    {{-- ===== Expertise on Softwares (optional) ===== --}}
    <div class="tmx-card mb-3" id="card-soft">
      <div class="tmx-card-hd">
        <div class="tmx-iconbox"><i class="fa-solid fa-laptop-code"></i></div>
        <div class="h-name">Select your Expertise on Softwares</div>
        <div class="h-note">Optional: if selected, provide your years of experience.</div>
      </div>
      <div class="tmx-card-bd">
        @php
          $softGeneral = $errors->first('software_ids') ?: collect($errors->getMessages())
            ->first(function($v,$k){ return str_starts_with($k,'software_years.'); });
        @endphp
        @if($softGeneral)
          <div class="tmx-req srv-err mb-2">{{ is_array($softGeneral)?($softGeneral[0]??''): $softGeneral }}</div>
        @endif

        <div class="tmx-muted mb-2">For each checked item, fill <em>Years of Experience</em> (e.g., 2.5).</div>
        <div class="scroll-box">
          @php
            $softwares = $softwares ?? collect();
            $oldSoftSel   = collect(old('software_ids', []))->map(fn($v)=>(int)$v)->all();
            $oldSoftYears = old('software_years', []);
          @endphp
          @forelse($softwares as $s)
            @php $isChecked = in_array($s->id, $oldSoftSel); $yrs = $oldSoftYears[$s->id] ?? ''; @endphp
            <div class="form-check soft-inline">
              <input class="form-check-input js-soft-toggle" type="checkbox"
                     name="software_ids[]" value="{{ $s->id }}"
                     id="sw_{{ $s->id }}" data-years="#yrs_{{ $s->id }}"
                     {{ $isChecked ? 'checked' : '' }}>
              <label class="form-check-label" for="sw_{{ $s->id }}">{{ $s->software_name }}</label>
              <div class="soft-years" style="width:160px">
                <input type="number" step="0.1" min="0" inputmode="decimal"
                       name="software_years[{{ $s->id }}]" id="yrs_{{ $s->id }}"
                       class="form-control @error('software_years.' . $s->id) is-invalid @enderror"
                       value="{{ $yrs }}" placeholder="Years of Experience" {{ $isChecked ? '' : 'disabled' }}>
                @error('software_years.' . $s->id) <div class="tmx-req srv-err">{{ $message }}</div> @enderror
              </div>
            </div>
          @empty
            <div class="tmx-muted">No software configured.</div>
          @endforelse
        </div>
      </div>
    </div>

    {{-- ===== Skills (required) ===== --}}
    <div class="tmx-card mb-3" id="card-skills">
      <div class="tmx-card-hd">
        <div class="tmx-iconbox"><i class="fa-solid fa-screwdriver-wrench"></i></div>
        <div class="h-name">Select Your Skills</div>
        <div class="h-note">Required: select at least one skill.</div>
      </div>
      <div class="tmx-card-bd">
        <div class="tmx-muted mb-2">Select at least one relevant skill.</div>
        <div class="scroll-box">
          @php $oldSkills = collect(old('skill_ids', []))->map(fn($v)=>(int)$v)->all(); @endphp
          @forelse($skills as $sk)
            <div class="form-check">
              <input class="form-check-input @error('skill_ids') is-invalid @enderror" type="checkbox" name="skill_ids[]"
                     value="{{ $sk->id }}" id="sk_{{ $sk->id }}" {{ in_array($sk->id, $oldSkills) ? 'checked' : '' }}>
              <label class="form-check-label" for="sk_{{ $sk->id }}">{{ $sk->skill }}</label>
            </div>
          @empty
            <div class="tmx-muted">No skills configured.</div>
          @endforelse
        </div>
        @error('skill_ids') <div class="tmx-req srv-err mt-1">{{ $message }}</div> @enderror
      </div>
    </div>

    {{-- ===== Preferred Areas (optional) ===== --}}
    <div class="tmx-card mb-3" id="card-areas">
      <div class="tmx-card-hd">
        <div class="tmx-iconbox"><i class="fa-solid fa-diagram-project"></i></div>
        <div class="h-name">Select Areas where you may contribute to this company</div>
        <div class="h-note">Optional: select if applicable.</div>
      </div>
      <div class="tmx-card-bd">
        <div class="tmx-muted mb-2">This section is optional.</div>
        <div class="scroll-box">
          @php $oldTasks = collect(old('task_ids', []))->map(fn($v)=>(int)$v)->all(); @endphp
          @forelse($tasks as $t)
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="task_ids[]"
                     value="{{ $t->Task_Param_ID }}" id="tk_{{ $t->Task_Param_ID }}" {{ in_array($t->Task_Param_ID, $oldTasks) ? 'checked' : '' }}>
              <label class="form-check-label" for="tk_{{ $t->Task_Param_ID }}">{{ $t->Task_Param_Name }}</label>
            </div>
          @empty
            <div class="tmx-muted">No tasks configured.</div>
          @endforelse
        </div>
      </div>
    </div>

    {{-- ===== Future Training (optional) ===== --}}
    <div class="tmx-card mb-3" id="card-train">
      <div class="tmx-card-hd">
        <div class="tmx-iconbox"><i class="fa-solid fa-chalkboard-user"></i></div>
        <div class="h-name">Future Training Required</div>
        <div class="h-note">Optional: if you add a row, category and training name are mandatory.</div>
      </div>
      <div class="tmx-card-bd"><div id="trainRows"></div></div>
    </div>

    {{-- ===== Confirmation ===== --}}
    <div class="tmx-card mb-4" id="card-confirm">
      <div class="tmx-card-hd">
        <div class="tmx-iconbox"><i class="fa-solid fa-clipboard-check"></i></div>
        <div class="h-name">Confirmation</div>
        <div class="h-note">Review your inputs. Nothing will be saved unless all validations pass.</div>
      </div>
      <div class="tmx-card-bd">
        <div class="c-row"><div>Personal Info</div><div id="c-personal" class="c-pill c-bad">Not validated</div></div>
        <div class="c-row"><div>Educational Background</div><div id="c-edu" class="c-pill c-bad">Not validated</div></div>
        <div class="c-row"><div>Job Experience (optional)</div><div id="c-job" class="c-pill c-bad">Not validated</div></div>
        <div class="c-row"><div>Software Expertise (optional)</div><div id="c-soft" class="c-pill c-bad">Not validated</div></div>
        <div class="c-row"><div>Skills (required)</div><div id="c-skills" class="c-pill c-bad">Not validated</div></div>
        <div class="c-row"><div>Preferred Areas (optional)</div><div id="c-areas" class="c-pill c-ok">Optional</div></div>
        <div class="c-row"><div>Future Training (optional)</div><div id="c-train" class="c-pill c-bad">Not validated</div></div>

        <div class="d-flex flex-wrap gap-2 justify-content-end mt-3">
          <a href="{{ url()->previous() }}" class="btn btn-x btn-view"><i class="fa-solid fa-arrow-left-long"></i> Back</a>
          <button type="button" id="btnTop" class="btn btn-x btn-add"><i class="fa-solid fa-arrow-up"></i> Return to Top</button>
          <button type="button" id="btnCancel" class="btn btn-x btn-cancel"><i class="fa-solid fa-broom"></i> Cancel (Clear Unsaved Data)</button>
          <button type="button" id="btnValidateSave" class="btn btn-x btn-save"><i class="fa-solid fa-paper-plane"></i> Validate &amp; Save All</button>
        </div>
      </div>
    </div>

  </form>
</div>

<div id="tmx-date-overlay" style="position:fixed;inset:0;background:rgba(0,0,0,.5);display:none;justify-content:center;align-items:center;z-index:1050">
  <div style="background:#fff;border-radius:12px;max-width:520px;width:95%;padding:12px;">
    <div id="calendar-container-generic"></div>
    <div class="text-end mt-2"><button type="button" class="btn btn-x btn-cancel" id="close_calendar_generic">Close</button></div>
  </div>
</div>

@include('backend.layouts.partials.zoom-modal')
@endsection

@push('scripts')
<script>
(function () {
  const form       = document.getElementById('proCreateForm');
  const companySeg = @json($companyParam);
  const NID_ALLOWED= @json($nidLengths);
  const base       = `/registration/${companySeg}/professional`;
  const MAX_KB     = @json($maxKB);

  const SRV_ERR_SK = @json($errors->has('skill_ids'));

  async function fetchJSON(url){try{const r=await fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest'}});return r.ok?r.json():[]}catch(e){return[]}}
  function pad(n){return String(n).padStart(2,'0')}

  const tsOpts={maxItems:1,create:false,allowEmptyOption:true,plugins:['dropdown_input'],placeholder:'— Select —',dropdownParent:'body'};
  function tsInit(el){if(!el)return null;if(el.tomselect)return el.tomselect;try{return(el.tomselect=new TomSelect(el,tsOpts))}catch(e){return null}}
  function tsEnsure(el){return el.tomselect||tsInit(el)}
  function tsClearAndSet(el,items,pre){const ts=tsEnsure(el);if(ts){ts.clear(true);ts.clearOptions();ts.addOptions(items||[]);ts.refreshOptions(false);if(pre)ts.setValue(String(pre),true);}else{el.innerHTML='<option value="" selected hidden>— Select —</option>';(items||[]).forEach(o=>{el.insertAdjacentHTML('beforeend',`<option value="${o.value}">${o.text}</option>`)});if(pre)el.value=pre;}}
  function tsDisable(el,dis=true){const ts=tsEnsure(el);if(ts)dis?ts.disable():ts.enable();else el.disabled=!!dis}
  function tsGetValue(el){const ts=el&&el.tomselect;if(ts){const v=ts.getValue();return Array.isArray(v)?(v[0]||''):(v||'')}return el?(el.value||''):''}
  function tsSetValue(el,val){const ts=tsEnsure(el);if(ts){try{ts.setValue(String(val||''))}catch(e){} }else if(el){el.value=val||''}}

  function openZoom(url){ if(window.tmxOpenZoom){ window.tmxOpenZoom(url); if(window.tmxSetZoom) window.tmxSetZoom(35); return;} window.open(url,'_blank'); }

  document.addEventListener('click', function (e) {
    const img = e.target.closest('.preview img');
    if (img){ e.preventDefault(); openZoom(img.src); return; }
    const add = e.target.closest('button[data-add-after]');
    if (add){
      const type=add.getAttribute('data-add-after');
      if(type==='edu') addEduAfter(document.querySelector('[data-edu-idx]:last-of-type'));
      if(type==='job'){ const last=document.querySelector('[data-job-idx]:last-of-type'); addJobAfter(last); }
      if(type==='train') addTrainAfter(document.querySelector('[data-trn-idx]:last-of-type'));
      return;
    }
    const del = e.target.closest('button[data-remove]');
    if (del){
      const type=del.getAttribute('data-remove');
      const selector = type==='edu'?'[data-edu-idx]':(type==='job'?'[data-job-idx]':'[data-trn-idx]');
      const block=del.closest(selector); if(block) block.remove();
      return;
    }
    const clr = e.target.closest('button.rmv[data-clear]');
    if (clr){
      const field = clr.getAttribute('data-clear');
      const hidden = form.querySelector(`input[name="temp_${field}"]`);
      if (hidden) hidden.value='';
      const file = document.getElementById(field);
      if (file) file.value='';
      const box = document.getElementById(`preview_${field==='birth_certificate'?'birth':field}`);
      if (box){ box.innerHTML=''; box.hidden=true; }
      return;
    }
  });

  document.addEventListener('change', function (e) {
    const sel = e.target.closest('select[name^="job"][name$="[is_present]"]');
    if (sel) {
      const box = sel.closest('[data-job-idx]');
      const end = box.querySelector('input[name^="job"][name$="[end_date]"]');
      if (tsGetValue(sel) === 'Y'){ end.value=''; end.setAttribute('disabled','disabled'); } else { end.removeAttribute('disabled'); }
    }
  });

  (function mountDOB(){
    const dobIn=document.getElementById('dob_field'), dobIc=document.getElementById('dob_icon');
    const wrap=document.createElement('div'); wrap.style.position='fixed';wrap.style.inset='0';wrap.style.background='rgba(0,0,0,.5)';wrap.style.display='none';wrap.style.justifyContent='center';wrap.style.alignItems='center';wrap.style.zIndex='1050';
    wrap.innerHTML='<div style="background:#fff;border-radius:12px;max-width:480px;width:95%;padding:12px;"><div id="calendar-container-dob"></div><div class="text-end mt-2"><button type="button" class="btn btn-x btn-cancel" id="close_calendar_dob">Close</button></div></div>';
    document.body.appendChild(wrap);
    dobIc.addEventListener('click',()=>wrap.style.display='flex');
    wrap.querySelector('#close_calendar_dob').addEventListener('click',()=>wrap.style.display='none');
    if(typeof DatePicker==='function'){ try{
      const picker=new DatePicker('#calendar-container-dob','#dob_field');
      picker.onSelect=function(dateISO){ dobIn.value=dateISO; wrap.style.display='none'; };
    }catch(e){} }
  })();

  const genericOverlay = document.getElementById('tmx-date-overlay');
  const genericClose   = document.getElementById('close_calendar_generic');
  let activeTargetInput = null;
  if (genericClose) genericClose.addEventListener('click', ()=>{ genericOverlay.style.display='none'; activeTargetInput=null; });
  function ensureId(el){ if(!el.id){ el.dataset.tmpId='dp_target_'+Date.now()+'_'+Math.floor(Math.random()*1000); el.id=el.dataset.tmpId; } return el.id; }
  function mountDatePickerForInput(inputEl){
    if(!inputEl) return;
    const icon = inputEl.parentElement.querySelector('.fa-regular.fa-calendar-days');
    if(!icon || icon._bound) return;
    icon._bound = true;
    icon.addEventListener('click', ()=>{
      activeTargetInput = inputEl;
      const targetId = ensureId(inputEl);
      genericOverlay.style.display = 'flex';
      if (typeof DatePicker==='function'){
        try{
          const host = document.getElementById('calendar-container-generic');
          if (host) host.innerHTML = '';
          const picker = new DatePicker('#calendar-container-generic', '#'+targetId);
          picker.onSelect = function(dateISO){
            if(activeTargetInput){ activeTargetInput.value = dateISO; }
            genericOverlay.style.display='none'; activeTargetInput=null;
          };
        }catch(e){}
      }
    });
  }
  function mountJobCalendars(scope){ (scope||document).querySelectorAll('.tmx-date').forEach(mountDatePickerForInput); }

  const divisionEl=document.getElementById('division_id'), districtEl=document.getElementById('district_id'), upazilaEl=document.getElementById('upazila_id'), thanaEl=document.getElementById('thana_id');
  tsInit(divisionEl); tsInit(districtEl); tsInit(upazilaEl); tsInit(thanaEl);

  async function loadDistricts(divisionId,pre=null){
    tsClearAndSet(districtEl,[]); tsClearAndSet(upazilaEl,[]); tsClearAndSet(thanaEl,[]);
    if(!divisionId){ tsDisable(districtEl,true); tsDisable(upazilaEl,true); tsDisable(thanaEl,true); return;}
    tsDisable(districtEl,true);
    const rows=await fetchJSON(`${base}/api/geo/districts?division_id=${encodeURIComponent(divisionId)}`);
    const items=(rows||[]).map(r=>({value:String(r.id),text:`${r.short_code} — ${r.name}`}));
    tsClearAndSet(districtEl,items,pre); tsDisable(districtEl,false); tsDisable(upazilaEl,true); tsDisable(thanaEl,true);
  }
  async function loadUpazilas(districtId,pre=null){
    tsClearAndSet(upazilaEl,[]); tsClearAndSet(thanaEl,[]);
    if(!districtId){ tsDisable(upazilaEl,true); tsDisable(thanaEl,true); return;}
    tsDisable(upazilaEl,true);
    const rows=await fetchJSON(`${base}/api/geo/upazilas?district_id=${encodeURIComponent(districtId)}`);
    const items=(rows||[]).map(r=>({value:String(r.id),text:`${r.short_code} — ${r.name}`}));
    tsClearAndSet(upazilaEl,items,pre); tsDisable(upazilaEl,false); tsDisable(thanaEl,true);
  }
  async function loadThanas(districtId,pre=null){
    tsClearAndSet(thanaEl,[]);
    if(!districtId){ tsDisable(thanaEl,true); return;}
    tsDisable(thanaEl,true);
    const rows=await fetchJSON(`${base}/api/geo/thanas?district_id=${encodeURIComponent(districtId)}`);
    const items=(rows||[]).map(r=>({value:String(r.id),text:(r.short_code?`${r.short_code} — `:'')+r.name}));
    tsClearAndSet(thanaEl,items,pre); tsDisable(thanaEl,false);
  }
  divisionEl.addEventListener('change',async ()=>{await loadDistricts(tsGetValue(divisionEl));});
  districtEl.addEventListener('change',async ()=>{const d=tsGetValue(districtEl); await loadUpazilas(d); await loadThanas(d);});
  (async function restoreGeo(){
    const oldDivision=@json(old('division_id')), oldDistrict=@json(old('district_id')), oldUpazila=@json(old('upazila_id')), oldThana=@json(old('thana_id'));
    if(oldDivision) tsSetValue(divisionEl,oldDivision);
    await loadDistricts(oldDivision,oldDistrict||null);
    if(oldDistrict){ await loadUpazilas(oldDistrict,oldUpazila||null); await loadThanas(oldDistrict,oldThana||null); }
  })();

  const eduWrap=document.getElementById('eduRows'), jobWrap=document.getElementById('jobRows'), trnWrap=document.getElementById('trainRows');
  function eduRowHtml(idx){
    const opt=`@foreach($degrees as $d)<option value="{{ $d->id }}">{{ $d->short_code }} — {{ $d->name }}</option>@endforeach`;
    const resOpt=`<option value="" hidden>— Select —</option><option value="GPA">GPA</option><option value="CGPA">CGPA</option><option value="Division">Division</option><option value="Class">Class</option><option value="Percentage">Percentage</option>`;
    return `
      <div class="rec" data-edu-idx="${idx}">
        <div class="row g-2">
          <div class="col-12 col-lg-3"><label class="tmx-label">Degree *</label>
            <select name="edu[${idx}][degree_id]" class="form-select tmx-ts-edu"><option value="" hidden>— Select —</option>${opt}</select>
          </div>
          <div class="col-12 col-lg-3"><label class="tmx-label">Institution *</label><input type="text" name="edu[${idx}][institution]" class="form-control"></div>
          <div class="col-6 col-lg-2"><label class="tmx-label">Passing Year *</label><input type="text" name="edu[${idx}][passing_year]" class="form-control" placeholder="YYYY" maxlength="4"></div>
          <div class="col-6 col-lg-2"><label class="tmx-label">Result Type *</label><select name="edu[${idx}][result_type]" class="form-select tmx-ts-single">${resOpt}</select></div>
          <div class="col-6 col-lg-1"><label class="tmx-label">Score *</label><input type="text" name="edu[${idx}][score]" class="form-control"></div>
          <div class="col-6 col-lg-1"><label class="tmx-label">Out of *</label><input type="number" min="0" name="edu[${idx}][out_of]" class="form-control"></div>
        </div>
        <div class="rec-actions">
          <button type="button" class="btn btn-x btn-add" data-add-after="edu"><i class="fa-solid fa-plus"></i> Add New</button>
          <button type="button" class="btn btn-x btn-delete" data-remove="edu"><i class="fa-solid fa-trash-can"></i> Remove this</button>
        </div>
      </div>`;
  }
  function jobRowHtml(idx){
    return `
      <div class="rec" data-job-idx="${idx}">
        <div class="row g-2">
          <div class="col-12 col-md-4"><label class="tmx-label">Employer *</label><input type="text" name="job[${idx}][employer]" class="form-control"></div>
          <div class="col-12 col-md-3"><label class="tmx-label">Job Title *</label><input type="text" name="job[${idx}][job_title]" class="form-control"></div>
          <div class="col-6 col-md-2"><label class="tmx-label">Joining Date *</label><div class="date-wrap"><input type="text" name="job[${idx}][join_date]" class="form-control tmx-date" placeholder="YYYY-MM-DD" inputmode="none" autocomplete="off"><i class="fa-regular fa-calendar-days"></i></div></div>
          <div class="col-6 col-md-2"><label class="tmx-label">Present?</label><select name="job[${idx}][is_present]" class="form-select tmx-ts-single"><option value="" hidden>— Select —</option><option value="Y">Yes</option><option value="N">No</option></select></div>
          <div class="col-6 col-md-3"><label class="tmx-label">End Date</label><div class="date-wrap"><input type="text" name="job[${idx}][end_date]" class="form-control tmx-date" placeholder="YYYY-MM-DD" inputmode="none" autocomplete="off"><i class="fa-regular fa-calendar-days"></i></div></div>
        </div>
        <div class="rec-actions">
          <button type="button" class="btn btn-x btn-add" data-add-after="job"><i class="fa-solid fa-plus"></i> Add New</button>
          <button type="button" class="btn btn-x btn-delete" data-remove="job"><i class="fa-solid fa-trash-can"></i> Remove this</button>
        </div>
      </div>`;
  }
  function trainRowHtml(idx){
    const cat=`@foreach($trainCats as $c)<option value="{{ $c->Training_Category_Id }}">{{ $c->Training_Category_Name }}</option>@endforeach`;
    return `
      <div class="rec" data-trn-idx="${idx}">
        <div class="row g-2">
          <div class="col-12 col-md-6"><label class="tmx-label">Training Category *</label><select name="train[${idx}][category_id]" class="form-select tmx-ts-single trn-cat"><option value="" hidden>— Select —</option>${cat}</select></div>
          <div class="col-12 col-md-6"><label class="tmx-label">Training Name *</label><select name="train[${idx}][training_id]" class="form-select tmx-ts-single trn-name" disabled><option value="" hidden>— Select category first —</option></select></div>
        </div>
        <div class="rec-actions">
          <button type="button" class="btn btn-x btn-add" data-add-after="train"><i class="fa-solid fa-plus"></i> Add New</button>
          <button type="button" class="btn btn-x btn-delete" data-remove="train"><i class="fa-solid fa-trash-can"></i> Remove this</button>
        </div>
      </div>`;
  }
  function mountTS(scope){
    scope.querySelectorAll('select.tmx-ts-edu, select.tmx-ts-single').forEach(function(sel){
      tsInit(sel);
      if (sel.classList.contains('trn-cat')){
        const ts=tsEnsure(sel);
        if (ts) ts.on('change',()=> loadTrainings(sel)); else sel.addEventListener('change',()=> loadTrainings(sel));
      }
    });
  }
  function addEduAfter(node){const idx=document.querySelectorAll('[data-edu-idx]').length; const d=document.createElement('div'); d.innerHTML=eduRowHtml(idx); const n=d.firstElementChild; (node?node.after(n):eduWrap.appendChild(n)); mountTS(n);}
  function addJobAfter(node){const idx=document.querySelectorAll('[data-job-idx]').length; const d=document.createElement('div'); d.innerHTML=jobRowHtml(idx); const n=d.firstElementChild; (node?node.after(n):jobWrap.appendChild(n)); mountTS(n); mountJobCalendars(n);}
  function addTrainAfter(node){const idx=document.querySelectorAll('[data-trn-idx]').length; const d=document.createElement('div'); d.innerHTML=trainRowHtml(idx); const n=d.firstElementChild; (node?node.after(n):trnWrap.appendChild(n)); mountTS(n);}

  (function initRows(){
    const oldEdu=@json(old('edu', [])), oldJob=@json(old('job', [])), oldTrn=@json(old('train', []));
    if(!(Array.isArray(oldEdu)&&oldEdu.length)){ addEduAfter(null); }
    if(!(Array.isArray(oldJob)&&oldJob.length)){ addJobAfter(null); }
    if(!(Array.isArray(oldTrn)&&oldTrn.length)){ addTrainAfter(null); }
    mountTS(document);
    mountJobCalendars(document);
  })();

  (async function restoreDynamic(){
    const oldEdu=@json(old('edu', [])), oldJob=@json(old('job', [])), oldTrn=@json(old('train', []));
    if(Array.isArray(oldEdu)&&oldEdu.length){
      eduWrap.innerHTML='';
      oldEdu.forEach((r,i)=>{ const d=document.createElement('div'); d.innerHTML=eduRowHtml(i); const n=d.firstElementChild; eduWrap.appendChild(n); mountTS(n);
        tsSetValue(n.querySelector(`[name="edu[${i}][degree_id]"]`), r.degree_id??'');
        n.querySelector(`[name="edu[${i}][institution]"]`).value=r.institution??'';
        n.querySelector(`[name="edu[${i}][passing_year]"]`).value=r.passing_year??'';
        tsSetValue(n.querySelector(`[name="edu[${i}][result_type]"]`), r.result_type??'');
        n.querySelector(`[name="edu[${i}][score]"]`).value=r.score??'';
        n.querySelector(`[name="edu[${i}][out_of]"]`).value=r.out_of??'';
      });
    }
    if(Array.isArray(oldJob)&&oldJob.length){
      jobWrap.innerHTML='';
      oldJob.forEach((r,i)=>{ const d=document.createElement('div'); d.innerHTML=jobRowHtml(i); const n=d.firstElementChild; jobWrap.appendChild(n); mountTS(n); mountJobCalendars(n);
        n.querySelector(`[name="job[${i}][employer]"]`).value=r.employer??'';
        n.querySelector(`[name="job[${i}][job_title]"]`).value=r.job_title??'';
        n.querySelector(`[name="job[${i}][join_date]"]`).value=r.join_date??'';
        tsSetValue(n.querySelector(`[name="job[${i}][is_present]"]`), r.is_present??'');
        const end=n.querySelector(`[name="job[${i}][end_date]"]`);
        end.value=r.end_date??'';
        if((r.is_present??'')==='Y'){ end.value=''; end.setAttribute('disabled','disabled'); }
      });
    }
    if(Array.isArray(oldTrn)&&oldTrn.length){
      trnWrap.innerHTML='';
      for(let i=0;i<oldTrn.length;i++){
        const r=oldTrn[i];
        const d=document.createElement('div'); d.innerHTML=trainRowHtml(i); const n=d.firstElementChild; trnWrap.appendChild(n); mountTS(n);
        const cat=n.querySelector(`[name="train[${i}][category_id]"]`), nameSel=n.querySelector(`[name="train[${i}][training_id]"]`);
        tsSetValue(cat, r.category_id??'');
        if(r.category_id){ await loadTrainings(cat); tsSetValue(nameSel, r.training_id??''); }
      }
    }
  })();

  async function loadTrainings(selectCatEl){
    const rec=selectCatEl.closest('[data-trn-idx]'); const nameEl=rec.querySelector('.trn-name'); if(!nameEl) return;
    const catId=tsGetValue(selectCatEl)||selectCatEl.value; const tsName=tsEnsure(nameEl);
    if(tsName){ tsName.clear(true); tsName.clearOptions(); tsName.disable(); }
    if(!catId){ if(tsName) tsName.refreshOptions(false); nameEl.setAttribute('disabled','disabled'); return; }
    let list=await fetchJSON(`${base}/api/trainings?category_id=${encodeURIComponent(catId)}`); const items=(list||[]).map(r=>({value:String(r.id),text:r.name}));
    if(tsName){ tsName.addOptions(items); tsName.refreshOptions(false); tsName.enable(); } else { nameEl.innerHTML='<option value="" hidden>— Select —</option>'; items.forEach(r=>nameEl.insertAdjacentHTML('beforeend',`<option value="${r.value}">${r.text}</option>`)); nameEl.removeAttribute('disabled'); }
  }

  let uploading=0;
  async function tempUpload(fieldName,file){
    const fd=new FormData(); fd.append('file',file); fd.append('_token',@json(csrf_token()));
    uploading++;
    try{
      const res=await fetch(`${base}/api/temp-upload?field=${encodeURIComponent(fieldName)}`,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:fd});
      const j=await res.json().catch(()=>null);
      if(j&&j.ok&&j.url){ const hidden=form.querySelector(`input[name="temp_${fieldName}"]`); if(hidden) hidden.value=j.url; return j.url; }
      return null;
    }catch(err){ return null; } finally{ uploading--; }
  }

  function addFieldError(el,msg){
    if(!el) return;
    el.classList.add('is-invalid');
    let holder=el.closest('.col-12,.col-6,.rec,.form-check,.date-wrap')||el.parentElement;
    if(holder){
      let m=holder.querySelector('.client-msg');
      if(!m){ m=document.createElement('div'); m.className='tmx-req client-msg'; holder.appendChild(m); }
      m.textContent=msg;
    }
  }
  function clearFieldError(el){
    if(!el) return;
    el.classList.remove('is-invalid');
    let holder=el.closest('.col-12,.col-6,.rec,.form-check,.date-wrap')||el.parentElement;
    const m=holder&&holder.querySelector('.client-msg');
    if(m) m.remove();
  }

  function bindUploadAndPreview(inputId, previewId, fieldName){
    const inp=document.getElementById(inputId); const box=document.getElementById(previewId); const hidden=form.querySelector(`input[name="temp_${fieldName}"]`);
    if(!inp||!box) return;

    if(hidden && hidden.value){
      box.innerHTML = `<span class="thumb"><img src="${hidden.value}" alt="${fieldName}"><button type="button" class="rmv" data-clear="${fieldName}">Remove</button></span>`;
      box.hidden=false;
    }

    inp.addEventListener('change', async function(){
      box.innerHTML=''; box.hidden=true;
      const f=inp.files&&inp.files[0]; if(!f) return;
      if(f.size>(MAX_KB*1024)){ addFieldError(inp,`File size must be ≤ ${MAX_KB}KB.`); return; }
      clearFieldError(inp);
      const url=await tempUpload(fieldName,f);
      const finalUrl=url||URL.createObjectURL(f);
      if(hidden && url) hidden.value=url;
      const span=document.createElement('span'); span.className='thumb';
      const img=document.createElement('img'); img.src=finalUrl; img.alt=fieldName; img.addEventListener('click', (e)=>{e.preventDefault(); openZoom(finalUrl);});
      const rmv=document.createElement('button'); rmv.type='button'; rmv.className='rmv'; rmv.dataset.clear=fieldName; rmv.textContent='Remove';
      span.appendChild(img); span.appendChild(rmv); box.appendChild(span); box.hidden=false;
    });
  }

  bindUploadAndPreview('photo','preview_photo','photo');
  bindUploadAndPreview('nid_front','preview_nid_front','nid_front');
  bindUploadAndPreview('nid_back','preview_nid_back','nid_back');
  bindUploadAndPreview('birth_certificate','preview_birth','birth_certificate');

  (function(){
    function setYearsReq(cb){
      const yrs=form.querySelector(`input[name="software_years[${cb.value}]"]`); if(!yrs) return;
      if(cb.checked){ yrs.removeAttribute('disabled'); yrs.setAttribute('required','required'); }
      else { yrs.value=''; yrs.setAttribute('disabled','disabled'); yrs.removeAttribute('required'); yrs.classList.remove('is-invalid'); const m=yrs.closest('.form-check,.soft-years')?.querySelector('.client-msg'); if(m) m.remove(); }
    }
    form.querySelectorAll('input[name="software_ids[]"]').forEach(cb=> cb.addEventListener('change',()=>setYearsReq(cb)));
    form.querySelectorAll('input[name="software_ids[]"]').forEach(cb=> setYearsReq(cb));
  })();

  (function(){
    const nid = document.getElementById('nid_number');
    const msg = document.getElementById('nid_len_msg');
    function update(){
      const v = (nid.value||'').trim();
      if(v.length===0){ msg.style.display='none'; nid.classList.remove('is-invalid'); return; }
      const ok = NID_ALLOWED.includes(v.length);
      msg.style.display = ok ? 'none' : 'block';
      if(!ok){ nid.classList.add('is-invalid'); } else { nid.classList.remove('is-invalid'); }
    }
    if(nid){ nid.addEventListener('input', update); nid.addEventListener('blur', update); update(); }
  })();

  const statusMap={personal:document.getElementById('c-personal'),edu:document.getElementById('c-edu'),job:document.getElementById('c-job'),soft:document.getElementById('c-soft'),skills:document.getElementById('c-skills'),areas:document.getElementById('c-areas'),train:document.getElementById('c-train')};
  function markStatus(el,ok){el.textContent=ok?'Validated':'Not validated'; el.classList.toggle('c-ok',!!ok); el.classList.toggle('c-bad',!ok);}

  function validateAll(){
    document.querySelectorAll('.is-invalid').forEach(n=>n.classList.remove('is-invalid'));
    document.querySelectorAll('.client-msg').forEach(n=>n.remove());
    let firstBad=null;

    let okPersonal=true;
    if(!form.querySelector('input[name="gender"]:checked')){ okPersonal=false; if(!firstBad){ firstBad=form.querySelector('input[name="gender"]'); addFieldError(firstBad,'Select gender.'); } }
    const dob=document.getElementById('dob_field'); if(!dob.value.trim()){ okPersonal=false; if(!firstBad){ firstBad=dob; addFieldError(dob,'Date of birth is required.'); } }

    const phoneEl = form.querySelector('input[name="phone"]');
    if(!phoneEl.value.trim()){ okPersonal=false; addFieldError(phoneEl,'Required'); if(!firstBad){ firstBad=phoneEl; } }

    ['division_id','district_id','upazila_id','thana_id'].forEach(id=>{
      const el=form.querySelector('#'+id); const v=tsGetValue(el); if(!v){ okPersonal=false; if(!firstBad){ firstBad=el; addFieldError(el,'Required.'); } }
    });

    if(!form.querySelector('input[name="person_type"]:checked')){ okPersonal=false; if(!firstBad){ firstBad=form.querySelector('input[name="person_type"]'); addFieldError(firstBad,'Select person type.'); } }

    if(!form.querySelector('input[name="profession"]:checked')){ okPersonal=false; const msg=document.getElementById('prof_msg'); if(msg) msg.style.display='block'; if(!firstBad){ firstBad=form.querySelector('input[name="profession"]'); } }
    else { const msg=document.getElementById('prof_msg'); if(msg) msg.style.display='none'; }

    const addr=form.present_address; if(!addr.value.trim()){ okPersonal=false; if(!firstBad){ firstBad=addr; addFieldError(addr,'Present address is required.'); } }

    const photoFile=document.getElementById('photo').files[0];
    const photoTemp=(form.querySelector('input[name="temp_photo"]')?.value||'').trim();
    if(!photoFile && !photoTemp){ okPersonal=false; const ph=document.getElementById('photo'); if(!firstBad) firstBad=ph; addFieldError(ph,'Photo is required.'); }

    const nidNum=document.getElementById('nid_number').value.trim();
    const nfFile=document.getElementById('nid_front').files[0];
    const nbFile=document.getElementById('nid_back').files[0];
    const bcFile=document.getElementById('birth_certificate').files[0];
    const nfTemp=(form.querySelector('input[name="temp_nid_front"]')?.value||'').trim();
    const nbTemp=(form.querySelector('input[name="temp_nid_back"]')?.value||'').trim();
    const bcTemp=(form.querySelector('input[name="temp_birth_certificate"]')?.value||'').trim();

    if(nidNum.length){
      if(!@json($nidLengths).includes(nidNum.length)){ okPersonal=false; const nid=document.getElementById('nid_number'); if(!firstBad) firstBad=nid; addFieldError(nid,'NID length must be one of: '+@json($nidLengths).join(', ')); const live=document.getElementById('nid_len_msg'); if(live){ live.style.display='block'; } }
      if(!nfFile && !nfTemp){ okPersonal=false; const nf=document.getElementById('nid_front'); if(!firstBad) firstBad=nf; addFieldError(nf,'Provide NID front image.'); }
      if(!nbFile && !nbTemp){ okPersonal=false; const nb=document.getElementById('nid_back'); if(!firstBad) firstBad=nb; addFieldError(nb,'Provide NID back image.'); }
    }else{
      if(!bcFile && !bcTemp){ okPersonal=false; const bc=document.getElementById('birth_certificate'); if(!firstBad) firstBad=bc; addFieldError(bc,'Birth certificate is required if NID not provided.'); }
    }

    markStatus(statusMap.personal, okPersonal);

    let okEdu=true;
    const eduBlocks=document.querySelectorAll('[data-edu-idx]');
    if(eduBlocks.length===0) okEdu=false;
    eduBlocks.forEach(b=>{
      const degree=b.querySelector('select[name*="[degree_id]"]'), inst=b.querySelector('input[name*="[institution]"]'), year=b.querySelector('input[name*="[passing_year]"]'), rtype=b.querySelector('select[name*="[result_type]"]'), score=b.querySelector('input[name*="[score]"]'), outof=b.querySelector('input[name*="[out_of]"]');
      const checks=[[degree,!!tsGetValue(degree)],[inst,!!(inst&&inst.value.trim())],[year,!!(year&&year.value.trim())],[rtype,!!tsGetValue(rtype)],[score,!!(score&&score.value.trim())],[outof,outof&&outof.value!=='']];
      checks.forEach(([el,ok])=>{ if(!ok){ okEdu=false; addFieldError(el,'Required'); if(!firstBad) firstBad=el; }});
    });
    markStatus(statusMap.edu, okEdu);

    let okJob=true;
    const jobBlocks=document.querySelectorAll('[data-job-idx]');
    jobBlocks.forEach(b=>{
      const employer=b.querySelector('input[name*="[employer]"]'), title=b.querySelector('input[name*="[job_title]"]'), join=b.querySelector('input[name*="[join_date]"]'), present=b.querySelector('select[name*="[is_present]"]'), end=b.querySelector('input[name*="[end_date]"]');
      const touched=[employer,title,join,end,present].some(x=>x && x.value && x.value.trim()); if(!touched) return;
      const checks=[[employer,!!(employer&&employer.value.trim())],[title,!!(title&&title.value.trim())],[join,!!(join&&join.value.trim())],[present,!!tsGetValue(present)]];
      checks.forEach(([el,ok])=>{ if(!ok){ okJob=false; addFieldError(el,'Required'); if(!firstBad) firstBad=el; }});
      if(tsGetValue(present)==='N' && (!end || !end.value.trim())){ okJob=false; addFieldError(end,'End date is required.'); if(!firstBad) firstBad=end; }
    });
    markStatus(statusMap.job, okJob);

    let okSoft=true;
    document.querySelectorAll('input[name="software_ids[]"]:checked').forEach(cb=>{
      const yrs=form.querySelector(`input[name="software_years[${cb.value}]"]`);
      if(!yrs || yrs.disabled || yrs.value==='' || Number.isNaN(parseFloat(yrs.value)) || parseFloat(yrs.value)<0){ okSoft=false; if(!firstBad) firstBad=yrs; addFieldError(yrs,'Enter valid years of experience.'); }
    });
    markStatus(statusMap.soft, okSoft);

    let okSkills=!!form.querySelector('input[name="skill_ids[]"]:checked');
    if(!okSkills){
      const skillsBox = document.querySelector('#card-skills .scroll-box');
      if(skillsBox){
        let m = skillsBox.parentElement.querySelector('.client-msg.skills');
        if(!m){ m = document.createElement('div'); m.className = 'tmx-req client-msg skills'; skillsBox.parentElement.appendChild(m); }
        m.textContent = 'Required';
        if(!firstBad){ const firstCb = skillsBox.querySelector('input[name="skill_ids[]"]'); firstBad = firstCb || skillsBox; }
      }
    }
    markStatus(statusMap.skills, okSkills);

    markStatus(statusMap.areas, true);

    let okTrain=true;
    document.querySelectorAll('[data-trn-idx]').forEach(b=>{
      const cat=b.querySelector('select[name*="[category_id]"]'), trn=b.querySelector('select[name*="[training_id]"]');
      const touched = tsGetValue(cat) || tsGetValue(trn);
      if(!touched) return;
      const checks=[[cat,!!tsGetValue(cat)],[trn,!!tsGetValue(trn)]];
      checks.forEach(([el,ok])=>{ if(!ok){ okTrain=false; addFieldError(el,'Required'); if(!firstBad) firstBad=el; }});
    });
    markStatus(statusMap.train, okTrain);

    return { ok: (okPersonal && okEdu && okJob && okSoft && okSkills && okTrain), firstBad };
  }

  const goDownBtn = document.getElementById('btnGoDown');
  if(goDownBtn){
    goDownBtn.addEventListener('click', ()=>{
      const target = document.getElementById('btnValidateSave');
      if (target) {
        target.scrollIntoView({ behavior:'smooth', block:'center' });
        setTimeout(()=>{ try{ target.focus({preventScroll:true}); }catch(e){} }, 300);
      }
    });
  }

  document.getElementById('btnTop').addEventListener('click', ()=> window.scrollTo({ top:0, behavior:'smooth' }));
  document.getElementById('btnCancel').addEventListener('click', ()=>{
    form.reset();
    document.querySelectorAll('select.tmx-ts-edu, select.tmx-ts-single').forEach(sel=>{ const ts=sel.tomselect; if(ts){ ts.clear(true); ts.clearOptions(); } });
    document.querySelectorAll('[name^="software_years["]').forEach(i=>{ i.value=''; i.setAttribute('disabled','disabled'); i.classList.remove('is-invalid'); });
    ['photo','nid_front','nid_back','birth_certificate'].forEach(f=>{
      const hidden=form.querySelector(`input[name="temp_${f}"]`); if(hidden) hidden.value='';
      const input=document.getElementById(f); if(input) input.value='';
      const box=document.getElementById(`preview_${f==='birth_certificate'?'birth':f}`); if(box){ box.innerHTML=''; box.hidden=true; }
    });
    window.scrollTo({ top:0, behavior:'smooth' });
    document.getElementById('valAlert').style.display='none';
    Object.values(statusMap).forEach(el=> markStatus(el,false));
    document.querySelectorAll('.srv-err').forEach(n=> n.style.display='');
  });

  document.getElementById('btnValidateSave').addEventListener('click', async function(){
    const res=validateAll();
    const alertBox=document.getElementById('valAlert');

    if (SRV_ERR_SK) {
      const first = document.querySelector('#card-skills input[name="skill_ids[]"]');
      if (first) { first.scrollIntoView({behavior:'smooth', block:'center'}); try{ first.focus({preventScroll:true}); }catch(e){} }
    }

    if(!res.ok){
      alertBox.style.display='block';
      if(res.firstBad){ res.firstBad.scrollIntoView({behavior:'smooth',block:'center'}); try{res.firstBad.focus({preventScroll:true});}catch(e){} }
      return;
    }
    alertBox.style.display='none';

    const btn=this; btn.disabled=true;
    await new Promise((resolve)=>{ const t=setInterval(()=>{ if(uploading<=0){ clearInterval(t); resolve(); } },100); });

    document.querySelectorAll('#card-soft input[id^="yrs_"]').forEach(inp=>{ if(inp && !inp.disabled && typeof inp.value==='string'){ inp.value = inp.value.replace(',', '.').trim(); } });

    form.submit();
  });

  mountJobCalendars(document);
})();
</script>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const firstSrv = document.querySelector('.srv-err');
  if (firstSrv){
    const fld = firstSrv.closest('.col-12,.col-6,.date-wrap,.rec')?.querySelector('.is-invalid, input, select, textarea');
    if (fld){
      fld.scrollIntoView({behavior:'smooth', block:'center'});
      try{ fld.focus({preventScroll:true}); }catch(e){}
    } else {
      firstSrv.scrollIntoView({behavior:'smooth', block:'center'});
    }
  }
});
</script>

@endpush
