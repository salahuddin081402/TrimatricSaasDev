@extends('backend.layouts.master')

@section('content')
<div class="container py-4">
    <h1 class="h4 mb-3">CEO Dashboard</h1>
    <p class="text-muted mb-4">Role type: {{ $roleType ?? 'N/A' }}</p>
    {{-- CEO-specific cards go here --}}
</div>
@endsection
