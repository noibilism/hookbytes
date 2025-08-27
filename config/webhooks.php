<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the webhook service.
    | You can customize various aspects of webhook delivery, security,
    | and behavior through these settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'timeout' => env('WEBHOOK_TIMEOUT', 30), // seconds
        'retry_attempts' => env('WEBHOOK_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('WEBHOOK_RETRY_DELAY', 60), // seconds
        'retry_multiplier' => env('WEBHOOK_RETRY_MULTIPLIER', 2),
        'max_retry_delay' => env('WEBHOOK_MAX_RETRY_DELAY', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */

    'security' => [
        'signature_header' => env('WEBHOOK_SIGNATURE_HEADER', 'X-Webhook-Signature'),
        'timestamp_header' => env('WEBHOOK_TIMESTAMP_HEADER', 'X-Webhook-Timestamp'),
        'signature_algorithm' => env('WEBHOOK_SIGNATURE_ALGORITHM', 'sha256'),
        'timestamp_tolerance' => env('WEBHOOK_TIMESTAMP_TOLERANCE', 300), // 5 minutes
        'verify_ssl' => env('WEBHOOK_VERIFY_SSL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limiting' => [
        'enabled' => env('WEBHOOK_RATE_LIMITING_ENABLED', true),
        'max_attempts' => env('WEBHOOK_RATE_LIMIT_ATTEMPTS', 60),
        'decay_minutes' => env('WEBHOOK_RATE_LIMIT_DECAY', 1),
        'per_endpoint' => env('WEBHOOK_RATE_LIMIT_PER_ENDPOINT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Idempotency
    |--------------------------------------------------------------------------
    */

    'idempotency' => [
        'enabled' => env('WEBHOOK_IDEMPOTENCY_ENABLED', true),
        'header' => env('WEBHOOK_IDEMPOTENCY_HEADER', 'X-Idempotency-Key'),
        'ttl' => env('WEBHOOK_IDEMPOTENCY_TTL', 86400), // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Dead Letter Queue
    |--------------------------------------------------------------------------
    */

    'dead_letter' => [
        'enabled' => env('WEBHOOK_DEAD_LETTER_ENABLED', true),
        'max_failures' => env('WEBHOOK_DEAD_LETTER_MAX_FAILURES', 5),
        'retention_days' => env('WEBHOOK_DEAD_LETTER_RETENTION_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging and Monitoring
    |--------------------------------------------------------------------------
    */

    'logging' => [
        'enabled' => env('WEBHOOK_LOGGING_ENABLED', true),
        'log_requests' => env('WEBHOOK_LOG_REQUESTS', true),
        'log_responses' => env('WEBHOOK_LOG_RESPONSES', true),
        'log_failures' => env('WEBHOOK_LOG_FAILURES', true),
        'retention_days' => env('WEBHOOK_LOG_RETENTION_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Redaction
    |--------------------------------------------------------------------------
    */

    'redaction' => [
        'enabled' => env('WEBHOOK_REDACTION_ENABLED', true),
        'fields' => [
            'password',
            'secret',
            'token',
            'api_key',
            'credit_card',
            'ssn',
            'social_security_number',
        ],
        'replacement' => '[REDACTED]',
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */

    'queue' => [
        'connection' => env('WEBHOOK_QUEUE_CONNECTION', 'redis'),
        'queue' => env('WEBHOOK_QUEUE_NAME', 'webhooks'),
        'high_priority_queue' => env('WEBHOOK_HIGH_PRIORITY_QUEUE', 'webhooks-high'),
        'failed_queue' => env('WEBHOOK_FAILED_QUEUE', 'webhooks-failed'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Configuration
    |--------------------------------------------------------------------------
    */

    'http' => [
        'user_agent' => env('WEBHOOK_USER_AGENT', 'Laravel-Webhook-Service/1.0'),
        'connect_timeout' => env('WEBHOOK_CONNECT_TIMEOUT', 10),
        'read_timeout' => env('WEBHOOK_READ_TIMEOUT', 30),
        'follow_redirects' => env('WEBHOOK_FOLLOW_REDIRECTS', false),
        'max_redirects' => env('WEBHOOK_MAX_REDIRECTS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics and Analytics
    |--------------------------------------------------------------------------
    */

    'metrics' => [
        'enabled' => env('WEBHOOK_METRICS_ENABLED', true),
        'daily_aggregation' => env('WEBHOOK_DAILY_AGGREGATION', true),
        'retention_days' => env('WEBHOOK_METRICS_RETENTION_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Types
    |--------------------------------------------------------------------------
    */

    'event_types' => [
        'user.created',
        'user.updated',
        'user.deleted',
        'order.created',
        'order.updated',
        'order.cancelled',
        'order.completed',
        'payment.succeeded',
        'payment.failed',
        'subscription.created',
        'subscription.updated',
        'subscription.cancelled',
        'invoice.created',
        'invoice.paid',
        'invoice.failed',
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Endpoints Validation
    |--------------------------------------------------------------------------
    */

    'validation' => [
        'url_schemes' => ['http', 'https'],
        'blocked_domains' => [
            'localhost',
            '127.0.0.1',
            '0.0.0.0',
            '::1',
        ],
        'blocked_ports' => [22, 23, 25, 53, 80, 110, 143, 993, 995],
        'max_url_length' => 2048,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Configuration
    |--------------------------------------------------------------------------
    */

    'dashboard' => [
        'enabled' => env('WEBHOOK_DASHBOARD_ENABLED', true),
        'route_prefix' => env('WEBHOOK_DASHBOARD_PREFIX', 'dashboard'),
        'middleware' => ['web', 'auth:sanctum'],
        'pagination' => [
            'per_page' => env('WEBHOOK_DASHBOARD_PER_PAGE', 25),
            'max_per_page' => env('WEBHOOK_DASHBOARD_MAX_PER_PAGE', 100),
        ],
    ],

];