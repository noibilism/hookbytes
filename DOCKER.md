# 🐳 Docker Guide for HookBytes

This guide provides comprehensive information about running HookBytes with Docker, including development, production deployment, and troubleshooting.

## 📋 Table of Contents

- [Quick Start](#quick-start)
- [Architecture](#architecture)
- [Development Setup](#development-setup)
- [Production Deployment](#production-deployment)
- [Configuration](#configuration)
- [Troubleshooting](#troubleshooting)
- [Performance Optimization](#performance-optimization)
- [Security Considerations](#security-considerations)

## 🚀 Quick Start

### Prerequisites

- Docker 20.10+
- Docker Compose 2.0+
- Git
- 4GB+ RAM available
- 10GB+ disk space

### One-Command Setup

```bash
git clone https://github.com/noibilism/hookbytes.git
cd hookbytes
./docker-setup.sh
```

## 🏗️ Architecture

### Container Overview

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│     nginx       │    │      app        │    │      db         │
│   (Port 8000)   │◄──►│   Laravel App   │◄──►│    MySQL 8.0    │
│   Web Server    │    │   PHP 8.2-FPM   │    │   (Port 3306)   │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                              │
                              ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│     queue       │    │   scheduler     │    │     redis       │
│ Queue Worker    │    │ Task Scheduler  │    │ Cache & Queue   │
│   Background    │    │   Cron Jobs     │    │  (Port 6379)    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### Network Architecture

- **hookbytes-network**: Internal Docker bridge network
- **Port Mapping**: Only nginx (8000), db (3306), and redis (6379) exposed
- **Service Discovery**: Containers communicate via service names

## 🛠️ Development Setup

### Environment Files

1. **Copy environment template**:
   ```bash
   cp .env.docker .env
   ```

2. **Customize settings**:
   ```bash
   # Edit .env file
   APP_ENV=local
   APP_DEBUG=true
   DB_HOST=db
   REDIS_HOST=redis
   ```

### Development Commands

```bash
# Start development environment
docker-compose up -d

# View logs
docker-compose logs -f

# Access application container
docker-compose exec app bash

# Run artisan commands
docker-compose exec app php artisan migrate
docker-compose exec app php artisan tinker

# Install dependencies
docker-compose exec app composer install
docker-compose exec app npm install

# Run tests
docker-compose exec app php artisan test
docker-compose exec app ./run-test-scenarios.sh
```

### Hot Reloading

The development setup includes:
- **Volume mounting** for real-time code changes
- **Xdebug** for debugging (port 9003)
- **Laravel Telescope** for request debugging
- **Asset watching** with Vite

### Debugging with Xdebug

1. **VS Code Configuration** (`.vscode/launch.json`):
   ```json
   {
     "version": "0.2.0",
     "configurations": [
       {
         "name": "Listen for Xdebug",
         "type": "php",
         "request": "launch",
         "port": 9003,
         "pathMappings": {
           "/var/www/html": "${workspaceFolder}"
         }
       }
     ]
   }
   ```

2. **Start debugging**: Set breakpoints and start the debugger in VS Code

## 🚀 Production Deployment

### Production Setup

1. **Use production compose file**:
   ```bash
   docker-compose -f docker-compose.prod.yml up -d
   ```

2. **Environment configuration**:
   ```bash
   # Production .env settings
   APP_ENV=production
   APP_DEBUG=false
   DB_HOST=db
   REDIS_HOST=redis
   
   # Set secure passwords
   DB_ROOT_PASSWORD=secure_root_password
   DB_PASSWORD=secure_app_password
   REDIS_PASSWORD=secure_redis_password
   ```

### Production Optimizations

- **Multi-stage builds** for smaller images
- **OPcache enabled** for PHP performance
- **Supervisor** for process management
- **Health checks** for all services
- **Resource limits** and restart policies
- **Optimized MySQL** configuration

### SSL/HTTPS Setup

1. **Create SSL directory**:
   ```bash
   mkdir -p docker/nginx/ssl
   ```

2. **Add SSL certificates**:
   ```bash
   # Copy your SSL certificates
   cp your-cert.crt docker/nginx/ssl/
   cp your-key.key docker/nginx/ssl/
   ```

3. **Update nginx configuration** for HTTPS

### Load Balancing

For high-traffic deployments:

```yaml
# docker-compose.scale.yml
services:
  app:
    deploy:
      replicas: 3
  
  nginx:
    depends_on:
      - app
    # Load balancer configuration
```

## ⚙️ Configuration

### Environment Variables

| Variable | Development | Production | Description |
|----------|-------------|------------|--------------|
| `APP_ENV` | local | production | Application environment |
| `APP_DEBUG` | true | false | Debug mode |
| `DB_HOST` | db | db | Database host |
| `REDIS_HOST` | redis | redis | Redis host |
| `QUEUE_CONNECTION` | redis | redis | Queue driver |
| `CACHE_DRIVER` | redis | redis | Cache driver |

### Volume Management

```bash
# List volumes
docker volume ls

# Backup database
docker-compose exec db mysqldump -u root -p hookbytes > backup.sql

# Restore database
docker-compose exec -T db mysql -u root -p hookbytes < backup.sql

# Clean volumes (⚠️ Data loss)
docker-compose down -v
```

### Resource Limits

```yaml
# Production resource limits
services:
  app:
    deploy:
      resources:
        limits:
          cpus: '2.0'
          memory: 1G
        reservations:
          cpus: '0.5'
          memory: 512M
```

## 🔧 Troubleshooting

### Common Issues

#### 1. Port Already in Use
```bash
# Check what's using port 8000
lsof -i :8000

# Kill the process or change port in docker-compose.yml
ports:
  - "8080:80"  # Use port 8080 instead
```

#### 2. Permission Issues
```bash
# Fix storage permissions
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

#### 3. Database Connection Failed
```bash
# Check database status
docker-compose ps db

# View database logs
docker-compose logs db

# Test connection
docker-compose exec app php artisan tinker
# >>> DB::connection()->getPdo();
```

#### 4. Queue Not Processing
```bash
# Check queue worker status
docker-compose ps queue

# Restart queue worker
docker-compose restart queue

# View queue logs
docker-compose logs queue
```

### Health Checks

```bash
# Check all service health
docker-compose ps

# Test application health
curl http://localhost:8000/health

# Test database connectivity
docker-compose exec db mysql -u hookbytes -p -e "SELECT 1;"

# Test Redis connectivity
docker-compose exec redis redis-cli ping
```

### Log Analysis

```bash
# Application logs
docker-compose logs app

# Nginx access logs
docker-compose exec nginx tail -f /var/log/nginx/access.log

# PHP error logs
docker-compose exec app tail -f /var/log/php_errors.log

# Laravel logs
docker-compose exec app tail -f storage/logs/laravel.log
```

## ⚡ Performance Optimization

### PHP Optimization

```ini
# docker/php/php.ini (production)
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0

memory_limit=512M
max_execution_time=300
```

### MySQL Optimization

```ini
# docker/mysql/my.cnf
innodb_buffer_pool_size=512M
innodb_log_file_size=128M
query_cache_size=64M
max_connections=200
```

### Redis Optimization

```bash
# Redis configuration
maxmemory 256mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
```

### Monitoring

```bash
# Container resource usage
docker stats

# System resource usage
docker system df

# Container logs size
docker-compose exec app du -sh /var/log/
```

## 🔒 Security Considerations

### Production Security

1. **Environment Variables**:
   - Use strong passwords
   - Never commit `.env` files
   - Use Docker secrets for sensitive data

2. **Network Security**:
   - Use internal networks
   - Limit exposed ports
   - Implement reverse proxy

3. **Container Security**:
   - Run as non-root user
   - Use minimal base images
   - Regular security updates

4. **Data Security**:
   - Encrypt data at rest
   - Use SSL/TLS for connections
   - Regular backups

### Security Checklist

- [ ] Strong database passwords
- [ ] Redis authentication enabled
- [ ] SSL certificates configured
- [ ] Firewall rules in place
- [ ] Regular security updates
- [ ] Log monitoring enabled
- [ ] Backup strategy implemented

## 📚 Additional Resources

- [Docker Best Practices](https://docs.docker.com/develop/best-practices/)
- [Laravel Docker Documentation](https://laravel.com/docs/deployment#docker)
- [MySQL Docker Hub](https://hub.docker.com/_/mysql)
- [Redis Docker Hub](https://hub.docker.com/_/redis)
- [Nginx Docker Hub](https://hub.docker.com/_/nginx)

## 🆘 Getting Help

If you encounter issues:

1. Check the [troubleshooting section](#troubleshooting)
2. Review container logs: `docker-compose logs`
3. Check GitHub issues
4. Join our community discussions

---

**Happy Dockerizing! 🐳**