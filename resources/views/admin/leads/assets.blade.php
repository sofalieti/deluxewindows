<link rel="stylesheet" href="{{ asset('css/admin-leads.css') }}?v=5">
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
</script>

