{{-- backend/layouts/partials/scripts.blade.php --}}

{{-- Core JS libraries (always loaded) --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

{{-- Global reusable JS --}}
<script src="{{ asset('assets/js/validation.js?v=1.0') }}"></script> {{-- your global client validators --}}
<script src="{{ asset('assets/js/calendar.js?v=1.0') }}"></script>    {{-- modal calendar widget --}}
<script src="{{ asset('assets/js/toaster.js?v=1.0') }}"></script>     {{-- custom toaster (tmx…) --}}
<script src="{{ asset('assets/js/image-zoom.js?v=1.0') }}"></script>  {{-- global zoom modal logic --}}
<script src="{{ asset('assets/js/radio-glow.js?v=1.0') }}"></script>  {{-- radio highlight fallback --}}

{{-- Company Officer: registration-key flow (header + public dashboard) --}}
<script src="{{ asset('assets/js/registration-co-key.js?v=1.0') }}"></script>

{{-- Bootstrap toasts auto-show (flash messages) --}}
<script>
  (function () {
    if (!window.bootstrap || !document.querySelectorAll) return;
    document.querySelectorAll('.toast').forEach(function (t) {
      try { new bootstrap.Toast(t).show(); } catch(e) {}
    });
  })();
</script>

{{-- Page-specific scripts --}}
@stack('scripts')
