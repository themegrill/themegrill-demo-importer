# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

### PHP
```bash
composer run phpcs          # lint against WPEverest-Core + WordPress coding standards
composer run phpcbf         # auto-fix coding standard violations
composer run make-pot       # regenerate .pot translation file
```

### JS/TS
```bash
npm run dev         # webpack dev server on :8887 (HMR enabled)
npm run build       # production build → dist/
npm run lint        # ESLint on resources/**/*.{ts,tsx}
npm run format      # Prettier write on TS/TSX files
npm run build:routes  # regenerate TanStack Router routeTree.gen.ts
npm run watch:routes  # watch and regenerate on route file changes
```

### Dev server integration
When `TDI_DEVELOPMENT` is defined in PHP, assets load from `http://localhost:8887/` instead of `dist/`. Define it in `wp-config.php` for local development.

## Architecture

### PHP backend (`src/`)
Namespace: `ThemeGrill\Demo\Importer`. PSR-4 autoloaded from `src/`.

- **`App`** — singleton bootstrap. Wires hooks, instantiates `Admin`, `RestApi`, `ImportHooks`.
- **`Admin`** — WP admin menu, asset enqueueing, demo package fetching from remote API (cached via transient `themegrill_demo_importer_demos`, 1 week TTL), localized data passed to frontend as `__TDI_DASHBOARD__`.
- **`RestApi`** — registers REST routes under `tg-demo-importer/v1`: `/data` (GET), `/install` (POST), `/cleanup` (POST), `/activate-pro` (POST), `/localized-data` (GET).
- **`Controllers/`** — thin HTTP layer. `ImportController` delegates to `ImportService`; `SiteController` delegates to `SiteService`.
- **`Services/ImportService`** — orchestrates the multi-step import sequence: `install-plugins` → `import-content` → `import-customizer` → `import-widgets` → `complete`.
- **`Services/SiteService`** — fetches per-demo site data from remote API.
- **`Importers/`** — `ContentImporter` (WXR), `WidgetsImporter`, `ThemeModsImporter`, `PluginImporter`.
- **`Traits/Singleton`** — used by `App`, `Admin`, `RestApi`, `ImportHooks`. Pattern: private constructor calls `init()`; subclasses override `init()`.

Remote API base URLs are constants `THEMEGRILL_BASE_URL` and `ZAKRA_BASE_URL`. Namespace suffix is `TGDM_NAMESPACE`.

### React frontend (`resources/onboarding/`)
Entry: `resources/onboarding/index.tsx` → compiled to `dist/dashboard.js`.

Stack: React 18 + TanStack Router (hash history) + TanStack Query + Radix UI + Tailwind CSS (PostCSS via `dashboard.pcss`).

Route structure (file-based, auto-generated into `routeTree.gen.ts`):
- `/` → site listing (demo grid with category/pagebuilder filters)
- `/import/$theme/$id` → detail view with import wizard dialogs

`LocalizedDataContext` exposes `__TDI_DASHBOARD__` (PHP-localized) to all components.

API calls in `resources/onboarding/components/features/api/`:
- `site.api.ts` → `GET /tg-demo-importer/v1/data`
- `import.api.ts` → `POST /tg-demo-importer/v1/install` (called sequentially per step)

### Coding standards
- PHP: `WPEverest-Core` ruleset + `WordPress.WP.I18n` with text domain `themegrill-demo-importer`. Run `phpcs` before committing.
- TS path alias: `@/*` maps to `resources/*`.
- Routes added under `resources/onboarding/routes/` are auto-discovered by TanStack Router webpack plugin — run `build:routes` after adding/renaming route files.