{{-- backend/dev/jurisdiction-test.blade.php --}}
@extends('backend.layouts.master')

@section('content')
<div class="container-fluid py-3">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Jurisdiction Filter Demo</h5>
    <a href="" class="btn btn-sm btn-outline-secondary" id="btn-reset">Reset</a>
  </div>

  {{-- The reusable filter bar (Division → District → Cluster) --}}
  @include('backend.layouts.partials.filterbar', [
      'showCluster' => true,
      'defaults' => [
          'division_id' => request('division_id'),
          'district_id' => request('district_id'),
          'cluster_id'  => request('cluster_id'),
      ],
  ])

  {{-- Live preview cards --}}
  <div class="row g-3 mt-2">
    <div class="col-12 col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header py-2"><strong>Selected Values</strong></div>
        <div class="card-body">
          <div class="row g-2">
            <div class="col-12 col-md-4">
              <label class="form-label">Division ID</label>
              <input id="out-division" class="form-control" readonly>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">District ID</label>
              <input id="out-district" class="form-control" readonly>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Cluster ID</label>
              <input id="out-cluster" class="form-control" readonly>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <div class="card shadow-sm">
        <div class="card-header py-2"><strong>Endpoint Snapshot</strong></div>
        <div class="card-body">
          <small class="text-muted d-block mb-2">Shows the JSON returned by the AJAX endpoints for your current selections.</small>
          <div class="mb-2">
            <code>/backend/ajax/geo/divisions</code>
            <pre class="bg-light p-2 small" id="json-divisions" style="max-height:160px; overflow:auto;"></pre>
          </div>
          <div class="mb-2">
            <code>/backend/ajax/geo/districts?division_id=…</code>
            <pre class="bg-light p-2 small" id="json-districts" style="max-height:160px; overflow:auto;"></pre>
          </div>
          <div>
            <code>/backend/ajax/geo/clusters?district_id=…</code>
            <pre class="bg-light p-2 small" id="json-clusters" style="max-height:160px; overflow:auto;"></pre>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
(function(){
  // Inputs from the filterbar partial
  const $division = document.getElementById('division_id');
  const $district = document.getElementById('district_id');
  const $cluster  = document.getElementById('cluster_id');

  // Outputs on the right
  const outDiv = document.getElementById('out-division');
  const outDis = document.getElementById('out-district');
  const outClu = document.getElementById('out-cluster');

  // JSON snapshots
  const jsDiv = document.getElementById('json-divisions');
  const jsDis = document.getElementById('json-districts');
  const jsClu = document.getElementById('json-clusters');

  // Endpoints (from the filterbar's data attribute)
  const eps = JSON.parse(document.getElementById('filterbar').dataset.endpoints || '{}');

  function val(el){ return el ? (el.value || '') : ''; }
  function put(el, txt){ if (el) el.value = txt || ''; }
  function code(el, obj){ if (el) el.textContent = JSON.stringify(obj ?? [], null, 2); }

  async function fetchJSON(url){
    try {
      const res = await fetch(url, { headers: {'X-Requested-With':'XMLHttpRequest'} });
      if (!res.ok) return [];
      return res.json();
    } catch(e) { return []; }
  }

  async function refreshSnapshots(){
    // Divisions
    const divs = await fetchJSON(eps.divisions);
    code(jsDiv, divs);

    // Districts (only if a division is chosen)
    const dId = val($division);
    const dists = dId ? await fetchJSON(`${eps.districts}?division_id=${dId}`) : [];
    code(jsDis, dists);

    // Clusters (only if a district is chosen)
    const disId = val($district);
    const clus = disId ? await fetchJSON(`${eps.clusters}?district_id=${disId}`) : [];
    code(jsClu, clus);
  }

  function syncOutputs(){
    put(outDiv, val($division));
    put(outDis, val($district));
    put(outClu, val($cluster));
  }

  // Listen to changes from the filterbar
  $division?.addEventListener('change', () => { syncOutputs(); refreshSnapshots(); });
  $district?.addEventListener('change', () => { syncOutputs(); refreshSnapshots(); });
  $cluster ?.addEventListener('change',  () => { syncOutputs(); });

  // Reset button
  document.getElementById('btn-reset')?.addEventListener('click', (e) => {
    e.preventDefault();
    if ($division) { if ($division.tomselect) $division.tomselect.clear(); else $division.value = ''; $division.dispatchEvent(new Event('change')); }
    if ($district) { if ($district.tomselect) $district.tomselect.clear(); else $district.value = ''; $district.dispatchEvent(new Event('change')); }
    if ($cluster)  { if ($cluster.tomselect)  $cluster.tomselect.clear();  else $cluster.value  = ''; $cluster.dispatchEvent(new Event('change')); }
    syncOutputs();
    refreshSnapshots();
  });

  // First render
  syncOutputs();
  refreshSnapshots();
})();
</script>
@endpush
