<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Authentication</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

        <style>
            body { background: #f4f6f9; }
            .auth-card { max-width: 420px; }
            .otp-input {
                width: 48px;
                height: 48px;
                text-align: center;
                font-size: 20px;
            }
        </style>
    </head>
    <body>
        <div class="container min-vh-100 d-flex align-items-center justify-content-center">
            <div class="card shadow auth-card w-100">
                <div class="card-body p-4">
                    @yield('content')
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            function togglePassword(id, icon){
                const el = document.getElementById(id);
                el.type = el.type === 'password' ? 'text' : 'password';
                icon.classList.toggle('bi-eye');
                icon.classList.toggle('bi-eye-slash');
            }
        </script>

        @stack('scripts')
    </body>
</html>
