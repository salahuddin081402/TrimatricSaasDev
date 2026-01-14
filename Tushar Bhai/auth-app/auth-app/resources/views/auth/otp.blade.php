@extends('auth.layout')

@section('content')
    <h4 class="mb-3 text-center">OTP Verification</h4>
    <p class="text-center small">Enter the 6-digit code sent to your phone</p>

    @if(session('success'))
        <div class="alert alert-success text-center py-2">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->has('otp'))
        <div class="alert alert-danger text-center py-2">
            {{ $errors->first('otp') }}
        </div>
    @endif

    <form method="POST">
        @csrf

        <div class="d-flex justify-content-center mb-3 gap-2">
            @for($i = 0; $i < 6; $i++)
                <input type="text"
                       maxlength="1"
                       name="otp[]"
                       class="form-control otp-box text-center"
                       required>
            @endfor
        </div>

        <div class="text-center mb-3">
            <span id="timer" class="fw-bold small text-muted"></span>
        </div>

        <button class="btn btn-success w-100">
            Verify OTP
        </button>
    </form>

    <form method="POST"
          action="{{ route('company.otp.resend', ['company' => request()->route('company')]) }}"
          id="resend-form"
          class="mt-2 d-none text-center">
        @csrf
        <button type="submit" class="btn btn-link text-decoration-none p-0">
            Resend OTP
        </button>
    </form>
@endsection

@push('scripts')
    <script>
        (function () {

            const otpResendAt = {{ session('otp_resend_at') ? session('otp_resend_at') * 1000 : 'null' }};

            const timerEl   = document.getElementById('timer');
            const resendBtn = document.getElementById('resend-form');

            function updateTimer() {
                const now = Date.now();

                if (!otpResendAt) {
                    resendBtn.classList.remove('d-none');
                    timerEl.parentElement.classList.add('d-none');
                    return;
                }

                const diff = Math.max(0, otpResendAt - now);

                // resend link show after 5 minutes timer
                if (diff <= 0) {
                    resendBtn.classList.remove('d-none');
                    timerEl.parentElement.classList.add('d-none');
                    clearInterval(interval);
                    return;
                }

                const m = Math.floor(diff / 60000);
                const s = Math.floor((diff % 60000) / 1000);

                timerEl.innerText =
                    `Resend OTP in ${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
            }

            updateTimer();
            const interval = setInterval(updateTimer, 1000);

            const otpInputs = document.querySelectorAll('.otp-box');

            otpInputs.forEach((input, index) => {
                input.addEventListener('input', () => {
                    input.value = input.value.replace(/\D/g, '');
                    if (input.value.length === 1 && index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                });
            });

            otpInputs[0]?.addEventListener('paste', (e) => {
                const paste = e.clipboardData.getData('text').replace(/\D/g, '');
                if (paste.length === 6) {
                    otpInputs.forEach((input, idx) => {
                        input.value = paste[idx] || '';
                    });
                }
            });

        })();
    </script>
@endpush
