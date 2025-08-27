# Laravel Webhook Service

A robust, enterprise-grade webhook delivery service built with Laravel 10+. This service provides reliable webhook delivery with advanced features like retry mechanisms, signature verification, idempotency, throttling, and comprehensive monitoring.

## 🚀 Features

### Core Webhook Functionality
- **Reliable Delivery**: Automatic retry with exponential backoff
- **Signature Verification**: HMAC-SHA256 signature validation for security
- **Idempotency**: Prevent duplicate deliveries with idempotency keys
- **Throttling**: Rate limiting to prevent overwhelming endpoints
- **Dead Letter Queue**: Failed deliveries management
- **Bulk Operations**: Replay multiple deliveries efficiently

### Security & Authentication
- **API Key Authentication**: Secure API access with key-based authentication
- **Request Redaction**: Sensitive data filtering and masking
- **CORS Support**: Cross-origin request handling
- **Request ID Tracking**: Unique request identification for debugging

### Monitoring & Management
- **Web Dashboard**: Intuitive Blade + Livewire interface
- **Laravel Horizon**: Real-time queue monitoring
- **Delivery Metrics**: Daily aggregated statistics
- **User Management**: Multi-user support with role-based access
- **API Key Management**: Generate and manage API keys

### Developer Experience
- **RESTful API**: Clean, well-documented API endpoints
- **Queue Processing**: Background job processing with Redis
- **Database Agnostic**: Support for MySQL, PostgreSQL, SQLite
- **Docker Support**: Containerized deployment ready
- **Comprehensive Testing**: Unit and feature tests included

## 📋 Requirements

### System Requirements
- **PHP**: 8.2 or higher
- **Database**: MySQL 8.0+, PostgreSQL 13+, or SQLite 3.8+
- **Redis**: 6.0+ (for queues and caching)
- **Node.js**: 18+ (for frontend assets)
- **Composer**: 2.0+

### PHP Extensions
- PDO (MySQL/SQLite)
- mbstring
- openssl
- tokenizer
- xml
- ctype
- json
- bcmath
- fileinfo
- redis

## 🛠 Quick Setup

### Automated Installation

The easiest way to set up the service is using our automated setup script:

```bash
# Clone the repository
git clone <repository-url> laravel-webhook-service
cd laravel-webhook-service

# Run the automated setup
./setup-dependencies.sh install
```

This script will:
- Install all required dependencies (PHP, Node.js, Redis, etc.)
- Configure the environment
- Set up the database
- Build frontend assets
- Start required services

### Manual Installation

If you prefer manual setup:

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database (edit .env file)
# Then run migrations
php artisan migrate --seed

# Build frontend assets
npm run build

# Start services
php artisan serve
php artisan horizon
```

### Docker Setup

For containerized deployment:

```bash
# Build and start containers
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate --seed
```

## 🏗 Architecture

### Database Schema

- **subscriptions**: Webhook endpoint configurations
- **events**: Webhook events to be delivered
- **deliveries**: Individual delivery attempts
- **dead_letters**: Failed deliveries after max retries
- **delivery_metrics_daily**: Aggregated daily statistics
- **users**: Dashboard users
- **api_keys**: API authentication keys

### Core Services

- **SignatureService**: HMAC signature generation and verification
- **IdempotencyService**: Duplicate delivery prevention
- **ThrottleService**: Rate limiting implementation
- **RedactionService**: Sensitive data filtering

### Queue Jobs

- **DispatchEventDeliveries**: Distribute events to subscriptions
- **AttemptDelivery**: Execute individual webhook deliveries
- **BulkReplayDeliveries**: Handle bulk replay operations

## 📚 API Documentation

### Authentication

All API requests require an API key in the header:

```bash
X-API-Key: your-api-key-here
```

### Core Endpoints

#### Subscriptions Management
```bash
# List subscriptions
GET /api/subscriptions

# Create subscription
POST /api/subscriptions
{
  "url": "https://example.com/webhook",
  "events": ["user.created", "order.completed"],
  "secret": "your-webhook-secret",
  "active": true
}

# Update subscription
PUT /api/subscriptions/{id}

# Delete subscription
DELETE /api/subscriptions/{id}
```

#### Event Publishing
```bash
# Publish event
POST /api/events
{
  "type": "user.created",
  "data": {
    "user_id": 123,
    "email": "user@example.com"
  },
  "idempotency_key": "unique-key-123"
}
```

#### Delivery Management
```bash
# List deliveries
GET /api/deliveries

# Get delivery details
GET /api/deliveries/{id}

# Replay delivery
POST /api/deliveries/{id}/replay

# Bulk replay
POST /api/deliveries/bulk-replay
{
  "delivery_ids": [1, 2, 3]
}
```

## 🎛 Dashboard

Access the web dashboard at `/dashboard` with these features:

- **Overview**: System statistics and recent activity
- **Subscriptions**: Manage webhook endpoints
- **Events**: View and search webhook events
- **Deliveries**: Monitor delivery status and retry failed ones
- **Dead Letters**: Manage permanently failed deliveries
- **Users**: User account management
- **API Keys**: Generate and manage API keys

### Default Credentials
- **Email**: admin@webhook.local
- **Password**: password

## 🔧 Configuration

### Environment Variables

Key configuration options in `.env`:

```env
# Application
APP_NAME="Laravel Webhook Service"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=webhook_service
DB_USERNAME=username
DB_PASSWORD=password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# Webhook Configuration
WEBHOOK_MAX_RETRIES=5
WEBHOOK_RETRY_DELAY=60
WEBHOOK_TIMEOUT=30
WEBHOOK_THROTTLE_LIMIT=100
WEBHOOK_THROTTLE_WINDOW=60
```

### Webhook Configuration

Customize webhook behavior in `config/webhooks.php`:

```php
return [
    'max_retries' => env('WEBHOOK_MAX_RETRIES', 5),
    'retry_delay' => env('WEBHOOK_RETRY_DELAY', 60),
    'timeout' => env('WEBHOOK_TIMEOUT', 30),
    'throttle' => [
        'limit' => env('WEBHOOK_THROTTLE_LIMIT', 100),
        'window' => env('WEBHOOK_THROTTLE_WINDOW', 60),
    ],
    'signature' => [
        'algorithm' => 'sha256',
        'header' => 'X-Webhook-Signature',
    ],
];
```

## 🚀 Production Deployment

### Using Supervisor

Set up process management with our Supervisor script:

```bash
# Install and configure Supervisor
./setup-supervisor.sh install

# Start services
./setup-supervisor.sh start

# Monitor services
./setup-supervisor.sh status
```

### Web Server Configuration

#### Nginx Example
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/laravel-webhook-service/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Performance Optimization

```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

## 📊 Monitoring

### Laravel Horizon

Monitor queue performance at `/horizon`:
- Real-time job processing metrics
- Failed job management
- Queue workload distribution
- Performance insights

### Health Checks

```bash
# Check application health
php artisan tinker
>>> DB::connection()->getPdo(); // Database
>>> Redis::ping(); // Redis
>>> \App\Models\Subscription::count(); // Application
```

### Logging

Logs are stored in `storage/logs/` with different channels:
- `laravel.log`: General application logs
- `webhook.log`: Webhook-specific events
- `horizon.log`: Queue processing logs

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage
```

## 🔧 Development

### Local Development

```bash
# Start development server
php artisan serve

# Start queue worker
php artisan queue:work

# Start Horizon
php artisan horizon

# Watch frontend assets
npm run dev
```

### Useful Commands

```bash
# Generate API key
php artisan webhook:generate-api-key user@example.com

# Clear caches
php artisan optimize:clear

# Reset database
php artisan migrate:fresh --seed

# Queue management
php artisan queue:work
php artisan queue:restart
php artisan horizon:terminate
```

## 📖 Additional Documentation

- [Dependencies Setup Guide](DEPENDENCIES_SETUP.md)
- [Supervisor Setup Guide](SUPERVISOR_SETUP.md)
- [API Documentation](docs/api.md) *(if available)*
- [Deployment Guide](docs/deployment.md) *(if available)*

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Check the documentation
- Review existing issues
- Create a new issue with detailed information

---

**Built with ❤️ using Laravel, Vue.js, and modern web technologies.**
