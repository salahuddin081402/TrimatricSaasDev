/* Trimatric Toaster (accessible, stacked, backward compatible)
   - Keeps window.toast(message, type='info', ms=3000)
   - Adds window.tmxToast.{success,error,info,warn}(msg, ms)
   - Stacked toasts, per-toast timers, click-to-dismiss, Esc to clear last
   - No external CSS required (inline styles applied), but classes are present for theme overrides
*/
(function () {
  'use strict';

  const TYPE = {
    success: { bg: '#16a34a', fg: '#ffffff', icon: 'M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z' },
    error:   { bg: '#dc2626', fg: '#ffffff', icon: 'M6 6l12 12M18 6L6 18' , stroke: true },
    info:    { bg: '#0ea5e9', fg: '#ffffff', icon: 'M12 2a10 10 0 1 0 0 20a10 10 0 0 0 0-20zm1 8h-2v8h2V10zm0-4h-2v2h2V6z' },
    warning: { bg: '#b45309', fg: '#ffffff', icon: 'M12 2 1 22h22L12 2zm1 14h-2v2h2v-2zm0-8h-2v6h2V8z' }
  };

  function ensureHost() {
    let host = document.getElementById('tmxToaster');
    if (!host) {
      host = document.createElement('div');
      host.id = 'tmxToaster';
      host.className = 'tmx-toaster-host';
      host.setAttribute('aria-live', 'polite');
      host.setAttribute('aria-atomic', 'false');
      Object.assign(host.style, {
        position: 'fixed',
        zIndex: '1080',
        top: '12px',
        right: '12px',
        display: 'flex',
        flexDirection: 'column',
        gap: '10px',
        maxWidth: '92vw',
        pointerEvents: 'none', // clicks pass through except on toasts
      });
      document.body.appendChild(host);

      // ESC clears the last toast
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          const last = host.lastElementChild;
          if (last) dismiss(last);
        }
      });
    }
    return host;
  }

  function iconSVG(def, color) {
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('width', '18');
    svg.setAttribute('height', '18');
    svg.setAttribute('aria-hidden', 'true');
    svg.style.flex = '0 0 auto';
    svg.style.display = 'block';
    svg.style.color = color || '#fff';

    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    if (def.stroke) {
      path.setAttribute('d', def.icon);
      path.setAttribute('fill', 'none');
      path.setAttribute('stroke', 'currentColor');
      path.setAttribute('stroke-width', '2');
      path.setAttribute('stroke-linecap', 'round');
      path.setAttribute('stroke-linejoin', 'round');
    } else {
      path.setAttribute('d', def.icon);
      path.setAttribute('fill', 'currentColor');
    }
    svg.appendChild(path);
    return svg;
  }

  function dismiss(node) {
    if (!node || node.__closing) return;
    node.__closing = true;
    node.style.transform = 'translateY(-6px)';
    node.style.opacity = '0';
    setTimeout(() => node.remove(), 150);
  }

  function createToast(message, type = 'info', ms = 3000) {
    const host = ensureHost();
    const t = TYPE[type] || TYPE.info;

    const item = document.createElement('div');
    item.role = 'alert';
    item.className = `tmx-toast tmx-toast-${type}`;
    item.tabIndex = 0; // focusable for screen readers
    item.style.pointerEvents = 'auto';
    Object.assign(item.style, {
      display: 'flex',
      alignItems: 'center',
      gap: '10px',
      background: t.bg,
      color: t.fg,
      padding: '10px 12px',
      borderRadius: '10px',
      boxShadow: '0 8px 18px rgba(0,0,0,.18)',
      transform: 'translateY(6px)',
      opacity: '0',
      transition: 'opacity .15s ease, transform .15s ease',
      maxWidth: '520px',
      wordBreak: 'break-word',
    });

    // icon
    const svg = iconSVG({ icon: t.icon, stroke: t.stroke }, t.fg);
    item.appendChild(svg);

    // text
    const text = document.createElement('div');
    text.className = 'tmx-toast-text';
    text.style.flex = '1 1 auto';
    text.style.fontSize = '14px';
    text.textContent = String(message || '');
    item.appendChild(text);

    // close button
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'tmx-toast-close';
    btn.setAttribute('aria-label', 'Close');
    Object.assign(btn.style, {
      background: 'transparent',
      color: t.fg,
      border: '0',
      padding: '2px',
      marginLeft: '2px',
      cursor: 'pointer',
      lineHeight: '0',
      fontSize: '18px'
    });
    btn.innerHTML = '&times;';
    btn.addEventListener('click', () => dismiss(item));
    item.appendChild(btn);

    // insert + animate
    host.appendChild(item);
    requestAnimationFrame(() => {
      item.style.transform = 'translateY(0)';
      item.style.opacity = '1';
    });

    // auto-dismiss
    if (ms && ms > 0) {
      item.__tmxTimer = setTimeout(() => dismiss(item), ms);
    }

    // click anywhere on toast also dismisses
    item.addEventListener('click', (e) => {
      // but ignore click on text selection
      if (window.getSelection && String(window.getSelection())) return;
      dismiss(item);
    });

    return item;
  }

  // Public API
  const api = {
    show: (type, msg, ms) => createToast(msg, type, ms),
    success: (msg, ms = 3000) => createToast(msg, 'success', ms),
    error:   (msg, ms = 3000) => createToast(msg, 'error',   ms),
    info:    (msg, ms = 3000) => createToast(msg, 'info',    ms),
    warn:    (msg, ms = 3000) => createToast(msg, 'warning', ms),
    clearAll: () => {
      const host = document.getElementById('tmxToaster');
      if (!host) return;
      Array.from(host.children).forEach(dismiss);
    }
  };

  // Expose new API
  window.tmxToast = api;

  // Backward compatibility: keep window.toast(message, type='info', ms=3000)
  window.toast = function (message, type = 'info', ms = 3000) {
    return createToast(message, type, ms);
  };
})();
