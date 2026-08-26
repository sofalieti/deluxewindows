<link rel="stylesheet" href="{{ asset('css/admin-leads.css') }}?v=6">
<link rel="stylesheet" href="{{ asset('css/admin-contacts.css') }}?v=6">
<link rel="stylesheet" href="{{ asset('css/admin-call-stats.css') }}?v=1">
<link rel="stylesheet" href="{{ asset('css/admin-call-recording.css') }}?v=2">

<script>
window.leadEnsureHidden = window.leadEnsureHidden || function (form, name, value) {
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

window.contactSubmitNote = function (btn) {
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
    window.leadEnsureHidden(form, 'contact', btn.dataset.contactId);
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
