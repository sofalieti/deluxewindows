import fs from "node:fs/promises";
import path from "node:path";

const root = process.cwd();
const manifestPath = path.join(root, "storage", "app", "webflow-export", "current", "manifest.json");
const viewsRoot = path.join(root, "resources", "views", "webflow", "collections");

const ensureDir = async (dir) => fs.mkdir(dir, { recursive: true });

const genericIndex = `<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ data_get($collection, 'displayName', ucfirst($collectionSlug)) }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<main class="container py-4">
    <a href="/" class="btn btn-outline-secondary btn-sm mb-3">Home</a>
    <h1 class="mb-1">{{ data_get($collection, 'displayName', ucfirst($collectionSlug)) }}</h1>
    <p class="text-muted">Collection slug: <code>{{ $collectionSlug }}</code> | Items: {{ count($items) }}</p>

    <div class="row g-3 mt-2">
        @forelse($items as $item)
            @php
                $fd = $item['field_data'] ?? [];
                $name = data_get($fd, 'name', data_get($fd, 'title', data_get($fd, 'slug', 'Untitled')));
                $slug = data_get($fd, 'slug', $item['webflow_item_id'] ?? '');
            @endphp
            <div class="col-12 col-md-6 col-xl-4">
                <article class="card h-100">
                    <div class="card-body">
                        <h2 class="h6 mb-2">{{ $name }}</h2>
                        @if($slug)
                            <a href="/{{ $collectionSlug }}/{{ $slug }}" class="btn btn-sm btn-primary">Open item</a>
                        @endif
                        <pre class="small mt-3 mb-0">{{ json_encode($fd, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-warning mb-0">No items found in this collection.</div>
            </div>
        @endforelse
    </div>
</main>
</body>
</html>
`;

const genericShow = `<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ data_get($fieldData, 'name', ucfirst($itemSlug)) }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<main class="container py-4">
    <a href="/{{ $collectionSlug }}" class="btn btn-outline-secondary btn-sm mb-3">Back to collection</a>
    <h1 class="mb-1">{{ data_get($fieldData, 'name', ucfirst($itemSlug)) }}</h1>
    <p class="text-muted">
        Collection: <code>{{ $collectionSlug }}</code>,
        Slug: <code>{{ $itemSlug }}</code>
    </p>

    @if(!empty(data_get($fieldData, 'description')))
        <div class="mb-4">{!! data_get($fieldData, 'description') !!}</div>
    @endif

    @if(!empty(data_get($fieldData, 'blog-post---rich-text')))
        <section class="mb-4">{!! data_get($fieldData, 'blog-post---rich-text') !!}</section>
    @endif

    <section class="card">
        <div class="card-header">Field Data</div>
        <div class="card-body">
            <pre class="small mb-0">{{ json_encode($fieldData, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </section>
</main>
</body>
</html>
`;

const main = async () => {
  const manifestRaw = await fs.readFile(manifestPath, "utf8");
  const manifest = JSON.parse(manifestRaw);
  const collections = manifest.collections || [];

  await ensureDir(path.join(viewsRoot, "generic"));
  await fs.writeFile(path.join(viewsRoot, "generic", "index.blade.php"), genericIndex, "utf8");
  await fs.writeFile(path.join(viewsRoot, "generic", "show.blade.php"), genericShow, "utf8");

  for (const collection of collections) {
    const slug = collection.slug;
    if (!slug) continue;
    const dir = path.join(viewsRoot, slug);
    await ensureDir(dir);
    await fs.writeFile(
      path.join(dir, "index.blade.php"),
      "@include('webflow.collections.generic.index')\n",
      "utf8"
    );
    await fs.writeFile(
      path.join(dir, "show.blade.php"),
      "@include('webflow.collections.generic.show')\n",
      "utf8"
    );
  }

  console.log(
    JSON.stringify(
      { collections: collections.length, outDir: "resources/views/webflow/collections" },
      null,
      2
    )
  );
};

await main();
