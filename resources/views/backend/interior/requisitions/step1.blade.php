@extends('backend.layouts.master')
{{-- TMX-CR | resources/views/backend/interior/requisitions/step1.blade.php
     Client Interior Requisition | Step-1 (Create/Edit/View)
     Notes:
     - Single blade serves create/edit/view. Controller passes $mode = 'create'|'edit'|'view'
     - Tenant-aware routes resolved via Route::has(), compatible with AppServiceProvider folder prefixing.
--}}

@php
    $mode      = $mode ?? 'create';
    $ctx       = $ctx ?? [];
    $company   = $company ?? null; // company row or slug
    $companySlug = $companySlug ?? ($company->slug ?? (is_string($company) ? $company : request()->route('company')));
    $requisition = $requisition ?? null;

    $maxKB   = $maxKB ?? 10240; // 10MB default (KB)

    // Resolve route name with/without folder prefixes (mirrors controller logic)
    $reqRoute = function(string $suffix) {
        // Keep blades stable across AppServiceProvider folder-prefix changes by trying multiple candidates.
        // Also map legacy suffixes used in the HTML to actual route names in Requisitions.php.
        $alias = [
            'step1.save' => 'store',
            'step2.save' => 'step2.update',
        ];
        $suffix = $alias[$suffix] ?? $suffix;

        $candidates = [
            "backend.interior.interior.requisitions.$suffix",
            "backend.interior.requisitions.$suffix",
            "interior.requisitions.$suffix",
            "backend.interior.interior.requisition.$suffix",
            "backend.interior.requisition.$suffix",
            "interior.requisition.$suffix",
            "requisitions.$suffix",
        ];

        foreach ($candidates as $n) {
            if (\Illuminate\Support\Facades\Route::has($n)) return $n;
        }
        return $candidates[0];
    };
    $clusterDisplay = (data_get($ctx,'cluster_id') && data_get($ctx,'cluster_name')) ? (data_get($ctx,'cluster_id').' - '.data_get($ctx,'cluster_name')) : null;
@endphp

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>

        :root {
            --bg: #ffffff;
            --bg-soft: #f9fafb;
            --border-subtle: #e5e7eb;
            --ink: #0f172a;
            --muted: #6b7280;
            --accent: #f59e0b;
            --accent-soft: rgba(245, 158, 11, 0.08);
            --accent-strong: #ea580c;
            --pill-bg: #eef2ff;
            --radius-card: 20px 20px 16px 16px;
            --radius-pill: 999px;
            --shadow-soft: 0 18px 40px rgba(15, 23, 42, 0.08);
            --shadow-hover: 0 20px 45px rgba(15, 23, 42, 0.14);
            --error: #b91c1c;

            /* Elegant layer card background */
            --layer-card-bg: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            --layer-card-border: #e6edf7;
            --layer-card-inset: inset 0 1px 0 rgba(255, 255, 255, 0.85);

            --layer-selection-border: #16a34a;

            --section-title-color: #111827;
            --section-subtitle-color: #4b5563;
        }

        html {
            -webkit-text-size-adjust: 100%;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Poppins", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background-color: var(--bg);
            color: var(--ink);
            -webkit-font-smoothing: antialiased;
        }

        .cr-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .cr-container {
            width: 100%;
            max-width: 1120px;
            margin: 0 auto;
            padding: 16px 16px 48px;
        }

        @media (min-width: 992px) {
            .cr-container {
                padding: 32px 16px 64px;
            }
        }

        main {
            flex: 1;
        }

        .cr-section {
            margin-top: 20px;
        }

        .cr-section-header {
            margin-bottom: 10px;
        }

        .cr-section-title {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 4px;
            color: var(--section-title-color);
        }

        .cr-section-subtitle {
            font-size: 0.9rem;
            color: var(--section-subtitle-color);
        }

        .cr-card {
            border-radius: var(--radius-card);
            background-color: var(--bg-soft);
            border: 1px solid var(--border-subtle);
            box-shadow: var(--shadow-soft);
            padding: 18px 16px 20px;
        }

        /* Elegant inner layer panels */
        .cr-layer-card {
            background: var(--layer-card-bg);
            border: 1px solid var(--layer-card-border);
            box-shadow: var(--layer-card-inset), var(--shadow-soft);
        }

        .cr-layer-card.has-selection {
            border-color: var(--layer-selection-border);
            box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.2), var(--layer-card-inset), var(--shadow-soft);
        }

        @media (min-width: 992px) {
            .cr-card {
                padding: 22px 20px 24px;
            }
        }

        .cr-form-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 12px 16px;
        }

        @media (min-width: 768px) {
            .cr-form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .cr-field {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .cr-field label {
            font-size: 0.85rem;
            font-weight: 500;
        }

        .cr-field label span.required {
            color: var(--accent-strong);
            margin-left: 2px;
        }

        .cr-input,
        .cr-textarea,
        .cr-select {
            width: 100%;
            padding: 8px 10px;
            border-radius: 999px;
            border: 1px solid var(--border-subtle);
            font-size: 0.9rem;
            background-color: #ffffff;
            outline: none;
            transition: border-color 0.16s ease, box-shadow 0.16s ease, background-color 0.16s ease;
        }

        .cr-textarea {
            border-radius: 16px;
            min-height: 72px;
            resize: vertical;
            padding: 8px 10px;
        }

        .cr-input:focus,
        .cr-textarea:focus,
        .cr-select:focus {
            border-color: var(--accent-strong);
            box-shadow: 0 0 0 1px rgba(234, 88, 12, 0.16);
        }

        .cr-field.readonly .cr-input,
        .cr-field.readonly .cr-textarea {
            background-color: #f3f4f6;
            color: var(--muted);
        }

        .cr-error {
            font-size: 0.78rem;
            color: var(--error);
            margin-top: 2px;
        }

        .cr-help-text {
            font-size: 0.8rem;
            color: var(--muted);
            line-height: 1.4;
        }

        .cr-card-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 14px;
        }

        @media (min-width: 576px) {
            .cr-card-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 992px) {
            .cr-card-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        .cr-select-card {
            border-radius: var(--radius-card);
            background-color: #ffffff;
            border: 1px solid var(--border-subtle);
            box-shadow: var(--shadow-soft);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        }

        .cr-select-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-hover);
        }

        /* UPDATED: thick "photo album" border on selection */
        .cr-select-card.selected {
            border-color: var(--accent-strong);
            border-width: 2px;
            box-shadow: 0 0 0 5px rgba(234, 88, 12, 0.38), var(--shadow-hover);
            transform: translateY(-3px);
        }

        .cr-card-media {
            position: relative;
            height: 120px;
            overflow: hidden;
            border-radius: var(--radius-card);
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        .cr-card-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .cr-card-media-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(15, 23, 42, 0.45), transparent 60%);
        }

        .cr-card-tick {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            border-radius: 999px;
            background: linear-gradient(135deg, #22c55e, #15803d);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            box-shadow: 0 10px 24px rgba(22, 163, 74, 0.4);
            opacity: 0;
            transform: scale(0.8);
            transition: opacity 0.16s ease, transform 0.16s ease;
            z-index: 2;
        }

        .cr-select-card.selected .cr-card-tick {
            opacity: 1;
            transform: scale(1);
        }

        /* Zoom icon button (does not affect selection) */
        .cr-zoom-btn {
            position: absolute;
            left: 10px;
            top: 10px;
            width: 32px;
            height: 32px;
            border-radius: 999px;
            border: none;
            background: rgba(255, 255, 255, 0.92);
            color: #111827;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.18);
            cursor: pointer;
            z-index: 3;
            transition: transform 0.14s ease, box-shadow 0.14s ease, background 0.14s ease;
        }

        .cr-zoom-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.24);
            background: #ffffff;
        }

        .cr-card-body {
            padding: 10px 14px 12px;
        }

        .cr-card-title {
            font-size: 0.95rem;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .cr-card-text {
            font-size: 0.82rem;
            color: var(--muted);
        }

        .cr-card-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            border-radius: var(--radius-pill);
            background-color: var(--accent-soft);
            color: var(--accent-strong);
            font-size: 0.7rem;
            margin-bottom: 4px;
        }

        .cr-card-badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: var(--accent-strong);
        }

        .cr-scope-subtypes {
            margin-top: 20px;
        }

        .cr-scope-subtypes.is-hidden {
            display: none;
        }

        .cr-space-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        @media (min-width: 576px) {
            .cr-space-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (min-width: 992px) {
            .cr-space-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .cr-space-card {
            border-radius: var(--radius-card);
            background-color: #ffffff;
            border: 1px solid var(--border-subtle);
            box-shadow: var(--shadow-soft);
            padding: 10px 10px 12px;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            text-align: left;
            gap: 8px;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        }

        .cr-space-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-hover);
        }

        /* UPDATED: thick "photo album" border on selection */
        .cr-space-card.selected {
            border-color: var(--accent-strong);
            border-width: 2px;
            box-shadow: 0 0 0 5px rgba(234, 88, 12, 0.38), var(--shadow-hover);
            transform: translateY(-3px);
        }

        .cr-space-avatar {
            width: 100%;
            aspect-ratio: 4/3;
            border-radius: 14px;
            overflow: hidden;
            position: relative;
        }

        .cr-space-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            cursor: default !important; /* remove hover zoom cursor on spaces */
        }

        /* Zoom icon for space cards */
        .cr-space-zoom-btn {
            position: absolute;
            left: 10px;
            top: 10px;
            width: 32px;
            height: 32px;
            border-radius: 999px;
            border: none;
            background: rgba(255, 255, 255, 0.92);
            color: #111827;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.18);
            cursor: pointer;
            z-index: 3;
        }

        /* NEW: tick for space cards (top-right like project-type/subtype) */
        .cr-space-card .cr-card-tick {
            z-index: 3;
        }

        .cr-space-card.selected .cr-card-tick {
            opacity: 1;
            transform: scale(1);
        }

        .cr-space-label {
            font-size: 0.86rem;
            font-weight: 500;
        }

        .cr-space-meta {
            font-size: 0.8rem;
            color: var(--muted);
        }

        .cr-space-inputs {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-top: 4px;
        }

        .cr-space-inputs-inline {
            display: flex;
            gap: 6px;
        }

        .cr-space-inputs-inline .cr-input {
            border-radius: 999px;
        }

        .cr-space-inputs label {
            font-size: 0.75rem;
            text-align: left;
        }

        .cr-actions {
            margin-top: 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: flex-end;
        }

        .cr-btn {
            border-radius: var(--radius-pill);
            border: none;
            font-size: 0.86rem;
            font-weight: 500;
            padding: 9px 20px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.16s ease, box-shadow 0.16s ease, transform 0.12s ease;
            text-decoration: none;
        }

        .cr-btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            color: #111827;
            box-shadow: 0 14px 30px rgba(248, 181, 43, 0.45);
        }

        .cr-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 40px rgba(248, 181, 43, 0.55);
            background: linear-gradient(135deg, #facc15, #ea580c);
        }

        .cr-btn-secondary {
            background-color: #ffffff;
            color: var(--muted);
            border: 1px solid var(--border-subtle);
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.04);
        }

        .cr-btn-secondary:hover {
            background-color: #f3f4ff;
            color: #111827;
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
        }

        .cr-btn-icon-circle {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.9);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.82rem;
        }

        .cr-modal-backdrop {
            position: fixed;
            inset: 0;
            background-color: rgba(15, 23, 42, 0.55);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }

        .cr-modal-backdrop.is-open {
            display: flex;
        }

        .cr-modal {
            background-color: #ffffff;
            border-radius: 16px;
            max-width: 90vw;
            max-height: 90vh;
            overflow: auto;
            box-shadow: 0 22px 60px rgba(15, 23, 42, 0.5);
            position: relative;
        }

        .cr-modal img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        .cr-modal-close {
            position: absolute;
            top: 8px;
            right: 10px;
            border: none;
            background: rgba(15, 23, 42, 0.06);
            border-radius: 999px;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.1rem;
        }

        @media (max-width: 576px) {
            .cr-modal {
                width: 100%;
                height: 100%;
                max-width: 100%;
                max-height: 100%;
                border-radius: 0;
            }
        }

        /* Keep for project-type/subtype only (space images overridden to default cursor) */
        .cr-zoomable {
            cursor: zoom-in;
        }

        .layer-slider {
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 36px;
        }

        .slider-viewport {
            flex: 1;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
        }

        .slider-track {
            display: inline-flex;
            gap: 14px;
            padding: 4px 0 12px;
        }

        .slider-item {
            flex: 0 0 calc(50% - 10px);
            scroll-snap-align: start;
        }

        @media (min-width: 768px) {
            .slider-item {
                flex: 0 0 calc(33.333% - 12px);
            }
        }

        @media (min-width: 1200px) {
            .slider-item {
                flex: 0 0 calc(25% - 14px);
            }
        }

        .slider-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 42px;
            height: 42px;
            border-radius: 999px;
            border: none;
            background: linear-gradient(135deg, #1f2937, #0f172a);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.4);
            font-size: 1.1rem;
            z-index: 2;
            transition: transform 0.14s ease, box-shadow 0.14s ease, opacity 0.14s ease, background 0.14s ease;
        }

        .slider-arrow:hover {
            transform: translateY(-50%) translateY(-1px);
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.55);
            background: linear-gradient(135deg, #111827, #020617);
        }

        .slider-arrow-left {
            left: 6px;
        }

        .slider-arrow-right {
            right: 6px;
        }

        .slider-arrow[disabled] {
            opacity: 0.3;
            cursor: default;
            box-shadow: none;
        }

        .cr-slider-meta {
            margin-top: 4px;
            font-size: 0.78rem;
            color: var(--muted);
            text-align: right;
        }

        .cr-toast {
            position: fixed;
            right: 16px;
            bottom: 20px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #ecfdf5;
            padding: 10px 16px;
            border-radius: 999px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 18px 40px rgba(22, 163, 74, 0.5);
            opacity: 0;
            transform: translateY(10px);
            pointer-events: none;
            transition: opacity 0.18s ease, transform 0.18s ease;
            z-index: 60;
        }

        .cr-toast.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        .cr-toast-icon {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            background-color: rgba(236, 253, 245, 0.22);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
        }

        @media (max-width: 480px) {
            .cr-container {
                padding: 16px 12px 32px;
            }

            .cr-card {
                padding: 16px 12px 18px;
            }

            .cr-section-title {
                font-size: 1rem;
            }

            .cr-section-subtitle {
                font-size: 0.85rem;
            }

            .layer-slider {
                padding: 0 24px;
            }

            .slider-arrow {
                width: 36px;
                height: 36px;
            }
        }
    
/* Assistance note (Blade-friendly placeholder) */
.cr-assist-note{
  display:flex;
  align-items:flex-start;
  gap:.75rem;
  margin-top:.75rem;
  padding:.85rem 1rem;
  border:1px solid rgba(14,165,233,.25);
  background: rgba(14,165,233,.08);
  border-radius: 14px;
}
.cr-assist-note__icon{
  width:32px;height:32px;
  display:grid;place-items:center;
  border-radius:10px;
  background: rgba(14,165,233,.18);
  flex: 0 0 auto;
}
.cr-assist-note__text{
  font-size:.95rem;
  line-height:1.35;
  color: var(--ink, #17212e);
}
.cr-assist-note__mobile{
  font-weight:700;
  color: var(--brand, #1f3a8a);
  white-space: normal;
}

/* --- Head-level compact display bar --- */
.cr-headbar{
  margin-top:.75rem;
  padding:.75rem .9rem;
  border:1px solid rgba(31,58,138,.16);
  background: rgba(248,251,255,.9);
  border-radius: 14px;
}
.cr-headbar-grid{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));
  gap:14px;
  align-items:center;
}
.cr-headbar-item{
  min-width: 0;
}
.cr-headbar-label{
  font-size:.72rem;
  letter-spacing:.02em;
  color: var(--muted, #8f9ab0);
  text-transform: uppercase;
}
.cr-headbar-value{
  font-size:.92rem;
  font-weight: 700;
  color: var(--ink, #17212e);
  white-space: normal;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* --- Attachments preview --- */
.cr-attach-preview{
  margin-top:.55rem;
  display:grid;
  grid-template-columns: repeat(auto-fill, minmax(86px, 1fr));
  gap:.45rem;
}
.cr-attach-thumb{
  position: relative;
  border-radius: 12px;
  overflow: hidden;
  border:1px solid rgba(229,233,242,.9);
  background: #fff;
  aspect-ratio: 1 / 1;
}
.cr-attach-thumb img{
  width:100%;
  height:100%;
  object-fit: cover;
  display:block;
}
.cr-attach-thumb .cr-attach-badge{
  position:absolute;
  left:6px;
  top:6px;
  font-size:.72rem;
  padding:.15rem .45rem;
  border-radius: 999px;
  background: rgba(15,40,154,.85);
  color:#fff;
}


</style>
@endpush

@section('content')

    <div class="cr-page">
        <main>
            <div class="cr-container">
                <form id="cr-step1-form" novalidate action="{{ route($reqRoute('step1.save'), ['company' => $companySlug]) }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="requisition_id" value="{{ $requisition->id ?? '' }}">
    <input type="hidden" name="mode" value="{{ $mode }}">
    <input type="hidden" name="go_next" id="go_next" value="0">

                    <!-- SECTION A: PROJECT DETAILS -->
                    <section class="cr-section" id="section-project-details">
                        <div class="cr-section-header">
                            <h1 class="cr-section-title text-center" style="color:#0f289a">Requisition Details</h1>
                            
                        <div class="cr-assist-note" role="note" aria-label="Assistance note">
                            <div class="cr-assist-note__icon">ℹ️</div>
                            <div class="cr-assist-note__text">
                                To assist you in Fill-up the Requisition, You may contact to Your Nearest Cluster Admin.
                                Mobile: <span class="cr-assist-note__mobile">{{ data_get($ctx,'cluster_supervisor_phone') ?: 'xxxxxxxx' }}</span>.
                                He will give you support until your Project/Requisition Completion.
                            
                            </div>
                        </div>

                        <div class="cr-headbar" aria-label="Client profile summary">
                            <div class="cr-headbar-grid">
                                <div class="cr-headbar-item">
                                    <div class="cr-headbar-label">Reg ID</div>
                                    <div class="cr-headbar-value" id="hb_reg_id">{{ data_get($ctx,'reg_id') ?? '—' }}</div>
                                </div>
                                <div class="cr-headbar-item">
                                    <div class="cr-headbar-label">User ID</div>
                                    <div class="cr-headbar-value" id="hb_client_user_id">{{ data_get($ctx,'client_user_id') ?? '—' }}</div>
                                </div>
                                <div class="cr-headbar-item">
                                    <div class="cr-headbar-label">Client</div>
                                    <div class="cr-headbar-value" id="hb_client_name">{{ data_get($ctx,'client_name') ?? '—' }}</div>
                                </div>
                                <div class="cr-headbar-item">
                                    <div class="cr-headbar-label">Email</div>
                                    <div class="cr-headbar-value" id="hb_client_email">{{ data_get($ctx,'client_email') ?? '—' }}</div>
                                </div>
                                <div class="cr-headbar-item">
                                    <div class="cr-headbar-label">Phone</div>
                                    <div class="cr-headbar-value" id="hb_client_phone">{{ data_get($ctx,'client_phone') ?? '—' }}</div>
                                </div>
                                <div class="cr-headbar-item">
                                    <div class="cr-headbar-label">Cluster</div>
                                    <div class="cr-headbar-value" id="hb_cluster">{{ $clusterDisplay ?? '—' }}</div>
                                </div>
                            </div>
                        </div>
                            <h2 class="cr-section-title">Project Details</h2>
                            <p class="cr-section-subtitle"> Share your basic information so we can attach this
                                requisition to your profile and project. </p>
                        </div>

                        <div class="cr-card cr-layer-card" id="project-details-card">
                            <div class="cr-form-grid">
                                
                                <!-- Client Attachments (multiple images) -->
                                
<div class="cr-field readonly">
                                    <label for="reg_id">Registration ID<span class="required">*</span></label>
                                    <input id="reg_id" class="cr-input" type="text" value="REG-INT-2025-00123" readonly>
                                </div>

                                <div class="cr-field readonly">
                                    <label for="client_name">Name<span class="required">*</span></label>
                                    <input id="client_name" class="cr-input" type="text" value="{{ data_get($ctx,'client_name', '') }}"
                                        readonly readonly>
                                </div>

                                <div class="cr-field readonly">
                                    <label for="client_email">Email<span class="required">*</span></label>
                                    <input id="client_email" class="cr-input" type="email" placeholder="you@example.com"
                                        value="{{ data_get($ctx,'client_email', '') }}" readonly readonly>
                                </div>

                                <div class="cr-field readonly">
                                    <label for="client_phone">Contact No<span class="required">*</span></label>
                                    <input id="client_phone" class="cr-input" type="tel" placeholder="+8801XXXXXXXXX"
                                        value="{{ data_get($ctx,'client_phone','') }}" readonly readonly>
                                </div>
                                    @if(!data_get($ctx,'client_phone'))
                                        <div class="text-danger small mt-1">
                                            You should edit your Registration for Phone No first which is required before placing any Requisition
                                        </div>
                                    @endif
                                </div>

                                <div class="cr-field readonly">
                                    <label for="cluster_display">Cluster</label>
                                    <input id="cluster_display" class="cr-input" type="text"
                                           value="{{ $clusterDisplay ?? '' }}" readonly>
                                </div>

                                <div class="cr-field" style="grid-column: 1 / -1;">
                                    <label for="project_address">Project Location / Address<span
                                            class="required">*</span></label>
                                    <textarea id="project_address" class="cr-textarea"
                                        placeholder="Apartment / House name, road, block, area, city, country"></textarea>
                                </div>

                                <div class="cr-field" style="grid-column: 1 / -1;">
                                    <label for="project_note">Note / Special Instructions (Optional)</label>
                                    <textarea id="project_note" class="cr-textarea"
                                        placeholder="Tell us about any preferences, constraints, or special instructions."></textarea>
                                </div>

                                <div class="cr-field readonly">
                                    <label for="project_total_sqft">Total SQFT of the Entire Project</label>
                                    <input id="project_total_sqft" class="cr-input" type="number" value="0" readonly>
                                </div>

                                <div class="cr-field">
                                    <label>&nbsp;</label>
                                    <p class="cr-help-text"> Combined SQFT of all selected spaces in this requisition.
                                        It updates automatically when you change each space’s SQFT. </p>
                                </div>

                                <div class="cr-field">
                                    <label for="project_budget">Budget (Approximate)<span
                                            class="required">*</span></label>
                                    <input id="project_budget" class="cr-input" type="text"
                                        placeholder="For example: 25,00,000 BDT">
                                </div>

                                <div class="cr-field">
                                    <label for="project_eta">Expected Time of Delivery<span
                                            class="required">*</span></label>
                                    <input id="project_eta" class="cr-input" type="date">
                                </div>

                                <!-- Cluster Member Remark -->
                                <div class="cr-field" style="grid-column: 1 / -1;">
                                    <label for="cluster_member_remark">Cluster Member Remark</label>
                                    <textarea id="cluster_member_remark" class="cr-textarea"
                                        placeholder="(For internal use) Cluster member will write remarks after discussion with client."></textarea>
                                </div>

                                <!-- Head Office Remark -->
                                <div class="cr-field" style="grid-column: 1 / -1;">
                                    <label for="head_office_remark">Head Office Remark</label>
                                    <textarea id="head_office_remark" class="cr-textarea"
                                        placeholder="(For internal use) Head Office will write remarks and decisions."></textarea>
                                </div>
                            </div>
            <!-- Client Attachments (Optional) -->
            
            <!-- Client Attachments (Optional) -->
            <div class="cr-card mt-3" id="client-attachments-card">
                <div class="cr-card-body">
                    <h3 class="cr-card-title mb-1">Client Attachments (Optional)</h3>
                    <div class="text-muted small mb-3">
                        You may upload multiple images (jpg, jpeg, png, webp, gif, bmp, svg, tiff). Max 10MB each.
                    </div>

                    @if(!empty($existingAttachments) && count($existingAttachments))
                        <div class="row g-2 mb-3">
                            @foreach($existingAttachments as $att)
                                @php
                                    $attUrl = $att->file_url ?? $att->file_path ?? null;
                                    $attId  = $att->id ?? null;
                                @endphp
                                <div class="col-6 col-md-3 col-lg-2">
                                    <div class="border rounded p-2 h-100 d-flex flex-column">
                                        <div class="ratio ratio-1x1 bg-light rounded overflow-hidden">
                                            @if($attUrl)
                                                <a href="{{ $attUrl }}" target="_blank" rel="noopener">
                                                    <img src="{{ $attUrl }}" alt="Attachment" style="width:100%;height:100%;object-fit:cover;">
                                                </a>
                                            @else
                                                <div class="d-flex align-items-center justify-content-center text-muted small">No preview</div>
                                            @endif
                                        </div>

                                        @if($attId)
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" name="delete_attachment_ids[]" value="{{ $attId }}" id="del_att_{{ $attId }}">
                                                <label class="form-check-label small" for="del_att_{{ $attId }}">Remove</label>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mb-2">
                        <input type="file"
                               class="form-control @error('attachments') is-invalid @enderror @error('attachments.*') is-invalid @enderror"
                               name="attachments[]" id="attachments" accept="image/*" multiple>
                        

<div class="small text-muted mt-2">
    <div class="d-none d-md-block">
        <strong>Desktop:</strong> You can upload multiple images. Hold <kbd>Ctrl</kbd> (or <kbd>Shift</kbd>) while selecting.
        If your images are in different folders, add them in multiple rounds using <em>Add more images</em>.
    </div>
    <div class="d-md-none">
        <strong>Mobile:</strong> You can add multiple images. If multi-select isn’t available on your phone, add images in multiple rounds using <em>Add more images</em>.
    </div>
    <div class="mt-1">
        Max size: <strong>10MB</strong> per image. Supported: jpg, jpeg, png, webp, gif, bmp, svg, tiff.
    </div>
</div>

<div class="mt-2 d-flex flex-wrap gap-2">
    <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddMoreAttachments">
        <i class="fa-solid fa-plus me-1"></i> Add more images
    </button>
    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearAttachments">
        <i class="fa-solid fa-trash me-1"></i> Clear selection
    </button>
</div>

@error('attachments')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        @if($errors->has('attachments.*'))
                            <div class="invalid-feedback d-block">{{ $errors->first('attachments.*') }}</div>
                        @endif
                    </div>

                    <div id="attachments-preview" class="row g-2"></div>
                </div>
            </div>
</section>

                    <!-- SECTION B: PROJECT SCOPE -->
                    <section class="cr-section" id="section-project-scope">
                        <div class="cr-section-header">
                            <h2 class="cr-section-title">Project Scope</h2>
                            <p class="cr-section-subtitle">
                                Tell us what type of project this is, and which spaces we should design or supply
                                materials for.
                            </p>
                        </div>

                        <!-- Project Type -->
                        <div class="mb-3">
                            <h3 class="cr-section-title" style="font-size: 1rem;">Project Type</h3>
                            <p class="cr-section-subtitle">
                                Choose the overall nature of your project.
                            </p>
                        </div>

                        <div class="cr-card cr-layer-card" id="project-type-card">
                            <div class="layer-slider" data-layer="project-type" data-page="1" data-has-more="false">
                                <button class="slider-arrow slider-arrow-left" type="button"
                                    aria-label="Scroll left">&lt;</button>
                                <div class="slider-viewport">
                                    <div class="slider-track" id="project-type-track">
                                        <div class="slider-item">
                                            <article class="cr-select-card js-project-type-card"
                                                data-project-type="Residential">
                                                <div class="cr-card-media">
                                                    <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                        data-zoom-src="https://images.unsplash.com/photo-1505691723518-36a5ac3be353?auto=format&fit=crop&w=1600&q=80"
                                                        data-zoom-alt="Warm residential living room with sofa and decor"
                                                        aria-label="Zoom image">🔎</button>
                                                    <img class="cr-zoomable"
                                                        src="https://images.unsplash.com/photo-1505691723518-36a5ac3be353?auto=format&fit=crop&w=1200&q=80"
                                                        alt="Warm residential living room with sofa and decor">
                                                    <div class="cr-card-media-overlay"></div>
                                                    <div class="cr-card-tick">✓</div>
                                                </div>
                                                <div class="cr-card-body">
                                                    <div class="cr-card-badge">
                                                        <span class="cr-card-badge-dot"></span>
                                                        Most common
                                                    </div>
                                                    <h4 class="cr-card-title">Residential</h4>
                                                    <p class="cr-card-text">
                                                        Apartments, duplexes, villas and homes where family comfort
                                                        and character matter.
                                                    </p>
                                                </div>
                                            </article>
                                        </div>

                                        <div class="slider-item">
                                            <article class="cr-select-card js-project-type-card"
                                                data-project-type="Commercial">
                                                <div class="cr-card-media">
                                                    <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                        data-zoom-src="https://images.unsplash.com/photo-1529424301806-4be0bb154e3b?auto=format&fit=crop&w=1600&q=80"
                                                        data-zoom-alt="Modern open-plan office interior design"
                                                        aria-label="Zoom image">🔎</button>
                                                    <img class="cr-zoomable"
                                                        src="https://images.unsplash.com/photo-1529424301806-4be0bb154e3b?auto=format&fit=crop&w=1200&q=80"
                                                        alt="Modern open-plan office interior design">
                                                    <div class="cr-card-media-overlay"></div>
                                                    <div class="cr-card-tick">✓</div>
                                                </div>
                                                <div class="cr-card-body">
                                                    <div class="cr-card-badge">
                                                        <span class="cr-card-badge-dot"></span>
                                                        Teams &amp; workplaces
                                                    </div>
                                                    <h4 class="cr-card-title">Commercial</h4>
                                                    <p class="cr-card-text">
                                                        Offices, showrooms, banks, and co-working spaces focused on
                                                        productivity and brand.
                                                    </p>
                                                </div>
                                            </article>
                                        </div>

                                        <div class="slider-item">
                                            <article class="cr-select-card js-project-type-card"
                                                data-project-type="Hospitality">
                                                <div class="cr-card-media">
                                                    <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                        data-zoom-src="https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=1600&q=80"
                                                        data-zoom-alt="Hotel lobby with warm lighting and seating"
                                                        aria-label="Zoom image">🔎</button>
                                                    <img class="cr-zoomable"
                                                        src="https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=1200&q=80"
                                                        alt="Hotel lobby with warm lighting and seating">
                                                    <div class="cr-card-media-overlay"></div>
                                                    <div class="cr-card-tick">✓</div>
                                                </div>
                                                <div class="cr-card-body">
                                                    <div class="cr-card-badge">
                                                        <span class="cr-card-badge-dot"></span>
                                                        Experience-led
                                                    </div>
                                                    <h4 class="cr-card-title">Hospitality &amp; Retail</h4>
                                                    <p class="cr-card-text">
                                                        Hotels, cafes, restaurants and retail spaces where guest
                                                        experience is key.
                                                    </p>
                                                </div>
                                            </article>
                                        </div>

                                        <!-- Project Type cards from DB will be appended here -->
                                    </div>
                                </div>
                                <button class="slider-arrow slider-arrow-right" type="button"
                                    aria-label="Scroll right">&gt;</button>
                            </div>
                            <div class="cr-slider-meta">Total Images: 3</div>
                        </div>

                        <!-- Sub-Type (shown after Project Type selection) -->
                        <div class="cr-scope-subtypes is-hidden" id="subtype-section">
                            <div class="mt-4 mb-2">
                                <h3 class="cr-section-title" style="font-size: 1rem;">Project Sub-Type</h3>
                                <p class="cr-section-subtitle" id="subtype-section-hint">
                                    Choose a more specific type based on your selected project type.
                                </p>
                            </div>

                            <div class="cr-card cr-layer-card" id="subtype-card">
                                <div class="layer-slider" data-layer="sub-type" data-page="1" data-has-more="false">
                                    <button class="slider-arrow slider-arrow-left" type="button"
                                        aria-label="Scroll left">&lt;</button>
                                    <div class="slider-viewport">
                                        <div class="slider-track" id="subtype-track">
                                            <div class="slider-item">
                                                <article class="cr-select-card js-subtype-card"
                                                    data-project-type-ref="Residential" data-subtype="Apartment">
                                                    <div class="cr-card-media">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1523217582562-09d0def993a6?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Apartment interior with living and dining"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1523217582562-09d0def993a6?auto=format&fit=crop&w=1200&q=80"
                                                            alt="Apartment interior with living and dining">
                                                        <div class="cr-card-media-overlay"></div>
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-card-body">
                                                        <h4 class="cr-card-title">Apartment</h4>
                                                        <p class="cr-card-text">
                                                            Single or multiple apartments in the same building.
                                                        </p>
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-select-card js-subtype-card"
                                                    data-project-type-ref="Residential" data-subtype="Duplex">
                                                    <div class="cr-card-media">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1493809842364-78817add7ffb?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Duplex staircase and living area"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1493809842364-78817add7ffb?auto=format&fit=crop&w=1200&q=80"
                                                            alt="Duplex staircase and living area">
                                                        <div class="cr-card-media-overlay"></div>
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-card-body">
                                                        <h4 class="cr-card-title">Duplex</h4>
                                                        <p class="cr-card-text">
                                                            Two-level home with staircase and multiple zones.
                                                        </p>
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-select-card js-subtype-card"
                                                    data-project-type-ref="Residential" data-subtype="Villa">
                                                    <div class="cr-card-media">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1479839672679-a46483c0e7c8?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Luxury villa exterior at dusk"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1479839672679-a46483c0e7c8?auto=format&fit=crop&w=1200&q=80"
                                                            alt="Luxury villa exterior at dusk">
                                                        <div class="cr-card-media-overlay"></div>
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-card-body">
                                                        <h4 class="cr-card-title">Villa / Bungalow</h4>
                                                        <p class="cr-card-text">
                                                            Independent house with full interior and outdoor
                                                            coordination.
                                                        </p>
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-select-card js-subtype-card"
                                                    data-project-type-ref="Commercial" data-subtype="Corporate Office">
                                                    <div class="cr-card-media">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Corporate open office with glass partitions"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=1200&q=80"
                                                            alt="Corporate open office with glass partitions">
                                                        <div class="cr-card-media-overlay"></div>
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-card-body">
                                                        <h4 class="cr-card-title">Corporate Office</h4>
                                                        <p class="cr-card-text">
                                                            Complete office layout, cabins, workstations and
                                                            reception.
                                                        </p>
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-select-card js-subtype-card"
                                                    data-project-type-ref="Commercial" data-subtype="Bank Branch">
                                                    <div class="cr-card-media">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1523289333742-be1143f6b766?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Bank branch interior with counters and waiting area"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1523289333742-be1143f6b766?auto=format&fit=crop&w=1200&q=80"
                                                            alt="Bank branch interior with counters and waiting area">
                                                        <div class="cr-card-media-overlay"></div>
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-card-body">
                                                        <h4 class="cr-card-title">Bank Branch</h4>
                                                        <p class="cr-card-text">
                                                            Customer lobby, counters, vault access areas and back
                                                            office zones.
                                                        </p>
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-select-card js-subtype-card"
                                                    data-project-type-ref="Hospitality" data-subtype="Hotel Suite">
                                                    <div class="cr-card-media">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Hotel suite bedroom interior design"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1200&q=80"
                                                            alt="Hotel suite bedroom interior design">
                                                        <div class="cr-card-media-overlay"></div>
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-card-body">
                                                        <h4 class="cr-card-title">Hotel Suite / Room</h4>
                                                        <p class="cr-card-text">
                                                            Guest rooms, suites and associated corridors.
                                                        </p>
                                                    </div>
                                                </article>
                                            </div>

                                            <!-- Sub-type cards from DB will be appended here -->
                                        </div>
                                    </div>
                                    <button class="slider-arrow slider-arrow-right" type="button"
                                        aria-label="Scroll right">&gt;</button>
                                </div>
                                <div class="cr-slider-meta">Total Images: 6</div>
                            </div>
                        </div>

                        <!-- Spaces / Item Types -->
                        <div class="cr-section mt-4">
                            <div class="mb-3">
                                <h3 class="cr-section-title" style="font-size: 1rem;">Spaces to be Included</h3>
                                <p class="cr-section-subtitle">
                                    Select the rooms / areas where you want us to work. You can adjust the quantity
                                    and total area.
                                </p>
                            </div>

                            <div class="cr-card cr-layer-card" id="space-card">
                                <div class="layer-slider" data-layer="item-type" data-page="1" data-has-more="false">
                                    <button class="slider-arrow slider-arrow-left" type="button"
                                        aria-label="Scroll left">&lt;</button>
                                    <div class="slider-viewport">
                                        <div class="slider-track" id="space-track">
                                            <div class="slider-item">
                                                <article class="cr-space-card js-space-card"
                                                    data-space-name="Master Bed Room">
                                                    <div class="cr-space-avatar">
                                                        <button type="button" class="cr-space-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1505691723518-36a5ac3be353?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Master bedroom interior"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img
                                                            src="https://images.unsplash.com/photo-1505691723518-36a5ac3be353?auto=format&fit=crop&w=900&q=80"
                                                            alt="Master bedroom interior">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-space-label">Master Bed Room</div>
                                                    <div class="cr-space-meta">Primary bedroom for owners.</div>
                                                    <div class="cr-space-inputs">
                                                        <div>
                                                            <label for="space_master_qty">Quantity</label>
                                                            <input id="space_master_qty" class="cr-input" type="number"
                                                                min="0" value="1">
                                                        </div>
                                                        <div>
                                                            <label for="space_master_sqft">Total SQFT</label>
                                                            <input id="space_master_sqft" class="cr-input" type="number"
                                                                min="0" placeholder="e.g., 220" data-role="space-sqft">
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-space-card js-space-card"
                                                    data-space-name="Child Bed Room">
                                                    <div class="cr-space-avatar">
                                                        <button type="button" class="cr-space-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Child bedroom with playful decor"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img
                                                            src="https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=900&q=80"
                                                            alt="Child bedroom with playful decor">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-space-label">Child Bed Room</div>
                                                    <div class="cr-space-meta">For kids or teenagers.</div>
                                                    <div class="cr-space-inputs">
                                                        <div>
                                                            <label for="space_child_qty">Quantity</label>
                                                            <input id="space_child_qty" class="cr-input" type="number"
                                                                min="0" value="1">
                                                        </div>
                                                        <div>
                                                            <label for="space_child_sqft">Total SQFT</label>
                                                            <input id="space_child_sqft" class="cr-input" type="number"
                                                                min="0" placeholder="e.g., 180" data-role="space-sqft">
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-space-card js-space-card"
                                                    data-space-name="Living / Lounge">
                                                    <div class="cr-space-avatar">
                                                        <button type="button" class="cr-space-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1519710883215-83c5f3a9c1c8?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Living room with sofa and coffee table"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img
                                                            src="https://images.unsplash.com/photo-1519710883215-83c5f3a9c1c8?auto=format&fit=crop&w=900&q=80"
                                                            alt="Living room with sofa and coffee table">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-space-label">Living / Lounge</div>
                                                    <div class="cr-space-meta">Main family and guest area.</div>
                                                    <div class="cr-space-inputs">
                                                        <div>
                                                            <label for="space_living_qty">Quantity</label>
                                                            <input id="space_living_qty" class="cr-input" type="number"
                                                                min="0" value="1">
                                                        </div>
                                                        <div>
                                                            <label for="space_living_sqft">Total SQFT</label>
                                                            <input id="space_living_sqft" class="cr-input" type="number"
                                                                min="0" placeholder="e.g., 260" data-role="space-sqft">
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-space-card js-space-card" data-space-name="Dining">
                                                    <div class="cr-space-avatar">
                                                        <button type="button" class="cr-space-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1531297484001-80022131f5a1?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Dining table setup"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img
                                                            src="https://images.unsplash.com/photo-1531297484001-80022131f5a1?auto=format&fit=crop&w=900&q=80"
                                                            alt="Dining table setup">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-space-label">Dining</div>
                                                    <div class="cr-space-meta">Family or formal dining area.</div>
                                                    <div class="cr-space-inputs">
                                                        <div>
                                                            <label for="space_dining_qty">Quantity</label>
                                                            <input id="space_dining_qty" class="cr-input" type="number"
                                                                min="0" value="1">
                                                        </div>
                                                        <div>
                                                            <label for="space_dining_sqft">Total SQFT</label>
                                                            <input id="space_dining_sqft" class="cr-input" type="number"
                                                                min="0" placeholder="e.g., 160" data-role="space-sqft">
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-space-card js-space-card" data-space-name="Kitchen">
                                                    <div class="cr-space-avatar">
                                                        <button type="button" class="cr-space-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1519710164239-da123dc03ef4?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Modern kitchen interior"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img
                                                            src="https://images.unsplash.com/photo-1519710164239-da123dc03ef4?auto=format&fit=crop&w=900&q=80"
                                                            alt="Modern kitchen interior">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-space-label">Kitchen</div>
                                                    <div class="cr-space-meta">Wet and dry kitchens.</div>
                                                    <div class="cr-space-inputs">
                                                        <div>
                                                            <label for="space_kitchen_qty">Quantity</label>
                                                            <input id="space_kitchen_qty" class="cr-input" type="number"
                                                                min="0" value="1">
                                                        </div>
                                                        <div>
                                                            <label for="space_kitchen_sqft">Total SQFT</label>
                                                            <input id="space_kitchen_sqft" class="cr-input"
                                                                type="number" min="0" placeholder="e.g., 140"
                                                                data-role="space-sqft">
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-space-card js-space-card"
                                                    data-space-name="Toilet / Washroom">
                                                    <div class="cr-space-avatar">
                                                        <button type="button" class="cr-space-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1505691723518-36a5ac3be353?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Bathroom interior with vanity and shower"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img
                                                            src="https://images.unsplash.com/photo-1505691723518-36a5ac3be353?auto=format&fit=crop&w=900&q=80"
                                                            alt="Bathroom interior with vanity and shower">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-space-label">Toilet / Washroom</div>
                                                    <div class="cr-space-meta">Attached and common toilets.</div>
                                                    <div class="cr-space-inputs">
                                                        <div>
                                                            <label for="space_toilet_qty">Quantity</label>
                                                            <input id="space_toilet_qty" class="cr-input" type="number"
                                                                min="0" value="2">
                                                        </div>
                                                        <div>
                                                            <label for="space_toilet_sqft">Total SQFT</label>
                                                            <input id="space_toilet_sqft" class="cr-input" type="number"
                                                                min="0" placeholder="e.g., 120" data-role="space-sqft">
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>

                                            <!-- Space cards from DB will be appended here -->
                                        </div>
                                    </div>
                                    <button class="slider-arrow slider-arrow-right" type="button"
                                        aria-label="Scroll right">&gt;</button>
                                </div>
                                <div class="cr-slider-meta">Total Images: 6</div>
                            </div>
                        </div>
                    </section>

                    <!-- Actions -->
                    <section class="cr-actions">
                        <button type="button" class="cr-btn cr-btn-primary">
                            Save as Draft
                        </button>
                        
                        <button type="button" class="cr-btn cr-btn-primary" data-bs-toggle="modal" data-bs-target="#crCloseConfirmModal">
                            Close
                        </button>
<a href="client-requisition-step2.html" class="cr-btn cr-btn-primary">
                            <span>Continue to Detailed Requirements</span>
                            <span class="cr-btn-icon-circle">➝</span>
                        </a>
                    </section>
                </form>
            </div>
        </main>
    </div>

    <!-- Image Zoom Modal -->
    <div class="cr-modal-backdrop" id="imageZoomBackdrop" aria-hidden="true">
        <div class="cr-modal">
            <button type="button" class="cr-modal-close" id="imageZoomClose" aria-label="Close image zoom">×</button>
            <img src="" alt="Zoomed interior visual" id="imageZoomImg">
        </div>
    </div>

    <!-- Project Type Toast -->
    <div class="cr-toast" id="projectTypeToast" role="status" aria-live="polite">
        <span class="cr-toast-icon">✓</span>
        <span id="projectTypeToastText">You have selected Residential Project</span>
    </div>

    <script>
        (function () {
            var typeCards = document.querySelectorAll('.js-project-type-card');
            var subtypeSection = document.getElementById('subtype-section');
            var subtypeCards = document.querySelectorAll('.js-subtype-card');
            var spaceCards = document.querySelectorAll('.js-space-card');

            var projectTypeParent = document.getElementById('project-type-card');
            var subtypeParent = document.getElementById('subtype-card');
            var spaceParent = document.getElementById('space-card');

            var totalSqftInput = document.getElementById('project_total_sqft');

            var toastEl = document.getElementById('projectTypeToast');
            var toastTextEl = document.getElementById('projectTypeToastText');
            var toastTimeoutId = null;

            var currentProjectType = null;

            function showProjectTypeToast(message) {
                if (!toastEl || !toastTextEl) return;
                toastTextEl.textContent = message;
                toastEl.classList.add('is-visible');
                if (toastTimeoutId) clearTimeout(toastTimeoutId);
                toastTimeoutId = setTimeout(function () {
                    toastEl.classList.remove('is-visible');
                }, 2200);
            }

            function updateLayerSelectionStates() {
                if (projectTypeParent) {
                    var hasType = Array.prototype.some.call(typeCards, function (c) {
                        return c.classList.contains('selected');
                    });
                    projectTypeParent.classList.toggle('has-selection', hasType);
                }
                if (subtypeParent) {
                    var hasSub = Array.prototype.some.call(subtypeCards, function (c) {
                        return c.classList.contains('selected');
                    });
                    subtypeParent.classList.toggle('has-selection', hasSub);
                }
                if (spaceParent) {
                    var hasSpace = Array.prototype.some.call(spaceCards, function (c) {
                        return c.classList.contains('selected');
                    });
                    spaceParent.classList.toggle('has-selection', hasSpace);
                }
            }

            function recalcTotalSqft() {
                if (!totalSqftInput) return;
                var total = 0;
                spaceCards.forEach(function (card) {
                    if (!card.classList.contains('selected')) return;
                    var sqftInput = card.querySelector('input[data-role="space-sqft"]');
                    if (!sqftInput) return;
                    var val = parseFloat(sqftInput.value);
                    if (!isNaN(val)) total += val;
                });
                totalSqftInput.value = total > 0 ? total : 0;
            }

            function resetChildSelections() {
                if (subtypeSection) subtypeSection.classList.add('is-hidden');
                subtypeCards.forEach(function (c) {
                    c.classList.remove('selected');
                    c.style.display = 'none';
                });
                spaceCards.forEach(function (c) {
                    c.classList.remove('selected');
                });
                recalcTotalSqft();
                updateLayerSelectionStates();
            }

            typeCards.forEach(function (card) {
                card.addEventListener('click', function () {
                    var selectedType = card.getAttribute('data-project-type');
                    var alreadySelected = card.classList.contains('selected');

                    if (alreadySelected) {
                        var confirmMsg = 'All child items will be reset if you De-Select ' + selectedType + '. Continue De-Select?';
                        var proceed = window.confirm(confirmMsg);
                        if (!proceed) return;

                        card.classList.remove('selected');
                        currentProjectType = null;
                        resetChildSelections();
                        return;
                    }

                    typeCards.forEach(function (c) { c.classList.remove('selected'); });
                    card.classList.add('selected');
                    currentProjectType = selectedType;

                    if (subtypeSection) subtypeSection.classList.remove('is-hidden');

                    subtypeCards.forEach(function (subCard) {
                        var ref = subCard.getAttribute('data-project-type-ref');
                        if (ref === selectedType) {
                            subCard.style.display = '';
                        } else {
                            subCard.style.display = 'none';
                            subCard.classList.remove('selected');
                        }
                    });

                    var hint = document.getElementById('subtype-section-hint');
                    if (hint) hint.textContent = 'Sub-types available for ' + selectedType + ' projects.';

                    showProjectTypeToast('You have selected ' + selectedType + ' Project');
                    updateLayerSelectionStates();
                });
            });

            subtypeCards.forEach(function (card) {
                card.addEventListener('click', function () {
                    var refType = card.getAttribute('data-project-type-ref');
                    subtypeCards.forEach(function (c) {
                        if (c.getAttribute('data-project-type-ref') === refType) c.classList.remove('selected');
                    });
                    card.classList.add('selected');
                    updateLayerSelectionStates();
                });
            });

            spaceCards.forEach(function (card) {
                card.addEventListener('click', function (e) {
                    if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.closest('.js-zoom-btn'))) {
                        return;
                    }
                    card.classList.toggle('selected');
                    recalcTotalSqft();
                    updateLayerSelectionStates();
                });

                var sqftInput = card.querySelector('input[data-role="space-sqft"]');
                if (sqftInput) {
                    sqftInput.addEventListener('input', recalcTotalSqft);
                    sqftInput.addEventListener('change', recalcTotalSqft);
                }
            });

            function initializeLayerSliders() {
                var sliders = document.querySelectorAll('.layer-slider');
                sliders.forEach(function (slider) {
                    var viewport = slider.querySelector('.slider-viewport');
                    if (!viewport) return;

                    var leftBtn = slider.querySelector('.slider-arrow-left');
                    var rightBtn = slider.querySelector('.slider-arrow-right');

                    function updateArrowState() {
                        var maxScrollLeft = viewport.scrollWidth - viewport.clientWidth - 1;
                        if (leftBtn) leftBtn.disabled = viewport.scrollLeft <= 0;
                        if (rightBtn) rightBtn.disabled = viewport.scrollLeft >= maxScrollLeft;
                    }

                    if (leftBtn) leftBtn.addEventListener('click', function () {
                        viewport.scrollBy({ left: -viewport.clientWidth * 0.8, behavior: 'smooth' });
                    });

                    if (rightBtn) rightBtn.addEventListener('click', function () {
                        viewport.scrollBy({ left: viewport.clientWidth * 0.8, behavior: 'smooth' });
                    });

                    viewport.addEventListener('scroll', updateArrowState);
                    window.addEventListener('resize', updateArrowState);
                    updateArrowState();
                });
            }

            /* Zoom opens only from zoom button (not from image click) */
            var zoomBackdrop = document.getElementById('imageZoomBackdrop');
            var zoomImg = document.getElementById('imageZoomImg');
            var zoomClose = document.getElementById('imageZoomClose');

            function openZoom(src, alt) {
                if (!zoomImg || !zoomBackdrop) return;
                zoomImg.src = src;
                zoomImg.alt = alt || 'Zoomed image';
                zoomBackdrop.classList.add('is-open');
            }

            function closeZoom() {
                if (!zoomBackdrop || !zoomImg) return;
                zoomBackdrop.classList.remove('is-open');
                zoomImg.src = '';
            }

            document.querySelectorAll('.js-zoom-btn').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    openZoom(btn.getAttribute('data-zoom-src'), btn.getAttribute('data-zoom-alt'));
                });
            });

            if (zoomClose) zoomClose.addEventListener('click', closeZoom);

            if (zoomBackdrop) {
                zoomBackdrop.addEventListener('click', function (e) {
                    if (e.target === zoomBackdrop) closeZoom();
                });
            }

            recalcTotalSqft();
            updateLayerSelectionStates();
            initializeLayerSliders();
        })();
    </script>

    <!-- Close Confirmation Modal -->
    <div class="modal fade" id="crCloseConfirmModal" tabindex="-1" aria-labelledby="crCloseConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content cr-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="crCloseConfirmModalLabel">Close Requisition</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Any unsaved changes may be lost. Do you want to close this requisition?
                </div>
                <div class="modal-footer" style="gap:.5rem;">
                    <button type="button" class="cr-btn cr-btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <a href="{{ $closeUrl ?? 'javascript:history.back()' }}" class="cr-btn cr-btn-primary">Yes, Close</a>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
<script>
(function() {
  // Global flags from controller
  window.__CR_MODE__ = @json($mode);
  window.__CR_CTX__  = @json($ctx);

  // Ensure CSRF exists for fetch() calls if used by the page scripts
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (csrf) {
    window.__CR_CSRF__ = csrf;
  }
})();
</script>

<script>

        (function () {
            var typeCards = document.querySelectorAll('.js-project-type-card');
            var subtypeSection = document.getElementById('subtype-section');
            var subtypeCards = document.querySelectorAll('.js-subtype-card');
            var spaceCards = document.querySelectorAll('.js-space-card');

            var projectTypeParent = document.getElementById('project-type-card');
            var subtypeParent = document.getElementById('subtype-card');
            var spaceParent = document.getElementById('space-card');

            var totalSqftInput = document.getElementById('project_total_sqft');

            var toastEl = document.getElementById('projectTypeToast');
            var toastTextEl = document.getElementById('projectTypeToastText');
            var toastTimeoutId = null;

            var currentProjectType = null;

            function showProjectTypeToast(message) {
                if (!toastEl || !toastTextEl) return;
                toastTextEl.textContent = message;
                toastEl.classList.add('is-visible');
                if (toastTimeoutId) clearTimeout(toastTimeoutId);
                toastTimeoutId = setTimeout(function () {
                    toastEl.classList.remove('is-visible');
                }, 2200);
            }

            function updateLayerSelectionStates() {
                if (projectTypeParent) {
                    var hasType = Array.prototype.some.call(typeCards, function (c) {
                        return c.classList.contains('selected');
                    });
                    projectTypeParent.classList.toggle('has-selection', hasType);
                }
                if (subtypeParent) {
                    var hasSub = Array.prototype.some.call(subtypeCards, function (c) {
                        return c.classList.contains('selected');
                    });
                    subtypeParent.classList.toggle('has-selection', hasSub);
                }
                if (spaceParent) {
                    var hasSpace = Array.prototype.some.call(spaceCards, function (c) {
                        return c.classList.contains('selected');
                    });
                    spaceParent.classList.toggle('has-selection', hasSpace);
                }
            }

            function recalcTotalSqft() {
                if (!totalSqftInput) return;
                var total = 0;
                spaceCards.forEach(function (card) {
                    if (!card.classList.contains('selected')) return;
                    var sqftInput = card.querySelector('input[data-role="space-sqft"]');
                    if (!sqftInput) return;
                    var val = parseFloat(sqftInput.value);
                    if (!isNaN(val)) total += val;
                });
                totalSqftInput.value = total > 0 ? total : 0;
            }

            function resetChildSelections() {
                if (subtypeSection) subtypeSection.classList.add('is-hidden');
                subtypeCards.forEach(function (c) {
                    c.classList.remove('selected');
                    c.style.display = 'none';
                });
                spaceCards.forEach(function (c) {
                    c.classList.remove('selected');
                });
                recalcTotalSqft();
                updateLayerSelectionStates();
            }

            typeCards.forEach(function (card) {
                card.addEventListener('click', function () {
                    var selectedType = card.getAttribute('data-project-type');
                    var alreadySelected = card.classList.contains('selected');

                    if (alreadySelected) {
                        var confirmMsg = 'All child items will be reset if you De-Select ' + selectedType + '. Continue De-Select?';
                        var proceed = window.confirm(confirmMsg);
                        if (!proceed) return;

                        card.classList.remove('selected');
                        currentProjectType = null;
                        resetChildSelections();
                        return;
                    }

                    typeCards.forEach(function (c) { c.classList.remove('selected'); });
                    card.classList.add('selected');
                    currentProjectType = selectedType;

                    if (subtypeSection) subtypeSection.classList.remove('is-hidden');

                    subtypeCards.forEach(function (subCard) {
                        var ref = subCard.getAttribute('data-project-type-ref');
                        if (ref === selectedType) {
                            subCard.style.display = '';
                        } else {
                            subCard.style.display = 'none';
                            subCard.classList.remove('selected');
                        }
                    });

                    var hint = document.getElementById('subtype-section-hint');
                    if (hint) hint.textContent = 'Sub-types available for ' + selectedType + ' projects.';

                    showProjectTypeToast('You have selected ' + selectedType + ' Project');
                    updateLayerSelectionStates();
                });
            });

            subtypeCards.forEach(function (card) {
                card.addEventListener('click', function () {
                    var refType = card.getAttribute('data-project-type-ref');
                    subtypeCards.forEach(function (c) {
                        if (c.getAttribute('data-project-type-ref') === refType) c.classList.remove('selected');
                    });
                    card.classList.add('selected');
                    updateLayerSelectionStates();
                });
            });

            spaceCards.forEach(function (card) {
                card.addEventListener('click', function (e) {
                    if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.closest('.js-zoom-btn'))) {
                        return;
                    }
                    card.classList.toggle('selected');
                    recalcTotalSqft();
                    updateLayerSelectionStates();
                });

                var sqftInput = card.querySelector('input[data-role="space-sqft"]');
                if (sqftInput) {
                    sqftInput.addEventListener('input', recalcTotalSqft);
                    sqftInput.addEventListener('change', recalcTotalSqft);
                }
            });

            function initializeLayerSliders() {
                var sliders = document.querySelectorAll('.layer-slider');
                sliders.forEach(function (slider) {
                    var viewport = slider.querySelector('.slider-viewport');
                    if (!viewport) return;

                    var leftBtn = slider.querySelector('.slider-arrow-left');
                    var rightBtn = slider.querySelector('.slider-arrow-right');

                    function updateArrowState() {
                        var maxScrollLeft = viewport.scrollWidth - viewport.clientWidth - 1;
                        if (leftBtn) leftBtn.disabled = viewport.scrollLeft <= 0;
                        if (rightBtn) rightBtn.disabled = viewport.scrollLeft >= maxScrollLeft;
                    }

                    if (leftBtn) leftBtn.addEventListener('click', function () {
                        viewport.scrollBy({ left: -viewport.clientWidth * 0.8, behavior: 'smooth' });
                    });

                    if (rightBtn) rightBtn.addEventListener('click', function () {
                        viewport.scrollBy({ left: viewport.clientWidth * 0.8, behavior: 'smooth' });
                    });

                    viewport.addEventListener('scroll', updateArrowState);
                    window.addEventListener('resize', updateArrowState);
                    updateArrowState();
                });
            }

            /* Zoom opens only from zoom button (not from image click) */
            var zoomBackdrop = document.getElementById('imageZoomBackdrop');
            var zoomImg = document.getElementById('imageZoomImg');
            var zoomClose = document.getElementById('imageZoomClose');

            function openZoom(src, alt) {
                if (!zoomImg || !zoomBackdrop) return;
                zoomImg.src = src;
                zoomImg.alt = alt || 'Zoomed image';
                zoomBackdrop.classList.add('is-open');
            }

            function closeZoom() {
                if (!zoomBackdrop || !zoomImg) return;
                zoomBackdrop.classList.remove('is-open');
                zoomImg.src = '';
            }

            document.querySelectorAll('.js-zoom-btn').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    openZoom(btn.getAttribute('data-zoom-src'), btn.getAttribute('data-zoom-alt'));
                });
            });

            if (zoomClose) zoomClose.addEventListener('click', closeZoom);

            if (zoomBackdrop) {
                zoomBackdrop.addEventListener('click', function (e) {
                    if (e.target === zoomBackdrop) closeZoom();
                });
            }

            recalcTotalSqft();
            updateLayerSelectionStates();
            initializeLayerSliders();
        })();
    

  // ===== Client Attachments preview (multi-file) =====
  function crRenderAttachmentPreviews(){
    const input = document.getElementById('attachments');
    const wrap  = document.getElementById('attachments-preview');
    if(!input || !wrap) return;

    wrap.innerHTML = '';
    const files = Array.from(input.files || []);
    if(!files.length) return;

    files.forEach((file, idx) => {
      const col = document.createElement('div');
      col.className = 'col-6 col-md-3 col-lg-2';

      const card = document.createElement('div');
      card.className = 'border rounded p-2 h-100 d-flex flex-column';

      const ratio = document.createElement('div');
      ratio.className = 'ratio ratio-1x1 bg-light rounded overflow-hidden';

      const img = document.createElement('img');
      img.alt = file.name || ('Attachment ' + (idx+1));
      img.style.width = '100%';
      img.style.height = '100%';
      img.style.objectFit = 'cover';
      img.src = URL.createObjectURL(file);

      ratio.appendChild(img);

      const meta = document.createElement('div');
      meta.className = 'small text-muted mt-2 text-truncate';
      meta.title = file.name;
      meta.textContent = file.name;

      card.appendChild(ratio);
      card.appendChild(meta);
      col.appendChild(card);
      wrap.appendChild(col);
    });
  }

  document.addEventListener('change', function(e){
    if(e && e.target && e.target.id === 'attachments'){
      crRenderAttachmentPreviews();
    }
  });

  window.addEventListener('pageshow', function(){
    crRenderAttachmentPreviews();
  });
  // ===== /Client Attachments preview =====

</script>


<script>
/* TMX_ATTACHMENTS_APPEND_DT: attachments add-more + append-mode preview (DataTransfer) */
(function(){
  const input = document.getElementById('attachments');
  const wrap  = document.getElementById('attachments-preview');
  const btnAdd = document.getElementById('btnAddMoreAttachments');
  const btnClear = document.getElementById('btnClearAttachments');

  if(!input || !wrap) return;

  // Keep selections across multiple picker opens (allows multi-folder selection across rounds)
  const dt = new DataTransfer();

  function syncInputFiles(){
    input.files = dt.files;
  }

  function render(){
    wrap.innerHTML = '';
    const files = Array.from(dt.files || []);
    if(!files.length) return;

    files.forEach((file) => {
      const col = document.createElement('div');
      col.className = 'col-6 col-md-3 col-lg-2';

      const card = document.createElement('div');
      card.className = 'border rounded p-2 h-100 d-flex flex-column';

      const ratio = document.createElement('div');
      ratio.className = 'ratio ratio-1x1 bg-light rounded overflow-hidden';

      const img = document.createElement('img');
      img.alt = file.name || 'Attachment';
      img.style.width='100%';
      img.style.height='100%';
      img.style.objectFit='cover';
      img.src = URL.createObjectURL(file);

      ratio.appendChild(img);

      const meta = document.createElement('div');
      meta.className = 'small text-muted mt-2 text-truncate';
      meta.title = file.name || '';
      meta.textContent = file.name || '';

      card.appendChild(ratio);
      card.appendChild(meta);

      col.appendChild(card);
      wrap.appendChild(col);
    });
  }

  input.addEventListener('change', () => {
    Array.from(input.files || []).forEach(f => dt.items.add(f));
    syncInputFiles();
    render();
  });

  if(btnAdd){
    btnAdd.addEventListener('click', () => input.click());
  }

  if(btnClear){
    btnClear.addEventListener('click', () => {
      while(dt.items.length) dt.items.remove(0);
      syncInputFiles();
      render();
    });
  }

  // If user navigates back and browser restores DOM, re-render current selection
  window.addEventListener('pageshow', render);
})();
</script>

@endpush
