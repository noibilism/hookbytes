#!/bin/bash

# HookBytes Docker Development Helper Script
# Provides common development commands for Docker environment

set -e

COMMAND=${1:-help}

case $COMMAND in
    "start")
        echo "🚀 Starting development environment..."
        docker-compose up -d
        echo "✅ Development environment started"
        echo "📱 Application: http://localhost:8000"
        ;;
    
    "stop")
        echo "🛑 Stopping development environment..."
        docker-compose down
        echo "✅ Development environment stopped"
        ;;
    
    "restart")
        echo "🔄 Restarting development environment..."
        docker-compose down
        docker-compose up -d
        echo "✅ Development environment restarted"
        ;;
    
    "logs")
        SERVICE=${2:-}
        if [ -z "$SERVICE" ]; then
            echo "📋 Showing all logs..."
            docker-compose logs -f
        else
            echo "📋 Showing logs for $SERVICE..."
            docker-compose logs -f $SERVICE
        fi
        ;;
    
    "shell")
        SERVICE=${2:-app}
        echo "🐚 Opening shell in $SERVICE container..."
        docker-compose exec $SERVICE bash
        ;;
    
    "artisan")
        shift
        echo "🎨 Running artisan command: $@"
        docker-compose exec app php artisan "$@"
        ;;
    
    "composer")
        shift
        echo "📦 Running composer command: $@"
        docker-compose exec app composer "$@"
        ;;
    
    "npm")
        shift
        echo "📦 Running npm command: $@"
        docker-compose exec app npm "$@"
        ;;
    
    "test")
        echo "🧪 Running PHPUnit tests..."
        docker-compose exec app php artisan test
        ;;
    
    "test-scenarios")
        echo "🧪 Running test scenarios..."
        docker-compose exec app ./run-test-scenarios.sh
        ;;
    
    "migrate")
        echo "📊 Running database migrations..."
        docker-compose exec app php artisan migrate
        ;;
    
    "migrate-fresh")
        echo "📊 Fresh database migration with seeding..."
        docker-compose exec app php artisan migrate:fresh --seed
        ;;
    
    "seed")
        echo "🌱 Seeding database..."
        docker-compose exec app php artisan db:seed
        ;;
    
    "tinker")
        echo "🔧 Opening Laravel Tinker..."
        docker-compose exec app php artisan tinker
        ;;
    
    "queue")
        echo "⚡ Starting queue worker..."
        docker-compose exec app php artisan queue:work --verbose
        ;;
    
    "cache-clear")
        echo "🧹 Clearing all caches..."
        docker-compose exec app php artisan cache:clear
        docker-compose exec app php artisan config:clear
        docker-compose exec app php artisan route:clear
        docker-compose exec app php artisan view:clear
        echo "✅ All caches cleared"
        ;;
    
    "optimize")
        echo "⚡ Optimizing application..."
        docker-compose exec app php artisan config:cache
        docker-compose exec app php artisan route:cache
        docker-compose exec app php artisan view:cache
        echo "✅ Application optimized"
        ;;
    
    "build")
        echo "🏗️  Building containers..."
        docker-compose build --no-cache
        echo "✅ Containers built"
        ;;
    
    "rebuild")
        echo "🏗️  Rebuilding and restarting..."
        docker-compose down
        docker-compose build --no-cache
        docker-compose up -d
        echo "✅ Environment rebuilt and started"
        ;;
    
    "status")
        echo "📊 Container status:"
        docker-compose ps
        ;;
    
    "clean")
        echo "🧹 Cleaning up Docker resources..."
        docker-compose down -v
        docker system prune -f
        echo "✅ Docker resources cleaned"
        ;;
    
    "help")
        echo "🐳 HookBytes Docker Development Helper"
        echo "====================================="
        echo ""
        echo "Available commands:"
        echo "  start              Start development environment"
        echo "  stop               Stop development environment"
        echo "  restart            Restart development environment"
        echo "  logs [service]     Show logs (all or specific service)"
        echo "  shell [service]    Open shell in container (default: app)"
        echo "  artisan <cmd>      Run artisan command"
        echo "  composer <cmd>     Run composer command"
        echo "  npm <cmd>          Run npm command"
        echo "  test               Run PHPUnit tests"
        echo "  test-scenarios     Run test scenarios"
        echo "  migrate            Run database migrations"
        echo "  migrate-fresh      Fresh migration with seeding"
        echo "  seed               Seed database"
        echo "  tinker             Open Laravel Tinker"
        echo "  queue              Start queue worker"
        echo "  cache-clear        Clear all caches"
        echo "  optimize           Optimize application"
        echo "  build              Build containers"
        echo "  rebuild            Rebuild and restart"
        echo "  status             Show container status"
        echo "  clean              Clean up Docker resources"
        echo "  help               Show this help"
        echo ""
        echo "Examples:"
        echo "  ./docker-dev.sh start"
        echo "  ./docker-dev.sh artisan make:controller TestController"
        echo "  ./docker-dev.sh logs app"
        echo "  ./docker-dev.sh shell"
        ;;
    
    *)
        echo "❌ Unknown command: $COMMAND"
        echo "Run './docker-dev.sh help' for available commands"
        exit 1
        ;;
esac