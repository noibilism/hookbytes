<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class WebhookTransformation extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_endpoint_id',
        'name',
        'description',
        'type',
        'transformation_rules',
        'conditions',
        'priority',
        'is_active',
        'test_input',
        'expected_output',
        'last_tested_at',
    ];

    protected $casts = [
        'transformation_rules' => 'array',
        'conditions' => 'array',
        'test_input' => 'array',
        'expected_output' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'last_tested_at' => 'datetime',
    ];

    public function webhookEndpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEndpoint($query, $endpointId)
    {
        return $query->where('webhook_endpoint_id', $endpointId);
    }

    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    /**
     * Check if conditions are met for applying this transformation
     */
    public function shouldApply(array $payload, array $headers = []): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (empty($this->conditions)) {
            return true;
        }

        foreach ($this->conditions as $condition) {
            if (!$this->evaluateCondition($condition, $payload, $headers)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition
     */
    private function evaluateCondition(array $condition, array $payload, array $headers): bool
    {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? 'equals';
        $value = $condition['value'] ?? '';
        $source = $condition['source'] ?? 'payload'; // 'payload' or 'headers'

        $data = $source === 'headers' ? $headers : $payload;
        $fieldValue = data_get($data, $field);

        return match ($operator) {
            'equals' => $fieldValue == $value,
            'not_equals' => $fieldValue != $value,
            'contains' => is_string($fieldValue) && str_contains($fieldValue, $value),
            'starts_with' => is_string($fieldValue) && str_starts_with($fieldValue, $value),
            'ends_with' => is_string($fieldValue) && str_ends_with($fieldValue, $value),
            'exists' => !is_null($fieldValue),
            'not_exists' => is_null($fieldValue),
            'greater_than' => is_numeric($fieldValue) && $fieldValue > $value,
            'less_than' => is_numeric($fieldValue) && $fieldValue < $value,
            default => false,
        };
    }

    /**
     * Mark transformation as tested
     */
    public function markAsTested(): void
    {
        $this->update(['last_tested_at' => now()]);
    }
}
