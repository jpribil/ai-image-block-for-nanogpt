<?php

declare(strict_types=1);

namespace WordPress\NanoGptAiImageBlock\Catalog;

/**
 * Fetches and normalizes the NanoGPT image model catalog for the editor.
 *
 * @since 0.1.0
 *
 * @phpstan-type NormalizedModel array{
 *     id: string,
 *     name: string,
 *     description: string,
 *     sizes: list<string>,
 *     pricing: array{currency: string, perImage: array<string, float>}
 * }
 */
class NanoGptCatalog
{
    private const ENDPOINT = 'https://nano-gpt.com/api/v1/image-models?detailed=true';
    private const TRANSIENT = 'nanogpt_ai_image_block_catalog_v1';

    /**
     * Gets the normalized list of selectable image models.
     *
     * @since 0.1.0
     *
     * @return list<NormalizedModel> Normalized models.
     */
    public function getModels(): array
    {
        $cached = get_transient(self::TRANSIENT);
        if (is_array($cached)) {
            /** @var list<NormalizedModel> $cached */
            return $cached;
        }

        $response = wp_remote_get(self::ENDPOINT, ['timeout' => 15]);
        if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) {
            return [];
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data) || !isset($data['data']) || !is_array($data['data'])) {
            return [];
        }

        $models = [];
        foreach ($data['data'] as $modelData) {
            if (!is_array($modelData) || !isset($modelData['id']) || !is_string($modelData['id'])) {
                continue;
            }
            if (!$this->isSelectable($modelData)) {
                continue;
            }

            $models[] = [
                'id' => $modelData['id'],
                'name' => $this->getName($modelData),
                'description' => $this->getDescription($modelData),
                'sizes' => $this->extractSizes($modelData),
                'pricing' => $this->extractPricing($modelData),
            ];
        }

        usort($models, static function (array $a, array $b): int {
            return strcasecmp($a['name'], $b['name']);
        });

        set_transient(self::TRANSIENT, $models, HOUR_IN_SECONDS);

        return $models;
    }

    /**
     * Checks whether an image model can generate images from a text prompt.
     *
     * @since 0.1.0
     *
     * @param array<string, mixed> $modelData Raw model data.
     * @return bool Whether the model is selectable.
     */
    private function isSelectable(array $modelData): bool
    {
        $capabilities = $modelData['capabilities'] ?? null;
        if (
            is_array($capabilities) &&
            isset($capabilities['image_generation']) &&
            empty($capabilities['image_generation'])
        ) {
            return false;
        }

        // NanoGPT marks every image model with image_generation = true, including
        // edit/upscale/inpaint models that cannot create an image from a text prompt
        // alone. Those are only distinguishable by their id/name, so filter them out.
        return !$this->isEditingOnlyModel($modelData);
    }

    /**
     * Heuristically detects models that only transform an existing image (edit,
     * upscale, inpaint, image-to-image, …) rather than generate one from text.
     *
     * The result is filterable so the heuristic can be overridden for specific models.
     *
     * @since 0.1.4
     *
     * @param array<string, mixed> $modelData Raw model data.
     * @return bool Whether the model is editing-only.
     */
    private function isEditingOnlyModel(array $modelData): bool
    {
        $id = isset($modelData['id']) && is_string($modelData['id']) ? $modelData['id'] : '';
        $name = isset($modelData['name']) && is_string($modelData['name']) ? $modelData['name'] : '';

        $pattern = '#edit|image-to-image|img2img|image to image|remix|upscal|inpaint|outpaint'
            . '|\bremove|kontext|redux|relight|restyle|recolor|background-remover|\bvariation#i';
        $isEditing = (bool) preg_match($pattern, $id . ' ' . $name);

        if (function_exists('apply_filters')) {
            $isEditing = (bool) apply_filters(
                'nanogpt_ai_image_block_is_editing_only_model',
                $isEditing,
                $modelData
            );
        }

        return $isEditing;
    }

    /**
     * Gets a model display name.
     *
     * @since 0.1.0
     *
     * @param array<string, mixed> $modelData Raw model data.
     * @return string Display name.
     */
    private function getName(array $modelData): string
    {
        return isset($modelData['name']) && is_string($modelData['name']) && $modelData['name'] !== ''
            ? $modelData['name']
            : (string) $modelData['id'];
    }

    /**
     * Gets a model description.
     *
     * @since 0.1.0
     *
     * @param array<string, mixed> $modelData Raw model data.
     * @return string Description.
     */
    private function getDescription(array $modelData): string
    {
        return isset($modelData['description']) && is_string($modelData['description'])
            ? trim($modelData['description'])
            : '';
    }

    /**
     * Extracts supported image sizes (resolutions) from a model.
     *
     * @since 0.1.0
     *
     * @param array<string, mixed> $modelData Raw model data.
     * @return list<string> Sizes.
     */
    private function extractSizes(array $modelData): array
    {
        $supportedParameters = $modelData['supported_parameters'] ?? null;
        if (!is_array($supportedParameters)) {
            return [];
        }

        $resolutions = $supportedParameters['resolutions'] ?? null;
        if (!is_array($resolutions)) {
            return [];
        }

        $sizes = [];
        foreach ($resolutions as $resolution) {
            if (is_string($resolution) && $resolution !== '') {
                $sizes[] = $resolution;
            }
        }

        return array_values(array_unique($sizes));
    }

    /**
     * Extracts per-image pricing from a model.
     *
     * @since 0.1.0
     *
     * @param array<string, mixed> $modelData Raw model data.
     * @return array{currency: string, perImage: array<string, float>} Pricing.
     */
    private function extractPricing(array $modelData): array
    {
        $empty = ['currency' => 'USD', 'perImage' => []];

        $pricing = $modelData['pricing'] ?? null;
        if (!is_array($pricing)) {
            return $empty;
        }

        $perImageRaw = $pricing['per_image'] ?? null;
        if (!is_array($perImageRaw)) {
            return $empty;
        }

        $perImage = [];
        foreach ($perImageRaw as $size => $amount) {
            if (is_string($size) && $size !== '' && is_numeric($amount)) {
                $perImage[$size] = (float) $amount;
            }
        }

        $currency = isset($pricing['currency']) && is_string($pricing['currency']) && $pricing['currency'] !== ''
            ? $pricing['currency']
            : 'USD';

        return ['currency' => $currency, 'perImage' => $perImage];
    }
}
