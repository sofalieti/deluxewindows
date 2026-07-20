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

<div
    class="bg-white rounded shadow-sm mb-3 wf-collection-list"
    data-wf-reorder-url="{{ $reorderUrl }}"
    data-wf-reorder-enabled="{{ $reorderEnabled ? '1' : '0' }}"
>
    <div class="px-3 py-2 border-bottom text-muted small">
        @if($reorderEnabled)
            Drag the handle to change website display order. Order saves automatically.
        @else
            Clear search to reorder items. Drag-and-drop is disabled while a filter is active.
        @endif
    </div>

    @if($items->isEmpty())
        <div class="p-4 text-muted">No items found.</div>
    @else
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
                <li class="list-group-item wf-collection-row" data-model-id="{{ $id }}">
                    <div
                        class="wf-collection-row__handle{{ $reorderEnabled ? ' reorder-handle' : ' is-disabled' }}"
                        title="{{ $reorderEnabled ? 'Drag to reorder' : 'Clear search to reorder' }}"
                        aria-label="{{ $reorderEnabled ? 'Drag to reorder' : 'Reorder disabled while filtered' }}"
                    >
                        <span class="wf-collection-row__grip" aria-hidden="true"></span>
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

                        <form method="post" action="{{ $toggleUrl }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="item_id" value="{{ $id }}">
                            <button type="submit" class="btn btn-link btn-sm">
                                {{ $isDraft ? 'Enable' : 'Disable' }}
                            </button>
                        </form>

                        <form method="post" action="{{ $deleteUrl }}" class="d-inline"
                              onsubmit="return confirm('Delete this item? This action cannot be undone.');">
                            @csrf
                            <input type="hidden" name="item_id" value="{{ $id }}">
                            <button type="submit" class="btn btn-link btn-sm text-danger">Delete</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</div>

<script>
(function () {
    function boot() {
        if (typeof Sortable === 'undefined') {
            setTimeout(boot, 50);
            return;
        }

        var root = document.querySelector('.wf-collection-list');
        var list = document.getElementById('wf-collection-sortable');
        if (!root || !list) {
            return;
        }

        var url = root.getAttribute('data-wf-reorder-url');
        var enabled = root.getAttribute('data-wf-reorder-enabled') === '1';
        var saving = false;

        if (!enabled) {
            return;
        }

        function csrfToken() {
            var meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : (window._token || '');
        }

        function toast(message, type) {
            if (window.Toast && typeof window.Toast.fire === 'function') {
                window.Toast.fire({ icon: type === 'danger' ? 'error' : 'success', title: message });
                return;
            }
            console.log('[wf-reorder]', message);
        }

        function collectIds() {
            return Array.prototype.map.call(
                list.querySelectorAll('.wf-collection-row[data-model-id]'),
                function (el) { return el.getAttribute('data-model-id'); }
            );
        }

        function renumber() {
            Array.prototype.forEach.call(
                list.querySelectorAll('.wf-collection-row__order'),
                function (el, index) {
                    el.textContent = '#' + (index + 1);
                }
            );
        }

        function saveOrder() {
            if (saving || !url) {
                return;
            }
            saving = true;
            var ids = collectIds();
            var body = new FormData();
            body.append('_token', csrfToken());
            ids.forEach(function (id) {
                body.append('item_ids[]', id);
            });

            fetch(url, {
                method: 'POST',
                body: body,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            })
                .then(function (res) {
                    if (!res.ok) {
                        throw new Error('HTTP ' + res.status);
                    }
                    return res.json().catch(function () { return { ok: true }; });
                })
                .then(function () {
                    renumber();
                    toast('Order saved.');
                })
                .catch(function (err) {
                    console.error(err);
                    toast('Failed to save order.', 'danger');
                })
                .finally(function () {
                    saving = false;
                });
        }

        Sortable.create(list, {
            animation: 150,
            handle: '.reorder-handle',
            draggable: '.wf-collection-row',
            onEnd: function () {
                saveOrder();
            },
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
