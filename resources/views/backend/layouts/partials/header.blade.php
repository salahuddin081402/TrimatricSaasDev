{{-- resources/views/backend/layouts/partials/header.blade.php --}}
<header class="border-bottom bg-white">
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">

            {{-- Brand / Logo (left) — tenant if available, else platform --}}
            @php
                // From ViewServiceProvider:
                // - $headerCompany (or null)
                // - $isGuest, $headerUser, $menuTree
                $brandName = $headerCompany->name ?? 'ArchReach';
                $brandLogo = $headerCompany->logo ?? null; // e.g. assets/images/...
                // Brand link: keep context (tenant public vs global public)
                $brandHref = $headerCompany
                    ? route('backend.company.dashboard.public', ['company' => $headerCompany->slug])
                    : route('backend.dashboard.public');
            @endphp

            <a class="navbar-brand d-flex align-items-center gap-2" href="{{ $brandHref }}">
                @if($brandLogo)
                    <img src="{{ asset($brandLogo) }}" alt="Logo" width="36" height="36" class="rounded" style="object-fit:cover;" />
                @else
                    <img src="{{ asset('assets/images/trimatric_logo.png') }}" alt="Logo" width="36" height="36" class="rounded" />
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

                {{-- LEFT: Role-based menus (parents + children) --}}
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

                {{-- CENTER: Search (placeholder for now) --}}
                <form class="d-flex mx-lg-3 flex-grow-1 my-2 my-lg-0" role="search" method="GET" action="#">
                    <input class="form-control" type="search" name="q" placeholder="Search…" aria-label="Search">
                </form>

                {{-- RIGHT: Auth/Dev controls --}}
                <ul class="navbar-nav ms-lg-2 mb-2 mb-lg-0 align-items-lg-center">

                    {{-- Dev badge when forcing a user via config --}}
                    @php $forcedId = config('header.dev_force_user_id'); @endphp
                    @if($forcedId)
                        <li class="nav-item me-lg-2">
                            <span class="badge text-bg-warning" title="Dev forced user id">
                                User Id #{{ $forcedId }}
                            </span>
                        </li>
                    @endif

                    @if($isGuest)
                        {{-- Guest: show Register / Login enabled (placeholder routes for now), Logout disabled --}}
                        <li class="nav-item">
                            @if(Route::has('register'))
                                <a class="nav-link" href="{{ route('register') }}">
                                    <i class="fa-solid fa-user-plus me-1"></i> Register
                                </a>
                            @else
                                <a class="nav-link" href="#">
                                    <i class="fa-solid fa-user-plus me-1"></i> Register
                                </a>
                            @endif
                        </li>
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
                            <span class="nav-link disabled" title="Logout requires real auth">
                                <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
                            </span>
                        </li>
                    @else
                        {{-- Authenticated (or emulated): user dropdown --}}
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-circle-user"></i>
                                <span>{{ $headerUser->name ?? 'User' }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-id-badge me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fa-solid fa-gear me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>

                                {{-- Logout:
                                     - If forced user id is set → keep disabled (can’t truly log out).
                                     - Otherwise, if route exists, show a POST form. --}}
                                @if($forcedId)
                                    <li>
                                        <span class="dropdown-item disabled" title="Disabled in dev forced mode">
                                            <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
                                        </span>
                                    </li>
                                @else
                                    <li>
                                        @if(Route::has('logout'))
                                            <form method="POST" action="{{ route('logout') }}" class="px-3">
                                                @csrf
                                                <button class="btn btn-link p-0 text-start">
                                                    <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
                                                </button>
                                            </form>
                                        @else
                                            <a class="dropdown-item" href="#">
                                                <i class="fa-solid fa-right-from-bracket me-2"></i>Logout
                                            </a>
                                        @endif
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </nav>
</header>
