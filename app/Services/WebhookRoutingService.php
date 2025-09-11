<?php

namespace App\Services;

use App\Models\WebhookEndpoint;
use App\Models\WebhookRoutingRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class WebhookRoutingService
{
    public function evaluateRouting(WebhookEndpoint $endpoint, array $payload, array $headers = []): array
    {
        $routingRules = $endpoint->activeRoutingRules;
        $context = $this->buildContext($payload, $headers);
        
        // Check for drop rules first (highest priority)
        foreach ($routingRules->where('action', 'drop') as $rule) {
            if ($this->evaluateConditions($rule->conditions, $context)) {
                $rule->incrementMatchCount();
                Log::info('Webhook dropped by rule', [
                    'endpoint_id' => $endpoint->id,
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name
                ]);
                return ['action' => 'drop', 'rule' => $rule];
            }
        }
        
        // Collect all matching route rules
        $matchingRoutes = collect();
        foreach ($routingRules->where('action', 'route') as $rule) {
            if ($this->evaluateConditions($rule->conditions, $context)) {
                $rule->incrementMatchCount();
                $matchingRoutes->push($rule);
            }
        }
        
        // If no routing rules match, use default destinations
        if ($matchingRoutes->isEmpty()) {
            return [
                'action' => 'route',
                'destinations' => $endpoint->destination_urls ?? [],
                'rule' => null
            ];
        }
        
        // Combine all destinations from matching rules
        $allDestinations = $this->combineDestinations($matchingRoutes);
        
        return [
            'action' => 'route',
            'destinations' => $allDestinations,
            'rules' => $matchingRoutes->toArray()
        ];
    }
    
    protected function buildContext(array $payload, array $headers): array
    {
        return [
            'payload' => $payload,
            'headers' => $headers,
            'timestamp' => now()->toISOString(),
        ];
    }
    
    protected function evaluateConditions(array $conditions, array $context): bool
    {
        if (empty($conditions)) {
            return true;
        }
        
        foreach ($conditions as $condition) {
            if (!$this->evaluateCondition($condition, $context)) {
                return false;
            }
        }
        
        return true;
    }
    
    protected function evaluateCondition(array $condition, array $context): bool
    {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? '';
        
        $actualValue = $this->getNestedValue($context, $field);
        
        return match ($operator) {
            '=' => $actualValue == $value,
            '!=' => $actualValue != $value,
            '>' => is_numeric($actualValue) && is_numeric($value) && $actualValue > $value,
            '<' => is_numeric($actualValue) && is_numeric($value) && $actualValue < $value,
            '>=' => is_numeric($actualValue) && is_numeric($value) && $actualValue >= $value,
            '<=' => is_numeric($actualValue) && is_numeric($value) && $actualValue <= $value,
            'contains' => is_string($actualValue) && str_contains($actualValue, $value),
            'starts_with' => is_string($actualValue) && str_starts_with($actualValue, $value),
            'ends_with' => is_string($actualValue) && str_ends_with($actualValue, $value),
            'in' => is_array($value) && in_array($actualValue, $value),
            'not_in' => is_array($value) && !in_array($actualValue, $value),
            'exists' => !is_null($actualValue),
            'not_exists' => is_null($actualValue),
            default => false,
        };
    }
    
    protected function getNestedValue(array $data, string $path)
    {
        $keys = explode('.', $path);
        $current = $data;
        
        foreach ($keys as $key) {
            if (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            } else {
                return null;
            }
        }
        
        return $current;
    }
    
    protected function combineDestinations(Collection $rules): array
    {
        $destinations = collect();
        
        foreach ($rules as $rule) {
            if (!empty($rule->destinations)) {
                foreach ($rule->destinations as $destination) {
                    $destinations->push([
                        'url' => $destination['url'] ?? $destination,
                        'priority' => $destination['priority'] ?? $rule->priority ?? 100,
                        'rule_id' => $rule->id,
                        'rule_name' => $rule->name,
                    ]);
                }
            }
        }
        
        // Sort by priority (lower number = higher priority)
        return $destinations->sortBy('priority')->values()->toArray();
    }
}