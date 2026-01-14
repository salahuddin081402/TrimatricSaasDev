<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SuperAdmin\GlobalSetup\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Utility\SendSMSUtility;
use Carbon\Carbon;
use Laravel\Socialite\Facades\Socialite;

class AuthController_old extends Controller
{
    private function generateOtp($phone, $company)
    {
        $otp = rand(100000, 999999);
        $now = now();

        session([
            'otp'             => $otp,
            'otp_phone'       => $phone,
            'otp_valid_until' => $now->copy()->addMinutes(15)->timestamp, // OTP validity
            'otp_resend_at'   => $now->copy()->addMinutes(5)->timestamp,  // Resend timer
        ]);

        $sms = "Your OTP for %%BrandName%% is %%OTP%%. Please use it within %%TIME%% minutes.";
        $sms = str_replace(
            ['%%BrandName%%','%%OTP%%','%%TIME%%'],
            [$company->name, $otp, '15'],
            $sms
        );

        SendSMSUtility::sendSMS($phone, $sms);
    }

    public function showOtp()
    {
        if (!session()->has('otp_valid_until')) {
            return redirect()->route('company.login', request()->route()->parameters());
        }

        return view('auth.otp');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|array|size:6',
        ]);

        $enteredOtp = implode('', $request->otp);

        if (!session()->has('otp') || time() > session('otp_valid_until')) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP']);
        }

        if ($enteredOtp !== (string) session('otp')) {
            return back()->withErrors(['otp' => 'Invalid OTP']);
        }

        $signupData = session('signup_data');
        $userId = session('otp_user_id');
        $forgotPasswordUserId = session('forgot_password_user_id');

        // Signup
        if ($signupData) {
            $user = \App\Models\User::create($signupData);
            $user->created_by = $user->id;
            $user->updated_by = $user->id;
            $user->save();
            Auth::login($user);
            session()->forget('signup_data');
        }
        // Login
        elseif ($userId) {
            $user = User::find($userId);
            Auth::login($user, request('remember'));
            session()->forget('otp_user_id');
        }
        // Forgot Password
        elseif ($forgotPasswordUserId) {
            return redirect()->route('company.resetPassword', request()->route()->parameters());
        }

        session()->forget([
            'otp','otp_phone','otp_valid_until','otp_resend_at'
        ]);

        return redirect('backend/company/'.request()->company.'/dashboard/public');
    }


    public function resendOtp(Request $request)
    {
        if (session('otp_resend_at') && time() < session('otp_resend_at')) {
            return back()->withErrors(['otp' => 'Please wait before resending OTP']);
        }

        $user = User::findOrFail(session('otp_user_id'));
        $company = Company::where('slug', $request->route('company'))->firstOrFail();

        $this->generateOtp($user->phone, $company);

        return back()->with('success', 'OTP resent successfully');
    }

    public function showLogin() {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required',
            'password' => 'required'
        ]);

        $slug = $request->route('company');
        $company = Company::where('slug', $slug)->firstOrFail();

        $user = User::where('email', $request->login)
            ->orWhere('phone', $request->login)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['login' => 'Invalid credentials']);
        }else if($user->company_id != $company->id){
            return back()->withErrors(['login' => 'User does not belong to this company']);
        }

        $this->generateOtp($user->phone, $company);
        session(['otp_user_id' => $user->id]);

        return redirect()->route('company.otp', request()->route()->parameters());
    }

    public function showSignup() {
        return view('auth.signup');
    }

    public function signup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|unique:users',
            'email' => 'nullable|email|unique:users',
            'password' => [
                'required','confirmed','min:8',
                'regex:/[A-Z]/','regex:/[a-z]/','regex:/[0-9]/','regex:/[@$!%*#?&]/'
            ]
        ]);

        $slug = $request->route('company');
        $company = Company::where('slug', $slug)->firstOrFail();

        session([
            'signup_data' => [
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'company_id' => $company->id,
                'role_id' => 10
            ]
        ]);

        $this->generateOtp($request->phone, $company);

        return redirect()->route('company.otp', ['company' => $slug]);
    }

    public function showForgot() {
        return view('auth.forgot');
    }

    public function sendForgotOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|exists:users,phone'
        ]);
        $user = User::where('phone', $request->phone)->firstOrFail();
        session(['forgot_password_user_id' => $user->id]);

        $slug = $request->route('company');
        $company = Company::where('slug', $slug)->firstOrFail();
        $this->generateOtp($user->phone, $company);

        return redirect()->route('company.otp', request()->route()->parameters());
    }

    public function showReset() {
        return view('auth.reset');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => [
                'required','confirmed','min:8',
                'regex:/[A-Z]/','regex:/[a-z]/','regex:/[0-9]/','regex:/[@$!%*#?&]/'
            ]
        ]);

        $user = User::find(session('forgot_password_user_id'));
        $user->update(['password' => Hash::make($request->password)]);

        Auth::login($user);
        session()->forget(['forgot_password_user_id']);

        return redirect('backend/company/'.request()->company.'/dashboard/public');
    }

    public function googleRedirect(Request $request)
    {
        $companySlug = $request->route('company');
        session(['social_company_slug' => $companySlug]);

        return Socialite::driver('google')
            ->redirectUrl(route('login.google.callback'))
            ->redirect();
    }


    public function googleCallback()
    {
         try {
            $socialUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('company.login', ['company' => session('social_company_slug')])
                ->withErrors(['login' => 'Google login failed. Please try again.']);
        }

        $companySlug = session('social_company_slug');
        $company = Company::where('slug', $companySlug)->firstOrFail();

        $user = User::where('email', $socialUser->email)->first();
        if($user){
            if($user->provider != 'google'){
                return redirect()->route('company.login', ['company' => session('social_company_slug')])
                ->withErrors(['login' => 'This email is already registered with another login method.']);
            }else if($user->company_id != $company->id){
                return redirect()->route('company.login', ['company' => session('social_company_slug')])
                ->withErrors(['login' => 'This email is already registered for another company.']);
            }
        }else{
            $user = User::create([
                'name'          => $socialUser->name ?? 'Google User',
                'email'         => $socialUser->email,
                'role_id'       => 10,
                'company_id'    => $company->id,
                'password'      => Hash::make(Str::random(20)),
                'provider'      => 'google',
                'provider_id'   => $socialUser->id
            ]);
        }

        Auth::login($user);

        return redirect("backend/company/{$companySlug}/dashboard/public");
    }

    public function facebookRedirect(Request $request)
    {
        $companySlug = $request->route('company');
        session(['social_company_slug' => $companySlug]);

        return Socialite::driver('facebook')
            ->redirectUrl(route('login.facebook.callback'))
            ->redirect();
    }

    public function facebookCallback()
    {
        try {
            $socialUser = Socialite::driver('facebook')->user();
        } catch (\Exception $e) {
            return redirect()->route('company.login', ['company' => session('social_company_slug')])
                ->withErrors(['login' => 'Facebook login failed. Please try again.']);
        }

        $companySlug = session('social_company_slug');
        $company = Company::where('slug', $companySlug)->firstOrFail();

        $user = User::where('email', $socialUser->email)->first();
        if($user){
            if($user->provider != 'facebook'){
                return redirect()->route('company.login', ['company' => session('social_company_slug')])
                ->withErrors(['login' => 'This email is already registered with another login method.']);
            }else if($user->company_id != $company->id){
                return redirect()->route('company.login', ['company' => session('social_company_slug')])
                ->withErrors(['login' => 'This email is already registered for another company.']);
            }
        }else{
            $user = User::create([
                'name'          => $socialUser->name ?? 'Facebook User',
                'email'         => $socialUser->email,
                'role_id'       => 10,
                'company_id'    => $company->id,
                'password'      => Hash::make(Str::random(20)),
                'provider'      => 'facebook',
                'provider_id'   => $socialUser->id
            ]);
        }

        Auth::login($user);

        return redirect("backend/company/{$companySlug}/dashboard/public");
    }

    public function logout(Request $request)
    {
        // Resolve company slug for redirect (logout URL has no {company})
        $slug = $request->route('company');

        if (!$slug) {
            $ref = (string) $request->headers->get('referer', '');
            if ($ref && preg_match('~\/backend\/company\/([^\/]+)\/~', $ref, $m)) {
                $slug = $m[1];
            }
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

     //   return response('LOGOUT OK, AUTH='.(auth()->check() ? 'YES' : 'NO'), 200);
        // Redirect to your actual login route if possible, else home
       if ($slug && \Route::has('company.login')) {
            return redirect()->route('company.login', ['company' => $slug]);
        }
      
        return redirect('/');
    }


}
