# Makefile for Docker operations

.PHONY: help build up down restart logs shell migrate seed fresh install clean

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@awk 'BEGIN {FS = ":.*##"; printf "\033[36m\033[0m"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

##@ Docker Commands

build: ## Build Docker containers
	docker-compose build

up: ## Start Docker containers
	docker-compose up -d

down: ## Stop Docker containers
	docker-compose down

restart: ## Restart Docker containers
	docker-compose restart

logs: ## Show logs from all containers
	docker-compose logs -f

logs-app: ## Show logs from app container
	docker-compose logs -f app

##@ Application Commands

shell: ## Access app container shell
	docker-compose exec app bash

migrate: ## Run database migrations
	docker-compose exec app php artisan migrate

seed: ## Run database seeders
	docker-compose exec app php artisan db:seed

fresh: ## Fresh migrate with seeders
	docker-compose exec app php artisan migrate:fresh --seed

install: ## Install composer dependencies
	docker-compose exec app composer install

key: ## Generate application key
	docker-compose exec app php artisan key:generate

setup: ## Run initial setup (create credentials)
	docker-compose exec app php artisan initial:setup

cache: ## Clear and cache config
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache

clear: ## Clear all caches
	docker-compose exec app php artisan cache:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear

queue: ## Run queue worker
	docker-compose exec app php artisan queue:work

tinker: ## Run Laravel tinker
	docker-compose exec app php artisan tinker

##@ Database Commands

db-shell: ## Access PostgreSQL shell
	docker-compose exec db psql -U ezship -d ezship

db-backup: ## Backup database
	docker-compose exec db pg_dump -U ezship ezship > backup_$$(date +%Y%m%d_%H%M%S).sql

db-restore: ## Restore database from backup (usage: make db-restore FILE=backup.sql)
	docker-compose exec -T db psql -U ezship ezship < $(FILE)

##@ Deployment Commands

deploy: ## Full deployment process
	@echo "Starting deployment..."
	make build
	make up
	sleep 5
	make migrate
	make cache
	@echo "Deployment complete!"

clean: ## Clean up Docker resources
	docker-compose down -v
	docker system prune -f

##@ Development Commands

dev-setup: ## Setup development environment
	cp .env.docker .env
	make build
	make up
	sleep 5
	make key
	make migrate
	make setup
	make seed
	make cache
	@echo "Development environment ready!"

test: ## Run tests
	docker-compose exec app php artisan test

phpunit: ## Run PHPUnit tests
	docker-compose exec app ./vendor/bin/phpunit