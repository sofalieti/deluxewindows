<link rel="stylesheet" href="{{ asset('css/admin-leads.css') }}">
<script>
  window.leadStatusSelectChanged = function (select) {
    var form = select.closest('.lead-status-form');
    if (!form) return;
    select.className = 'lead-status-select lead-status-select--' + select.value;
    var saveBtn = form.querySelector('.lead-status-save');
    if (!saveBtn) return;
    var dirty = select.value !== form.getAttribute('data-original-status');
    saveBtn.hidden = !dirty;
  };
</script>
