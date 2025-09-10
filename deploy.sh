#!/bin/bash

# HookBytes Deployment Script for Ubuntu Server
# This script automates the deployment of HookBytes webhook gateway

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if running as root
check_root() {
    if [[ $EUID -eq 0 ]]; then
        print_error "This script should not be run as root for security reasons."
        print_error "Please run as a regular user with sudo privileges."
        exit 1
    fi
}

# Function to check Ubuntu version
check_ubuntu() {
    if ! grep -q "Ubuntu" /etc/os-release; then
        print_error "This script is designed for Ubuntu servers only."
        exit 1
    fi
    
    VERSION=$(lsb_release -rs)
    print_status "Detected Ubuntu $VERSION"
}

# Function to update system packages
update_system() {
    print_status "Updating system packages..."
    sudo apt update
    sudo apt upgrade -y
}

# Function to install required packages
install_dependencies() {
    print_status "Installing required packages..."
    
    # Install basic dependencies
    sudo apt install -y \
        curl \
        wget \
        git \
        unzip \
        software-properties-common \
        apt-transport-https \
        ca-certificates \
        gnupg \
        lsb-release
    
    # Add PHP repository
    sudo add-apt-repository ppa:ondrej/php -y
    sudo apt update
    
    # Install PHP and extensions
    sudo apt install -y \
        php8.2 \
        php8.2-fpm \
        php8.2-cli \
        php8.2-common \
        php8.2-mysql \
        php8.2-zip \
        php8.2-gd \
        php8.2-mbstring \
        php8.2-curl \
        php8.2-xml \
        php8.2-bcmath \
        php8.2-sqlite3 \
        php8.2-intl \
        php8.2-redis
    
    # Install Composer
    if ! command -v composer &> /dev/null; then
        print_status "Installing Composer..."
        curl -sS https://getcomposer.org/installer | php
        sudo mv composer.phar /usr/local/bin/composer
        sudo chmod +x /usr/local/bin/composer
    fi
    
    # Install Node.js and npm
    if ! command -v node &> /dev/null; then
        print_status "Installing Node.js..."
        curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
        sudo apt install -y nodejs
    fi
    
    # Install Nginx
    sudo apt install -y nginx
    
    # Install MySQL
    sudo apt install -y mysql-server
    
    # Install Redis
    sudo apt install -y redis-server
    
    # Install Supervisor for queue management
    sudo apt install -y supervisor
}

# Function to secure MySQL installation
secure_mysql() {
    print_status "Securing MySQL installation..."
    sudo mysql_secure_installation
}

# Function to get database configuration
get_database_config() {
    echo
    print_status "Database Configuration"
    echo "==========================================="
    
    read -p "Database Host [localhost]: " DB_HOST
    DB_HOST=${DB_HOST:-localhost}
    
    read -p "Database Port [3306]: " DB_PORT
    DB_PORT=${DB_PORT:-3306}
    
    read -p "Database Name: " DB_DATABASE
    while [[ -z "$DB_DATABASE" ]]; do
        print_error "Database name cannot be empty!"
        read -p "Database Name: " DB_DATABASE
    done
    
    read -p "Database Username: " DB_USERNAME
    while [[ -z "$DB_USERNAME" ]]; do
        print_error "Database username cannot be empty!"
        read -p "Database Username: " DB_USERNAME
    done
    
    read -s -p "Database Password: " DB_PASSWORD
    echo
    while [[ -z "$DB_PASSWORD" ]]; do
        print_error "Database password cannot be empty!"
        read -s -p "Database Password: " DB_PASSWORD
        echo
    done
}

# Function to get SMTP configuration
get_smtp_config() {
    echo
    print_status "SMTP Configuration"
    echo "==========================================="
    
    read -p "SMTP Host: " MAIL_HOST
    while [[ -z "$MAIL_HOST" ]]; do
        print_error "SMTP host cannot be empty!"
        read -p "SMTP Host: " MAIL_HOST
    done
    
    read -p "SMTP Port [587]: " MAIL_PORT
    MAIL_PORT=${MAIL_PORT:-587}
    
    read -p "SMTP Username: " MAIL_USERNAME
    while [[ -z "$MAIL_USERNAME" ]]; do
        print_error "SMTP username cannot be empty!"
        read -p "SMTP Username: " MAIL_USERNAME
    done
    
    read -s -p "SMTP Password: " MAIL_PASSWORD
    echo
    while [[ -z "$MAIL_PASSWORD" ]]; do
        print_error "SMTP password cannot be empty!"
        read -s -p "SMTP Password: " MAIL_PASSWORD
        echo
    done
    
    read -p "SMTP Encryption [tls]: " MAIL_ENCRYPTION
    MAIL_ENCRYPTION=${MAIL_ENCRYPTION:-tls}
    
    read -p "From Email Address: " MAIL_FROM_ADDRESS
    while [[ -z "$MAIL_FROM_ADDRESS" ]]; do
        print_error "From email address cannot be empty!"
        read -p "From Email Address: " MAIL_FROM_ADDRESS
    done
    
    read -p "From Name [HookBytes]: " MAIL_FROM_NAME
    MAIL_FROM_NAME=${MAIL_FROM_NAME:-HookBytes}
}

# Function to get application configuration
get_app_config() {
    echo
    print_status "Application Configuration"
    echo "==========================================="
    
    read -p "Application URL [http://localhost]: " APP_URL
    APP_URL=${APP_URL:-http://localhost}
    
    read -p "Application Environment [production]: " APP_ENV
    APP_ENV=${APP_ENV:-production}
}

# Function to setup application
setup_application() {
    print_status "Setting up HookBytes application..."
    
    # Create application directory
    APP_DIR="/var/www/hookbytes"
    sudo mkdir -p $APP_DIR
    
    # Clone or copy application files
    if [[ -d ".git" ]]; then
        print_status "Copying application files..."
        sudo cp -r . $APP_DIR/
    else
        print_status "Cloning from repository..."
        sudo git clone https://github.com/noibilism/hookbytes.git $APP_DIR
    fi
    
    # Set proper ownership
    sudo chown -R $USER:www-data $APP_DIR
    sudo chmod -R 755 $APP_DIR
    sudo chmod -R 775 $APP_DIR/storage
    sudo chmod -R 775 $APP_DIR/bootstrap/cache
    
    cd $APP_DIR
    
    # Install PHP dependencies
    print_status "Installing PHP dependencies..."
    composer install --no-dev --optimize-autoloader
    
    # Install Node.js dependencies and build assets
    print_status "Installing Node.js dependencies and building assets..."
    npm install
    npm run build
    
    # Create .env file
    print_status "Creating environment configuration..."
    cp .env.example .env
    
    # Generate application key
    php artisan key:generate
    
    # Update .env file with user inputs
    sed -i "s/APP_ENV=.*/APP_ENV=$APP_ENV/" .env
    sed -i "s|APP_URL=.*|APP_URL=$APP_URL|" .env
    sed -i "s/DB_HOST=.*/DB_HOST=$DB_HOST/" .env
    sed -i "s/DB_PORT=.*/DB_PORT=$DB_PORT/" .env
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=$DB_DATABASE/" .env
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=$DB_USERNAME/" .env
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/" .env
    sed -i "s/MAIL_HOST=.*/MAIL_HOST=$MAIL_HOST/" .env
    sed -i "s/MAIL_PORT=.*/MAIL_PORT=$MAIL_PORT/" .env
    sed -i "s/MAIL_USERNAME=.*/MAIL_USERNAME=$MAIL_USERNAME/" .env
    sed -i "s/MAIL_PASSWORD=.*/MAIL_PASSWORD=$MAIL_PASSWORD/" .env
    sed -i "s/MAIL_ENCRYPTION=.*/MAIL_ENCRYPTION=$MAIL_ENCRYPTION/" .env
    sed -i "s/MAIL_FROM_ADDRESS=.*/MAIL_FROM_ADDRESS=$MAIL_FROM_ADDRESS/" .env
    sed -i "s/MAIL_FROM_NAME=.*/MAIL_FROM_NAME=\"$MAIL_FROM_NAME\"/" .env
    
    # Set queue driver to database
    sed -i "s/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=database/" .env
    
    # Set cache and session drivers
    sed -i "s/CACHE_DRIVER=.*/CACHE_DRIVER=redis/" .env
    sed -i "s/SESSION_DRIVER=.*/SESSION_DRIVER=redis/" .env
}

# Function to setup database
setup_database() {
    print_status "Setting up database..."
    
    # Create database
    mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS $DB_DATABASE;"
    mysql -u root -p -e "CREATE USER IF NOT EXISTS '$DB_USERNAME'@'$DB_HOST' IDENTIFIED BY '$DB_PASSWORD';"
    mysql -u root -p -e "GRANT ALL PRIVILEGES ON $DB_DATABASE.* TO '$DB_USERNAME'@'$DB_HOST';"
    mysql -u root -p -e "FLUSH PRIVILEGES;"
    
    # Run migrations
    cd $APP_DIR
    php artisan migrate --force
    
    # Seed database (optional)
    read -p "Do you want to seed the database with sample data? [y/N]: " SEED_DB
    if [[ $SEED_DB =~ ^[Yy]$ ]]; then
        php artisan db:seed --force
    fi
}

# Function to configure Nginx
configure_nginx() {
    print_status "Configuring Nginx..."
    
    # Create Nginx configuration
    sudo tee /etc/nginx/sites-available/hookbytes > /dev/null <<EOF
server {
    listen 80;
    server_name _;
    root /var/www/hookbytes/public;
    
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    
    index index.php;
    
    charset utf-8;
    
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    
    error_page 404 /index.php;
    
    location ~ \.php\$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF
    
    # Enable site
    sudo ln -sf /etc/nginx/sites-available/hookbytes /etc/nginx/sites-enabled/
    sudo rm -f /etc/nginx/sites-enabled/default
    
    # Test and reload Nginx
    sudo nginx -t
    sudo systemctl reload nginx
}

# Function to configure supervisor for queue workers
configure_supervisor() {
    print_status "Configuring Supervisor for queue workers..."
    
    sudo tee /etc/supervisor/conf.d/hookbytes-worker.conf > /dev/null <<EOF
[program:hookbytes-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/hookbytes/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/hookbytes/storage/logs/worker.log
stopwaitsecs=3600
EOF
    
    sudo supervisorctl reread
    sudo supervisorctl update
    sudo supervisorctl start hookbytes-worker:*
}

# Function to setup systemd services
setup_services() {
    print_status "Enabling and starting services..."
    
    # Enable and start services
    sudo systemctl enable nginx
    sudo systemctl enable php8.2-fpm
    sudo systemctl enable mysql
    sudo systemctl enable redis-server
    sudo systemctl enable supervisor
    
    sudo systemctl start nginx
    sudo systemctl start php8.2-fpm
    sudo systemctl start mysql
    sudo systemctl start redis-server
    sudo systemctl start supervisor
}

# Function to setup firewall
setup_firewall() {
    print_status "Configuring firewall..."
    
    sudo ufw allow ssh
    sudo ufw allow 'Nginx Full'
    sudo ufw --force enable
}

# Function to create deployment script
create_update_script() {
    print_status "Creating update script..."
    
    sudo tee /usr/local/bin/hookbytes-update > /dev/null <<'EOF'
#!/bin/bash
cd /var/www/hookbytes
git pull origin main
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo supervisorctl restart hookbytes-worker:*
sudo systemctl reload nginx
echo "HookBytes updated successfully!"
EOF
    
    sudo chmod +x /usr/local/bin/hookbytes-update
}

# Main deployment function
main() {
    echo
    echo "==========================================="
    echo "    HookBytes Deployment Script"
    echo "==========================================="
    echo
    
    check_root
    check_ubuntu
    
    print_status "Starting HookBytes deployment..."
    
    # Get configuration from user
    get_app_config
    get_database_config
    get_smtp_config
    
    # Confirm before proceeding
    echo
    print_warning "Please review your configuration:"
    echo "App URL: $APP_URL"
    echo "App Environment: $APP_ENV"
    echo "Database: $DB_USERNAME@$DB_HOST:$DB_PORT/$DB_DATABASE"
    echo "SMTP: $MAIL_USERNAME@$MAIL_HOST:$MAIL_PORT"
    echo
    read -p "Do you want to proceed with the deployment? [y/N]: " CONFIRM
    if [[ ! $CONFIRM =~ ^[Yy]$ ]]; then
        print_error "Deployment cancelled."
        exit 1
    fi
    
    # Start deployment
    update_system
    install_dependencies
    secure_mysql
    setup_application
    setup_database
    configure_nginx
    configure_supervisor
    setup_services
    setup_firewall
    create_update_script
    
    echo
    print_success "==========================================="
    print_success "    HookBytes deployed successfully!"
    print_success "==========================================="
    echo
    print_status "Your HookBytes installation is ready at: $APP_URL"
    print_status "To update HookBytes in the future, run: sudo hookbytes-update"
    echo
    print_warning "Important next steps:"
    echo "1. Configure your domain/DNS to point to this server"
    echo "2. Set up SSL certificate (recommended: Let's Encrypt)"
    echo "3. Review and customize the configuration in /var/www/hookbytes/.env"
    echo "4. Set up regular backups for your database"
    echo
}

# Run main function
main "$@"