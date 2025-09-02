{{-- backend/layouts/partials/scripts.blade.php --}}

{{-- Core JS libraries (always loaded) --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

{{-- Toast auto-show --}}
<script>
    document.querySelectorAll('.toast').forEach(t => new bootstrap.Toast(t).show());
</script>

{{-- Page-specific scripts --}}
@stack('scripts')
