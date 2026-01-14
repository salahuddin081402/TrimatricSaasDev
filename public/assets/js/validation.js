(function (w, d) {
  "use strict";

  /* ================== Core validators ================== */
  function _trim(v){ return String(v == null ? '' : v).trim(); }
  function isEmpty(v) { return _trim(v) === ""; }
  function validateRequired(v) { return !isEmpty(v); }
  function validateMinLen(v, n){ return _trim(v).length >= (n || 0); }

  function validateEmail(v) {
    if (isEmpty(v)) return true; // optional unless page requires it
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i;
    return re.test(_trim(v));
  }

  // Normalize + validate Bangladesh mobile numbers:
  // Accepts: 01XXXXXXXXX, +8801XXXXXXXXX, 8801XXXXXXXXX
  function normalizeBDPhone(v) {
    if (isEmpty(v)) return "";
    return _trim(v)
      .replace(/\s+/g, "")
      .replace(/^\+880/, "0")
      .replace(/^880/, "0");
  }
  function validateBDPhone(v) {
    const x = normalizeBDPhone(v);
    return /^01[3-9][0-9]{8}$/.test(x);
  }

  // Dates
  function validateDateYMD(v) {
    if (isEmpty(v)) return true; // DOB is nullable in controller
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(_trim(v));
    if (!m) return false;
    const y = +m[1], mo = +m[2], d = +m[3];
    const dt = new Date(y, mo - 1, d);
    if (dt.getFullYear() !== y || (dt.getMonth() + 1) !== mo || dt.getDate() !== d) return false;
    const today = new Date(); today.setHours(0,0,0,0);
    return dt.getTime() <= today.getTime();
  }
  function validateDateNotFuture(iso){ return validateDateYMD(iso); }

  /* ============= Error rendering & UX helpers ============= */
  function _containerForField(form, nameKey) {
    let field = form.querySelector('[name="' + nameKey + '"]');
    if (!field) {
      const radios = form.querySelectorAll('input[type="radio"][name="' + nameKey + '"]');
      if (radios && radios.length) field = radios[0];
    }
    if (!field) return form;

    if (field.type === "radio") {
      const group = field.closest(".radio-group") || field.closest(".col-12, .col-md-6, .col-md-4, .col-md-3");
      return group || field.parentElement || form;
    }
    return field.closest(".dob-wrap, .col-12, .col-md-6, .col-md-4, .col-md-3, .form-group") || field.parentElement || form;
  }

  function _fieldEl(form, nameKey) {
    let el = form.querySelector('[name="'+nameKey+'"]');
    if (el) return el;
    const radios = form.querySelectorAll('input[type="radio"][name="'+nameKey+'"]');
    if (radios && radios.length) return radios[0];
    return null;
  }

  function _markInvalidVisual(fieldOrName, form) {
    let field = typeof fieldOrName === "string"
      ? form.querySelector('[name="' + fieldOrName + '"]')
      : fieldOrName;
    if (field && field.classList) field.classList.add("is-invalid");
    if (field && field.tomselect && field.tomselect.wrapper) {
      field.tomselect.wrapper.classList.add("is-invalid");
    }
  }

  function _clearInvalidVisual(fieldOrName, form) {
    let field = typeof fieldOrName === "string"
      ? form.querySelector('[name="' + fieldOrName + '"]')
      : fieldOrName;
    if (field && field.classList) field.classList.remove("is-invalid");
    if (field && field.tomselect && field.tomselect.wrapper) {
      field.tomselect.wrapper.classList.remove("is-invalid");
    }
  }

  function _clearAllErrors(form) {
    form.querySelectorAll(".err.js, .err.js-field-error").forEach(n => n.remove());
    form.querySelectorAll(".is-invalid").forEach(n => n.classList.remove("is-invalid"));
  }

  function setFieldError(form, nameKey, message){
    const el = _fieldEl(form, nameKey);
    const wrap = _containerForField(form, nameKey);

    if (el) _markInvalidVisual(el, form);

    let errNode = wrap.querySelector('.err.js-field-error[data-for="'+nameKey+'"]');
    if (!errNode) {
      errNode = d.createElement('div');
      errNode.className = 'err js-field-error';
      errNode.dataset.for = nameKey;
      if (el && el.parentNode) el.insertAdjacentElement('afterend', errNode);
      else wrap.appendChild(errNode);
    }
    errNode.textContent = message;
  }

  function clearFieldError(form, nameKey){
    const el = _fieldEl(form, nameKey);
    const wrap = _containerForField(form, nameKey);
    if (el) _clearInvalidVisual(el, form);
    const errNode = wrap.querySelector('.err.js-field-error[data-for="'+nameKey+'"]');
    if (errNode) errNode.remove();
  }

  function renderFieldErrors(form, pairs) {
    _clearAllErrors(form);
    pairs.forEach(([name, msg]) => {
      _markInvalidVisual(name, form);
      const container = _containerForField(form, name);
      const dnode = d.createElement('div');
      dnode.className = 'err js-field-error';
      dnode.dataset.for = name;
      dnode.textContent = msg;

      const inputs = container.querySelectorAll('input, select, textarea');
      if (inputs.length && inputs[inputs.length - 1].parentNode === container) {
        inputs[inputs.length - 1].insertAdjacentElement('afterend', dnode);
      } else {
        container.appendChild(dnode);
      }
    });
  }

  function focusErrorField(form, name) {
    const radios = form.querySelectorAll('input[type="radio"][name="' + name + '"]');
    if (radios && radios.length) { radios[0].focus(); return; }
    const el = form.querySelector('[name="' + name + '"]');
    if (el && el.tomselect) { try { el.tomselect.focus(); return; } catch(_){} }
    if (el && typeof el.focus === 'function') el.focus();
  }

  function attachLiveValidation(form, map) {
    const clearFor = (el) => {
      _clearInvalidVisual(el, form);
      const container = _containerForField(form, el.name || "");
      container && container.querySelectorAll('.err.js-field-error[data-for="'+(el.name||'')+'"]').forEach(n => n.remove());
    };

    form.addEventListener('input', (e) => {
      const t = e.target;
      if (!t.name) return;
      clearFor(t);
      if (t.name === 'phone') {
        const caret = t.selectionStart;
        const norm = normalizeBDPhone(t.value);
        if (norm !== t.value) {
          t.value = norm;
          try { t.setSelectionRange(caret, caret); } catch(_){}
        }
      }
      if (map && typeof map[t.name] === 'function') {
        const msg = map[t.name]();
        if (msg) setFieldError(form, t.name, msg);
      }
    });

    form.addEventListener('change', (e) => {
      const t = e.target;
      if (!t.name) return;
      clearFor(t);
      if (map && typeof map[t.name] === 'function') {
        const msg = map[t.name]();
        if (msg) setFieldError(form, t.name, msg);
      }
    });
  }

  w.validateRequired        = validateRequired;
  w.validateMinLen          = validateMinLen;
  w.validateEmail           = validateEmail;
  w.validateBDPhone         = validateBDPhone;
  w.normalizeBDPhone        = normalizeBDPhone;
  w.validateDateYMD         = validateDateYMD;
  w.validateDateNotFuture   = validateDateNotFuture;

  w.renderFieldErrors       = renderFieldErrors;
  w.focusErrorField         = focusErrorField;
  w.setFieldError           = setFieldError;
  w.clearFieldError         = clearFieldError;
  w.attachLiveValidation    = attachLiveValidation;

  d.addEventListener('DOMContentLoaded', function () {
    d.querySelectorAll('form').forEach(f => attachLiveValidation(f));
    const today = new Date(); const yyyy = today.getFullYear();
    const mm = String(today.getMonth()+1).padStart(2,'0');
    const dd = String(today.getDate()).padStart(2,'0');
    const maxStr = `${yyyy}-${mm}-${dd}`;
    d.querySelectorAll('input[type="date"]').forEach(el => { if (!el.max) el.max = maxStr; });
  });

})(window, document);
