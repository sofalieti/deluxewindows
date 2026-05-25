<script>
(function () {
    if (window.__webflowImageUploadReady) return;
    window.__webflowImageUploadReady = true;

    window.webflowSelectImage = function (safeKey) {
        var input = document.getElementById('wf-file-' + safeKey);
        if (input) input.click();
    };

    window.webflowHandleImageUpload = function (input, fieldKey, safeKey) {
        if (!input.files || !input.files[0]) return;

        var btn = document.getElementById('wf-btn-' + safeKey);
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Uploading…';
        }

        var fd = new FormData();
        fd.append('image', input.files[0]);
        fd.append('_token', (document.querySelector('meta[name="csrf-token"]') || {}).content || '');

        fetch('{{ route("platform.webflow.upload-image") }}', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.url) {
                    /* update URL input */
                    var urlInput = document.querySelector('[name="fieldData[' + fieldKey + ']"]');
                    if (urlInput) urlInput.value = data.url;

                    /* update preview image */
                    var preview = document.getElementById('wf-preview-' + safeKey);
                    if (preview) {
                        preview.src = data.url;
                        preview.style.display = 'block';
                    }
                }
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = '📁 Upload image';
                }
            })
            .catch(function (err) {
                console.error('Webflow image upload failed', err);
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = '📁 Upload image';
                }
            });
    };
})();
</script>
