<script>
(function () {
    /**
     * Open the hidden file input for a given image field.
     * @param {string} safeKey - sanitised field key used in element IDs
     */
    window.webflowSelectImage = function (safeKey) {
        var input = document.getElementById('wf-file-' + safeKey);
        if (input) {
            input.click();
        }
    };

    /**
     * Handle file selection: upload to the server, then update the URL
     * input and the inline preview image.
     *
     * @param {HTMLInputElement} input     - the file <input> element
     * @param {string}           fieldKey  - original field key (e.g. "main-image")
     * @param {string}           safeKey   - sanitised key used in element IDs
     */
    window.webflowHandleImageUpload = function (input, fieldKey, safeKey) {
        if (!input.files || !input.files[0]) {
            return;
        }

        var file   = input.files[0];
        var btn    = document.getElementById('wf-btn-' + safeKey);
        var urlInput = document.querySelector('[name="fieldData[' + fieldKey + ']"]');
        var preview  = document.getElementById('wf-preview-' + safeKey);

        if (btn) {
            btn.disabled = true;
            btn.textContent = '⏳ Uploading…';
        }

        var formData = new FormData();
        formData.append('image', file);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]')
            ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            : (window._token || ''));

        fetch('{{ route("platform.webflow.upload-image") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('Upload failed: ' + response.status);
            }
            return response.json();
        })
        .then(function (data) {
            if (!data.url) {
                throw new Error('No URL returned from server.');
            }

            // Update the text input that holds the URL
            if (urlInput) {
                urlInput.value = data.url;
            }

            // Show / refresh the preview image
            if (preview) {
                preview.src   = data.url;
                preview.style.display = 'block';
            }

            if (btn) {
                btn.disabled    = false;
                btn.textContent = '✅ Uploaded';
                setTimeout(function () {
                    btn.textContent = '📁 Upload image';
                }, 2500);
            }
        })
        .catch(function (err) {
            console.error('[webflow-upload]', err);
            alert('Image upload failed: ' + err.message);

            if (btn) {
                btn.disabled    = false;
                btn.textContent = '📁 Upload image';
            }
        })
        .finally(function () {
            // Reset file input so the same file can be re-selected if needed
            input.value = '';
        });
    };
})();
</script>
