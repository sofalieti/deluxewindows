/* Referral QR helpers (partner kit + print materials). Requires /vendor/qrcode-generator/qrcode.js */
(function () {
  'use strict';

  var LIB_SRC = '/vendor/qrcode-generator/qrcode.js';

  function loadLib(cb) {
    if (typeof window.qrcode === 'function') {
      cb();
      return;
    }
    var existing = document.querySelector('script[data-rf-qr-lib]');
    if (existing) {
      existing.addEventListener('load', cb, { once: true });
      return;
    }
    var s = document.createElement('script');
    s.src = LIB_SRC;
    s.async = true;
    s.setAttribute('data-rf-qr-lib', '1');
    s.addEventListener('load', cb, { once: true });
    document.head.appendChild(s);
  }

  function matrix(text) {
    var qr = window.qrcode(0, 'Q');
    qr.addData(text);
    qr.make();
    var n = qr.getModuleCount();
    var rows = [];
    for (var r = 0; r < n; r++) {
      var row = [];
      for (var c = 0; c < n; c++) {
        row.push(qr.isDark(r, c));
      }
      rows.push(row);
    }
    return rows;
  }

  function svg(text, opts) {
    opts = opts || {};
    var m = matrix(text);
    var n = m.length;
    var margin = opts.margin == null ? 2 : opts.margin;
    var size = n + margin * 2;
    var fg = opts.color || '#0d2236';
    var path = '';
    for (var r = 0; r < n; r++) {
      for (var c = 0; c < n; c++) {
        if (m[r][c]) {
          path += 'M' + (c + margin) + ' ' + (r + margin) + 'h1v1h-1z';
        }
      }
    }
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' + size + ' ' + size + '" shape-rendering="crispEdges"'
      + (opts.label ? ' role="img" aria-label="' + String(opts.label).replace(/"/g, '&quot;') + '"' : '')
      + '><rect width="' + size + '" height="' + size + '" fill="#fff"/><path fill="' + fg + '" d="' + path + '"/></svg>';
  }

  function pngDataUrl(text, px) {
    var m = matrix(text);
    var n = m.length;
    var margin = 2;
    var cells = n + margin * 2;
    var scale = Math.max(1, Math.floor((px || 1024) / cells));
    var canvas = document.createElement('canvas');
    canvas.width = canvas.height = cells * scale;
    var ctx = canvas.getContext('2d');
    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#0d2236';
    for (var r = 0; r < n; r++) {
      for (var c = 0; c < n; c++) {
        if (m[r][c]) {
          ctx.fillRect((c + margin) * scale, (r + margin) * scale, scale, scale);
        }
      }
    }
    return canvas.toDataURL('image/png');
  }

  function download(href, filename) {
    var a = document.createElement('a');
    a.href = href;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
  }

  function copy(text, btn) {
    var done = function () {
      if (!btn) return;
      var original = btn.getAttribute('data-label') || btn.textContent;
      btn.setAttribute('data-label', original);
      btn.textContent = 'Copied!';
      setTimeout(function () { btn.textContent = original; }, 1600);
    };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(done, function () { fallbackCopy(text); done(); });
    } else {
      fallbackCopy(text);
      done();
    }
  }

  function fallbackCopy(text) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly', '');
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); } catch (e) { /* ignore */ }
    ta.remove();
  }

  function renderAll(root) {
    (root || document).querySelectorAll('[data-rf-qr]').forEach(function (el) {
      var url = el.getAttribute('data-rf-qr');
      if (!url || el.getAttribute('data-rf-qr-done') === url) return;
      el.innerHTML = svg(url, { label: 'QR code for ' + url });
      el.setAttribute('data-rf-qr-done', url);
    });
  }

  function bind(root) {
    root = root || document;
    renderAll(root);

    if (root.__rfQrBound) return;
    root.__rfQrBound = true;

    root.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-rf-copy],[data-rf-copy-from],[data-rf-download]');
      if (!btn || !root.contains(btn)) return;
      e.preventDefault();

      if (btn.hasAttribute('data-rf-copy')) {
        copy(btn.getAttribute('data-rf-copy'), btn);
        return;
      }
      if (btn.hasAttribute('data-rf-copy-from')) {
        var src = root.querySelector(btn.getAttribute('data-rf-copy-from'));
        if (src) copy(src.value != null ? src.value : src.textContent, btn);
        return;
      }

      var url = btn.getAttribute('data-rf-url');
      var name = btn.getAttribute('data-rf-name') || 'deluxe-referral-qr';
      if (btn.getAttribute('data-rf-download') === 'svg') {
        var blob = new Blob([svg(url)], { type: 'image/svg+xml' });
        var href = URL.createObjectURL(blob);
        download(href, name + '.svg');
        setTimeout(function () { URL.revokeObjectURL(href); }, 2000);
      } else {
        download(pngDataUrl(url, 1200), name + '.png');
      }
    });

    root.addEventListener('change', function (e) {
      var select = e.target.closest('[data-rf-qr-select]');
      if (!select) return;
      var target = root.querySelector(select.getAttribute('data-rf-qr-select'));
      var option = select.options[select.selectedIndex];
      var url = option.getAttribute('data-url');
      if (target && url) {
        target.setAttribute('data-rf-qr', url);
        renderAll(root);
      }
      root.querySelectorAll('[data-rf-qr-link]').forEach(function (el) {
        el.textContent = url;
      });
      root.querySelectorAll('[data-rf-download]').forEach(function (el) {
        el.setAttribute('data-rf-url', url);
        el.setAttribute('data-rf-name', 'deluxe-referral-' + option.value);
      });
      root.querySelectorAll('[data-rf-qr-copy]').forEach(function (el) {
        el.setAttribute('data-rf-copy', url);
      });
    });
  }

  window.DeluxeReferralQr = {
    load: loadLib,
    svg: svg,
    png: pngDataUrl,
    render: function (root) { loadLib(function () { renderAll(root); }); },
    mount: function (root) { loadLib(function () { bind(root); }); }
  };
})();
