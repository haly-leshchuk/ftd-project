up: ## Start all docker containers
	@docker compose up --build -d

down: ## Stop all docker containers
	@docker compose down

rebuild: ## Rebuild all docker containers
	make down
	make up

dbup: ## create db called db
	@docker compose exec -it db psql -U postgres -d postgres -c "CREATE DATABASE db;"

migrate: ## Run all migrations
	@docker exec -it ftd-project-app-1 vendor/bin/phinx migrate -e development