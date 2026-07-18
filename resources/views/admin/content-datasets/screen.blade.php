<div class="bg-white rounded shadow-sm p-4">
    @if($dataset['ready'])
        <div class="alert alert-success" role="status">
            All required dataset files are valid and ready to import.
        </div>

        <dl class="row mb-0">
            <dt class="col-sm-4">Last complete export</dt>
            <dd class="col-sm-8">{{ $dataset['generated_at'] ?: 'Unknown' }}</dd>

            <dt class="col-sm-4">Webflow collections</dt>
            <dd class="col-sm-8">{{ number_format($dataset['webflow_collections']) }}</dd>

            <dt class="col-sm-4">Webflow records</dt>
            <dd class="col-sm-8">{{ number_format($dataset['webflow_records']) }}</dd>

            <dt class="col-sm-4">Door brands</dt>
            <dd class="col-sm-8">{{ number_format($dataset['door_brands']) }}</dd>

            <dt class="col-sm-4">Promotion controls</dt>
            <dd class="col-sm-8">{{ number_format($dataset['promotion_controls']) }}</dd>

            <dt class="col-sm-4">Page metadata files</dt>
            <dd class="col-sm-8">{{ number_format($dataset['page_metadata']) }}</dd>

            <dt class="col-sm-4">Dataset manifest</dt>
            <dd class="col-sm-8"><code>{{ $dataset['manifest_path'] }}</code></dd>
        </dl>
    @else
        <div class="alert alert-warning mb-0" role="alert">
            Dataset files are not ready: {{ $dataset['error'] }}
            Export all content to create a complete validated snapshot.
        </div>
    @endif
</div>

<div class="bg-white rounded shadow-sm p-4 mt-3">
    <h2 class="h5">Included data</h2>
    <p class="mb-2">
        All Webflow CMS collections, service-area and county content, door-brand content,
        promotion settings, and validated file-backed SEO, FAQ, and schema metadata.
    </p>
    <p class="mb-0 text-muted">
        Leads, admin users, system tables, caches, and media files are not included.
        Import updates or creates records and never deletes records that are absent from files.
    </p>
</div>
