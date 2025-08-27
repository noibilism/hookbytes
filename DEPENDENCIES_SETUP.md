# Dependencies Setup Guide

This guide explains how to set up all required dependencies for the Laravel Webhook Service application using the automated setup script.

## Quick Start

```bash
# Make the script executable (if not already)
chmod +x setup-dependencies.sh

# Run the complete setup
./setup-dependencies.sh install
```

## What Gets Installed

The setup script automatically installs and configures:

### Backend Dependencies
- **PHP 8.2+** with required extensions:
  - PDO (MySQL and SQLite)
  - mbstring, openssl, tokenizer, xml
  - ctype, json, bcmath, fileinfo
  - Redis extension
- **Composer** (PHP package manager)
- **Laravel dependencies** via Composer
- **Redis server** for caching and queues
- **Database** (SQLite by default, MySQL if available)

### Frontend Dependencies
- **Node.js 18+** and npm
- **Frontend packages** (Vue.js, Vite, etc.)
- **Built assets** for production

### System Requirements
- Git
- curl
- unzip

## Supported Operating Systems

- **macOS** (with Homebrew)
- **Ubuntu/Debian** (with apt)
- **RedHat/CentOS/Fedora** (with yum)

## Script Commands

### Complete Installation
```bash
./setup-dependencies.sh install
```
Runs the complete setup process including all dependencies, environment configuration, database setup, and asset building.

### Test Installation
```bash
./setup-dependencies.sh test
```
Tests the current installation to verify all components are working correctly.

### Frontend Only
```bash
./setup-dependencies.sh frontend
```
Sets up only the frontend dependencies and builds assets.

### Database Only
```bash
./setup-dependencies.sh database
```
Sets up only the database (migrations and seeding).

### Start Services
```bash
./setup-dependencies.sh services
```
Starts required services (Redis).

### Help
```bash
./setup-dependencies.sh help
```
Displays usage information and available commands.

## What the Script Does

### 1. System Detection
- Detects the operating system
- Identifies the appropriate package manager
- Checks system requirements

### 2. Package Manager Setup
- Installs Homebrew on macOS if needed
- Updates package repositories on Linux

### 3. PHP Installation
- Installs PHP 8.2+ if not present
- Installs required PHP extensions
- Verifies PHP version compatibility

### 4. Composer Installation
- Downloads and installs Composer globally
- Makes Composer available system-wide

### 5. Node.js Installation
- Installs Node.js 18+ and npm
- Verifies Node.js version compatibility

### 6. Redis Installation
- Installs Redis server
- Starts Redis service
- Enables Redis to start on boot (Linux)

### 7. Database Setup
- Configures SQLite as default database
- Creates database file and directory structure
- Updates .env configuration

### 8. Laravel Environment Setup
- Copies .env.example to .env if needed
- Installs PHP dependencies via Composer
- Generates application key
- Creates storage symlink
- Sets proper file permissions

### 9. Frontend Setup
- Installs Node.js dependencies
- Builds production assets with Vite

### 10. Database Migration
- Runs database migrations
- Seeds the database with initial data

### 11. Service Startup
- Starts Redis service
- Verifies service status

### 12. Installation Testing
- Tests PHP functionality
- Tests Composer
- Tests Laravel installation
- Tests database connectivity
- Tests Redis connectivity
- Tests Node.js and npm

## Post-Installation Steps

After running the setup script successfully:

### 1. Review Configuration
Check and update the `.env` file with your specific settings:
```bash
vim .env
```

Key settings to review:
- `APP_URL` - Your application URL
- `DB_*` - Database configuration (if using MySQL)
- `REDIS_*` - Redis configuration
- `MAIL_*` - Email configuration
- `WEBHOOK_*` - Webhook-specific settings

### 2. Start Development Server
```bash
php artisan serve
```
Access the application at: http://localhost:8000

### 3. Access Dashboard
Navigate to: http://localhost:8000/dashboard

Default admin credentials:
- **Email:** admin@webhook.local
- **Password:** password

### 4. Start Background Services

#### Queue Worker
```bash
php artisan queue:work
```

#### Laravel Horizon (recommended)
```bash
php artisan horizon
```
Access Horizon dashboard at: http://localhost:8000/horizon

## Troubleshooting

### Common Issues

#### Permission Errors
```bash
# Fix storage permissions
chmod -R 755 storage bootstrap/cache

# On Linux, set proper ownership
sudo chown -R www-data:www-data storage bootstrap/cache
```

#### Redis Connection Issues
```bash
# Check Redis status
redis-cli ping

# Start Redis manually
# macOS
brew services start redis

# Linux
sudo systemctl start redis-server
```

#### Database Issues
```bash
# Reset database
php artisan migrate:fresh --seed

# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

#### Frontend Build Issues
```bash
# Clear npm cache
npm cache clean --force

# Reinstall dependencies
rm -rf node_modules package-lock.json
npm install

# Rebuild assets
npm run build
```

#### PHP Extension Issues
```bash
# Check installed extensions
php -m

# Install missing extensions (Ubuntu/Debian)
sudo apt-get install php8.2-[extension-name]

# Install missing extensions (macOS)
brew install php@8.2
pecl install [extension-name]
```

### Environment-Specific Issues

#### macOS with Apple Silicon
- Ensure Homebrew is installed for Apple Silicon: `/opt/homebrew/bin/brew`
- Some PHP extensions might need compilation from source

#### Ubuntu/Debian
- Add Ondřej Surý's PPA for latest PHP versions
- Ensure `software-properties-common` is installed

#### RedHat/CentOS
- Enable EPEL repository for additional packages
- Some packages might have different names

## Manual Installation

If the automated script fails, you can install dependencies manually:

### 1. Install PHP 8.2+
```bash
# macOS
brew install php@8.2

# Ubuntu/Debian
sudo apt-get install php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-sqlite3 php8.2-redis php8.2-mbstring php8.2-xml php8.2-bcmath

# RedHat/CentOS
sudo yum install php82 php82-cli php82-mysqlnd php82-sqlite3 php82-redis
```

### 2. Install Composer
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 3. Install Node.js
```bash
# macOS
brew install node

# Ubuntu/Debian
curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
sudo apt-get install -y nodejs

# RedHat/CentOS
curl -fsSL https://rpm.nodesource.com/setup_lts.x | sudo bash -
sudo yum install -y nodejs
```

### 4. Install Redis
```bash
# macOS
brew install redis
brew services start redis

# Ubuntu/Debian
sudo apt-get install redis-server
sudo systemctl start redis-server
sudo systemctl enable redis-server

# RedHat/CentOS
sudo yum install redis
sudo systemctl start redis
sudo systemctl enable redis
```

### 5. Setup Laravel
```bash
# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Setup database
touch database/database.sqlite
php artisan migrate --seed

# Build frontend
npm install
npm run build
```

## Production Considerations

For production deployment:

1. **Use MySQL/PostgreSQL** instead of SQLite
2. **Configure Redis** with persistence and proper memory limits
3. **Set up Supervisor** using the provided `setup-supervisor.sh` script
4. **Configure web server** (Nginx/Apache) with proper SSL
5. **Set environment variables** for production
6. **Enable OPcache** for better PHP performance
7. **Configure log rotation** for application logs
8. **Set up monitoring** for services and application health

## Integration with Deployment

This script can be integrated into your deployment pipeline:

```bash
# In your deployment script
./setup-dependencies.sh install

# Or run specific parts
./setup-dependencies.sh frontend  # For frontend-only deployments
./setup-dependencies.sh database  # For database updates
```

## Support

If you encounter issues:

1. Run the test command: `./setup-dependencies.sh test`
2. Check the troubleshooting section above
3. Review system logs for specific error messages
4. Ensure your system meets the minimum requirements

For additional help, refer to the Laravel documentation and the specific package documentation for any components that fail to install.