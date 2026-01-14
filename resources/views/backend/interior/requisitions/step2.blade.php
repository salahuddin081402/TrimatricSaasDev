@extends('backend.layouts.master')
{{-- TMX-CR | resources/views/backend/interior/requisitions/step2.blade.php
     Client Interior Requisition | Step-2 (Create/Edit/View)
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
@endphp

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>

        :root {
            --bg: #f8fafc;
            --bg-soft: #f3f4ff;
            --border-subtle: #d4ddff;
            --ink: #0f172a;
            --muted: #5b6478;
            --accent: #f59e0b;
            --accent-soft: rgba(245, 158, 11, 0.12);
            --accent-strong: #ea580c;
            --pill-bg: #eef2ff;
            --radius-card: 20px 20px 16px 16px;
            --radius-pill: 999px;
            --shadow-soft: 0 18px 40px rgba(15, 23, 42, 0.08);
            --shadow-hover: 0 20px 45px rgba(15, 23, 42, 0.14);
            --error: #b91c1c;
            --success: #16a34a;
            --layer-card-bg: #f5f3ff;
            --layer-selection-border: #16a34a;
            --section-title-color: #111827;
            --section-subtitle-color: #475569;
            --panel-bg: radial-gradient(140% 160% at 0% 0%, #ffffff 0%, #eef2ff 40%, #f9fafb 100%);
            --panel-bg-strong: #edf2ff;
            --meta-text: #4b5563;
            --meta-soft: #64748b;
            --meta-strong: #111827;

            /* Album-like selection border */
            --album-border: #ea580c;
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
            padding: 10px 10px 5px;
        }

        @media (min-width: 992px) {
            .cr-container {
                padding: 32px 16px 5px;
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
            background: var(--panel-bg);
            border: 1px solid var(--border-subtle);
            box-shadow: var(--shadow-soft);
        }

        .cr-layer-card {
            background: linear-gradient(135deg, var(--layer-card-bg), #eef2ff);
            border-width: 1px;
        }

        .cr-layer-card.has-selection {
            border-color: var(--layer-selection-border);
            box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.2), var(--shadow-soft);
        }

        .cr-main-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 16px;
        }

        @media (min-width: 992px) {
            .cr-main-layout {
                grid-template-columns: 230px minmax(0, 1fr);
            }
        }

        .cr-space-nav-wrapper {
            padding: 10px 10px 12px;
        }

        .cr-space-nav-title {
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .cr-space-nav-subtitle {
            font-size: 0.8rem;
            color: var(--meta-soft);
            margin-bottom: 10px;
        }

        .cr-space-nav-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        @media (max-width: 991.98px) {
            .cr-main-layout {
                gap: 16px;
            }

            .cr-space-nav-wrapper {
                position: sticky;
                top: 52px;
                z-index: 30;
                overflow-x: auto;
                border-radius: 0 0 16px 16px;
                box-shadow: 0 12px 30px rgba(15, 23, 42, 0.16);
                background-color: var(--bg);
            }

            .cr-space-nav-list {
                flex-wrap: nowrap;
                overflow-x: auto;
                padding-bottom: 4px;
            }
        }

        .cr-space-pill {
            border-radius: var(--radius-pill);
            border: 1px solid var(--border-subtle);
            background-color: #ffffff;
            padding: 6px 10px;
            font-size: 0.78rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            white-space: nowrap;
            transition: background-color 0.16s ease, border-color 0.16s ease, transform 0.12s ease, box-shadow 0.16s ease;
        }

        .cr-space-pill:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
        }

        .cr-space-pill-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--muted);
        }

        .cr-space-pill.selected {
            border-color: #800000;
            background-color: var(--accent-soft);
        }

        .cr-space-pill.selected .cr-space-pill-dot {
            background-color: var(--accent-strong);
        }

        .cr-center-panel {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .cr-breadcrumb {
            font-size: 0.8rem;
            color: var(--meta-soft);
            padding: 6px 12px;
            border-radius: var(--radius-pill);
            background-color: #ffffff;
            border: 1px solid var(--border-subtle);
            display: inline-flex;
            flex-wrap: wrap;
            gap: 4px;
            align-items: center;
        }

        .cr-breadcrumb span {
            display: inline-flex;
            align-items: center;
        }

        .cr-breadcrumb-sep {
            padding: 0 6px;
        }

        .cr-panel-card {
            padding: 14px 12px 16px;
        }

        @media (min-width: 768px) {
            .cr-panel-card {
                padding: 16px 16px 18px;
            }
        }

        .cr-panel-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
        }

        .cr-panel-title {
            font-size: 1rem;
            font-weight: 500;
        }

        .cr-panel-subtitle {
            font-size: 0.82rem;
            color: var(--meta-soft);
        }

        /* --- minimal switch row (category/subcategory focus) --- */
        .cr-switch-row {
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .cr-switch-chip {
            border-radius: 999px;
            border: 1px solid var(--border-subtle);
            background: #fff;
            padding: 6px 10px;
            font-size: 0.78rem;
            color: var(--meta-strong);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: transform 0.12s ease, box-shadow 0.16s ease, border-color 0.16s ease, background-color 0.16s ease;
            user-select: none;
            white-space: nowrap;
        }

        .cr-switch-chip:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
        }

        .cr-switch-chip .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--meta-soft);
        }

        .cr-switch-chip.picked {
            border-color: rgba(128, 0, 0, 0.35);
            background: rgba(245, 158, 11, 0.12);
        }

        .cr-switch-chip.picked .dot {
            background: var(--accent-strong);
        }

        .cr-switch-chip.active {
            border-color: var(--album-border);
            box-shadow: 0 0 0 3px rgba(128, 0, 0, 0.25);
        }

        .cr-switch-hint {
            font-size: 0.78rem;
            color: var(--meta-soft);
        }

        /* --- common zoom button (like Step-1) --- */
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

        /* --- green tick (like Step-1) --- */
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
            z-index: 4;
        }

        .is-selected .cr-card-tick {
            opacity: 1;
            transform: scale(1);
        }

        /* --- Category/Subcategory cards --- */
        .cr-circle-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        @media (min-width: 576px) {
            .cr-circle-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (min-width: 992px) {
            .cr-circle-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .cr-circle-card {
            border-radius: var(--radius-card);
            background-color: #ffffff;
            border: 1px solid var(--border-subtle);
            box-shadow: var(--shadow-soft);
            padding: 10px 10px 12px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
            gap: 8px;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background-color 0.18s ease;
        }

        .cr-circle-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-hover);
            background-color: #fdfdfd;
        }

        /* Album-like selected */
        .cr-circle-card.selected {
            border-color: var(--album-border);
            box-shadow:
                0 0 0 4px rgba(128, 0, 0, 0.92),
                0 0 0 6px rgba(255, 255, 255, 0.95),
                var(--shadow-hover);
            background-color: var(--panel-bg-strong);
            transform: translateY(-3px);
        }

        .cr-circle-avatar {
            width: 100%;
            height: 96px;
            border-radius: 14px;
            overflow: hidden;
            background-color: #e5e7eb;
            position: relative;
        }

        @media (min-width: 576px) {
            .cr-circle-avatar {
                height: 110px;
            }
        }

        .cr-circle-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transform: scale(1.02);
        }

        /* Disable zoom cursor/magnifier */
        .cr-zoomable {
            cursor: default;
        }

        .cr-circle-label {
            font-size: 0.86rem;
            font-weight: 500;
        }

        .cr-circle-meta {
            font-size: 0.78rem;
            color: var(--meta-text);
        }

        /* --- Product cards --- */
        .cr-product-card {
            border-radius: var(--radius-card);
            background-color: #ffffff;
            border: 1px solid var(--border-subtle);
            box-shadow: var(--shadow-soft);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
            position: relative;
        }

        .cr-product-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-hover);
        }

        /* Album-like selected */
        .cr-product-card.selected {
            border-color: var(--album-border);
            box-shadow:
                0 0 0 4px rgba(128, 0, 0, 0.92),
                0 0 0 6px rgba(255, 255, 255, 0.95),
                var(--shadow-hover);
            transform: translateY(-3px);
        }

        .cr-product-media {
            position: relative;
            height: 140px;
            overflow: hidden;
            border-radius: var(--radius-card);
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        .cr-product-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .cr-product-media-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(15, 23, 42, 0.55), transparent 60%);
            pointer-events: none;
        }

        /* Replace old check with Step-1 style */
        .cr-product-check {
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
            z-index: 4;
        }

        .cr-product-card.selected .cr-product-check {
            opacity: 1;
            transform: scale(1);
        }

        .cr-product-body {
            padding: 10px 11px 11px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 0.82rem;
        }

        .cr-product-title {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .cr-product-meta {
            color: var(--meta-text);
            line-height: 1.45;
        }

        .cr-product-meta strong {
            color: var(--meta-strong);
            font-weight: 500;
        }

        .cr-product-footer {
            margin-top: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .cr-qty-control {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background-color: var(--bg-soft);
            border-radius: var(--radius-pill);
            padding: 4px 6px;
        }

        .cr-qty-btn {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: none;
            background-color: #ffffff;
            border: 1px solid var(--border-subtle);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            cursor: pointer;
        }

        .cr-qty-input {
            width: 32px;
            border: none;
            background-color: transparent;
            text-align: center;
            font-size: 0.82rem;
        }

        .cr-qty-input:focus {
            outline: none;
        }

        .cr-product-tag {
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: var(--radius-pill);
            border: 1px solid var(--border-subtle);
            background-color: #ffffff;
        }

        /* Top "View Selected Items" button */
        .cr-view-selected-btn {
            position: fixed;
            top: 12px;
            right: 12px;
            z-index: 40;
            border-radius: var(--radius-pill);
            border: none;
            padding: 8px 16px;
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            color: #111827;
            box-shadow: 0 16px 36px rgba(248, 181, 43, 0.5);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .cr-view-selected-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #22c55e;
            box-shadow: 0 0 12px rgba(34, 197, 94, 0.9);
        }

        @media (max-width: 576px) {
            .cr-view-selected-btn {
                width: 100%;
                left: 0;
                right: 0;
                border-radius: 0;
                justify-content: center;
            }

            body {
                padding-top: 44px;
            }
        }

        .cr-actions {
            margin-top: 18px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
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
            transform: translateY(-1px);
            box-shadow: 0 18px 40px rgba(248, 181, 43, 0.5);
        }

        .cr-btn-secondary {
            background-color: #ffffff;
            color: var(--meta-soft);
            border: 1px solid var(--border-subtle);
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

        /* Slider */
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

        .slider-item-product {
            flex: 0 0 calc(50% - 10px);
        }

        @media (min-width: 992px) {
            .slider-item-product {
                flex: 0 0 calc(33.333% - 12px);
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
            color: var(--meta-soft);
            text-align: right;
        }

        .cr-products-meta {
            margin-top: 6px;
            font-size: 0.78rem;
            color: var(--meta-soft);
            text-align: right;
        }

        .cr-products-meta span {
            color: var(--accent-strong);
            font-weight: 600;
        }

        /* Drawer + overlays */
        .cr-overlay {
            position: fixed;
            inset: 0;
            background-color: rgba(15, 23, 42, 0.55);
            display: none;
            z-index: 60;
        }

        .cr-overlay.is-open {
            display: block;
        }

        .cr-modal-panel {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #ffffff;
            border-radius: 16px;
            max-width: 420px;
            width: 92%;
            padding: 16px 16px 14px;
            box-shadow: 0 22px 60px rgba(15, 23, 42, 0.5);
            z-index: 70;
        }

        .cr-modal-title {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 6px;
        }

        .cr-modal-text {
            font-size: 0.86rem;
            color: var(--meta-soft);
            margin-bottom: 10px;
        }

        .cr-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 6px;
        }

        .cr-modal-close-btn {
            position: absolute;
            top: 8px;
            right: 10px;
            border: none;
            background: rgba(15, 23, 42, 0.04);
            border-radius: 999px;
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1rem;
        }

        .cr-summary-drawer {
            position: fixed;
            top: 0;
            right: 0;
            width: 420px;
            max-width: 100%;
            height: 100vh;
            background-color: #ffffff;
            border-left: 1px solid var(--border-subtle);
            box-shadow: -18px 0 40px rgba(15, 23, 42, 0.25);
            transform: translateX(100%);
            transition: transform 0.25s ease-out;
            z-index: 80;
            display: flex;
            flex-direction: column;
        }

        .cr-summary-drawer.is-open {
            transform: translateX(0);
        }

        @media (max-width: 768px) {
            .cr-summary-drawer {
                width: 100%;
            }
        }

        .cr-summary-header {
            padding: 12px 16px 10px;
            border-bottom: 1px solid var(--border-subtle);
            position: relative;
        }

        .cr-summary-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .cr-summary-subtitle {
            font-size: 0.78rem;
            color: var(--meta-soft);
        }

        .cr-summary-actions {
            margin-top: 10px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .cr-summary-body {
            padding: 12px 16px 90px;
            overflow-y: auto;
            font-size: 0.82rem;
        }

        .cr-summary-section-title {
            font-size: 0.86rem;
            font-weight: 700;
            margin-top: 12px;
            margin-bottom: 6px;
        }

        .cr-summary-close {
            position: absolute;
            top: 10px;
            right: 12px;
            border: none;
            background: rgba(15, 23, 42, 0.04);
            border-radius: 999px;
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1rem;
        }

        .cr-chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 6px;
        }

        .cr-chip {
            font-size: 0.72rem;
            padding: 4px 8px;
            border-radius: 999px;
            border: 1px solid var(--border-subtle);
            background: #ffffff;
            color: var(--meta-strong);
        }

        .cr-chip.accent {
            border-color: rgba(128, 0, 0, 0.35);
            background: rgba(245, 158, 11, 0.12);
        }

        .cr-summary-space {
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            padding: 10px 10px;
            background: #fff;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.06);
            margin-top: 10px;
        }

        .cr-summary-space-head {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: flex-start;
        }

        .cr-summary-space-title {
            font-weight: 700;
            font-size: 0.9rem;
        }

        .cr-summary-space-meta {
            font-size: 0.76rem;
            color: var(--meta-soft);
        }

        .cr-summary-products {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-top: 10px;
        }

        .cr-summary-product {
            display: grid;
            grid-template-columns: 64px minmax(0, 1fr);
            gap: 10px;
            align-items: center;
            border: 1px solid var(--border-subtle);
            border-radius: 12px;
            padding: 8px;
            background: #fff;
        }

        .cr-summary-product img {
            width: 64px;
            height: 48px;
            object-fit: cover;
            border-radius: 10px;
            display: block;
            background: #e5e7eb;
        }

        .cr-summary-product-title {
            font-weight: 600;
            font-size: 0.84rem;
            margin-bottom: 2px;
        }

        .cr-summary-product-meta {
            font-size: 0.76rem;
            color: var(--meta-soft);
            line-height: 1.35;
        }

        .cr-summary-footer-note {
            margin-top: 12px;
            font-size: 0.78rem;
            color: var(--meta-soft);
            border-top: 1px dashed var(--border-subtle);
            padding-top: 10px;
        }

        /* Zoom modal */
        .cr-modal-backdrop,
        .cr-zoom-backdrop {
            position: fixed;
            inset: 0;
            background-color: rgba(15, 23, 42, 0.55);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 90;
        }

        .cr-modal-backdrop.is-open,
        .cr-zoom-backdrop.is-open {
            display: flex;
        }

        .cr-modal,
        .cr-zoom-modal {
            background-color: #ffffff;
            border-radius: 16px;
            max-width: 90vw;
            max-height: 90vh;
            overflow: auto;
            box-shadow: 0 22px 60px rgba(15, 23, 42, 0.5);
            position: relative;
        }

        .cr-modal img,
        .cr-zoom-modal img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        .cr-modal-close,
        .cr-zoom-close {
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

            .cr-modal,
            .cr-zoom-modal {
                width: 100%;
                height: 100%;
                max-width: 100%;
                max-height: 100%;
                border-radius: 0;
            }
        }

        @media (max-width: 480px) {
            .cr-panel-title {
                font-size: 0.98rem;
            }

            .cr-panel-subtitle,
            .cr-breadcrumb,
            .cr-space-nav-subtitle {
                font-size: 0.8rem;
            }

            .cr-product-title {
                font-size: 0.88rem;
            }

            .cr-circle-label {
                font-size: 0.84rem;
            }
        }
    
';
                html += 'html,body{margin:0;padding:0;font-family:Poppins,system-ui,-apple-system,Segoe UI,sans-serif;color:#0f172a;}';
                html += '@page{size:A4; margin:14mm;}';
                html += '.page{width:210mm; min-height:297mm; padding:0; box-sizing:border-box;}';
                html += '.hdr{display:flex; justify-content:space-between; align-items:flex-start; gap:12px; border-bottom:1px solid #e5e7eb; padding-bottom:10px; margin-bottom:12px;}';
                html += '.brand{display:flex; gap:10px; align-items:center;}';
                html += '.mark{width:34px;height:34px;border-radius:50%;background:radial-gradient(circle at 20% 10%, #facc15, #f97316 40%, #0ea5e9 100%);}';
                html += '.h1{font-size:16px;font-weight:700;margin:0;}';
                html += '.sub{font-size:11px;color:#64748b;margin-top:2px;}';
                html += '.meta{font-size:11px;color:#64748b;text-align:right;}';
                html += '.card{border:1px solid #e5e7eb;border-radius:12px;padding:10px;margin-bottom:10px;}';
                html += '.title{font-size:12px;font-weight:700;margin:0 0 6px 0;}';
                html += '.grid2{display:grid;grid-template-columns:1fr 1fr;gap:10px;}';
                html += '.kv{display:flex;gap:8px;align-items:flex-start;font-size:11px;line-height:1.35;}';
                html += '.k{min-width:95px;color:#475569;font-weight:600;}';
                html += '.v{color:#0f172a;flex:1;}';
                html += '.chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;}';
                html += '.chip{font-size:10px;padding:3px 8px;border-radius:999px;border:1px solid #e5e7eb;background:#fff;}';
                html += '.chip.accent{border-color:rgba(234,88,12,.35);background:rgba(245,158,11,.12);}';
                html += '.space{page-break-inside:avoid;}';
                html += '.prod{display:grid;grid-template-columns:76px minmax(0,1fr);gap:10px;align-items:center;border:1px solid #e5e7eb;border-radius:12px;padding:8px;margin-top:8px;}';
                html += '.prod img{width:76px;height:56px;object-fit:cover;border-radius:10px;background:#e5e7eb;display:block;}';
                html += '.pname{font-size:11px;font-weight:700;margin:0;}';
                html += '.pmeta{font-size:10px;color:#475569;line-height:1.35;margin-top:2px;}';
                html += '.foot{margin-top:10px;font-size:10px;color:#64748b;border-top:1px dashed #e5e7eb;padding-top:8px;}';
                html += '@media print{.no-print{display:none !important;}}';
                html += '
</style>
@endpush

@section('content')

    <button type="button" class="cr-view-selected-btn" id="viewSelectedBtn">
        <span class="cr-view-selected-dot"></span>
        <span>View Selected Items</span>
    </button>

    <div class="cr-page">
        <div class="cr-container">
            <h1 class="cr-section-title">Detailed Requirements &amp; Products</h1>
            <p class="cr-section-subtitle">Client Requisition – Step 2</p>
            <div class="alert alert-warning mt-3 mb-0" role="alert" style="border-radius: 14px;">
                <strong>Support Note:</strong>
                To assist you in Fill-up the Requisition, You may contact to Your Nearest Cluster Admin. Mobile: <span class="fw-semibold">{{ $clusterAdminMobile ?? 'xxxxxxxx' }}</span>.
                He will give you support until your Project/Requisition Completion.
            </div>

        </div>

        <main>
            <div class="cr-container">
                <form id="cr-step2-form" novalidate action="{{ route($reqRoute('step2.save'), ['company' => $companySlug]) }}">
    @csrf
    <input type="hidden" name="requisition_id" value="{{ $requisition->id ?? '' }}">
    <input type="hidden" name="mode" value="{{ $mode }}">
    <input type="hidden" name="go_next" id="go_next" value="0">

                    <div class="cr-main-layout">
                        <aside class="cr-card cr-space-nav-wrapper" id="spaceNavCard">
                            <div class="cr-space-nav-title">Spaces in this Requisition</div>
                            <div class="cr-space-nav-subtitle">
                                Switch between rooms to choose categories and products.
                            </div>
                            <div class="cr-space-nav-list" id="spaceNavList">
                                <button type="button" class="cr-space-pill selected" data-space="Master Bed Room">
                                    <span class="cr-space-pill-dot"></span>
                                    <span>Master Bed Room</span>
                                </button>
                                <button type="button" class="cr-space-pill" data-space="Child Bed Room">
                                    <span class="cr-space-pill-dot"></span>
                                    <span>Child Bed Room</span>
                                </button>
                                <button type="button" class="cr-space-pill" data-space="Living / Lounge">
                                    <span class="cr-space-pill-dot"></span>
                                    <span>Living / Lounge</span>
                                </button>
                                <button type="button" class="cr-space-pill" data-space="Dining">
                                    <span class="cr-space-pill-dot"></span>
                                    <span>Dining</span>
                                </button>
                            </div>
                        </aside>

                        <section class="cr-center-panel">
                            <div class="cr-breadcrumb" id="breadcrumb">
                                <span id="breadcrumb-type">Residential</span>
                                <span class="cr-breadcrumb-sep">→</span>
                                <span id="breadcrumb-subtype">Duplex</span>
                                <span class="cr-breadcrumb-sep">→</span>
                                <span id="breadcrumb-space">Master Bed Room</span>
                                <span class="cr-breadcrumb-sep">→</span>
                                <span id="breadcrumb-category">Furniture</span>
                                <span class="cr-breadcrumb-sep">→</span>
                                <span id="breadcrumb-subcategory">Double Person Bed</span>
                            </div>

                            <!-- Categories -->
                            <div class="cr-card cr-panel-card cr-layer-card" id="categoryCard">
                                <div class="cr-panel-header">
                                    <div>
                                        <div class="cr-panel-title cr-section-title">Item Categories</div>
                                        <div class="cr-panel-subtitle cr-section-subtitle">
                                            Select the categories you want to include for this space.
                                        </div>
                                    </div>
                                    <div class="cr-panel-subtitle cr-section-subtitle">
                                        Space: <strong id="categorySpaceLabel">Master Bed Room</strong>
                                    </div>
                                </div>

                                <div class="layer-slider" data-layer="category" data-page="1" data-has-more="false">
                                    <button class="slider-arrow slider-arrow-left" type="button"
                                        aria-label="Scroll left">&lt;</button>
                                    <div class="slider-viewport">
                                        <div class="slider-track" id="categoryGrid">
                                            <div class="slider-item">
                                                <article class="cr-circle-card js-category-card"
                                                    data-category="Furniture">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1505691723518-36a5ac3be353?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Bedroom furniture"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1505691723518-36a5ac3be353?auto=format&fit=crop&w=900&q=80"
                                                            alt="Bedroom furniture">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Furniture</div>
                                                    <div class="cr-circle-meta">Bed, side tables, wardrobes, study.
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-circle-card js-category-card"
                                                    data-category="Lighting">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1473181488821-2d23949a045a?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Interior lighting fixtures"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1473181488821-2d23949a045a?auto=format&fit=crop&w=900&q=80"
                                                            alt="Interior lighting fixtures">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Lighting</div>
                                                    <div class="cr-circle-meta">Ceiling, wall, pendant &amp; task
                                                        lights.
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-circle-card js-category-card"
                                                    data-category="Curtains &amp; Fabrics">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1519710164239-da123dc03ef4?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Curtains and fabrics"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1519710164239-da123dc03ef4?auto=format&fit=crop&w=900&q=80"
                                                            alt="Curtains and fabrics">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Curtains &amp; Fabrics</div>
                                                    <div class="cr-circle-meta">Curtains, blinds, bed runners, cushions.
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-circle-card js-category-card"
                                                    data-category="Wall Treatment">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Wall paneling and paint"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=900&q=80"
                                                            alt="Wall paneling and paint">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Wall Treatment</div>
                                                    <div class="cr-circle-meta">Paint, wallpaper, wall panels.</div>
                                                </article>
                                            </div>
                                        </div>
                                    </div>
                                    <button class="slider-arrow slider-arrow-right" type="button"
                                        aria-label="Scroll right">&gt;</button>
                                </div>
                                <div class="cr-slider-meta">Total Images: 4</div>
                            </div>

                            <!-- Subcategories -->
                            <div class="cr-card cr-panel-card cr-layer-card" id="subcategoryCard">
                                <div class="cr-panel-header">
                                    <div>
                                        <div class="cr-panel-title cr-section-title">Sub-Categories</div>
                                        <div class="cr-panel-subtitle cr-section-subtitle">
                                            You can select multiple sub-categories. Use the “Active Category” chips to
                                            switch context.
                                        </div>

                                        <div class="cr-switch-row" id="catSwitchRow">
                                            <!-- Filled by JS -->
                                        </div>
                                    </div>
                                    <div class="cr-panel-subtitle cr-section-subtitle">
                                        Category: <strong id="subcategoryCategoryLabel">Furniture</strong>
                                    </div>
                                </div>

                                <div class="layer-slider" data-layer="subcategory" data-page="1" data-has-more="false">
                                    <button class="slider-arrow slider-arrow-left" type="button"
                                        aria-label="Scroll left">&lt;</button>
                                    <div class="slider-viewport">
                                        <div class="slider-track" id="subcategoryGrid">

                                            <!-- ===== Furniture (More cards) ===== -->
                                            <div class="slider-item">
                                                <article class="cr-circle-card js-subcategory-card"
                                                    data-parent-category="Furniture"
                                                    data-subcategory-id="SC-FUR-BED"
                                                    data-subcategory="Double Person Bed">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1505691723518-36a5ac3be353?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Double bed" aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1505691723518-36a5ac3be353?auto=format&fit=crop&w=900&q=80"
                                                            alt="Double bed">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Double Person Bed</div>
                                                    <div class="cr-circle-meta">King/Queen bed &amp; headboard styles.
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-circle-card js-subcategory-card"
                                                    data-parent-category="Furniture"
                                                    data-subcategory-id="SC-FUR-WARD"
                                                    data-subcategory="Wardrobe">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1523217582562-09d0def993a6?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Wardrobe" aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1523217582562-09d0def993a6?auto=format&fit=crop&w=900&q=80"
                                                            alt="Wardrobe">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Wardrobe</div>
                                                    <div class="cr-circle-meta">Sliding/hinged wardrobes &amp; internals.
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-circle-card js-subcategory-card"
                                                    data-parent-category="Furniture"
                                                    data-subcategory-id="SC-FUR-BSIDE"
                                                    data-subcategory="Bedside Table">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1486946255434-2466348c2166?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Bedside table" aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1486946255434-2466348c2166?auto=format&fit=crop&w=900&q=80"
                                                            alt="Bedside table">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Bedside Table</div>
                                                    <div class="cr-circle-meta">Side tables with drawers &amp; shelves.
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-circle-card js-subcategory-card"
                                                    data-parent-category="Furniture"
                                                    data-subcategory-id="SC-FUR-DESK"
                                                    data-subcategory="Study Desk">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Study desk" aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=900&q=80"
                                                            alt="Study desk">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Study Desk</div>
                                                    <div class="cr-circle-meta">Compact desks for reading/work.</div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-circle-card js-subcategory-card"
                                                    data-parent-category="Furniture"
                                                    data-subcategory-id="SC-FUR-DRESS"
                                                    data-subcategory="Dressing Table">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1586023492125-27b2c045efd7?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Dressing table" aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1586023492125-27b2c045efd7?auto=format&fit=crop&w=900&q=80"
                                                            alt="Dressing table">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Dressing Table</div>
                                                    <div class="cr-circle-meta">Mirror unit, drawers &amp; vanity stool.
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-circle-card js-subcategory-card"
                                                    data-parent-category="Furniture"
                                                    data-subcategory-id="SC-FUR-TV"
                                                    data-subcategory="TV Console">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="TV console" aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=900&q=80"
                                                            alt="TV console">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">TV Console</div>
                                                    <div class="cr-circle-meta">Low console units &amp; wall shelves.
                                                    </div>
                                                </article>
                                            </div>

                                            <!-- ===== Lighting (More cards) ===== -->
                                            <div class="slider-item">
                                                <article class="cr-circle-card js-subcategory-card"
                                                    data-parent-category="Lighting"
                                                    data-subcategory-id="SC-LGT-CEIL"
                                                    data-subcategory="Ceiling Light">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1524484485831-a92ffc0de03f?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Ceiling light" aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1524484485831-a92ffc0de03f?auto=format&fit=crop&w=900&q=80"
                                                            alt="Ceiling light">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Ceiling Light</div>
                                                    <div class="cr-circle-meta">Flush-mount, surface panels &amp; LED.
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-circle-card js-subcategory-card"
                                                    data-parent-category="Lighting"
                                                    data-subcategory-id="SC-LGT-WALL"
                                                    data-subcategory="Wall Sconce">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1493666438817-866a91353ca9?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Wall sconce" aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1493666438817-866a91353ca9?auto=format&fit=crop&w=900&q=80"
                                                            alt="Wall sconce">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Wall Sconce</div>
                                                    <div class="cr-circle-meta">Accent lighting for bedside/walls.</div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-circle-card js-subcategory-card"
                                                    data-parent-category="Lighting"
                                                    data-subcategory-id="SC-LGT-PEND"
                                                    data-subcategory="Pendant Light">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1519710164239-da123dc03ef4?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Pendant light" aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1519710164239-da123dc03ef4?auto=format&fit=crop&w=900&q=80"
                                                            alt="Pendant light">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Pendant Light</div>
                                                    <div class="cr-circle-meta">Single/double pendants for feature areas.
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-circle-card js-subcategory-card"
                                                    data-parent-category="Lighting"
                                                    data-subcategory-id="SC-LGT-LAMP"
                                                    data-subcategory="Bedside Lamp">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1486946255434-2466348c2166?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Bedside lamp" aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1486946255434-2466348c2166?auto=format&fit=crop&w=900&q=80"
                                                            alt="Bedside lamp">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Bedside Lamp</div>
                                                    <div class="cr-circle-meta">Table lamps for reading comfort.</div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-circle-card js-subcategory-card"
                                                    data-parent-category="Lighting"
                                                    data-subcategory-id="SC-LGT-STRIP"
                                                    data-subcategory="LED Strip">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1519710164239-da123dc03ef4?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="LED strip lighting"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1519710164239-da123dc03ef4?auto=format&fit=crop&w=900&q=80"
                                                            alt="LED strip lighting">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">LED Strip</div>
                                                    <div class="cr-circle-meta">Cove lighting, behind headboard, shelves.
                                                    </div>
                                                </article>
                                            </div>

                                            <!-- ===== Curtains & Fabrics (More cards) ===== -->
                                            <div class="slider-item">
                                                <article class="cr-circle-card js-subcategory-card"
                                                    data-parent-category="Curtains &amp; Fabrics"
                                                    data-subcategory-id="SC-CUR-WIN"
                                                    data-subcategory="Window Curtains">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Window curtains" aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=900&q=80"
                                                            alt="Window curtains">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Window Curtains</div>
                                                    <div class="cr-circle-meta">Day-to-day curtains: fabrics &amp; lining.
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-circle-card js-subcategory-card"
                                                    data-parent-category="Curtains &amp; Fabrics"
                                                    data-subcategory-id="SC-CUR-BLK"
                                                    data-subcategory="Blackout Curtains">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1519710164239-da123dc03ef4?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Blackout curtains"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1519710164239-da123dc03ef4?auto=format&fit=crop&w=900&q=80"
                                                            alt="Blackout curtains">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Blackout Curtains</div>
                                                    <div class="cr-circle-meta">Hotel-grade blackout for better sleep.
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-circle-card js-subcategory-card"
                                                    data-parent-category="Curtains &amp; Fabrics"
                                                    data-subcategory-id="SC-CUR-SHE"
                                                    data-subcategory="Sheer Curtains">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1505691723518-36a5ac3be353?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Sheer curtains" aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1505691723518-36a5ac3be353?auto=format&fit=crop&w=900&q=80"
                                                            alt="Sheer curtains">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Sheer Curtains</div>
                                                    <div class="cr-circle-meta">Soft daylight diffusion + privacy.</div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-circle-card js-subcategory-card"
                                                    data-parent-category="Curtains &amp; Fabrics"
                                                    data-subcategory-id="SC-CUR-ROM"
                                                    data-subcategory="Roman Blinds">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Roman blinds" aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=900&q=80"
                                                            alt="Roman blinds">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Roman Blinds</div>
                                                    <div class="cr-circle-meta">Neat folds; ideal for compact windows.
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-circle-card js-subcategory-card"
                                                    data-parent-category="Curtains &amp; Fabrics"
                                                    data-subcategory-id="SC-CUR-CUS"
                                                    data-subcategory="Cushion Covers">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1586023492125-27b2c045efd7?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Cushion covers" aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1586023492125-27b2c045efd7?auto=format&fit=crop&w=900&q=80"
                                                            alt="Cushion covers">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Cushion Covers</div>
                                                    <div class="cr-circle-meta">Accent cushions matching theme palette.
                                                    </div>
                                                </article>
                                            </div>

                                            <!-- ===== Wall Treatment (More cards) ===== -->
                                            <div class="slider-item">
                                                <article class="cr-circle-card js-subcategory-card"
                                                    data-parent-category="Wall Treatment"
                                                    data-subcategory-id="SC-WAL-PAINT"
                                                    data-subcategory="Interior Paint">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1582582621959-48d27397dc31?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Interior paint" aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1582582621959-48d27397dc31?auto=format&fit=crop&w=900&q=80"
                                                            alt="Interior paint">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Interior Paint</div>
                                                    <div class="cr-circle-meta">Primer + top coat, low VOC options.</div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-circle-card js-subcategory-card"
                                                    data-parent-category="Wall Treatment"
                                                    data-subcategory-id="SC-WAL-WALLP"
                                                    data-subcategory="Wallpaper">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1519710164239-da123dc03ef4?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Wallpaper" aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1519710164239-da123dc03ef4?auto=format&fit=crop&w=900&q=80"
                                                            alt="Wallpaper">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Wallpaper</div>
                                                    <div class="cr-circle-meta">Feature walls: texture, pattern &amp;
                                                        color.
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-circle-card js-subcategory-card"
                                                    data-parent-category="Wall Treatment"
                                                    data-subcategory-id="SC-WAL-WOOD"
                                                    data-subcategory="Wood Wall Panel">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Wood wall panel"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=900&q=80"
                                                            alt="Wood wall panel">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Wood Wall Panel</div>
                                                    <div class="cr-circle-meta">Fluted/slatted panels for premium look.
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-circle-card js-subcategory-card"
                                                    data-parent-category="Wall Treatment"
                                                    data-subcategory-id="SC-WAL-3D"
                                                    data-subcategory="3D Wall Panel">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1582582621959-48d27397dc31?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="3D wall panel"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1582582621959-48d27397dc31?auto=format&fit=crop&w=900&q=80"
                                                            alt="3D wall panel">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">3D Wall Panel</div>
                                                    <div class="cr-circle-meta">Textured feature panels with shadows.
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item">
                                                <article class="cr-circle-card js-subcategory-card"
                                                    data-parent-category="Wall Treatment"
                                                    data-subcategory-id="SC-WAL-MIRR"
                                                    data-subcategory="Mirror Panel">
                                                    <div class="cr-circle-avatar">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1493666438817-866a91353ca9?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Mirror panel" aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1493666438817-866a91353ca9?auto=format&fit=crop&w=900&q=80"
                                                            alt="Mirror panel">
                                                        <div class="cr-card-tick">✓</div>
                                                    </div>
                                                    <div class="cr-circle-label">Mirror Panel</div>
                                                    <div class="cr-circle-meta">Make room look larger; decorative panels.
                                                    </div>
                                                </article>
                                            </div>

                                        </div>
                                    </div>
                                    <button class="slider-arrow slider-arrow-right" type="button"
                                        aria-label="Scroll right">&gt;</button>
                                </div>
                                <div class="cr-slider-meta">Total Images: 21</div>
                            </div>

                            <!-- Products -->
                            <div class="cr-card cr-panel-card cr-layer-card" id="productsCard">
                                <div class="cr-panel-header">
                                    <div>
                                        <div class="cr-panel-title cr-section-title">Products</div>
                                        <div class="cr-panel-subtitle cr-section-subtitle">
                                            Products are shown for the “Active Sub-Category”. You can select products in
                                            multiple sub-categories; selections are retained while switching.
                                        </div>

                                        <div class="cr-switch-row" id="subSwitchRow">
                                            <!-- Filled by JS -->
                                        </div>
                                    </div>
                                    <div class="cr-panel-subtitle cr-section-subtitle">
                                        Sub-Category: <strong id="productSubcategoryLabel">Double Person Bed</strong>
                                    </div>
                                </div>

                                <div class="layer-slider" data-layer="products" data-page="1" data-has-more="false">
                                    <button class="slider-arrow slider-arrow-left" type="button"
                                        aria-label="Scroll left">&lt;</button>
                                    <div class="slider-viewport">
                                        <div class="slider-track" id="productTrack">

                                            <!-- ===== Furniture → Double Person Bed (SC-FUR-BED) ===== -->
                                            <div class="slider-item slider-item-product">
                                                <article class="cr-product-card js-product-card"
                                                    data-parent-subcategory-id="SC-FUR-BED"
                                                    data-product-id="P-FUR-BED-01"
                                                    data-product-title="Avenue Solid Wood Double Bed"
                                                    data-product-sku="DB-AVN-180x200"
                                                    data-product-origin="Malaysia"
                                                    data-product-spec="Solid oak + veneer, upholstered headboard, 180x200 cm."
                                                    data-thumb="https://images.unsplash.com/photo-1505691723518-36a5ac3be353?auto=format&fit=crop&w=400&q=80"
                                                    data-image="https://images.unsplash.com/photo-1505691723518-36a5ac3be353?auto=format&fit=crop&w=1600&q=80">
                                                    <div class="cr-product-media">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1505691723518-36a5ac3be353?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Avenue Solid Wood Double Bed"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1505691723518-36a5ac3be353?auto=format&fit=crop&w=900&q=80"
                                                            alt="Solid wood double bed with headboard">
                                                        <div class="cr-product-media-overlay"></div>
                                                        <div class="cr-product-check">✓</div>
                                                    </div>
                                                    <div class="cr-product-body">
                                                        <div class="cr-product-title">Avenue Solid Wood Double Bed</div>
                                                        <div class="cr-product-meta">
                                                            <strong>SKU:</strong> DB-AVN-180x200<br>
                                                            <strong>Specification:</strong> Solid oak + veneer,
                                                            upholstered headboard, 180x200 cm.<br>
                                                            <strong>Origin:</strong> Malaysia
                                                        </div>
                                                        <div class="cr-product-footer">
                                                            <div class="cr-qty-control">
                                                                <button type="button" class="cr-qty-btn js-qty-minus">−</button>
                                                                <input type="text" class="cr-qty-input js-qty-input"
                                                                    value="1" inputmode="numeric">
                                                                <button type="button" class="cr-qty-btn js-qty-plus">+</button>
                                                            </div>
                                                            <span class="cr-product-tag">Preferred</span>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item slider-item-product">
                                                <article class="cr-product-card js-product-card"
                                                    data-parent-subcategory-id="SC-FUR-BED"
                                                    data-product-id="P-FUR-BED-02"
                                                    data-product-title="Nova Storage Bed with Drawers"
                                                    data-product-sku="DB-NOV-160x200"
                                                    data-product-origin="Bangladesh"
                                                    data-product-spec="Engineered wood, 4 under-bed drawers, 160x200 cm."
                                                    data-thumb="https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=400&q=80"
                                                    data-image="https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=1600&q=80">
                                                    <div class="cr-product-media">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Nova Storage Bed with Drawers"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1545239351-1141bd82e8a6?auto=format&fit=crop&w=900&q=80"
                                                            alt="Storage bed with drawers">
                                                        <div class="cr-product-media-overlay"></div>
                                                        <div class="cr-product-check">✓</div>
                                                    </div>
                                                    <div class="cr-product-body">
                                                        <div class="cr-product-title">Nova Storage Bed with Drawers</div>
                                                        <div class="cr-product-meta">
                                                            <strong>SKU:</strong> DB-NOV-160x200<br>
                                                            <strong>Specification:</strong> Engineered wood, 4 under-bed
                                                            drawers, 160x200 cm.<br>
                                                            <strong>Origin:</strong> Bangladesh
                                                        </div>
                                                        <div class="cr-product-footer">
                                                            <div class="cr-qty-control">
                                                                <button type="button" class="cr-qty-btn js-qty-minus">−</button>
                                                                <input type="text" class="cr-qty-input js-qty-input"
                                                                    value="1" inputmode="numeric">
                                                                <button type="button" class="cr-qty-btn js-qty-plus">+</button>
                                                            </div>
                                                            <span class="cr-product-tag">Value</span>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>

                                            <!-- ===== Furniture → Wardrobe (SC-FUR-WARD) ===== -->
                                            <div class="slider-item slider-item-product">
                                                <article class="cr-product-card js-product-card"
                                                    data-parent-subcategory-id="SC-FUR-WARD"
                                                    data-product-id="P-FUR-WARD-01"
                                                    data-product-title="Linea Sliding Wardrobe – 6ft"
                                                    data-product-sku="WR-LIN-6FT"
                                                    data-product-origin="Turkey"
                                                    data-product-spec="Sliding doors, soft-close rails, matte laminate."
                                                    data-thumb="https://images.unsplash.com/photo-1523217582562-09d0def993a6?auto=format&fit=crop&w=400&q=80"
                                                    data-image="https://images.unsplash.com/photo-1523217582562-09d0def993a6?auto=format&fit=crop&w=1600&q=80">
                                                    <div class="cr-product-media">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1523217582562-09d0def993a6?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Linea Sliding Wardrobe – 6ft"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1523217582562-09d0def993a6?auto=format&fit=crop&w=900&q=80"
                                                            alt="Sliding wardrobe">
                                                        <div class="cr-product-media-overlay"></div>
                                                        <div class="cr-product-check">✓</div>
                                                    </div>
                                                    <div class="cr-product-body">
                                                        <div class="cr-product-title">Linea Sliding Wardrobe – 6ft</div>
                                                        <div class="cr-product-meta">
                                                            <strong>SKU:</strong> WR-LIN-6FT<br>
                                                            <strong>Specification:</strong> Sliding doors, soft-close
                                                            rails, matte laminate.<br>
                                                            <strong>Origin:</strong> Turkey
                                                        </div>
                                                        <div class="cr-product-footer">
                                                            <div class="cr-qty-control">
                                                                <button type="button" class="cr-qty-btn js-qty-minus">−</button>
                                                                <input type="text" class="cr-qty-input js-qty-input"
                                                                    value="1" inputmode="numeric">
                                                                <button type="button" class="cr-qty-btn js-qty-plus">+</button>
                                                            </div>
                                                            <span class="cr-product-tag">Premium</span>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item slider-item-product">
                                                <article class="cr-product-card js-product-card"
                                                    data-parent-subcategory-id="SC-FUR-WARD"
                                                    data-product-id="P-FUR-WARD-02"
                                                    data-product-title="Oak Hinged Wardrobe – 4 Door"
                                                    data-product-sku="WR-OAK-4D"
                                                    data-product-origin="Malaysia"
                                                    data-product-spec="Hinged doors, internal drawers + hanging rod."
                                                    data-thumb="https://images.unsplash.com/photo-1523217582562-09d0def993a6?auto=format&fit=crop&w=400&q=80"
                                                    data-image="https://images.unsplash.com/photo-1523217582562-09d0def993a6?auto=format&fit=crop&w=1600&q=80">
                                                    <div class="cr-product-media">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1523217582562-09d0def993a6?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Oak Hinged Wardrobe – 4 Door"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1523217582562-09d0def993a6?auto=format&fit=crop&w=900&q=80"
                                                            alt="Hinged wardrobe">
                                                        <div class="cr-product-media-overlay"></div>
                                                        <div class="cr-product-check">✓</div>
                                                    </div>
                                                    <div class="cr-product-body">
                                                        <div class="cr-product-title">Oak Hinged Wardrobe – 4 Door</div>
                                                        <div class="cr-product-meta">
                                                            <strong>SKU:</strong> WR-OAK-4D<br>
                                                            <strong>Specification:</strong> Hinged doors, internal
                                                            drawers + hanging rod.<br>
                                                            <strong>Origin:</strong> Malaysia
                                                        </div>
                                                        <div class="cr-product-footer">
                                                            <div class="cr-qty-control">
                                                                <button type="button" class="cr-qty-btn js-qty-minus">−</button>
                                                                <input type="text" class="cr-qty-input js-qty-input"
                                                                    value="1" inputmode="numeric">
                                                                <button type="button" class="cr-qty-btn js-qty-plus">+</button>
                                                            </div>
                                                            <span class="cr-product-tag">Standard</span>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>

                                            <!-- ===== Furniture → Bedside Table (SC-FUR-BSIDE) ===== -->
                                            <div class="slider-item slider-item-product">
                                                <article class="cr-product-card js-product-card"
                                                    data-parent-subcategory-id="SC-FUR-BSIDE"
                                                    data-product-id="P-FUR-BSIDE-01"
                                                    data-product-title="Nordic Bedside Table – Drawer"
                                                    data-product-sku="BT-NOR-01"
                                                    data-product-origin="Bangladesh"
                                                    data-product-spec="Compact 1-drawer bedside table, oak finish."
                                                    data-thumb="https://images.unsplash.com/photo-1486946255434-2466348c2166?auto=format&fit=crop&w=400&q=80"
                                                    data-image="https://images.unsplash.com/photo-1486946255434-2466348c2166?auto=format&fit=crop&w=1600&q=80">
                                                    <div class="cr-product-media">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1486946255434-2466348c2166?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Nordic Bedside Table – Drawer"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1486946255434-2466348c2166?auto=format&fit=crop&w=900&q=80"
                                                            alt="Bedside table">
                                                        <div class="cr-product-media-overlay"></div>
                                                        <div class="cr-product-check">✓</div>
                                                    </div>
                                                    <div class="cr-product-body">
                                                        <div class="cr-product-title">Nordic Bedside Table – Drawer</div>
                                                        <div class="cr-product-meta">
                                                            <strong>SKU:</strong> BT-NOR-01<br>
                                                            <strong>Specification:</strong> Compact 1-drawer bedside
                                                            table, oak finish.<br>
                                                            <strong>Origin:</strong> Bangladesh
                                                        </div>
                                                        <div class="cr-product-footer">
                                                            <div class="cr-qty-control">
                                                                <button type="button" class="cr-qty-btn js-qty-minus">−</button>
                                                                <input type="text" class="cr-qty-input js-qty-input"
                                                                    value="1" inputmode="numeric">
                                                                <button type="button" class="cr-qty-btn js-qty-plus">+</button>
                                                            </div>
                                                            <span class="cr-product-tag">Value</span>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item slider-item-product">
                                                <article class="cr-product-card js-product-card"
                                                    data-parent-subcategory-id="SC-FUR-BSIDE"
                                                    data-product-id="P-FUR-BSIDE-02"
                                                    data-product-title="Marble-Top Bedside Table – 2 Tier"
                                                    data-product-sku="BT-MRB-02"
                                                    data-product-origin="Turkey"
                                                    data-product-spec="Marble top, metal frame, 2-tier shelf."
                                                    data-thumb="https://images.unsplash.com/photo-1486946255434-2466348c2166?auto=format&fit=crop&w=400&q=80"
                                                    data-image="https://images.unsplash.com/photo-1486946255434-2466348c2166?auto=format&fit=crop&w=1600&q=80">
                                                    <div class="cr-product-media">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1486946255434-2466348c2166?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Marble-Top Bedside Table – 2 Tier"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1486946255434-2466348c2166?auto=format&fit=crop&w=900&q=80"
                                                            alt="Marble-top bedside table">
                                                        <div class="cr-product-media-overlay"></div>
                                                        <div class="cr-product-check">✓</div>
                                                    </div>
                                                    <div class="cr-product-body">
                                                        <div class="cr-product-title">Marble-Top Bedside Table – 2 Tier
                                                        </div>
                                                        <div class="cr-product-meta">
                                                            <strong>SKU:</strong> BT-MRB-02<br>
                                                            <strong>Specification:</strong> Marble top, metal frame, 2-tier
                                                            shelf.<br>
                                                            <strong>Origin:</strong> Turkey
                                                        </div>
                                                        <div class="cr-product-footer">
                                                            <div class="cr-qty-control">
                                                                <button type="button" class="cr-qty-btn js-qty-minus">−</button>
                                                                <input type="text" class="cr-qty-input js-qty-input"
                                                                    value="1" inputmode="numeric">
                                                                <button type="button" class="cr-qty-btn js-qty-plus">+</button>
                                                            </div>
                                                            <span class="cr-product-tag">Standard</span>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>

                                            <!-- ===== Lighting → Ceiling Light (SC-LGT-CEIL) ===== -->
                                            <div class="slider-item slider-item-product">
                                                <article class="cr-product-card js-product-card"
                                                    data-parent-subcategory-id="SC-LGT-CEIL"
                                                    data-product-id="P-LGT-CEIL-01"
                                                    data-product-title="Slim LED Ceiling Panel – 24W"
                                                    data-product-sku="CL-LED-24W"
                                                    data-product-origin="China"
                                                    data-product-spec="Cool/Neutral/Warm switch, anti-glare diffuser."
                                                    data-thumb="https://images.unsplash.com/photo-1524484485831-a92ffc0de03f?auto=format&fit=crop&w=400&q=80"
                                                    data-image="https://images.unsplash.com/photo-1524484485831-a92ffc0de03f?auto=format&fit=crop&w=1600&q=80">
                                                    <div class="cr-product-media">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1524484485831-a92ffc0de03f?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Slim LED Ceiling Panel – 24W"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1524484485831-a92ffc0de03f?auto=format&fit=crop&w=900&q=80"
                                                            alt="LED ceiling panel">
                                                        <div class="cr-product-media-overlay"></div>
                                                        <div class="cr-product-check">✓</div>
                                                    </div>
                                                    <div class="cr-product-body">
                                                        <div class="cr-product-title">Slim LED Ceiling Panel – 24W</div>
                                                        <div class="cr-product-meta">
                                                            <strong>SKU:</strong> CL-LED-24W<br>
                                                            <strong>Specification:</strong> Cool/Neutral/Warm switch,
                                                            anti-glare diffuser.<br>
                                                            <strong>Origin:</strong> China
                                                        </div>
                                                        <div class="cr-product-footer">
                                                            <div class="cr-qty-control">
                                                                <button type="button" class="cr-qty-btn js-qty-minus">−</button>
                                                                <input type="text" class="cr-qty-input js-qty-input"
                                                                    value="1" inputmode="numeric">
                                                                <button type="button" class="cr-qty-btn js-qty-plus">+</button>
                                                            </div>
                                                            <span class="cr-product-tag">Standard</span>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item slider-item-product">
                                                <article class="cr-product-card js-product-card"
                                                    data-parent-subcategory-id="SC-LGT-CEIL"
                                                    data-product-id="P-LGT-CEIL-02"
                                                    data-product-title="Designer Flush Mount – Brass"
                                                    data-product-sku="CL-BRS-01"
                                                    data-product-origin="Turkey"
                                                    data-product-spec="Brass finish, frosted glass, E27 bulbs."
                                                    data-thumb="https://images.unsplash.com/photo-1524484485831-a92ffc0de03f?auto=format&fit=crop&w=400&q=80"
                                                    data-image="https://images.unsplash.com/photo-1524484485831-a92ffc0de03f?auto=format&fit=crop&w=1600&q=80">
                                                    <div class="cr-product-media">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1524484485831-a92ffc0de03f?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Designer Flush Mount – Brass"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1524484485831-a92ffc0de03f?auto=format&fit=crop&w=900&q=80"
                                                            alt="Flush mount ceiling light">
                                                        <div class="cr-product-media-overlay"></div>
                                                        <div class="cr-product-check">✓</div>
                                                    </div>
                                                    <div class="cr-product-body">
                                                        <div class="cr-product-title">Designer Flush Mount – Brass</div>
                                                        <div class="cr-product-meta">
                                                            <strong>SKU:</strong> CL-BRS-01<br>
                                                            <strong>Specification:</strong> Brass finish, frosted glass,
                                                            E27 bulbs.<br>
                                                            <strong>Origin:</strong> Turkey
                                                        </div>
                                                        <div class="cr-product-footer">
                                                            <div class="cr-qty-control">
                                                                <button type="button" class="cr-qty-btn js-qty-minus">−</button>
                                                                <input type="text" class="cr-qty-input js-qty-input"
                                                                    value="1" inputmode="numeric">
                                                                <button type="button" class="cr-qty-btn js-qty-plus">+</button>
                                                            </div>
                                                            <span class="cr-product-tag">Premium</span>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>

                                            <!-- ===== Lighting → Wall Sconce (SC-LGT-WALL) ===== -->
                                            <div class="slider-item slider-item-product">
                                                <article class="cr-product-card js-product-card"
                                                    data-parent-subcategory-id="SC-LGT-WALL"
                                                    data-product-id="P-LGT-WALL-01"
                                                    data-product-title="Minimal Wall Sconce – Matte Black"
                                                    data-product-sku="WS-MBK-01"
                                                    data-product-origin="China"
                                                    data-product-spec="Directional sconce, warm LED, metal body."
                                                    data-thumb="https://images.unsplash.com/photo-1493666438817-866a91353ca9?auto=format&fit=crop&w=400&q=80"
                                                    data-image="https://images.unsplash.com/photo-1493666438817-866a91353ca9?auto=format&fit=crop&w=1600&q=80">
                                                    <div class="cr-product-media">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1493666438817-866a91353ca9?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Minimal Wall Sconce – Matte Black"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1493666438817-866a91353ca9?auto=format&fit=crop&w=900&q=80"
                                                            alt="Wall sconce">
                                                        <div class="cr-product-media-overlay"></div>
                                                        <div class="cr-product-check">✓</div>
                                                    </div>
                                                    <div class="cr-product-body">
                                                        <div class="cr-product-title">Minimal Wall Sconce – Matte Black
                                                        </div>
                                                        <div class="cr-product-meta">
                                                            <strong>SKU:</strong> WS-MBK-01<br>
                                                            <strong>Specification:</strong> Directional sconce, warm LED,
                                                            metal body.<br>
                                                            <strong>Origin:</strong> China
                                                        </div>
                                                        <div class="cr-product-footer">
                                                            <div class="cr-qty-control">
                                                                <button type="button" class="cr-qty-btn js-qty-minus">−</button>
                                                                <input type="text" class="cr-qty-input js-qty-input"
                                                                    value="1" inputmode="numeric">
                                                                <button type="button" class="cr-qty-btn js-qty-plus">+</button>
                                                            </div>
                                                            <span class="cr-product-tag">Value</span>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item slider-item-product">
                                                <article class="cr-product-card js-product-card"
                                                    data-parent-subcategory-id="SC-LGT-WALL"
                                                    data-product-id="P-LGT-WALL-02"
                                                    data-product-title="Brass Wall Sconce – Frosted Globe"
                                                    data-product-sku="WS-BRS-02"
                                                    data-product-origin="Turkey"
                                                    data-product-spec="Brass finish, frosted globe, E14 bulb."
                                                    data-thumb="https://images.unsplash.com/photo-1493666438817-866a91353ca9?auto=format&fit=crop&w=400&q=80"
                                                    data-image="https://images.unsplash.com/photo-1493666438817-866a91353ca9?auto=format&fit=crop&w=1600&q=80">
                                                    <div class="cr-product-media">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1493666438817-866a91353ca9?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Brass Wall Sconce – Frosted Globe"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1493666438817-866a91353ca9?auto=format&fit=crop&w=900&q=80"
                                                            alt="Brass wall sconce">
                                                        <div class="cr-product-media-overlay"></div>
                                                        <div class="cr-product-check">✓</div>
                                                    </div>
                                                    <div class="cr-product-body">
                                                        <div class="cr-product-title">Brass Wall Sconce – Frosted Globe
                                                        </div>
                                                        <div class="cr-product-meta">
                                                            <strong>SKU:</strong> WS-BRS-02<br>
                                                            <strong>Specification:</strong> Brass finish, frosted globe,
                                                            E14 bulb.<br>
                                                            <strong>Origin:</strong> Turkey
                                                        </div>
                                                        <div class="cr-product-footer">
                                                            <div class="cr-qty-control">
                                                                <button type="button" class="cr-qty-btn js-qty-minus">−</button>
                                                                <input type="text" class="cr-qty-input js-qty-input"
                                                                    value="1" inputmode="numeric">
                                                                <button type="button" class="cr-qty-btn js-qty-plus">+</button>
                                                            </div>
                                                            <span class="cr-product-tag">Premium</span>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>

                                            <!-- ===== Curtains → Window Curtains (SC-CUR-WIN) ===== -->
                                            <div class="slider-item slider-item-product">
                                                <article class="cr-product-card js-product-card"
                                                    data-parent-subcategory-id="SC-CUR-WIN"
                                                    data-product-id="P-CUR-WIN-01"
                                                    data-product-title="Linen Blend Curtains – Off White"
                                                    data-product-sku="CU-LIN-OW"
                                                    data-product-origin="Bangladesh"
                                                    data-product-spec="Linen blend, pinch-pleat, custom height."
                                                    data-thumb="https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=400&q=80"
                                                    data-image="https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=1600&q=80">
                                                    <div class="cr-product-media">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Linen Blend Curtains – Off White"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=900&q=80"
                                                            alt="Linen curtains">
                                                        <div class="cr-product-media-overlay"></div>
                                                        <div class="cr-product-check">✓</div>
                                                    </div>
                                                    <div class="cr-product-body">
                                                        <div class="cr-product-title">Linen Blend Curtains – Off White
                                                        </div>
                                                        <div class="cr-product-meta">
                                                            <strong>SKU:</strong> CU-LIN-OW<br>
                                                            <strong>Specification:</strong> Linen blend, pinch-pleat,
                                                            custom height.<br>
                                                            <strong>Origin:</strong> Bangladesh
                                                        </div>
                                                        <div class="cr-product-footer">
                                                            <div class="cr-qty-control">
                                                                <button type="button" class="cr-qty-btn js-qty-minus">−</button>
                                                                <input type="text" class="cr-qty-input js-qty-input"
                                                                    value="1" inputmode="numeric">
                                                                <button type="button" class="cr-qty-btn js-qty-plus">+</button>
                                                            </div>
                                                            <span class="cr-product-tag">Standard</span>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item slider-item-product">
                                                <article class="cr-product-card js-product-card"
                                                    data-parent-subcategory-id="SC-CUR-WIN"
                                                    data-product-id="P-CUR-WIN-02"
                                                    data-product-title="Cotton Curtains – Warm Grey"
                                                    data-product-sku="CU-CTN-WG"
                                                    data-product-origin="Turkey"
                                                    data-product-spec="Cotton, blackout lining optional, custom width."
                                                    data-thumb="https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=400&q=80"
                                                    data-image="https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=1600&q=80">
                                                    <div class="cr-product-media">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Cotton Curtains – Warm Grey"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=900&q=80"
                                                            alt="Cotton curtains">
                                                        <div class="cr-product-media-overlay"></div>
                                                        <div class="cr-product-check">✓</div>
                                                    </div>
                                                    <div class="cr-product-body">
                                                        <div class="cr-product-title">Cotton Curtains – Warm Grey</div>
                                                        <div class="cr-product-meta">
                                                            <strong>SKU:</strong> CU-CTN-WG<br>
                                                            <strong>Specification:</strong> Cotton, blackout lining
                                                            optional, custom width.<br>
                                                            <strong>Origin:</strong> Turkey
                                                        </div>
                                                        <div class="cr-product-footer">
                                                            <div class="cr-qty-control">
                                                                <button type="button" class="cr-qty-btn js-qty-minus">−</button>
                                                                <input type="text" class="cr-qty-input js-qty-input"
                                                                    value="1" inputmode="numeric">
                                                                <button type="button" class="cr-qty-btn js-qty-plus">+</button>
                                                            </div>
                                                            <span class="cr-product-tag">Preferred</span>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>

                                            <!-- ===== Wall Treatment → Interior Paint (SC-WAL-PAINT) ===== -->
                                            <div class="slider-item slider-item-product">
                                                <article class="cr-product-card js-product-card"
                                                    data-parent-subcategory-id="SC-WAL-PAINT"
                                                    data-product-id="P-WAL-PAINT-01"
                                                    data-product-title="Premium Interior Paint – Low VOC"
                                                    data-product-sku="PT-LVOC-01"
                                                    data-product-origin="Bangladesh"
                                                    data-product-spec="Washable matte, low odor, 1 gallon coverage ~350 sqft."
                                                    data-thumb="https://images.unsplash.com/photo-1582582621959-48d27397dc31?auto=format&fit=crop&w=400&q=80"
                                                    data-image="https://images.unsplash.com/photo-1582582621959-48d27397dc31?auto=format&fit=crop&w=1600&q=80">
                                                    <div class="cr-product-media">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1582582621959-48d27397dc31?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Premium Interior Paint – Low VOC"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1582582621959-48d27397dc31?auto=format&fit=crop&w=900&q=80"
                                                            alt="Interior paint">
                                                        <div class="cr-product-media-overlay"></div>
                                                        <div class="cr-product-check">✓</div>
                                                    </div>
                                                    <div class="cr-product-body">
                                                        <div class="cr-product-title">Premium Interior Paint – Low VOC
                                                        </div>
                                                        <div class="cr-product-meta">
                                                            <strong>SKU:</strong> PT-LVOC-01<br>
                                                            <strong>Specification:</strong> Washable matte, low odor, 1
                                                            gallon coverage ~350 sqft.<br>
                                                            <strong>Origin:</strong> Bangladesh
                                                        </div>
                                                        <div class="cr-product-footer">
                                                            <div class="cr-qty-control">
                                                                <button type="button" class="cr-qty-btn js-qty-minus">−</button>
                                                                <input type="text" class="cr-qty-input js-qty-input"
                                                                    value="1" inputmode="numeric">
                                                                <button type="button" class="cr-qty-btn js-qty-plus">+</button>
                                                            </div>
                                                            <span class="cr-product-tag">Preferred</span>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>

                                            <div class="slider-item slider-item-product">
                                                <article class="cr-product-card js-product-card"
                                                    data-parent-subcategory-id="SC-WAL-PAINT"
                                                    data-product-id="P-WAL-PAINT-02"
                                                    data-product-title="Primer + Sealer Combo"
                                                    data-product-sku="PT-PRM-02"
                                                    data-product-origin="India"
                                                    data-product-spec="Adhesion primer, damp protection sealer, 1 gallon."
                                                    data-thumb="https://images.unsplash.com/photo-1582582621959-48d27397dc31?auto=format&fit=crop&w=400&q=80"
                                                    data-image="https://images.unsplash.com/photo-1582582621959-48d27397dc31?auto=format&fit=crop&w=1600&q=80">
                                                    <div class="cr-product-media">
                                                        <button type="button" class="cr-zoom-btn js-zoom-btn"
                                                            data-zoom-src="https://images.unsplash.com/photo-1582582621959-48d27397dc31?auto=format&fit=crop&w=1600&q=80"
                                                            data-zoom-alt="Primer + Sealer Combo"
                                                            aria-label="Zoom image">🔎</button>
                                                        <img class="cr-zoomable"
                                                            src="https://images.unsplash.com/photo-1582582621959-48d27397dc31?auto=format&fit=crop&w=900&q=80"
                                                            alt="Primer paint">
                                                        <div class="cr-product-media-overlay"></div>
                                                        <div class="cr-product-check">✓</div>
                                                    </div>
                                                    <div class="cr-product-body">
                                                        <div class="cr-product-title">Primer + Sealer Combo</div>
                                                        <div class="cr-product-meta">
                                                            <strong>SKU:</strong> PT-PRM-02<br>
                                                            <strong>Specification:</strong> Adhesion primer, damp
                                                            protection sealer, 1 gallon.<br>
                                                            <strong>Origin:</strong> India
                                                        </div>
                                                        <div class="cr-product-footer">
                                                            <div class="cr-qty-control">
                                                                <button type="button" class="cr-qty-btn js-qty-minus">−</button>
                                                                <input type="text" class="cr-qty-input js-qty-input"
                                                                    value="1" inputmode="numeric">
                                                                <button type="button" class="cr-qty-btn js-qty-plus">+</button>
                                                            </div>
                                                            <span class="cr-product-tag">Standard</span>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>

                                        </div>
                                    </div>
                                    <button class="slider-arrow slider-arrow-right" type="button"
                                        aria-label="Scroll right">&gt;</button>
                                </div>

                                <div class="cr-products-meta">
                                    Total Selected Products (All Spaces): <span id="totalProductCount">0</span>
                                </div>

                                <div class="cr-actions">
                                    <button type="button" class="cr-btn cr-btn-primary"
                                        onclick="window.location.href='client-requisition-step1.html'">
                                        Back to Project Overview
                                    </button>
                                    <button type="button" class="cr-btn cr-btn-primary" id="submitRequisitionBtn">
                                        <span>Submit Requisition</span>
                                        <span class="cr-btn-icon-circle">✓</span>
                                    </button>
                                    <button type="button" class="cr-btn cr-btn-primary" id="closePageBtn">
                                        Close
                                    </button>

                                </div>
                            </div>
                        </section>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- Summary Drawer -->
    <div class="cr-summary-drawer" id="summaryDrawer" aria-hidden="true">
        <button type="button" class="cr-summary-close" id="summaryCloseBtn">×</button>
        <div class="cr-summary-header">
            <div class="cr-summary-title">Requisition Summary</div>
            <div class="cr-summary-subtitle">A4-ready layout (Print → Save as PDF). Includes Step-1 + Step-2 + images.
            </div>

            <div class="cr-summary-actions">
                <button type="button" class="cr-btn cr-btn-primary" id="downloadPdfBtn">
                    <span>Download PDF (A4)</span>
                    <span class="cr-btn-icon-circle">⤓</span>
                </button>
                <button type="button" class="cr-btn cr-btn-secondary" id="printPdfBtn">
                    <span>Print</span>
                    <span class="cr-btn-icon-circle">🖨</span>
                </button>
            </div>
        </div>
        <div class="cr-summary-body" id="summaryBody">
            <!-- Filled by JS -->
        </div>
    </div>

    <div class="cr-overlay" id="summaryOverlay" aria-hidden="true"></div>
    <div class="cr-overlay" id="confirmOverlay" aria-hidden="true"></div>

    <div class="cr-modal-panel" id="confirmModal" style="display:none;">
        <button type="button" class="cr-modal-close-btn" id="confirmCloseBtn">×</button>
        <div class="cr-modal-title">Have you completed all your requisitions?</div>
        <div class="cr-modal-text">
            Please ensure you have selected products for every space you want us to work on.
        </div>
        <div class="cr-modal-actions">
            <button type="button" class="cr-btn cr-btn-secondary" id="confirmNoBtn">No, Review Again</button>
            <button type="button" class="cr-btn cr-btn-primary" id="confirmYesBtn">Yes, Submit</button>
        </div>
    </div>

    <div class="cr-overlay" id="successOverlay" aria-hidden="true"></div>
    <div class="cr-modal-panel" id="successModal" style="display:none;">
        <button type="button" class="cr-modal-close-btn" id="successCloseBtn">×</button>
        <div class="cr-modal-title" style="color:var(--success);">Requisition Submitted</div>
        <div class="cr-modal-text">
            You have successfully completed your Requisition.<br><br>
            <strong>Requisition ID:</strong> REQ-XXXXXX<br>
            <strong>Date:</strong> DD-MM-YYYY<br><br>
            Our representative will contact you soon. Please check your portal Dashboard regularly for updates.
        </div>
        <div class="cr-modal-actions">
            <button type="button" class="cr-btn cr-btn-primary" id="successOkBtn">OK, Go to Dashboard</button>
        </div>
    </div>

    <!-- Zoom Modal -->
    <div class="cr-zoom-backdrop cr-modal-backdrop" id="zoomBackdrop" aria-hidden="true">
        <div class="cr-zoom-modal cr-modal">
            <button type="button" class="cr-zoom-close cr-modal-close" id="zoomCloseBtn">×</button>
            <img src="" alt="Zoomed interior image" id="zoomImg">
        </div>
    </div>

    <script>
        (function () {
            var STORAGE_STEP1 = 'cr_step1';
            var STORAGE_STEP2 = 'cr_step2';
            var STEP2_VERSION = 3;

            function safeParse(json, fallback) {
                try { return JSON.parse(json); } catch (e) { return fallback; }
            }

            function getDefaultStep1() {
                return {
                    regId: 'REG-INT-2025-00123',
                    name: 'Mr. Salahuddin Ahmed',
                    email: 'client@example.com',
                    phone: '+8801XXXXXXXXX',
                    address: 'Sample address from Step 1',
                    budget: '25,00,000 BDT (approx.)',
                    eta: '31-12-2025',
                    projectType: 'Residential',
                    projectSubType: 'Duplex',
                    spaces: [
                        { name: 'Master Bed Room', qty: 1, sqft: 220 },
                        { name: 'Child Bed Room', qty: 1, sqft: 180 },
                        { name: 'Living / Lounge', qty: 1, sqft: 260 },
                        { name: 'Dining', qty: 1, sqft: 160 }
                    ]
                };
            }

            function loadStep1() {
                var raw = localStorage.getItem(STORAGE_STEP1);
                var data = safeParse(raw, null);
                return data && typeof data === 'object' ? Object.assign(getDefaultStep1(), data) : getDefaultStep1();
            }

            function getEmptyStep2() {
                return {
                    version: STEP2_VERSION,
                    bySpace: {} // spaceName -> state
                };
            }

            function saveStep2(state) {
                localStorage.setItem(STORAGE_STEP2, JSON.stringify(state));
            }

            function migrateIfNeeded(rawObj) {
                if (!rawObj || typeof rawObj !== 'object') return getEmptyStep2();
                if (!rawObj.bySpace || typeof rawObj.bySpace !== 'object') rawObj.bySpace = {};

                if (rawObj.version === STEP2_VERSION) return rawObj;

                // v1/v2 -> v3: selectedSubsByCat array + activeSubByCat
                Object.keys(rawObj.bySpace).forEach(function (space) {
                    var s = rawObj.bySpace[space] || {};
                    if (!Array.isArray(s.categories)) s.categories = [];
                    if (!s.selectedSubsByCat || typeof s.selectedSubsByCat !== 'object') {
                        // older key names
                        if (s.selectedSubByCat && typeof s.selectedSubByCat === 'object') {
                            s.selectedSubsByCat = {};
                            Object.keys(s.selectedSubByCat).forEach(function (cat) {
                                var one = s.selectedSubByCat[cat];
                                s.selectedSubsByCat[cat] = one ? [one] : [];
                            });
                            delete s.selectedSubByCat;
                        } else {
                            s.selectedSubsByCat = {};
                        }
                    } else {
                        // ensure arrays
                        Object.keys(s.selectedSubsByCat).forEach(function (cat2) {
                            if (!Array.isArray(s.selectedSubsByCat[cat2])) {
                                var v = s.selectedSubsByCat[cat2];
                                s.selectedSubsByCat[cat2] = v ? [v] : [];
                            }
                        });
                    }

                    if (!s.activeSubByCat || typeof s.activeSubByCat !== 'object') {
                        s.activeSubByCat = {};
                        Object.keys(s.selectedSubsByCat).forEach(function (cat3) {
                            var arr = s.selectedSubsByCat[cat3] || [];
                            if (arr.length) s.activeSubByCat[cat3] = arr[0];
                        });
                    }

                    if (!s.productsBySub || typeof s.productsBySub !== 'object') s.productsBySub = {};
                    if (!s.activeCategory) s.activeCategory = s.categories[0] || null;

                    rawObj.bySpace[space] = s;
                });

                rawObj.version = STEP2_VERSION;
                return rawObj;
            }

            function loadStep2() {
                var raw = localStorage.getItem(STORAGE_STEP2);
                var data = safeParse(raw, null);
                return migrateIfNeeded(data);
            }

            var step1 = loadStep1();
            var step2 = loadStep2();

            // DOM refs
            var breadcrumbType = document.getElementById('breadcrumb-type');
            var breadcrumbSubType = document.getElementById('breadcrumb-subtype');
            var breadcrumbSpace = document.getElementById('breadcrumb-space');
            var breadcrumbCategory = document.getElementById('breadcrumb-category');
            var breadcrumbSubcategory = document.getElementById('breadcrumb-subcategory');

            var categorySpaceLabel = document.getElementById('categorySpaceLabel');
            var subcategoryCategoryLabel = document.getElementById('subcategoryCategoryLabel');
            var productSubcategoryLabel = document.getElementById('productSubcategoryLabel');

            var catSwitchRow = document.getElementById('catSwitchRow');
            var subSwitchRow = document.getElementById('subSwitchRow');

            var totalProductCountEl = document.getElementById('totalProductCount');

            var categoryCardWrap = document.getElementById('categoryCard');
            var subcategoryCardWrap = document.getElementById('subcategoryCard');
            var productsCardWrap = document.getElementById('productsCard');

            var categoryCards = Array.prototype.slice.call(document.querySelectorAll('.js-category-card'));
            var subcategoryCards = Array.prototype.slice.call(document.querySelectorAll('.js-subcategory-card'));
            var productCards = Array.prototype.slice.call(document.querySelectorAll('.js-product-card'));

            var allCategoryKeys = categoryCards.map(function (c) { return (c.getAttribute('data-category') || '').trim(); }).filter(Boolean);

            // Build sub meta: id -> {name, category}
            var subMetaById = {};
            subcategoryCards.forEach(function (card) {
                var cat = (card.getAttribute('data-parent-category') || '').trim();
                var sid = (card.getAttribute('data-subcategory-id') || '').trim();
                var name = (card.getAttribute('data-subcategory') || '').trim() ||
                    (card.querySelector('.cr-circle-label') ? card.querySelector('.cr-circle-label').textContent.trim() : sid);
                if (sid) subMetaById[sid] = { id: sid, name: name, category: cat };
            });

            // Group sub cards by category
            var subCardsByCategory = {};
            subcategoryCards.forEach(function (card) {
                var cat = (card.getAttribute('data-parent-category') || '').trim();
                if (!subCardsByCategory[cat]) subCardsByCategory[cat] = [];
                subCardsByCategory[cat].push(card);
            });

            function esc(s) {
                return String(s || '').replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }

            function setSelectedTickClass(cardEl, isSelected) {
                cardEl.classList.toggle('is-selected', !!isSelected);
            }

            function getSubId(card) {
                return (card.getAttribute('data-subcategory-id') || card.getAttribute('data-subcategory') || '').trim();
            }

            function getSubNameById(subId) {
                return (subMetaById[subId] && subMetaById[subId].name) ? subMetaById[subId].name : (subId || '—');
            }

            function firstAvailableCategory() {
                return allCategoryKeys[0] || '—';
            }

            function persist() {
                step2.version = STEP2_VERSION;
                saveStep2(step2);
            }

            function getDefaultSpaceState() {
                // Demo defaults only on first-time load (keeps UI not blank)
                return {
                    categories: ['Furniture'],
                    activeCategory: 'Furniture',
                    selectedSubsByCat: { 'Furniture': ['SC-FUR-BED'] },
                    activeSubByCat: { 'Furniture': 'SC-FUR-BED' },
                    productsBySub: {}
                };
            }

            function ensureSpaceState(spaceName) {
                if (!step2.bySpace[spaceName]) {
                    step2.bySpace[spaceName] = getDefaultSpaceState();
                }

                var s = step2.bySpace[spaceName];
                if (!Array.isArray(s.categories)) s.categories = [];
                if (!s.activeCategory) s.activeCategory = s.categories[0] || firstAvailableCategory();
                if (!s.selectedSubsByCat || typeof s.selectedSubsByCat !== 'object') s.selectedSubsByCat = {};
                if (!s.activeSubByCat || typeof s.activeSubByCat !== 'object') s.activeSubByCat = {};
                if (!s.productsBySub || typeof s.productsBySub !== 'object') s.productsBySub = {};

                // ensure arrays for selectedSubsByCat
                Object.keys(s.selectedSubsByCat).forEach(function (cat) {
                    if (!Array.isArray(s.selectedSubsByCat[cat])) {
                        var v = s.selectedSubsByCat[cat];
                        s.selectedSubsByCat[cat] = v ? [v] : [];
                    }
                });

                return s;
            }

            function normalizeActiveCategory(s) {
                // active category can be any, but if selected categories exist, prefer one of them
                if (s.activeCategory && allCategoryKeys.indexOf(s.activeCategory) !== -1) {
                    if (!s.categories.length) return s.activeCategory;
                    if (s.categories.indexOf(s.activeCategory) !== -1) return s.activeCategory;
                }
                if (s.categories.length) return s.categories[0];
                return firstAvailableCategory();
            }

            function setSliderItemVisible(el, visible) {
                var item = el && el.closest ? el.closest('.slider-item') : null;
                if (!item) item = el;
                if (!item) return;
                item.style.display = visible ? '' : 'none';
            }

            function setProductsSliderItemVisible(el, visible) {
                var item = el && el.closest ? el.closest('.slider-item-product') : null;
                if (!item) item = el && el.closest ? el.closest('.slider-item') : null;
                if (!item) item = el;
                if (!item) return;
                item.style.display = visible ? '' : 'none';
            }

            function getProductId(card) {
                return (card.getAttribute('data-product-id') || '').trim();
            }

            function getProductPayloadFromCard(card) {
                var pid = getProductId(card);
                var qtyInput = card.querySelector('.js-qty-input');
                var qty = parseInt((qtyInput && qtyInput.value) ? qtyInput.value : '1', 10);
                if (!qty || qty < 1) qty = 1;

                return {
                    id: pid,
                    title: card.getAttribute('data-product-title') || (card.querySelector('.cr-product-title') ? card.querySelector('.cr-product-title').textContent.trim() : pid),
                    sku: card.getAttribute('data-product-sku') || '',
                    origin: card.getAttribute('data-product-origin') || '',
                    spec: card.getAttribute('data-product-spec') || '',
                    thumb: card.getAttribute('data-thumb') || (card.querySelector('img') ? card.querySelector('img').src : ''),
                    image: card.getAttribute('data-image') || (card.querySelector('img') ? card.querySelector('img').src : ''),
                    qty: qty
                };
            }

            function updateTotalSelectedProducts() {
                var total = 0;
                Object.keys(step2.bySpace).forEach(function (space) {
                    var s = step2.bySpace[space];
                    if (!s || !s.productsBySub) return;
                    Object.keys(s.productsBySub).forEach(function (subId) {
                        var bucket = s.productsBySub[subId];
                        if (!bucket || typeof bucket !== 'object') return;
                        total += Object.keys(bucket).length;
                    });
                });
                if (totalProductCountEl) totalProductCountEl.textContent = String(total);
            }

            function updateLayerSelectionStatesForSpace(spaceName) {
                var s = ensureSpaceState(spaceName);

                var hasCat = s.categories && s.categories.length > 0;

                var hasSub = false;
                if (s.selectedSubsByCat && typeof s.selectedSubsByCat === 'object') {
                    hasSub = Object.keys(s.selectedSubsByCat).some(function (cat) {
                        var arr = s.selectedSubsByCat[cat];
                        return Array.isArray(arr) && arr.length > 0 && s.categories.indexOf(cat) !== -1;
                    });
                }

                var hasProd = false;
                if (s.productsBySub && typeof s.productsBySub === 'object') {
                    hasProd = Object.keys(s.productsBySub).some(function (subId) {
                        var b = s.productsBySub[subId];
                        return b && typeof b === 'object' && Object.keys(b).length > 0;
                    });
                }

                if (categoryCardWrap) categoryCardWrap.classList.toggle('has-selection', hasCat);
                if (subcategoryCardWrap) subcategoryCardWrap.classList.toggle('has-selection', hasSub);
                if (productsCardWrap) productsCardWrap.classList.toggle('has-selection', hasProd);
            }

            function renderCategories(spaceName) {
                var s = ensureSpaceState(spaceName);
                categoryCards.forEach(function (card) {
                    var cat = (card.getAttribute('data-category') || '').trim();
                    var on = s.categories.indexOf(cat) !== -1;
                    card.classList.toggle('selected', on);
                    setSelectedTickClass(card, on);
                });
            }

            function buildCategorySwitch(spaceName, activeCat) {
                if (!catSwitchRow) return;
                var s = ensureSpaceState(spaceName);

                var html = '';
                html += '<span class="cr-switch-hint">Active Category:</span>';
                allCategoryKeys.forEach(function (cat) {
                    var picked = s.categories.indexOf(cat) !== -1;
                    var isActive = (cat === activeCat);
                    html += '<button type="button" class="cr-switch-chip' +
                        (picked ? ' picked' : '') +
                        (isActive ? ' active' : '') +
                        '" data-cat="' + esc(cat) + '"><span class="dot"></span><span>' + esc(cat) + '</span></button>';
                });

                catSwitchRow.innerHTML = html;

                Array.prototype.slice.call(catSwitchRow.querySelectorAll('.cr-switch-chip[data-cat]')).forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var cat = btn.getAttribute('data-cat');
                        if (!cat) return;
                        setActiveCategory(activeSpace, cat);
                    });
                });
            }

            function getSelectedSubsForCat(s, cat) {
                var arr = s.selectedSubsByCat[cat];
                return Array.isArray(arr) ? arr : [];
            }

            function resolveActiveSubId(spaceName, activeCat) {
                var s = ensureSpaceState(spaceName);

                var active = s.activeSubByCat[activeCat];
                if (active) return active;

                var selected = getSelectedSubsForCat(s, activeCat);
                if (selected.length) return selected[0];

                // fallback: first visible sub in this category
                var list = subCardsByCategory[activeCat] || [];
                if (list.length) return getSubId(list[0]);

                return '—';
            }

            function buildSubSwitch(spaceName, activeCat, activeSubId) {
                if (!subSwitchRow) return;
                var s = ensureSpaceState(spaceName);

                var list = subCardsByCategory[activeCat] || [];
                if (!list.length) {
                    subSwitchRow.innerHTML = '<span class="cr-switch-hint">No sub-categories available for this category.</span>';
                    return;
                }

                var selectedArr = getSelectedSubsForCat(s, activeCat);
                var html = '';
                html += '<span class="cr-switch-hint">Active Sub-Category:</span>';

                list.forEach(function (card) {
                    var sid = getSubId(card);
                    var name = (card.getAttribute('data-subcategory') || '').trim() || getSubNameById(sid);
                    var picked = selectedArr.indexOf(sid) !== -1;
                    var isActive = (sid === activeSubId);
                    html += '<button type="button" class="cr-switch-chip' +
                        (picked ? ' picked' : '') +
                        (isActive ? ' active' : '') +
                        '" data-sub="' + esc(sid) + '"><span class="dot"></span><span>' + esc(name) + '</span></button>';
                });

                subSwitchRow.innerHTML = html;

                Array.prototype.slice.call(subSwitchRow.querySelectorAll('.cr-switch-chip[data-sub]')).forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var sid = btn.getAttribute('data-sub');
                        if (!sid) return;
                        var ss = ensureSpaceState(activeSpace);
                        var ac = normalizeActiveCategory(ss);
                        ss.activeCategory = ac;
                        ss.activeSubByCat[ac] = sid;
                        renderProducts(activeSpace, ac, sid);
                        renderSubcategories(activeSpace, ac);
                        updateLayerSelectionStatesForSpace(activeSpace);
                        updateTotalSelectedProducts();
                        persist();
                    });
                });
            }

            function renderSubcategories(spaceName, activeCat) {
                var s = ensureSpaceState(spaceName);

                // show only subcategories for active category
                subcategoryCards.forEach(function (card) {
                    var cat = (card.getAttribute('data-parent-category') || '').trim();
                    setSliderItemVisible(card, (cat === activeCat));
                });

                var selectedArr = getSelectedSubsForCat(s, activeCat);

                subcategoryCards.forEach(function (card) {
                    var cat = (card.getAttribute('data-parent-category') || '').trim();
                    var sid = getSubId(card);
                    var on = (cat === activeCat && selectedArr.indexOf(sid) !== -1);
                    card.classList.toggle('selected', on);
                    setSelectedTickClass(card, on);
                });

                if (subcategoryCategoryLabel) subcategoryCategoryLabel.textContent = activeCat || '—';
                if (breadcrumbCategory) breadcrumbCategory.textContent = activeCat || '—';

                buildCategorySwitch(spaceName, activeCat);

                var activeSubId = resolveActiveSubId(spaceName, activeCat);
                buildSubSwitch(spaceName, activeCat, activeSubId);
            }

            function renderProducts(spaceName, activeCat, activeSubId) {
                var s = ensureSpaceState(spaceName);
                if (!activeSubId || activeSubId === '—') activeSubId = resolveActiveSubId(spaceName, activeCat);

                // show only products for active subcategory
                productCards.forEach(function (card) {
                    var subId = (card.getAttribute('data-parent-subcategory-id') || '').trim();
                    setProductsSliderItemVisible(card, (subId === activeSubId));
                });

                // apply selection/qty from bucket
                var bucket = s.productsBySub[activeSubId];
                if (!bucket || typeof bucket !== 'object') bucket = {};

                productCards.forEach(function (card) {
                    var pid = getProductId(card);
                    var subId = (card.getAttribute('data-parent-subcategory-id') || '').trim();
                    if (subId !== activeSubId) {
                        card.classList.remove('selected');
                        return;
                    }
                    var on = !!bucket[pid];
                    card.classList.toggle('selected', on);
                    var qtyInput = card.querySelector('.js-qty-input');
                    if (qtyInput) qtyInput.value = on ? String(bucket[pid].qty || 1) : (qtyInput.value || '1');
                });

                var subName = getSubNameById(activeSubId);
                if (breadcrumbSubcategory) breadcrumbSubcategory.textContent = subName || '—';
                if (productSubcategoryLabel) productSubcategoryLabel.textContent = subName || '—';

                buildSubSwitch(spaceName, activeCat, activeSubId);
            }

            function pruneCategory(spaceName, cat) {
                var s = ensureSpaceState(spaceName);

                // remove selected subs + active sub
                if (s.selectedSubsByCat && s.selectedSubsByCat[cat]) delete s.selectedSubsByCat[cat];
                if (s.activeSubByCat && s.activeSubByCat[cat]) delete s.activeSubByCat[cat];

                // remove product buckets for all subs under this category
                var list = subCardsByCategory[cat] || [];
                list.forEach(function (card) {
                    var sid = getSubId(card);
                    if (sid && s.productsBySub && s.productsBySub[sid]) delete s.productsBySub[sid];
                });
            }

            function setActiveCategory(spaceName, cat) {
                var s = ensureSpaceState(spaceName);
                s.activeCategory = cat;

                // if there are selected categories, keep active inside them when possible
                if (s.categories.length && s.categories.indexOf(cat) === -1) {
                    // allow viewing subcats even if not selected; do not force-select
                    // but keep activeCategory as requested
                }

                var activeCat = normalizeActiveCategory(s);
                // if user explicitly clicked a cat not selected, honor it unless it is invalid
                if (allCategoryKeys.indexOf(cat) !== -1) activeCat = cat;
                s.activeCategory = activeCat;

                renderSubcategories(spaceName, activeCat);

                var activeSubId = resolveActiveSubId(spaceName, activeCat);
                // keep activeSubByCat in sync for rendering
                if (!s.activeSubByCat[activeCat]) s.activeSubByCat[activeCat] = activeSubId;

                renderProducts(spaceName, activeCat, s.activeSubByCat[activeCat]);

                updateLayerSelectionStatesForSpace(spaceName);
                updateTotalSelectedProducts();
                persist();
            }

            function toggleSubSelection(spaceName, activeCat, subId) {
                var s = ensureSpaceState(spaceName);

                // auto-select category if user is choosing subs/products under it
                if (s.categories.indexOf(activeCat) === -1) {
                    s.categories.push(activeCat);
                    renderCategories(spaceName);
                }

                if (!s.selectedSubsByCat[activeCat]) s.selectedSubsByCat[activeCat] = [];
                var arr = s.selectedSubsByCat[activeCat];

                var idx = arr.indexOf(subId);
                if (idx !== -1) {
                    arr.splice(idx, 1);

                    // if deselecting active sub, pick a fallback
                    if (s.activeSubByCat[activeCat] === subId) {
                        s.activeSubByCat[activeCat] = arr[0] || resolveActiveSubId(spaceName, activeCat);
                    }

                    // if no longer selected, keep products bucket (retains until user explicitly deselects sub? requirement says retain until de-select)
                    // When sub is deselected, remove products under it (explicit de-select implies remove)
                    if (s.productsBySub && s.productsBySub[subId]) delete s.productsBySub[subId];
                } else {
                    arr.push(subId);
                    // set active sub to the one user interacted with
                    s.activeSubByCat[activeCat] = subId;
                }

                renderSubcategories(spaceName, activeCat);
                renderProducts(spaceName, activeCat, s.activeSubByCat[activeCat]);

                updateLayerSelectionStatesForSpace(spaceName);
                updateTotalSelectedProducts();
                persist();
            }

            // Active Space
            var activeSpace = (function () {
                var first = document.querySelector('.cr-space-pill.selected');
                return first ? first.getAttribute('data-space') : 'Master Bed Room';
            })();

            // Initialize breadcrumb from Step-1
            if (breadcrumbType) breadcrumbType.textContent = step1.projectType || '—';
            if (breadcrumbSubType) breadcrumbSubType.textContent = step1.projectSubType || '—';
            if (breadcrumbSpace) breadcrumbSpace.textContent = activeSpace;
            if (categorySpaceLabel) categorySpaceLabel.textContent = activeSpace;

            // Space pills: rebuild from Step-1
            (function syncSpacesFromStep1() {
                if (!step1.spaces || !step1.spaces.length) return;

                var list = document.getElementById('spaceNavList');
                if (!list) return;

                var wanted = activeSpace;
                var exists = step1.spaces.some(function (sp) { return sp.name === wanted; });
                if (!exists) wanted = step1.spaces[0].name;

                list.innerHTML = '';
                step1.spaces.forEach(function (sp) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'cr-space-pill' + (sp.name === wanted ? ' selected' : '');
                    btn.setAttribute('data-space', sp.name);
                    btn.innerHTML = '<span class="cr-space-pill-dot"></span><span>' + esc(sp.name) + '</span>';
                    list.appendChild(btn);
                });

                activeSpace = wanted;
                if (breadcrumbSpace) breadcrumbSpace.textContent = activeSpace;
                if (categorySpaceLabel) categorySpaceLabel.textContent = activeSpace;

                var spacePills = document.querySelectorAll('.cr-space-pill');
                spacePills.forEach(function (pill) {
                    pill.addEventListener('click', function () {
                        spacePills.forEach(function (p) { p.classList.remove('selected'); });
                        pill.classList.add('selected');

                        activeSpace = pill.getAttribute('data-space');
                        if (breadcrumbSpace) breadcrumbSpace.textContent = activeSpace;
                        if (categorySpaceLabel) categorySpaceLabel.textContent = activeSpace;

                        applySpaceState(activeSpace);
                    });
                });
            })();

            // Category clicks: multi-select + sets active context
            categoryCards.forEach(function (card) {
                card.addEventListener('click', function (e) {
                    if (e.target && e.target.closest && e.target.closest('.js-zoom-btn')) return;

                    var s = ensureSpaceState(activeSpace);
                    var cat = (card.getAttribute('data-category') || '').trim();
                    if (!cat) return;

                    var already = s.categories.indexOf(cat) !== -1;

                    if (already) {
                        s.categories = s.categories.filter(function (x) { return x !== cat; });
                        pruneCategory(activeSpace, cat);
                    } else {
                        s.categories.push(cat);
                    }

                    // set active to clicked category always (lets you view subcats even if you just deselected it)
                    s.activeCategory = cat;

                    renderCategories(activeSpace);
                    persist();
                    setActiveCategory(activeSpace, cat);
                });
            });

            // Subcategory clicks: multi-select under active category
            subcategoryCards.forEach(function (card) {
                card.addEventListener('click', function (e) {
                    if (e.target && e.target.closest && e.target.closest('.js-zoom-btn')) return;

                    var s = ensureSpaceState(activeSpace);
                    var activeCat = normalizeActiveCategory(s);
                    // honor current view category if it differs (user may be exploring unselected category via switch)
                    if (s.activeCategory && allCategoryKeys.indexOf(s.activeCategory) !== -1) activeCat = s.activeCategory;

                    var catOfCard = (card.getAttribute('data-parent-category') || '').trim();
                    if (catOfCard && catOfCard !== activeCat) return;

                    var subId = getSubId(card);
                    if (!subId) return;

                    toggleSubSelection(activeSpace, activeCat, subId);
                });
            });

            // Product selection + qty (scoped by active subcategory context)
            productCards.forEach(function (card) {
                var qtyInput = card.querySelector('.js-qty-input');
                var minusBtn = card.querySelector('.js-qty-minus');
                var plusBtn = card.querySelector('.js-qty-plus');

                function getActiveContext() {
                    var s = ensureSpaceState(activeSpace);
                    var activeCat = normalizeActiveCategory(s);
                    if (s.activeCategory && allCategoryKeys.indexOf(s.activeCategory) !== -1) activeCat = s.activeCategory;

                    var activeSub = s.activeSubByCat[activeCat] || resolveActiveSubId(activeSpace, activeCat);
                    return { s: s, activeCat: activeCat, activeSubId: activeSub };
                }

                card.addEventListener('click', function (e) {
                    if (
                        (e.target && e.target.closest && e.target.closest('.js-zoom-btn')) ||
                        (e.target && (e.target.classList.contains('js-qty-minus') ||
                            e.target.classList.contains('js-qty-plus') ||
                            e.target.classList.contains('js-qty-input')))
                    ) {
                        return;
                    }

                    var ctx = getActiveContext();
                    var activeSubId = ctx.activeSubId;
                    var cardSubId = (card.getAttribute('data-parent-subcategory-id') || '').trim();
                    if (cardSubId && cardSubId !== activeSubId) return;

                    if (!activeSubId || activeSubId === '—') return;

                    // ensure category + sub are selected when choosing products
                    if (ctx.s.categories.indexOf(ctx.activeCat) === -1) {
                        ctx.s.categories.push(ctx.activeCat);
                        renderCategories(activeSpace);
                    }
                    if (!ctx.s.selectedSubsByCat[ctx.activeCat]) ctx.s.selectedSubsByCat[ctx.activeCat] = [];
                    if (ctx.s.selectedSubsByCat[ctx.activeCat].indexOf(activeSubId) === -1) {
                        ctx.s.selectedSubsByCat[ctx.activeCat].push(activeSubId);
                    }
                    ctx.s.activeSubByCat[ctx.activeCat] = activeSubId;

                    if (!ctx.s.productsBySub[activeSubId] || typeof ctx.s.productsBySub[activeSubId] !== 'object') {
                        ctx.s.productsBySub[activeSubId] = {};
                    }

                    var pid = getProductId(card);
                    if (!pid) return;

                    var bucket = ctx.s.productsBySub[activeSubId];

                    if (bucket[pid]) {
                        delete bucket[pid];
                        card.classList.remove('selected');
                    } else {
                        if (qtyInput && (qtyInput.value === '0' || qtyInput.value.trim() === '')) qtyInput.value = '1';
                        bucket[pid] = getProductPayloadFromCard(card);
                        card.classList.add('selected');
                    }

                    renderSubcategories(activeSpace, ctx.activeCat);
                    renderProducts(activeSpace, ctx.activeCat, activeSubId);

                    persist();
                    updateLayerSelectionStatesForSpace(activeSpace);
                    updateTotalSelectedProducts();
                });

                if (minusBtn) {
                    minusBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        var ctx = getActiveContext();
                        var activeSubId = ctx.activeSubId;
                        var cardSubId = (card.getAttribute('data-parent-subcategory-id') || '').trim();
                        if (cardSubId && cardSubId !== activeSubId) return;

                        var current = parseInt(qtyInput.value || '0', 10);
                        if (current > 1) qtyInput.value = String(current - 1);

                        var pid = getProductId(card);
                        if (activeSubId !== '—' && ctx.s.productsBySub[activeSubId] && ctx.s.productsBySub[activeSubId][pid]) {
                            ctx.s.productsBySub[activeSubId][pid].qty = parseInt(qtyInput.value || '1', 10) || 1;
                            persist();
                        }
                    });
                }

                if (plusBtn) {
                    plusBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        var ctx = getActiveContext();
                        var activeSubId = ctx.activeSubId;
                        var cardSubId = (card.getAttribute('data-parent-subcategory-id') || '').trim();
                        if (cardSubId && cardSubId !== activeSubId) return;

                        var current = parseInt(qtyInput.value || '0', 10);
                        qtyInput.value = String((current || 0) + 1);

                        if (!activeSubId || activeSubId === '—') return;

                        // ensure category + sub are selected when using qty
                        if (ctx.s.categories.indexOf(ctx.activeCat) === -1) {
                            ctx.s.categories.push(ctx.activeCat);
                            renderCategories(activeSpace);
                        }
                        if (!ctx.s.selectedSubsByCat[ctx.activeCat]) ctx.s.selectedSubsByCat[ctx.activeCat] = [];
                        if (ctx.s.selectedSubsByCat[ctx.activeCat].indexOf(activeSubId) === -1) {
                            ctx.s.selectedSubsByCat[ctx.activeCat].push(activeSubId);
                        }
                        ctx.s.activeSubByCat[ctx.activeCat] = activeSubId;

                        if (!ctx.s.productsBySub[activeSubId] || typeof ctx.s.productsBySub[activeSubId] !== 'object') {
                            ctx.s.productsBySub[activeSubId] = {};
                        }

                        var pid = getProductId(card);
                        if (!pid) return;

                        if (!ctx.s.productsBySub[activeSubId][pid]) {
                            ctx.s.productsBySub[activeSubId][pid] = getProductPayloadFromCard(card);
                            card.classList.add('selected');
                        } else {
                            ctx.s.productsBySub[activeSubId][pid].qty = parseInt(qtyInput.value || '1', 10) || 1;
                        }

                        renderSubcategories(activeSpace, ctx.activeCat);
                        renderProducts(activeSpace, ctx.activeCat, activeSubId);

                        persist();
                        updateLayerSelectionStatesForSpace(activeSpace);
                        updateTotalSelectedProducts();
                    });
                }
            });

            /* Zoom opens only from zoom button */
            var zoomBackdrop = document.getElementById('zoomBackdrop');
            var zoomImg = document.getElementById('zoomImg');
            var zoomCloseBtn = document.getElementById('zoomCloseBtn');

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

            if (zoomCloseBtn) zoomCloseBtn.addEventListener('click', closeZoom);
            if (zoomBackdrop) {
                zoomBackdrop.addEventListener('click', function (e) {
                    if (e.target === zoomBackdrop) closeZoom();
                });
            }

            /* Summary Drawer */
            var viewSelectedBtn = document.getElementById('viewSelectedBtn');
            var summaryDrawer = document.getElementById('summaryDrawer');
            var summaryOverlay = document.getElementById('summaryOverlay');
            var summaryCloseBtn = document.getElementById('summaryCloseBtn');
            var summaryBody = document.getElementById('summaryBody');
            var downloadPdfBtn = document.getElementById('downloadPdfBtn');
            var printPdfBtn = document.getElementById('printPdfBtn');

            function listAllSubcategoriesForSpace(spaceName) {
                var s = ensureSpaceState(spaceName);
                var out = [];
                if (!s.selectedSubsByCat) return out;
                s.categories.forEach(function (cat) {
                    var arr = getSelectedSubsForCat(s, cat);
                    arr.forEach(function (sid) { out.push(sid); });
                });
                // unique
                var uniq = [];
                out.forEach(function (x) { if (uniq.indexOf(x) === -1) uniq.push(x); });
                return uniq;
            }

            function listAllProductsForSpace(spaceName) {
                var s = ensureSpaceState(spaceName);
                var out = [];
                if (!s.productsBySub) return out;
                Object.keys(s.productsBySub).forEach(function (subId) {
                    var bucket = s.productsBySub[subId];
                    if (!bucket || typeof bucket !== 'object') return;
                    Object.keys(bucket).forEach(function (pid) {
                        out.push(bucket[pid]);
                    });
                });
                return out;
            }

            function buildSummaryHTML() {
                var html = '';

                html += '<div class="cr-summary-section-title">Project Details (Step-1)</div>';
                html += '<div class="cr-summary-space" style="margin-top:0;">';
                html += '<div class="cr-summary-space-head">';
                html += '<div>';
                html += '<div class="cr-summary-space-title">' + esc(step1.regId) + '</div>';
                html += '<div class="cr-summary-space-meta">' + esc(step1.name) + '</div>';
                html += '</div>';
                html += '<div class="cr-summary-space-meta" style="text-align:right;">' + esc(step1.projectType) + ' → ' + esc(step1.projectSubType) + '</div>';
                html += '</div>';

                html += '<div style="margin-top:8px; font-size:0.8rem; color:var(--meta-soft); line-height:1.5;">';
                html += '<div><strong>Email:</strong> ' + esc(step1.email) + '</div>';
                html += '<div><strong>Contact:</strong> ' + esc(step1.phone) + '</div>';
                html += '<div><strong>Address:</strong> ' + esc(step1.address) + '</div>';
                html += '<div><strong>Budget:</strong> ' + esc(step1.budget) + '</div>';
                html += '<div><strong>Expected Delivery:</strong> ' + esc(step1.eta) + '</div>';
                html += '</div>';
                html += '</div>';

                html += '<div class="cr-summary-section-title">Selections (Step-2)</div>';

                var spaces = (step1.spaces && step1.spaces.length) ? step1.spaces : [{ name: activeSpace, qty: 1, sqft: 0 }];
                spaces.forEach(function (sp) {
                    var sName = sp.name;
                    var s = ensureSpaceState(sName);

                    html += '<div class="cr-summary-space">';
                    html += '<div class="cr-summary-space-head">';
                    html += '<div>';
                    html += '<div class="cr-summary-space-title">' + esc(sName) + '</div>';
                    html += '<div class="cr-summary-space-meta">Qty: ' + esc(sp.qty) + ' • Total SQFT: ' + esc(sp.sqft) + '</div>';
                    html += '</div>';
                    html += '<div class="cr-summary-space-meta">' + esc(step1.projectType) + '</div>';
                    html += '</div>';

                    html += '<div style="margin-top:10px;">';
                    html += '<div style="font-weight:700; font-size:0.8rem;">Categories</div>';
                    if (s.categories && s.categories.length) {
                        html += '<div class="cr-chip-row">';
                        s.categories.forEach(function (c) { html += '<span class="cr-chip accent">' + esc(c) + '</span>'; });
                        html += '</div>';
                    } else {
                        html += '<div class="cr-summary-space-meta">No category selected.</div>';
                    }
                    html += '</div>';

                    html += '<div style="margin-top:10px;">';
                    html += '<div style="font-weight:700; font-size:0.8rem;">Sub-Categories</div>';
                    var subs = listAllSubcategoriesForSpace(sName).map(getSubNameById);
                    if (subs.length) {
                        html += '<div class="cr-chip-row">';
                        subs.forEach(function (c) { html += '<span class="cr-chip">' + esc(c) + '</span>'; });
                        html += '</div>';
                    } else {
                        html += '<div class="cr-summary-space-meta">No sub-category selected.</div>';
                    }
                    html += '</div>';

                    html += '<div style="margin-top:10px;">';
                    html += '<div style="font-weight:700; font-size:0.8rem;">Products</div>';
                    var products = listAllProductsForSpace(sName);
                    if (products.length) {
                        html += '<div class="cr-summary-products">';
                        products.forEach(function (p) {
                            html += '<div class="cr-summary-product">';
                            html += '<img src="' + esc(p.thumb || p.image) + '" alt="' + esc(p.title) + '">';
                            html += '<div>';
                            html += '<div class="cr-summary-product-title">' + esc(p.title) + '</div>';
                            html += '<div class="cr-summary-product-meta">';
                            if (p.sku) html += '<div><strong>SKU:</strong> ' + esc(p.sku) + '</div>';
                            html += '<div><strong>Qty:</strong> ' + esc(p.qty) + '</div>';
                            if (p.origin) html += '<div><strong>Origin:</strong> ' + esc(p.origin) + '</div>';
                            html += '</div>';
                            html += '</div>';
                            html += '</div>';
                        });
                        html += '</div>';
                    } else {
                        html += '<div class="cr-summary-space-meta">No product selected.</div>';
                    }
                    html += '</div>';

                    html += '</div>';
                });

                html += '<div class="cr-summary-footer-note">';
                html += 'PDF is generated via A4 print layout. Use “Download PDF (A4)” → Save as PDF.';
                html += '</div>';

                return html;
            }

            function openSummary() {
                if (summaryBody) summaryBody.innerHTML = buildSummaryHTML();
                summaryDrawer.classList.add('is-open');
                summaryOverlay.classList.add('is-open');
            }

            function closeSummary() {
                summaryDrawer.classList.remove('is-open');
                summaryOverlay.classList.remove('is-open');
            }

            if (viewSelectedBtn) viewSelectedBtn.addEventListener('click', openSummary);
            if (summaryCloseBtn) summaryCloseBtn.addEventListener('click', closeSummary);
            if (summaryOverlay) summaryOverlay.addEventListener('click', closeSummary);

            function buildA4DocumentHTML() {
                var now = new Date();
                var genTime = now.toLocaleString();
                var spaces = (step1.spaces && step1.spaces.length) ? step1.spaces : [{ name: activeSpace, qty: 1, sqft: 0 }];

                // Client meta (Step-1 provides these; fallback-safe)
                var clientRegId = (step1.regId || step1.reg_id || '').toString();
                var clientUserId = (step1.clientUserId || step1.userId || step1.client_user_id || step1.user_id || '').toString();
                var clusterText = '';
                if (step1.cluster && ('' + step1.cluster).trim()) {
                    clusterText = '' + step1.cluster;
                } else {
                    var cid = (step1.clusterId || step1.cluster_id || '').toString();
                    var cname = (step1.clusterName || step1.cluster_name || '').toString();
                    if (cid && cname) clusterText = cid + ' - ' + cname;
                    else if (cid) clusterText = cid;
                    else if (cname) clusterText = cname;
                }


                function sectionLine(label, value) {
                    return '<div class="kv"><div class="k">' + esc(label) + '</div><div class="v">' + esc(value) + '</div></div>';
                }

                var html = '';
                html += '<!DOCTYPE html><html><head><meta charset="utf-8">';
                html += '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
                html += '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
                html += '<title>Requisition PDF</title>';
                html += '<style>';
                html += 'html,body{margin:0;padding:0;font-family:Poppins,system-ui,-apple-system,Segoe UI,sans-serif;color:#0f172a;}';
                html += '@page{size:A4; margin:14mm;}';
                html += '.page{width:210mm; min-height:297mm; padding:0; box-sizing:border-box;}';
                html += '.hdr{display:flex; justify-content:space-between; align-items:flex-start; gap:12px; border-bottom:1px solid #e5e7eb; padding-bottom:10px; margin-bottom:12px;}';
                html += '.brand{display:flex; gap:10px; align-items:center;}';
                html += '.mark{width:34px;height:34px;border-radius:50%;background:radial-gradient(circle at 20% 10%, #facc15, #f97316 40%, #0ea5e9 100%);}';
                html += '.h1{font-size:16px;font-weight:700;margin:0;}';
                html += '.sub{font-size:11px;color:#64748b;margin-top:2px;}';
                html += '.meta{font-size:11px;color:#64748b;text-align:right;}';
                html += '.card{border:1px solid #e5e7eb;border-radius:12px;padding:10px;margin-bottom:10px;}';
                html += '.title{font-size:12px;font-weight:700;margin:0 0 6px 0;}';
                html += '.grid2{display:grid;grid-template-columns:1fr 1fr;gap:10px;}';
                html += '.kv{display:flex;gap:8px;align-items:flex-start;font-size:11px;line-height:1.35;}';
                html += '.k{min-width:95px;color:#475569;font-weight:600;}';
                html += '.v{color:#0f172a;flex:1;}';
                html += '.chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;}';
                html += '.chip{font-size:10px;padding:3px 8px;border-radius:999px;border:1px solid #e5e7eb;background:#fff;}';
                html += '.chip.accent{border-color:rgba(234,88,12,.35);background:rgba(245,158,11,.12);}';
                html += '.space{page-break-inside:avoid;}';
                html += '.prod{display:grid;grid-template-columns:76px minmax(0,1fr);gap:10px;align-items:center;border:1px solid #e5e7eb;border-radius:12px;padding:8px;margin-top:8px;}';
                html += '.prod img{width:76px;height:56px;object-fit:cover;border-radius:10px;background:#e5e7eb;display:block;}';
                html += '.pname{font-size:11px;font-weight:700;margin:0;}';
                html += '.pmeta{font-size:10px;color:#475569;line-height:1.35;margin-top:2px;}';
                html += '.foot{margin-top:10px;font-size:10px;color:#64748b;border-top:1px dashed #e5e7eb;padding-top:8px;}';
                html += '@media print{.no-print{display:none !important;}}';
                html += '</style>';
                html += '
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
            var STORAGE_STEP1 = 'cr_step1';
            var STORAGE_STEP2 = 'cr_step2';
            var STEP2_VERSION = 3;

            function safeParse(json, fallback) {
                try { return JSON.parse(json); } catch (e) { return fallback; }
            }

            function getDefaultStep1() {
                return {
                    regId: 'REG-INT-2025-00123',
                    name: 'Mr. Salahuddin Ahmed',
                    email: 'client@example.com',
                    phone: '+8801XXXXXXXXX',
                    address: 'Sample address from Step 1',
                    budget: '25,00,000 BDT (approx.)',
                    eta: '31-12-2025',
                    projectType: 'Residential',
                    projectSubType: 'Duplex',
                    spaces: [
                        { name: 'Master Bed Room', qty: 1, sqft: 220 },
                        { name: 'Child Bed Room', qty: 1, sqft: 180 },
                        { name: 'Living / Lounge', qty: 1, sqft: 260 },
                        { name: 'Dining', qty: 1, sqft: 160 }
                    ]
                };
            }

            function loadStep1() {
                var raw = localStorage.getItem(STORAGE_STEP1);
                var data = safeParse(raw, null);
                return data && typeof data === 'object' ? Object.assign(getDefaultStep1(), data) : getDefaultStep1();
            }

            function getEmptyStep2() {
                return {
                    version: STEP2_VERSION,
                    bySpace: {} // spaceName -> state
                };
            }

            function saveStep2(state) {
                localStorage.setItem(STORAGE_STEP2, JSON.stringify(state));
            }

            function migrateIfNeeded(rawObj) {
                if (!rawObj || typeof rawObj !== 'object') return getEmptyStep2();
                if (!rawObj.bySpace || typeof rawObj.bySpace !== 'object') rawObj.bySpace = {};

                if (rawObj.version === STEP2_VERSION) return rawObj;

                // v1/v2 -> v3: selectedSubsByCat array + activeSubByCat
                Object.keys(rawObj.bySpace).forEach(function (space) {
                    var s = rawObj.bySpace[space] || {};
                    if (!Array.isArray(s.categories)) s.categories = [];
                    if (!s.selectedSubsByCat || typeof s.selectedSubsByCat !== 'object') {
                        // older key names
                        if (s.selectedSubByCat && typeof s.selectedSubByCat === 'object') {
                            s.selectedSubsByCat = {};
                            Object.keys(s.selectedSubByCat).forEach(function (cat) {
                                var one = s.selectedSubByCat[cat];
                                s.selectedSubsByCat[cat] = one ? [one] : [];
                            });
                            delete s.selectedSubByCat;
                        } else {
                            s.selectedSubsByCat = {};
                        }
                    } else {
                        // ensure arrays
                        Object.keys(s.selectedSubsByCat).forEach(function (cat2) {
                            if (!Array.isArray(s.selectedSubsByCat[cat2])) {
                                var v = s.selectedSubsByCat[cat2];
                                s.selectedSubsByCat[cat2] = v ? [v] : [];
                            }
                        });
                    }

                    if (!s.activeSubByCat || typeof s.activeSubByCat !== 'object') {
                        s.activeSubByCat = {};
                        Object.keys(s.selectedSubsByCat).forEach(function (cat3) {
                            var arr = s.selectedSubsByCat[cat3] || [];
                            if (arr.length) s.activeSubByCat[cat3] = arr[0];
                        });
                    }

                    if (!s.productsBySub || typeof s.productsBySub !== 'object') s.productsBySub = {};
                    if (!s.activeCategory) s.activeCategory = s.categories[0] || null;

                    rawObj.bySpace[space] = s;
                });

                rawObj.version = STEP2_VERSION;
                return rawObj;
            }

            function loadStep2() {
                var raw = localStorage.getItem(STORAGE_STEP2);
                var data = safeParse(raw, null);
                return migrateIfNeeded(data);
            }

            var step1 = loadStep1();
            var step2 = loadStep2();

            // DOM refs
            var breadcrumbType = document.getElementById('breadcrumb-type');
            var breadcrumbSubType = document.getElementById('breadcrumb-subtype');
            var breadcrumbSpace = document.getElementById('breadcrumb-space');
            var breadcrumbCategory = document.getElementById('breadcrumb-category');
            var breadcrumbSubcategory = document.getElementById('breadcrumb-subcategory');

            var categorySpaceLabel = document.getElementById('categorySpaceLabel');
            var subcategoryCategoryLabel = document.getElementById('subcategoryCategoryLabel');
            var productSubcategoryLabel = document.getElementById('productSubcategoryLabel');

            var catSwitchRow = document.getElementById('catSwitchRow');
            var subSwitchRow = document.getElementById('subSwitchRow');

            var totalProductCountEl = document.getElementById('totalProductCount');

            var categoryCardWrap = document.getElementById('categoryCard');
            var subcategoryCardWrap = document.getElementById('subcategoryCard');
            var productsCardWrap = document.getElementById('productsCard');

            var categoryCards = Array.prototype.slice.call(document.querySelectorAll('.js-category-card'));
            var subcategoryCards = Array.prototype.slice.call(document.querySelectorAll('.js-subcategory-card'));
            var productCards = Array.prototype.slice.call(document.querySelectorAll('.js-product-card'));

            var allCategoryKeys = categoryCards.map(function (c) { return (c.getAttribute('data-category') || '').trim(); }).filter(Boolean);

            // Build sub meta: id -> {name, category}
            var subMetaById = {};
            subcategoryCards.forEach(function (card) {
                var cat = (card.getAttribute('data-parent-category') || '').trim();
                var sid = (card.getAttribute('data-subcategory-id') || '').trim();
                var name = (card.getAttribute('data-subcategory') || '').trim() ||
                    (card.querySelector('.cr-circle-label') ? card.querySelector('.cr-circle-label').textContent.trim() : sid);
                if (sid) subMetaById[sid] = { id: sid, name: name, category: cat };
            });

            // Group sub cards by category
            var subCardsByCategory = {};
            subcategoryCards.forEach(function (card) {
                var cat = (card.getAttribute('data-parent-category') || '').trim();
                if (!subCardsByCategory[cat]) subCardsByCategory[cat] = [];
                subCardsByCategory[cat].push(card);
            });

            function esc(s) {
                return String(s || '').replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }

            function setSelectedTickClass(cardEl, isSelected) {
                cardEl.classList.toggle('is-selected', !!isSelected);
            }

            function getSubId(card) {
                return (card.getAttribute('data-subcategory-id') || card.getAttribute('data-subcategory') || '').trim();
            }

            function getSubNameById(subId) {
                return (subMetaById[subId] && subMetaById[subId].name) ? subMetaById[subId].name : (subId || '—');
            }

            function firstAvailableCategory() {
                return allCategoryKeys[0] || '—';
            }

            function persist() {
                step2.version = STEP2_VERSION;
                saveStep2(step2);
            }

            function getDefaultSpaceState() {
                // Demo defaults only on first-time load (keeps UI not blank)
                return {
                    categories: ['Furniture'],
                    activeCategory: 'Furniture',
                    selectedSubsByCat: { 'Furniture': ['SC-FUR-BED'] },
                    activeSubByCat: { 'Furniture': 'SC-FUR-BED' },
                    productsBySub: {}
                };
            }

            function ensureSpaceState(spaceName) {
                if (!step2.bySpace[spaceName]) {
                    step2.bySpace[spaceName] = getDefaultSpaceState();
                }

                var s = step2.bySpace[spaceName];
                if (!Array.isArray(s.categories)) s.categories = [];
                if (!s.activeCategory) s.activeCategory = s.categories[0] || firstAvailableCategory();
                if (!s.selectedSubsByCat || typeof s.selectedSubsByCat !== 'object') s.selectedSubsByCat = {};
                if (!s.activeSubByCat || typeof s.activeSubByCat !== 'object') s.activeSubByCat = {};
                if (!s.productsBySub || typeof s.productsBySub !== 'object') s.productsBySub = {};

                // ensure arrays for selectedSubsByCat
                Object.keys(s.selectedSubsByCat).forEach(function (cat) {
                    if (!Array.isArray(s.selectedSubsByCat[cat])) {
                        var v = s.selectedSubsByCat[cat];
                        s.selectedSubsByCat[cat] = v ? [v] : [];
                    }
                });

                return s;
            }

            function normalizeActiveCategory(s) {
                // active category can be any, but if selected categories exist, prefer one of them
                if (s.activeCategory && allCategoryKeys.indexOf(s.activeCategory) !== -1) {
                    if (!s.categories.length) return s.activeCategory;
                    if (s.categories.indexOf(s.activeCategory) !== -1) return s.activeCategory;
                }
                if (s.categories.length) return s.categories[0];
                return firstAvailableCategory();
            }

            function setSliderItemVisible(el, visible) {
                var item = el && el.closest ? el.closest('.slider-item') : null;
                if (!item) item = el;
                if (!item) return;
                item.style.display = visible ? '' : 'none';
            }

            function setProductsSliderItemVisible(el, visible) {
                var item = el && el.closest ? el.closest('.slider-item-product') : null;
                if (!item) item = el && el.closest ? el.closest('.slider-item') : null;
                if (!item) item = el;
                if (!item) return;
                item.style.display = visible ? '' : 'none';
            }

            function getProductId(card) {
                return (card.getAttribute('data-product-id') || '').trim();
            }

            function getProductPayloadFromCard(card) {
                var pid = getProductId(card);
                var qtyInput = card.querySelector('.js-qty-input');
                var qty = parseInt((qtyInput && qtyInput.value) ? qtyInput.value : '1', 10);
                if (!qty || qty < 1) qty = 1;

                return {
                    id: pid,
                    title: card.getAttribute('data-product-title') || (card.querySelector('.cr-product-title') ? card.querySelector('.cr-product-title').textContent.trim() : pid),
                    sku: card.getAttribute('data-product-sku') || '',
                    origin: card.getAttribute('data-product-origin') || '',
                    spec: card.getAttribute('data-product-spec') || '',
                    thumb: card.getAttribute('data-thumb') || (card.querySelector('img') ? card.querySelector('img').src : ''),
                    image: card.getAttribute('data-image') || (card.querySelector('img') ? card.querySelector('img').src : ''),
                    qty: qty
                };
            }

            function updateTotalSelectedProducts() {
                var total = 0;
                Object.keys(step2.bySpace).forEach(function (space) {
                    var s = step2.bySpace[space];
                    if (!s || !s.productsBySub) return;
                    Object.keys(s.productsBySub).forEach(function (subId) {
                        var bucket = s.productsBySub[subId];
                        if (!bucket || typeof bucket !== 'object') return;
                        total += Object.keys(bucket).length;
                    });
                });
                if (totalProductCountEl) totalProductCountEl.textContent = String(total);
            }

            function updateLayerSelectionStatesForSpace(spaceName) {
                var s = ensureSpaceState(spaceName);

                var hasCat = s.categories && s.categories.length > 0;

                var hasSub = false;
                if (s.selectedSubsByCat && typeof s.selectedSubsByCat === 'object') {
                    hasSub = Object.keys(s.selectedSubsByCat).some(function (cat) {
                        var arr = s.selectedSubsByCat[cat];
                        return Array.isArray(arr) && arr.length > 0 && s.categories.indexOf(cat) !== -1;
                    });
                }

                var hasProd = false;
                if (s.productsBySub && typeof s.productsBySub === 'object') {
                    hasProd = Object.keys(s.productsBySub).some(function (subId) {
                        var b = s.productsBySub[subId];
                        return b && typeof b === 'object' && Object.keys(b).length > 0;
                    });
                }

                if (categoryCardWrap) categoryCardWrap.classList.toggle('has-selection', hasCat);
                if (subcategoryCardWrap) subcategoryCardWrap.classList.toggle('has-selection', hasSub);
                if (productsCardWrap) productsCardWrap.classList.toggle('has-selection', hasProd);
            }

            function renderCategories(spaceName) {
                var s = ensureSpaceState(spaceName);
                categoryCards.forEach(function (card) {
                    var cat = (card.getAttribute('data-category') || '').trim();
                    var on = s.categories.indexOf(cat) !== -1;
                    card.classList.toggle('selected', on);
                    setSelectedTickClass(card, on);
                });
            }

            function buildCategorySwitch(spaceName, activeCat) {
                if (!catSwitchRow) return;
                var s = ensureSpaceState(spaceName);

                var html = '';
                html += '<span class="cr-switch-hint">Active Category:</span>';
                allCategoryKeys.forEach(function (cat) {
                    var picked = s.categories.indexOf(cat) !== -1;
                    var isActive = (cat === activeCat);
                    html += '<button type="button" class="cr-switch-chip' +
                        (picked ? ' picked' : '') +
                        (isActive ? ' active' : '') +
                        '" data-cat="' + esc(cat) + '"><span class="dot"></span><span>' + esc(cat) + '</span></button>';
                });

                catSwitchRow.innerHTML = html;

                Array.prototype.slice.call(catSwitchRow.querySelectorAll('.cr-switch-chip[data-cat]')).forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var cat = btn.getAttribute('data-cat');
                        if (!cat) return;
                        setActiveCategory(activeSpace, cat);
                    });
                });
            }

            function getSelectedSubsForCat(s, cat) {
                var arr = s.selectedSubsByCat[cat];
                return Array.isArray(arr) ? arr : [];
            }

            function resolveActiveSubId(spaceName, activeCat) {
                var s = ensureSpaceState(spaceName);

                var active = s.activeSubByCat[activeCat];
                if (active) return active;

                var selected = getSelectedSubsForCat(s, activeCat);
                if (selected.length) return selected[0];

                // fallback: first visible sub in this category
                var list = subCardsByCategory[activeCat] || [];
                if (list.length) return getSubId(list[0]);

                return '—';
            }

            function buildSubSwitch(spaceName, activeCat, activeSubId) {
                if (!subSwitchRow) return;
                var s = ensureSpaceState(spaceName);

                var list = subCardsByCategory[activeCat] || [];
                if (!list.length) {
                    subSwitchRow.innerHTML = '<span class="cr-switch-hint">No sub-categories available for this category.</span>';
                    return;
                }

                var selectedArr = getSelectedSubsForCat(s, activeCat);
                var html = '';
                html += '<span class="cr-switch-hint">Active Sub-Category:</span>';

                list.forEach(function (card) {
                    var sid = getSubId(card);
                    var name = (card.getAttribute('data-subcategory') || '').trim() || getSubNameById(sid);
                    var picked = selectedArr.indexOf(sid) !== -1;
                    var isActive = (sid === activeSubId);
                    html += '<button type="button" class="cr-switch-chip' +
                        (picked ? ' picked' : '') +
                        (isActive ? ' active' : '') +
                        '" data-sub="' + esc(sid) + '"><span class="dot"></span><span>' + esc(name) + '</span></button>';
                });

                subSwitchRow.innerHTML = html;

                Array.prototype.slice.call(subSwitchRow.querySelectorAll('.cr-switch-chip[data-sub]')).forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var sid = btn.getAttribute('data-sub');
                        if (!sid) return;
                        var ss = ensureSpaceState(activeSpace);
                        var ac = normalizeActiveCategory(ss);
                        ss.activeCategory = ac;
                        ss.activeSubByCat[ac] = sid;
                        renderProducts(activeSpace, ac, sid);
                        renderSubcategories(activeSpace, ac);
                        updateLayerSelectionStatesForSpace(activeSpace);
                        updateTotalSelectedProducts();
                        persist();
                    });
                });
            }

            function renderSubcategories(spaceName, activeCat) {
                var s = ensureSpaceState(spaceName);

                // show only subcategories for active category
                subcategoryCards.forEach(function (card) {
                    var cat = (card.getAttribute('data-parent-category') || '').trim();
                    setSliderItemVisible(card, (cat === activeCat));
                });

                var selectedArr = getSelectedSubsForCat(s, activeCat);

                subcategoryCards.forEach(function (card) {
                    var cat = (card.getAttribute('data-parent-category') || '').trim();
                    var sid = getSubId(card);
                    var on = (cat === activeCat && selectedArr.indexOf(sid) !== -1);
                    card.classList.toggle('selected', on);
                    setSelectedTickClass(card, on);
                });

                if (subcategoryCategoryLabel) subcategoryCategoryLabel.textContent = activeCat || '—';
                if (breadcrumbCategory) breadcrumbCategory.textContent = activeCat || '—';

                buildCategorySwitch(spaceName, activeCat);

                var activeSubId = resolveActiveSubId(spaceName, activeCat);
                buildSubSwitch(spaceName, activeCat, activeSubId);
            }

            function renderProducts(spaceName, activeCat, activeSubId) {
                var s = ensureSpaceState(spaceName);
                if (!activeSubId || activeSubId === '—') activeSubId = resolveActiveSubId(spaceName, activeCat);

                // show only products for active subcategory
                productCards.forEach(function (card) {
                    var subId = (card.getAttribute('data-parent-subcategory-id') || '').trim();
                    setProductsSliderItemVisible(card, (subId === activeSubId));
                });

                // apply selection/qty from bucket
                var bucket = s.productsBySub[activeSubId];
                if (!bucket || typeof bucket !== 'object') bucket = {};

                productCards.forEach(function (card) {
                    var pid = getProductId(card);
                    var subId = (card.getAttribute('data-parent-subcategory-id') || '').trim();
                    if (subId !== activeSubId) {
                        card.classList.remove('selected');
                        return;
                    }
                    var on = !!bucket[pid];
                    card.classList.toggle('selected', on);
                    var qtyInput = card.querySelector('.js-qty-input');
                    if (qtyInput) qtyInput.value = on ? String(bucket[pid].qty || 1) : (qtyInput.value || '1');
                });

                var subName = getSubNameById(activeSubId);
                if (breadcrumbSubcategory) breadcrumbSubcategory.textContent = subName || '—';
                if (productSubcategoryLabel) productSubcategoryLabel.textContent = subName || '—';

                buildSubSwitch(spaceName, activeCat, activeSubId);
            }

            function pruneCategory(spaceName, cat) {
                var s = ensureSpaceState(spaceName);

                // remove selected subs + active sub
                if (s.selectedSubsByCat && s.selectedSubsByCat[cat]) delete s.selectedSubsByCat[cat];
                if (s.activeSubByCat && s.activeSubByCat[cat]) delete s.activeSubByCat[cat];

                // remove product buckets for all subs under this category
                var list = subCardsByCategory[cat] || [];
                list.forEach(function (card) {
                    var sid = getSubId(card);
                    if (sid && s.productsBySub && s.productsBySub[sid]) delete s.productsBySub[sid];
                });
            }

            function setActiveCategory(spaceName, cat) {
                var s = ensureSpaceState(spaceName);
                s.activeCategory = cat;

                // if there are selected categories, keep active inside them when possible
                if (s.categories.length && s.categories.indexOf(cat) === -1) {
                    // allow viewing subcats even if not selected; do not force-select
                    // but keep activeCategory as requested
                }

                var activeCat = normalizeActiveCategory(s);
                // if user explicitly clicked a cat not selected, honor it unless it is invalid
                if (allCategoryKeys.indexOf(cat) !== -1) activeCat = cat;
                s.activeCategory = activeCat;

                renderSubcategories(spaceName, activeCat);

                var activeSubId = resolveActiveSubId(spaceName, activeCat);
                // keep activeSubByCat in sync for rendering
                if (!s.activeSubByCat[activeCat]) s.activeSubByCat[activeCat] = activeSubId;

                renderProducts(spaceName, activeCat, s.activeSubByCat[activeCat]);

                updateLayerSelectionStatesForSpace(spaceName);
                updateTotalSelectedProducts();
                persist();
            }

            function toggleSubSelection(spaceName, activeCat, subId) {
                var s = ensureSpaceState(spaceName);

                // auto-select category if user is choosing subs/products under it
                if (s.categories.indexOf(activeCat) === -1) {
                    s.categories.push(activeCat);
                    renderCategories(spaceName);
                }

                if (!s.selectedSubsByCat[activeCat]) s.selectedSubsByCat[activeCat] = [];
                var arr = s.selectedSubsByCat[activeCat];

                var idx = arr.indexOf(subId);
                if (idx !== -1) {
                    arr.splice(idx, 1);

                    // if deselecting active sub, pick a fallback
                    if (s.activeSubByCat[activeCat] === subId) {
                        s.activeSubByCat[activeCat] = arr[0] || resolveActiveSubId(spaceName, activeCat);
                    }

                    // if no longer selected, keep products bucket (retains until user explicitly deselects sub? requirement says retain until de-select)
                    // When sub is deselected, remove products under it (explicit de-select implies remove)
                    if (s.productsBySub && s.productsBySub[subId]) delete s.productsBySub[subId];
                } else {
                    arr.push(subId);
                    // set active sub to the one user interacted with
                    s.activeSubByCat[activeCat] = subId;
                }

                renderSubcategories(spaceName, activeCat);
                renderProducts(spaceName, activeCat, s.activeSubByCat[activeCat]);

                updateLayerSelectionStatesForSpace(spaceName);
                updateTotalSelectedProducts();
                persist();
            }

            // Active Space
            var activeSpace = (function () {
                var first = document.querySelector('.cr-space-pill.selected');
                return first ? first.getAttribute('data-space') : 'Master Bed Room';
            })();

            // Initialize breadcrumb from Step-1
            if (breadcrumbType) breadcrumbType.textContent = step1.projectType || '—';
            if (breadcrumbSubType) breadcrumbSubType.textContent = step1.projectSubType || '—';
            if (breadcrumbSpace) breadcrumbSpace.textContent = activeSpace;
            if (categorySpaceLabel) categorySpaceLabel.textContent = activeSpace;

            // Space pills: rebuild from Step-1
            (function syncSpacesFromStep1() {
                if (!step1.spaces || !step1.spaces.length) return;

                var list = document.getElementById('spaceNavList');
                if (!list) return;

                var wanted = activeSpace;
                var exists = step1.spaces.some(function (sp) { return sp.name === wanted; });
                if (!exists) wanted = step1.spaces[0].name;

                list.innerHTML = '';
                step1.spaces.forEach(function (sp) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'cr-space-pill' + (sp.name === wanted ? ' selected' : '');
                    btn.setAttribute('data-space', sp.name);
                    btn.innerHTML = '<span class="cr-space-pill-dot"></span><span>' + esc(sp.name) + '</span>';
                    list.appendChild(btn);
                });

                activeSpace = wanted;
                if (breadcrumbSpace) breadcrumbSpace.textContent = activeSpace;
                if (categorySpaceLabel) categorySpaceLabel.textContent = activeSpace;

                var spacePills = document.querySelectorAll('.cr-space-pill');
                spacePills.forEach(function (pill) {
                    pill.addEventListener('click', function () {
                        spacePills.forEach(function (p) { p.classList.remove('selected'); });
                        pill.classList.add('selected');

                        activeSpace = pill.getAttribute('data-space');
                        if (breadcrumbSpace) breadcrumbSpace.textContent = activeSpace;
                        if (categorySpaceLabel) categorySpaceLabel.textContent = activeSpace;

                        applySpaceState(activeSpace);
                    });
                });
            })();

            // Category clicks: multi-select + sets active context
            categoryCards.forEach(function (card) {
                card.addEventListener('click', function (e) {
                    if (e.target && e.target.closest && e.target.closest('.js-zoom-btn')) return;

                    var s = ensureSpaceState(activeSpace);
                    var cat = (card.getAttribute('data-category') || '').trim();
                    if (!cat) return;

                    var already = s.categories.indexOf(cat) !== -1;

                    if (already) {
                        s.categories = s.categories.filter(function (x) { return x !== cat; });
                        pruneCategory(activeSpace, cat);
                    } else {
                        s.categories.push(cat);
                    }

                    // set active to clicked category always (lets you view subcats even if you just deselected it)
                    s.activeCategory = cat;

                    renderCategories(activeSpace);
                    persist();
                    setActiveCategory(activeSpace, cat);
                });
            });

            // Subcategory clicks: multi-select under active category
            subcategoryCards.forEach(function (card) {
                card.addEventListener('click', function (e) {
                    if (e.target && e.target.closest && e.target.closest('.js-zoom-btn')) return;

                    var s = ensureSpaceState(activeSpace);
                    var activeCat = normalizeActiveCategory(s);
                    // honor current view category if it differs (user may be exploring unselected category via switch)
                    if (s.activeCategory && allCategoryKeys.indexOf(s.activeCategory) !== -1) activeCat = s.activeCategory;

                    var catOfCard = (card.getAttribute('data-parent-category') || '').trim();
                    if (catOfCard && catOfCard !== activeCat) return;

                    var subId = getSubId(card);
                    if (!subId) return;

                    toggleSubSelection(activeSpace, activeCat, subId);
                });
            });

            // Product selection + qty (scoped by active subcategory context)
            productCards.forEach(function (card) {
                var qtyInput = card.querySelector('.js-qty-input');
                var minusBtn = card.querySelector('.js-qty-minus');
                var plusBtn = card.querySelector('.js-qty-plus');

                function getActiveContext() {
                    var s = ensureSpaceState(activeSpace);
                    var activeCat = normalizeActiveCategory(s);
                    if (s.activeCategory && allCategoryKeys.indexOf(s.activeCategory) !== -1) activeCat = s.activeCategory;

                    var activeSub = s.activeSubByCat[activeCat] || resolveActiveSubId(activeSpace, activeCat);
                    return { s: s, activeCat: activeCat, activeSubId: activeSub };
                }

                card.addEventListener('click', function (e) {
                    if (
                        (e.target && e.target.closest && e.target.closest('.js-zoom-btn')) ||
                        (e.target && (e.target.classList.contains('js-qty-minus') ||
                            e.target.classList.contains('js-qty-plus') ||
                            e.target.classList.contains('js-qty-input')))
                    ) {
                        return;
                    }

                    var ctx = getActiveContext();
                    var activeSubId = ctx.activeSubId;
                    var cardSubId = (card.getAttribute('data-parent-subcategory-id') || '').trim();
                    if (cardSubId && cardSubId !== activeSubId) return;

                    if (!activeSubId || activeSubId === '—') return;

                    // ensure category + sub are selected when choosing products
                    if (ctx.s.categories.indexOf(ctx.activeCat) === -1) {
                        ctx.s.categories.push(ctx.activeCat);
                        renderCategories(activeSpace);
                    }
                    if (!ctx.s.selectedSubsByCat[ctx.activeCat]) ctx.s.selectedSubsByCat[ctx.activeCat] = [];
                    if (ctx.s.selectedSubsByCat[ctx.activeCat].indexOf(activeSubId) === -1) {
                        ctx.s.selectedSubsByCat[ctx.activeCat].push(activeSubId);
                    }
                    ctx.s.activeSubByCat[ctx.activeCat] = activeSubId;

                    if (!ctx.s.productsBySub[activeSubId] || typeof ctx.s.productsBySub[activeSubId] !== 'object') {
                        ctx.s.productsBySub[activeSubId] = {};
                    }

                    var pid = getProductId(card);
                    if (!pid) return;

                    var bucket = ctx.s.productsBySub[activeSubId];

                    if (bucket[pid]) {
                        delete bucket[pid];
                        card.classList.remove('selected');
                    } else {
                        if (qtyInput && (qtyInput.value === '0' || qtyInput.value.trim() === '')) qtyInput.value = '1';
                        bucket[pid] = getProductPayloadFromCard(card);
                        card.classList.add('selected');
                    }

                    renderSubcategories(activeSpace, ctx.activeCat);
                    renderProducts(activeSpace, ctx.activeCat, activeSubId);

                    persist();
                    updateLayerSelectionStatesForSpace(activeSpace);
                    updateTotalSelectedProducts();
                });

                if (minusBtn) {
                    minusBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        var ctx = getActiveContext();
                        var activeSubId = ctx.activeSubId;
                        var cardSubId = (card.getAttribute('data-parent-subcategory-id') || '').trim();
                        if (cardSubId && cardSubId !== activeSubId) return;

                        var current = parseInt(qtyInput.value || '0', 10);
                        if (current > 1) qtyInput.value = String(current - 1);

                        var pid = getProductId(card);
                        if (activeSubId !== '—' && ctx.s.productsBySub[activeSubId] && ctx.s.productsBySub[activeSubId][pid]) {
                            ctx.s.productsBySub[activeSubId][pid].qty = parseInt(qtyInput.value || '1', 10) || 1;
                            persist();
                        }
                    });
                }

                if (plusBtn) {
                    plusBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        var ctx = getActiveContext();
                        var activeSubId = ctx.activeSubId;
                        var cardSubId = (card.getAttribute('data-parent-subcategory-id') || '').trim();
                        if (cardSubId && cardSubId !== activeSubId) return;

                        var current = parseInt(qtyInput.value || '0', 10);
                        qtyInput.value = String((current || 0) + 1);

                        if (!activeSubId || activeSubId === '—') return;

                        // ensure category + sub are selected when using qty
                        if (ctx.s.categories.indexOf(ctx.activeCat) === -1) {
                            ctx.s.categories.push(ctx.activeCat);
                            renderCategories(activeSpace);
                        }
                        if (!ctx.s.selectedSubsByCat[ctx.activeCat]) ctx.s.selectedSubsByCat[ctx.activeCat] = [];
                        if (ctx.s.selectedSubsByCat[ctx.activeCat].indexOf(activeSubId) === -1) {
                            ctx.s.selectedSubsByCat[ctx.activeCat].push(activeSubId);
                        }
                        ctx.s.activeSubByCat[ctx.activeCat] = activeSubId;

                        if (!ctx.s.productsBySub[activeSubId] || typeof ctx.s.productsBySub[activeSubId] !== 'object') {
                            ctx.s.productsBySub[activeSubId] = {};
                        }

                        var pid = getProductId(card);
                        if (!pid) return;

                        if (!ctx.s.productsBySub[activeSubId][pid]) {
                            ctx.s.productsBySub[activeSubId][pid] = getProductPayloadFromCard(card);
                            card.classList.add('selected');
                        } else {
                            ctx.s.productsBySub[activeSubId][pid].qty = parseInt(qtyInput.value || '1', 10) || 1;
                        }

                        renderSubcategories(activeSpace, ctx.activeCat);
                        renderProducts(activeSpace, ctx.activeCat, activeSubId);

                        persist();
                        updateLayerSelectionStatesForSpace(activeSpace);
                        updateTotalSelectedProducts();
                    });
                }
            });

            /* Zoom opens only from zoom button */
            var zoomBackdrop = document.getElementById('zoomBackdrop');
            var zoomImg = document.getElementById('zoomImg');
            var zoomCloseBtn = document.getElementById('zoomCloseBtn');

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

            if (zoomCloseBtn) zoomCloseBtn.addEventListener('click', closeZoom);
            if (zoomBackdrop) {
                zoomBackdrop.addEventListener('click', function (e) {
                    if (e.target === zoomBackdrop) closeZoom();
                });
            }

            /* Summary Drawer */
            var viewSelectedBtn = document.getElementById('viewSelectedBtn');
            var summaryDrawer = document.getElementById('summaryDrawer');
            var summaryOverlay = document.getElementById('summaryOverlay');
            var summaryCloseBtn = document.getElementById('summaryCloseBtn');
            var summaryBody = document.getElementById('summaryBody');
            var downloadPdfBtn = document.getElementById('downloadPdfBtn');
            var printPdfBtn = document.getElementById('printPdfBtn');

            function listAllSubcategoriesForSpace(spaceName) {
                var s = ensureSpaceState(spaceName);
                var out = [];
                if (!s.selectedSubsByCat) return out;
                s.categories.forEach(function (cat) {
                    var arr = getSelectedSubsForCat(s, cat);
                    arr.forEach(function (sid) { out.push(sid); });
                });
                // unique
                var uniq = [];
                out.forEach(function (x) { if (uniq.indexOf(x) === -1) uniq.push(x); });
                return uniq;
            }

            function listAllProductsForSpace(spaceName) {
                var s = ensureSpaceState(spaceName);
                var out = [];
                if (!s.productsBySub) return out;
                Object.keys(s.productsBySub).forEach(function (subId) {
                    var bucket = s.productsBySub[subId];
                    if (!bucket || typeof bucket !== 'object') return;
                    Object.keys(bucket).forEach(function (pid) {
                        out.push(bucket[pid]);
                    });
                });
                return out;
            }

            function buildSummaryHTML() {
                var html = '';

                html += '<div class="cr-summary-section-title">Project Details (Step-1)</div>';
                html += '<div class="cr-summary-space" style="margin-top:0;">';
                html += '<div class="cr-summary-space-head">';
                html += '<div>';
                html += '<div class="cr-summary-space-title">' + esc(step1.regId) + '</div>';
                html += '<div class="cr-summary-space-meta">' + esc(step1.name) + '</div>';
                html += '</div>';
                html += '<div class="cr-summary-space-meta" style="text-align:right;">' + esc(step1.projectType) + ' → ' + esc(step1.projectSubType) + '</div>';
                html += '</div>';

                html += '<div style="margin-top:8px; font-size:0.8rem; color:var(--meta-soft); line-height:1.5;">';
                html += '<div><strong>Email:</strong> ' + esc(step1.email) + '</div>';
                html += '<div><strong>Contact:</strong> ' + esc(step1.phone) + '</div>';
                html += '<div><strong>Address:</strong> ' + esc(step1.address) + '</div>';
                html += '<div><strong>Budget:</strong> ' + esc(step1.budget) + '</div>';
                html += '<div><strong>Expected Delivery:</strong> ' + esc(step1.eta) + '</div>';
                html += '</div>';
                html += '</div>';

                html += '<div class="cr-summary-section-title">Selections (Step-2)</div>';

                var spaces = (step1.spaces && step1.spaces.length) ? step1.spaces : [{ name: activeSpace, qty: 1, sqft: 0 }];
                spaces.forEach(function (sp) {
                    var sName = sp.name;
                    var s = ensureSpaceState(sName);

                    html += '<div class="cr-summary-space">';
                    html += '<div class="cr-summary-space-head">';
                    html += '<div>';
                    html += '<div class="cr-summary-space-title">' + esc(sName) + '</div>';
                    html += '<div class="cr-summary-space-meta">Qty: ' + esc(sp.qty) + ' • Total SQFT: ' + esc(sp.sqft) + '</div>';
                    html += '</div>';
                    html += '<div class="cr-summary-space-meta">' + esc(step1.projectType) + '</div>';
                    html += '</div>';

                    html += '<div style="margin-top:10px;">';
                    html += '<div style="font-weight:700; font-size:0.8rem;">Categories</div>';
                    if (s.categories && s.categories.length) {
                        html += '<div class="cr-chip-row">';
                        s.categories.forEach(function (c) { html += '<span class="cr-chip accent">' + esc(c) + '</span>'; });
                        html += '</div>';
                    } else {
                        html += '<div class="cr-summary-space-meta">No category selected.</div>';
                    }
                    html += '</div>';

                    html += '<div style="margin-top:10px;">';
                    html += '<div style="font-weight:700; font-size:0.8rem;">Sub-Categories</div>';
                    var subs = listAllSubcategoriesForSpace(sName).map(getSubNameById);
                    if (subs.length) {
                        html += '<div class="cr-chip-row">';
                        subs.forEach(function (c) { html += '<span class="cr-chip">' + esc(c) + '</span>'; });
                        html += '</div>';
                    } else {
                        html += '<div class="cr-summary-space-meta">No sub-category selected.</div>';
                    }
                    html += '</div>';

                    html += '<div style="margin-top:10px;">';
                    html += '<div style="font-weight:700; font-size:0.8rem;">Products</div>';
                    var products = listAllProductsForSpace(sName);
                    if (products.length) {
                        html += '<div class="cr-summary-products">';
                        products.forEach(function (p) {
                            html += '<div class="cr-summary-product">';
                            html += '<img src="' + esc(p.thumb || p.image) + '" alt="' + esc(p.title) + '">';
                            html += '<div>';
                            html += '<div class="cr-summary-product-title">' + esc(p.title) + '</div>';
                            html += '<div class="cr-summary-product-meta">';
                            if (p.sku) html += '<div><strong>SKU:</strong> ' + esc(p.sku) + '</div>';
                            html += '<div><strong>Qty:</strong> ' + esc(p.qty) + '</div>';
                            if (p.origin) html += '<div><strong>Origin:</strong> ' + esc(p.origin) + '</div>';
                            html += '</div>';
                            html += '</div>';
                            html += '</div>';
                        });
                        html += '</div>';
                    } else {
                        html += '<div class="cr-summary-space-meta">No product selected.</div>';
                    }
                    html += '</div>';

                    html += '</div>';
                });

                html += '<div class="cr-summary-footer-note">';
                html += 'PDF is generated via A4 print layout. Use “Download PDF (A4)” → Save as PDF.';
                html += '</div>';

                return html;
            }

            function openSummary() {
                if (summaryBody) summaryBody.innerHTML = buildSummaryHTML();
                summaryDrawer.classList.add('is-open');
                summaryOverlay.classList.add('is-open');
            }

            function closeSummary() {
                summaryDrawer.classList.remove('is-open');
                summaryOverlay.classList.remove('is-open');
            }

            if (viewSelectedBtn) viewSelectedBtn.addEventListener('click', openSummary);
            if (summaryCloseBtn) summaryCloseBtn.addEventListener('click', closeSummary);
            if (summaryOverlay) summaryOverlay.addEventListener('click', closeSummary);

            function buildA4DocumentHTML() {
                var now = new Date();
                var genTime = now.toLocaleString();
                var spaces = (step1.spaces && step1.spaces.length) ? step1.spaces : [{ name: activeSpace, qty: 1, sqft: 0 }];

                // Client meta (Step-1 provides these; fallback-safe)
                var clientRegId = (step1.regId || step1.reg_id || '').toString();
                var clientUserId = (step1.clientUserId || step1.userId || step1.client_user_id || step1.user_id || '').toString();
                var clusterText = '';
                if (step1.cluster && ('' + step1.cluster).trim()) {
                    clusterText = '' + step1.cluster;
                } else {
                    var cid = (step1.clusterId || step1.cluster_id || '').toString();
                    var cname = (step1.clusterName || step1.cluster_name || '').toString();
                    if (cid && cname) clusterText = cid + ' - ' + cname;
                    else if (cid) clusterText = cid;
                    else if (cname) clusterText = cname;
                }


                function sectionLine(label, value) {
                    return '<div class="kv"><div class="k">' + esc(label) + '</div><div class="v">' + esc(value) + '</div></div>';
                }

                var html = '';
                html += '<!DOCTYPE html><html><head><meta charset="utf-8">';
                html += '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
                html += '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
                html += '<title>Requisition PDF</title>';
                html += '<style>';
                html += 'html,body{margin:0;padding:0;font-family:Poppins,system-ui,-apple-system,Segoe UI,sans-serif;color:#0f172a;}';
                html += '@page{size:A4; margin:14mm;}';
                html += '.page{width:210mm; min-height:297mm; padding:0; box-sizing:border-box;}';
                html += '.hdr{display:flex; justify-content:space-between; align-items:flex-start; gap:12px; border-bottom:1px solid #e5e7eb; padding-bottom:10px; margin-bottom:12px;}';
                html += '.brand{display:flex; gap:10px; align-items:center;}';
                html += '.mark{width:34px;height:34px;border-radius:50%;background:radial-gradient(circle at 20% 10%, #facc15, #f97316 40%, #0ea5e9 100%);}';
                html += '.h1{font-size:16px;font-weight:700;margin:0;}';
                html += '.sub{font-size:11px;color:#64748b;margin-top:2px;}';
                html += '.meta{font-size:11px;color:#64748b;text-align:right;}';
                html += '.card{border:1px solid #e5e7eb;border-radius:12px;padding:10px;margin-bottom:10px;}';
                html += '.title{font-size:12px;font-weight:700;margin:0 0 6px 0;}';
                html += '.grid2{display:grid;grid-template-columns:1fr 1fr;gap:10px;}';
                html += '.kv{display:flex;gap:8px;align-items:flex-start;font-size:11px;line-height:1.35;}';
                html += '.k{min-width:95px;color:#475569;font-weight:600;}';
                html += '.v{color:#0f172a;flex:1;}';
                html += '.chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;}';
                html += '.chip{font-size:10px;padding:3px 8px;border-radius:999px;border:1px solid #e5e7eb;background:#fff;}';
                html += '.chip.accent{border-color:rgba(234,88,12,.35);background:rgba(245,158,11,.12);}';
                html += '.space{page-break-inside:avoid;}';
                html += '.prod{display:grid;grid-template-columns:76px minmax(0,1fr);gap:10px;align-items:center;border:1px solid #e5e7eb;border-radius:12px;padding:8px;margin-top:8px;}';
                html += '.prod img{width:76px;height:56px;object-fit:cover;border-radius:10px;background:#e5e7eb;display:block;}';
                html += '.pname{font-size:11px;font-weight:700;margin:0;}';
                html += '.pmeta{font-size:10px;color:#475569;line-height:1.35;margin-top:2px;}';
                html += '.foot{margin-top:10px;font-size:10px;color:#64748b;border-top:1px dashed #e5e7eb;padding-top:8px;}';
                html += '@media print{.no-print{display:none !important;}}';
                html += '</style>';
                html += '</head><body><div class="page">';

                html += '<div class="hdr">';
                html += '<div class="brand"><div class="mark"></div><div><div class="h1">Client Requisition</div><div class="sub">Step-1 + Step-2 Summary (A4)</div></div></div>';
                html += '<div class="meta"><div><strong>Generated:</strong> ' + esc(genTime) + '</div><div><strong>Reg ID:</strong> ' + esc(clientRegId) + '</div>' + (clientUserId ? '<div><strong>User ID:</strong> ' + esc(clientUserId) + '</div>' : '') + (clusterText ? '<div><strong>Cluster:</strong> ' + esc(clusterText) + '</div>' : '') + '</div>';
                html += '</div>';

                html += '<div class="card">';
                html += '<div class="title">Project Details (Step-1)</div>';
                html += '<div class="grid2">';
                html += '<div>';
                html += sectionLine('Name', step1.name);
                html += sectionLine('Email', step1.email);
                html += sectionLine('Contact', step1.phone);
                if (clientUserId) html += sectionLine('User ID', clientUserId);
                if (clusterText) html += sectionLine('Cluster', clusterText);
                html += '</div>';
                html += '<div>';
                html += sectionLine('Type', step1.projectType);
                html += sectionLine('Sub-Type', step1.projectSubType);
                html += sectionLine('Expected', step1.eta);
                html += '</div>';
                html += '</div>';
                html += sectionLine('Address', step1.address);
                html += sectionLine('Budget', step1.budget);
                html += '</div>';

                html += '<div class="card">';
                html += '<div class="title">Selections (Step-2)</div>';

                spaces.forEach(function (sp) {
                    var sName = sp.name;
                    var s = ensureSpaceState(sName);

                    html += '<div class="card space" style="margin:10px 0 0 0;border-radius:12px;">';
                    html += '<div class="title" style="margin-bottom:4px;">' + esc(sName) + '</div>';
                    html += '<div class="sub" style="margin:0 0 6px 0;">Qty: ' + esc(sp.qty) + ' • Total SQFT: ' + esc(sp.sqft) + '</div>';

                    html += '<div style="font-size:11px;font-weight:700;margin-top:6px;">Categories</div>';
                    if (s.categories && s.categories.length) {
                        html += '<div class="chips">';
                        s.categories.forEach(function (c) { html += '<span class="chip accent">' + esc(c) + '</span>'; });
                        html += '</div>';
                    } else {
                        html += '<div class="sub">No category selected.</div>';
                    }

                    html += '<div style="font-size:11px;font-weight:700;margin-top:10px;">Sub-Categories</div>';
                    var subs = listAllSubcategoriesForSpace(sName).map(getSubNameById);
                    if (subs.length) {
                        html += '<div class="chips">';
                        subs.forEach(function (c) { html += '<span class="chip">' + esc(c) + '</span>'; });
                        html += '</div>';
                    } else {
                        html += '<div class="sub">No sub-category selected.</div>';
                    }

                    html += '<div style="font-size:11px;font-weight:700;margin-top:10px;">Products</div>';
                    var products = listAllProductsForSpace(sName);
                    if (products.length) {
                        products.forEach(function (p) {
                            html += '<div class="prod">';
                            html += '<img src="' + esc(p.thumb || p.image) + '" alt="' + esc(p.title) + '">';
                            html += '<div>';
                            html += '<p class="pname">' + esc(p.title) + '</p>';
                            html += '<div class="pmeta">';
                            if (p.sku) html += '<div><strong>SKU:</strong> ' + esc(p.sku) + '</div>';
                            html += '<div><strong>Qty:</strong> ' + esc(p.qty) + '</div>';
                            if (p.origin) html += '<div><strong>Origin:</strong> ' + esc(p.origin) + '</div>';
                            if (p.spec) html += '<div><strong>Spec:</strong> ' + esc(p.spec) + '</div>';
                            html += '</div>';
                            html += '</div>';
                            html += '</div>';
                        });
                    } else {
                        html += '<div class="sub">No product selected.</div>';
                    }

                    html += '</div>';
                });

                html += '<div class="foot">This PDF is produced from the client-side A4 print layout. Images included as selected.</div>';
                html += '</div>';

                html += '<div class="no-print" style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end;">';
                html += '<button id="crPrintBtn" style="border:none;border-radius:999px;padding:8px 14px;font-weight:600;background:linear-gradient(135deg,#f59e0b,#ea580c);cursor:pointer;">Print / Save as PDF</button>';
                html += '<button id="crCloseBtn" style="border:1px solid #e5e7eb;border-radius:999px;padding:8px 14px;background:#fff;cursor:pointer;">Close</button>';
                html += '</div>';

                html += '<script>';
                html += '(function(){';
                html += 'var p=document.getElementById("crPrintBtn");';
                html += 'var c=document.getElementById("crCloseBtn");';
                html += 'if(p){p.addEventListener("click",function(){try{window.focus();window.print();}catch(e){}});}';
                html += 'function hardClose(){try{window.close();}catch(e){} setTimeout(function(){try{window.open("","_self");}catch(e){} try{window.close();}catch(e){}},80);}';
                html += 'if(c){c.addEventListener("click",function(){hardClose();});}';
                html += 'document.addEventListener("keydown",function(e){if(e.key==="Escape"){hardClose();}});';
                html += '})();';
                html += '<\/script>';

                html += '</div></body></html>';
                return html;
            }

            function openA4PrintWindow(autoPrint) {
                var winName = 'cr_pdf_' + Date.now();
                var w = window.open('about:blank', winName);
                if (!w) return;

                var html = buildA4DocumentHTML();
                w.document.open();
                w.document.write(html);
                w.document.close();

                function tryPrint() {
                    if (!autoPrint) return;
                    setTimeout(function () {
                        try { w.focus(); w.print(); } catch (e) { }
                    }, 700);
                }

                try { w.onload = tryPrint; } catch (e) { }
                setTimeout(tryPrint, 1200);
            }

            if (downloadPdfBtn) {
                downloadPdfBtn.addEventListener('click', function () {
                    openA4PrintWindow(true);
                });
            }

            if (printPdfBtn) {
                printPdfBtn.addEventListener('click', function () {
                    openA4PrintWindow(true);
                });
            }

            /* Confirm/Success modal (kept) */
            var submitBtn = document.getElementById('submitRequisitionBtn');
            var confirmOverlay = document.getElementById('confirmOverlay');
            var confirmModal = document.getElementById('confirmModal');
            var confirmCloseBtn = document.getElementById('confirmCloseBtn');
            var confirmNoBtn = document.getElementById('confirmNoBtn');
            var confirmYesBtn = document.getElementById('confirmYesBtn');

            var successOverlay = document.getElementById('successOverlay');
            var successModal = document.getElementById('successModal');
            var successCloseBtn = document.getElementById('successCloseBtn');
            var successOkBtn = document.getElementById('successOkBtn');

            function openConfirm() {
                confirmOverlay.classList.add('is-open');
                confirmModal.style.display = 'block';
            }

            function closeConfirm() {
                confirmOverlay.classList.remove('is-open');
                confirmModal.style.display = 'none';
            }

            function openSuccess() {
                successOverlay.classList.add('is-open');
                successModal.style.display = 'block';
            }

            function closeSuccess() {
                successOverlay.classList.remove('is-open');
                successModal.style.display = 'none';
            }

            if (submitBtn) {
                submitBtn.addEventListener('click', function () {
                    persist();
                    openConfirm();
                });
            }

            if (confirmCloseBtn) confirmCloseBtn.addEventListener('click', closeConfirm);
            if (confirmNoBtn) confirmNoBtn.addEventListener('click', closeConfirm);
            if (confirmYesBtn) confirmYesBtn.addEventListener('click', function () {
                closeConfirm();
                openSuccess();
            });

            if (successCloseBtn) successCloseBtn.addEventListener('click', closeSuccess);
            if (successOkBtn) successOkBtn.addEventListener('click', function () {
                closeSuccess();
            });

            if (confirmOverlay) {
                confirmOverlay.addEventListener('click', function (e) {
                    if (e.target === confirmOverlay) closeConfirm();
                });
            }

            if (successOverlay) {
                successOverlay.addEventListener('click', function (e) {
                    if (e.target === successOverlay) closeSuccess();
                });
            }

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

                    if (leftBtn) {
                        leftBtn.addEventListener('click', function () {
                            viewport.scrollBy({ left: -viewport.clientWidth * 0.8, behavior: 'smooth' });
                        });
                    }

                    if (rightBtn) {
                        rightBtn.addEventListener('click', function () {
                            viewport.scrollBy({ left: viewport.clientWidth * 0.8, behavior: 'smooth' });
                        });
                    }

                    viewport.addEventListener('scroll', updateArrowState);
                    window.addEventListener('resize', updateArrowState);
                    updateArrowState();
                });
            }

            function applySpaceState(spaceName) {
                var s = ensureSpaceState(spaceName);

                renderCategories(spaceName);

                var activeCat = normalizeActiveCategory(s);
                // show what user last focused
                if (s.activeCategory && allCategoryKeys.indexOf(s.activeCategory) !== -1) activeCat = s.activeCategory;
                s.activeCategory = activeCat;

                renderSubcategories(spaceName, activeCat);

                var activeSubId = resolveActiveSubId(spaceName, activeCat);
                if (!s.activeSubByCat[activeCat]) s.activeSubByCat[activeCat] = activeSubId;

                renderProducts(spaceName, activeCat, s.activeSubByCat[activeCat]);

                if (breadcrumbCategory) breadcrumbCategory.textContent = activeCat || '—';
                if (subcategoryCategoryLabel) subcategoryCategoryLabel.textContent = activeCat || '—';

                updateLayerSelectionStatesForSpace(spaceName);
                updateTotalSelectedProducts();
                persist();
            }

            // First paint
            applySpaceState(activeSpace);
            initializeLayerSliders();

            // Close button (works best when opened as a popup/tab; otherwise falls back safely)
            var closePageBtn = document.getElementById('closePageBtn');
            function tryCloseWindow() {
                try { window.close(); } catch (e) { }
                // If the browser blocks window.close(), fallback:
                setTimeout(function () {
                    if (!document.hidden) {
                        if (history.length > 1) { history.back(); }
                        else { window.location.href = 'client-requisition-step1.html'; }
                    }
                }, 120);
            }
            if (closePageBtn) closePageBtn.addEventListener('click', tryCloseWindow);
        })();
    
</script>
@endpush
