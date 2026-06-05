=== AI Image Block for NanoGPT ===
Contributors: jiri
Tags: ai, nanogpt, image-generation, gutenberg, block
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.1.3
License: GPL-2.0-or-later
License URI: https://spdx.org/licenses/GPL-2.0-or-later.html

A Gutenberg block that generates an image with NanoGPT from a prompt, model, and size.

== Description ==

Adds an "AI Image (NanoGPT)" block to the editor. Describe the image, pick a NanoGPT model and size (with an estimated price), and generate a single image that is saved to the media library. The prompt, model, and size are stored with the attachment so you can return to the block and regenerate with different settings.

Requires the WordPress AI Client (WordPress 7.0+) and the companion "nano-gpt.com AI" provider plugin with a NanoGPT API key configured under Settings > Connectors.

== Installation ==

1. Build the block assets with `npm install` and `npm run build`.
2. Upload the plugin directory to `/wp-content/plugins/ai-image-block-for-nanogpt/`.
3. Activate the plugin and ensure the NanoGPT provider plugin is active with an API key.

== Changelog ==

= 0.1.3 =
* Add Tags and Tested up to headers to the readme for the WordPress.org plugin directory.

= 0.1.2 =
* Rename the plugin to "AI Image Block for NanoGPT" to follow the WordPress.org "X for Y" naming guideline.

= 0.1.1 =
* Pre-fill the block's model and image size from the NanoGPT provider's configured defaults (Settings > NanoGPT).

= 0.1.0 =
* Initial release: AI Image (NanoGPT) block with prompt, model, and size selection, estimated price, single-image generation, media library import, and regeneration.
