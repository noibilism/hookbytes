<?php

namespace App\Services;

use App\Models\WebhookEndpoint;
use App\Models\WebhookTransformation;
use Illuminate\Support\Facades\Log;
use Exception;

class WebhookTransformationService
{
    /**
     * Apply all active transformations to a payload
     */
    public function applyTransformations(WebhookEndpoint $endpoint, array $payload, array $headers = []): array
    {
        $transformations = $endpoint->activeTransformations;
        
        if ($transformations->isEmpty()) {
            return $payload;
        }

        $transformedPayload = $payload;

        foreach ($transformations as $transformation) {
            if ($transformation->shouldApply($transformedPayload, $headers)) {
                try {
                    $transformedPayload = $this->applyTransformation($transformation, $transformedPayload, $headers);
                    
                    Log::info('Transformation applied successfully', [
                        'transformation_id' => $transformation->id,
                        'transformation_name' => $transformation->name,
                        'endpoint_id' => $endpoint->id,
                    ]);
                } catch (Exception $e) {
                    Log::error('Transformation failed', [
                        'transformation_id' => $transformation->id,
                        'transformation_name' => $transformation->name,
                        'endpoint_id' => $endpoint->id,
                        'error' => $e->getMessage(),
                    ]);
                    
                    // Continue with other transformations even if one fails
                    continue;
                }
            }
        }

        return $transformedPayload;
    }

    /**
     * Apply a single transformation
     */
    private function applyTransformation(WebhookTransformation $transformation, array $payload, array $headers): array
    {
        return match ($transformation->type) {
            'field_mapping' => $this->applyFieldMapping($transformation, $payload),
            'template' => $this->applyTemplate($transformation, $payload, $headers),
            'javascript' => $this->applyJavaScript($transformation, $payload, $headers),
            'jq' => $this->applyJq($transformation, $payload),
            default => throw new Exception("Unsupported transformation type: {$transformation->type}"),
        };
    }

    /**
     * Apply field mapping transformation
     */
    private function applyFieldMapping(WebhookTransformation $transformation, array $payload): array
    {
        $rules = $transformation->transformation_rules;
        $mappings = $rules['mappings'] ?? [];
        $result = [];

        foreach ($mappings as $mapping) {
            $sourceField = $mapping['source'] ?? '';
            $targetField = $mapping['target'] ?? '';
            $defaultValue = $mapping['default'] ?? null;
            $transform = $mapping['transform'] ?? null;

            if (empty($targetField)) {
                continue;
            }

            $value = data_get($payload, $sourceField, $defaultValue);

            // Apply field-level transformations
            if ($transform && $value !== null) {
                $value = $this->applyFieldTransform($value, $transform);
            }

            data_set($result, $targetField, $value);
        }

        // Merge with original payload if specified
        if ($rules['merge_with_original'] ?? false) {
            $result = array_merge($payload, $result);
        }

        return $result;
    }

    /**
     * Apply template transformation
     */
    private function applyTemplate(WebhookTransformation $transformation, array $payload, array $headers): array
    {
        $rules = $transformation->transformation_rules;
        $template = $rules['template'] ?? '';

        if (empty($template)) {
            return $payload;
        }

        // Simple template engine - replace {{field.path}} with actual values
        $context = array_merge($payload, [
            'payload' => $payload,
            'headers' => $headers,
            'timestamp' => now()->toISOString(),
        ]);

        $result = $this->renderTemplate($template, $context);
        
        return json_decode($result, true) ?? $payload;
    }

    /**
     * Apply JavaScript transformation (basic implementation)
     */
    private function applyJavaScript(WebhookTransformation $transformation, array $payload, array $headers): array
    {
        // For security reasons, this is a placeholder
        // In production, you'd want to use a sandboxed JavaScript engine
        throw new Exception('JavaScript transformations are not yet implemented for security reasons');
    }

    /**
     * Apply jq transformation
     */
    private function applyJq(WebhookTransformation $transformation, array $payload): array
    {
        $rules = $transformation->transformation_rules;
        $jqFilter = $rules['filter'] ?? '.';

        // This would require the jq binary to be installed
        // For now, we'll throw an exception
        throw new Exception('jq transformations require jq binary to be installed');
    }

    /**
     * Apply field-level transformations
     */
    private function applyFieldTransform($value, string $transform)
    {
        return match ($transform) {
            'uppercase' => is_string($value) ? strtoupper($value) : $value,
            'lowercase' => is_string($value) ? strtolower($value) : $value,
            'trim' => is_string($value) ? trim($value) : $value,
            'to_string' => (string) $value,
            'to_int' => (int) $value,
            'to_float' => (float) $value,
            'to_bool' => (bool) $value,
            'md5' => is_string($value) ? md5($value) : $value,
            'sha1' => is_string($value) ? sha1($value) : $value,
            'base64_encode' => is_string($value) ? base64_encode($value) : $value,
            'base64_decode' => is_string($value) ? base64_decode($value) : $value,
            'url_encode' => is_string($value) ? urlencode($value) : $value,
            'url_decode' => is_string($value) ? urldecode($value) : $value,
            default => $value,
        };
    }

    /**
     * Simple template renderer
     */
    private function renderTemplate(string $template, array $context): string
    {
        return preg_replace_callback('/\{\{([^}]+)\}\}/', function ($matches) use ($context) {
            $path = trim($matches[1]);
            return data_get($context, $path, $matches[0]);
        }, $template);
    }

    /**
     * Test a transformation with sample data
     */
    public function testTransformation(WebhookTransformation $transformation, array $testPayload, array $testHeaders = []): array
    {
        try {
            $result = $this->applyTransformation($transformation, $testPayload, $testHeaders);
            $transformation->markAsTested();
            
            return [
                'success' => true,
                'result' => $result,
                'original' => $testPayload,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'original' => $testPayload,
            ];
        }
    }
}