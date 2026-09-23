<script>
window.callEnsureHidden = window.callEnsureHidden || function (form, name, value) {
    var input = form.querySelector('input[type="hidden"][name="' + name + '"][data-call-note="1"]');
    if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.setAttribute('data-call-note', '1');
        form.appendChild(input);
    }
    input.value = value;
};

window.callSubmitNote = function (btn) {
    if (!btn || btn.dataset.submitting === '1') {
        return;
    }

    var form = document.getElementById('post-form');
    var cell = btn.closest('.lead-note-cell');
    var textarea = cell ? cell.querySelector('textarea') : null;
    if (!form || !textarea) {
        return;
    }

    var note = (textarea.value || '').trim();
    if (!note) {
        textarea.focus();
        return;
    }

    btn.dataset.submitting = '1';
    window.callEnsureHidden(form, 'subject_id', btn.dataset.subjectId);
    window.callEnsureHidden(form, 'note', note);

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
