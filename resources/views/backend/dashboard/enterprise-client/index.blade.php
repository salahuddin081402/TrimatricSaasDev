@extends('backend.layouts.master')

@section('content')
<div class="container py-4">
    <h1 class="h4 mb-3">Enterprise Client Dashboard</h1>
    <p class="text-muted mb-4">Role type: {{ $roleType ?? 'Enterprise' }}</p>
    {{-- Client-facing widgets / requisitions overview --}}
</div>
@endsection
