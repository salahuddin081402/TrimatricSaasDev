@extends('backend.layouts.master')
{{-- TMX-ENTR | resources/views/registration/entrepreneur/create.blade.php | v6.4.6
   Deltas vs v6.4.5:
   - Step-1: photo client validation now accepts existing DB photo (no false red border after Back from Step-2).
   - Step-2: Present Job restored from localStorage keyed by company+reg when coming back from Step-3.
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

/* Step-3 specific layout to widen last 4 fields */
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

/* Buttons – generic on this form */
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

/* Step-3 Add / Remove nicer buttons */
.btn-edu-add{
  background:linear-gradient(90deg,#22c55e,#16a34a)!important;
  border-color:#16a34a!important;color:#f9fafb!important;
}
.btn-edu-add:hover{background:linear-gradient(90deg,#16a34a,#15803d)!important}

.btn-edu-remove{
  background:linear-gradient(90deg,#ef4444,#dc2626)!important;
  border-color:#dc2626!important;color:#fef2f2!important;
}
.btn-edu-remove:hover{background:linear-gradient(90deg,#dc2626,#b91c1c)!important}

/* step-footer */
.form-actions{display:flex;gap:10px;justify-content:space-between;padding:12px 0;flex-wrap:wrap}

/* Step-3 block aesthetics */
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

/* calendar portal (kept for compatibility if external calendar.js is used globally) */
#dob-cal-portal{position:fixed; z-index:99999; display:none;background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 12px 28px rgba(23,33,46,.18)}
#dob-cal-portal.open{display:block}
.calendar-card{max-width:320px}
.err-list{margin:6px 0 0 18px;color:var(--bad)}

/* step-4–6 modal */
.modal-ok{position:fixed;inset:0;background:rgba(0,0,0,.35);display:none;align-items:center;justify-content:center;z-index:99999}
.modal-card{background:#fff;border-radius:16px;max-width:540px;width:92%;padding:18px;border:1px solid #e5e9f2;box-shadow:0 20px 60px rgba(23,33,46,.25)}
</style>
@endpush

@section('content')
@php
  $currentStep = (int)($step ?? 1);
  $fv = fn($k, $def = '') => old($k, $form[$k] ?? $def);

  // Resolve registration id once for DB fallbacks + navigation
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

  // ================== STEP-3: Education preload with DB fallback ==================
  $educationRows = old('education', $educationRows ?? null);

  if (($educationRows === null || (is_array($educationRows) && empty($educationRows)))
      && $currentStep === 3
      && $regId > 0) {
      try {
          $educationRows = \DB::table('education_background')
              ->where('Registration_ID', $regId)
              ->orderBy('id')
              ->get()
              ->map(function ($r) {
                  return [
                      'degree_id' => $r->Degree_ID ?? $r->degree_id ?? null,
                      'institution' => $r->Institution ?? $r->institution ?? null,
                      'passing_year' => $r->Passing_Year ?? $r->passing_year ?? null,
                      'result_type' => $r->Result_Type ?? $r->result_type ?? null,
                      'obtained_grade_or_score' => $r->obtained_grade_or_score ?? $r->Obtained_Grade_or_Score ?? null,
                      'out_of' => $r->Out_of ?? $r->out_of ?? null,
                  ];
              })
              ->toArray();
      } catch (\Throwable $e) {
          $educationRows = [];
      }
  }

  if ($educationRows instanceof \Illuminate\Support\Collection) {
      $educationRows = $educationRows->map(function($r){
          if (is_array($r)) return $r;
          return [
              'degree_id' => $r->degree_id ?? null,
              'institution' => $r->institution ?? null,
              'passing_year' => $r->passing_year ?? null,
              'result_type' => $r->result_type ?? null,
              'obtained_grade_or_score' => $r->obtained_grade_or_score ?? null,
              'out_of' => $r->out_of ?? null,
          ];
      })->toArray();
  } elseif (!is_array($educationRows)) {
      $tmp = [];
      if (is_iterable($educationRows)) {
          foreach ($educationRows as $r) {
              if (is_array($r)) {
                  $tmp[] = $r;
              } else {
                  $tmp[] = [
                      'degree_id' => $r->degree_id ?? null,
                      'institution' => $r->institution ?? null,
                      'passing_year' => $r->passing_year ?? null,
                      'result_type' => $r->result_type ?? null,
                      'obtained_grade_or_score' => $r->obtained_grade_or_score ?? null,
                      'out_of' => $r->out_of ?? null,
                  ];
              }
          }
      }
      $educationRows = $tmp;
  }
  if(!is_array($educationRows) || empty($educationRows)){
    $educationRows = [[
      'degree_id' => '',
      'institution' => '',
      'passing_year' => '',
      'result_type' => '',
      'obtained_grade_or_score' => '',
      'out_of' => '',
    ]];
  }

  // ================== STEP-4: Software & Skills preload (with DB fallback for software) ==================
  $softOldIds   = old('softwares', old('software_ids', []));
  $softYearsOld = old('years', []);

  if(!empty($softOldIds)){
      $selectedSoftwares = collect($softOldIds)->map(function($r) use ($softYearsOld){
          if(is_array($r)){
              $id = (string)($r['id'] ?? '');
              $yearsKey = $id;
              $years = (string)($r['years'] ?? ($softYearsOld[$yearsKey] ?? ''));
          } else {
              $id = (string)$r;
              $years = isset($softYearsOld[$id]) ? (string)$softYearsOld[$id] : '';
          }
          return ['id'=>$id, 'years'=>$years];
      })->filter(function($row){ return $row['id'] !== ''; })->values()->all();
  } else {
      if (empty($selectedSoftwares) && $regId > 0) {
          try {
              $selectedSoftwares = \DB::table('expertise_on_softwares')
                  ->where('Registration_ID', $regId)
                  ->orderBy('id')
                  ->get()
                  ->map(function($r){
                      return [
                          'id'    => (string)($r->expert_on_software ?? $r->software_id ?? $r->Software_ID ?? $r->id ?? ''),
                          'years' => (string)($r->experience_in_years ?? $r->Experience_in_years ?? $r->years ?? ''),
                      ];
                  })
                  ->toArray();
          } catch (\Throwable $e) {
              $selectedSoftwares = [];
          }
      }

      $selectedSoftwares = collect($selectedSoftwares ?? [])->map(function($r){
          if(is_array($r)){
              $id    = (string)($r['id'] ?? $r['software_id'] ?? $r['expert_on_software'] ?? '');
              $years = (string)($r['years'] ?? $r['experience_in_years'] ?? '');
          } elseif(is_object($r)){
              $id    = (string)($r->id ?? $r->software_id ?? $r->expert_on_software ?? $r->Software_ID ?? '');
              $years = (string)($r->years ?? $r->experience_in_years ?? $r->Experience_in_years ?? '');
          } else {
              $id    = (string)$r;
              $years = '';
          }
          return ['id'=>$id, 'years'=>$years];
      })->filter(function($row){ return $row['id'] !== ''; })->values()->all();
  }

  $selectedSkills    = collect(old('skills', $selectedSkills ?? []))
      ->map(function($id){
          if (is_object($id)) {
              return (string)($id->id ?? '');
          }
          return (string)$id;
      })->filter(fn($v)=>$v!=='')->values()->all();

  // ================== STEP-5: Preferred Areas preload ==================
  $selectedTasks     = collect(old('tasks', $selectedTasks ?? []))
      ->map(function($id){
          if (is_object($id)) {
              return (string)($id->task_param_id ?? $id->Task_Param_ID ?? $id->id ?? '');
          }
          return (string)$id;
      })->filter(fn($v)=>$v!=='')->values()->all();

  // ================== STEP-6: Training Required preload with DB fallback ==================
  $trainingRowsFromDb = isset($trainingRows) ? $trainingRows : null;

  if (( $trainingRowsFromDb === null || (is_array($trainingRowsFromDb) && empty($trainingRowsFromDb)) )
      && $regId > 0
      && $currentStep === 6) {
      try {
          $trainingRowsFromDb = \DB::table('training_required')
              ->where('Registration_ID', $regId)
              ->orderBy('id')
              ->get()
              ->map(function($r){
                  return [
                      'category_id' => $r->Training_Category_Id ?? $r->training_category_id ?? $r->category_id ?? null,
                      'training_id' => $r->Training_Id ?? $r->training_id ?? $r->Training_ID ?? null,
                  ];
              })
              ->toArray();
      } catch (\Throwable $e) {
          $trainingRowsFromDb = [];
      }
  }

  $trainingRows = old('train');
  if ($trainingRows === null) {
      $trainingRows = $trainingRowsFromDb ?? [];
  }
  if ($trainingRows instanceof \Illuminate\Support\Collection) {
      $trainingRows = $trainingRows->map(function($r){
          if (is_array($r)) return $r;
          return [
              'category_id' => $r->category_id
                  ?? $r->Training_Category_Id
                  ?? $r->training_category_id
                  ?? null,
              'training_id' => $r->training_id
                  ?? $r->Training_ID
                  ?? $r->Training_Id
                  ?? null,
          ];
      })->toArray();
  } elseif (!is_array($trainingRows)) {
      $tmp = [];
      if (is_iterable($trainingRows)) {
          foreach ($trainingRows as $r) {
              if (is_array($r)) {
                  $tmp[] = [
                      'category_id' => $r['category_id'] ?? null,
                      'training_id' => $r['training_id'] ?? null,
                  ];
              } else {
                  $tmp[] = [
                      'category_id' => $r->category_id
                          ?? $r->Training_Category_Id
                          ?? $r->training_category_id
                          ?? null,
                      'training_id' => $r->training_id
                          ?? $r->Training_ID
                          ?? $r->Training_Id
                          ?? null,
                  ];
              }
          }
      }
      $trainingRows = $tmp;
  }
  if(!is_array($trainingRows) || empty($trainingRows)){
    $trainingRows = [[ 'category_id'=>'', 'training_id'=>'' ]];
  }

  // enums for result type
  $resultEnums = $resultEnums ?? ['GPA','CGPA','Division','Class','Percentage'];
@endphp

<div class="container-slim"
     data-company-slug="{{ $company->slug }}"
     data-route-districts="{{ route('registration.entrepreneur.api.geo.districts', ['company'=>$company->slug]) }}"
     data-route-upazilas="{{ route('registration.entrepreneur.api.geo.upazilas', ['company'=>$company->slug]) }}"
     data-route-thanas="{{ route('registration.entrepreneur.api.geo.thanas', ['company'=>$company->slug]) }}"
     data-route-temp-upload="{{ route('registration.entrepreneur.api.temp_upload', ['company'=>$company->slug]) }}"
     data-csrf="{{ csrf_token() }}">

  <header class="sticky-wrap" role="navigation" aria-label="Registration progress">
    <div class="gg-hdr">
      <div class="gg-title">Entrepreneur Registration</div>
      <small class="gg-hint">
        @if($currentStep===1) Step 1 of 6
        @elseif($currentStep===2) Step 2 of 6
        @elseif($currentStep===3) Step 3 of 6
        @elseif($currentStep===4) Step 4 of 6
        @elseif($currentStep===5) Step 5 of 6
        @else Step 6 of 6 @endif
      </small>
    </div>
    <div class="gg">
      <div class="gg-rail">
        <div class="gg-track">
          @php
            $fillPct = [1=>'0% ',2=>'22%',3=>'44%',4=>'66%',5=>'88%',6=>'100%'][$currentStep] ?? '0%';
          @endphp
          <div class="gg-fill" id="ggFill" style="width:{{ $fillPct }}"></div>
        </div>
        <ol class="gg-list">
          <li class="gg-step {{ $currentStep===1 ? 'current' : '' }}"><div class="gg-dot"></div><div class="gg-name">Basic Info</div></li>
          <li class="gg-step {{ $currentStep===2 ? 'current' : '' }}"><div class="gg-dot"></div><div class="gg-name">Present Job</div></li>
          <li class="gg-step {{ $currentStep===3 ? 'current' : '' }}"><div class="gg-dot"></div><div class="gg-name">Education</div></li>
          <li class="gg-step {{ $currentStep===4 ? 'current' : '' }}"><div class="gg-dot"></div><div class="gg-name">Expertise & Skills</div></li>
          <li class="gg-step {{ $currentStep===5 ? 'current' : '' }}"><div class="gg-dot"></div><div class="gg-name">Area of Interest</div></li>
          <li class="gg-step {{ $currentStep===6 ? 'current' : '' }}"><div class="gg-dot"></div><div class="gg-name">Training Required</div></li>
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
  <form id="entrepreneur-step1"
        method="POST"
        action="{{ route('registration.entrepreneur.step1.store', ['company'=>$company->slug]) }}"
        enctype="multipart/form-data" novalidate>
    @csrf
    <input type="hidden" name="registration_type" value="entrepreneur">

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
              <div class="inp" style="background:#f8fafc">Entrepreneur</div>
              <div class="help">Saved as <code>entrepreneur</code></div>
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
                  {{-- NEW: tells JS there is already a persisted photo in DB --}}
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

  <form id="entrepreneur-step2"
        method="POST"
        action="{{ route('registration.entrepreneur.step2.store', ['company'=>$company->slug]) }}"
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
          <p class="h-sub">Optional: leave all fields empty to skip; Save/Next will continue to Step-3.</p>
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
      <a href="{{ route('registration.entrepreneur.step1.create', ['company'=>$company->slug, 'reg'=>$regIdForNav]) }}" class="btn-x btn-back">
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

  {{-- ========================== STEP 3: Education ========================== --}}
  @if($currentStep===3)
  @php
    $regIdForNav = $regId;
  @endphp
  <form id="entrepreneur-step3"
        method="POST"
        action="{{ route('registration.entrepreneur.step3.store', ['company'=>$company->slug]) }}"
        novalidate>
    @csrf
    <input type="hidden" name="reg" value="{{ $regIdForNav }}">

    <div class="card-x">
      <div class="section-hdr">
        <div class="icon-edge" aria-hidden="true">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l9 6-9 6-9-6 9-6Zm0 14l7-4v4l-7 4-7-4v-4l7 4Z"/></svg>
        </div>
        <div>
          <h3 class="h-title">Educational Info</h3>
          <p class="h-sub">(Mandatory) Only input the latest certificates or course you have completed or presently studying.</p>
        </div>
        <span class="badge">Phase 3</span>
      </div>

      <div class="card-inner">
        <div id="edu-list">
          @foreach($educationRows as $i=>$row)
          <div class="rec-block" data-edu-index="{{ $i }}">
            <div class="grid grid-edu">
              <div class="col-6">
                <div class="fld">
                  <label class="lbl">Degree<span class="req">*</span></label>
                  <select name="education[{{ $i }}][degree_id]"
                          class="sel edu-degree @error("education.$i.degree_id") is-invalid @enderror"
                          required>
                    <option value="">Select degree</option>
                    @foreach($degrees as $deg)
                      @php
                        $degLabel = $deg->degree
                          ?? $deg->degree_name
                          ?? $deg->Degree
                          ?? $deg->DEGREE
                          ?? $deg->Degree_Name
                          ?? $deg->degree_title
                          ?? $deg->name
                          ?? $deg->title
                          ?? '#'.$deg->id;
                      @endphp
                      <option value="{{ $deg->id }}"
                        {{ (string)($row['degree_id'] ?? '')===(string)$deg->id?'selected':'' }}>
                        {{ $degLabel }}
                      </option>
                    @endforeach
                  </select>
                  @error("education.$i.degree_id")<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="col-6">
                <div class="fld">
                  <label class="lbl">Institution<span class="req">*</span></label>
                  <input type="text"
                         name="education[{{ $i }}][institution]"
                         value="{{ $row['institution'] ?? '' }}"
                         class="inp edu-inst @error("education.$i.institution") is-invalid @enderror"
                         required
                         placeholder="Institute/University/Board">
                  @error("education.$i.institution")<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="col-3">
                <div class="fld">
                  <label class="lbl">Passing Year</label>
                  <select name="education[{{ $i }}][passing_year]"
                          class="sel edu-year @error("education.$i.passing_year") is-invalid @enderror"
                          data-link="{{ $i }}">
                    <option value="">N/A / Under Study</option>
                    @for($y= (int)date('Y')+1; $y>=1950; $y--)
                      <option value="{{ $y }}" {{ (string)($row['passing_year'] ?? '')===(string)$y?'selected':'' }}>
                        {{ $y }}
                      </option>
                    @endfor
                  </select>
                  @error("education.$i.passing_year")<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <div class="help">Keep empty if studying.</div>
                </div>
              </div>

              <div class="col-3">
                <div class="fld">
                  <label class="lbl">Result Type</label>
                  <select name="education[{{ $i }}][result_type]"
                          class="sel edu-rtype @error("education.$i.result_type") is-invalid @enderror"
                          data-link="{{ $i }}">
                    <option value="">Select</option>
                    @foreach($resultEnums as $r)
                      <option value="{{ $r }}" {{ (string)($row['result_type'] ?? '')===(string)$r?'selected':'' }}>
                        {{ $r }}
                      </option>
                    @endforeach
                  </select>
                  @error("education.$i.result_type")<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="col-3">
                <div class="fld">
                  <label class="lbl">Obtained</label>
                  <input type="text"
                         name="education[{{ $i }}][obtained_grade_or_score]"
                         value="{{ $row['obtained_grade_or_score'] ?? '' }}"
                         class="inp edu-obt @error("education.$i.obtained_grade_or_score") is-invalid @enderror"
                         placeholder="e.g., 4.50">
                  @error("education.$i.obtained_grade_or_score")<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>

              <div class="col-3">
                <div class="fld">
                  <label class="lbl">Out of</label>
                  <input type="text"
                         name="education[{{ $i }}][out_of]"
                         value="{{ $row['out_of'] ?? '' }}"
                         class="inp edu-outof @error("education.$i.out_of") is-invalid @enderror"
                         placeholder="e.g., 5.00">
                  @error("education.$i.out_of")<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>
            </div>

            <div class="rec-actions">
              <button type="button" class="btn-x btn-edu-add btn-add-edu">
                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
                  <path d="M11 11V6h2v5h5v2h-5v5h-2v-5H6v-2z"/>
                </svg>
                Add row
              </button>
              <button type="button" class="btn-x btn-edu-remove btn-remove-edu" data-index="{{ $i }}">
                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
                  <path d="M7 11h10v2H7z"/>
                </svg>
                Remove
              </button>
              <span class="note-ghost">If only one block remains, Remove will just reset inputs.</span>
            </div>
          </div>
          @endforeach
        </div>

        <template id="edu-template">
          <div class="rec-block" data-edu-index="__IDX__">
            <div class="grid grid-edu">
              <div class="col-6">
                <div class="fld">
                  <label class="lbl">Degree<span class="req">*</span></label>
                  <select name="education[__IDX__][degree_id]" class="sel edu-degree" required>
                    <option value="">Select degree</option>
                    @foreach($degrees as $deg)
                      @php
                        $degLabel = $deg->degree
                          ?? $deg->degree_name
                          ?? $deg->Degree
                          ?? $deg->DEGREE
                          ?? $deg->Degree_Name
                          ?? $deg->degree_title
                          ?? $deg->name
                          ?? $deg->title
                          ?? '#'.$deg->id;
                      @endphp
                      <option value="{{ $deg->id }}">{{ $degLabel }}</option>
                    @endforeach
                  </select>
                </div>
              </div>

              <div class="col-6">
                <div class="fld">
                  <label class="lbl">Institution<span class="req">*</span></label>
                  <input type="text"
                         name="education[__IDX__][institution]"
                         class="inp edu-inst"
                         required
                         placeholder="Institute/University/Board">
                </div>
              </div>

              <div class="col-3">
                <div class="fld">
                  <label class="lbl">Passing Year</label>
                  <select name="education[__IDX__][passing_year]"
                          class="sel edu-year"
                          data-link="__IDX__">
                    <option value="">N/A / Under Study</option>
                    @for($y= (int)date('Y')+1; $y>=1950; $y--)
                      <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                  </select>
                  <div class="help">Keep empty if studying.</div>
                </div>
              </div>

              <div class="col-3">
                <div class="fld">
                  <label class="lbl">Result Type</label>
                  <select name="education[__IDX__][result_type]"
                          class="sel edu-rtype"
                          data-link="__IDX__">
                    <option value="">Select</option>
                    @foreach($resultEnums as $r)
                      <option value="{{ $r }}">{{ $r }}</option>
                    @endforeach
                  </select>
                </div>
              </div>

              <div class="col-3">
                <div class="fld">
                  <label class="lbl">Obtained</label>
                  <input type="text"
                         name="education[__IDX__][obtained_grade_or_score]"
                         class="inp edu-obt"
                         placeholder="e.g., 4.50">
                </div>
              </div>

              <div class="col-3">
                <div class="fld">
                  <label class="lbl">Out of</label>
                  <input type="text"
                         name="education[__IDX__][out_of]"
                         class="inp edu-outof"
                         placeholder="e.g., 5.00">
                </div>
              </div>
            </div>

            <div class="rec-actions">
              <button type="button" class="btn-x btn-edu-add btn-add-edu">
                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
                  <path d="M11 11V6h2v5h5v2h-5v5h-2v-5H6v-2z"/>
                </svg>
                Add row
              </button>
              <button type="button" class="btn-x btn-edu-remove btn-remove-edu">
                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
                  <path d="M7 11h10v2H7z"/>
                </svg>
                Remove
              </button>
              <span class="note-ghost">If only one block remains, Remove will just reset inputs.</span>
            </div>
          </div>
        </template>

      </div>
    </div>

    <div class="form-actions">
      <a href="{{ route('registration.entrepreneur.step2.create', ['company'=>$company->slug, 'reg'=>$regIdForNav]) }}" class="btn-x btn-back">
        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
          <path d="M10 19l-7-7 7-7v4h8v6h-8v4z"/>
        </svg>
        Back to Step-2
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

  {{-- ========================== STEP 4: Expertise & Skills ========================== --}}
  @if($currentStep===4)
  @php
    $regIdForNav = $regId;
  @endphp
  <form id="entrepreneur-step4"
        method="POST"
        action="{{ route('registration.entrepreneur.step4.store', ['company'=>$company->slug]) }}"
        novalidate>
    @csrf
    <input type="hidden" name="reg" value="{{ $regIdForNav }}">

    {{-- Software Expertise --}}
    <div class="card-x">
      <div class="section-hdr">
        <div class="icon-edge" aria-hidden="true">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v6H4zM4 13h16v6H4z"/></svg>
        </div>
        <div>
          <h3 class="h-title">Software Expertise</h3>
          <p class="h-sub">(Optional) If you have expertise on any softwares then please select.</p>
        </div>
        <span class="badge">Phase 4A</span>
      </div>

      <div class="card-inner">
        <div class="sub-card">
          <div class="pill-grid" id="soft-pills">
            @php
              $selMap = collect($selectedSoftwares)->keyBy('id')->map(fn($r)=>$r['years']??'')->all();
            @endphp
            @foreach($softwareList as $sw)
              @php
                $sid = (string)$sw->id;
                $isSel = array_key_exists($sid,$selMap);
                $swLabel = $sw->software_name
                  ?? $sw->Software_Name
                  ?? $sw->name
                  ?? $sw->title
                  ?? '#'.$sid;
              @endphp
              <label class="pill {{ $isSel ? 'selected':'' }}" data-soft-id="{{ $sid }}">
                <input type="checkbox" class="hidden soft-check" value="{{ $sid }}" {{ $isSel?'checked':'' }}>
                {{ $swLabel }}
              </label>
            @endforeach
          </div>

          <div id="soft-years-grid" class="soft-grid" style="margin-top:10px">
            @foreach($softwareList as $sw)
              @php
                $sid=(string)$sw->id;
                $years=$selMap[$sid]??'';
                $swLabel = $sw->software_name
                  ?? $sw->Software_Name
                  ?? $sw->name
                  ?? $sw->title
                  ?? '#'.$sid;
              @endphp
              <div class="soft-name hidden" data-soft-years-name="{{ $sid }}">
                <span class="lbl">{{ $swLabel }}</span>
              </div>
              <div class="soft-years hidden" data-soft-years-input="{{ $sid }}">
                <input type="number"
                       step="0.1"
                       min="0"
                       name="soft_years[{{ $sid }}]"
                       value="{{ $years }}"
                       class="inp soft-years-inp"
                       placeholder="Years (e.g., 1.5)">
              </div>
            @endforeach
          </div>

          <input type="hidden" name="softwares_json" id="softwares_json" value="">
          <div class="help">If you select a software, entering years of experience becomes mandatory.</div>
          <div class="invalid-feedback hidden" id="soft-err">Please provide years for each selected software.</div>
        </div>
      </div>
    </div>

    {{-- Skills --}}
    <div class="card-x">
      <div class="section-hdr">
        <div class="icon-edge" aria-hidden="true">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 2 7l10 5 10-5-10-5Zm0 20L2 17l10-5 10 5-10 5Z"/></svg>
        </div>
        <div>
          <h3 class="h-title">Your Skills</h3>
          <p class="h-sub">(Optional) Select any skills you have.</p>
        </div>
        <span class="badge">Phase 4B</span>
      </div>

      <div class="card-inner">
        <div class="sub-card">
          <div class="pill-grid" id="skill-pills">
            @php $skillSet = collect($selectedSkills)->flip(); @endphp
            @foreach(($skills ?? []) as $sk)
              @php
                $kid=(string)$sk->id;
                $isOn = $skillSet->has($kid);
                $skLabel = $sk->skill
                  ?? $sk->Skill
                  ?? $sk->skill_name
                  ?? $sk->Skill_Name
                  ?? $sk->name
                  ?? $sk->title
                  ?? '#'.$kid;
              @endphp
              <label class="pill {{ $isOn?'selected':'' }}" data-skill-id="{{ $kid }}">
                <input type="checkbox" class="hidden skill-check" value="{{ $kid }}" {{ $isOn?'checked':'' }}>
                {{ $skLabel }}
              </label>
            @endforeach
          </div>
          <input type="hidden" name="skills_json" id="skills_json" value="">
        </div>
      </div>
    </div>

    <div class="form-actions">
      <a href="{{ route('registration.entrepreneur.step3.create', ['company'=>$company->slug, 'reg'=>$regIdForNav]) }}" class="btn-x btn-back">
        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
          <path d="M10 19l-7-7 7-7v4h8v6h-8v4z"/>
        </svg>
        Back to Step-3
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

  {{-- ========================== STEP 5: Area of Interest ========================== --}}
  @if($currentStep===5)
  @php
    $regIdForNav = $regId;
  @endphp
  <form id="entrepreneur-step5"
        method="POST"
        action="{{ route('registration.entrepreneur.step5.store', ['company'=>$company->slug]) }}"
        novalidate>
    @csrf
    <input type="hidden" name="reg" value="{{ $regIdForNav }}">

    <div class="card-x">
      <div class="section-hdr">
        <div class="icon-edge" aria-hidden="true">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a7 7 0 0 1 7 7c0 5.25-7 13-7 13S5 14.25 5 9a7 7 0 0 1 7-7Zm0 9.5A2.5 2.5 0 1 0 9.5 9 2.5 2.5 0 0 0 12 11.5Z"/></svg>
        </div>
        <div>
          <h3 class="h-title">Your preferred Area</h3>
          <p class="h-sub">(Optional) For Student or Part-Time Job aspirant in this company</p>
        </div>
        <span class="badge">Phase 5</span>
      </div>

      <div class="card-inner">
        <div class="sub-card">
          <div class="pill-grid" id="task-pills">
            @php $taskSet = collect($selectedTasks)->flip(); @endphp
            @foreach($taskParams as $tp)
              @php
                $tid = (string)($tp->id ?? $tp->task_param_id ?? $tp->Task_Param_ID ?? '');
                $isOn = $taskSet->has($tid);
                $tpLabel = $tp->task_param_name
                  ?? $tp->Task_Param_Name
                  ?? $tp->name
                  ?? $tp->title
                  ?? '#'.$tid;
              @endphp
              <label class="pill {{ $isOn?'selected':'' }}" data-task-id="{{ $tid }}">
                <input type="checkbox" class="hidden task-check" value="{{ $tid }}" {{ $isOn?'checked':'' }}>
                {{ $tpLabel }}
              </label>
            @endforeach
          </div>
          <input type="hidden" name="tasks_json" id="tasks_json" value="">
        </div>
      </div>
    </div>

    <div class="form-actions">
      <a href="{{ route('registration.entrepreneur.step4.create', ['company'=>$company->slug, 'reg'=>$regIdForNav]) }}" class="btn-x btn-back">
        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
          <path d="M10 19l-7-7 7-7v4h8v6h-8v4z"/>
        </svg>
        Back to Step-4
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

  {{-- ========================== STEP 6: Training Required ========================== --}}
  @if($currentStep===6)
  @php
    $regIdForNav = $regId;
  @endphp
  <form id="entrepreneur-step6"
        method="POST"
        action="{{ route('registration.entrepreneur.step6.store', ['company'=>$company->slug]) }}"
        novalidate>
    @csrf
    <input type="hidden" name="reg" value="{{ $regIdForNav }}">

    <div class="card-x">
      <div class="section-hdr">
        <div class="icon-edge" aria-hidden="true">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a5 5 0 0 1 5 5v1h1a4 4 0 0 1 4 4v6h-3v4h-2v-4H7v4H5v-4H2v-6a4 4 0 0 1 4-4h1V7a5 5 0 0 1 5-5Z"/></svg>
        </div>
        <div>
          <h3 class="h-title">Training You Need (If any)</h3>
          <p class="h-sub">(Optional) For Student or Part-Time Job aspirant in this company</p>
        </div>
        <span class="badge">Phase 6</span>
      </div>

      <div class="card-inner">
        <div id="train-list">
          @foreach($trainingRows as $i=>$tr)
          <div class="rec-block" data-train-index="{{ $i }}">
            <div class="grid">
              <div class="col-6">
                <div class="fld">
                  <label class="lbl">Training Category</label>
                  <select name="train[{{ $i }}][category_id]"
                          class="sel train-cat"
                          data-link="{{ $i }}">
                    <option value="">Select Category</option>
                    @foreach($trainingCategories as $cat)
                      @php
                        $catLabel = $cat->training_category
                          ?? $cat->Training_Category
                          ?? $cat->category_name
                          ?? $cat->Category_Name
                          ?? $cat->name
                          ?? $cat->title
                          ?? '';
                      @endphp
                      <option value="{{ $cat->id }}" {{ (string)($tr['category_id'] ?? '')===(string)$cat->id?'selected':'' }}>
                        {{ $catLabel }}
                      </option>
                    @endforeach
                  </select>
                </div>
              </div>

              <div class="col-6">
                <div class="fld">
                  <label class="lbl">Training</label>
                  <select name="train[{{ $i }}][training_id]"
                          class="sel train-opt"
                          data-link="{{ $i }}">
                    <option value="">Select Training</option>
                    @if(!empty($trainingList))
                      @foreach($trainingList as $t)
                        @php
                          $tLabel = $t->training_name
                            ?? $t->Training_Name
                            ?? $t->training_title
                            ?? $t->Training_Title
                            ?? $t->name
                            ?? $t->title
                            ?? '';
                        @endphp
                        <option value="{{ $t->id }}"
                                data-cat="{{ $t->training_category_id ?? $t->category_id }}"
                                {{ (string)($tr['training_id'] ?? '')===(string)$t->id?'selected':'' }}>
                          {{ $tLabel }}
                        </option>
                      @endforeach
                    @endif
                  </select>
                </div>
              </div>
            </div>

            <div class="rec-actions">
              <button type="button" class="btn-x btn-edu-add btn-add-train">
                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
                  <path d="M11 11V6h2v5h5v2h-5v5h-2v-5H6v-2z"/>
                </svg>
                Add row
              </button>
              <button type="button" class="btn-x btn-edu-remove btn-remove-train" data-index="{{ $i }}">
                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
                  <path d="M7 11h10v2H7z"/>
                </svg>
                Remove
              </button>
              <span class="note-ghost">Optional. If only one remains, Remove resets.</span>
            </div>
          </div>
          @endforeach
        </div>

        <template id="train-template">
          <div class="rec-block" data-train-index="__IDX__">
            <div class="grid">
              <div class="col-6">
                <div class="fld">
                  <label class="lbl">Training Category</label>
                  <select name="train[__IDX__][category_id]"
                          class="sel train-cat"
                          data-link="__IDX__">
                    <option value="">Select Category</option>
                    @foreach($trainingCategories as $cat)
                      @php
                        $catLabel = $cat->training_category
                          ?? $cat->Training_Category
                          ?? $cat->category_name
                          ?? $cat->Category_Name
                          ?? $cat->name
                          ?? $cat->title
                          ?? '';
                      @endphp
                      <option value="{{ $cat->id }}">{{ $catLabel }}</option>
                    @endforeach
                  </select>
                </div>
              </div>

              <div class="col-6">
                <div class="fld">
                  <label class="lbl">Training</label>
                  <select name="train[__IDX__][training_id]"
                          class="sel train-opt"
                          data-link="__IDX__">
                    <option value="">Select Training</option>
                    @if(!empty($trainingList))
                      @foreach($trainingList as $t)
                        @php
                          $tLabel = $t->training_name
                            ?? $t->Training_Name
                            ?? $t->training_title
                            ?? $t->Training_Title
                            ?? $t->name
                            ?? $t->title
                            ?? '';
                        @endphp
                        <option value="{{ $t->id }}"
                                data-cat="{{ $t->training_category_id ?? $t->category_id }}">
                          {{ $tLabel }}
                        </option>
                      @endforeach
                    @endif
                  </select>
                </div>
              </div>
            </div>

            <div class="rec-actions">
              <button type="button" class="btn-x btn-edu-add btn-add-train">
                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
                  <path d="M11 11V6h2v5h5v2h-5v5h-2v-5H6v-2z"/>
                </svg>
                Add row
              </button>
              <button type="button" class="btn-x btn-edu-remove btn-remove-train">
                <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
                  <path d="M7 11h10v2H7z"/>
                </svg>
                Remove
              </button>
              <span class="note-ghost">Optional. If only one remains, Remove resets.</span>
            </div>
          </div>
        </template>

      </div>
    </div>

    <div class="form-actions">
      <a href="{{ route('registration.entrepreneur.step5.create', ['company'=>$company->slug, 'reg'=>$regIdForNav]) }}" class="btn-x btn-back">
        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
          <path d="M10 19l-7-7 7-7v4h8v6h-8v4z"/>
        </svg>
        Back to Step-5
      </a>

      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <button type="submit" class="btn-x btn-save">
          <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
            <path d="M17 3H5a2 2 0 0 0-2 2v14l4-4h10a2 2 0 0 0 2-2V5a2 2 0 0 0 2-2z"/>
          </svg>
          Save
        </button>

        {{-- No Next per spec --}}
        <a href="{{ route('backend.company.dashboard.index', ['company'=>$company->slug]) }}" class="btn-x btn-cancel">
          <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18" aria-hidden="true">
            <path d="M18.3 5.71 12 12.01l-6.3-6.3-1.4 1.41 6.29 6.3-6.3 6.3 1.41 1.41 6.3-6.29 6.29 6.29 1.41-1.41-6.29-6.3z"/>
          </svg>
          Cancel
        </a>
      </div>
    </div>
  </form>

  {{-- Congrats Modal --}}
  <div class="modal-ok" id="done-modal" @if(session('reg_completed')) style="display:flex" @endif>
    <div class="modal-card">
      <h3 class="h-title" style="margin-bottom:6px">Congratulations</h3>
      <p class="h-sub" style="margin-bottom:12px">
        You have successfully completed all steps of Registration. Head Office will deliver you an appropriate Entrepreneur card suitable for you as Enterprise Client/Entrepreneur.
      </p>
      <div style="display:flex;justify-content:flex-end;gap:10px">
        <a href="{{ route('backend.company.dashboard.index', ['company'=>$company->slug]) }}" class="btn-x btn-next">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
            <path d="M12 2a10 10 0 1 0 10 10A10.011 10.011 0 0 0 12 2Zm1 15h-2v-2h2Zm0-4h-2V7h2Z"/>
          </svg>
          OK
        </a>
      </div>
    </div>
  </div>
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
  var form1 = document.getElementById('entrepreneur-step1');

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

      // ---- FIXED: photo validation now respects existing DB photo ----
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
  var form2 = document.getElementById('entrepreneur-step2');
  function wireStep2(){
    if(!form2) return;

    // --- NEW: restore Last Present Job from localStorage so Back from Step-3 shows data ---
    var companySlug = dataOr(root,'companySlug','');
    var regInput = form2.querySelector('input[name="reg"]');
    var regId = regInput ? regInput.value : '';
    var storageKey = (companySlug && regId) ? ('tmx_entr_s2_'+companySlug+'_'+regId) : '';

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
    // ------------------------------------------------------------------

    form2.addEventListener('submit', function(ev){
      // always snapshot latest values before any validation
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

  // =========================================================
  // Step-3: Education dynamic rows + conditional triad
  // =========================================================
  var form3 = document.getElementById('entrepreneur-step3');
  function wireStep3(){
    if(!form3) return;
    var list = form3.querySelector('#edu-list');
    var tpl  = form3.querySelector('#edu-template');
    if(!list || !tpl) return;

    function renumberEdu(){
      var blocks = list.querySelectorAll('.rec-block');
      blocks.forEach(function(b, idx){
        var cur = b.getAttribute('data-edu-index');
        if(String(cur)===String(idx)) return;
        b.setAttribute('data-edu-index', idx);
        b.querySelectorAll('[name]').forEach(function(n){
          var nn = n.getAttribute('name');
          if(!nn) return;
          nn = nn
            .replace(/\beducation\[\d+\]/,'education['+idx+']')
            .replace(/education\[\__IDX__\]/,'education['+idx+']');
          n.setAttribute('name', nn);
        });
        b.querySelectorAll('[data-link]').forEach(function(n){
          n.setAttribute('data-link', String(idx));
        });
      });
    }

    list.addEventListener('click', function(ev){
      var addBtn = ev.target.closest('.btn-add-edu');
      var rmBtn  = ev.target.closest('.btn-remove-edu');
      if(addBtn){
        var html = tpl.innerHTML.replace(/__IDX__/g, Date.now());
        var tmp = document.createElement('div');
        tmp.innerHTML = html.trim();
        var block = tmp.firstElementChild;
        list.appendChild(block);
        renumberEdu();
        return;
      }
      if(rmBtn){
        var blocks = list.querySelectorAll('.rec-block');
        var blk = rmBtn.closest('.rec-block');
        if(!blk) return;
        if(blocks.length<=1){
          blk.querySelectorAll('input,select').forEach(function(el){
            if(el.tagName==='SELECT'){
              el.value='';
              var t = tsInstance(el);
              if(t){
                t.clear(true);
                t.setValue('', true);
              }
            }else{
              el.value='';
            }
            el.classList.remove('is-invalid');
          });
          return;
        }
        blk.remove();
        renumberEdu();
      }
    });

    function firstInvalidFocus3(){
      var el = form3.querySelector('.is-invalid');
      if(el){
        try{ el.focus({preventScroll:false}); }catch(e){}
        el.scrollIntoView({behavior:'smooth',block:'center'});
      }
    }

    form3.addEventListener('submit', function(ev){
      var blocks = list.querySelectorAll('.rec-block');
      if(blocks.length===0){
        ev.preventDefault();
        var html = tpl.innerHTML.replace(/__IDX__/g, Date.now());
        var tmp = document.createElement('div');
        tmp.innerHTML = html.trim();
        list.appendChild(tmp.firstElementChild);
        renumberEdu();
        return;
      }
      var hasErr=false;
      blocks.forEach(function(b){
        var deg = b.querySelector('.edu-degree');
        var inst= b.querySelector('.edu-inst');
        var yr  = b.querySelector('.edu-year');
        var rt  = b.querySelector('.edu-rtype');
        var ob  = b.querySelector('.edu-obt');
        var of  = b.querySelector('.edu-outof');
        [deg,inst,yr,rt,ob,of].forEach(function(el){
          if(el) el.classList.remove('is-invalid');
        });

        if(!deg || !deg.value){
          if(deg){deg.classList.add('is-invalid');}
          hasErr=true;
        }
        if(!inst || !inst.value.trim()){
          if(inst){inst.classList.add('is-invalid');}
          hasErr=true;
        }

        var yearVal = yr && yr.value;
        if(yearVal){
          if(!rt || !rt.value){
            if(rt){rt.classList.add('is-invalid');}
            hasErr=true;
          }
          if(!ob || !ob.value.trim()){
            if(ob){ob.classList.add('is-invalid');}
            hasErr=true;
          }
          if(!of || !of.value.trim()){
            if(of){of.classList.add('is-invalid');}
            hasErr=true;
          }
        }else{
          if(rt) rt.classList.remove('is-invalid');
          if(ob) ob.classList.remove('is-invalid');
          if(of) of.classList.remove('is-invalid');
        }
      });
      if(hasErr){
        ev.preventDefault();
        firstInvalidFocus3();
      }
    });
  }
  wireStep3();

  // =========================================================
  // Step-4: Software & Skills
  // =========================================================
  var form4 = document.getElementById('entrepreneur-step4');
  function wireStep4(){
    if(!form4) return;

    var softPills = form4.querySelector('#soft-pills');
    var softGrid  = form4.querySelector('#soft-years-grid');
    var softHidden= form4.querySelector('#softwares_json');
    var softErr   = form4.querySelector('#soft-err');
    var skillPills = form4.querySelector('#skill-pills');
    var skillsHidden = form4.querySelector('#skills_json');

    if(softGrid && softPills){
      softGrid.querySelectorAll('[data-soft-years-name]').forEach(function(n){
        var id = n.getAttribute('data-soft-years-name');
        var chk = softPills.querySelector('[data-soft-id="'+id+'"] input.soft-check');
        var yearsBox = softGrid.querySelector('[data-soft-years-input="'+id+'"]');
        if(chk && chk.checked){
          n.classList.remove('hidden');
          if(yearsBox) yearsBox.classList.remove('hidden');
        } else {
          n.classList.add('hidden');
          if(yearsBox) yearsBox.classList.add('hidden');
        }
      });
    }

    if(softPills){
      softPills.addEventListener('click', function(ev){
        var pill = ev.target.closest('.pill');
        if(!pill || !pill.hasAttribute('data-soft-id')) return;
        var id = pill.getAttribute('data-soft-id');
        var input = pill.querySelector('.soft-check');
        input.checked = !input.checked;
        pill.classList.toggle('selected', input.checked);
        var nameEl = softGrid.querySelector('[data-soft-years-name="'+id+'"]');
        var inpEl  = softGrid.querySelector('[data-soft-years-input="'+id+'"]');
        if(nameEl){ nameEl.classList.toggle('hidden', !input.checked); }
        if(inpEl){ inpEl.classList.toggle('hidden', !input.checked); }
      });
    }

    function buildSoftPayload(){
      var out = [];
      if(!softPills) return out;
      softPills.querySelectorAll('.soft-check').forEach(function(chk){
        if(chk.checked){
          var id = chk.value;
          var yEl = softGrid.querySelector('[data-soft-years-input="'+id+'"] .soft-years-inp');
          var years = yEl ? (yEl.value||'').trim() : '';
          out.push({id:id, years:years});
        }
      });
      return out;
    }

    if(skillPills){
      skillPills.addEventListener('click', function(ev){
        var pill = ev.target.closest('.pill');
        if(!pill || !pill.hasAttribute('data-skill-id')) return;
        var input = pill.querySelector('.skill-check');
        input.checked = !input.checked;
        pill.classList.toggle('selected', input.checked);
      });
    }

    function buildSkillsPayload(){
      var out=[];
      if(!skillPills) return out;
      skillPills.querySelectorAll('.skill-check:checked').forEach(function(c){ out.push(c.value); });
      return out;
    }

    function firstInvalidFocus4(){
      var el = form4.querySelector('.is-invalid');
      if(el){
        try{ el.focus({preventScroll:false}); }catch(e){}
        el.scrollIntoView({behavior:'smooth',block:'center'});
      }
    }

    form4.addEventListener('submit', function(ev){
      if(softErr) softErr.classList.add('hidden');
      form4.querySelectorAll('.soft-years-inp').forEach(function(e){ e.classList.remove('is-invalid'); });

      var softPayload = buildSoftPayload();
      var skillPayload= buildSkillsPayload();
      if(softHidden) softHidden.value = JSON.stringify(softPayload);
      if(skillsHidden) skillsHidden.value = JSON.stringify(skillPayload);

      form4.querySelectorAll('input[data-soft-hidden="1"]').forEach(function(x){ x.remove(); });
      form4.querySelectorAll('input[data-skill-hidden="1"]').forEach(function(x){ x.remove(); });
      form4.querySelectorAll('input[data-softid-hidden="1"]').forEach(function(x){ x.remove(); });
      form4.querySelectorAll('input[data-year-hidden="1"]').forEach(function(x){ x.remove(); });

      softPayload.forEach(function(s){
        var hSoft = document.createElement('input');
        hSoft.type = 'hidden';
        hSoft.name = 'softwares[]';
        hSoft.value = s.id;
        hSoft.setAttribute('data-soft-hidden','1');
        form4.appendChild(hSoft);

        var hIds = document.createElement('input');
        hIds.type = 'hidden';
        hIds.name = 'software_ids[]';
        hIds.value = s.id;
        hIds.setAttribute('data-softid-hidden','1');
        form4.appendChild(hIds);

        var hYears = document.createElement('input');
        hYears.type = 'hidden';
        hYears.name = 'years['+s.id+']';
        hYears.value = s.years;
        hYears.setAttribute('data-year-hidden','1');
        form4.appendChild(hYears);
      });
      skillPayload.forEach(function(id){
        var h = document.createElement('input');
        h.type = 'hidden';
        h.name = 'skills[]';
        h.value = id;
        h.setAttribute('data-skill-hidden','1');
        form4.appendChild(h);
      });

      var anySoft = softPayload.length>0;
      var softBad=false;
      if(anySoft){
        softPayload.forEach(function(s){
          var box = softGrid.querySelector('[data-soft-years-input="'+s.id+'"] .soft-years-inp');
          var val = (s.years||'').trim();
          if(!val || isNaN(val) || Number(val) <= 0){
            softBad = true;
            if(box){ box.classList.add('is-invalid'); }
          }
        });
      }
      if(softBad){
        ev.preventDefault();
        if(softErr) softErr.classList.remove('hidden');
        firstInvalidFocus4();
      }
    });
  }
  wireStep4();

  // =========================================================
  // Step-5: Preferred Areas
  // =========================================================
  var form5 = document.getElementById('entrepreneur-step5');
  function wireStep5(){
    if(!form5) return;
    var taskPills = form5.querySelector('#task-pills');
    var tasksHidden = form5.querySelector('#tasks_json');
    if(!taskPills) return;

    taskPills.addEventListener('click', function(ev){
      var pill = ev.target.closest('.pill');
      if(!pill || !pill.hasAttribute('data-task-id')) return;
      var input = pill.querySelector('.task-check');
      input.checked = !input.checked;
      pill.classList.toggle('selected', input.checked);
    });

    form5.addEventListener('submit', function(){
      var out=[];
      taskPills.querySelectorAll('.task-check:checked').forEach(function(c){ out.push(c.value); });
      if(tasksHidden) tasksHidden.value = JSON.stringify(out);

      form5.querySelectorAll('input[data-task-hidden="1"]').forEach(function(x){ x.remove(); });
      out.forEach(function(id){
        var h = document.createElement('input');
        h.type = 'hidden';
        h.name = 'tasks[]';
        h.value = id;
        h.setAttribute('data-task-hidden','1');
        form5.appendChild(h);
      });
    });
  }
  wireStep5();

  // =========================================================
  // Step-6: Training Required (TomSelect-aware, preserves selections)
  // =========================================================
  var form6 = document.getElementById('entrepreneur-step6');
  function wireStep6(){
    if(!form6) return;
    var list = form6.querySelector('#train-list');
    var tpl  = form6.querySelector('#train-template');
    if(!list || !tpl) return;

    function ensureTomSelect(el){
      if(!window.TomSelect) return;
      if(!el || el.tomselect) return;
      new TomSelect(el, {
        create:false,
        allowEmptyOption:true,
        maxOptions:1000,
        sortField:{field:'text',direction:'asc'}
      });
    }

    function initBlock(block){
      if(!block || block._wired) return;
      block._wired = true;

      var catSel = block.querySelector('.train-cat');
      var trSel  = block.querySelector('.train-opt');

      if(catSel) ensureTomSelect(catSel);
      if(trSel) ensureTomSelect(trSel);

      if(trSel && !trSel._allOptions){
        var opts = [];
        Array.prototype.slice.call(trSel.options).forEach(function(o){
          if(!o.value) return;
          opts.push({
            value:o.value,
            text:o.textContent || '',
            cat:o.getAttribute('data-cat') || ''
          });
        });
        trSel._allOptions = opts;
      }

      function filterTrainings(){
        if(!trSel || !trSel._allOptions) return;
        var catVal = catSel ? (catSel.tomselect ? catSel.tomselect.getValue() : catSel.value) : '';
        var inst = trSel.tomselect;
        var prev = inst ? inst.getValue() : trSel.value;
        if(inst){
          inst.clear(true);
          inst.clearOptions();
          inst.addOption({value:'', text:'Select Training'});
          trSel._allOptions.forEach(function(o){
            if(!catVal || !o.cat || String(o.cat)===String(catVal)){
              inst.addOption({value:o.value, text:o.text});
            }
          });
          inst.refreshOptions(false);
          if(prev){
            inst.setValue(prev, true);
          }
        }else{
          var html = '<option value="">Select Training</option>';
          trSel._allOptions.forEach(function(o){
            if(!catVal || !o.cat || String(o.cat)===String(catVal)){
              html+='<option value="'+o.value+'">'+o.text+'</option>';
            }
          });
          trSel.innerHTML = html;
          if(prev){
            trSel.value = prev;
          }
        }
      }

      if(catSel){
        if(catSel.tomselect){
          catSel.tomselect.on('change', filterTrainings);
        }else{
          catSel.addEventListener('change', filterTrainings);
        }
      }

      filterTrainings();
    }

    Array.prototype.slice.call(list.querySelectorAll('.rec-block')).forEach(initBlock);

    function renumberTrain(){
      var blocks = list.querySelectorAll('.rec-block');
      blocks.forEach(function(b, idx){
        var cur = b.getAttribute('data-train-index');
        if(String(cur)===String(idx)) return;
        b.setAttribute('data-train-index', idx);
        b.querySelectorAll('[name]').forEach(function(n){
          var nn = n.getAttribute('name');
          if(!nn) return;
          nn = nn
            .replace(/\btrain\[\d+\]/,'train['+idx+']')
            .replace(/train\[\__IDX__\]/,'train['+idx+']');
          n.setAttribute('name', nn);
        });
        b.querySelectorAll('[data-link]').forEach(function(n){
          n.setAttribute('data-link', String(idx));
        });
      });
    }

    list.addEventListener('click', function(ev){
      var addBtn = ev.target.closest('.btn-add-train');
      var rmBtn  = ev.target.closest('.btn-remove-train');
      if(addBtn){
        var html = tpl.innerHTML.replace(/__IDX__/g, Date.now());
        var tmp = document.createElement('div');
        tmp.innerHTML = html.trim();
        var block = tmp.firstElementChild;
        list.appendChild(block);
        initBlock(block);
        renumberTrain();
        return;
      }
      if(rmBtn){
        var blocks = list.querySelectorAll('.rec-block');
        var blk = rmBtn.closest('.rec-block');
        if(!blk) return;
        if(blocks.length<=1){
          blk.querySelectorAll('select').forEach(function(s){
            if(s.tomselect){
              s.tomselect.clear(true);
            }else{
              s.value='';
            }
          });
          return;
        }
        blk.remove();
        renumberTrain();
      }
    });
  }
  wireStep6();
});
</script>
@endverbatim
@endpush
