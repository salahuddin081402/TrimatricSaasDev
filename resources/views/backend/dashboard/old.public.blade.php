{{-- resources/views/backend/dashboard/public.blade.php --}}
@extends('backend.layouts.master')

@section('content')
@php
    $brandName   = 'ArchReach';
    $companyId   = null;
    $companySlug = null;
    $param       = request()->route('company');

    if ($param instanceof \App\Models\SuperAdmin\GlobalSetup\Company) {
        $brandName = $param->name; $companyId = $param->id; $companySlug = $param->slug;
    } elseif (is_string($param) && $param !== '') {
        $row = \Illuminate\Support\Facades\DB::table('companies')
            ->where('slug', $param)->where('status', 1)->whereNull('deleted_at')->first();
        if ($row) { $brandName = $row->name; $companyId = $row->id; $companySlug = $row->slug; }
    }

    // Auth or forced user
    $forcedId = config('header.dev_force_user_id');
    $forcedId = is_numeric($forcedId) ? (int) $forcedId : null;
    $uid      = \Illuminate\Support\Facades\Auth::id() ?? $forcedId;
    $isGuest  = ($uid === null);

    // Defaults
    $canEditClient        = false;
    $canEditOfficer       = false;
    $canEditProfessional  = false;
    $canEditEntrepreneur  = false;
    $canEditEnterprise    = false;


    if (!$isGuest && $companyId) {
        $user = \Illuminate\Support\Facades\DB::table('users')->where('id', $uid)->first();

        // Resolve user's company
        $userCompanyId = null;
        if ($user) {
            $userCompanyId = (int) ($user->company_id ?? 0);
            if (!$userCompanyId && !empty($user->role_id)) {
                $userCompanyId = (int) \Illuminate\Support\Facades\DB::table('roles')
                    ->where('id', $user->role_id)->value('company_id');
            }
        }

        $userActive       = $user && (int)($user->status ?? 0) === 1;
        $inThisCompany    = $userActive && $userCompanyId === (int)$companyId;

        if ($inThisCompany && \Illuminate\Support\Facades\Schema::hasTable('registration_master')) {
            // Client reg exists and not declined
            $hasClient = \Illuminate\Support\Facades\DB::table('registration_master')
                ->where('user_id', $uid)
                ->where('company_id', $companyId)
                ->where('registration_type', 'client')
                ->where(function($q){ $q->whereNull('approval_status')->orWhere('approval_status', '<>', 'declined'); })
                ->exists();

            // Company officer reg exists and not declined
            $hasOfficer = \Illuminate\Support\Facades\DB::table('registration_master')
                ->where('user_id', $uid)
                ->where('company_id', $companyId)
                ->where('registration_type', 'company_officer')
                ->where(function($q){ $q->whereNull('approval_status')->orWhere('approval_status', '<>', 'declined'); })
                ->exists();

            // Professional reg exists and not declined
            $hasProfessional = \Illuminate\Support\Facades\DB::table('registration_master')
                ->where('user_id', $uid)
                ->where('company_id', $companyId)
                ->where('registration_type', 'professional')
                ->where(function($q){ $q->whereNull('approval_status')->orWhere('approval_status', '<>', 'declined'); })
                ->exists();

            // Entrepreneur reg exists and not declined
            $hasEntrepreneur = \Illuminate\Support\Facades\DB::table('registration_master')
                ->where('user_id', $uid)
                ->where('company_id', $companyId)
                ->where('registration_type', 'entrepreneur')
                ->where(function($q){ $q->whereNull('approval_status')->orWhere('approval_status', '<>', 'declined'); })
                ->exists();

            // Enterprise reg exists and not declined
            $hasEnterprise = \Illuminate\Support\Facades\DB::table('registration_master')
                ->where('user_id', $uid)
                ->where('company_id', $companyId)
                ->where('registration_type', 'enterprise_client')
                ->where(function($q){ $q->whereNull('approval_status')->orWhere('approval_status', '<>', 'declined'); })
                ->exists();

            $canEditClient       = $hasClient;
            $canEditOfficer      = $hasOfficer;
            $canEditProfessional = $hasProfessional;
            $canEditEntrepreneur = $hasEntrepreneur;
            $canEditEnterprise   = $hasEnterprise;
        }
    }

    // Derive "hasRegistration" for existing UI logic
    $hasRegistration = ($canEditClient || $canEditOfficer || $canEditProfessional ||  $canEditEntrepreneur ||  $canEditEnterprise);

    $companyParam = $companySlug ?? $companyId;

    $regUrl = function (string $type) use ($companyParam) {
        switch ($type) {
            case 'client':
                return $companyParam ? route('registration.client.create', ['company'=>$companyParam]) : '#';
            case 'professional':
                return $companyParam ? route('registration.professional.create', ['company'=>$companyParam]) : '#';
            case 'entrepreneur':
                return $companyParam ? route('registration.entrepreneur.step1.create', ['company'=>$companyParam]) : '#';  
            case 'enterprise_client':
                return $companyParam ? route('registration.enterprise_client.step1.create', ['company'=>$companyParam]) : '#';  
            case 'company_officer':
            default:
                return '#'; // JS handles company_officer key modal
        }
    };

    $editClientUrl       = $companyParam ? route('registration.client.edit', ['company'=>$companyParam]) : '#';
    $editOfficerUrl      = $companyParam ? route('registration.company_officer.edit', ['company'=>$companyParam]) : '#';
    $editProfessionalUrl = $companyParam ? route('registration.professional.edit', ['company'=>$companyParam]) : '#';
    $editEntrepreneurUrl = $companyParam ? route('registration.entrepreneur.edit_entry.go', ['company'=>$companyParam]) : '#';
    $editEnterpriseUrl   = $companyParam ? route('registration.enterprise_client.edit_entry.go', ['company' => $companyParam]): '#';

    $loginUrl = \Illuminate\Support\Facades\Route::has('company.login') ? route('company.login',['company'=>$companyParam]) : '#';
@endphp

<div class="container py-4">
    <div class="p-4 bg-light border rounded">
        <h1 class="h4 mb-3">Welcome to {{ $brandName }}</h1>
        <p class="mb-3">This is the public landing dashboard.</p>

        @if($isGuest)
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="fa-solid fa-circle-info me-2"></i>
                <div>Please login to continue and start registration.</div>
            </div>
        @elseif(!$hasRegistration)
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="fa-solid fa-user-plus me-2"></i>
                <div>You haven’t completed registration yet. Choose a registration type to begin.</div>
            </div>
        @else
            <div class="alert alert-secondary d-flex align-items-center" role="alert">
                <i class="fa-solid fa-pen-to-square me-2"></i>
                <div>Your registration exists. You can edit it anytime.</div>
            </div>
        @endif

        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('backend.dashboard.index') }}" class="btn btn-primary">Go to Dashboard</a>

            @if($isGuest)
                <a href="{{ $loginUrl }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-right-to-bracket me-1"></i> Login
                </a>
            @else
                @if(!$hasRegistration)
                    <div class="btn-group">
                        <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-user-plus me-1"></i> Register
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="{{ $regUrl('client') }}">
                                    <i class="fa-solid fa-user-check me-2"></i> Client
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ $regUrl('enterprise_client') }}">
                                    <i class="fa-solid fa-user-check me-2"></i> Enterprise Client
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ $regUrl('entrepreneur') }}">
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
                                <a class="dropdown-item" href="{{ $regUrl('professional') }}">
                                    <i class="fa-solid fa-briefcase me-2"></i> Professional
                                </a>
                            </li>
                        </ul>
                    </div>
                @else
                    {{-- Show specific edit buttons when eligible --}}
                    @if($canEditClient)
                        <a href="{{ $editClientUrl }}" class="btn btn-outline-primary" title="Client">
                            <i class="fa-solid fa-user-check me-1"></i> Edit Registration
                        </a>
                    @endif
                    @if($canEditEnterprise)
                        <a href="{{ $editEnterpriseUrl }}" class="btn btn-outline-primary" title="Enterprise Client">
                            <i class="fa-solid fa-user-check me-1"></i> Edit Registration
                        </a>
                    @endif
                    @if($canEditEntrepreneur)
                        <a href="{{ $editEntrepreneurUrl }}" class="btn btn-outline-primary" title="Entrepreneur">
                            <i class="fa-solid fa-user-check me-1"></i> Edit Registration
                        </a>
                    @endif
                    @if($canEditOfficer)
                        <a href="{{ $editOfficerUrl }}" class="btn btn-outline-primary" title="Company Officer">
                            <i class="fa-solid fa-user-tie me-1"></i> Edit Registration
                        </a>
                    @endif
                    @if($canEditProfessional)
                        <a href="{{ $editProfessionalUrl }}" class="btn btn-outline-primary" title="Professional">
                            <i class="fa-solid fa-briefcase me-1"></i> Edit Registration
                        </a>
                    @endif
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
