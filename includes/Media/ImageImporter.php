<?php

declare(strict_types=1);

namespace WordPress\NanoGptAiImageBlock\Media;

use WP_Error;
use WordPress\AiClient\Files\DTO\File;

/**
 * Imports a generated image File into the WordPress media library.
 *
 * @since 0.1.0
 */
class ImageImporter
{
    /**
     * Imports the generated image and records its generation parameters.
     *
     * @since 0.1.0
     *
     * @param File                                              $file   Generated image file.
     * @param array{prompt: string, model: string, size: string} $params Generation parameters.
     * @return int|WP_Error Attachment ID, or an error.
     */
    public function import(File $file, array $params)
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachmentId = $file->isRemote()
            ? $this->importRemote($file)
            : $this->importInline($file);

        if (is_wp_error($attachmentId)) {
            return $attachmentId;
        }

        $this->saveMeta($attachmentId, $params);

        return $attachmentId;
    }

    /**
     * Imports an inline (base64) image file.
     *
     * @since 0.1.0
     *
     * @param File $file Inline image file.
     * @return int|WP_Error Attachment ID, or an error.
     */
    private function importInline(File $file)
    {
        $base64 = $file->getBase64Data();
        if (!is_string($base64) || $base64 === '') {
            return new WP_Error(
                'nanogpt_empty_image',
                __('The generated image contained no data.', 'ai-image-block-for-nanogpt')
            );
        }

        $bytes = base64_decode($base64, true);
        if ($bytes === false) {
            return new WP_Error(
                'nanogpt_invalid_image',
                __('The generated image could not be decoded.', 'ai-image-block-for-nanogpt')
            );
        }

        $extension = $this->extensionForMimeType($file->getMimeType());
        $filename = $this->buildFilename($extension);

        $tmp = wp_tempnam($filename);
        if (!$tmp) {
            return new WP_Error(
                'nanogpt_tmp_failed',
                __('Could not create a temporary file for the image.', 'ai-image-block-for-nanogpt')
            );
        }

        if (file_put_contents($tmp, $bytes) === false) {
            @unlink($tmp);
            return new WP_Error(
                'nanogpt_write_failed',
                __('Could not write the generated image to disk.', 'ai-image-block-for-nanogpt')
            );
        }

        return $this->sideload($tmp, $filename);
    }

    /**
     * Imports a remote (URL) image file.
     *
     * @since 0.1.0
     *
     * @param File $file Remote image file.
     * @return int|WP_Error Attachment ID, or an error.
     */
    private function importRemote(File $file)
    {
        $url = $file->getUrl();
        if (!is_string($url) || $url === '') {
            return new WP_Error(
                'nanogpt_empty_image',
                __('The generated image had no URL.', 'ai-image-block-for-nanogpt')
            );
        }

        $tmp = download_url($url);
        if (is_wp_error($tmp)) {
            return $tmp;
        }

        $path = (string) wp_parse_url($url, PHP_URL_PATH);
        $filename = $path !== '' ? basename($path) : $this->buildFilename('png');

        return $this->sideload($tmp, $filename);
    }

    /**
     * Sideloads a temporary file into the media library.
     *
     * @since 0.1.0
     *
     * @param string $tmp      Temporary file path.
     * @param string $filename Desired file name.
     * @return int|WP_Error Attachment ID, or an error.
     */
    private function sideload(string $tmp, string $filename)
    {
        $fileArray = ['name' => $filename, 'tmp_name' => $tmp];

        $attachmentId = media_handle_sideload($fileArray, 0);
        if (is_wp_error($attachmentId)) {
            @unlink($tmp);
            return $attachmentId;
        }

        return (int) $attachmentId;
    }

    /**
     * Saves generation parameters as attachment meta.
     *
     * @since 0.1.0
     *
     * @param int                                               $attachmentId Attachment ID.
     * @param array{prompt: string, model: string, size: string} $params       Generation parameters.
     * @return void
     */
    private function saveMeta(int $attachmentId, array $params): void
    {
        update_post_meta($attachmentId, '_wp_attachment_image_alt', sanitize_text_field($params['prompt']));
        update_post_meta($attachmentId, '_nanogpt_ai_prompt', sanitize_textarea_field($params['prompt']));
        update_post_meta($attachmentId, '_nanogpt_ai_model', sanitize_text_field($params['model']));
        update_post_meta($attachmentId, '_nanogpt_ai_size', sanitize_text_field($params['size']));
        update_post_meta($attachmentId, '_nanogpt_ai_provider', 'nanogpt');
        update_post_meta($attachmentId, '_nanogpt_ai_generated_at', gmdate('c'));
    }

    /**
     * Maps a MIME type to a file extension.
     *
     * @since 0.1.0
     *
     * @param string $mimeType MIME type.
     * @return string Extension without a dot.
     */
    private function extensionForMimeType(string $mimeType): string
    {
        $map = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        return $map[strtolower($mimeType)] ?? 'png';
    }

    /**
     * Builds a unique file name for a generated image.
     *
     * @since 0.1.0
     *
     * @param string $extension File extension without a dot.
     * @return string File name.
     */
    private function buildFilename(string $extension): string
    {
        return 'nanogpt-' . gmdate('Ymd-His') . '-' . wp_generate_password(6, false, false) . '.' . $extension;
    }
}
