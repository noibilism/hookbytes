.PHONY: help build up down restart logs shell test migrate seed fresh install

# Default target
help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

# Docker operations
build: ## Build Docker containers
	docker-compose build --no-cache

up: ## Start all services
	docker-compose up -d
	@echo "Services started. Application available at http://localhost:8000"
	@echo "Mailpit available at http://localhost:8025"

down: ## Stop all services
	docker-compose down

restart: ## Restart all services
	docker-compose restart

logs: ## Show logs for all services
	docker-compose logs -f

logs-app: ## Show logs for app service
	docker-compose logs -f app

logs-horizon: ## Show logs for horizon service
	docker-compose logs -f horizon

# Development operations
shell: ## Access application shell
	docker-compose exec app sh

shell-mysql: ## Access MySQL shell
	docker-compose exec mysql mysql -u webhook_user -p webhook_service

shell-redis: ## Access Redis shell
	docker-compose exec redis redis-cli

# Laravel operations
install: ## Install application dependencies and setup
	docker-compose exec app composer install
	docker-compose exec app php artisan key:generate
	docker-compose exec app php artisan storage:link
	@echo "Application installed successfully"

migrate: ## Run database migrations
	docker-compose exec app php artisan migrate

seed: ## Run database seeders
	docker-compose exec app php artisan db:seed

fresh: ## Fresh migration with seeding
	docker-compose exec app php artisan migrate:fresh --seed

optimize: ## Optimize application
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache

clear: ## Clear application caches
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear
	docker-compose exec app php artisan cache:clear

# Testing
test: ## Run tests
	docker-compose exec app php artisan test

test-coverage: ## Run tests with coverage
	docker-compose exec app php artisan test --coverage

# Queue operations
queue-work: ## Start queue worker
	docker-compose exec app php artisan queue:work

queue-restart: ## Restart queue workers
	docker-compose exec app php artisan queue:restart

queue-failed: ## Show failed jobs
	docker-compose exec app php artisan queue:failed

queue-retry: ## Retry failed jobs
	docker-compose exec app php artisan queue:retry all

# Horizon operations
horizon: ## Start Horizon dashboard
	docker-compose exec app php artisan horizon

horizon-terminate: ## Terminate Horizon
	docker-compose exec app php artisan horizon:terminate

# Development setup
setup: build up install migrate seed ## Complete development setup
	@echo "Development environment setup complete!"
	@echo "Application: http://localhost:8000"
	@echo "Mailpit: http://localhost:8025"

# Production operations
deploy: ## Deploy to production
	docker-compose -f docker-compose.prod.yml up -d --build

backup-db: ## Backup database
	docker-compose exec mysql mysqldump -u webhook_user -p webhook_service > backup_$(shell date +%Y%m%d_%H%M%S).sql

restore-db: ## Restore database (usage: make restore-db FILE=backup.sql)
	docker-compose exec -T mysql mysql -u webhook_user -p webhook_service < $(FILE)

# Cleanup
clean: ## Clean up Docker resources
	docker-compose down -v
	docker system prune -f
	docker volume prune -f

clean-all: ## Clean up everything including images
	docker-compose down -v --rmi all
	docker system prune -af
	docker volume prune -f

# Monitoring
stats: ## Show container stats
	docker stats

ps: ## Show running containers
	docker-compose ps

top: ## Show container processes
	docker-compose top