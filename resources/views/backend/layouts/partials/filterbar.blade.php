{{-- File: resources/views/backend/layouts/partials/filterbar.blade.php --}}
@php
    use Illuminate\Support\Facades\Route;

    $showCluster = $showCluster ?? true;
    $defaults = $defaults ?? ['division_id'=>null,'district_id'=>null,'cluster_id'=>null];

    // Detect route name base
    $nameBase = Route::has('ajax.geo.divisions') ? 'ajax.geo.' : 'backend.ajax.geo.';

    // Resolve tenant (company) if the page is under /company/{company:slug}/...
    $routeCompany = request()->route('company');   // model from route model binding (nullable)
    $companyId    = $routeCompany->id ?? optional(auth()->user())->company_id;

    $endpoints = [
        'divisions' => route($nameBase.'divisions'),
        'districts' => route($nameBase.'districts'),
        'clusters'  => route($nameBase.'clusters'),
    ];
@endphp

{{-- Lightweight, scoped styling (safe for Tom Select) --}}
@push('styles')
<style>
#filterbar .form-label { font-weight: 600; color: #334155; }
#filterbar .ts-control { border-radius: 10px; min-height: 44px; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
#filterbar .ts-control:focus, #filterbar .ts-control.focus { box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
#filterbar .ts-dropdown { border-radius: 10px; box-shadow: 0 8px 24px rgba(15,23,42,.08); }
#filterbar .ts-dropdown .option { padding: .55rem .75rem; }
#filterbar .ts-dropdown .active { background: #f1f5f9; }
</style>
@endpush

<div class="row g-2 align-items-end"
     id="filterbar"
     data-endpoints='@json($endpoints)'
     @if($companyId) data-company-id="{{ $companyId }}" @endif
>
    <div class="col-12 col-md-4">
        <label for="division_id" class="form-label mb-1">Division</label>
        <select id="division_id" name="division_id" class="form-select"></select>
    </div>

    <div class="col-12 col-md-4">
        <label for="district_id" class="form-label mb-1">District</label>
        <select id="district_id" name="district_id" class="form-select" disabled></select>
    </div>

    @if($showCluster)
    <div class="col-12 col-md-4">
        <label for="cluster_id" class="form-label mb-1">Cluster</label>
        <select id="cluster_id" name="cluster_id" class="form-select" disabled></select>
    </div>
    @endif
</div>

@push('scripts')
<script>
(function(){
    const el = document.getElementById('filterbar');
    if (!el) return;

    const eps    = JSON.parse(el.dataset.endpoints || '{}');
    const compId = el.dataset.companyId || '';

    const $div = document.getElementById('division_id');
    const $dis = document.getElementById('district_id');
    const $clu = document.getElementById('cluster_id');

    const hasTS = (typeof window.TomSelect !== 'undefined');

    function upgrade(select, placeholder){
        if (!hasTS || !select) return null;
        // Keep provided order (⟨ ALL … ⟩ first), don’t re-sort by text
        return new TomSelect(select, {
            placeholder: placeholder || '',
            create: false,
            allowEmptyOption: false,   // <- no placeholder option in the list
            maxOptions: 500,
            sortField: [{ field: '$order' }]
        });
    }

    function disableTS(select, isDisabled){
        if (!select || !select.tomselect) return;
        isDisabled ? select.tomselect.disable() : select.tomselect.enable();
    }

    function resetTS(select, placeholder, disabled=true){
        if (!select) return;
        // Ensure a TS instance exists with the right placeholder
        if (!select.tomselect) {
            upgrade(select, placeholder);
        } else {
            // Update placeholder for existing instance
            select.tomselect.settings.placeholder = placeholder || '';
            select.tomselect.input.placeholder = placeholder || '';
        }
        // Clear all items & options, then (re)disable
        select.tomselect.clear(true);
        select.tomselect.clearOptions();
        disableTS(select, !!disabled);
    }

    async function fetchJSON(url){
        try{
            const res = await fetch(url, { headers: {'X-Requested-With':'XMLHttpRequest'} });
            if (!res.ok) return [];
            return res.json();
        }catch(_){ return []; }
    }

    function urlWithParams(base, params){
        const usp = new URLSearchParams(params || {});
        const qs  = usp.toString();
        return qs ? `${base}?${qs}` : base;
    }

    function fillTS(select, items, placeholder, includeAllText=null){
        // Ensure TS exists & cleared
        resetTS(select, placeholder, false);
        const ts = select.tomselect;

        // Add <ALL> first (keeps first due to sortField $order)
        if (includeAllText) {
            ts.addOption({ value: '__all__', text: includeAllText });
        }

        // Add real items in the order received
        for (const it of items){
            ts.addOption({ value: String(it.id), text: it.label || it.name });
        }

        // No preselection → placeholder remains visible until user picks something
        ts.refreshOptions(false);
    }

    // ---------- Init ----------
    // Create TS with placeholders (no placeholder options in the list)
    upgrade($div, '— Select Division —');
    upgrade($dis, '— Select District —');
    if ($clu) upgrade($clu, '— Select Cluster —');

    // Initially District & Cluster disabled
    disableTS($dis, true);
    if ($clu) disableTS($clu, true);

    // Load Divisions (scoped by jurisdiction server-side)
    (async function init(){
        const divisions = await fetchJSON(eps.divisions);
        fillTS($div, divisions, '— Select Division —', '⟨ ALL Divisions ⟩');
    })();

    // Division change → load Districts
    $div.addEventListener('change', async () => {
        const id = $div.value || '';
        // Reset & disable lower levels first
        resetTS($dis, '— Select District —', true);
        if ($clu) resetTS($clu, '— Select Cluster —', true);

        // Params: omit division_id for <ALL>
        const params = {};
        if (id && id !== '__all__') params.division_id = id;

        const districts = await fetchJSON(urlWithParams(eps.districts, params));
        fillTS($dis, districts, '— Select District —', '⟨ ALL Districts ⟩');
        disableTS($dis, false); // enable
    });

    // District change → load Clusters
    $dis.addEventListener('change', async () => {
        if (!$clu) return;
        const id = $dis.value || '';

        resetTS($clu, '— Select Cluster —', true);

        const params = {};
        if (id && id !== '__all__') params.district_id = id;

        const clusters = await fetchJSON(urlWithParams(eps.clusters, params));
        fillTS($clu, clusters, '— Select Cluster —', '⟨ ALL Clusters ⟩');
        disableTS($clu, false); // enable
    });
})();
</script>
@endpush
