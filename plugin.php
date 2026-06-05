<?php

/**
 * Plugin Name: AI Image Block for NanoGPT
 * Plugin URI: https://nano-gpt.com/
 * Description: Gutenberg block that generates an image with NanoGPT from a prompt, model, and size, and stores the generation parameters for later regeneration.
 * Requires at least: 7.0
 * Requires PHP: 7.4
 * Version: 0.1.3
 * Author: Jiri
 * License: GPL-2.0-or-later
 * License URI: https://spdx.org/licenses/GPL-2.0-or-later.html
 * Text Domain: ai-image-block-for-nanogpt
 * Domain Path: /languages
 *
 * @package WordPress\NanoGptAiImageBlock
 */

declare(strict_types=1);

namespace WordPress\NanoGptAiImageBlock;

use WordPress\NanoGptAiImageBlock\Rest\GenerateController;
use WordPress\NanoGptAiImageBlock\Rest\ModelsController;

if (!defined('ABSPATH')) {
    return;
}

require_once __DIR__ . '/includes/autoload.php';

const VERSION = '0.1.0';

/**
 * Loads plugin translations.
 *
 * @since 0.1.0
 *
 * @return void
 */
function load_textdomain(): void
{
    load_plugin_textdomain(
        'ai-image-block-for-nanogpt',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}

add_action('init', __NAMESPACE__ . '\\load_textdomain');

/**
 * Registers the block type and wires up editor script translations.
 *
 * @since 0.1.0
 *
 * @return void
 */
function register_block(): void
{
    $buildDir = __DIR__ . '/build';
    if (!is_dir($buildDir) || !file_exists($buildDir . '/block.json')) {
        return;
    }

    $blockType = register_block_type($buildDir);
    if (!$blockType) {
        return;
    }

    $languagesPath = plugin_dir_path(__FILE__) . 'languages';
    foreach ((array) $blockType->editor_script_handles as $handle) {
        wp_set_script_translations((string) $handle, 'ai-image-block-for-nanogpt', $languagesPath);
    }
}

add_action('init', __NAMESPACE__ . '\\register_block');

/**
 * Registers REST API routes.
 *
 * @since 0.1.0
 *
 * @return void
 */
function register_rest_routes(): void
{
    (new ModelsController())->register_routes();
    (new GenerateController())->register_routes();
}

add_action('rest_api_init', __NAMESPACE__ . '\\register_rest_routes');
