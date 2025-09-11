# 🪝 HookBytes - Open Source Webhook Gateway

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-red?style=for-the-badge&logo=laravel" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-8.2+-blue?style=for-the-badge&logo=php" alt="PHP">
  <img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="License">
  <img src="https://img.shields.io/badge/Status-Production%20Ready-brightgreen?style=for-the-badge" alt="Status">
</p>

**HookBytes** is a powerful, self-hosted webhook management platform that serves as an open-source alternative to Hookdeck. Built with Laravel, it provides enterprise-grade webhook processing, monitoring, and debugging capabilities without vendor lock-in.

## 🚀 Why Choose HookBytes over Hookdeck?

| Feature | HookBytes | Hookdeck |
|---------|-----------|----------|
| **Cost** | ✅ Free & Open Source | ❌ Paid SaaS with usage limits |
| **Self-Hosted** | ✅ Full control of your data | ❌ Cloud-only |
| **Customization** | ✅ Unlimited customization | ❌ Limited to platform features |
| **Privacy** | ✅ Your data stays on your servers | ❌ Data processed on third-party servers |
| **Scalability** | ✅ Scale as needed on your infrastructure | ❌ Limited by pricing tiers |
| **API Access** | ✅ Full REST API included | ✅ Available |
| **Event Replay** | ✅ Built-in replay functionality | ✅ Available |
| **Multi-tenancy** | ✅ Project-based organization | ✅ Available |

## ✨ Key Features

### 🎯 **Webhook Management**
- **Multi-project Organization**: Organize webhooks by projects with isolated environments
- **Dynamic Endpoint Creation**: Create webhook endpoints via web UI or REST API
- **Flexible Authentication**: Support for HMAC, shared secrets, or no authentication
- **Custom Headers**: Configure custom headers for outbound requests
- **Retry Logic**: Configurable retry policies with exponential backoff

### 📊 **Monitoring & Analytics**
- **Real-time Event Tracking**: Monitor webhook deliveries in real-time
- **Comprehensive Logging**: Detailed logs with request/response data
- **Status Monitoring**: Track success, failure, and retry attempts
- **Performance Metrics**: Built-in analytics dashboard
- **Laravel Telescope Integration**: Deep debugging capabilities

### 🔄 **Event Processing**
- **Asynchronous Processing**: Queue-based event processing for high throughput
- **Event Replay**: Replay failed or specific events with one click
- **Bulk Operations**: Replay multiple events with advanced filtering
- **Event Filtering**: Filter events by status, date, project, or endpoint

### 🔐 **Security & Authentication**
- **API Key Authentication**: Secure API access with project-specific keys
- **Rate Limiting**: Built-in rate limiting to prevent abuse
- **Request Validation**: Comprehensive input validation and sanitization
- **Audit Logging**: Complete audit trail of all operations

### 🛠 **Developer Experience**
- **REST API**: Full-featured API for programmatic access
- **CLI Tools**: Command-line interface for automation
- **Webhook Testing**: Built-in tools for testing webhook endpoints
- **Documentation**: Comprehensive API documentation with examples
- **Code Examples**: Ready-to-use examples in multiple languages

## 🏗 Architecture

HookBytes is built on a modern, scalable architecture:

- **Framework**: Laravel 12.x with PHP 8.2+
- **Database**: SQLite (default) or MySQL/PostgreSQL
- **Queue System**: Redis/Database queues for async processing
- **Monitoring**: Laravel Telescope for debugging
- **Frontend**: Blade templates with Tailwind CSS
- **API**: RESTful API with comprehensive documentation

## 📦 Quick Start

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js & NPM (for asset compilation)
- SQLite, MySQL, or PostgreSQL

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/noibilism/hookbytes.git
   cd hookbytes
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install && npm run build
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   touch database/database.sqlite  # For SQLite
   php artisan migrate
   php artisan db:seed
   ```

5. **Start the application**
   ```bash
   php artisan serve
   php artisan queue:work  # In a separate terminal
   ```

6. **Access the dashboard**
   Open `http://localhost:8000` and create your first project!

## ✨ New Features - Testing Framework

### 🎯 Comprehensive Test Suite
This iteration introduces a robust testing framework with 6 specialized test categories:

- **🔄 Integration Workflow Tests** (`IntegrationWorkflowTest.php`) - Complete webhook lifecycle testing
- **🔒 Security Tests** (`SecurityTest.php`) - Authentication, authorization, and security validation
- **⚠️ Error Handling Tests** (`ErrorHandlingTest.php`) - Edge cases and failure scenario coverage
- **🌐 API Endpoint Tests** (`ApiEndpointTest.php`) - Comprehensive API route testing
- **⚡ Performance Tests** (`PerformanceTest.php`) - Load testing and optimization validation
- **🖥️ UI Interaction Tests** (`UiInteractionTest.php`) - Frontend and user experience testing

### 🚀 Automated Test Runner
New `run-test-scenarios.sh` script provides:
- **One-command testing** - Run all scenarios with a single command
- **Selective testing** - Run specific test categories
- **Environment auto-setup** - Automatic test environment configuration
- **Detailed reporting** - Comprehensive test results and summaries
- **CI/CD ready** - Perfect for automated pipelines

### 📋 Test Coverage Highlights
- **74+ test methods** across all application layers
- **End-to-end workflows** from webhook receipt to delivery
- **Security validation** for all authentication mechanisms
- **Performance benchmarks** for high-load scenarios
- **Error resilience** testing for production reliability

### 🛠️ Developer Experience
- **Easy setup** - No complex configuration required
- **Fast execution** - Optimized test performance
- **Clear documentation** - Comprehensive guides in `TEST_SCENARIOS.md`
- **Extensible framework** - Easy to add new test scenarios

## 🔧 Configuration

### Environment Variables

```env
# Application
APP_NAME="HookBytes"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database.sqlite

# Queue (for production)
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Rate Limiting
API_RATE_LIMIT=1000  # requests per hour per project
```

### Production Deployment

#### Quick Ubuntu Deployment

Deploy HookBytes on Ubuntu servers with a single command:

```bash
wget -O - https://raw.githubusercontent.com/noibilism/hookbytes/main/deploy.sh | bash
```

The deployment script will:
- Install all required dependencies (PHP, Nginx, MySQL, Redis, Node.js)
- Configure the web server and services
- Prompt for database and SMTP configuration
- Set up queue workers and supervisor
- Configure firewall and security settings

#### Manual Production Setup

For production deployment, consider:

- Use a robust database (MySQL/PostgreSQL)
- Configure Redis for queues and caching
- Set up proper logging and monitoring
- Use a reverse proxy (Nginx/Apache)
- Enable HTTPS with SSL certificates
- Configure backup strategies

## 📚 API Documentation

### Authentication
All API requests require an API key in the header:
```bash
X-API-Key: your-project-api-key
```

### Core Endpoints

#### Create Webhook Endpoint
```bash
POST /api/v1/webhooks/endpoints
Content-Type: application/json
X-API-Key: your-api-key

{
  "name": "My Webhook",
  "destination_urls": ["https://your-app.com/webhook"],
  "auth_method": "hmac",
  "auth_secret": "your-secret"
}
```

#### Get Events
```bash
GET /api/v1/events?status=failed&limit=10
X-API-Key: your-api-key
```

#### Replay Event
```bash
POST /api/v1/events/{id}/replay
X-API-Key: your-api-key
```

### Webhook URLs
Once created, your webhook endpoints are available at:
```
https://your-domain.com/api/webhook/{project-slug}/{endpoint-slug}
```

## 🧪 Testing & Quality Assurance

HookBytes includes a comprehensive test suite with 74+ test scenarios covering all aspects of the application.

### 🎯 Test Categories

- **Integration Tests** - End-to-end webhook workflows
- **Security Tests** - Authentication, authorization, and data protection
- **Error Handling Tests** - Edge cases and failure scenarios
- **API Endpoint Tests** - Complete API coverage
- **Performance Tests** - Load testing and optimization
- **UI Interaction Tests** - Frontend and user experience

### 🚀 Quick Testing

```bash
# Run all test scenarios
./run-test-scenarios.sh

# Run with coverage analysis
./run-test-scenarios.sh --coverage

# Run specific test category
./run-test-scenarios.sh --test integration
./run-test-scenarios.sh --test security
./run-test-scenarios.sh --test performance

# Run existing Laravel tests
php artisan test

# Get help and see all options
./run-test-scenarios.sh --help
```

### 📊 Test Scenarios Overview

| Test Category | Test Count | Coverage |
|---------------|------------|----------|
| **Integration Workflow** | 9 tests | End-to-end webhook lifecycle, retry mechanisms, bulk operations |
| **Security & Auth** | 13 tests | Authentication, authorization, input validation, CSRF protection |
| **Error Handling** | 18 tests | Edge cases, malformed data, timeout scenarios, concurrent processing |
| **API Endpoints** | 12 tests | All API routes, response formats, rate limiting, authentication |
| **Performance** | 10 tests | Load testing, memory usage, database optimization, caching |
| **UI Interaction** | 12 tests | Frontend functionality, form validation, responsive design |

### 🔧 Test Environment Setup

The test suite automatically configures the testing environment:

```bash
# Test environment is auto-configured
# - Creates .env.testing from .env.example
# - Generates application encryption key
# - Sets up test database
# - Configures Laravel factories and seeders
```

### 📈 Continuous Integration

Integrate with CI/CD pipelines:

```yaml
# GitHub Actions example
name: Test Suite
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - name: Install Dependencies
        run: composer install
      - name: Run Test Scenarios
        run: ./run-test-scenarios.sh --coverage
```

### 📚 Test Documentation

Comprehensive test documentation available in [`TEST_SCENARIOS.md`](TEST_SCENARIOS.md) including:
- Detailed test scenario descriptions
- Setup and configuration instructions
- Troubleshooting guide
- Best practices for test development

## 🛠 CLI Commands

HookBytes includes powerful CLI tools:

```bash
# List all webhook endpoints
php artisan webhook:list

# Create a new project
php artisan webhook:project:create "My Project"

# Test webhook endpoint
php artisan webhook:test {endpoint-id}

# Replay failed events
php artisan webhook:replay --status=failed --project="My Project"
```

## 🔍 Monitoring

### Laravel Telescope
Access the Telescope dashboard at `/telescope` for:
- Request monitoring
- Query analysis
- Job tracking
- Exception handling
- Performance profiling

### Logging
Comprehensive logging includes:
- Webhook receipt and processing
- API access and authentication
- Error tracking and debugging
- Performance metrics

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Development Setup

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

### Code Style
- Follow PSR-12 coding standards
- Use Laravel best practices
- Write comprehensive tests
- Document new features

## 📄 License

HookBytes is open-source software licensed under the [MIT License](LICENSE).

## 🆘 Support

- **Documentation**: [hookbytes.io](https://hookbytes.io)
- **Issues**: [GitHub Issues](https://github.com/noibilism/hookbytes/issues)
- **Discussions**: [GitHub Discussions](https://github.com/noibilism/hookbytes/discussions)
- **Community**: [Discord Server](https://discord.gg/hookbytes)

## 🗺 Roadmap

### ✅ Recently Completed
- [x] **Comprehensive Test Suite**: 74+ test scenarios across 6 categories
- [x] **Automated Test Runner**: One-command testing with detailed reporting
- [x] **CI/CD Integration**: Ready-to-use pipeline configurations
- [x] **Test Documentation**: Complete testing guides and best practices

### 🚀 Upcoming Features
- [ ] **Webhook Transformations**: Transform payloads before delivery
- [ ] **Advanced Filtering**: Complex event filtering and routing
- [ ] **Webhook Proxy**: Proxy mode for development environments
- [ ] **Multi-region Deployment**: Global webhook processing
- [ ] **Webhook Marketplace**: Pre-built integrations
- [ ] **GraphQL API**: Alternative to REST API
- [ ] **Real-time Dashboard**: WebSocket-based live updates
- [ ] **Webhook Analytics**: Advanced analytics and insights

### 🧪 Testing Enhancements
- [ ] **Visual Regression Testing**: UI component testing
- [ ] **Load Testing Dashboard**: Real-time performance monitoring
- [ ] **Test Data Management**: Advanced test data generation
- [ ] **Cross-browser Testing**: Automated browser compatibility tests

## 🌟 Star History

If you find HookBytes useful, please consider giving it a star on GitHub!

---

**Built with ❤️ by the HookBytes team**

*Making webhook management simple, powerful, and free for everyone.*
