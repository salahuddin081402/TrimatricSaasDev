@extends('auth.layout')

@section('content')
    <h5 class="mb-3 text-center">Set New Password</h5>

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" novalidate>
        @csrf

        <div class="mb-3">
            <div class="input-group">
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="New Password"
                    required
                    onkeyup="checkPasswordRules(this.value)"
                >
                <span class="input-group-text">
                    <i class="bi bi-eye-slash cursor-pointer"
                       onclick="togglePassword('password', this)"></i>
                </span>
            </div>

            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror

            <div class="mt-1 small" id="password-rules">
                <span class="me-2 text-muted" id="rule-length">
                    <i class="bi bi-circle"></i> 8+ chars
                </span>
                <span class="me-2 text-muted" id="rule-upper">
                    <i class="bi bi-circle"></i> Uppercase
                </span>
                <span class="me-2 text-muted" id="rule-lower">
                    <i class="bi bi-circle"></i> Lowercase
                </span>
                <span class="me-2 text-muted" id="rule-number">
                    <i class="bi bi-circle"></i> Number
                </span>
                <span class="text-muted" id="rule-special">
                    <i class="bi bi-circle"></i> Special
                </span>
            </div>
        </div>

        <div class="input-group mb-3">
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                class="form-control @error('password_confirmation') is-invalid @enderror"
                placeholder="Confirm Password"
                required
            >
            <span class="input-group-text">
                <i class="bi bi-eye-slash cursor-pointer"
                   onclick="togglePassword('password_confirmation', this)"></i>
            </span>
            @error('password_confirmation')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-success w-100">
            Reset Password
        </button>
    </form>

    @push('scripts')
        <script>
            function checkPasswordRules(password) {
                updateRule('rule-length', password.length >= 8);
                updateRule('rule-upper', /[A-Z]/.test(password));
                updateRule('rule-lower', /[a-z]/.test(password));
                updateRule('rule-number', /[0-9]/.test(password));
                updateRule('rule-special', /[@$!%*#?&]/.test(password));
            }

            function updateRule(id, valid) {
                const el = document.getElementById(id);
                const icon = el.querySelector('i');

                if (valid) {
                    el.classList.remove('text-muted');
                    el.classList.add('text-success');
                    icon.classList.remove('bi-circle');
                    icon.classList.add('bi-check-circle-fill');
                } else {
                    el.classList.add('text-muted');
                    el.classList.remove('text-success');
                    icon.classList.add('bi-circle');
                    icon.classList.remove('bi-check-circle-fill');
                }
            }
        </script>
    @endpush
@endsection
