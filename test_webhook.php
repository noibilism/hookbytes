<?php

/**
 * Test script to demonstrate webhook functionality
 * This script sends a test event to the webhook endpoint
 */

// Configuration from seeded data
$baseUrl = 'http://127.0.0.1:8000';
$projectSlug = 'demo-project';
$endpointSlug = 'payment-events'; // This will be generated from "Payment Events"
$webhookSecret = 'hWwnBe6VvYYWqBnp8F1hHIbUlBroWlo3';

// Test payload
$payload = [
    'event_type' => 'payment.completed',
    'data' => [
        'payment_id' => 'pay_123456789',
        'amount' => 2500, // $25.00 in cents
        'currency' => 'USD',
        'customer_id' => 'cust_987654321',
        'status' => 'completed',
        'created_at' => date('c'),
    ],
    'metadata' => [
        'source' => 'payment_processor',
        'version' => '1.0',
    ],
];

$jsonPayload = json_encode($payload);

// Generate HMAC signature
$signature = 'sha256=' . hash_hmac('sha256', $jsonPayload, $webhookSecret);

// Prepare headers
$headers = [
    'Content-Type: application/json',
    'X-Signature-256: ' . $signature,
    'X-Event-Type: payment.completed',
    'User-Agent: Test-Webhook-Client/1.0',
];

// Send webhook request
$url = $baseUrl . '/api/webhook/' . $projectSlug . '/' . $endpointSlug;

echo "Sending webhook to: $url\n";
echo "Payload: " . $jsonPayload . "\n";
echo "Signature: $signature\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "cURL Error: $error\n";
} else {
    echo "HTTP Status: $httpCode\n";
    echo "Response: $response\n";
}

// Test the info endpoint
echo "\n--- Testing Info Endpoint ---\n";
$infoUrl = $baseUrl . '/api/webhook/' . $projectSlug . '/' . $endpointSlug . '/info';
echo "Info URL: $infoUrl\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $infoUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$infoResponse = curl_exec($ch);
$infoHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Info HTTP Status: $infoHttpCode\n";
echo "Info Response: $infoResponse\n";