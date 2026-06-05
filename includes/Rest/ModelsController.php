<?php

declare(strict_types=1);

namespace WordPress\NanoGptAiImageBlock\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WordPress\NanoGptAiImageBlock\Catalog\NanoGptCatalog;

/**
 * REST controller exposing the NanoGPT image model catalog to the editor.
 *
 * @since 0.1.0
 */
class ModelsController
{
    private const NAMESPACE = 'nanogpt-ai-image/v1';

    /**
     * Registers the REST routes.
     *
     * @since 0.1.0
     *
     * @return void
     */
    public function register_routes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            '/models',
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_models'],
                'permission_callback' => static function (): bool {
                    return current_user_can('edit_posts');
                },
            ]
        );
    }

    /**
     * Returns the normalized image model catalog plus provider defaults.
     *
     * @since 0.1.0
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response Response.
     */
    public function get_models(WP_REST_Request $request): WP_REST_Response
    {
        unset($request);

        return rest_ensure_response([
            'models' => (new NanoGptCatalog())->getModels(),
            'defaults' => $this->get_provider_defaults(),
        ]);
    }

    /**
     * Reads the default image model and size configured in the NanoGPT provider plugin.
     *
     * @since 0.1.1
     *
     * @return array{model: string, size: string} Provider defaults.
     */
    private function get_provider_defaults(): array
    {
        $settings = get_option('nanogpt_ai_provider_settings', []);
        if (!is_array($settings)) {
            $settings = [];
        }

        $model = isset($settings['default_image_model_id']) && is_string($settings['default_image_model_id'])
            ? $settings['default_image_model_id']
            : '';
        $size = isset($settings['default_image_size']) && is_string($settings['default_image_size'])
            ? $settings['default_image_size']
            : '';

        return ['model' => $model, 'size' => $size];
    }
}
