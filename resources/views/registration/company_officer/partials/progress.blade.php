{{-- Goal-Gradient Progress (responsive, glowing current step) --}}
@props(['steps'=>[], 'current'=>null])

<style>
  .gg-wrap{--bar:#e5e7eb;--active:#2563eb;--text:#334155;--muted:#64748b}
  .gg-rail{position:relative;height:4px;background:var(--bar);border-radius:999px;margin:10px 8px 18px}
  .gg-rail::after{content:"";position:absolute;inset-block:0;left:0;
    width:calc((var(--i,0)/(var(--n,1)-1)) * 100%);background:var(--active);border-radius:999px;transition:width .35s ease}
  .gg-steps{display:grid;grid-template-columns:repeat(var(--n),1fr);gap:0;align-items:center}
  .gg-step{display:flex;flex-direction:column;align-items:center;text-align:center}
  .gg-dot{width:34px;height:34px;border-radius:50%;display:grid;place-items:center;
    background:linear-gradient(135deg,#dbeafe,#f5d0fe);color:#0f172a;font-weight:800;border:2px solid #fff;
    box-shadow:0 6px 16px rgba(2,6,23,.08)}
  .gg-step.is-active .gg-dot{background:radial-gradient(120px 120px at 50% 0%, #fef08a 0%, #fde68a 40%, #f59e0b 78%);
    color:#111827; animation:pulse 1.35s infinite; box-shadow:0 0 0 6px rgba(245,158,11,.25)}
  @keyframes pulse{0%{transform:scale(1)}50%{transform:scale(1.06)}100%{transform:scale(1)}}
  .gg-label{margin-top:8px;max-width:90px;font-size:.8rem;color:var(--muted);font-weight:700}
  .gg-step.is-active .gg-label{color:#111827}
  @media (max-width:640px){.gg-label{font-size:.72rem;max-width:68px}}
</style>

@php
  $total = max(1, count($steps));
  $idx   = max(0, array_search($current, $steps) !== false ? array_search($current, $steps) : 0);
@endphp

<div class="gg-wrap" style="--n: {{ $total }}; --i: {{ $idx }};">
  <div class="gg-rail"></div>
  <div class="gg-steps">
    @foreach($steps as $i => $s)
      <div class="gg-step {{ $i===$idx ? 'is-active' : '' }}">
        <div class="gg-dot">{{ $i+1 }}</div>
        <div class="gg-label">{{ $s }}</div>
      </div>
    @endforeach
  </div>
</div>
