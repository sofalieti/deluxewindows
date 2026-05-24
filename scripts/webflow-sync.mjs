import fs from "node:fs/promises";
import path from "node:path";

const token =
  process.env.WEBFLOW_API_TOKEN ??
  "";
const siteId = process.env.WEBFLOW_SITE_ID ?? "";
const apiBase = process.env.WEBFLOW_API_BASE_URL ?? "https://api.webflow.com/v2";

if (!token || !siteId) {
  throw new Error("WEBFLOW_API_TOKEN and WEBFLOW_SITE_ID are required.");
}

const root = process.cwd();
const exportRoot = path.join(root, "storage", "app", "webflow-export", "current");

const headers = {
  Authorization: `Bearer ${token}`,
  accept: "application/json",
};

const request = async (pathname, params = {}) => {
  const url = new URL(apiBase + pathname);
  Object.entries(params).forEach(([k, v]) => {
    if (v !== undefined && v !== null) url.searchParams.set(k, String(v));
  });
  const res = await fetch(url, { headers });
  if (!res.ok) {
    throw new Error(`Webflow API ${res.status} for ${url.toString()}`);
  }
  return res.json();
};

const safeSlug = (value, fallback = "item") => {
  const out = String(value || "")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
  return out || fallback;
};

const snake = (value) => safeSlug(value, value).replace(/-/g, "_");
const studly = (value) =>
  safeSlug(value, value)
    .split("-")
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join("");

const paginate = async (pathname, key) => {
  let offset = 0;
  let limit = 100;
  const all = [];
  while (true) {
    const payload = await request(pathname, { limit, offset });
    const chunk = payload[key] || [];
    all.push(...chunk);
    if (!payload.pagination) break;
    const total = Number(payload.pagination.total ?? all.length);
    limit = Number(payload.pagination.limit ?? limit);
    offset = Number(payload.pagination.offset ?? offset);
    if (offset + limit >= total) break;
    offset += limit;
  }
  return all;
};

const toJson = (value) => JSON.stringify(value, null, 2);
const ensureDir = async (dir) => fs.mkdir(dir, { recursive: true });

const clearDir = async (dir) => {
  await fs.rm(dir, { recursive: true, force: true });
  await ensureDir(dir);
};

const fetchPageDom = async (pageId) => {
  try {
    const first = await request(`/pages/${pageId}/dom`, { limit: 100, offset: 0 });
    let nodes = first.nodes || [];
    const pagination = first.pagination;
    if (!pagination) return nodes;
    let offset = Number(pagination.offset ?? 0);
    let limit = Number(pagination.limit ?? 100);
    const total = Number(pagination.total ?? nodes.length);
    while (offset + limit < total) {
      offset += limit;
      const next = await request(`/pages/${pageId}/dom`, { limit, offset });
      nodes = nodes.concat(next.nodes || []);
      limit = Number(next.pagination?.limit ?? limit);
    }
    return nodes;
  } catch {
    return [];
  }
};

const fieldColumnMap = (fields) => {
  const used = new Set();
  const map = {};
  for (const field of fields || []) {
    const slug = String(field.slug || "");
    if (!slug || slug === "name" || slug === "slug") continue;
    let col = `wf_${snake(slug)}`.slice(0, 55);
    let i = 1;
    while (used.has(col)) {
      col = `${col.slice(0, 50)}_${i++}`;
    }
    used.add(col);
    map[slug] = col;
  }
  return map;
};

const migrationColumn = (type, column) => {
  switch (type) {
    case "Switch":
      return `$table->boolean('${column}')->nullable();`;
    case "Number":
      return `$table->decimal('${column}', 16, 4)->nullable();`;
    case "DateTime":
      return `$table->timestamp('${column}')->nullable();`;
    case "RichText":
      return `$table->longText('${column}')->nullable();`;
    case "Image":
    case "MultiImage":
    case "File":
    case "Video":
    case "Reference":
    case "MultiReference":
    case "Option":
      return `$table->json('${column}')->nullable();`;
    default:
      return `$table->text('${column}')->nullable();`;
  }
};

const makeMigration = (table, fields) => {
  const map = fieldColumnMap(fields);
  const fieldBySlug = Object.fromEntries((fields || []).map((f) => [f.slug, f]));
  const lines = Object.entries(map).map(([slug, col]) => {
    const type = fieldBySlug[slug]?.type ?? "PlainText";
    return `            ${migrationColumn(type, col)}`;
  });

  return `<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('${table}', function (Blueprint $table) {
            $table->id();
            $table->string('webflow_item_id')->unique();
            $table->string('webflow_cms_locale_id')->nullable();
            $table->timestamp('webflow_created_on')->nullable();
            $table->timestamp('webflow_updated_on')->nullable();
            $table->timestamp('webflow_published_on')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->json('field_data')->nullable();
${lines.join("\n")}
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('${table}');
    }
};
`;
};

const makeModel = (className, table) => `<?php

namespace App\\Models\\Webflow;

use Illuminate\\Database\\Eloquent\\Model;

class ${className} extends Model
{
    protected $table = '${table}';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'field_data' => 'array',
            'is_archived' => 'boolean',
            'is_draft' => 'boolean',
        ];
    }
}
`;

const escapePhpSingle = (value) =>
  String(value).replaceAll("\\", "\\\\").replaceAll("'", "\\'");

const pageViewName = (publishedPath) => {
  const cleaned = String(publishedPath || "/").replace(/^\/+|\/+$/g, "");
  if (!cleaned) return "home";
  return cleaned
    .split("/")
    .map((p) => p.replace(/[^a-zA-Z0-9]+/g, "_").replace(/^_+|_+$/g, "").toLowerCase())
    .filter(Boolean)
    .join("_");
};

const makePageBlade = (page) => {
  const title = escapePhpSingle(page.title || "Webflow Page");
  const slug = escapePhpSingle(page.slug || "");
  const publishedPath = escapePhpSingle(page.publishedPath || "/");
  const nodeHtml = (page.domNodes || [])
    .map((n) => n?.text?.html)
    .filter((x) => typeof x === "string" && x.trim())
    .slice(0, 60)
    .map((html) => `    <section class="mb-3">{!! '${escapePhpSingle(html)}' !!}</section>`)
    .join("\n");

  return `@extends('webflow.layouts.app')

@section('title', '${title}')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">${title}</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>${slug}</code> | Path: <code>${publishedPath}</code></p>

${nodeHtml || `    <p class="text-muted">DOM content is unavailable for this page in API response.</p>`}

    @if(!empty($items))
    <section class="mt-4">
        <h2 class="h5 mb-3">Collection items</h2>
        <div class="row g-3">
            @foreach($items as $item)
            <div class="col-12 col-md-6 col-lg-4">
                <article class="card h-100">
                    <div class="card-body">
                        <h3 class="h6">{{ data_get($item, 'field_data.name', 'Untitled') }}</h3>
                        <pre class="small mb-0">{{ json_encode($item['field_data'] ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </article>
            </div>
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection
`;
};

const layoutBlade = `<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Webflow Mirror')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    @yield('content')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
`;

const run = async () => {
  await clearDir(exportRoot);
  await ensureDir(path.join(exportRoot, "site"));
  await ensureDir(path.join(exportRoot, "collections"));

  const pages = await paginate(`/sites/${siteId}/pages`, "pages");
  const pagesWithDom = [];
  for (const page of pages) {
    const domNodes = await fetchPageDom(page.id);
    pagesWithDom.push({ ...page, domNodes });
  }

  await fs.writeFile(path.join(exportRoot, "site", "pages.json"), toJson({ pages: pagesWithDom }));

  const collections = await request(`/sites/${siteId}/collections`).then((x) => x.collections || []);
  const manifestCollections = [];
  const importsDir = path.join(exportRoot, "imports");
  await ensureDir(importsDir);

  for (const collection of collections) {
    const slug = safeSlug(collection.slug || collection.displayName || collection.id, collection.id);
    const colDir = path.join(exportRoot, "collections", slug);
    await ensureDir(colDir);

    const schema = await request(`/collections/${collection.id}`);
    const items = await paginate(`/collections/${collection.id}/items`, "items");

    await fs.writeFile(path.join(colDir, "schema.json"), toJson(schema));
    await fs.writeFile(path.join(colDir, "items.json"), toJson({ items }));

    const table = `wf_${snake(slug)}`;
    const map = fieldColumnMap(schema.fields || []);
    const importPayload = {
      table,
      collectionId: collection.id,
      collectionSlug: slug,
      flattenedFieldMap: map,
      items,
    };
    await fs.writeFile(path.join(importsDir, `${slug}.json`), toJson(importPayload));

    manifestCollections.push({
      id: collection.id,
      slug,
      displayName: collection.displayName || null,
      itemsCount: items.length,
      fieldsCount: (schema.fields || []).length,
    });

    const migrationsDir = path.join(root, "database", "migrations");
    const modelsDir = path.join(root, "app", "Models", "Webflow");
    await ensureDir(modelsDir);

    const existing = await fs.readdir(migrationsDir);
    const baseName = `_create_${table}_table.php`;
    const found = existing.find((name) => name.endsWith(baseName));
    const timestamp = new Date(Date.now() + manifestCollections.length * 1000)
      .toISOString()
      .replace(/[-:TZ.]/g, "")
      .slice(0, 14);
    const migrationFile = found || `${timestamp.slice(0, 4)}_${timestamp.slice(4, 6)}_${timestamp.slice(6, 8)}_${timestamp.slice(8, 10)}${timestamp.slice(10, 12)}${timestamp.slice(12, 14)}${baseName}`;
    await fs.writeFile(path.join(migrationsDir, migrationFile), makeMigration(table, schema.fields || []));

    const modelClass = `${studly(slug)}WebflowItem`;
    await fs.writeFile(path.join(modelsDir, `${modelClass}.php`), makeModel(modelClass, table));
  }

  const manifest = {
    siteId,
    generatedAt: new Date().toISOString(),
    pagesCount: pagesWithDom.length,
    collectionsCount: manifestCollections.length,
    collections: manifestCollections,
  };
  await fs.writeFile(path.join(exportRoot, "manifest.json"), toJson(manifest));

  const viewsBase = path.join(root, "resources", "views", "webflow");
  const viewsPages = path.join(viewsBase, "pages");
  const viewsLayouts = path.join(viewsBase, "layouts");
  await ensureDir(viewsPages);
  await ensureDir(viewsLayouts);
  await fs.writeFile(path.join(viewsLayouts, "app.blade.php"), layoutBlade);

  for (const page of pagesWithDom) {
    const viewName = pageViewName(page.publishedPath || "/");
    await fs.writeFile(path.join(viewsPages, `${viewName}.blade.php`), makePageBlade(page));
  }

  console.log(
    JSON.stringify(
      {
        ok: true,
        siteId,
        exportRoot: "storage/app/webflow-export/current",
        pages: pagesWithDom.length,
        collections: manifestCollections.length,
      },
      null,
      2
    )
  );
};

await run();
