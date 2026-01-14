{{-- backend/layouts/partials/head.blade.php --}}
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ $title ?? 'Trimatric SaaS' }}</title>

{{-- Bootstrap 5 --}}
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

{{-- Font Awesome --}}
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>

{{-- Tom Select --}}
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet"/>

{{-- Global reusable CSS --}}
<link href="{{ asset('assets/css/table-header.css') }}" rel="stylesheet"/>
<link href="{{ asset('assets/css/calendar.css?v=1.0') }}" rel="stylesheet"/>   {{-- modal calendar --}}
<link href="{{ asset('assets/css/trimatric-ui.css?v=1.0') }}" rel="stylesheet"/> {{-- cards, labels, inputs --}}
<link href="{{ asset('assets/css/buttons.css?v=1.0') }}" rel="stylesheet"/>      {{-- frozen buttons set --}}

@stack('styles')
