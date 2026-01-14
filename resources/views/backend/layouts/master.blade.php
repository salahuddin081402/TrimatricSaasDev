{{-- resources/views/backend/layouts/master.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  @include('backend.layouts.partials.head')   {{-- Bootstrap 5, Font Awesome, Tom Select + @stack('styles') --}}
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

  @include('backend.layouts.partials.header')   {{-- logo, search, contact/about/complain, registration link --}}
  @include('backend.layouts.partials.sidebar')  {{-- dynamic role-based menu --}}

  <main class="content-wrapper">
    @include('backend.layouts.partials.alerts') {{-- toasts/flash --}}
    @yield('content')
  </main>

  @include('backend.layouts.partials.footer')

  {{-- Optional control sidebar if ever needed in future. --}}
  {{-- <aside class="control-sidebar control-sidebar-dark"></aside> --}}

</div>

{{-- Global image zoom modal (body-level so it overlays entire app) --}}
@include('backend.layouts.partials.zoom-modal')

@include('backend.layouts.partials.scripts')     {{-- jQuery, Bootstrap bundle, Tom Select + @stack('scripts') --}}
</body>
</html>
