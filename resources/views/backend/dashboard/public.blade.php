@extends('backend.layouts.master')

@section('content')
<div class="container py-4">
    <div class="p-4 bg-light border rounded">
        <h1 class="h4 mb-3">Welcome to Trimatric SaaS</h1>
        <p class="mb-3">This is the public landing dashboard.</p>
        <div class="d-flex gap-2">
            <a href="{{ route('backend.dashboard.index') }}" class="btn btn-primary">Go to Dashboard</a>
            {{-- Replace with real routes when auth is ready --}}
            <a href="#" class="btn btn-outline-secondary disabled">Login</a>
            <a href="#" class="btn btn-outline-secondary">Register</a>
        </div>
    </div>
</div>
@endsection
