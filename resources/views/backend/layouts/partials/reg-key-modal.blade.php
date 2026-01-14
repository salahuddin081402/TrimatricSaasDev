{{-- resources/views/backend/layouts/partials/reg-key-modal.blade.php --}}
<div class="modal fade" id="coRegKeyModal" tabindex="-1" aria-labelledby="coRegKeyLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content shadow">
      <div class="modal-header">
        <h5 class="modal-title" id="coRegKeyLabel">
          <i class="fa-solid fa-key me-2"></i> Registration Key
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <label for="coRegKeyInput" class="form-label" style="color: var(--label,#2b2ba1cf);">Enter the key</label>
        <input type="text" id="coRegKeyInput" class="form-control" autocomplete="off" placeholder="e.g. ABCD-1234" />
        <div class="form-text">Ask your Head Office for the correct key.</div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-x btn-cancel" data-bs-dismiss="modal">
          <i class="fa-solid fa-circle-xmark me-1"></i> Cancel
        </button>
        <button type="button" id="coRegKeySubmit" class="btn btn-x btn-save">
          <i class="fa-solid fa-check me-1"></i> Submit
        </button>
      </div>
    </div>
  </div>
</div>
