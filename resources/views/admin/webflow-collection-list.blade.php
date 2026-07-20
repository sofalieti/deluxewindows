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
                Drag rows to set the website display order, then click <strong>Save order</strong>.
            @else
                Clear search to reorder items. Drag-and-drop is disabled while a filter is active.
            @endif
        </div>

        @if($reorderEnabled && $items->isNotEmpty())
            <button
                type="submit"
                id="wf-save-order-btn"
                class="btn btn-primary btn-sm wf-save-order-btn"
                disabled
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

                        <button
                            type="submit"
                            formaction="{{ $toggleUrl }}"
                            formmethod="post"
                            name="item_id"
                            value="{{ $id }}"
                            class="btn btn-link btn-sm"
                            formnovalidate
                        >
                            {{ $isDraft ? 'Enable' : 'Disable' }}
                        </button>

                        <button
                            type="submit"
                            formaction="{{ $deleteUrl }}"
                            formmethod="post"
                            name="item_id"
                            value="{{ $id }}"
                            class="btn btn-link btn-sm text-danger"
                            formnovalidate
                            onclick="return confirm('Delete this item? This action cannot be undone.');"
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
    function boot() {
        if (typeof Sortable === 'undefined') {
            setTimeout(boot, 50);
            return;
        }

        var form = document.getElementById('wf-collection-reorder-form');
        var list = document.getElementById('wf-collection-sortable');
        var idsBox = document.getElementById('wf-reorder-ids');
        var saveBtn = document.getElementById('wf-save-order-btn');
        if (!form || !list || !idsBox) {
            return;
        }

        var enabled = form.getAttribute('data-wf-reorder-enabled') === '1';
        if (!enabled) {
            return;
        }

        function syncHiddenIds() {
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

        function renumber() {
            Array.prototype.forEach.call(
                list.querySelectorAll('.wf-collection-row__order'),
                function (el, index) {
                    el.textContent = '#' + (index + 1);
                }
            );
        }

        function markDirty() {
            if (!saveBtn) {
                return;
            }
            saveBtn.disabled = false;
            saveBtn.classList.add('is-dirty');
            saveBtn.textContent = 'Save order';
        }

        Sortable.create(list, {
            animation: 150,
            handle: '.reorder-handle',
            draggable: '.wf-collection-row',
            onEnd: function () {
                syncHiddenIds();
                renumber();
                markDirty();
            },
        });

        form.addEventListener('submit', function (event) {
            // Only the main Save order button (or Enter) should submit reorder.
            // Enable/Disable/Delete use formaction and must keep their own target.
            var submitter = event.submitter;
            if (submitter && submitter.getAttribute('formaction')) {
                return;
            }
            syncHiddenIds();
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.textContent = 'Saving…';
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
