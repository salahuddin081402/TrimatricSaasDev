{{-- TMX-RMGM | registration/Registration_Management/pdf_resume.blade.php | v1.8 final --}}
@php
  /** Controller provides:
   *  $registration (stdClass with StudlyCase aliases: Full_Name, Gender, DOB, Phone, Email, Present_Address, Notes)
   *  $education, $job_experiences, $software_expertise, $skills, $preferred_areas, $training_required (Collections)
   *  $roleRow (nullable {id,name})
   */

  $rm    = $registration ?? null;
  $edu   = collect($education ?? []);
  $jobs  = collect($job_experiences ?? []);
  $soft  = collect($software_expertise ?? []);
  $skls  = collect($skills ?? []);
  $prefs = collect($preferred_areas ?? []);
  $trng  = collect($training_required ?? []);

  $safe    = fn($v,$d='—') => (isset($v) && $v!=='') ? $v : $d;
  $fmtDate = fn($d) => ($d ? \Carbon\Carbon::parse($d)->format('d-M-Y') : '—');

  // Public file helper (images saved under public/assets/images/{country}/{slug}/registration/…)
  $imgUrl = function($rel){
    if(!$rel) return null;
    $rel = ltrim($rel,'/');
    return asset($rel);
  };

  // ---- Resolve image paths once, and prevent swaps/duplication ----
  $photoPath     = $imgUrl($rm->Photo ?? null); // Header must use ONLY rm->Photo
  $nidFrontPath  = $imgUrl($rm->NID_Photo_Front_Page ?? null);
  $nidBackPath   = $imgUrl($rm->NID_Photo_Back_Page ?? null);
  $birthPath     = $imgUrl($rm->Birth_Certificate_Photo ?? null);

  // If any identity path is accidentally same as Photo, suppress it in Identity section
  $same = function($a,$b){ return $a && $b && (trim($a) === trim($b)); };
  if ($same($nidFrontPath, $photoPath))  $nidFrontPath = null;
  if ($same($nidBackPath,  $photoPath))  $nidBackPath  = null;
  if ($same($birthPath,    $photoPath))  $birthPath    = null;

@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>
  Company{{ $rm->company_id ?? '' }}_User{{ $rm->user_id ?? '' }}_
  {{ \Illuminate\Support\Str::slug($rm->user_name ?? $rm->Full_Name ?? 'resume','_') }}
</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  :root{
    --ink:#273a56; --sub:#5a6b85; --hl:#2d5be3;
    --ok:#22a06b; --muted:#7b8794; --br:#e5e7eb; --bg:#ffffff; --card:#fff; --row:#f7fbff;
  }
  html,body{background:#fff;color:var(--ink);font:14px/1.5 system-ui,-apple-system,Segoe UI,Roboto,Arial}
  .page{max-width:900px;margin:0 auto;padding:20px 18px}
  .card{background:var(--card);border:1px solid var(--br);border-radius:12px;box-shadow:0 6px 18px -10px rgba(0,0,0,.12);margin-top:14px}

  /* Header visuals */
  .hdr{display:flex;gap:16px;align-items:center;padding:18px}
  .hdr .meta{flex:1}
  .hdr h1{margin:0;font-size:22px}
  .hdr .sub{color:var(--sub);font-size:13px}
  .hdr .idline{margin-top:6px;color:var(--muted);font-size:12px}
  /* Header photo should be framed — keep cover */
  .photo-lg{width:96px;height:96px;border-radius:10px;object-fit:cover;border:1px solid var(--br);background:#f8fafc}

  .sec .title{padding:10px 14px;border-bottom:1px solid var(--br);font-weight:700}
  .sec .body{padding:12px 14px}
  .grid2{display:grid;grid-template-columns:1fr 1fr;gap:8px}
  .kv{display:flex;gap:8px}
  .kv .k{min-width:150px;color:var(--muted)}
  table{width:100%;border-collapse:collapse}
  th,td{border-bottom:1px solid var(--br);padding:8px 6px;text-align:left;vertical-align:top}
  th{background:#eef3ff;font-weight:700;font-size:12px}
  tbody tr:nth-child(odd) td{background:var(--row)}
  .pill{display:inline-block;margin:4px 6px 0 0;padding:4px 8px;border:1px solid var(--br);border-radius:999px;font-size:12px;color:#334155;background:#f8fafc}
  .muted{color:var(--muted)}

  /* Identity thumbnails: show FULL image, no crop.
     - Fixed height (96px), width auto so wide images aren't cut.
     - object-fit: contain keeps the whole image.
     - A padded frame prevents edges from touching the border. */
  .id-item{display:inline-block}
  .id-label{font-size:12px;color:var(--muted);margin-bottom:4px}
  .id-frame{
    display:inline-flex;align-items:center;justify-content:center;
    border:1px solid var(--br);border-radius:10px;background:#fff;
    padding:6px; /* give breathing room for wide images */
    max-width:100%;
  }
  .id-thumb{
    height:96px; width:auto; max-width:100%;
    object-fit:contain; /* <- key change */
    border-radius:6px;
    background:#fff;
    display:block;
  }

  @media print{
    body{background:#fff}
    .page{margin:0;padding:0;box-shadow:none}
    .card{border:0;box-shadow:none}
    a{color:inherit;text-decoration:none}
    .hdr{padding:12px}
  }
</style>
</head>
<body>
<div class="page">

  <!-- Header -->
  <div class="card hdr">
    <img class="photo-lg" src="{{ $photoPath ?? 'about:blank' }}" alt="Photo">
    <div class="meta">
      <h1>{{ $safe($rm->Full_Name, $rm->user_name ?? '—') }}</h1>
      <div class="sub">
        {{ ucfirst($safe($rm->registration_type,'—')) }}
        @if(isset($roleRow) && $roleRow && $roleRow->id) • {{ $safe($roleRow->name,'Role') }} @endif
      </div>
      <div class="idline">
        Company ID: <b>{{ $rm->company_id ?? '—' }}</b> • User ID: <b>{{ $rm->user_id ?? '—' }}</b> • Registration ID: <b>{{ $rm->id ?? '—' }}</b>
      </div>
      <div class="idline">
        Approval: <b>{{ ucfirst($safe($rm->approval_status,'—')) }}</b>
        @if(isset($roleRow) && $roleRow && $roleRow->id) • Role ID: <b>{{ $roleRow->id }}</b>@endif
        • Reg Type: <b>{{ $safe($rm->registration_type,'—') }}</b>
      </div>
    </div>
  </div>

  <!-- Profile -->
  <div class="card sec">
    <div class="title">Profile</div>
    <div class="body grid2">
      <div class="kv"><div class="k">Full Name</div><div class="v">{{ $safe($rm->Full_Name, $rm->user_name ?? '—') }}</div></div>
      <div class="kv"><div class="k">Gender</div><div class="v">{{ $rm->Gender ? ucfirst($rm->Gender) : '—' }}</div></div>
      <div class="kv"><div class="k">Date of Birth</div><div class="v">{{ $fmtDate($rm->DOB ?? null) }}</div></div>
      <div class="kv"><div class="k">Phone</div><div class="v">{{ $safe($rm->Phone) }}</div></div>
      <div class="kv"><div class="k">Email</div><div class="v">{{ $safe($rm->Email ?? $rm->user_email ?? '') }}</div></div>
      <div class="kv"><div class="k">NID</div><div class="v">{{ $safe($rm->NID) }}</div></div>
      <div class="kv"><div class="k">Present Address</div><div class="v">{{ $safe($rm->Present_Address) }}</div></div>
      <div class="kv"><div class="k">Geography</div>
        <div class="v">{{ $safe($rm->division_name) }} • {{ $safe($rm->district_name) }} • {{ $safe($rm->upazila_name) }}</div>
      </div>
      <div class="kv"><div class="k">Profession</div><div class="v">{{ $safe($rm->profession_name) }}</div></div>
      <div class="kv"><div class="k">Notes</div><div class="v muted">{{ $safe($rm->Notes ?? '') }}</div></div>
    </div>
  </div>

  <!-- Identity Docs -->
  @if($nidFrontPath || $nidBackPath || $birthPath)
  <div class="card sec">
    <div class="title">Identity Documents</div>
    <div class="body" style="display:flex;gap:16px;flex-wrap:wrap;align-items:flex-start">
      @if($nidFrontPath)
        <div class="id-item">
          <div class="id-label">NID Front</div>
          <div class="id-frame">
            <img class="id-thumb" src="{{ $nidFrontPath }}" alt="NID Front">
          </div>
        </div>
      @endif
      @if($nidBackPath)
        <div class="id-item">
          <div class="id-label">NID Back</div>
          <div class="id-frame">
            <img class="id-thumb" src="{{ $nidBackPath }}" alt="NID Back">
          </div>
        </div>
      @endif
      @if($birthPath)
        <div class="id-item">
          <div class="id-label">Birth Certificate</div>
          <div class="id-frame">
            <img class="id-thumb" src="{{ $birthPath }}" alt="Birth Certificate">
          </div>
        </div>
      @endif
    </div>
  </div>
  @endif

  <!-- Education -->
  <div class="card sec">
    <div class="title">Education</div>
    <div class="body">
      @if($edu->count())
        <table>
          <thead>
            <tr><th>Degree</th><th>Institution</th><th>Result</th><th>Out of</th><th>Year</th></tr>
          </thead>
          <tbody>
          @foreach($edu as $e)
            <tr>
              <td>{{ $safe($e->degree_name) }}</td>
              <td>{{ $safe($e->institute_name) }}</td>
              <td>{{ $safe($e->result) }} <span class="muted">({{ $safe($e->result_type) }})</span></td>
              <td>{{ $safe($e->out_of) }}</td>
              <td>{{ $safe($e->passing_year) }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      @else
        <div class="muted">No education records.</div>
      @endif
    </div>
  </div>

  <!-- Experience -->
  <div class="card sec">
    <div class="title">Experience</div>
    <div class="body">
      @if($jobs->count())
        <table>
          <thead><tr><th>Employer</th><th>Title</th><th>From</th><th>To</th></tr></thead>
          <tbody>
          @foreach($jobs as $j)
            <tr>
              <td>{{ $safe($j->employer) }}</td>
              <td>{{ $safe($j->designation) }}</td>
              <td>{{ $fmtDate($j->start_date ?? null) }}</td>
              <td>{{ ($j->is_present_job === 'Y') ? 'Present' : $fmtDate($j->end_date ?? null) }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      @else
        <div class="muted">No job experience records.</div>
      @endif
    </div>
  </div>

  <!-- Software Expertise -->
  <div class="card sec">
    <div class="title">Software Expertise</div>
    <div class="body">
      @if($soft->count())
        <table>
          <thead><tr><th>Software</th><th>Experience (years)</th></tr></thead>
          <tbody>
          @foreach($soft as $s)
            <tr>
              <td>{{ $safe($s->software_name) }}</td>
              <td>{{ $safe($s->experience_in_years) }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      @else
        <div class="muted">No software expertise records.</div>
      @endif
    </div>
  </div>

  <!-- Skills -->
  <div class="card sec">
    <div class="title">Skills</div>
    <div class="body">
      @if($skls->count())
        @foreach($skls as $s)
          <span class="pill">{{ $safe($s->skill_name) }}</span>
        @endforeach
      @else
        <div class="muted">No skills provided.</div>
      @endif
    </div>
  </div>

  <!-- Preferred Areas -->
  <div class="card sec">
    <div class="title">Preferred Areas</div>
    <div class="body">
      @if($prefs->count())
        @foreach($prefs as $p)
          <span class="pill">{{ $safe($p->task_param_name) }}</span>
        @endforeach
      @else
        <div class="muted">No preferred areas selected.</div>
      @endif
    </div>
  </div>

  <!-- Training -->
  <div class="card sec">
    <div class="title">Training</div>
    <div class="body">
      @if($trng->count())
        <table>
          <thead><tr><th>Category</th><th>Training</th></tr></thead>
          <tbody>
          @foreach($trng as $t)
            <tr>
              <td>{{ $safe($t->training_category_name) }}</td>
              <td>{{ $safe($t->training_name) }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      @else
        <div class="muted">No training entries.</div>
      @endif
    </div>
  </div>

  <!-- Footer -->
  <div class="card sec" style="text-align:center;color:var(--muted);font-size:12px;box-shadow:none">
    Generated on {{ now()->format('d-M-Y H:i:s') }} from registration_master and related tables. For internal use.
  </div>
</div>
</body>
</html>
