@extends('auth.layout')

@section('content')
    <div class="d-flex justify-content-between">
        <h4 class="mb-3 text-center">Login</h4>
        <span>Back To <a href="{{ route('backend.company.dashboard.public',request()->route()->parameters()) }}">Home</a><i class="bi bi-arrow-right ms-1"></i></span>
    </div>

    @if ($errors->has('login'))
        <div class="alert alert-danger text-center py-2 mb-3">
            {{ $errors->first('login') }}
        </div>
    @endif

    <form method="POST">
        @csrf

        <input type="text" name="login" class="form-control mb-3"
            placeholder="Email or Phone" required>

        <div class="input-group mb-3">
            <input type="password" id="password" name="password"
                class="form-control" placeholder="Password" required>
            <span class="input-group-text">
                <i class="bi bi-eye-slash" onclick="togglePassword('password', this)"></i>
            </span>
        </div>

        <div class="d-flex justify-content-between mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember">
                <label class="form-check-label">Remember me</label>
            </div>
            <a href="{{ route('company.forgotPassword', request()->route()->parameters()) }}">Forgot Password?</a>
        </div>

        <button class="btn btn-primary w-100">Login</button>
    </form>

    <div class="text-center mt-3">
        Don't have an account?
        <a href="{{ route('company.signup', request()->route()->parameters()) }}">Sign Up</a>
    </div>

    <hr>

    <a href="{{ route('login.google', request()->route()->parameters()) }}"
        class="btn btn-outline-danger w-100 mb-2">
        <i class="bi bi-google"></i> Continue with Google
    </a>

    <a href="{{ route('login.facebook', request()->route()->parameters()) }}"
        class="btn btn-outline-primary w-100">
        <i class="bi bi-facebook"></i> Continue with Facebook
    </a>
@endsection
