/* public/assets/js/registration-co-key.js
   Company Officer » Registration Key flow (header & public dashboard)
   - Intercepts "Company Officer" click
   - Shows modal to collect reg_key
   - POSTs to /registration/{company}/company-officer/check-key
   - On ok -> redirect; on invalid -> toast error and allow retry
*/
(function () {
  var modalEl, modal, input, submitBtn;
  var activeCompany = null;

  function qs(sel, root){ return (root||document).querySelector(sel); }
  function qsa(sel, root){ return Array.prototype.slice.call((root||document).querySelectorAll(sel)); }

  function ensureModal() {
    modalEl = document.getElementById('coRegKeyModal');
    input   = document.getElementById('coRegKeyInput');
    submitBtn = document.getElementById('coRegKeySubmit');
    if (!modalEl || !window.bootstrap) return false;
    modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    return true;
  }

  function csrf() {
    var m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }

  function openModal(company) {
    if (!ensureModal()) return;
    activeCompany = company;
    input.value = '';
    setTimeout(function(){ input && input.focus(); }, 200);
    modal.show();
  }

  function closeModal() {
    if (modal) modal.hide();
  }

  function endpoint(company) {
    // AppServiceProvider adds '/registration' prefix.
    return '/registration/' + encodeURIComponent(company) + '/company-officer/check-key';
  }

  async function postKey(company, regKey) {
    try {
      var res = await fetch(endpoint(company), {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf(),
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ reg_key: regKey })
      });
      if (!res.ok) {
        var msg = 'Something went wrong.';
        try { var t = await res.json(); msg = t && t.message ? t.message : msg; } catch(e){}
        throw new Error(msg);
      }
      return await res.json();
    } catch (e) {
      throw e;
    }
  }

  function handleSubmit() {
    var key = (input.value || '').trim();
    if (!activeCompany) {
      toast('Company info missing. Reload the page and try again.', 'error', 3500);
      return;
    }
    if (!key) {
      toast('Registration Key required.', 'warning', 2500);
      input && input.focus();
      return;
    }
    submitBtn && (submitBtn.disabled = true);

    postKey(activeCompany, key).then(function (json) {
      submitBtn && (submitBtn.disabled = false);
      if (json && json.ok && json.redirect) {
        toast('Key accepted. Redirecting…', 'success', 1200);
        closeModal();
        window.location.href = json.redirect;
        return;
      }
      // Not OK -> allow retry
      var msg = (json && json.message) ? json.message : 'Invalid Registration Key. Try again.';
      toast(msg, 'error', 3200);
      input && input.focus();
    }).catch(function (err) {
      submitBtn && (submitBtn.disabled = false);
      toast(err.message || 'Unable to verify key. Try again.', 'error', 3200);
      input && input.focus();
    });
  }

  function wire() {
    // Any "Company Officer" trigger should carry data-co-trigger + data-company
    qsa('[data-co-trigger="1"]').forEach(function (a) {
      a.addEventListener('click', function (e) {
        // Only intercept if user intended to open CO flow (href may be '#')
        e.preventDefault();
        var company = a.getAttribute('data-company') || '';
        if (!company) {
          toast('Company is not available in this context.', 'error', 3200);
          return;
        }
        openModal(company);
      });
    });

    if (!ensureModal()) return;

    submitBtn.addEventListener('click', handleSubmit);
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        handleSubmit();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', wire);
  } else {
    wire();
  }
})();


