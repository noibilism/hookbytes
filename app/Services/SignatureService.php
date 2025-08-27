<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class SignatureService
{
    /**
     * Supported signature algorithms
     */
    const SUPPORTED_ALGORITHMS = [
        'sha256' => 'sha256',
        'sha1' => 'sha1',
        'md5' => 'md5'
    ];

    /**
     * Generate webhook signature
     *
     * @param string $payload
     * @param string $secret
     * @param string $algorithm
     * @return string
     */
    public function generateSignature(string $payload, string $secret, string $algorithm = 'sha256'): string
    {
        $this->validateAlgorithm($algorithm);
        
        return hash_hmac($algorithm, $payload, $secret);
    }

    /**
     * Verify webhook signature
     *
     * @param string $payload
     * @param string $signature
     * @param string $secret
     * @param string $algorithm
     * @return bool
     */
    public function verifySignature(string $payload, string $signature, string $secret, string $algorithm = 'sha256'): bool
    {
        $this->validateAlgorithm($algorithm);
        
        $expectedSignature = $this->generateSignature($payload, $secret, $algorithm);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Parse signature header (e.g., "sha256=abc123")
     *
     * @param string $signatureHeader
     * @return array
     */
    public function parseSignatureHeader(string $signatureHeader): array
    {
        if (!str_contains($signatureHeader, '=')) {
            throw new InvalidArgumentException('Invalid signature header format');
        }

        [$algorithm, $signature] = explode('=', $signatureHeader, 2);
        
        $this->validateAlgorithm($algorithm);
        
        return [
            'algorithm' => $algorithm,
            'signature' => $signature
        ];
    }

    /**
     * Validate signature algorithm
     *
     * @param string $algorithm
     * @throws InvalidArgumentException
     */
    private function validateAlgorithm(string $algorithm): void
    {
        if (!array_key_exists($algorithm, self::SUPPORTED_ALGORITHMS)) {
            throw new InvalidArgumentException("Unsupported signature algorithm: {$algorithm}");
        }
    }

    /**
     * Get supported algorithms
     *
     * @return array
     */
    public function getSupportedAlgorithms(): array
    {
        return array_keys(self::SUPPORTED_ALGORITHMS);
    }
}