<div class="bg-white rounded shadow-sm p-4 mb-3">
  <h5 class="mb-2">Preview</h5>
  <p class="mb-3 text-muted">
    The switch above sets the hero block for every visitor. Adding
    <code>?hero=new</code> or <code>?hero=old</code> to any page overrides it in your own
    browser for 30 days, so you can compare both versions without changing what visitors see.
    Use <code>?hero=default</code> to drop that personal override and follow the setting again.
  </p>

  <div class="d-flex flex-wrap gap-2">
    <a href="/?hero=new" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
      Open new hero
    </a>
    <a href="/?hero=old" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
      Open old hero
    </a>
    <a href="/?hero=default" target="_blank" rel="noopener" class="btn btn-sm btn-link">
      Open with the site setting ({{ ($hero['is_new'] ?? true) ? 'new' : 'old' }})
    </a>
  </div>
</div>
