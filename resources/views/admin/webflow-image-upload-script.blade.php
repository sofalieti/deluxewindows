<script>
(function () {
    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')
            ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            : (window._token || '');
    }

    function fieldUrlInput(fieldKey) {
        var name = 'fieldData[' + fieldKey + ']';
        var byName = document.getElementsByName(name);
        if (byName && byName.length) {
            return byName[0];
        }

        return document.querySelector('[name="' + name.replace(/"/g, '\\"') + '"]');
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

    function renderMultiPreview(fieldKey, safeKey, items) {
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
            btn.className = 'wf-image-action';
            btn.setAttribute('data-wf-action', 'remove-multi');
            btn.setAttribute('data-field-key', fieldKey);
            btn.setAttribute('data-safe-key', safeKey);
            btn.setAttribute('data-index', String(i));
            btn.style.cssText = 'position:absolute;top:2px;right:2px;width:22px;height:22px;padding:0;border:none;'
                + 'border-radius:50%;background:rgba(185,28,28,.92);color:#fff;font-size:14px;line-height:22px;'
                + 'cursor:pointer;box-shadow:0 1px 3px rgba(0,0,0,.25);z-index:2';

            wrap.appendChild(image);
            wrap.appendChild(btn);
            preview.appendChild(wrap);
        });
    }

    /**
     * Open the hidden file input for a given image field.
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
            alert('Could not find the gallery field. Try editing the JSON manually, then save.');
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

        renderMultiPreview(fieldKey, safeKey, items);
    };

    /**
     * Handle file selection: upload to the server, then update the URL
     * input and the inline preview image.
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

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || !target.closest) {
            return;
        }

        var actionEl = target.closest('.wf-image-action');
        if (!actionEl) {
            return;
        }

        var action = actionEl.getAttribute('data-wf-action');
        var fieldKey = actionEl.getAttribute('data-field-key') || '';
        var safeKey = actionEl.getAttribute('data-safe-key') || '';
        var index = parseInt(actionEl.getAttribute('data-index') || '-1', 10);

        if (action === 'select') {
            event.preventDefault();
            window.webflowSelectImage(safeKey);
            return;
        }

        if (action === 'clear') {
            event.preventDefault();
            window.webflowClearImage(fieldKey, safeKey);
            return;
        }

        if (action === 'remove-multi') {
            event.preventDefault();
            window.webflowRemoveMultiImage(fieldKey, safeKey, index);
        }
    });

    document.addEventListener('change', function (event) {
        var input = event.target;
        if (!input || input.getAttribute('data-wf-action') !== 'upload') {
            return;
        }

        var fieldKey = input.getAttribute('data-field-key') || '';
        var safeKey = input.getAttribute('data-safe-key') || '';
        window.webflowHandleImageUpload(input, fieldKey, safeKey);
    });
})();
</script>
