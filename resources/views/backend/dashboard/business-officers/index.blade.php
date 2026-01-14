@extends('backend.layouts.master')

@section('content')
<div class="container py-4">
    <h1 class="h4 mb-3">Business Officers Dashboard</h1>
    <p class="text-muted mb-4">Role type: {{ $roleType ?? 'Business Officer' }}</p>
    {{-- Division/District/Cluster summaries can go here --}}
</div>
@endsection
