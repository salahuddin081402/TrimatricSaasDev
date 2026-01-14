{{-- resources/views/backend/layouts/partials/header.blade.php --}}
<header class="bg-white border-bottom">

    @php
        $brandName = $headerCompany->name ?? 'ArchReach';
        $brandLogo = $headerCompany->logo ?? null;
        $brandHref = $headerCompany
            ? route('backend.company.dashboard.public', ['company' => $headerCompany->slug])
            : route('backend.dashboard.public');

        $companyParam = $headerCompany->slug ?? ($headerCompany->id ?? null);
    @endphp

    {{-- Premium hover + button polish (UI only; no behavior changes) --}}
    <style>
        /* ===========================
           HEADER SAFETY RESET (scoped)
           Prevent page-level CSS (html font-size, global * rules) from shrinking header UI
           =========================== */
        header {
            font-size: 16px !important;
            line-height: 1.5 !important;
            font-family: var(--bs-body-font-family, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif) !important;
        }

        header *, header *::before, header *::after {
            text-transform: none !important;
            letter-spacing: normal !important;
        }

        /* Restore “normal Bootstrap-like” sizes using PX (not REM) so root html size can't shrink them */
        header .navbar-brand,
        header .navbar .nav-link,
        header .navbar .navbar-text {
            font-size: 16px !important;
        }

        header .btn,
        header .dropdown-item {
            font-size: 14px !important;
        }

        header .badge {
            font-size: 12px !important;
        }

        header .small {
            font-size: 14px !important;
        }

        header .navbar-nav {
            line-height: 1.25 !important;
        }

        /* ===== Gray theme + Accent ===== */
        header{
            --tmx-accent: #00A3FF;
            --tmx-bg: #F3F4F6;
            --tmx-bg-2: #EEF0F3;
            --tmx-text: #111827;
            --tmx-border: rgba(17,24,39,.14);
        }

        /* Make both navbars + header gray */
        header{
            background: var(--tmx-bg) !important;
            border-bottom-color: var(--tmx-border) !important;
        }
        header .navbar{
            background: var(--tmx-bg) !important;
        }
        header .navbar.border-top,
        header .navbar.border-bottom{
            border-color: var(--tmx-border) !important;
        }

        /* Brand + general text on gray */
        header .navbar-brand,
        header .text-dark,
        header .navbar .nav-link{
            color: var(--tmx-text) !important;
        }

        /* Toggler cosmetics for gray */
        header .navbar-toggler{
            border-color: rgba(17,24,39,.20) !important;
        }

        /* ===== Links ===== */
        header .tmx-navlink {
            border-radius: 999px;
            padding: .45rem .8rem;
            transition: color .15s ease, background-color .15s ease, box-shadow .15s ease, transform .15s ease;
        }
        header .tmx-navlink:hover,
        header .tmx-navlink:focus {
            color: var(--tmx-accent) !important;
            background: rgba(0,163,255,.10);
            box-shadow: 0 6px 18px rgba(17,24,39,.10);
            text-decoration: none;
        }

        header .tmx-menu-link {
            border-radius: 12px;
            padding: .5rem .75rem;
            transition: color .15s ease, background-color .15s ease, box-shadow .15s ease, transform .15s ease;
        }
        header .tmx-menu-link:hover,
        header .tmx-menu-link:focus {
            color: var(--tmx-accent) !important;
            background: rgba(0,163,255,.10);
            box-shadow: 0 6px 18px rgba(17,24,39,.10);
            text-decoration: none;
        }

        /* Dropdown */
        header .dropdown-menu{
            border-color: var(--tmx-border) !important;
            box-shadow: 0 18px 40px rgba(17,24,39,.14) !important;
        }
        header .dropdown-menu .dropdown-item {
            border-radius: 10px;
            transition: color .15s ease, background-color .15s ease;
        }
        header .dropdown-menu .dropdown-item:hover,
        header .dropdown-menu .dropdown-item:focus {
            color: var(--tmx-accent) !important;
            background: rgba(0,163,255,.12);
        }

        /* ===== Buttons ===== */
        header .tmx-btn {
            border-radius: 999px;
            transition: color .15s ease, background-color .15s ease, border-color .15s ease, box-shadow .15s ease, transform .15s ease;
        }

        header .tmx-btn-dark {
            background: linear-gradient(180deg, #111827 0%, #0B1220 100%);
            border-color: #0B1220;
            color: #fff;
            box-shadow: 0 10px 22px rgba(17,24,39,.18);
        }
        header .tmx-btn-dark:hover,
        header .tmx-btn-dark:focus {
            color: var(--tmx-accent) !important;
            box-shadow: 0 14px 28px rgba(17,24,39,.22);
            transform: translateY(-1px);
        }

        header .tmx-btn-outline {
            background: rgba(255,255,255,.35);
            border-color: rgba(17,24,39,.45);
            color: var(--tmx-text);
            box-shadow: 0 10px 22px rgba(17,24,39,.10);
            backdrop-filter: blur(2px);
        }
        header .tmx-btn-outline:hover,
        header .tmx-btn-outline:focus {
            background: #111827;
            border-color: #111827;
            color: var(--tmx-accent) !important;
            box-shadow: 0 14px 28px rgba(17,24,39,.18);
            transform: translateY(-1px);
        }

        header .tmx-btn:active {
            transform: translateY(0);
            box-shadow: none;
        }

        /* Menu icon pill */
        header .tmx-menu-pill{
            background: var(--tmx-bg-2);
            border-color: rgba(17,24,39,.18) !important;
            color: var(--tmx-text);
        }

        /* ===========================
           HEADER BUTTON HEIGHT FIX
           Keep auth buttons compact & Bootstrap-like
           =========================== */
        header .btn,
        header .tmx-btn {
            padding: 0.375rem 0.75rem !important;
            line-height: 1.25 !important;
            min-height: 32px !important;
        }

        header .btn-sm {
            padding: 0.25rem 0.6rem !important;
            min-height: 30px !important;
        }

        header .btn i {
            line-height: 1 !important;
        }

        /* Fix badge alignment near buttons */
        header .badge {
            padding: 0.35em 0.6em !important;
            line-height: 1 !important;
            display: inline-flex !important;
            align-items: center !important;
            vertical-align: middle !important;
        }

        /* ===========================
           FIX: TRUE SINGLE-LINE HORIZONTAL ALIGNMENT (TOP RIGHT)
           Root cause: baseline differences between <a>, <button>, <form>, <span>, .btn-group inside <li>.
           Solution: make the RIGHT navbar a flex row and force every direct child to center-align.
           No behavior changes.
           =========================== */
        header #topNavbar > ul.navbar-nav.ms-lg-3 {
            display: flex !important;
            flex-direction: row !important;
            flex-wrap: nowrap !important;
            align-items: center !important;
        }

        header #topNavbar > ul.navbar-nav.ms-lg-3 > li.nav-item {
            display: flex !important;
            align-items: center !important;
            margin: 0 !important;
        }

        /* Any wrapper inside nav-item (form, btn-group, div) must not create baseline shift */
        header #topNavbar > ul.navbar-nav.ms-lg-3 > li.nav-item > * {
            display: inline-flex !important;
            align-items: center !important;
        }

        header #topNavbar > ul.navbar-nav.ms-lg-3 form {
            display: inline-flex !important;
            align-items: center !important;
            margin: 0 !important;
        }

        header #topNavbar > ul.navbar-nav.ms-lg-3 .btn-group {
            display: inline-flex !important;
            align-items: center !important;
        }

        /* Prevent any top/bottom margins from causing “up/down” illusion */
        header #topNavbar > ul.navbar-nav.ms-lg-3 .btn,
        header #topNavbar > ul.navbar-nav.ms-lg-3 .nav-link,
        header #topNavbar > ul.navbar-nav.ms-lg-3 .badge,
        header #topNavbar > ul.navbar-nav.ms-lg-3 .small {
            margin-top: 0 !important;
            margin-bottom: 0 !important;
        }

        /* Ensure icon alignment inside buttons/links */
        header #topNavbar > ul.navbar-nav.ms-lg-3 i,
        header #topNavbar > ul.navbar-nav.ms-lg-3 svg {
            line-height: 1 !important;
            vertical-align: middle !important;
        }
    </style>

    {{-- =========================
        TOP BAR (Brand + Static Links + Auth Actions)
    ========================== --}}
    <nav class="navbar navbar-expand-lg py-2">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ $brandHref }}">
                @if($brandLogo)
                    <img src="{{ asset($brandLogo) }}" alt="Logo" width="36" height="36" class="rounded" style="object-fit:cover;">
                @else
                    <img src="{{ asset('assets/images/trimatric_logo.png') }}" alt="Logo" width="36" height="36" class="rounded">
                @endif
                <span class="fw-semibold">{{ $brandName }}</span>
            </a>

            {{-- Mobile toggler for TOP links --}}
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavbar"
                    aria-controls="topNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="topNavbar">
                {{-- Left: Static top links (placeholders; you will set routes later) --}}
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-lg-2">
                    <li class="nav-item">
                        <a class="nav-link tmx-navlink" href="#" data-route-placeholder="home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link tmx-navlink" href="#" data-route-placeholder="about">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link tmx-navlink" href="#" data-route-placeholder="services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link tmx-navlink" href="#" data-route-placeholder="contact">Contact Us</a>
                    </li>
                </ul>

                <ul class="navbar-nav ms-lg-3 mb-2 mb-lg-0 align-items-lg-center gap-2">

                    @php $forcedId = config('header.dev_force_user_id'); @endphp
                    @if($forcedId)
                        <li class="nav-item">
                            <span class="badge text-bg-warning" title="Dev forced user id">User Id #{{ $forcedId }}</span>
                        </li>
                    @endif

                    {{-- === Registration actions (copied from public dashboard logic) === --}}
                    @php
                        use Illuminate\Support\Facades\Auth;
                        use Illuminate\Support\Facades\DB;
                        use Illuminate\Support\Facades\Route as Rt;
                        use Illuminate\Support\Facades\Schema;

                        $hdrForced = config('header.dev_force_user_id');
                        $hdrUid    = Auth::id() ?? (is_numeric($hdrForced) ? (int)$hdrForced : null);
                        $hdrIsGuest = ($hdrUid === null);
                        $isGuest = isset($isGuest) ? (bool)$isGuest : $hdrIsGuest;

                        // Resolve company id from $companyParam (slug or id)
                        $companyId = null;
                        if (!empty($companyParam)) {
                            $companyId = is_numeric($companyParam)
                                ? (int)$companyParam
                                : (DB::table('companies')->where('slug', $companyParam)->value('id') ?: null);
                        }

                        // Mirror public dashboard decision
                        $canEditClient        = false;
                        $canEditOfficer       = false;
                        $canEditProfessional  = false;
                        $canEditEntrepreneur  = false;
                        $canEditEnterprise    = false;

                        if (!$isGuest && $companyId && Schema::hasTable('registration_master')) {
                            $user = DB::table('users')->where('id', $hdrUid)->first();

                            $userCompanyId = 0;
                            if ($user) {
                                $userCompanyId = (int) ($user->company_id ?? 0);
                                if (!$userCompanyId && !empty($user->role_id)) {
                                    $userCompanyId = (int) DB::table('roles')->where('id', $user->role_id)->value('company_id');
                                }
                            }

                            $userActive    = $user && (int)($user->status ?? 0) === 1;
                            $inThisCompany = $userActive && $userCompanyId === (int)$companyId;

                            if ($inThisCompany) {
                                $base = DB::table('registration_master')
                                    ->where('user_id', $hdrUid)
                                    ->where('company_id', $companyId)
                                    ->where(function($q){ $q->whereNull('approval_status')->orWhere('approval_status','<>','declined'); });

                                $canEditClient       = (clone $base)->where('registration_type','client')->exists();
                                $canEditOfficer      = (clone $base)->where('registration_type','company_officer')->exists();
                                $canEditProfessional = (clone $base)->where('registration_type','professional')->exists();
                                $canEditEntrepreneur = (clone $base)->where('registration_type','entrepreneur')->exists();
                                $canEditEnterprise   = (clone $base)->where('registration_type','enterprise_client')->exists();
                            }
                        }

                        $hasRegistration = ($canEditClient || $canEditOfficer || $canEditProfessional || $canEditEntrepreneur || $canEditEnterprise);
                        $loginUrl = ($companyParam && Rt::has('company.login'))
                                    ? route('company.login', ['company' => $companyParam])
                                    : '#';

                        $editClientUrl        = $companyParam ? route('registration.client.edit', ['company'=>$companyParam]) : '#';
                        $editOfficerUrl       = $companyParam ? route('registration.company_officer.edit', ['company'=>$companyParam]) : '#';
                        $editProfessionalUrl  = $companyParam ? route('registration.professional.edit', ['company'=>$companyParam]) : '#';
                        $editEntrepreneurUrl  = $companyParam ? route('registration.entrepreneur.edit_entry.go', ['company'=>$companyParam]) : '#';
                        $editEnterpriseUrl    = $companyParam ? route('registration.enterprise_client.edit_entry.go', ['company' => $companyParam]): '#';

                        $regClientUrl        = $companyParam ? route('registration.client.create', ['company'=>$companyParam]) : '#';
                        $regProfessionalUrl  = $companyParam ? route('registration.professional.create', ['company'=>$companyParam]) : '#';
                        $regEntrepreneurUrl  = $companyParam ? route('registration.entrepreneur.step1.create', ['company'=>$companyParam]) : '#';
                        $regEnterpriseUrl    = $companyParam ? route('registration.enterprise_client.step1.create', ['company'=>$companyParam]) : '#';

                        // Keep UI switches
                        $showRegisterBtn = !empty($ui->registerVisible);
                        $registerEnabled = !empty($ui->registerEnabled);
                    @endphp

                    @if($isGuest)
                        @if(!empty($ui->loginVisible))
                            @if(!empty($ui->loginEnabled))
                                <li class="nav-item">
                                    <a class="btn btn-sm tmx-btn tmx-btn-dark px-3" href="{{ $loginUrl }}">
                                        <i class="fa-solid fa-right-to-bracket me-1"></i> Login
                                    </a>
                                </li>
                            @else
                                <li class="nav-item">
                                    <span class="btn btn-sm tmx-btn tmx-btn-outline px-3 disabled" aria-disabled="true" tabindex="-1">
                                        <i class="fa-solid fa-right-to-bracket me-1"></i> Login
                                    </span>
                                </li>
                            @endif
                        @endif

                        @if($showRegisterBtn && !$registerEnabled)
                            <li class="nav-item">
                                <span class="btn btn-sm tmx-btn tmx-btn-outline disabled px-3" aria-disabled="true" tabindex="-1" title="Please login to register">
                                    <i class="fa-solid fa-user-plus me-1"></i> Register
                                </span>
                            </li>
                        @endif
                    @else
                        {{-- Register / Edit Registration buttons (no logic change) --}}
                        @if(!$hasRegistration && $showRegisterBtn)
                            <li class="nav-item d-flex gap-2">
                                @if($registerEnabled)
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm tmx-btn tmx-btn-dark dropdown-toggle px-3" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa-solid fa-user-plus me-1"></i> Register
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border">
                                            <li>
                                                <a class="dropdown-item" href="{{ $regClientUrl }}">
                                                    <i class="fa-solid fa-user-check me-2"></i> Client
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ $regEnterpriseUrl }}">
                                                    <i class="fa-solid fa-user-check me-2"></i> Enterprise Client
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ $regEntrepreneurUrl }}">
                                                    <i class="fa-solid fa-user-check me-2"></i> Entrepreneur
                                                </a>
                                            </li>
                                            <li>
                                                {{-- Company Officer triggers Reg-Key modal (handled by registration-co-key.js) --}}
                                                <a class="dropdown-item"
                                                   href="#"
                                                   data-co-trigger="1"
                                                   data-company="{{ $companyParam }}">
                                                    <i class="fa-solid fa-user-tie me-2"></i> Company Officer
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ $regProfessionalUrl }}">
                                                    <i class="fa-solid fa-briefcase me-2"></i> Professional
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                @else
                                    <span class="btn btn-sm tmx-btn tmx-btn-outline disabled px-3" aria-disabled="true" tabindex="-1" title="Please login to register">
                                        <i class="fa-solid fa-user-plus me-1"></i> Register
                                    </span>
                                @endif
                            </li>
                        @elseif($hasRegistration)
                            <li class="nav-item d-flex flex-wrap gap-2">
                                @if($canEditClient)
                                    <a class="btn btn-sm tmx-btn tmx-btn-outline px-3" href="{{ $editClientUrl }}">
                                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit Registration
                                    </a>
                                @endif
                                @if($canEditEnterprise)
                                    <a class="btn btn-sm tmx-btn tmx-btn-outline px-3" href="{{ $editEnterpriseUrl }}">
                                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit Registration
                                    </a>
                                @endif
                                @if($canEditEntrepreneur)
                                    <a class="btn btn-sm tmx-btn tmx-btn-outline px-3" href="{{ $editEntrepreneurUrl }}">
                                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit Registration
                                    </a>
                                @endif
                                @if($canEditOfficer)
                                    <a class="btn btn-sm tmx-btn tmx-btn-outline px-3" href="{{ $editOfficerUrl }}">
                                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit Registration
                                    </a>
                                @endif
                                @if($canEditProfessional)
                                    <a class="btn btn-sm tmx-btn tmx-btn-outline px-3" href="{{ $editProfessionalUrl }}">
                                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit Registration
                                    </a>
                                @endif
                            </li>
                        @endif

                        {{-- Auth identity + Logout (no dropdown, no profile/settings) --}}
                        <li class="nav-item d-none d-lg-flex align-items-center ms-lg-1">
                            <span class="small opacity-75" style="color:var(--tmx-text) !important;">
                                <i class="fa-solid fa-circle-user me-1"></i> {{ $headerUser->name ?? 'User' }}
                            </span>
                        </li>

                        @if(!empty($ui->logoutVisible))
                            <li class="nav-item">
                                @if(!empty($ui->logoutEnabled))
                                    @if(Route::has('logout'))
                                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                                            @csrf
                                            <button type="submit" class="btn btn-sm tmx-btn tmx-btn-outline px-3">
                                                <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                                            </button>
                                        </form>
                                    @else
                                        <a class="btn btn-sm tmx-btn tmx-btn-outline px-3" href="#">
                                            <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                                        </a>
                                    @endif
                                @else
                                    <span class="btn btn-sm tmx-btn tmx-btn-outline px-3 disabled" aria-disabled="true" tabindex="-1">
                                        <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                                    </span>
                                @endif
                            </li>
                        @endif
                    @endif
                    {{-- === /Registration actions === --}}
                </ul>
            </div>
        </div>
    </nav>

    {{-- =========================
        SECOND NAV (Dynamic Menu Tree)
        Separate modern navbar with hamburger for small devices
    ========================== --}}
    <nav class="navbar navbar-expand-lg border-top border-bottom py-2">
        <div class="container-fluid">
            <span class="small fw-semibold d-flex align-items-center gap-2" style="color:var(--tmx-text) !important;">
                <span class="tmx-menu-pill rounded-circle d-inline-flex align-items-center justify-content-center border"
                      style="width:30px;height:30px;">
                    <i class="fa-solid fa-bars"></i>
                </span>
                <span class="d-none d-md-inline">Menu</span>
            </span>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuNavbar"
                    aria-controls="menuNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menuNavbar">
                <ul class="navbar-nav ms-lg-3 me-auto mb-2 mb-lg-0 gap-lg-2">

                    @foreach($menuTree as $node)
                        @php $hasChildren = !empty($node->children) && count($node->children) > 0; @endphp

                        @if($hasChildren)
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 px-lg-2 tmx-menu-link"
                                   href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    @if(!empty($node->icon)) <i class="{{ $node->icon }}"></i> @endif
                                    <span class="fw-medium">{{ $node->name }}</span>
                                </a>
                                <ul class="dropdown-menu shadow-sm border">
                                    @foreach($node->children as $child)
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ $child->url }}">
                                                @if(!empty($child->icon)) <i class="{{ $child->icon }}"></i> @endif
                                                <span>{{ $child->name }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @else
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2 px-lg-2 tmx-menu-link"
                                   href="{{ $node->url }}">
                                    @if(!empty($node->icon)) <i class="{{ $node->icon }}"></i> @endif
                                    <span class="fw-medium">{{ $node->name }}</span>
                                </a>
                            </li>
                        @endif
                    @endforeach

                </ul>

                {{-- Quick Action removed (UI only) --}}
            </div>
        </div>
    </nav>

    @if(!empty($ui->toastMessage))
        @php $makeGreen = $isGuest || (!empty($ui->registerVisible)); @endphp
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 1080;">
            <div class="toast {{ $makeGreen ? 'text-bg-success' : '' }}"
                 role="alert" aria-live="assertive" aria-atomic="true"
                 data-bs-delay="3500" data-bs-autohide="true">
                <div class="toast-body">
                    {{ $ui->toastMessage }}
                </div>
            </div>
        </div>
    @endif
</header>

{{-- Reg-Key modal for Company Officer (used by registration-co-key.js) --}}
@include('backend.layouts.partials.reg-key-modal')
