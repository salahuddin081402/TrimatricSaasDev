{{-- resources/views/frontend/home.blade.php (example path) --}}
@extends('backend.layouts.master')

@section('title', 'HOME | TRIMATRIC ARCHITECTS & ENGINEERS')

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

    <!-- swiper css link -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

    <!-- font awesome cdn link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Custom CSS embedded here -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600&display=swap');

        /* CSS starts here */
        :root {
            --main-color:#808080;
            --black:#222;
            --white:#fff;
            --light-black:#777;
            --light-white:rgba(255, 255, 255, 0.6);
            --dark-bg:rgba(0, 0, 0, 0.7);
            --light-bg:#eee;
            --border:.1rem solid var(--black);
            --box-shadow:0 .5rem 1rem rgba(0, 0, 0, 0.5);
            --text-shadow:0 1.5rem 3rem rgba(0, 0, 0, 0.3);
        }

        *{
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            outline: none;
            border: none;
            text-decoration: none;
            text-transform: capitalize;
        }

        html{
            font-size: 80%;
            overflow-x: hidden;
            scroll-behavior: smooth;
        }
            
        html::-webkit-scrollbar { width: 1rem; }
        html::-webkit-scrollbar-track { background-color: var(--white); }
        html::-webkit-scrollbar-thumb { background-color: var(--main-color); }

        section{ padding: 5rem 10%; }

        @keyframes fadeIn {
            0%{ opacity: 0; transform: scale(.8); }
        }

        .heading{
            background-size: cover !important;
            background-position: center !important;
            padding-top: 10rem;
            padding-bottom: 15rem;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .heading h1{
            font-size: 4rem;
            text-transform: uppercase;
            color: var(--white);
            text-shadow: var(--text-shadow);
        }

        .btn{
            display: inline-block;
            background: rgb(110, 199, 42);
            margin-top: 1rem;
            color: var(--white);
            font-size: 1.5rem;
            padding: 1rem 2rem;
            cursor: pointer;
            border-radius: 5px;
        }
        .btn:hover{
            background: whitesmoke;
            color: rgb(122, 185, 26);
            transition: .2s linear;
            border: 2px solid rgb(110, 199, 42);
        }

        .heading-title{
            text-align: center;
            margin-bottom: 3rem;
            font-size: 2.5rem;
            text-transform: uppercase;
            color: var(--black);
        }

        /* NOTE: You already have header from master layout. Keep this .header block if you use it elsewhere. */
        .header{
            position: sticky;
            top: 0;left: 0; right: 0;
            z-index: 1000;
            background-color: var(--white);
            display: flex;
            padding: 2rem 10%;
            box-shadow: var(--box-shadow);
            align-items: center;
            justify-content: space-between;
        }
        .header .logo{ font-size: 2.5rem; color: var(--black); }
        .header .navbar a{ font-size: 1.5rem; color: var(--black); margin-left: 3rem; }
        .header .navbar a:hover{ color: rgb(120, 190, 8); }

        #menu-btn{
            font-size: 2.5rem;
            color: var(--black);
            cursor: pointer;
            display: none;
        }

        .home{ padding: 0; }
        .home .slide{
            text-align: center;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background-size: cover !important;
            background-position: center !important;
            min-height: 60rem;
            position: relative;
        }

        .home .slide::before{
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 1;
        }
        .home .slide .content{
            max-width: 100rem;
            position: relative;
            z-index: 2;
            display: none;
        }
        .home .swiper-slide-active .content{ display: inline-block; }
        .home .slide .content span{
            display: block;
            font-size: 3rem;
            color: var(--white);
            padding-bottom: 1rem;
            font-weight: 600;
            animation:fadeIn .3s linear backwards .4s;
        }
        .home .slide .content h3{
            font-size: 6vh;
            color: var(--white);
            text-transform: uppercase;
            line-height: 1.2;
            text-shadow: var(--text-shadow);
            padding: 1rem 0;
            animation:fadeIn .3s linear backwards .6s;
        }
        .home .slide .content .btn{
            animation:fadeIn .3s linear backwards .8s;
        }

        .home .swiper-button-next{
            right: inherit;
            top: inherit;
            bottom: 0;
            width: 5rem;
            height: 5rem;
            background: rgb(207, 207, 207);
            right: 0;
            border-radius: 50%;
            color: var(--black);
        }
        .home .swiper-button-prev{
            top:inherit;
            left: inherit;
            bottom: 0;
            left: 0;
            right: 0;
            width: 5rem;
            height: 5rem;
            background:  rgb(175, 175, 175);
            color: var(--black);
            border-radius: 50%;
            z-index: 3;
        }
        .home .swiper-button-next:hover,
        .home .swiper-button-prev:hover{
            background: var(--main-color);
            color: var(--white);
        }
        .home .swiper-button-next::after,
        .home .swiper-button-prev::after{
            font-size: 2rem;
        }
        .home .swiper-button-prev{ right: 5rem; }

        .services .box-container{
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));
            gap: 1.5rem;
        }
        .services .box-container .box{
            padding: 3rem 2rem;
            text-align: center;
            background: #3f3f3f;
            border-radius: .5rem;
            box-shadow: 0px 5px 10px rgba(0,0,0,0.8);
            transition: .2s linear;
            cursor: pointer;
        }
        .services .box-container .box img{
            width: 30%;
            height: 8rem;
            object-fit: contain;
        }
        .box p{ color: #ffffff; padding:0; font-size: 1 rem; /* equivalent to h4 */ }
        .services .box-container .box:hover{
            background: whitesmoke;
            box-shadow: var(--box-shadow);
        }
        .services .box-container .box:hover h3{ color:rgb(110, 199, 42); }
        .services .box-container .box:hover p{ color: #545454; padding: 0; }

        .box .btn{
            margin-top: 1rem;
            padding: 0.8rem 1.5rem;
            border: 2px solid rgb(255, 255, 255);
            background: #646464;
            color: rgb(255, 255, 255);
            border-radius: 8px;
            box-shadow: 0 5px 10px rgb(255, 255, 255);
        }
        .box .btn:hover{
            background: whitesmoke;
            color: rgb(0, 0, 0);
            border: 2px solid rgb(0, 0, 0);
            box-shadow: 0 5px 10px rgb(60, 60, 60);
        }

        .home-about{
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 2rem;
        }
        .home-about .image{ flex: 1 1 40rem; }
        .home-about .image img{
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: .5rem;
        }
        .home-about .content{
            flex: 1 1 40rem;
            padding: 2rem;
            text-align: left;
        }
        .home-about .content h3{
            font-size: 3rem;
            color: var(--black);
            padding-bottom: 1rem;
        }
        .home-about .content p{
            color: var(--light-black);
            font-size: 1.5rem;
            line-height: 2;
            padding-bottom: 1.5rem;
        }
        .home-about .content .btn{
            background: #3f3f3f;
            border: 2px solid #ffffff;
        }
        .home-about .content .btn:hover{
            background: #fff;
            border: 2px solid #26cc2b;
        }

        .home-packages{
            background: #f5f5f5;
            padding: 60px 5%;
            background: #ededed;
        }
        .heading-title{
            text-align: center;
            font-size: 3rem;
            margin-bottom: 40px;
        }
        .packagesSwiper{
            width: 100%;
            padding-bottom: 60px;
        }
        .swiper-wrapper{ display: flex; }
        .swiper-slide{ height: auto; }
        .box{
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(0,0,0,.8);
        }
        .image img{
            width: 100%;
            height: 230px;
            object-fit: cover;
            padding: 5px;
            border-radius: 10px;
        }
        .content{ padding: 20px; text-align: center; }
        .content h3{ font-size: 2rem; }
        .content h4{
            font-size: 1.8rem;
            color: #717271;
            margin-bottom: 10px;
            font-weight: 300;
        }
        .content p{ font-size: 1.2rem; margin-bottom: 15px; }

        .packages-next, .packages-prev{ color: #626262; }

        .reviews{
            background: var(--light-bg);
            padding-bottom: 8rem;
        }
        .reviews .slide{
            padding: 2rem;
            border: 2px solid  rgb(77, 77, 77);
            background: var(--white);
            text-align: center;
            box-shadow: 0 5px 10px rgba(0,0,0,1.0);
            transition: .2s linear;
            border-radius: 8px;
            user-select: none;
        }
        .reviews .slide .stars{ padding-bottom: .5rem; }
        .reviews .slide .stars i{ color: rgb(255, 200, 61); font-size: 2rem; }
        .reviews .slide h3{ font-size: 2rem; color: white; }
        .reviews .slide p{
            color: var(--light-black);
            font-size: 1.2rem;
            line-height: 2;
            padding: 1rem 0;
        }
        .reviews .slide span{
            font-size: 1.5rem;
            color: var(--main-color);
            display: block;
        }
        .reviews .slide img{
            width: 10rem;
            height: 10rem;
            border-radius: 50%;
            object-fit: cover;
            margin-top: 1rem;
        }
        .reviews .swiper-pagination{ position: absolute; bottom: 2rem; }
        .reviews .swiper-pagination-bullet{
            background: var(--main-color);
            opacity: 0.5;
            width: 1rem;
            height: 1rem;
        }
        .reviews .swiper-pagination-bullet-active{
            background: var(--main-color);
            opacity: 1;
            width: 1.2rem;
            height: 1.2rem;
        }

        body{
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            line-height: 1.6;
        }

        .booking{
            padding: 2rem;
            background: none;
            max-width: 600px;
            margin: 50px auto;
            border-radius: 8px;
        }

        .heading-title{
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2.5rem;
            color: #26cc2b;
        }
        .heading-title::after{
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: var(--primary-color);
            margin: 0.5rem auto 0;
            border-radius: 9999px;
        }

        .book-form{
            background: #eeeeee;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        }
        .book-form .flex{
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .inputBox span{
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        .inputBox input{
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid #d2dbd1;
            font-size: 1rem;
            color: var(--text-color);
            transition: all 0.3s ease;
            outline: none;
        }
        .inputBox input:focus{
            border-color: rgb(28, 184, 28);
            box-shadow: 0 0 0 3px rgb(33, 191, 67);
        }
        .flex{
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        .form-options{
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
        }
        .remember-me{
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .forgot-pass{
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-form{
            display: block;
            width: 100%;
            padding: 8px;
            background: #4a4b4a;
            color: rgb(255, 255, 255);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.5rem;
            margin-top: 2rem;
            transition: 0.3s;
            text-align: center;
            text-decoration: none;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.5);
        }
        .btn-form:hover{
            background: #ffffff;
            border: 1px solid rgb(49, 215, 43);
            color: #26cc2b;
        }

        .social-signup{
            text-align: center;
            margin-top: 1rem;
            color: #1b9b1f;
            font-size: 15px;
        }
        .social-btns{
            width: 100%;
            display: block;
            gap: 15px;
        }
        .social-btns .btn{
            margin-top: 0;
            margin-bottom: 20px;
            background: #4a4b4a;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.5);
            padding: 8px;
        }
        .google { background: #ffffff; }
        .google:hover { background: #ffffff; }
        .facebook { background: var(--facebook); }
        .facebook:hover { background: #ffffff; }

        #banner{
            width: 100%;
            height: 300px;
            background: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            text-align: center;
        }
        .banner-text{
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .banner-text h1{
            font-size: 40px;
            font-family:sans-serif;
            font-weight: 600;
            color:  rgb(88, 219, 36);
            white-space: nowrap;
        }
        .banner-text h1{
            margin-top: 10px;
            width: 1200px;
            animation: move 16s linear infinite;
            cursor: pointer;
            transition: transform 8s reverse;
        }
        .banner-text h2{
            font-size: 30px;
            text-decoration: underline;
            font-family:sans-serif;
            font-weight: 600;
            color: #5a5a5a;
        }
        @keyframes move {
            from { transform: translateX(-100%); }
            to { transform: translateX(100%); }
        }

        .banner-btn{ margin: 30px auto 0; }
        .banner-btn a{
            width: 150px;
            text-decoration: none;
            display: inline-block;
            font-family: sans-serif;
            margin: 0 5px;
            padding: 15px 0;
            color: #131414;
            border: 1px solid  rgb(78, 196, 31);
            border-radius: 8px;
            position: relative;
            z-index: 1;
            transition: color 0.5s;
            font-size: 15px;
            font-weight: 600;
        }
        .banner-btn a span{
            width: 0%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            background: rgb(88, 219, 36);
            z-index: -1;
            transition: 0.5s;
        }
        .banner-btn a:hover span{ width: 100%; }
        .banner-btn a:hover{ color: #040404; }

        .input[type="number"], input[type="date"], input[type="email"] {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        @media (min-width: 640px) { .book-form { padding: 3rem; } }

        .home-offer{
            background:white;
            padding: 8rem 5%;
            text-align: center;
            margin-top: 0%;
        }
        .home-offer .content{
            max-width: 50rem;
            margin: auto;
            text-align: center;
        }
        .home-offer .content h3{
            font-size: 4rem;
            color: rgb(88, 219, 36);
            text-transform: uppercase;
        }
        .home-offer .content p{
            font-size: 1.5rem;
            color: var(--light-black);
            line-height: 2;
            padding: 1.5rem 0;
        }
        .home-offer .content .btn{
            margin-top: 1rem;
            background: #4a4b4a;
            border-radius: 8px;
        }
        .home-offer .content .btn:hover{
            background: whitesmoke;
            color: rgb(88, 219, 36);
            border: 2px solid rgb(88, 219, 36);
        }

        .footer{
            background: #545454;
            padding: 60px 5% 20px;
            color: #e2e2e2;
        }
        .footer .box-container{
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 2fr));
            gap: 30px;
        }
        .footer .box-container .box{
            text-align: left;
            padding: 20px;
            background: #3f3f3f;
            border: none;
        }
        .footer .box h3{
            font-size: 1.8rem;
            margin-bottom: 15px;
            margin-left: 5px;
            color: #ffffff;
            text-transform: capitalize;
        }
        .footer .box a{
            display: block;
            font-size: 1.4rem;
            color: #ffffff;
            padding: 8px 0;
            text-decoration: none;
            transition: 0.3s;
        }
        .footer .box a i{
            color: #4edf25;
            margin-right: 8px;
            size: 1.6rem;
        }
        .footer .box a:hover{
            color: #fff;
            padding-left: 5px;
        }
        .footer .box p{
            font-size: 1.4rem;
            color: #ccc;
            line-height: 2;
        }
        .footer .credit{
            text-align: center;
            margin-top: 40px;
            font-size: 1.4rem;
            color: #ccc;
        }
        .footer .credit span{ color: #e53935; margin-left: 5px; }

        @media (max-width: 768px) {
            .footer { text-align: center; }
            .footer .box a { justify-content: center; }
        }

        @media (max-width: 1200px){
            section{ padding: 3rem 5%; }
        }
        @media (max-width: 991px){
            html{ font-size: 55%; }
            section{ padding: 3rem 2rem; }
            .home .slide .content h3{ font-size: 4rem; }
            .header { padding: 2rem; }
        }
        @media (max-width: 768px){
            .home .slide .content h3{ font-size: 3.5rem; }
        }
        @media (max-width: 600px){
            html{ font-size: 50%; }
            .home .slide .content h3{ font-size: 3.2rem; }
        }
        @media (max-width: 500px){
            .home .slide .content h3{ font-size: 3rem; }
        }
        @media (max-width: 480px){
            html{ font-size: 48%; }
        }
        @media (max-width: 460px){
            .home .slide .content h3{ font-size: 2.8rem; }
        }
        @media (max-width: 450px){
            html{ font-size: 50%; }
            .heading-title{ font-size: 2rem; }
        }
        @media (max-width: 350px){
            .home .slide .content h3{ font-size: 3rem; }
        }
        @media (max-width: 280px){
            html{ font-size: 45%; }
        }
    </style>

    <!-- home section starts -->
    <section id="home" class="home">
        <div class="swiper home-slider">
            <div class="swiper-wrapper">
                <div class="swiper-slide slide" style="background: url('{{ asset('assets/images/Build 1.jpg') }}') no-repeat">
                    <div class="content">
                        <span>Building, Construction</span>
                        <h3>we build your dream</h3>
                        <a href="#" class="btn">learn more</a>
                    </div>
                </div>

                <div class="swiper-slide slide" style="background: url('{{ asset('assets/images/Architecture.jpg') }}') no-repeat">
                    <div class="content">
                        <span>archituctural, design, concept</span>
                        <h3>futurestic design and concept</h3>
                        <a href="#" class="btn">discover more</a>
                    </div>
                </div>

                <div class="swiper-slide slide" style="background: url('{{ asset('assets/images/Interior1.jpg') }}') no-repeat">
                    <div class="content">
                        <span>exterior, interior, ideas</span>
                        <h3>make your home studio</h3>
                        <a href="#" class="btn">discover more</a>
                    </div>
                </div>

                <div class="swiper-slide slide" style="background: url('{{ asset('assets/images/Furniture L1.jpg') }}') no-repeat">
                    <div class="content">
                        <span>furniture, design, concept</span>
                        <h3>Luxurious living</h3>
                        <a href="#" class="btn">discover more</a>
                    </div>
                </div>

                <div class="swiper-slide slide" style="background: url('{{ asset('assets/images/Kitchen.jpg') }}') no-repeat">
                    <div class="content">
                        <span>kitchen, concept, healthy</span>
                        <h3>future healthy kitchen</h3>
                        <a href="#" class="btn">discover more</a>
                    </div>
                </div>

                <div class="swiper-slide slide" style="background: url('{{ asset('assets/images/Arch D1.jpg') }}') no-repeat">
                    <div class="content">
                        <span>structure, strength, future</span>
                        <h3>make your dream worthwhile</h3>
                        <a href="#" class="btn">discover more</a>
                    </div>
                </div>
            </div>

            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
    </section>
    <!-- home section ends-->

    <!--- services section starts-->
    <section id="services" class="services">
        <h1 class="heading-title">our services</h1>

        <div class="box-container">
            <div class="box">
                <img src="{{ asset('assets/images/icon-1.png') }}" alt="Architecture">
                <h3 style="color: #39FF14;">Architectural</h3>
                <p>We provive best archetuctural services</p>
            </div>

            <div class="box">
                <img src="{{ asset('assets/images/icon-2.png') }}" alt="Construction">
                <h3 style="color: #39FF14;">Construction</h3>
                <p>We provive best construction support</p>
            </div>

            <div class="box">
                <img src="{{ asset('assets/images/icon-3.png') }}" alt="Structural">
                <h3 style="color: #39FF14;">Structural</h3>
                <p>We provive best structural services</p>
            </div>

            <div class="box">
                <img src="{{ asset('assets/images/icon-4.png') }}" alt="Interior">
                <h3 style="color: #39FF14;">Interior</h3>
                <p>We provive best interion design with material supply</p>
            </div>

            <div class="box">
                <img src="{{ asset('assets/images/icon-6.png') }}" alt="Supply Materials">
                <h3 style="color: #39FF14;">Material Supply</h3>
                <P>We supply all trendy materials required for your Interior</P>
            </div>
        </div>
    </section>
    <!-- services section ends -->

    <!-- about section starts -->
    <section id="about" class="home-about">
        <div class="image">
            <img src="{{ asset('assets/images/build3.jpg') }}" alt="About Us">
        </div>
        <div class="content">
            <h1 class="heading-title">about us</h1>
            <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Doloribus, voluptatum! Quisquam, cumque. Quod, asperiores.</p>
            <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Doloribus, voluptatum! Quisquam, cumque. Quod, asperiores.</p>
            <a href="#about" class="btn">read more</a>
        </div>
    </section>
    <!-- about section ends -->

    <!-- packages section starts -->
    <section id="packages" class="home-packages">
        <h1 class="heading-title">Our Projects</h1>

        <div class="swiper packagesSwiper">
            <div class="swiper-wrapper box-container">
                <div class="swiper-slide box">
                    <div class="image">
                        <img src="{{ asset('assets/images/nyc.jpg') }}" alt="BG">
                    </div>
                    <div class="content">
                        <h3>Bodhundhara Group</h3>
                        <h4>Bangladesh</h4>
                        <p>Lorem ipsum dolor sit amet consectetur adipisicing elit.</p>
                        <a href="#booking" class="btn">learn-more</a>
                    </div>
                </div>

                <div class="swiper-slide box">
                    <div class="image">
                        <img src="{{ asset('assets/images/paris.jpg') }}" alt="TM">
                    </div>
                    <div class="content">
                        <h3>TM corporation</h3>
                        <h4>Bangladesh</h4>
                        <p>Lorem ipsum dolor sit amet consectetur adipisicing elit.</p>
                        <a href="#booking" class="btn">learn more</a>
                    </div>
                </div>

                <div class="swiper-slide box">
                    <div class="image">
                        <img src="{{ asset('assets/images/paris.jpg') }}" alt="TM">
                    </div>
                    <div class="content">
                        <h3>TM corporation</h3>
                        <h4>Bangladesh</h4>
                        <p>Lorem ipsum dolor sit amet consectetur adipisicing elit.</p>
                        <a href="#booking" class="btn">learn more</a>
                    </div>
                </div>

                <div class="swiper-slide box">
                    <div class="image">
                        <img src="{{ asset('assets/images/paris.jpg') }}" alt="TM">
                    </div>
                    <div class="content">
                        <h3>TM corporation</h3>
                        <h4>Bangladesh</h4>
                        <p>Lorem ipsum dolor sit amet consectetur adipisicing elit.</p>
                        <a href="#booking" class="btn">learn more</a>
                    </div>
                </div>

                <div class="swiper-slide box">
                    <div class="image">
                        <img src="{{ asset('assets/images/paris.jpg') }}" alt="TM">
                    </div>
                    <div class="content">
                        <h3>TM corporation</h3>
                        <h4>Bangladesh</h4>
                        <p>Lorem ipsum dolor sit amet consectetur adipisicing elit.</p>
                        <a href="#booking" class="btn">learn more</a>
                    </div>
                </div>

                <div class="swiper-slide box">
                    <div class="image">
                        <img src="{{ asset('assets/images/london.jpg') }}" alt="England">
                    </div>
                    <div class="content">
                        <h3>Jamuna Group</h3>
                        <h4>Bangladesh</h4>
                        <p>Lorem ipsum dolor sit amet consectetur adipisicing elit.</p>
                        <a href="#booking" class="btn">learn-more</a>
                    </div>
                </div>
            </div>

            <div class="swiper-button-next packages-next"></div>
            <div class="swiper-button-prev packages-prev"></div>
        </div>
    </section>
    <!--package section ends-->

    <!-- reviews section starts -->
    <section class="reviews">
        <h1 class="heading-title">Our Clients</h1>
        <div class="swiper reviews-slider">
            <div class="swiper-wrapper">
                <div class="swiper-slide slide">
                    <div class="stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Quisquam, voluptatum. Quisquam, voluptatum. Quisquam, voluptatum.</p>
                    <h3>Ahmed Sobhan</h3>
                    <span>Chairman</span>
                    <img src="{{ asset('assets/images/Boshundhara.jpg') }}" alt="AS">
                </div>

                <div class="swiper-slide slide">
                    <div class="stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Quisquam, voluptatum. Quisquam, voluptatum. Quisquam, voluptatum.</p>
                    <h3>A.K Azad</h3>
                    <span>Managing Director</span>
                    <img src="{{ asset('assets/images/HG.png') }}" alt="Barbara Hope">
                </div>

                <div class="swiper-slide slide">
                    <div class="stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Quisquam, voluptatum. Quisquam, voluptatum. Quisquam, voluptatum.</p>
                    <h3>Babul Hossain</h3>
                    <span>Chairman</span>
                    <img src="{{ asset('assets/images/JG.jpeg') }}" alt="Scatler Johanson">
                </div>

                <div class="swiper-slide slide">
                    <div class="stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Quisquam, voluptatum. Quisquam, voluptatum. Quisquam, voluptatum.</p>
                    <h3>M.A Hashem</h3>
                    <span>Chairman</span>
                    <img src="{{ asset('assets/images/partex.png') }}" alt="Lorence Cook">
                </div>

                <div class="swiper-slide slide">
                    <div class="stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Quisquam, voluptatum. Quisquam, voluptatum. Quisquam, voluptatum.</p>
                    <h3>domain host</h3>
                    <span>client</span>
                    <img src="https://placehold.co/100x100/808080/ffffff?text=User5" alt="Domain Host">
                </div>

                <div class="swiper-slide slide">
                    <div class="stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                    </div>
                    <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Quisquam, voluptatum. Quisquam, voluptatum. Quisquam, voluptatum.</p>
                    <h3>jan doe</h3>
                    <span>client</span>
                    <img src="https://placehold.co/100x100/808080/ffffff?text=User6" alt="Jan Doe">
                </div>
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </section>
    <!-- reviews section ends -->

    <!--- Registration section starts-->
    <section id="services" class="services">
        <h1 class="heading-title">Types of Registration</h1>

        <div class="box-container">
            <div class="box">
                <img src="{{ asset('assets/images/icon-1.png') }}" alt="Architecture">
                <h3 style="color: #39FF14;">Clients</h3>
                <p>For new client registration</p>
                @if($isGuest)
            <span class="btn disabled" aria-disabled="true" tabindex="-1" title="Please login to register">Register Now</span>
        @elseif(!$hasRegistration)
            <a class="btn" href="{{ $regUrl('client') }}">Register Now</a>
        @else
            @if($canEditClient)
                <a class="btn" href="{{ $editUrl('client') }}">Edit Registration</a>
            @else
                <span class="btn disabled" aria-disabled="true" tabindex="-1" title="Already registered">Register Now</span>
            @endif
        @endif
            </div>

            <div class="box">
                <img src="{{ asset('assets/images/icon-2.png') }}" alt="Construction">
                <h3 style="color: #39FF14;">Enterprise Clients</h3>
                <P>For enterprice client registration</P>
                @if($isGuest)
            <span class="btn disabled" aria-disabled="true" tabindex="-1" title="Please login to register">Register Now</span>
        @elseif(!$hasRegistration)
            <a class="btn" href="{{ $regUrl('enterprise_client') }}">Register Now</a>
        @else
            @if($canEditEntClient)
                <a class="btn" href="{{ $editUrl('enterprise_client') }}">Edit Registration</a>
            @else
                <span class="btn disabled" aria-disabled="true" tabindex="-1" title="Already registered">Register Now</span>
            @endif
        @endif
            </div>

            <div class="box">
                <img src="{{ asset('assets/images/icon-3.png') }}" alt="Structural">
                <h3 style="color: #39FF14;">Entrepreneur</h3>
                <p>For Entrepreneur Registration. Become our Business Partner.</p>
                @if($isGuest)
            <span class="btn disabled" aria-disabled="true" tabindex="-1" title="Please login to register">Register Now</span>
        @elseif(!$hasRegistration)
            <a class="btn" href="{{ $regUrl('entrepreneur') }}">Register Now</a>
        @else
            @if($canEditEntr)
                <a class="btn" href="{{ $editUrl('entrepreneur') }}">Edit Registration</a>
            @else
                <span class="btn disabled" aria-disabled="true" tabindex="-1" title="Already registered">Register Now</span>
            @endif
        @endif
            </div>

            <div class="box">
                <img src="{{ asset('assets/images/icon-4.png') }}" alt="Interior">
                <h3 style="color: #39FF14;">Professional</h3>
                <p>For professional registration</p>
                @if($isGuest)
            <span class="btn disabled" aria-disabled="true" tabindex="-1" title="Please login to register">Register Now</span>
        @elseif(!$hasRegistration)
            <a class="btn" href="{{ $regUrl('professional') }}">Register Now</a>
        @else
            @if($canEditPro)
                <a class="btn" href="{{ $editUrl('professional') }}">Edit Registration</a>
            @else
                <span class="btn disabled" aria-disabled="true" tabindex="-1" title="Already registered">Register Now</span>
            @endif
        @endif
            </div>

            <div class="box">
                <img src="{{ asset('assets/images/icon-5.png') }}" alt="Kitchen">
                <h3 style="color: #39FF14;">Company Officer</h3>
                <p>For new employee register</p>
                @if($isGuest)
            <span class="btn disabled" aria-disabled="true" tabindex="-1" title="Please login to register">Register Now</span>
        @elseif(!$hasRegistration)
            <a class="btn" href="#" data-co-trigger="1">Register Now</a>
        @else
            @if($canEditOfficer)
                <a class="btn" href="{{ $editUrl('company_officer') }}">Edit Registration</a>
            @else
                <span class="btn disabled" aria-disabled="true" tabindex="-1" title="Already registered">Register Now</span>
            @endif
        @endif
            </div>
        </div>
    </section>
    <!-- Registration section ends -->

    <!-- work flow section starts -->
    <section id="banner">
        <div class="banner-text">
            <h2>Our Work-flow</h2>
            <h1>HOW YOU WILL ENGAGE WITH US</h1>
            <div class="banner-btn">
                <a href="#"><span></span>Sign-up</a>
                <a href="#"><span></span>Registration</a>
                <a href="#"><span></span>Requisition</a>
                <a href="#"><span></span>Tracking</a>
            </div>
        </div>
    </section>
    <!-- work flow section ends -->

    <!-- offer section starts -->
    <section class="home-offer">
        <div class="content">
            <h3>up to 50% off</h3>
            <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Doloribus, voluptatum! Quisquam, cumque. Quod, asperiores.</p>
            <a href="#booking" class="btn">book now</a>
        </div>
    </section>
    <!-- offer section ends -->

    <!--footer section starts-->
    <section id="contact" class="footer">
        <div class="box-container">
            <div class="box">
                <h3>quick links</h3>
                <a href="#home"><i class="fas fa-angle-right"></i>Home</a>
                <a href="#about"><i class="fas fa-angle-right"></i>About</a>
                <a href="#packages"><i class="fas fa-angle-right"></i>Services</a>
                <a href="#booking"><i class="fas fa-angle-right"></i>Contact</a>
            </div>

            <div class="box">
                <h3>extra links</h3>
                <a href="#"><i class="fas fa-angle-right"></i>ask question</a>
                <a href="#about"><i class="fas fa-angle-right"></i>about us</a>
                <a href="#"><i class="fas fa-angle-right"></i>privacy policy</a>
                <a href="#"><i class="fas fa-angle-right"></i>terms of use</a>
            </div>

            <div class="box">
                <h3>contact info</h3>
                <a href="#"><i class="fas fa-phone"></i> +123-456-7890</a>
                <a href="#"><i class="fas fa-phone"></i> +111-222-3333</a>
                <a href="#"><i class="fas fa-envelope"></i> hello@trimatric.com</a>
                <a href="#"><i class="fas fa-map"></i> los angeles, ca - 400104</a>
            </div>

            <div class="box">
                <h3>follow us</h3>
                <a href="#"><i class="fab fa-facebook-f"></i> facebook</a>
                <a href="#"><i class="fab fa-twitter"></i> twitter</a>
                <a href="#"><i class="fab fa-instagram"></i> instagram</a>
                <a href="#"><i class="fab fa-linkedin"></i> linkedin</a>
            </div>
        </div>
        <div class="credit">created by<span>Trimatric AI</span> | all rights reserved | 2025</div>
    </section>
    <!--footer section ends-->

    <!-- swiper js link -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const menuBtn = document.getElementById('menu-btn');
            const navbar = document.querySelector('.header .navbar');

            if (menuBtn && navbar) {
                menuBtn.onclick = () => {
                    menuBtn.classList.toggle('fa-times');
                    navbar.classList.toggle('active');
                };

                navbar.querySelectorAll('a').forEach(link => {
                    link.onclick = () => {
                        setTimeout(() => {
                            menuBtn.classList.remove('fa-times');
                            navbar.classList.remove('active');
                        }, 100);
                    };
                });
            }

            if (document.querySelector(".home-slider")) {
                new Swiper(".home-slider", {
                    loop: true,
                    grabCursor: true,
                    effect: "fade",
                    autoplay: { delay: 2000, disableOnInteraction: false },
                    navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
                });
            }

            if (document.querySelector(".reviews-slider")) {
                new Swiper(".reviews-slider", {
                    loop: true,
                    grabCursor: true,
                    spaceBetween: 20,
                    autoHeight: true,
                    pagination: { el: ".reviews .swiper-pagination", clickable: true },
                    autoplay: { delay: 5000, disableOnInteraction: false },
                    breakpoints: {
                        640: { slidesPerView: 1 },
                        768: { slidesPerView: 2 },
                        1024: { slidesPerView: 3 },
                    },
                });
            }

            if (document.querySelector(".packagesSwiper")) {
                new Swiper(".packagesSwiper", {
                    loop: true,
                    spaceBetween: 30,
                    speed: 1200,
                    autoplay: { delay: 3000, disableOnInteraction: false },
                    navigation: { nextEl: ".packages-next", prevEl: ".packages-prev" },
                    breakpoints: {
                        0: { slidesPerView: 1 },
                        768: { slidesPerView: 2 },
                        1024: { slidesPerView: 3 },
                    },
                });
            }

            window.onscroll = () => {
                if (menuBtn && navbar) {
                    menuBtn.classList.remove('fa-times');
                    navbar.classList.remove('active');
                }
            };

        });

        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('.book-form');
            if (!form) return;

            form.addEventListener('submit', (e) => {
                const pass = document.getElementsByName('password')[0]?.value ?? '';
                const rePass = document.getElementsByName('re_password')[0]?.value ?? '';

                if (pass !== rePass) {
                    e.preventDefault();
                    alert("Passwords do not match! Please try again.");
                    return;
                }

                const phoneEl = document.getElementsByName('phone')[0];
                if (phoneEl) {
                    const phone = phoneEl.value || '';
                    if (phone.length < 10) {
                        e.preventDefault();
                        alert("Please enter a valid phone number.");
                        return;
                    }
                }
            });

            const googleBtn = document.querySelector('.google');
            if (googleBtn) {
                googleBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    alert("Redirecting to Google Login...");
                });
            }

            const fbBtn = document.querySelector('.facebook');
            if (fbBtn) {
                fbBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    alert("Redirecting to Facebook Login...");
                });
            }
        });
    </script>
@endsection
