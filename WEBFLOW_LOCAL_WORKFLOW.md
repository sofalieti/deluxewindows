# Webflow Local Files Workflow

This project supports two modes:

1. Pull from Webflow API once, generate code artifacts.
2. Work only with local JSON files (edit with AI), then import/export without Webflow API.

## 1) Initial pull from Webflow

```bash
php artisan webflow:sync --site-id=YOUR_SITE_ID --token=YOUR_TOKEN
```

Result files:

- `webflow-data/current/site/pages.json`
- `webflow-data/current/collections/<slug>/schema.json`
- `webflow-data/current/collections/<slug>/items.json`
- `webflow-data/current/imports/<slug>.json`
- `webflow-data/current/manifest.json`

Also generated:

- `database/migrations/*_create_wf_*_table.php`
- `app/Models/Webflow/*.php`
- `resources/views/webflow/pages/*.blade.php`

## 2) Local-only workflow (no API calls)

Generate/update code from local files:

```bash
php artisan webflow:local generate
```

Import local JSON into DB:

```bash
php artisan webflow:local import --with-migrate
```

Export DB content back to local editable files:

```bash
php artisan webflow:local export
```

Run full local cycle:

```bash
php artisan webflow:local all --with-migrate
```

## Editable files for AI

Primary editable files:

- `webflow-data/current/imports/<slug>.json`

Optional export snapshots from DB:

- `webflow-data/current/collections/<slug>/items.local.json`

After editing JSON files, run:

```bash
php artisan webflow:local import
```
