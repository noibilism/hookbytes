#!/bin/bash

# Laravel Webhook Service - Dependencies Setup Script
# This script sets up all required dependencies for both frontend and backend

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP_MIN_VERSION="8.2"
NODE_MIN_VERSION="18"
REQUIRED_PHP_EXTENSIONS=("pdo" "pdo_mysql" "pdo_sqlite" "mbstring" "openssl" "tokenizer" "xml" "ctype" "json" "bcmath" "fileinfo" "redis" "zip" "gd" "intl" "soap" "xmlwriter" "simplexml" "dom")
REQUIRED_SYSTEM_PACKAGES=("git" "curl" "unzip")

echo -e "${BLUE}Laravel Webhook Service - Dependencies Setup${NC}"
echo -e "${BLUE}=============================================${NC}"
echo

# Function to print status messages
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

# Detect OS and package manager
detect_os() {
    if [[ "$OSTYPE" == "darwin"* ]]; then
        OS="macos"
        PACKAGE_MANAGER="brew"
    elif [[ -f /etc/debian_version ]]; then
        OS="debian"
        PACKAGE_MANAGER="apt"
    elif [[ -f /etc/redhat-release ]]; then
        OS="redhat"
        PACKAGE_MANAGER="yum"
    else
        print_error "Unsupported operating system"
        exit 1
    fi
    print_status "Detected OS: $OS with package manager: $PACKAGE_MANAGER"
}

# Check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Version comparison function
version_ge() {
    printf '%s\n%s\n' "$2" "$1" | sort -V -C
}

# Check and install system requirements
check_system_requirements() {
    print_status "Checking system requirements..."
    
    # Check and install required system packages
    for package in "${REQUIRED_SYSTEM_PACKAGES[@]}"; do
        if ! command_exists "$package"; then
            print_status "Installing required package: $package"
            case $PACKAGE_MANAGER in
                "brew")
                    brew install "$package"
                    ;;
                "apt")
                    sudo apt-get install -y "$package"
                    ;;
                "yum")
                    sudo yum install -y "$package"
                    ;;
            esac
        else
            print_status "Package '$package' is already installed"
        fi
    done
    
    print_success "System requirements check passed"
}

# Install package manager if needed
install_package_manager() {
    case $OS in
        "macos")
            if ! command_exists brew; then
                print_status "Installing Homebrew..."
                /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
                # Add Homebrew to PATH for current session
                if [[ -f "/opt/homebrew/bin/brew" ]]; then
                    eval "$(/opt/homebrew/bin/brew shellenv)"
                elif [[ -f "/usr/local/bin/brew" ]]; then
                    eval "$(/usr/local/bin/brew shellenv)"
                fi
            fi
            ;;
        "debian")
            sudo apt-get update
            ;;
        "redhat")
            sudo yum update -y
            ;;
    esac
}

# Check and install PHP
check_install_php() {
    print_status "Checking PHP installation..."
    
    if command_exists php; then
        PHP_VERSION=$(php -r "echo PHP_VERSION;")
        if version_ge "$PHP_VERSION" "$PHP_MIN_VERSION"; then
            print_success "PHP $PHP_VERSION is installed and meets requirements"
        else
            print_error "PHP $PHP_VERSION is installed but version $PHP_MIN_VERSION or higher is required"
            exit 1
        fi
    else
        print_status "Installing PHP $PHP_MIN_VERSION..."
        case $PACKAGE_MANAGER in
            "brew")
                brew install php@8.2
                brew link php@8.2 --force
                ;;
            "apt")
                sudo apt-get install -y software-properties-common
                sudo add-apt-repository ppa:ondrej/php -y
                sudo apt-get update
                sudo apt-get install -y php8.2 php8.2-cli php8.2-fpm php8.2-common php8.2-mysql php8.2-sqlite3 php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-zip php8.2-gd php8.2-intl php8.2-redis
                ;;
            "yum")
                sudo yum install -y epel-release
                sudo yum install -y php82 php82-cli
                ;;
        esac
    fi
    
    # Check PHP extensions
    print_status "Checking PHP extensions..."
    for ext in "${REQUIRED_PHP_EXTENSIONS[@]}"; do
        if ! php -m | grep -q "^$ext$"; then
            print_status "Installing PHP extension: $ext"
            case $PACKAGE_MANAGER in
                "brew")
                    # Most extensions come with PHP on macOS via Homebrew
                    if [[ "$ext" == "redis" ]]; then
                        pecl install redis
                    fi
                    ;;
                "apt")
                    case $ext in
                        "pdo_mysql") sudo apt-get install -y php8.2-mysql ;;
                        "pdo_sqlite") sudo apt-get install -y php8.2-sqlite3 ;;
                        "redis") sudo apt-get install -y php8.2-redis ;;
                        "gd") sudo apt-get install -y php8.2-gd ;;
                        "intl") sudo apt-get install -y php8.2-intl ;;
                        "soap") sudo apt-get install -y php8.2-soap ;;
                        "zip") sudo apt-get install -y php8.2-zip ;;
                        "xml"|"xmlwriter"|"simplexml"|"dom") sudo apt-get install -y php8.2-xml ;;
                        "mbstring") sudo apt-get install -y php8.2-mbstring ;;
                        "bcmath") sudo apt-get install -y php8.2-bcmath ;;
                        "ctype"|"tokenizer"|"fileinfo"|"openssl"|"json"|"pdo") 
                            # These are typically included in php8.2-common or php8.2-cli
                            sudo apt-get install -y php8.2-common php8.2-cli
                            ;;
                        *) sudo apt-get install -y "php8.2-$ext" ;;
                    esac
                    ;;
                "yum")
                    case $ext in
                        "pdo_mysql") sudo yum install -y php82-mysqlnd ;;
                        "pdo_sqlite") sudo yum install -y php82-sqlite3 ;;
                        "redis") sudo yum install -y php82-redis ;;
                        *) sudo yum install -y "php82-$ext" ;;
                    esac
                    ;;
            esac
        fi
    done
}

# Check and install Composer
check_install_composer() {
    print_status "Checking Composer installation..."
    
    if command_exists composer; then
        print_success "Composer is already installed"
    else
        print_status "Installing Composer..."
        curl -sS https://getcomposer.org/installer | php
        sudo mv composer.phar /usr/local/bin/composer
        chmod +x /usr/local/bin/composer
    fi
}

# Check and install Node.js
check_install_nodejs() {
    print_status "Checking Node.js installation..."
    
    if command_exists node; then
        NODE_VERSION=$(node --version | sed 's/v//')
        if version_ge "$NODE_VERSION" "$NODE_MIN_VERSION"; then
            print_success "Node.js $NODE_VERSION is installed and meets requirements"
        else
            print_warning "Node.js $NODE_VERSION is installed but version $NODE_MIN_VERSION or higher is recommended"
        fi
    else
        print_status "Installing Node.js..."
        case $PACKAGE_MANAGER in
            "brew")
                brew install node
                ;;
            "apt")
                curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
                sudo apt-get install -y nodejs
                ;;
            "yum")
                curl -fsSL https://rpm.nodesource.com/setup_lts.x | sudo bash -
                sudo yum install -y nodejs
                ;;
        esac
    fi
    
    # Check npm
    if ! command_exists npm; then
        print_error "npm is not installed with Node.js"
        exit 1
    fi
}

# Check and install Redis
check_install_redis() {
    print_status "Checking Redis installation..."
    
    if command_exists redis-server || command_exists redis-cli; then
        print_success "Redis is already installed"
    else
        print_status "Installing Redis..."
        case $PACKAGE_MANAGER in
            "brew")
                brew install redis
                ;;
            "apt")
                sudo apt-get install -y redis-server
                ;;
            "yum")
                sudo yum install -y redis
                ;;
        esac
    fi
    
    # Start Redis service
    print_status "Starting Redis service..."
    case $OS in
        "macos")
            if command_exists brew; then
                brew services start redis || true
            fi
            ;;
        "debian")
            sudo systemctl start redis-server || true
            sudo systemctl enable redis-server || true
            ;;
        "redhat")
            sudo systemctl start redis || true
            sudo systemctl enable redis || true
            ;;
    esac
}

# Check and install database
check_install_database() {
    print_status "Checking database options..."
    
    # Check for MySQL
    if command_exists mysql; then
        print_success "MySQL is available"
        DB_AVAILABLE="mysql"
    # Check for SQLite (usually available by default)
    elif command_exists sqlite3; then
        print_success "SQLite is available"
        DB_AVAILABLE="sqlite"
    else
        print_status "Installing SQLite as default database..."
        case $PACKAGE_MANAGER in
            "brew")
                brew install sqlite
                ;;
            "apt")
                sudo apt-get install -y sqlite3
                ;;
            "yum")
                sudo yum install -y sqlite
                ;;
        esac
        DB_AVAILABLE="sqlite"
    fi
}

# Setup Laravel environment
setup_laravel_environment() {
    print_status "Setting up Laravel environment..."
    
    cd "$APP_PATH"
    
    # Copy .env file if it doesn't exist
    if [[ ! -f ".env" ]]; then
        if [[ -f ".env.example" ]]; then
            cp .env.example .env
            print_status "Created .env file from .env.example"
        else
            print_error ".env.example file not found"
            exit 1
        fi
    fi
    
    # Install PHP dependencies
    print_status "Installing PHP dependencies..."
    composer install --optimize-autoloader
    
    # Generate application key if not set
    if ! grep -q "APP_KEY=base64:" .env; then
        print_status "Generating application key..."
        php artisan key:generate
    fi
    
    # Create storage link
    print_status "Creating storage link..."
    php artisan storage:link || true
    
    # Set proper permissions
    print_status "Setting proper permissions..."
    chmod -R 755 storage bootstrap/cache
    
    if [[ "$OS" != "macos" ]]; then
        # On Linux, set www-data ownership if it exists
        if id "www-data" &>/dev/null; then
            sudo chown -R www-data:www-data storage bootstrap/cache
        fi
    fi
}

# Setup frontend dependencies
setup_frontend_dependencies() {
    print_status "Setting up frontend dependencies..."
    
    cd "$APP_PATH"
    
    # Install Node.js dependencies
    if [[ -f "package.json" ]]; then
        print_status "Installing Node.js dependencies..."
        npm install
        
        # Build frontend assets
        print_status "Building frontend assets..."
        npm run build
    else
        print_warning "package.json not found, skipping frontend setup"
    fi
}

# Setup database
setup_database() {
    print_status "Setting up database..."
    
    cd "$APP_PATH"
    
    # Update .env for database configuration
    if [[ "$DB_AVAILABLE" == "sqlite" ]]; then
        print_status "Configuring SQLite database..."
        
        # Update .env file for SQLite
        sed -i.bak 's/DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env
        sed -i.bak "s|DB_DATABASE=.*|DB_DATABASE=$APP_PATH/database/database.sqlite|" .env
        
        # Create SQLite database file
        touch database/database.sqlite
        
        # Comment out other DB settings
        sed -i.bak 's/^DB_HOST=/#DB_HOST=/' .env
        sed -i.bak 's/^DB_PORT=/#DB_PORT=/' .env
        sed -i.bak 's/^DB_USERNAME=/#DB_USERNAME=/' .env
        sed -i.bak 's/^DB_PASSWORD=/#DB_PASSWORD=/' .env
    fi
    
    # Run migrations
    print_status "Running database migrations..."
    php artisan migrate --force
    
    # Seed database
    print_status "Seeding database..."
    php artisan db:seed --force
}

# Test installation
test_installation() {
    print_status "Testing installation..."
    
    cd "$APP_PATH"
    
    # Test PHP
    if ! php -v >/dev/null 2>&1; then
        print_error "PHP test failed"
        return 1
    fi
    
    # Test Composer
    if ! composer --version >/dev/null 2>&1; then
        print_error "Composer test failed"
        return 1
    fi
    
    # Test Laravel
    if ! php artisan --version >/dev/null 2>&1; then
        print_error "Laravel test failed"
        return 1
    fi
    
    # Test database connection
    if ! php artisan tinker --execute="DB::connection()->getPdo();" >/dev/null 2>&1; then
        print_error "Database connection test failed"
        return 1
    fi
    
    # Test Redis connection
    if ! php artisan tinker --execute="Redis::ping();" >/dev/null 2>&1; then
        print_warning "Redis connection test failed - Redis might not be running"
    fi
    
    # Test Node.js and npm
    if [[ -f "package.json" ]]; then
        if ! node --version >/dev/null 2>&1; then
            print_error "Node.js test failed"
            return 1
        fi
        
        if ! npm --version >/dev/null 2>&1; then
            print_error "npm test failed"
            return 1
        fi
    fi
    
    print_success "All tests passed!"
}

# Start services
start_services() {
    print_status "Starting services..."
    
    # Start Redis
    case $OS in
        "macos")
            if command_exists brew; then
                brew services start redis >/dev/null 2>&1 || true
            fi
            ;;
        "debian")
            sudo systemctl start redis-server >/dev/null 2>&1 || true
            ;;
        "redhat")
            sudo systemctl start redis >/dev/null 2>&1 || true
            ;;
    esac
    
    print_success "Services started"
}

# Show next steps
show_next_steps() {
    echo
    echo -e "${GREEN}✅ Dependencies setup completed successfully!${NC}"
    echo
    echo -e "${BLUE}Next steps:${NC}"
    echo -e "  1. Review and update .env file with your specific configuration"
    echo -e "  2. Start the development server: ${YELLOW}php artisan serve${NC}"
    echo -e "  3. Access the application at: ${YELLOW}http://localhost:8000${NC}"
    echo -e "  4. Access the dashboard at: ${YELLOW}http://localhost:8000/dashboard${NC}"
    echo -e "  5. Access Horizon at: ${YELLOW}http://localhost:8000/horizon${NC}"
    echo
    echo -e "${BLUE}Default admin credentials:${NC}"
    echo -e "  Email: ${YELLOW}admin@webhook.local${NC}"
    echo -e "  Password: ${YELLOW}password${NC}"
    echo
    echo -e "${BLUE}Useful commands:${NC}"
    echo -e "  Start development server:  ${YELLOW}php artisan serve${NC}"
    echo -e "  Start queue worker:        ${YELLOW}php artisan queue:work${NC}"
    echo -e "  Start Horizon:             ${YELLOW}php artisan horizon${NC}"
    echo -e "  Run tests:                 ${YELLOW}php artisan test${NC}"
    echo -e "  Clear caches:              ${YELLOW}php artisan optimize:clear${NC}"
    echo
    echo -e "${BLUE}For production deployment:${NC}"
    echo -e "  1. Run: ${YELLOW}./setup-supervisor.sh install${NC}"
    echo -e "  2. Configure your web server (nginx/apache)"
    echo -e "  3. Set up SSL certificates"
    echo -e "  4. Configure environment variables for production"
    echo
}

# Main execution
main() {
    echo -e "${BLUE}Starting dependencies setup for Laravel Webhook Service...${NC}"
    echo
    
    # Detect OS
    detect_os
    
    # Check system requirements
    check_system_requirements
    
    # Install package manager
    install_package_manager
    
    # Install dependencies
    check_install_php
    check_install_composer
    check_install_nodejs
    check_install_redis
    check_install_database
    
    # Setup application
    setup_laravel_environment
    setup_frontend_dependencies
    setup_database
    
    # Start services
    start_services
    
    # Test installation
    test_installation
    
    # Show next steps
    show_next_steps
}

# Handle command line arguments
case "${1:-}" in
    "install")
        main
        ;;
    "test")
        test_installation
        ;;
    "frontend")
        setup_frontend_dependencies
        ;;
    "database")
        setup_database
        ;;
    "services")
        start_services
        ;;
    "help"|"--help"|"-h"|"")
        echo -e "${BLUE}Laravel Webhook Service - Dependencies Setup Script${NC}"
        echo
        echo -e "${YELLOW}Usage:${NC}"
        echo -e "  $0 install     - Complete dependencies installation"
        echo -e "  $0 test        - Test current installation"
        echo -e "  $0 frontend    - Setup frontend dependencies only"
        echo -e "  $0 database    - Setup database only"
        echo -e "  $0 services    - Start services"
        echo -e "  $0 help        - Show this help message"
        echo
        echo -e "${BLUE}What this script installs:${NC}"
        echo -e "  - PHP 8.2+ with required extensions"
        echo -e "  - Composer (PHP package manager)"
        echo -e "  - Node.js 18+ and npm"
        echo -e "  - Redis server"
        echo -e "  - Database (SQLite by default, MySQL if available)"
        echo -e "  - Laravel dependencies and configuration"
        echo -e "  - Frontend assets build"
        echo
        echo -e "${BLUE}Supported operating systems:${NC}"
        echo -e "  - macOS (with Homebrew)"
        echo -e "  - Ubuntu/Debian (with apt)"
        echo -e "  - RedHat/CentOS (with yum)"
        echo
        ;;
    *)
        print_error "Unknown command: $1"
        echo "Run '$0 help' for usage information."
        exit 1
        ;;
esac