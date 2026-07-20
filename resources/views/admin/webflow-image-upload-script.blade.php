<script>
(function () {
    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')
            ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            : (window._token || '');
    }

    function fieldUrlInput(fieldKey) {
        return document.querySelector('[name="fieldData[' + fieldKey + ']"]');
    }

    function maybeDeleteLocalUpload(url) {
        if (!url || typeof url !== 'string') {
            return;
        }
        if (url.indexOf('/storage/webflow-uploads/') === -1) {
            return;
        }

        var formData = new FormData();
        formData.append('url', url);
        formData.append('_token', csrfToken());

        fetch('{{ route("platform.webflow.delete-image") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).catch(function (err) {
            console.warn('[webflow-delete]', err);
        });
    }

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
     * Clear a single-image field (URL + preview). Save the item to persist.
     */
    window.webflowClearImage = function (fieldKey, safeKey) {
        if (!window.confirm('Delete this image? Remember to save the item afterward.')) {
            return;
        }

        var urlInput = fieldUrlInput(fieldKey);
        var preview = document.getElementById('wf-preview-' + safeKey);
        var wrapper = document.getElementById('wf-preview-wrapper-' + safeKey);
        var delBtn = document.getElementById('wf-del-' + safeKey);
        var oldUrl = urlInput ? urlInput.value : '';

        if (urlInput) {
            urlInput.value = '';
            urlInput.dispatchEvent(new Event('input', { bubbles: true }));
            urlInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        if (preview) {
            preview.src = '';
            preview.style.display = 'none';
        }

        if (wrapper) {
            wrapper.style.display = 'none';
        }

        if (delBtn) {
            delBtn.style.display = 'none';
        }

        maybeDeleteLocalUpload(oldUrl);
    };

    /**
     * Remove one image from a multi-image JSON field and refresh thumbnails.
     */
    window.webflowRemoveMultiImage = function (fieldKey, safeKey, index) {
        if (!window.confirm('Delete this image from the gallery? Remember to save the item afterward.')) {
            return;
        }

        var textarea = fieldUrlInput(fieldKey);
        if (!textarea) {
            return;
        }

        var items;
        try {
            items = JSON.parse(textarea.value || '[]');
        } catch (e) {
            alert('Could not parse gallery JSON. Fix the JSON first, then try again.');
            return;
        }

        if (!Array.isArray(items) || index < 0 || index >= items.length) {
            return;
        }

        var removed = items.splice(index, 1)[0];
        if (removed && removed.url) {
            maybeDeleteLocalUpload(removed.url);
        }

        textarea.value = JSON.stringify(items, null, 2);
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
        textarea.dispatchEvent(new Event('change', { bubbles: true }));

        var preview = document.getElementById('wf-multi-preview-' + safeKey);
        if (!preview) {
            return;
        }

        preview.innerHTML = '';
        items.forEach(function (img, i) {
            if (!img || typeof img.url !== 'string' || img.url === '') {
                return;
            }
            var wrap = document.createElement('div');
            wrap.className = 'wf-multi-thumb';
            wrap.setAttribute('data-index', String(i));
            wrap.style.cssText = 'position:relative;width:90px;height:68px;';

            var image = document.createElement('img');
            image.src = img.url;
            image.style.cssText = 'width:90px;height:68px;object-fit:cover;border-radius:4px;border:1px solid #dee2e6;display:block;';
            image.onerror = function () { wrap.style.display = 'none'; };

            var btn = document.createElement('button');
            btn.type = 'button';
            btn.title = 'Delete image';
            btn.textContent = '×';
            btn.style.cssText = 'position:absolute;top:2px;right:2px;width:22px;height:22px;padding:0;border:none;'
                + 'border-radius:50%;background:rgba(185,28,28,.92);color:#fff;font-size:14px;line-height:22px;'
                + 'cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,.25)';
            btn.onclick = function () {
                window.webflowRemoveMultiImage(fieldKey, safeKey, i);
            };

            wrap.appendChild(image);
            wrap.appendChild(btn);
            preview.appendChild(wrap);
        });
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

        var file = input.files[0];
        var btn = document.getElementById('wf-btn-' + safeKey);
        var delBtn = document.getElementById('wf-del-' + safeKey);
        var urlInput = fieldUrlInput(fieldKey);
        var preview = document.getElementById('wf-preview-' + safeKey);
        var wrapper = document.getElementById('wf-preview-wrapper-' + safeKey);
        var previousUrl = urlInput ? urlInput.value : '';

        if (btn) {
            btn.disabled = true;
            btn.textContent = '⏳ Uploading…';
        }

        var formData = new FormData();
        formData.append('image', file);
        formData.append('_token', csrfToken());

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

            if (urlInput) {
                urlInput.value = data.url;
                urlInput.dispatchEvent(new Event('input', { bubbles: true }));
                urlInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            if (preview) {
                preview.src = data.url;
                preview.style.display = 'block';
            }

            if (wrapper) {
                wrapper.style.display = 'block';
            }

            if (delBtn) {
                delBtn.style.display = '';
            }

            if (previousUrl && previousUrl !== data.url) {
                maybeDeleteLocalUpload(previousUrl);
            }

            if (btn) {
                btn.disabled = false;
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
                btn.disabled = false;
                btn.textContent = '📁 Upload image';
            }
        })
        .finally(function () {
            input.value = '';
        });
    };
})();
</script>
