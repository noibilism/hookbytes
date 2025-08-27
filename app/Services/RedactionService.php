<?php

namespace App\Services;

class RedactionService
{
    /**
     * Default sensitive field patterns
     */
    const DEFAULT_SENSITIVE_FIELDS = [
        'password',
        'secret',
        'token',
        'api_key',
        'apikey',
        'auth',
        'authorization',
        'credit_card',
        'creditcard',
        'card_number',
        'cardnumber',
        'cvv',
        'ssn',
        'social_security',
        'bank_account',
        'routing_number',
        'private_key',
        'privatekey'
    ];

    /**
     * Redaction placeholder
     */
    const REDACTION_PLACEHOLDER = '[REDACTED]';

    /**
     * Custom sensitive field patterns
     */
    private array $customSensitiveFields = [];

    /**
     * Whether redaction is enabled
     */
    private bool $enabled = true;

    /**
     * Set custom sensitive field patterns
     *
     * @param array $fields
     * @return self
     */
    public function setSensitiveFields(array $fields): self
    {
        $this->customSensitiveFields = $fields;
        return $this;
    }

    /**
     * Add custom sensitive field pattern
     *
     * @param string $field
     * @return self
     */
    public function addSensitiveField(string $field): self
    {
        $this->customSensitiveFields[] = $field;
        return $this;
    }

    /**
     * Enable or disable redaction
     *
     * @param bool $enabled
     * @return self
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Redact sensitive data from payload
     *
     * @param array $payload
     * @return array
     */
    public function redact(array $payload): array
    {
        if (!$this->enabled) {
            return $payload;
        }

        return $this->redactRecursive($payload);
    }

    /**
     * Redact sensitive data from JSON string
     *
     * @param string $jsonPayload
     * @return string
     */
    public function redactJson(string $jsonPayload): string
    {
        if (!$this->enabled) {
            return $jsonPayload;
        }

        $payload = json_decode($jsonPayload, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $jsonPayload; // Return original if not valid JSON
        }

        $redacted = $this->redact($payload);
        
        return json_encode($redacted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Check if a field should be redacted
     *
     * @param string $fieldName
     * @return bool
     */
    public function isSensitiveField(string $fieldName): bool
    {
        $fieldName = strtolower($fieldName);
        $allSensitiveFields = array_merge(
            self::DEFAULT_SENSITIVE_FIELDS,
            array_map('strtolower', $this->customSensitiveFields)
        );

        // Check exact matches
        if (in_array($fieldName, $allSensitiveFields)) {
            return true;
        }

        // Check if field contains sensitive patterns
        foreach ($allSensitiveFields as $pattern) {
            if (str_contains($fieldName, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Redact email addresses (partial redaction)
     *
     * @param string $email
     * @return string
     */
    public function redactEmail(string $email): string
    {
        if (!$this->enabled || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        [$local, $domain] = explode('@', $email);
        
        $localLength = strlen($local);
        if ($localLength <= 2) {
            $redactedLocal = str_repeat('*', $localLength);
        } else {
            $redactedLocal = substr($local, 0, 1) . str_repeat('*', $localLength - 2) . substr($local, -1);
        }

        return $redactedLocal . '@' . $domain;
    }

    /**
     * Redact phone numbers (partial redaction)
     *
     * @param string $phone
     * @return string
     */
    public function redactPhone(string $phone): string
    {
        if (!$this->enabled) {
            return $phone;
        }

        // Remove non-numeric characters for processing
        $numbersOnly = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($numbersOnly) < 4) {
            return str_repeat('*', strlen($phone));
        }

        // Keep last 4 digits
        $lastFour = substr($numbersOnly, -4);
        $redacted = str_repeat('*', strlen($numbersOnly) - 4) . $lastFour;
        
        // Restore original formatting pattern
        $result = $phone;
        $pos = 0;
        for ($i = 0; $i < strlen($phone); $i++) {
            if (is_numeric($phone[$i])) {
                $result[$i] = $redacted[$pos++] ?? '*';
            }
        }

        return $result;
    }

    /**
     * Redact credit card numbers
     *
     * @param string $cardNumber
     * @return string
     */
    public function redactCreditCard(string $cardNumber): string
    {
        if (!$this->enabled) {
            return $cardNumber;
        }

        $numbersOnly = preg_replace('/[^0-9]/', '', $cardNumber);
        
        if (strlen($numbersOnly) < 4) {
            return str_repeat('*', strlen($cardNumber));
        }

        // Keep last 4 digits
        $lastFour = substr($numbersOnly, -4);
        $redacted = str_repeat('*', strlen($numbersOnly) - 4) . $lastFour;
        
        // Restore original formatting
        $result = $cardNumber;
        $pos = 0;
        for ($i = 0; $i < strlen($cardNumber); $i++) {
            if (is_numeric($cardNumber[$i])) {
                $result[$i] = $redacted[$pos++] ?? '*';
            }
        }

        return $result;
    }

    /**
     * Get all sensitive field patterns
     *
     * @return array
     */
    public function getSensitiveFields(): array
    {
        return array_merge(self::DEFAULT_SENSITIVE_FIELDS, $this->customSensitiveFields);
    }

    /**
     * Recursively redact sensitive data
     *
     * @param mixed $data
     * @return mixed
     */
    private function redactRecursive($data)
    {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                if ($this->isSensitiveField($key)) {
                    $result[$key] = $this->redactValue($value, $key);
                } else {
                    $result[$key] = $this->redactRecursive($value);
                }
            }
            return $result;
        }

        if (is_object($data)) {
            $result = new \stdClass();
            foreach (get_object_vars($data) as $key => $value) {
                if ($this->isSensitiveField($key)) {
                    $result->$key = $this->redactValue($value, $key);
                } else {
                    $result->$key = $this->redactRecursive($value);
                }
            }
            return $result;
        }

        return $data;
    }

    /**
     * Redact a specific value based on field type
     *
     * @param mixed $value
     * @param string $fieldName
     * @return mixed
     */
    private function redactValue($value, string $fieldName)
    {
        if (!is_string($value)) {
            return self::REDACTION_PLACEHOLDER;
        }

        $fieldName = strtolower($fieldName);

        // Special handling for specific field types
        if (str_contains($fieldName, 'email')) {
            return $this->redactEmail($value);
        }

        if (str_contains($fieldName, 'phone') || str_contains($fieldName, 'mobile')) {
            return $this->redactPhone($value);
        }

        if (str_contains($fieldName, 'card') || str_contains($fieldName, 'credit')) {
            return $this->redactCreditCard($value);
        }

        // Default redaction
        return self::REDACTION_PLACEHOLDER;
    }
}