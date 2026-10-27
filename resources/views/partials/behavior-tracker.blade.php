@once
@if(app(\App\Services\VisitsSettingsService::class)->behaviorEnabled())
<script>
  (function () {
    if (window.DeluxeBehavior) return;
    if (location.pathname.indexOf('/admin') === 0) return;
    if (!window.DeluxeAttribution) return;

    var endpoint = @json(route('behavior.events'));
    var csrf = @json(csrf_token());
    var MAX_BATCH = 50;
    var MAX_SESSION = 500;
    var SENT_KEY = 'dw_evt_n';
    var buffer = [];
    var flushTimer = null;
    var engagedMs = 0;
    var engagedSent = 0;
    var lastTick = Date.now();
    var lastActivity = 0;
    var scrollScheduled = false;
    var scrollMarks = { 25: false, 50: false, 75: false, 100: false };
    var ragePoints = [];
    var rageLockUntil = 0;
    var startedForms = [];
    var submittedForms = [];
    var focusedFields = {};
    var SIGNIFICANT = 'a[href^="tel:"], a[href^="mailto:"], button, input[type="submit"], [data-track], a.w-button, a.button, a.primary-button, [role="button"]';

    function sessionSent() {
      try {
        return parseInt(sessionStorage.getItem(SENT_KEY) || '0', 10) || 0;
      } catch (_) {
        return 0;
      }
    }

    function addSessionSent(count) {
      try {
        sessionStorage.setItem(SENT_KEY, String(sessionSent() + count));
      } catch (_) {}
    }

    function identity() {
      var payload = window.DeluxeAttribution.payloadFields();
      return {
        visitor_id: payload.visitor_id || '',
        session_id: payload.session_id || '',
      };
    }

    function deliver(body) {
      try {
        var blob = new Blob([body], { type: 'application/json' });
        if (navigator.sendBeacon && navigator.sendBeacon(endpoint, blob)) return;
      } catch (_) {}
      try {
        fetch(endpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf,
          },
          body: body,
          credentials: 'same-origin',
          keepalive: true,
        }).catch(function () {});
      } catch (_) {}
    }

    function flush() {
      if (flushTimer) {
        clearTimeout(flushTimer);
        flushTimer = null;
      }
      if (!buffer.length) return;
      var ids = identity();
      if (!ids.session_id || !ids.visitor_id) {
        buffer = [];
        return;
      }
      var batch = buffer.splice(0, MAX_BATCH);
      addSessionSent(batch.length);
      deliver(JSON.stringify({
        _token: csrf,
        visitor_id: ids.visitor_id,
        session_id: ids.session_id,
        events: batch,
      }));
    }

    function schedule() {
      if (flushTimer) return;
      flushTimer = setTimeout(flush, 10000);
    }

    function push(type, extra) {
      extra = extra || {};
      if (sessionSent() + buffer.length >= MAX_SESSION) return;
      var ids = identity();
      if (!ids.session_id) return;
      var value = extra.value == null ? null : Math.max(0, Math.round(Number(extra.value) || 0));
      buffer.push({
        type: type,
        path: (location.pathname || '/').slice(0, 500),
        target: extra.target ? String(extra.target).slice(0, 255) : null,
        value: value,
        meta: extra.meta || null,
      });
      if (buffer.length >= MAX_BATCH) flush();
      else schedule();
    }

    function pageMeta() {
      var meta = {};
      var title = (document.title || '').replace(/\s+/g, ' ').trim();
      if (title) meta.title = title.slice(0, 200);
      try {
        if (document.referrer) {
          var ref = new URL(document.referrer);
          if (ref.hostname === location.hostname) meta.referrer = (ref.pathname || '/').slice(0, 200);
        }
      } catch (_) {}
      return meta.title || meta.referrer ? meta : null;
    }

    function label(el) {
      if (!el || !el.getAttribute) return 'page';
      var track = el.getAttribute('data-track');
      if (track) return String(track).slice(0, 255);
      var href = el.getAttribute('href') || '';
      if (href.indexOf('tel:') === 0) return 'tel';
      if (href.indexOf('mailto:') === 0) return 'mailto';
      var text = (el.innerText || el.value || el.getAttribute('aria-label') || '').replace(/\s+/g, ' ').trim();
      text = text.replace(/\d[\d\s().-]{6,}\d/g, '#');
      var tag = el.tagName ? el.tagName.toLowerCase() : 'el';
      return (tag + (text ? ':' + text : '')).slice(0, 255);
    }

    function percent(pos, size) {
      if (!size) return 0;
      return Math.max(0, Math.min(100, Math.round((pos / size) * 100)));
    }

    function bump() {
      lastActivity = Date.now();
    }

    function accumulate() {
      var now = Date.now();
      if (document.visibilityState === 'visible' && lastActivity > 0 && (now - lastActivity) < 30000) {
        engagedMs += now - lastTick;
      }
      lastTick = now;
    }

    function sendEngaged() {
      accumulate();
      var total = Math.floor(engagedMs / 1000);
      var delta = total - engagedSent;
      if (delta > 0) {
        push('engaged_time', { value: delta });
        engagedSent = total;
      }
    }

    function measureScroll() {
      scrollScheduled = false;
      var doc = document.documentElement;
      var max = doc.scrollHeight - window.innerHeight;
      var pct = max <= 0 ? 100 : (window.scrollY / max) * 100;
      [25, 50, 75, 100].forEach(function (mark) {
        if (!scrollMarks[mark] && pct >= mark) {
          scrollMarks[mark] = true;
          push('scroll_depth', { value: mark });
        }
      });
    }

    function isTrackableForm(form) {
      if (!form || !form.querySelector) return false;
      if (form.matches && form.matches('[data-no-track]')) return false;
      return !!form.querySelector('input[type="tel"], input[name="phone"], input[name="Phone"], input[name="Name"], input[name="full_name"]');
    }

    function formId(form) {
      return String(form.getAttribute('data-form-id') || form.getAttribute('name') || form.id || 'form').slice(0, 120);
    }

    function abandonOpenForms() {
      for (var i = 0; i < startedForms.length; i++) {
        var form = startedForms[i];
        if (submittedForms.indexOf(form) !== -1) continue;
        submittedForms.push(form);
        push('form_abandon', { target: formId(form) });
      }
    }

    push('page_view', { meta: pageMeta() });

    window.addEventListener('scroll', function () {
      bump();
      if (scrollScheduled) return;
      scrollScheduled = true;
      requestAnimationFrame(measureScroll);
    }, { passive: true });
    measureScroll();

    document.addEventListener('click', function (e) {
      bump();
      var el = e.target && e.target.closest ? e.target.closest(SIGNIFICANT) : null;
      var name = el ? label(el) : 'page';
      var now = Date.now();
      if (now >= rageLockUntil) {
        ragePoints.push({ t: now, x: e.clientX, y: e.clientY });
        ragePoints = ragePoints.filter(function (point) {
          var dx = point.x - e.clientX;
          var dy = point.y - e.clientY;
          return now - point.t <= 600 && (dx * dx + dy * dy) <= 900;
        });
        if (ragePoints.length >= 3) {
          rageLockUntil = now + 1000;
          ragePoints = [];
          push('rage_click', { target: name, value: 3 });
        }
      }
      if (!el) return;
      push('click', {
        target: name,
        value: percent(e.clientX, window.innerWidth),
        meta: { y: percent(e.clientY, window.innerHeight) },
      });
    }, true);

    ['mousemove', 'keydown', 'touchstart'].forEach(function (eventName) {
      document.addEventListener(eventName, bump, { passive: true });
    });

    document.addEventListener('focusin', function (e) {
      bump();
      var field = e.target;
      if (!field || field.nodeType !== 1 || !field.form) return;
      var type = (field.getAttribute('type') || '').toLowerCase();
      if (type === 'password' || type === 'hidden') return;
      var form = field.form;
      if (!isTrackableForm(form)) return;
      if (startedForms.indexOf(form) === -1) {
        startedForms.push(form);
        push('form_start', { target: formId(form) });
      }
      var fieldName = String(field.getAttribute('name') || field.id || '').slice(0, 80);
      if (!fieldName || focusedFields[fieldName]) return;
      focusedFields[fieldName] = true;
      push('form_field_focus', { target: fieldName });
    }, true);

    document.addEventListener('submit', function (e) {
      var form = e.target;
      if (!isTrackableForm(form)) return;
      if (submittedForms.indexOf(form) === -1) submittedForms.push(form);
      push('form_submit', { target: formId(form) });
      flush();
    }, true);

    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState === 'hidden') {
        sendEngaged();
        flush();
      } else {
        lastTick = Date.now();
      }
    });

    window.addEventListener('pagehide', function () {
      abandonOpenForms();
      sendEngaged();
      flush();
    });

    setInterval(accumulate, 1000);
    window.DeluxeBehavior = { flush: flush };
  })();
</script>
@endif
@endonce
