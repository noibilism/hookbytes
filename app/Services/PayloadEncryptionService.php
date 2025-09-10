<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class PayloadEncryptionService
{
    /**
     * Encrypt a payload for secure storage
     */
    public function encrypt(array $payload): string
    {
        return Crypt::encrypt(json_encode($payload));
    }

    /**
     * Decrypt a payload from secure storage
     */
    public function decrypt(string $encryptedPayload): array
    {
        try {
            $decrypted = Crypt::decrypt($encryptedPayload);
            return json_decode($decrypted, true) ?? [];
        } catch (DecryptException $e) {
            throw new \InvalidArgumentException('Invalid encrypted payload: ' . $e->getMessage());
        }
    }

    /**
     * Generate HMAC signature for payload verification
     */
    public function generateSignature(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Verify HMAC signature
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = $this->generateSignature($payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Encrypt sensitive data in payload (like PII)
     */
    public function encryptSensitiveFields(array $payload, array $sensitiveFields = []): array
    {
        $defaultSensitiveFields = [
            'email', 'phone', 'ssn', 'credit_card', 'password', 
            'api_key', 'token', 'secret', 'private_key'
        ];
        
        $fieldsToEncrypt = array_merge($defaultSensitiveFields, $sensitiveFields);
        
        return $this->encryptFields($payload, $fieldsToEncrypt);
    }

    /**
     * Recursively encrypt specified fields in payload
     */
    private function encryptFields(array $data, array $fieldsToEncrypt): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->encryptFields($value, $fieldsToEncrypt);
            } elseif (in_array($key, $fieldsToEncrypt) && is_string($value)) {
                $data[$key] = Crypt::encrypt($value);
            }
        }
        
        return $data;
    }

    /**
     * Decrypt sensitive fields in payload
     */
    public function decryptSensitiveFields(array $payload, array $sensitiveFields = []): array
    {
        $defaultSensitiveFields = [
            'email', 'phone', 'ssn', 'credit_card', 'password', 
            'api_key', 'token', 'secret', 'private_key'
        ];
        
        $fieldsToDecrypt = array_merge($defaultSensitiveFields, $sensitiveFields);
        
        return $this->decryptFields($payload, $fieldsToDecrypt);
    }

    /**
     * Recursively decrypt specified fields in payload
     */
    private function decryptFields(array $data, array $fieldsToDecrypt): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->decryptFields($value, $fieldsToDecrypt);
            } elseif (in_array($key, $fieldsToDecrypt) && is_string($value)) {
                try {
                    $data[$key] = Crypt::decrypt($value);
                } catch (DecryptException $e) {
                    // Field might not be encrypted, leave as is
                }
            }
        }
        
        return $data;
    }

    /**
     * Generate a secure webhook secret
     */
    public function generateWebhookSecret(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Mask sensitive data for logging
     */
    public function maskSensitiveData(array $payload): array
    {
        $sensitiveFields = [
            'email', 'phone', 'ssn', 'credit_card', 'password', 
            'api_key', 'token', 'secret', 'private_key'
        ];
        
        return $this->maskFields($payload, $sensitiveFields);
    }

    /**
     * Recursively mask specified fields
     */
    private function maskFields(array $data, array $fieldsToMask): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskFields($value, $fieldsToMask);
            } elseif (in_array($key, $fieldsToMask) && is_string($value)) {
                $data[$key] = $this->maskValue($value);
            }
        }
        
        return $data;
    }

    /**
     * Mask a single value
     */
    private function maskValue(string $value): string
    {
        $length = strlen($value);
        
        if ($length <= 4) {
            return str_repeat('*', $length);
        }
        
        return substr($value, 0, 2) . str_repeat('*', $length - 4) . substr($value, -2);
    }
}