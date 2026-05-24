import fs from "node:fs/promises";
import path from "node:path";

const root = process.cwd();
const mapPath = path.join(root, ".firecrawl", "deluxe-map.json");
const viewsRoot = path.join(root, "resources", "views", "webflow", "mirror");
const manifestOut = path.join(root, "storage", "app", "webflow-export", "current", "mirror-routes.json");
const concurrency = Number(process.env.WEBFLOW_MATERIALIZE_CONCURRENCY || 10);

const ensureDir = async (dir) => fs.mkdir(dir, { recursive: true });
const domainPattern = /https?:\/\/(?:www\.)?deluxewindows\.com/gi;

const normalizePath = (urlPath) => {
  const cleaned = `/${String(urlPath || "").replace(/^\/+|\/+$/g, "")}`;
  return cleaned === "/" ? "/" : cleaned.replace(/\/+$/g, "");
};

const toViewNameAndFile = (normalizedPath) => {
  if (normalizedPath === "/") {
    return {
      viewName: "webflow.mirror.home",
      filePath: path.join(viewsRoot, "home.blade.php"),
    };
  }

  const segments = normalizedPath
    .slice(1)
    .split("/")
    .map((s) => s.toLowerCase())
    .map((s) => s.replace(/[^a-z0-9_-]/g, "-"))
    .map((s) => s.replace(/-+/g, "-"))
    .map((s) => s.replace(/^-+|-+$/g, ""))
    .filter(Boolean);

  const leaf = segments.length ? segments[segments.length - 1] : "page";
  const dirs = segments.slice(0, -1);
  const filePath = path.join(viewsRoot, ...dirs, `${leaf}.blade.php`);
  const viewName = `webflow.mirror.${[...dirs, leaf].join(".")}`;

  return { viewName, filePath };
};

const bladeFromHtml = (html) => {
  const normalized = String(html || "").replace(domainPattern, "");
  return `@php
echo <<<'HTML'
${normalized}
HTML;
@endphp
`;
};

const loadUrls = async () => {
  const raw = await fs.readFile(mapPath, "utf8");
  const payload = JSON.parse(raw);
  const links = payload?.data?.links || [];
  const urls = links
    .map((x) => x?.url)
    .filter((x) => typeof x === "string")
    .filter((x) => {
      try {
        const u = new URL(x);
        if (!["http:", "https:"].includes(u.protocol)) return false;
        if (!u.hostname.includes("deluxewindows.com")) return false;
        if (u.search || u.hash) return false;
        return true;
      } catch {
        return false;
      }
    });
  return [...new Set(urls)];
};

const fetchHtml = async (url) => {
  const res = await fetch(url, {
    headers: { "User-Agent": "Mozilla/5.0 (compatible; WebflowMaterializer/1.0)" },
  });
  if (!res.ok) {
    throw new Error(`HTTP ${res.status}`);
  }
  const body = await res.text();
  if (!body.toLowerCase().includes("<html")) {
    throw new Error("Invalid HTML response");
  }
  return body;
};

const worker = async (queue, results) => {
  while (queue.length > 0) {
    const url = queue.shift();
    if (!url) break;

    try {
      const parsed = new URL(url);
      const normalizedPath = normalizePath(parsed.pathname);
      const { viewName, filePath } = toViewNameAndFile(normalizedPath);
      const html = await fetchHtml(url);

      await ensureDir(path.dirname(filePath));
      await fs.writeFile(filePath, bladeFromHtml(html), "utf8");

      results.push({
        ok: true,
        url,
        path: normalizedPath,
        viewName,
        filePath: path.relative(root, filePath).replace(/\\/g, "/"),
      });
    } catch (error) {
      results.push({
        ok: false,
        url,
        error: String(error?.message || error),
      });
    }
  }
};

const main = async () => {
  await ensureDir(viewsRoot);
  await ensureDir(path.dirname(manifestOut));

  const urls = await loadUrls();
  const queue = [...urls];
  const results = [];

  const workers = Array.from({ length: Math.max(1, concurrency) }, () => worker(queue, results));
  await Promise.all(workers);

  const routeMap = {};
  for (const row of results) {
    if (!row.ok) continue;
    routeMap[row.path] = row.viewName;
  }

  const output = {
    generatedAt: new Date().toISOString(),
    total: urls.length,
    ok: results.filter((x) => x.ok).length,
    failed: results.filter((x) => !x.ok).length,
    routeMap,
    failures: results.filter((x) => !x.ok).slice(0, 200),
  };

  await fs.writeFile(manifestOut, JSON.stringify(output, null, 2), "utf8");

  console.log(
    JSON.stringify(
      {
        total: output.total,
        ok: output.ok,
        failed: output.failed,
        manifest: "storage/app/webflow-export/current/mirror-routes.json",
      },
      null,
      2
    )
  );
};

await main();
