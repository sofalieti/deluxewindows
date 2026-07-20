@php
    /** @var \Illuminate\Support\Collection|\Orchid\Screen\Repository[] $items */
    $items = $items ?? collect();
    $collectionSlug = $collectionSlug ?? ($collection['slug'] ?? '');
    $reorderEnabled = (bool) ($reorderEnabled ?? true);
    $reorderUrl = $reorderUrl ?? route('platform.webflow.collection', [
        'collection' => $collectionSlug,
        'method' => 'reorder',
    ]);
@endphp

<form
    id="wf-collection-reorder-form"
    method="post"
    action="{{ $reorderUrl }}"
    class="bg-white rounded shadow-sm mb-3 wf-collection-list"
    data-wf-reorder-enabled="{{ $reorderEnabled ? '1' : '0' }}"
>
    @csrf

    <div class="px-3 py-2 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="text-muted small mb-0">
            @if($reorderEnabled)
                Drag rows by the handle to set website order, then click <strong>Save order</strong>.
            @else
                Clear search to reorder items. Drag-and-drop is disabled while a filter is active.
            @endif
        </div>

        @if($reorderEnabled && $items->isNotEmpty())
            <button
                type="submit"
                id="wf-save-order-btn"
                class="btn btn-primary btn-sm wf-save-order-btn"
            >
                Save order
            </button>
        @endif
    </div>

    @if($items->isEmpty())
        <div class="p-4 text-muted">No items found.</div>
    @else
        <div id="wf-reorder-ids">
            @foreach($items as $item)
                <input type="hidden" name="item_ids[]" value="{{ (int) data_get($item, 'id', 0) }}">
            @endforeach
        </div>

        <ol class="list-group list-group-flush wf-collection-sortable" id="wf-collection-sortable">
            @foreach($items as $item)
                @php
                    $id = (int) data_get($item, 'id', 0);
                    $name = (string) data_get($item, 'field_data.name', '-');
                    $slug = (string) data_get($item, 'field_data.slug', '-');
                    $webflowId = (string) data_get($item, 'webflow_item_id', '-');
                    $updatedAt = (string) data_get($item, 'updated_at', '-');
                    $order = (int) data_get($item, 'order', 0);
                    $isDraft = (bool) data_get($item, 'is_draft', false);
                    $relations = (string) data_get($item, 'relation_summary', '-');
                    $editUrl = route('platform.webflow.collection.edit', [
                        'collection' => $collectionSlug,
                        'item' => $id,
                    ]);
                    $toggleUrl = route('platform.webflow.collection', [
                        'collection' => $collectionSlug,
                        'method' => 'toggleDraft',
                    ]);
                    $deleteUrl = route('platform.webflow.collection', [
                        'collection' => $collectionSlug,
                        'method' => 'delete',
                    ]);
                @endphp
                <li
                    class="list-group-item wf-collection-row"
                    data-model-id="{{ $id }}"
                >
                    <div
                        class="wf-collection-row__handle{{ $reorderEnabled ? ' reorder-handle' : ' is-disabled' }}"
                        title="{{ $reorderEnabled ? 'Drag to reorder' : 'Clear search to reorder' }}"
                    >
                        <span class="wf-collection-row__grip"></span>
                    </div>

                    <div class="wf-collection-row__main">
                        <div class="wf-collection-row__title">
                            <span class="wf-collection-row__order">#{{ $order >= 999999 ? '—' : $order }}</span>
                            <strong>{{ \Illuminate\Support\Str::limit($name, 80) }}</strong>
                            @if($isDraft)
                                <span class="badge bg-secondary">Disabled</span>
                            @else
                                <span class="badge bg-success">Enabled</span>
                            @endif
                        </div>
                        <div class="wf-collection-row__meta text-muted small">
                            <span>slug: {{ \Illuminate\Support\Str::limit($slug, 60) }}</span>
                            <span>id: {{ $id }}</span>
                            <span>webflow: {{ \Illuminate\Support\Str::limit($webflowId, 40) }}</span>
                            <span>updated: {{ $updatedAt }}</span>
                            @if($relations !== '-' && $relations !== '')
                                <span>rels: {{ $relations }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="wf-collection-row__actions">
                        <a class="btn btn-link btn-sm" href="{{ $editUrl }}">Edit</a>

                        <button
                            type="button"
                            class="btn btn-link btn-sm wf-row-action"
                            data-action-url="{{ $toggleUrl }}"
                            data-item-id="{{ $id }}"
                        >
                            {{ $isDraft ? 'Enable' : 'Disable' }}
                        </button>

                        <button
                            type="button"
                            class="btn btn-link btn-sm text-danger wf-row-action"
                            data-action-url="{{ $deleteUrl }}"
                            data-item-id="{{ $id }}"
                            data-confirm="Delete this item? This action cannot be undone."
                        >
                            Delete
                        </button>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</form>

<script>
(function () {
    var SORTABLE_KEY = '__wfCollectionSortable';
    var LOADING_KEY = '__wfSortableLoading';

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : (window._token || '');
    }

    function sortableSrc() {
        var meta = document.querySelector('meta[name="wf-sortable-src"]');
        return meta ? meta.getAttribute('content') : '/js/sortable.min.js';
    }

    function postAction(url, itemId) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        form.style.display = 'none';

        var token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = csrfToken();
        form.appendChild(token);

        var item = document.createElement('input');
        item.type = 'hidden';
        item.name = 'item_id';
        item.value = String(itemId);
        form.appendChild(item);

        document.body.appendChild(form);
        form.submit();
    }

    function syncHiddenIds(list, idsBox) {
        var ids = Array.prototype.map.call(
            list.querySelectorAll('.wf-collection-row[data-model-id]'),
            function (el) { return el.getAttribute('data-model-id'); }
        );
        idsBox.innerHTML = '';
        ids.forEach(function (id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'item_ids[]';
            input.value = id;
            idsBox.appendChild(input);
        });
    }

    function renumber(list) {
        Array.prototype.forEach.call(
            list.querySelectorAll('.wf-collection-row__order'),
            function (el, index) {
                el.textContent = '#' + (index + 1);
            }
        );
    }

    function bindRowActions(root) {
        Array.prototype.forEach.call(root.querySelectorAll('.wf-row-action'), function (btn) {
            if (btn.dataset.wfBound) {
                return;
            }
            btn.dataset.wfBound = '1';
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                var confirmText = btn.getAttribute('data-confirm');
                if (confirmText && !window.confirm(confirmText)) {
                    return;
                }
                postAction(btn.getAttribute('data-action-url'), btn.getAttribute('data-item-id'));
            });
        });
    }

    function ensureSortable(done) {
        if (typeof window.Sortable !== 'undefined') {
            done();
            return;
        }

        if (window[LOADING_KEY]) {
            window[LOADING_KEY].push(done);
            return;
        }

        window[LOADING_KEY] = [done];

        var script = document.createElement('script');
        script.src = sortableSrc();
        script.async = false;
        script.onload = function () {
            var queue = window[LOADING_KEY] || [];
            window[LOADING_KEY] = null;
            queue.forEach(function (cb) {
                try { cb(); } catch (e) { console.error(e); }
            });
        };
        script.onerror = function () {
            window[LOADING_KEY] = null;
            console.error('[wf-reorder] Failed to load SortableJS from', script.src);
        };
        document.head.appendChild(script);
    }

    function boot() {
        var form = document.getElementById('wf-collection-reorder-form');
        var list = document.getElementById('wf-collection-sortable');
        var idsBox = document.getElementById('wf-reorder-ids');
        var saveBtn = document.getElementById('wf-save-order-btn');

        if (!form || !list || !idsBox) {
            return;
        }

        bindRowActions(form);

        if (form.getAttribute('data-wf-reorder-enabled') !== '1') {
            return;
        }

        ensureSortable(function () {
            if (typeof window.Sortable === 'undefined') {
                console.error('[wf-reorder] SortableJS is not loaded');
                return;
            }

            // DOM may have been replaced by Turbo between load and callback.
            list = document.getElementById('wf-collection-sortable');
            idsBox = document.getElementById('wf-reorder-ids');
            saveBtn = document.getElementById('wf-save-order-btn');
            form = document.getElementById('wf-collection-reorder-form');
            if (!form || !list || !idsBox) {
                return;
            }

            if (list[SORTABLE_KEY]) {
                try {
                    list[SORTABLE_KEY].destroy();
                } catch (e) {}
                list[SORTABLE_KEY] = null;
            }

            list[SORTABLE_KEY] = window.Sortable.create(list, {
                animation: 150,
                handle: '.reorder-handle',
                draggable: '.wf-collection-row',
                filter: 'a, button, input, .wf-row-action',
                preventOnFilter: true,
                forceFallback: true,
                fallbackOnBody: true,
                fallbackTolerance: 5,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onEnd: function () {
                    syncHiddenIds(list, idsBox);
                    renumber(list);
                    if (saveBtn) {
                        saveBtn.classList.add('is-dirty');
                    }
                },
            });
        });

        if (!form.dataset.wfSubmitBound) {
            form.dataset.wfSubmitBound = '1';
            form.addEventListener('submit', function () {
                syncHiddenIds(list, idsBox);
                if (saveBtn) {
                    saveBtn.textContent = 'Saving…';
                    saveBtn.disabled = true;
                }
            });
        }
    }

    function scheduleBoot() {
        setTimeout(boot, 0);
        setTimeout(boot, 50);
        setTimeout(boot, 250);
    }

    document.addEventListener('DOMContentLoaded', scheduleBoot);
    document.addEventListener('turbo:load', scheduleBoot);
    document.addEventListener('turbo:render', scheduleBoot);
    document.addEventListener('turbo:frame-load', scheduleBoot);

    if (document.readyState !== 'loading') {
        scheduleBoot();
    }
})();
</script>
