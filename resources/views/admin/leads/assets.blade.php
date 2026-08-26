<link rel="stylesheet" href="{{ asset('css/admin-leads.css') }}?v=6">
<link rel="stylesheet" href="{{ asset('css/admin-call-stats.css') }}?v=1">
<link rel="stylesheet" href="{{ asset('css/admin-contacts.css') }}?v=5">
<link rel="stylesheet" href="{{ asset('css/admin-call-recording.css') }}?v=2">

<script>
window.leadInlineSubmit = function (select) {
    if (!select || select.dataset.submitting === '1') {
        return;
    }
    var form = document.getElementById('post-form');
    if (!form) {
        return;
    }
    select.dataset.submitting = '1';
    var param = select.dataset.param || 'status';
    var action = select.dataset.actionBase
        + '?lead=' + encodeURIComponent(select.dataset.leadId)
        + '&' + encodeURIComponent(param) + '=' + encodeURIComponent(select.value);
    var btn = document.createElement('button');
    btn.type = 'submit';
    btn.setAttribute('form', 'post-form');
    btn.setAttribute('formaction', action);
    btn.hidden = true;
    document.body.appendChild(btn);
    btn.click();
    btn.remove();
};

window.leadEnsureHidden = function (form, name, value) {
    var input = form.querySelector('input[type="hidden"][name="' + name + '"][data-lead-inline="1"]');
    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.setAttribute('data-lead-inline', '1');
        form.appendChild(input);
    }
    input.value = value;
};

window.leadSubmitNote = function (btn) {
    if (!btn || btn.dataset.submitting === '1') {
        return;
    }
    var form = document.getElementById('post-form');
    var cell = btn.closest('.lead-note-cell');
    var ta = cell ? cell.querySelector('textarea') : null;
    if (!form || !ta) {
        return;
    }
    var note = (ta.value || '').trim();
    if (!note) {
        ta.focus();
        return;
    }
    btn.dataset.submitting = '1';
    window.leadEnsureHidden(form, 'lead', btn.dataset.leadId);
    window.leadEnsureHidden(form, 'note', note);
    var submit = document.createElement('button');
    submit.type = 'submit';
    submit.setAttribute('form', 'post-form');
    submit.setAttribute('formaction', btn.dataset.actionBase);
    submit.hidden = true;
    document.body.appendChild(submit);
    submit.click();
    submit.remove();
};
</script>

