# Supervisor Setup for Laravel Webhook Service

This document explains how to set up and manage supervisor on the host environment to ensure all critical processes for the Laravel Webhook Service are running continuously.

## Overview

The Laravel Webhook Service requires several background processes to function properly:

1. **Laravel Horizon** - Manages queue workers for webhook deliveries
2. **Laravel Scheduler** - Handles scheduled tasks and maintenance jobs
3. **Queue Workers** - Fallback queue processing (disabled by default, Horizon preferred)

## Quick Start

### 1. Install and Configure Supervisor

```bash
# Make the script executable (if not already)
chmod +x setup-supervisor.sh

# Run the complete setup
./setup-supervisor.sh install
```

### 2. Verify Services are Running

```bash
# Check service status
./setup-supervisor.sh status

# Or use supervisor directly
sudo supervisorctl status
```

## Script Commands

| Command | Description |
|---------|-------------|
| `./setup-supervisor.sh install` | Complete installation and configuration |
| `./setup-supervisor.sh status` | Show current service status |
| `./setup-supervisor.sh start` | Start all services |
| `./setup-supervisor.sh stop` | Stop all services |
| `./setup-supervisor.sh restart` | Restart all services |
| `./setup-supervisor.sh logs` | Show recent logs from all services |
| `./setup-supervisor.sh uninstall` | Remove supervisor configuration |
| `./setup-supervisor.sh help` | Show help message |

## What the Script Does

### 1. System Detection and Installation
- Detects your operating system (macOS, Debian/Ubuntu, RedHat/CentOS)
- Installs supervisor using the appropriate package manager
- Creates necessary directories for logs and configuration

### 2. Service Configuration

Creates supervisor configuration files for:

#### Laravel Horizon (`laravel-webhook-horizon.conf`)
```ini
[program:laravel-webhook-horizon]
process_name=%(program_name)s
command=/path/to/php /path/to/project/artisan horizon
directory=/path/to/project
user=your-user
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
redirect_stderr=true
stdout_logfile=/var/log/supervisor/laravel-webhook-horizon.log
stopwaitsecs=3600
```

#### Laravel Scheduler (`laravel-webhook-scheduler.conf`)
```ini
[program:laravel-webhook-scheduler]
process_name=%(program_name)s
command=/bin/bash -c "while [ true ]; do (php artisan schedule:run --verbose --no-interaction &); sleep 60; done"
directory=/path/to/project
user=your-user
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
redirect_stderr=true
stdout_logfile=/var/log/supervisor/laravel-webhook-scheduler.log
```

### 3. System Integration
- Creates systemd service (Linux) for automatic startup
- Configures log rotation
- Sets appropriate permissions

## Manual Management

### Supervisor Commands

```bash
# View all processes
sudo supervisorctl status

# Start specific service
sudo supervisorctl start laravel-webhook-horizon
sudo supervisorctl start laravel-webhook-scheduler

# Stop specific service
sudo supervisorctl stop laravel-webhook-horizon
sudo supervisorctl stop laravel-webhook-scheduler

# Restart specific service
sudo supervisorctl restart laravel-webhook-horizon
sudo supervisorctl restart laravel-webhook-scheduler

# Reload configuration
sudo supervisorctl reread
sudo supervisorctl update
```

### Log Management

```bash
# View live logs
sudo tail -f /var/log/supervisor/laravel-webhook-horizon.log
sudo tail -f /var/log/supervisor/laravel-webhook-scheduler.log

# View recent logs
sudo tail -n 100 /var/log/supervisor/laravel-webhook-horizon.log
sudo tail -n 100 /var/log/supervisor/laravel-webhook-scheduler.log
```

## Prerequisites

### System Requirements
- PHP 8.2+ installed and accessible via command line
- Redis server running and accessible
- Proper Laravel environment configuration (`.env` file)
- Database connection configured
- Sufficient system permissions for supervisor installation

### Laravel Requirements
- Application key generated (`php artisan key:generate`)
- Database migrations run (`php artisan migrate`)
- Redis connection configured in `.env`
- Queue connection set to `redis` in `.env`

### Environment Variables

Ensure these are properly configured in your `.env` file:

```env
# Application
APP_KEY=base64:your-generated-key
APP_ENV=production
APP_DEBUG=false

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=webhook_service
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# Horizon
HORIZON_PREFIX=webhook_service_horizon:
```

## Troubleshooting

### Common Issues

#### 1. Permission Denied
```bash
# Fix file permissions
chown -R www-data:www-data /path/to/project/storage
chmod -R 755 /path/to/project/storage
```

#### 2. Redis Connection Failed
```bash
# Check Redis status
redis-cli ping

# Start Redis (Ubuntu/Debian)
sudo systemctl start redis-server

# Start Redis (macOS with Homebrew)
brew services start redis
```

#### 3. Database Connection Failed
```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

#### 4. Horizon Not Processing Jobs
```bash
# Clear and restart Horizon
php artisan horizon:terminate
sudo supervisorctl restart laravel-webhook-horizon

# Check Horizon status
php artisan horizon:status
```

### Log Analysis

#### Check for Common Error Patterns
```bash
# Memory issues
grep -i "memory" /var/log/supervisor/laravel-webhook-*.log

# Database connection issues
grep -i "database\|connection" /var/log/supervisor/laravel-webhook-*.log

# Redis issues
grep -i "redis" /var/log/supervisor/laravel-webhook-*.log

# PHP errors
grep -i "fatal\|error" /var/log/supervisor/laravel-webhook-*.log
```

## Monitoring and Maintenance

### Health Checks

Create a simple health check script:

```bash
#!/bin/bash
# health-check.sh

echo "Checking Laravel Webhook Service health..."

# Check supervisor processes
if ! sudo supervisorctl status laravel-webhook-horizon | grep -q RUNNING; then
    echo "❌ Horizon is not running"
    exit 1
fi

if ! sudo supervisorctl status laravel-webhook-scheduler | grep -q RUNNING; then
    echo "❌ Scheduler is not running"
    exit 1
fi

# Check Redis connection
if ! redis-cli ping > /dev/null 2>&1; then
    echo "❌ Redis is not accessible"
    exit 1
fi

# Check database connection
if ! php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; then
    echo "❌ Database is not accessible"
    exit 1
fi

echo "✅ All services are healthy"
```

### Automated Monitoring

Add to your system crontab for regular health checks:

```bash
# Add to crontab (crontab -e)
*/5 * * * * /path/to/project/health-check.sh >> /var/log/webhook-health.log 2>&1
```

## Production Considerations

### Security
- Run services with minimal required permissions
- Secure Redis with authentication if exposed
- Use environment-specific configuration
- Regular security updates for supervisor and dependencies

### Performance
- Monitor memory usage of Horizon processes
- Adjust `maxProcesses` in Horizon configuration based on load
- Configure appropriate log rotation
- Monitor disk space for log files

### Backup and Recovery
- Backup supervisor configuration files
- Document environment-specific settings
- Test recovery procedures regularly
- Monitor failed job queues

## Integration with Deployment

Include supervisor management in your deployment scripts:

```bash
#!/bin/bash
# deploy.sh

# ... other deployment steps ...

# Restart services after deployment
./setup-supervisor.sh restart

# Verify services are running
./setup-supervisor.sh status
```

This ensures your background processes are properly restarted after code deployments.