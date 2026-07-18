<div class="bg-white rounded shadow-sm p-4">
    @if($sitemap['exists'])
        <dl class="row mb-0">
            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9 text-success">Saved</dd>

            <dt class="col-sm-3">URLs</dt>
            <dd class="col-sm-9">{{ number_format($sitemap['url_count']) }}</dd>

            <dt class="col-sm-3">File size</dt>
            <dd class="col-sm-9">{{ number_format($sitemap['size'] / 1024, 1) }} KB</dd>

            <dt class="col-sm-3">Last generated</dt>
            <dd class="col-sm-9">{{ $sitemap['updated_at'] }}</dd>

            <dt class="col-sm-3">Public URL</dt>
            <dd class="col-sm-9">
                <a href="{{ $sitemap['url'] }}" target="_blank" rel="noopener">
                    {{ $sitemap['url'] }}
                </a>
            </dd>

            <dt class="col-sm-3">Saved file</dt>
            <dd class="col-sm-9"><code>{{ $sitemap['path'] }}</code></dd>
        </dl>
    @else
        <p class="mb-0 text-muted">
            Sitemap has not been generated yet. Use the button above to create and save it.
        </p>
    @endif
</div>
