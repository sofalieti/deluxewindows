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

{{--
  Important: do NOT wrap this list in <form>.
  Orchid screens already use forms; a nested form breaks the DOM and kills drag-and-drop.
  Drag uses Orchid's built-in Stimulus Sortable controller (same as Layout::sortable).
--}}
<div class="bg-white rounded shadow-sm mb-3 wf-collection-list">
    <div class="px-3 py-2 border-bottom text-muted small">
        @if($reorderEnabled)
            Drag a row to change website display order. Order saves automatically when you drop.
        @else
            Clear search to reorder items. Drag-and-drop is disabled while a filter is active.
        @endif
    </div>

    @if($items->isEmpty())
        <div class="p-4 text-muted">No items found.</div>
    @else
        <ol
            class="list-group list-group-flush wf-collection-sortable"
            id="wf-collection-sortable"
            @if($reorderEnabled)
                data-controller="sortable"
                data-sortable-selector-value=".reorder-handle"
                data-sortable-model-value="webflow-collection"
                data-sortable-action-value="{{ $reorderUrl }}"
                data-sortable-success-message-value="Order saved."
                data-sortable-failure-message-value="Failed to save order."
            @endif
        >
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
                    data-model-id="{{ $id }}"
                    class="list-group-item wf-collection-row{{ $reorderEnabled ? ' reorder-handle' : '' }}"
                >
                    <div
                        class="wf-collection-row__handle{{ $reorderEnabled ? '' : ' is-disabled' }}"
                        title="{{ $reorderEnabled ? 'Drag to reorder' : 'Clear search to reorder' }}"
                        aria-hidden="true"
                    >
                        <span class="wf-collection-row__grip"></span>
                    </div>

                    <div class="wf-collection-row__main">
                        <div class="wf-collection-row__title">
                            <span class="wf-collection-row__order">#{{ $order > 0 ? $order : $loop->iteration }}</span>
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

                    <div class="wf-collection-row__actions" onclick="event.stopPropagation()">
                        <a class="btn btn-link btn-sm" href="{{ $editUrl }}" onclick="event.stopPropagation()">Edit</a>

                        <form method="post" action="{{ $toggleUrl }}" class="d-inline" onclick="event.stopPropagation()">
                            @csrf
                            <input type="hidden" name="item_id" value="{{ $id }}">
                            <button type="submit" class="btn btn-link btn-sm">
                                {{ $isDraft ? 'Enable' : 'Disable' }}
                            </button>
                        </form>

                        <form
                            method="post"
                            action="{{ $deleteUrl }}"
                            class="d-inline"
                            onclick="event.stopPropagation()"
                            onsubmit="return confirm('Delete this item? This action cannot be undone.');"
                        >
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
