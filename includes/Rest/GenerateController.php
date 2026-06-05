<?php

declare(strict_types=1);

namespace WordPress\NanoGptAiImageBlock\Rest;

use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WordPress\AiClient\AiClient;
use WordPress\AiClient\Providers\Models\DTO\ModelConfig;
use WordPress\NanoGptAiImageBlock\Media\ImageImporter;

/**
 * REST controller that generates an image and stores it in the media library.
 *
 * @since 0.1.0
 */
class GenerateController
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
            '/generate',
            [
                'methods' => 'POST',
                'callback' => [$this, 'generate'],
                'permission_callback' => static function (): bool {
                    return current_user_can('upload_files');
                },
                'args' => [
                    'prompt' => ['type' => 'string', 'required' => true],
                    'model' => ['type' => 'string', 'required' => true],
                    'size' => ['type' => 'string', 'required' => false],
                ],
            ]
        );
    }

    /**
     * Generates a single image and imports it into the media library.
     *
     * @since 0.1.0
     *
     * @param WP_REST_Request $request REST request.
     * @return WP_REST_Response|WP_Error Response, or an error.
     */
    public function generate(WP_REST_Request $request)
    {
        if (!class_exists(AiClient::class)) {
            return new WP_Error(
                'nanogpt_ai_client_missing',
                __('The WordPress AI Client is not available.', 'ai-image-block-for-nanogpt'),
                ['status' => 501]
            );
        }

        $prompt = trim((string) $request->get_param('prompt'));
        $model = trim((string) $request->get_param('model'));
        $size = trim((string) $request->get_param('size'));

        if ($prompt === '') {
            return new WP_Error(
                'nanogpt_missing_prompt',
                __('A prompt is required.', 'ai-image-block-for-nanogpt'),
                ['status' => 400]
            );
        }
        if ($model === '') {
            return new WP_Error(
                'nanogpt_missing_model',
                __('A model is required.', 'ai-image-block-for-nanogpt'),
                ['status' => 400]
            );
        }

        try {
            $builder = AiClient::prompt($prompt)->usingModelPreference($model);
            if ($size !== '') {
                $builder = $builder->usingModelConfig(
                    ModelConfig::fromArray(['customOptions' => ['size' => $size]])
                );
            }
            $file = $builder->generateImage();
        } catch (Throwable $e) {
            return new WP_Error(
                'nanogpt_generate_failed',
                $e->getMessage(),
                ['status' => 502]
            );
        }

        $attachmentId = (new ImageImporter())->import(
            $file,
            ['prompt' => $prompt, 'model' => $model, 'size' => $size]
        );
        if (is_wp_error($attachmentId)) {
            return $attachmentId;
        }

        return rest_ensure_response([
            'attachmentId' => $attachmentId,
            'url' => wp_get_attachment_url($attachmentId),
            'alt' => (string) get_post_meta($attachmentId, '_wp_attachment_image_alt', true),
        ]);
    }
}
