# Agrad Toolkit

Agrad Toolkit is a single, composable WordPress plugin that bundles the recurring tweaks we ship across Agrad projects. It focuses on security hardening, performance optimisations, operational monitoring, and a toolbox of WooCommerce utilities that used to live in seven separate plugins.

## Highlights
- **Security & performance layer** – disable XML-RPC, hide the WP version, force `font-display: swap` in Elementor Pro, remove dashboard noise, optionally disable Gutenberg, and short‑circuit core update calls so third‑party dashboards stop timing out. Each control can be switched on/off from the settings screen.
- **Customisable HTTP & REST guard** – when `WP_HTTP_BLOCK_EXTERNAL` is true you can still allow or deny outbound hosts from *Tools → Agrad Toolkit*. Requests to the allow list bypass WordPress’ block (e.g. `api.crocoblock.com` by default) while the block list is always enforced. You can also keep the REST API disabled for visitors but whitelist specific route prefixes (e.g. `/wp-rocket/`, `/wc/`).
- **Operational visibility** – exposes `/wp-health-check/v1/status?api_key=…` for uptime probes and keeps the hardened REST API disabled for visitors everywhere else.
- **Admin hardening** – rewrites `wp-login.php` to `/agrad-admin` (toggleable) and ships the legacy comment customisations used on our Persian sites. Disable the rewrite manually if another login plugin conflicts.
- **WooCommerce toolbox** – the old standalone plugins now live as modules: term transfer assistant, bulk text replacer (posts, meta, Elementor), global discount remover, catalog/maintenance toggle, products without featured images report, post type copier, and custom product sorting.

## Installation
1. Drop the plugin folder into `wp-content/plugins/agrad-toolkit`.
2. Activate it from the WordPress plugins screen.
3. Visit **Tools → Agrad Toolkit** to review defaults and enable/disable modules.

The activation hook seeds sensible defaults: security/performance features, health check, comment tweaks, HTTP guard, and the `/agrad-admin` login rewrite are enabled. WooCommerce utilities start disabled until you flip the relevant switches.
REST lockdown is now off by default; enable it only after adding the necessary REST allowlist prefixes for your stack.

## Configuration
- Use the settings panel to toggle modules. Every switch is persisted inside `agrad_settings` so deployments stay idempotent.
- When REST lockdown is enabled, use the REST allow list textarea to permit selected route prefixes for visitors (one prefix per line, with a leading `/`).
- Manage allow/deny HTTP hosts with one host per line. This works in conjunction with the `WP_HTTP_BLOCK_EXTERNAL` constant already present on our servers.
- To push shared allow/block lists across all sites, edit `config/global-config.json` in this plugin and deploy. Its REST prefixes and HTTP hosts are merged into per-site settings and shown in the UI.
- The text replacer keeps the last 20 changes logged inside the database (`agrad_text_replacer_logs`).
- Product maintenance logs live under `agrad_product_status_logs` and can be cleared from the UI.
- The catalog-only toggle hooks into WooCommerce’s purchasability checks without touching stock.
- Custom sorting registers a `agrad_custom_sort` shortcode that can be dropped into Elementor templates or WooCommerce archive pages.
- The health endpoint uses the shared API key from our infrastructure vault. Rotate it here if needed and update external probes accordingly.

## Development Notes
- All modules live under `includes/modules/` and should stay self-contained. When adding new functionality, follow the module pattern and gate it behind a setting to avoid loading unused code.
- Keep `README.md` **and** `agent.md` in sync with every behavioural change so the GitHub portfolio mirrors production behaviour.
- The plugin intentionally avoids network requests during admin loads. If you add a feature that calls remote APIs, respect the HTTP guard and expose host toggles when necessary.
- The repository expects ASCII files and no auto-generated formatting. Use `apply_patch` for manual edits and keep inline documentation brief.

## Support Commands
Because the CLI sandbox that runs automated checks often lacks PHP, verify syntax locally with:

```bash
php -l agradplugin.php
php -l includes/modules/*.php
```

When WooCommerce-specific modules are enabled, smoke test them on a staging site – most features rely on ajax actions and batch WP_Query loops that can only be validated in WordPress.***
