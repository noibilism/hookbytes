#!/bin/bash

# Laravel Webhook Service - Supervisor Setup Script
# This script sets up supervisor on the host environment to manage all necessary processes

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
APP_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_USER="${USER}"
PHP_PATH="$(which php)"
SUPERVISOR_CONF_DIR="/etc/supervisor/conf.d"
SUPERVISOR_LOG_DIR="/var/log/supervisor"
APP_LOG_DIR="${APP_PATH}/storage/logs"

echo -e "${BLUE}Laravel Webhook Service - Supervisor Setup${NC}"
echo -e "${BLUE}==========================================${NC}"
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

# Check if running as root for supervisor installation
check_root() {
    if [[ $EUID -eq 0 ]]; then
        print_status "Running as root - can install supervisor"
        return 0
    else
        print_warning "Not running as root - will need sudo for supervisor installation"
        return 1
    fi
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

# Install supervisor if not present
install_supervisor() {
    if command -v supervisord &> /dev/null; then
        print_status "Supervisor is already installed"
        return 0
    fi

    print_status "Installing supervisor..."
    
    case $PACKAGE_MANAGER in
        "brew")
            brew install supervisor
            ;;
        "apt")
            sudo apt-get update
            sudo apt-get install -y supervisor
            ;;
        "yum")
            sudo yum install -y supervisor
            ;;
        *)
            print_error "Unsupported package manager: $PACKAGE_MANAGER"
            exit 1
            ;;
    esac
    
    print_status "Supervisor installed successfully"
}

# Create necessary directories
create_directories() {
    print_status "Creating necessary directories..."
    
    # Create log directories
    sudo mkdir -p "$SUPERVISOR_LOG_DIR"
    mkdir -p "$APP_LOG_DIR"
    
    # Create supervisor config directory if it doesn't exist
    if [[ "$OS" == "macos" ]]; then
        SUPERVISOR_CONF_DIR="/usr/local/etc/supervisor.d"
        sudo mkdir -p "$SUPERVISOR_CONF_DIR"
    else
        sudo mkdir -p "$SUPERVISOR_CONF_DIR"
    fi
    
    print_status "Directories created successfully"
}

# Generate supervisor configuration for Laravel Horizon
generate_horizon_config() {
    local config_file="$SUPERVISOR_CONF_DIR/laravel-webhook-horizon.conf"
    
    print_status "Generating Horizon supervisor configuration..."
    
    sudo tee "$config_file" > /dev/null <<EOF
[program:laravel-webhook-horizon]
process_name=%(program_name)s
command=$PHP_PATH $APP_PATH/artisan horizon
directory=$APP_PATH
user=$APP_USER
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
redirect_stderr=true
stdout_logfile=$SUPERVISOR_LOG_DIR/laravel-webhook-horizon.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=10
stopwaitsecs=3600
EOF
    
    print_status "Horizon configuration created: $config_file"
}

# Generate supervisor configuration for Laravel Scheduler
generate_scheduler_config() {
    local config_file="$SUPERVISOR_CONF_DIR/laravel-webhook-scheduler.conf"
    
    print_status "Generating Scheduler supervisor configuration..."
    
    sudo tee "$config_file" > /dev/null <<EOF
[program:laravel-webhook-scheduler]
process_name=%(program_name)s
command=/bin/bash -c "while [ true ]; do ($PHP_PATH $APP_PATH/artisan schedule:run --verbose --no-interaction &); sleep 60; done"
directory=$APP_PATH
user=$APP_USER
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
redirect_stderr=true
stdout_logfile=$SUPERVISOR_LOG_DIR/laravel-webhook-scheduler.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=10
EOF
    
    print_status "Scheduler configuration created: $config_file"
}

# Generate supervisor configuration for Queue Workers (fallback)
generate_queue_worker_config() {
    local config_file="$SUPERVISOR_CONF_DIR/laravel-webhook-queue.conf"
    
    print_status "Generating Queue Worker supervisor configuration (fallback)..."
    
    sudo tee "$config_file" > /dev/null <<EOF
[program:laravel-webhook-queue]
process_name=%(program_name)s_%(process_num)02d
command=$PHP_PATH $APP_PATH/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --max-jobs=1000
directory=$APP_PATH
user=$APP_USER
autostart=false
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
redirect_stderr=true
stdout_logfile=$SUPERVISOR_LOG_DIR/laravel-webhook-queue.log
stdout_logfile_maxbytes=50MB
stdout_logfile_backups=10
stopwaitsecs=3600
EOF
    
    print_status "Queue Worker configuration created: $config_file (disabled by default - use Horizon instead)"
}

# Update supervisor configuration
update_supervisor() {
    print_status "Updating supervisor configuration..."
    
    if [[ "$OS" == "macos" ]]; then
        # On macOS, supervisor might be managed differently
        if command -v brew &> /dev/null && brew services list | grep -q supervisor; then
            brew services restart supervisor
        else
            sudo supervisorctl reread
            sudo supervisorctl update
        fi
    else
        sudo supervisorctl reread
        sudo supervisorctl update
    fi
    
    print_status "Supervisor configuration updated"
}

# Start supervisor services
start_services() {
    print_status "Starting Laravel Webhook services..."
    
    # Start Horizon
    sudo supervisorctl start laravel-webhook-horizon
    
    # Start Scheduler
    sudo supervisorctl start laravel-webhook-scheduler
    
    print_status "Services started successfully"
}

# Show service status
show_status() {
    print_status "Current service status:"
    echo
    sudo supervisorctl status | grep laravel-webhook || print_warning "No Laravel Webhook services found"
}

# Create environment file check
check_environment() {
    print_status "Checking Laravel environment..."
    
    if [[ ! -f "$APP_PATH/.env" ]]; then
        print_error ".env file not found. Please copy .env.example to .env and configure it."
        exit 1
    fi
    
    # Check if required environment variables are set
    source "$APP_PATH/.env"
    
    if [[ -z "$APP_KEY" ]]; then
        print_error "APP_KEY not set. Run 'php artisan key:generate' first."
        exit 1
    fi
    
    if [[ -z "$REDIS_HOST" ]]; then
        print_warning "REDIS_HOST not set. Make sure Redis is configured."
    fi
    
    print_status "Environment check passed"
}

# Create systemd service for supervisor (Linux only)
create_systemd_service() {
    if [[ "$OS" != "macos" ]]; then
        print_status "Creating systemd service for supervisor..."
        
        sudo tee /etc/systemd/system/laravel-webhook-supervisor.service > /dev/null <<EOF
[Unit]
Description=Laravel Webhook Service Supervisor
After=network.target

[Service]
Type=forking
User=root
ExecStart=/usr/bin/supervisord -c /etc/supervisor/supervisord.conf
ExecReload=/usr/bin/supervisorctl reload
ExecStop=/usr/bin/supervisorctl shutdown
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF
        
        sudo systemctl daemon-reload
        sudo systemctl enable laravel-webhook-supervisor
        
        print_status "Systemd service created and enabled"
    fi
}

# Main execution
main() {
    echo -e "${BLUE}Starting supervisor setup for Laravel Webhook Service...${NC}"
    echo
    
    # Detect OS
    detect_os
    
    # Check environment
    check_environment
    
    # Install supervisor
    install_supervisor
    
    # Create directories
    create_directories
    
    # Generate configurations
    generate_horizon_config
    generate_scheduler_config
    generate_queue_worker_config
    
    # Create systemd service (Linux only)
    create_systemd_service
    
    # Update supervisor
    update_supervisor
    
    # Start services
    start_services
    
    # Show status
    show_status
    
    echo
    echo -e "${GREEN}✅ Supervisor setup completed successfully!${NC}"
    echo
    echo -e "${BLUE}Useful commands:${NC}"
    echo -e "  View status:           ${YELLOW}sudo supervisorctl status${NC}"
    echo -e "  Restart Horizon:       ${YELLOW}sudo supervisorctl restart laravel-webhook-horizon${NC}"
    echo -e "  Restart Scheduler:     ${YELLOW}sudo supervisorctl restart laravel-webhook-scheduler${NC}"
    echo -e "  View Horizon logs:     ${YELLOW}sudo tail -f $SUPERVISOR_LOG_DIR/laravel-webhook-horizon.log${NC}"
    echo -e "  View Scheduler logs:   ${YELLOW}sudo tail -f $SUPERVISOR_LOG_DIR/laravel-webhook-scheduler.log${NC}"
    echo -e "  Stop all services:     ${YELLOW}sudo supervisorctl stop laravel-webhook-*${NC}"
    echo -e "  Start all services:    ${YELLOW}sudo supervisorctl start laravel-webhook-*${NC}"
    echo
    echo -e "${BLUE}Dashboard URLs:${NC}"
    echo -e "  Application:           ${YELLOW}http://localhost:8000${NC}"
    echo -e "  Horizon Dashboard:     ${YELLOW}http://localhost:8000/horizon${NC}"
    echo
}

# Handle command line arguments
case "${1:-}" in
    "install")
        main
        ;;
    "status")
        show_status
        ;;
    "start")
        start_services
        ;;
    "stop")
        print_status "Stopping Laravel Webhook services..."
        sudo supervisorctl stop laravel-webhook-*
        ;;
    "restart")
        print_status "Restarting Laravel Webhook services..."
        sudo supervisorctl restart laravel-webhook-*
        ;;
    "logs")
        print_status "Showing recent logs..."
        echo -e "${YELLOW}Horizon logs:${NC}"
        sudo tail -n 20 "$SUPERVISOR_LOG_DIR/laravel-webhook-horizon.log" 2>/dev/null || echo "No Horizon logs found"
        echo
        echo -e "${YELLOW}Scheduler logs:${NC}"
        sudo tail -n 20 "$SUPERVISOR_LOG_DIR/laravel-webhook-scheduler.log" 2>/dev/null || echo "No Scheduler logs found"
        ;;
    "uninstall")
        print_status "Uninstalling Laravel Webhook supervisor configuration..."
        sudo supervisorctl stop laravel-webhook-* 2>/dev/null || true
        sudo rm -f "$SUPERVISOR_CONF_DIR/laravel-webhook-*.conf"
        sudo supervisorctl reread
        sudo supervisorctl update
        print_status "Uninstallation completed"
        ;;
    "help"|"--help"|"-h"|"")
        echo -e "${BLUE}Laravel Webhook Service - Supervisor Setup Script${NC}"
        echo
        echo -e "${YELLOW}Usage:${NC}"
        echo -e "  $0 install     - Install and configure supervisor"
        echo -e "  $0 status      - Show service status"
        echo -e "  $0 start       - Start all services"
        echo -e "  $0 stop        - Stop all services"
        echo -e "  $0 restart     - Restart all services"
        echo -e "  $0 logs        - Show recent logs"
        echo -e "  $0 uninstall   - Remove supervisor configuration"
        echo -e "  $0 help        - Show this help message"
        echo
        echo -e "${BLUE}Services managed:${NC}"
        echo -e "  - Laravel Horizon (queue processing)"
        echo -e "  - Laravel Scheduler (cron jobs)"
        echo -e "  - Queue Workers (fallback, disabled by default)"
        echo
        ;;
    *)
        print_error "Unknown command: $1"
        echo "Run '$0 help' for usage information."
        exit 1
        ;;
esac