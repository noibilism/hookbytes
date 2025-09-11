#!/bin/bash

# HookBytes Docker Setup Script
# This script helps you set up the HookBytes application with Docker

set -e

echo "🐳 HookBytes Docker Setup"
echo "========================"

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

echo "✅ Docker and Docker Compose are installed"

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file from template..."
    cp .env.docker .env
    echo "✅ .env file created. Please review and modify it as needed."
else
    echo "✅ .env file already exists"
fi

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 Generating application key..."
    # We'll generate this after the container is built
    echo "⏳ Application key will be generated after container setup"
fi

# Build and start containers
echo "🏗️  Building Docker containers..."
docker-compose build

echo "🚀 Starting containers..."
docker-compose up -d

# Wait for database to be ready
echo "⏳ Waiting for database to be ready..."
sleep 10

# Generate application key
echo "🔑 Generating application key..."
docker-compose exec app php artisan key:generate

# Run database migrations
echo "📊 Running database migrations..."
docker-compose exec app php artisan migrate --force

# Seed database (optional)
read -p "🌱 Do you want to seed the database with sample data? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "🌱 Seeding database..."
    docker-compose exec app php artisan db:seed
fi

# Create storage link
echo "🔗 Creating storage link..."
docker-compose exec app php artisan storage:link

# Clear and cache config
echo "⚡ Optimizing application..."
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache

# Set permissions
echo "🔐 Setting permissions..."
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chown -R www-data:www-data /var/www/html/bootstrap/cache

echo ""
echo "🎉 Setup complete!"
echo "=================="
echo "📱 Application: http://localhost:8000"
echo "📊 Database: localhost:3306 (hookbytes/password)"
echo "🔴 Redis: localhost:6379"
echo ""
echo "📋 Useful commands:"
echo "  docker-compose logs -f          # View logs"
echo "  docker-compose exec app bash    # Access app container"
echo "  docker-compose down             # Stop containers"
echo "  docker-compose up -d            # Start containers"
echo "  docker-compose exec app php artisan tinker  # Laravel tinker"
echo ""
echo "🧪 Run tests:"
echo "  docker-compose exec app php artisan test"
echo "  docker-compose exec app ./run-test-scenarios.sh"
echo ""
echo "Happy coding! 🚀"