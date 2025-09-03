@extends('backend.layouts.master')

@section('content')
@php
    // Determine brand name from route company param; fallback to global platform
    $brandName = 'ArchReach';
    $param = request()->route('company');
    if ($param instanceof \App\Models\SuperAdmin\GlobalSetup\Company) {
        $brandName = $param->name;
    } elseif (is_string($param) && $param !== '') {
        $row = \Illuminate\Support\Facades\DB::table('companies')
            ->where('slug', $param)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->first();
        if ($row) {
            $brandName = $row->name;
        }
    }
@endphp

<div class="container py-4">
    <div class="p-4 bg-light border rounded">
        <h1 class="h4 mb-3">Welcome to {{ $brandName }}</h1>
        <p class="mb-3">This is the public landing dashboard.</p>

        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('backend.dashboard.index') }}" class="btn btn-primary">Go to Dashboard</a>
            {{-- Placeholder actions until Breeze/auth & registration routes are ready --}}
            <a href="#" class="btn btn-outline-secondary disabled" aria-disabled="true">Login</a>
            <a href="#" class="btn btn-outline-secondary">Register</a>
        </div>
    </div>
</div>
@endsection
