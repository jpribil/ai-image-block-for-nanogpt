# AI Image Block for NanoGPT

A Gutenberg block that generates an image with [NanoGPT](https://nano-gpt.com/) through the WordPress AI Client — with model selection, image size, and a live price estimate. Always generates **one** image.

> ⏳ **Status:** submitted to the [WordPress.org plugin directory](https://wordpress.org/plugins/) and awaiting review. **Not yet officially published there.** Until then, install the **[latest release ZIP](https://github.com/jpribil/ai-image-block-for-nanogpt/releases)** via *Plugins → Add New → Upload Plugin* (or clone this repo into `wp-content/plugins/`). This note will be replaced with the official WordPress.org listing once it goes live.

> 🧩 **Requires the companion provider:** **[nano-gpt.com AI (provider)](https://github.com/jpribil/ai-provider-for-nanogpt)** must be installed and active — it registers NanoGPT with the WordPress AI Client and stores your API key and default model/size (which this block pre-fills). These two plugins are designed to work together: the provider is the backend, this block is the editor UI.

> 💸 **Affiliate link (optional):** signing up via **[nano-gpt.com/r/VLX8bWbQ](https://nano-gpt.com/r/VLX8bWbQ)** supports this plugin's author at no extra cost to you. **This is an affiliate link and is entirely optional** — if you'd rather not use it, the plain link works exactly the same: **[nano-gpt.com](https://nano-gpt.com/)**.

## Requirements

- WordPress 7.0+ (bundles the AI Client).
- The companion **[nano-gpt.com AI provider](https://github.com/jpribil/ai-provider-for-nanogpt)** plugin, active, with a NanoGPT API key configured under **Settings → Connectors**.
- PHP 7.4+.
- Node 18+ / npm (build only).

## How it works

1. The block (`nanogpt/ai-image`) renders a form: prompt, model, image size, and a live estimated price.
2. On **Generate**, the block calls the plugin's REST route `POST /nanogpt-ai-image/v1/generate`.
3. Server-side, the request goes through the core AI Client:
   `AiClient::prompt($prompt)->usingModelPreference($model)->usingModelConfig(size)->generateImage()`.
4. The resulting image (inline base64 or remote URL) is imported into the **Media Library**.
5. The prompt, model, and size are stored both as **block attributes** (to edit/regenerate in the editor) and as **attachment meta** (`_nanogpt_ai_*`) for provenance.
6. **Regenerate** runs again with the current settings and produces a *new* attachment (the previous one is kept).

The model catalog (names, sizes, per-size pricing) is served to the editor from `GET /nanogpt-ai-image/v1/models`, cached for an hour.

## Build

```bash
npm install
npm run build      # outputs to build/
# dev: npm start
```

PHP dev tooling:

```bash
composer install
composer run lint
composer run stan
```

## Internationalization

PHP strings are translated via the bundled `.mo` files (cs_CZ, de_DE, en_US).

The **editor (JS) strings** require WordPress's JSON translation files. Generate them on a machine with WP-CLI after building:

```bash
wp i18n make-pot . languages/ai-image-block-for-nanogpt.pot
wp i18n make-json languages --no-purge
```

Until those `.json` files exist, the editor UI shows English; the PHP-side messages are already localized.

## Notes

- Single image per generation by design.
- This plugin does not bundle the AI Client SDK; it relies on WordPress 7.0 core and the NanoGPT provider plugin at runtime.
