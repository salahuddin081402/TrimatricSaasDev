@extends('backend.layouts.master')
{{-- TMX-CR | Client Interior Requisition | Single Blade (Create + Edit) | Phase-1 --}}
@push('styles')
<style>
/* ---------- Layout ---------- */
.cr-wrap{max-width:1200px;margin:0 auto;padding:0 12px}
.cr-card{border:1px solid #e5e7eb;border-radius:16px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.04)}
.cr-card-header{padding:12px 16px;font-weight:700;border-bottom:1px solid #eef2f7}
.cr-card-body{padding:16px}
.cr-muted{color:#6b7280}

/* Top info strip */
.cr-info-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.cr-info-item{border:1px dashed #e5e7eb;border-radius:12px;padding:10px 12px;background:#fafafa}
.cr-info-item .k{font-size:12px;color:#6b7280;margin-bottom:2px}
.cr-info-item .v{font-weight:700;color:#111827;word-break:break-word}
@media(max-width:992px){
}

/* ---------- Horizontal swipe cards ---------- */
.cr-strip{display:flex;gap:12px;overflow-x:auto;padding:2px 2px 10px;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch}
.cr-strip::-webkit-scrollbar{display:none}
.cr-pcard{min-width:220px;border:2px solid #1f2937;border-radius:16px;background:#0b0f14;color:#39ff14;padding:12px;cursor:pointer;position:relative;transition:.15s ease;box-shadow:0 10px 24px rgba(0,0,0,.22);}
.cr-pcard:hover{box-shadow:0 10px 22px rgba(0,0,0,.08)}
.cr-pcard.selected{border-color:#7c2d12;box-shadow:0 10px 24px rgba(124,45,18,.25)}
.cr-pcard .tick{
  display:none; position:absolute; top:8px; right:8px;
  width:24px; height:24px; border-radius:999px;
  background:#16a34a; color:#fff; font-weight:800;
  font-size:14px; line-height:24px; text-align:center;
}
.cr-pcard.selected .tick{display:block}
.cr-pcard .img{height:88px;border-radius:12px;background:linear-gradient(135deg, rgba(57,255,20,.16), rgba(255,255,255,.05));display:flex;align-items:center;justify-content:center;color:#39ff14;font-size:12px;margin-bottom:10px;border:1px dashed rgba(57,255,20,.35)}
.cr-pcard .img img{width:100%;height:100%;object-fit:cover;display:block}
.cr-pcard .name{font-weight:800;font-size:15px;letter-spacing:.2px;color:#39ff14}
.cr-pcard .desc{font-size:12px;color:#39ff14;opacity:.85}

/* ---------- Slider arrows ---------- */
.cr-strip-wrap{position:relative;overflow:visible}
.cr-navbtn{position:absolute;top:50%;transform:translateY(-50%);width:36px;height:36px;border-radius:50%;border:1px solid rgba(255,255,255,.14);background:#111827;color:#39ff14;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 8px 18px rgba(0,0,0,.35);z-index:3;}
.cr-navbtn:active{transform:scale(.98)}
.cr-navbtn[disabled]{opacity:.45;box-shadow:none}
.cr-nav-left{position:absolute;left:6px;top:50%;transform:translateY(-50%)}
.cr-nav-right{position:absolute;right:6px;top:50%;transform:translateY(-50%)}
.cr-strip{padding-left:48px;padding-right:48px}

/* ---------- Attachments ---------- */
.cr-att-actions{display:flex;gap:10px;flex-wrap:wrap}
.cr-att-preview{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}
.cr-att-thumb{
  width:110px; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; background:#fff;
}
.cr-att-thumb img{width:100%;height:86px;object-fit:cover;display:block}
.cr-att-thumb .cap{padding:6px 8px;font-size:11px;color:#6b7280;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
</style>
@endpush

@section('content')
@php
  $ctx = $ctx ?? [];
  $mode = $mode ?? 'create';
  $regId = $ctx['reg_id'] ?? null;
  $clientUserId = $ctx['client_user_id'] ?? null;
  $clientName = $ctx['client_name'] ?? null;
  $clientPhone = $ctx['client_phone'] ?? null;
  $clusterDisplay = $ctx['cluster_display'] ?? null;
  $clusterAdminPhone = $ctx['cluster_admin_phone'] ?? null;

  $canClusterRemark = $canClusterRemark ?? false;
  $canHeadOfficeRemark = $canHeadOfficeRemark ?? false;

  // Project detail old values (edit + validation)
  $vAddress = old('project_address', $ctx['project_address'] ?? '');
  $vBudget = old('project_budget', $ctx['project_budget'] ?? '');
  $vEta = old('expected_delivery_date', $ctx['expected_delivery_date'] ?? '');
  $vTotalSqft = old('project_total_sqft', $ctx['project_total_sqft'] ?? '');

  $vClusterRemark = old('cluster_member_remark', $ctx['cluster_member_remark'] ?? '');
  $vHoRemark = old('head_office_remark', $ctx['head_office_remark'] ?? '');

  // Attachments (edit + old input)
  $existingAttachments = $existingAttachments ?? ($ctx['attachments'] ?? []);
@endphp

<div class="cr-wrap">
  {{-- Cluster help message --}}
  <div class="alert alert-info mb-3">
    To assist you in Fill-up the Requisition, You may contact to Your Nearest Cluster Admin.
    Mobile: <strong>{{ $clusterAdminPhone ?: 'N/A' }}</strong>.
    He will give you support until your Project/Requisition Completion.
  </div>

  {{-- Top display-only info --}}
  <div class="cr-card mb-3">
    <div class="cr-card-body">
      <div class="cr-info-grid">
        <div class="cr-info-item">
          <div class="k">Reg ID</div>
          <div class="v">{{ $regId ?: '-' }}</div>
        </div>
        <div class="cr-info-item">
          <div class="k">Client User ID</div>
          <div class="v">{{ $clientUserId ?: 'Not Registered' }}</div>
        </div>
        <div class="cr-info-item">
          <div class="k">Client Name</div>
          <div class="v">{{ $clientName ?: '-' }}</div>
        </div>
        <div class="cr-info-item">
          <div class="k">Client Phone</div>
          <div class="v">{{ $clientPhone ?: '-' }}</div>
        </div>
        <div class="cr-info-item">
          <div class="k">Cluster</div>
          <div class="v">{{ $clusterDisplay ?: '-' }}</div>
        </div>
      </div>
    </div>
  </div>

  {{-- FORM (Phase-1: Step-1 fields + Phase-1 card wiring hooks) --}}
  <form method="POST" action="{{ $formAction ?? request()->url() }}" enctype="multipart/form-data" id="crForm" novalidate>
    @csrf
    @if(!empty($formMethod) && strtoupper($formMethod) !== 'POST')
      @method($formMethod)
    @endif

    {{-- Project Details --}}
    <div class="cr-card mb-3" id="step-project">
      <div class="cr-card-header">Project Details <span class="cr-muted">(Mandatory)</span></div>
      <div class="cr-card-body">

        {{-- Server errors (top) --}}
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

        <div class="mb-3">
          <label class="form-label fw-bold">Project Location / Address <span class="text-danger">*</span></label>
          <textarea name="project_address" class="form-control @error('project_address') is-invalid @enderror" rows="2" required>{{ $vAddress }}</textarea>
          @error('project_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label fw-bold">Total SQFT of the Entire Project <span class="text-danger">*</span></label>
            <input type="text" name="project_total_sqft" class="form-control @error('project_total_sqft') is-invalid @enderror" id="project_total_sqft"
                   value="{{ $vTotalSqft }}" readonly placeholder="Auto calculated">
            @error('project_total_sqft')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">Auto calculated from selected Spaces (sum of space_total_sqft).</div>
          </div>

          <div class="col-md-4 mb-3">
            <label class="form-label fw-bold">Budget (Approximate) <span class="text-danger">*</span></label>
            <input type="text" name="project_budget" class="form-control @error('project_budget') is-invalid @enderror" value="{{ $vBudget }}" required>
            @error('project_budget')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="col-md-4 mb-3">
            <label class="form-label fw-bold">Expected Time of Delivery <span class="text-danger">*</span></label>
            <input type="date" name="expected_delivery_date" class="form-control @error('expected_delivery_date') is-invalid @enderror"
                   value="{{ $vEta }}" required>
            @error('expected_delivery_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="row">
          {{-- Cluster Member Remark --}}
          <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">Cluster Member Remark</label>
            @if($canClusterRemark)
              <textarea name="cluster_member_remark" class="form-control @error('cluster_member_remark') is-invalid @enderror" rows="2">{{ $vClusterRemark }}</textarea>
              @error('cluster_member_remark')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @else
              <div class="form-control bg-light" style="min-height:74px">{{ $vClusterRemark ?: '-' }}</div>
            @endif
          </div>

          {{-- Head Office Remark --}}
          <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">Head Office Remark</label>
            @if($canHeadOfficeRemark)
              <textarea name="head_office_remark" class="form-control @error('head_office_remark') is-invalid @enderror" rows="2">{{ $vHoRemark }}</textarea>
              @error('head_office_remark')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @else
              <div class="form-control bg-light" style="min-height:74px">{{ $vHoRemark ?: '-' }}</div>
            @endif
          </div>
        </div>

        {{-- Attachments (Optional) — MUST be after Head Office Remark --}}
        <div class="cr-card mt-2" style="border-radius:14px;border-style:dashed">
          <div class="cr-card-header">Client Attachments (Optional)</div>
          <div class="cr-card-body">
            <input type="file" id="crAttachments" name="attachments[]" accept="image/*" multiple class="d-none">
            <div class="cr-att-actions">
              <button type="button" class="btn btn-outline-primary" id="btnAddMoreImages">
                Add More Images
              </button>
              <button type="button" class="btn btn-outline-secondary" id="btnClearImages">
                Clear Selection
              </button>
            </div>
            <div class="form-text mt-2">
              You can select multiple images from different folders: click <strong>Add More Images</strong> multiple times and pick images each time.
              Allowed: common image types. Max size: <strong>10MB per file</strong>.
            </div>
            @error('attachments')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            @error('attachments.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror

            <div class="cr-att-preview" id="attPreview">
              {{-- Existing attachments preview (edit) --}}
              @if(!empty($existingAttachments) && is_iterable($existingAttachments))
                @foreach($existingAttachments as $a)
                  @php
                    $url = $a['url'] ?? $a['file_url'] ?? $a['path_url'] ?? null;
                    $cap = $a['original_name'] ?? $a['file_name'] ?? basename((string)$url);
                  @endphp
                  @if($url)
                    <div class="cr-att-thumb">
                      <img src="{{ $url }}" alt="attachment">
                      <div class="cap" title="{{ $cap }}">{{ $cap }}</div>
                    </div>
                  @endif
                @endforeach
              @endif
            </div>
          </div>
        </div>

      </div>
    </div>

    <div class="cr-card mb-3" id="layer-type">
  <div class="cr-card-body">
      <div class="d-flex align-items-center justify-content-between">
        <div class="fw-bold">Project Type <span class="text-danger">*</span></div>
        <div class="text-muted small">Swipe horizontally</div>
      </div>
      <div class="cr-strip-wrap mt-2">
        <button type="button" class="cr-navbtn cr-nav-left" id="typePrev" aria-label="Prev">&lt;</button>
        <div class="cr-strip pad-arrows" id="stripType"></div>
        <button type="button" class="cr-navbtn cr-nav-right" id="typeNext" aria-label="Next">&gt;</button>
      </div>
      <input type="hidden" name="project_type_id" id="project_type_id" value="{{ old('project_type_id', $ctx['project_type_id'] ?? '') }}">
      @error('project_type_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    
  </div>
</div>

    <div class="cr-card mb-3" id="layer-subtype">
  <div class="cr-card-body">
      <div class="d-flex align-items-center justify-content-between">
        <div class="fw-bold">Project Sub-Type <span class="text-danger">*</span></div>
        <div class="text-muted small">Depends on Project Type</div>
      </div>
      <div class="cr-strip mt-2" id="stripSubtype"></div>
      <input type="hidden" name="project_subtype_id" id="project_subtype_id" value="{{ old('project_subtype_id', $ctx['project_subtype_id'] ?? '') }}">
      @error('project_subtype_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    
  </div>
</div>

    <div class="cr-card mb-3" id="layer-space">
  <div class="cr-card-body">
      <div class="d-flex align-items-center justify-content-between">
        <div class="fw-bold">Spaces to be Included <span class="text-danger">*</span></div>
        <div class="text-muted small">At least one</div>
      </div>
      <div class="cr-strip mt-2" id="stripSpace"></div>
      @error('spaces')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    
  </div>
</div>

    <div class="cr-card mb-3" id="layer-category">
  <div class="cr-card-body">
      <div class="fw-bold">Categories</div>
      <div class="cr-strip mt-2" id="stripCategory"></div>
    
  </div>
</div>

    <div class="cr-card mb-3" id="layer-subcategory">
  <div class="cr-card-body">
      <div class="fw-bold">Sub-Categories</div>
      <div class="cr-strip mt-2" id="stripSubcategory"></div>
    
  </div>
</div>

    <div class="cr-card mb-3" id="layer-product">
  <div class="cr-card-body">
      <div class="fw-bold">Products</div>
      <div class="cr-strip mt-2" id="stripProduct"></div>
    
  </div>
</div>

    <div class="mt-4 d-flex justify-content-end gap-2">
      <button type="submit" class="btn btn-primary" id="btnSubmit">Save / Submit</button>
    </div>
  </form>
</div>

@push('scripts')
<script>
(function(){
  // ---------- Left nav active state ----------
  const sections = ['layer-type','layer-subtype','layer-space','layer-category','layer-subcategory','layer-product']
    .map(id => document.getElementById(id)).filter(Boolean);

  function setActive(id){
    navLinks.forEach(a => a.classList.toggle('active', a.getAttribute('data-nav') === id));
  }
  const io = new IntersectionObserver((entries)=>{
    entries.forEach(e=>{
      if(e.isIntersecting){ setActive(e.target.id); }
    });
  }, {root:null, threshold:0.4});
  sections.forEach(s => io.observe(s));

  // ---------- Attachments: Add More Images + Clear Selection ----------
  const input = document.getElementById('crAttachments');
  const btnAdd = document.getElementById('btnAddMoreImages');
  const btnClear = document.getElementById('btnClearImages');
  const preview = document.getElementById('attPreview');

  let dt = new DataTransfer();

  function renderPreview(){
    // Keep server-rendered thumbs (edit) if user hasn't selected anything new
    // If user selects new files, we show ONLY newly selected files (clear existing preview)
    preview.innerHTML = '';
    Array.from(dt.files).forEach((f)=>{
      const wrap = document.createElement('div');
      wrap.className = 'cr-att-thumb';
      const img = document.createElement('img');
      const cap = document.createElement('div');
      cap.className = 'cap';
      cap.title = f.name;
      cap.textContent = f.name;

      wrap.appendChild(img);
      wrap.appendChild(cap);
      preview.appendChild(wrap);

      const reader = new FileReader();
      reader.onload = (ev)=>{ img.src = ev.target.result; };
      reader.readAsDataURL(f);
    });
  }

  btnAdd?.addEventListener('click', ()=> input.click());

  input?.addEventListener('change', ()=>{
    const files = Array.from(input.files || []);
    if(!files.length) return;

    // Merge into DataTransfer to preserve previous selections
    files.forEach(f => dt.items.add(f));
    input.files = dt.files;
    renderPreview();
  });

  btnClear?.addEventListener('click', ()=>{
    dt = new DataTransfer();
    input.value = '';
    preview.innerHTML = '';
  });

  // ---------- Project Type layer (Phase-1) ----------
  const projectTypes = @json($projectTypes ?? []);
  const stripType = document.getElementById('stripType');
  const typeId = document.getElementById('project_type_id');
  const typePrev = document.getElementById('typePrev');
  const typeNext = document.getElementById('typeNext');

  function normalizeImg(p){
    if(!p) return '';
    const s = String(p);
    if(/^https?:\/\//i.test(s)) return s;
    if(s.startsWith('/')) return s;
    if(s.startsWith('storage/')) return '/' + s;
    return '/storage/' + s.replace(/^\/+/, '');
  }

  function clearDescendantLayers(){
    // descendant containers remain blank for now
    document.getElementById('stripSubtype')?.replaceChildren();
    document.getElementById('stripSpace')?.replaceChildren();
    document.getElementById('stripCategory')?.replaceChildren();
    document.getElementById('stripSubcategory')?.replaceChildren();
    document.getElementById('stripProduct')?.replaceChildren();
    // hidden ids (future)
    const hid = ['project_subtype_id','selected_spaces_json','selected_categories_json','selected_subcategories_json','selected_products_json'];
    hid.forEach(id=>{ const el = document.getElementById(id); if(el) el.value=''; });
  }

  function setSelectedType(newId){
    if(!typeId) return;
    const current = String(typeId.value || '');
    const incoming = newId == null ? '' : String(newId);

    // deselect if same
    if(incoming && incoming === current){
      typeId.value = '';
      stripType?.querySelectorAll('.cr-pcard.selected').forEach(c=>c.classList.remove('selected'));
      clearDescendantLayers();
      return;
    }

    // select one
    typeId.value = incoming;
    stripType?.querySelectorAll('.cr-pcard').forEach(c=>{
      c.classList.toggle('selected', c.dataset.id === incoming);
    });
    // when switching type, clear descendants (will be rehydrated in future passes)
    clearDescendantLayers();
  }

  function renderProjectTypes(){
    if(!stripType) return;
    stripType.innerHTML = '';

    (projectTypes || []).forEach(row => {
      const id = row.id;
      const name = row.name ?? row.title ?? ('Type #' + id);
      const desc = row.description ?? '';
      const img = normalizeImg(row.card_image_path ?? row.main_image_url ?? row.image_path ?? '');

      const el = document.createElement('div');
      el.className = 'cr-pcard';
      el.dataset.id = String(id);
      el.setAttribute('role', 'button');
      el.setAttribute('tabindex', '0');

      const media = document.createElement('div');
      media.className = 'media';
      if(img){
        media.style.backgroundImage = `url("${img}")`;
      } else {
        media.style.background = 'linear-gradient(135deg, rgba(124,45,18,.18), rgba(6,95,70,.12))';
      }

      const body = document.createElement('div');
      body.className = 'body';

      const title = document.createElement('div');
      title.className = 'title';
      title.textContent = name;

      const d = document.createElement('div');
      d.className = 'desc';
      d.textContent = desc;

      const tick = document.createElement('div');
      tick.className = 'tick';
      tick.textContent = '✓';

      body.appendChild(title);
      if(desc) body.appendChild(d);
      el.appendChild(media);
      el.appendChild(body);
      el.appendChild(tick);

      el.addEventListener('click', ()=> setSelectedType(id));
      el.addEventListener('keydown', (ev)=>{
        if(ev.key === 'Enter' || ev.key === ' '){
          ev.preventDefault();
          setSelectedType(id);
        }
      });

      stripType.appendChild(el);
    });

    // apply selection if already set (edit mode)
    if(typeId && typeId.value){
      setSelectedType(typeId.value);
    }
  }

  typePrev?.addEventListener('click', ()=>{ stripType?.scrollBy({left:-260, behavior:'smooth'}); });
  typeNext?.addEventListener('click', ()=>{ stripType?.scrollBy({left: 260, behavior:'smooth'}); });

  renderProjectTypes();

  // ---------- Client-side validation (Phase-1 mandatory fields) ----------
  const form = document.getElementById('crForm');
  form?.addEventListener('submit', function(e){
    const addr = form.querySelector('[name="project_address"]');
    const budget = form.querySelector('[name="project_budget"]');
    const eta = form.querySelector('[name="expected_delivery_date"]');
    const typeId = document.getElementById('project_type_id');
    const subtypeId = document.getElementById('project_subtype_id');

    // spaces: expect checkboxes named spaces[] or hidden JSON; keep minimal here
    const spaceSelected = form.querySelectorAll('[name="spaces[]"]:checked').length > 0
      || (document.querySelectorAll('#stripSpace .cr-pcard.selected').length > 0);

    let firstBad = null;

    function mark(el, ok){
      if(!el) return;
      if(ok){ el.classList.remove('is-invalid'); }
      else { el.classList.add('is-invalid'); firstBad = firstBad || el; }
    }

    mark(addr, !!(addr && addr.value.trim()));
    mark(budget, !!(budget && budget.value.trim()));
    mark(eta, !!(eta && eta.value));
    mark(typeId, !!(typeId && typeId.value));
    mark(subtypeId, !!(subtypeId && subtypeId.value));

    if(!spaceSelected){
      // soft alert (Bootstrap)
      const existing = document.getElementById('crSpaceErr');
      if(!existing){
        const div = document.createElement('div');
        div.id = 'crSpaceErr';
        div.className = 'alert alert-danger mt-2';
        div.textContent = 'Please select at least one Space.';
        document.getElementById('layer-space')?.appendChild(div);
      }
      firstBad = firstBad || document.getElementById('layer-space');
    } else {
      const existing = document.getElementById('crSpaceErr');
      if(existing) existing.remove();
    }

    if(firstBad){
      e.preventDefault();
      firstBad.scrollIntoView({behavior:'smooth', block:'center'});
    }
  });

  // Descendant layers will be implemented in the next passes.
})();
</script>
@endpush
@endsection