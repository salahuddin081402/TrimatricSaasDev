@extends('auth.layout')

@section('content')
    <h5 class="mb-3 text-center">Forgot Password</h5>

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST">
        @csrf
        <input class="form-control mb-3"
            name="phone"
            placeholder="Registered Phone Number"
            required>
        <button class="btn btn-primary w-100" type="submit">Send OTP</button>
    </form>
@endsection
