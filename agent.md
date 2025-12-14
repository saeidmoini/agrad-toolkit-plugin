# Agent Playbook – Agrad Toolkit

This repository powers the multi-site Agrad Toolkit plugin. Keep these rules in mind whenever you touch it:

1. **Respect the module architecture.**  
   - Core helpers live in `includes/options.php` and `includes/settings-page.php`.  
   - Each feature sits in `includes/modules/` and is conditionally loaded from `agradplugin.php`.  
   - New functionality must be gated behind a setting and belong to its own module file. Avoid anonymous functions sprinkled inside the root plugin file.

2. **Always update docs.**  
   - `README.md` mirrors the public GitHub portfolio. Summarise any new capability or behavioural change here.  
   - `agent.md` (this file) must also be updated so future agents inherit the latest constraints, toggles, and caveats.

3. **Keep HTTP hygiene.**  
   - External requests are blocked by default (`WP_HTTP_BLOCK_EXTERNAL`). Any new network call must pass through the HTTP access module and expose a way to whitelist hosts.  
   - REST is disabled for visitors by default; use the REST allow list setting (route prefixes) when a new feature needs public REST access without opening everything.
   - The Crocoblock timeout issue was fixed by stubbing update transients and whitelisting `api.crocoblock.com`. Do not regress this.
   - Shared allow/deny lists live in `config/global-config.json` and are merged into each site’s settings; keep this file updated when adding global hosts or REST prefixes.
   - REST lockdown default is now off; if enabling, whitelist needed route prefixes first.

4. **Admin UX expectations.**  
   - The main menu is **Tools → Agrad Toolkit**. All subpages for modules should live under this parent.  
   - WooCommerce utilities must degrade gracefully when WooCommerce is inactive (show notices, skip hooks when possible).
   - The login rewrite is disabled automatically when the Digits plugin is active to avoid breaking its custom login flow.

5. **Testing & validation.**  
   - The CLI environment might miss PHP, so syntax checks can fail. Default to `php -l` locally or run WordPress integration tests on a staging site if touching WooCommerce logic.  
   - When altering AJAX handlers or batch operations, test with realistic data volumes to avoid timeouts.

6. **Security defaults.**  
   - Security/performance toggles (XML-RPC, REST lockdown, `/agrad-admin` login, HTTP guard, etc.) stay enabled by default; Gutenberg disable is now opt-in. Any change must preserve or tighten these defaults.  
   - The health-check endpoint must remain authenticated by API key; never expose internal details without that guard.

7. **No orphaned files.**  
   - Legacy single-purpose plugins have been merged. Do not reintroduce standalone versions or leave unused folders in the root – it bloats deployments.

Follow these notes and keep every change consistent, documented, and toggleable.***
