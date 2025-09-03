{{-- resources/views/backend/layouts/partials/header.blade.php --}}
<header class="border-bottom bg-white">
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">

            {{-- Brand / logo (tenant if available, else platform). Keep public context in the link. --}}
            @php
                $brandName = $headerCompany->name ?? 'ArchReach';
                $brandLogo = $headerCompany->logo ?? null;
                $brandHref = $headerCompany
                    ? route('backend.company.dashboard.public', ['company' => $headerCompany->slug])
                    : route('backend.dashboard.public');
            @endphp

            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ $brandHref }}">
                @if($brandLogo)
                    <img src="{{ asset($brandLogo) }}" alt="Logo" width="36" height="36" class="rounded" style="object-fit:cover;">
                @else
                    <img src="{{ asset('assets/images/trimatric_logo.png') }}" alt="Logo" width="36" height="36" class="rounded">
                @endif
                <span class="fw-semibold">{{ $brandName }}</span>
            </a>

            {{-- Mobile toggler --}}
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                    aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            {{-- Collapsible content --}}
            <div class="collapse navbar-collapse" id="mainNavbar">

                {{-- LEFT: role menus (none for guests; provider already returns empty). --}}
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    @foreach($menuTree as $node)
                        @php $hasChildren = !empty($node->children) && count($node->children) > 0; @endphp

                        @if($hasChildren)
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center gap-2"
                                   href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    @if(!empty($node->icon)) <i class="{{ $node->icon }}"></i> @endif
                                    <span>{{ $node->name }}</span>
                                </a>
                                <ul class="dropdown-menu">
                                    @foreach($node->children as $child)
                                        <li>
                                            <a class="dropdown-item d-flex align-items-center gap-2" href="{{ $child->url }}">
                                                @if(!empty($child->icon)) <i class="{{ $child->icon }}"></i> @endif
                                                <span>{{ $child->name }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @else
                            <li class="nav-item">
                                <a class="nav-link d-flex align-items-center gap-2" href="{{ $node->url }}">
                                    @if(!empty($node->icon)) <i class="{{ $node->icon }}"></i> @endif
                                    <span>{{ $node->name }}</span>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>

                {{-- CENTER: simple search placeholder --}}
                <form class="d-flex mx-lg-3 flex-grow-1 my-2 my-lg-0" role="search" method="GET" action="#">
                    <input class="form-control" type="search" name="q" placeholder="Search…" aria-label="Search">
                </form>

                {{-- RIGHT: registration buttons + auth menu --}}
                <ul class="navbar-nav ms-lg-2 mb-2 mb-lg-0 align-items-lg-center">

                    {{-- Dev badge when forcing user id --}}
                    @php $forcedId = config('header.dev_force_user_id'); @endphp
                    @if($forcedId)
                        <li class="nav-item me-lg-2">
                            <span class="badge text-bg-warning" title="Dev forced user id">User Id #{{ $forcedId }}</span>
                        </li>
                    @endif

                    {{-- Registration / Edit Registration (only one shows at a time) --}}
                    @if(!empty($ui->registerVisible) || !empty($ui->editRegVisible))
                        <li class="nav-item me-lg-1">
                            @if(!empty($ui->editRegVisible) && !empty($ui->editRegEnabled))
                                {{-- Edit Registration (enabled) --}}
                                @if(Route::has('registration.edit'))
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('registration.edit') }}">
                                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit Registration
                                    </a>
                                @else
                                    <a class="btn btn-sm btn-outline-primary" href="#" title="Edit registration">
                                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit Registration
                                    </a>
                                @endif
                            @elseif(!empty($ui->registerVisible) && !empty($ui->registerEnabled))
                                {{-- Register (enabled) --}}
                                @if(Route::has('registration.start'))
                                    <a class="btn btn-sm btn-primary" href="{{ route('registration.start') }}">
                                        <i class="fa-solid fa-user-plus me-1"></i> Register
                                    </a>
                                @elseif(Route::has('registration.create'))
                                    <a class="btn btn-sm btn-primary" href="{{ route('registration.create') }}">
                                        <i class="fa-solid fa-user-plus me-1"></i> Register
                                    </a>
                                @else
                                    <a class="btn btn-sm btn-primary" href="#" title="Start registration">
                                        <i class="fa-solid fa-user-plus me-1"></i> Register
                                    </a>
                                @endif
                            @elseif(!empty($ui->registerVisible) && empty($ui->registerEnabled))
                                {{-- Register (visible but disabled for guests) --}}
                                <span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true" tabindex="-1"
                                      title="Please login to register">
                                    <i class="fa-solid fa-user-plus me-1"></i> Register
                                </span>
                            @endif
                        </li>
                    @endif

                    {{-- Guest auth links (Login enabled, Logout disabled) --}}
                    @if(!empty($ui->loginVisible) && $ui->loginEnabled && $isGuest)
                        <li class="nav-item">
                            @if(Route::has('login'))
                                <a class="nav-link" href="{{ route('login') }}">
                                    <i class="fa-solid fa-right-to-bracket me-1"></i> Login
                                </a>
                            @else
                                <a class="nav-link" href="#">
                                    <i class="fa-solid fa-right-to-bracket me-1"></i> Login
                                </a>
                            @endif
                        </li>
                        <li class="nav-item">
                            <span class="nav-link disabled" aria-disabled="true" tabindex="-1" title="Logout requires auth">
                                <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                            </span>
                        </li>
                    @endif

                    {{-- Authenticated (or forced) user dropdown --}}
                    @if(!$isGuest)
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-circle-user"></i>
                                <span>{{ $headerUser->name ?? 'User' }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-id-badge me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-gear me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>

                                {{-- Show Login in dropdown but disabled when authenticated --}}
                                @if(!empty($ui->loginVisible) && empty($ui->loginEnabled))
                                    <li>
                                        <span class="dropdown-item disabled" aria-disabled="true" tabindex="-1">
                                            <i class="fa-solid fa-right-to-bracket me-2"></i>Login
                                        </span>
                                    </li>
                                @endif

                                {{-- Logout (enabled for forced or real auth per requirement) --}}
                                @if(!empty($ui->logoutVisible) && !empty($ui->logoutEnabled))
                                    <li>
                                        @if(Route::has('logout'))
                                            <form method="POST" action="{{ route('logout') }}" class="px-3">
                                                @csrf
                                                <button type="submit" class="btn btn-link p-0 text-start">
                                                    <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
                                                </button>
                                            </form>
                                        @else
                                            <a class="dropdown-item" href="#">
                                                <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
                                            </a>
                                        @endif
                                    </li>
                                @else
                                    <li>
                                        <span class="dropdown-item disabled" aria-disabled="true" tabindex="-1">
                                            <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
                                        </span>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    {{-- Simple toast (Bootstrap 5). 
        Green for: 
        • Guest "Welcome" message, and 
        • Authenticated but NOT registered ("Pls, Register...") message.
        Logic: use $isGuest OR the provider’s UI flags (registerVisible = true).
    --}}
    @if(!empty($ui->toastMessage))
        @php
            // Green when guest, or when registration is required
            $makeGreen = $isGuest || (!empty($ui->registerVisible));
        @endphp
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 1080;">
            <div class="toast show {{ $makeGreen ? 'text-bg-success' : '' }}" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-body">
                    {{ $ui->toastMessage }}
                </div>
            </div>
        </div>
    @endif


</header>
