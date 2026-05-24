import fs from "node:fs/promises";
import path from "node:path";
import { spawn } from "node:child_process";

const root = process.cwd();
const mapPath = path.join(root, ".firecrawl", "deluxe-map.json");
const outRoot = path.join(root, "storage", "app", "webflow-export", "current", "html");
const concurrency = Number(process.env.WEBFLOW_MIRROR_CONCURRENCY || 6);
const baseDomain = "www.deluxewindows.com";

const run = (cmd, args) =>
  new Promise((resolve, reject) => {
    const child = spawn(cmd, args, {
      stdio: ["ignore", "pipe", "pipe"],
      shell: process.platform === "win32",
    });
    let stderr = "";
    child.stderr.on("data", (d) => {
      stderr += d.toString();
    });
    child.on("close", (code) => {
      if (code === 0) return resolve();
      reject(new Error(`${cmd} ${args.join(" ")} failed (${code}) ${stderr}`));
    });
  });

const readMap = async () => {
  const raw = await fs.readFile(mapPath, "utf8");
  const data = JSON.parse(raw);
  const links = data?.data?.links || [];
  return links
    .map((l) => l.url)
    .filter((u) => typeof u === "string")
    .filter((u) => {
      try {
        const parsed = new URL(u);
        if (!["https:", "http:"].includes(parsed.protocol)) return false;
        if (!parsed.hostname.includes("deluxewindows.com")) return false;
        if (parsed.search || parsed.hash) return false;
        return true;
      } catch {
        return false;
      }
    });
};

const normalizePathname = (pathname) => {
  const cleaned = pathname.replace(/\/+/g, "/").replace(/\/$/, "");
  return cleaned === "" ? "/" : cleaned;
};

const htmlOutputPath = (url) => {
  const parsed = new URL(url);
  const pathname = normalizePathname(parsed.pathname);
  if (pathname === "/") {
    return path.join(outRoot, "index.html");
  }

  const segments = pathname
    .replace(/^\//, "")
    .split("/")
    .map((s) => s.toLowerCase());

  return path.join(outRoot, ...segments, "index.html");
};

const ensureParents = async (filePath) => {
  await fs.mkdir(path.dirname(filePath), { recursive: true });
};

const unique = (arr) => Array.from(new Set(arr));

const scrapeOne = async (url) => {
  const outPath = htmlOutputPath(url);
  try {
    await fs.access(outPath);
    return { url, outPath, skipped: true };
  } catch {
    // proceed
  }

  await ensureParents(outPath);
  const firecrawlCmd = process.platform === "win32" ? "firecrawl.cmd" : "firecrawl";
  await run(firecrawlCmd, ["scrape", url, "--html", "-o", outPath]);
  return { url, outPath, skipped: false };
};

const worker = async (queue, results) => {
  while (queue.length) {
    const url = queue.shift();
    if (!url) break;

    try {
      const done = await scrapeOne(url);
      results.push({ ...done, error: null });
    } catch (error) {
      results.push({ url, outPath: null, skipped: false, error: String(error) });
    }
  }
};

const main = async () => {
  await fs.mkdir(outRoot, { recursive: true });

  const urls = unique(await readMap());
  const queue = [...urls];
  const results = [];

  const workers = Array.from({ length: Math.max(1, concurrency) }, () => worker(queue, results));
  await Promise.all(workers);

  const routeMap = {};
  for (const entry of results) {
    if (entry.error || !entry.outPath) continue;
    const rel = path.relative(outRoot, entry.outPath).replace(/\\/g, "/");
    const parsed = new URL(entry.url);
    routeMap[normalizePathname(parsed.pathname)] = rel;
  }

  const manifestPath = path.join(outRoot, "_manifest.json");
  await fs.writeFile(
    manifestPath,
    JSON.stringify(
      {
        generatedAt: new Date().toISOString(),
        baseDomain,
        totalUrls: urls.length,
        ok: results.filter((r) => !r.error).length,
        failed: results.filter((r) => r.error).length,
        routeMap,
        errors: results.filter((r) => r.error).slice(0, 50),
      },
      null,
      2
    ),
    "utf8"
  );

  console.log(
    JSON.stringify(
      {
        totalUrls: urls.length,
        ok: results.filter((r) => !r.error).length,
        failed: results.filter((r) => r.error).length,
        outRoot: "storage/app/webflow-export/current/html",
      },
      null,
      2
    )
  );
};

await main();
