# SDK Stats Integration — Design Spec

**Date:** 2026-05-26
**Plugin:** themegrill-demo-importer (Starter Templates & Sites Pack)
**Scope:** Wire ThemeGrill SDK into the plugin for site health tracking, import analytics, Formbricks survey, and deactivation feedback.

---

## Overview

The SDK (`themegrill/themegrill-sdk ^1.0`) is already installed as a Composer dependency and loaded via `vendor/autoload.php`. It is not yet registered or configured. This spec adds a single `Stats` class that connects the plugin to all four SDK capabilities.

---

## Architecture

### New file: `src/Stats.php`

- Namespace: `ThemeGrill\Demo\Importer`
- Uses `Singleton` trait (same pattern as `Admin`, `RestApi`, `ImportHooks`)
- Instantiated from `App::init_hooks()` alongside existing singletons

### Modified file: `src/Services/ImportService.php`

- After successful import, persist demo slug to `_tgdm_imported_demos` option (array, auto-deduplicated)

### Modified file: `src/App.php`

- One added line: `Stats::instance();` inside `init_hooks()`

---

## Components

### 1. Product Registration

```php
add_filter( 'themegrill_sdk_products', [ $this, 'register_product' ] );
```

Appends `TGDM_PLUGIN_FILE` to the products array. Required for all SDK modules to activate for this plugin.

### 2. Logger — Site Health + Import Data

SDK Logger module handles the opt-in banner and scheduling automatically once the product is registered.

Custom data added via filter:

```php
add_filter( '{product_key}_logger_data', [ $this, 'logger_data' ] );
```

Payload appended to the standard Logger POST body:

```json
{
  "active_theme": "zakra",
  "total_imports": 3,
  "imported_demos": ["zakra-business", "colormag-news", "spacious-agency"]
}
```

Standard fields sent by SDK automatically (no extra code needed):
- `site` — site URL
- `wp_version`, `locale`, `install_time`
- `environment.theme` — active theme name/author
- `environment.plugins` — active plugins list

### 3. Formbricks Survey

Fires on the plugin's own admin screen (`toplevel_page_tg-starter-templates`).

```php
add_action( 'admin_enqueue_scripts', [ $this, 'declare_internal_page' ] );
add_filter( 'themegrill-sdk/survey/themegrill-demo-importer', [ $this, 'configure_formbricks' ], 10, 2 );
```

`declare_internal_page` fires `do_action( 'themegrill_internal_page', 'themegrill-demo-importer', $page )` only when the current page is the plugin's admin screen. Screen ID varies by context: `appearance_page_tg-starter-templates` (under Appearance) or `toplevel_page_tg-starter-templates` (top-level menu). Check via `isset( $_GET['page'] ) && 'tg-starter-templates' === sanitize_text_field( $_GET['page'] )` to cover both cases.

`configure_formbricks` returns:

```php
[
    'environmentId' => self::FORMBRICKS_ENV_ID,
    'attributes'    => [
        'install_days_number' => (int) $this->get_install_days(),
        'is_premium'          => false,
        'total_imports'       => count( get_option( '_tgdm_imported_demos', [] ) ),
        'imported_demos'      => implode( ',', get_option( '_tgdm_imported_demos', [] ) ),
    ],
]
```

**Environment ID:** `const FORMBRICKS_ENV_ID = 'TODO';` — replace before deploying.

Install days derived from `_tgdm_activation_time` option. `Activator::activate()` is currently empty — it must be updated to call `update_option( '_tgdm_activation_time', time() )` on first activation. Falls back to `time()` if option missing (new install).

### 4. Deactivation Survey Labels

Runs at `init` hook, after checking `ThemeGrillSDK\Loader` class exists:

```php
\ThemeGrillSDK\Loader::$labels['uninstall']['heading_plugin'] = __(
    'Why are you deactivating Starter Templates?',
    'themegrill-demo-importer'
);
\ThemeGrillSDK\Loader::$labels['uninstall']['options'] = array_merge(
    \ThemeGrillSDK\Loader::$labels['uninstall']['options'],
    [
        [ 'id' => 'no_templates',     'label' => "Couldn't find templates I needed" ],
        [ 'id' => 'import_failed',    'label' => 'Import failed or errored' ],
        [ 'id' => 'not_needed',       'label' => 'No longer need it' ],
        [ 'id' => 'compatibility',    'label' => 'Compatibility issue with theme or plugin' ],
        [ 'id' => 'found_alternative','label' => 'Found a better alternative' ],
    ]
);
```

---

## Data Flow

```
ImportService::finalize()
    → update_option('_tgdm_imported_demos', [...slugs])

Stats::logger_data()
    → reads _tgdm_imported_demos
    → returns { active_theme, total_imports, imported_demos[] }
    → SDK Logger POSTs to https://api.themegrill.com/tracking/log  (opt-in only)

Stats::declare_internal_page()  [admin_enqueue_scripts]
    → screen check: toplevel_page_tg-starter-templates
    → do_action('themegrill_internal_page', 'themegrill-demo-importer', $page)
    → SDK ScriptLoader::load_survey_for_product()
    → applies filter 'themegrill-sdk/survey/themegrill-demo-importer'
    → Stats::configure_formbricks() returns environmentId + attributes
    → SDK enqueues survey JS + localizes tgsdk_survey_data
```

---

## Import Tracking (ImportService change)

**File:** `src/Services/ImportService.php`

After line 86 (successful import log), add:

```php
$imported   = get_option( '_tgdm_imported_demos', [] );
$imported[] = $demo_config['slug'];
update_option( '_tgdm_imported_demos', array_unique( $imported ), false );
```

`false` autoload flag — only needed by stats, not on every page load.

---

## Error Handling

- Deactivation label mutation guarded by `class_exists( 'ThemeGrillSDK\Loader' )` check
- Formbricks configure returns empty array if `$page_slug` is empty (SDK skips enqueue)
- Logger data filter always returns array — no fatal if option missing (defaults to `[]`)
- Import tracking wrapped in existing successful-import code path only

---

## Out of Scope

- No Formbricks account creation — placeholder env ID only
- No tracking JS (SDK's `tgTrk` tracking.js) — Logger module covers server-side stats
- No changes to REST API or frontend React code
- No new admin UI

---

## Files Changed

| File | Change |
|------|--------|
| `src/Stats.php` | **New** — ~110 lines |
| `src/App.php` | +1 line: `Stats::instance();` in `init_hooks()` |
| `src/Services/ImportService.php` | +3 lines: persist demo slug after import |
| `src/Activator.php` | +1 line: `update_option( '_tgdm_activation_time', time() )` in `activate()` |
