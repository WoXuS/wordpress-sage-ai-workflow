DC    = docker compose
PHP   = $(DC) exec php
THEME = wp-content/themes/arpi

.DEFAULT_GOAL := help

help: ## Ten ekran pomocy
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN{FS=":.*?## "}{printf "\033[32m%-16s\033[0m %s\n",$$1,$$2}'

up: ## Start kontenerów
	$(DC) up -d

down: ## Stop kontenerów
	$(DC) down --remove-orphans

build: ## Zbuduj obraz php
	$(DC) build

rebuild: ## Przebuduj i wystartuj
	$(DC) down --remove-orphans
	$(DC) build
	$(DC) up -d

logs: ## Podgląd logów
	$(DC) logs -f

shell: ## Wejście do kontenera php
	$(PHP) bash

wp: ## WP-CLI, np. make wp ARGS="plugin list"
	$(PHP) wp --allow-root $(ARGS)

composer: ## Composer w motywie, np. make composer ARGS="require x"
	$(PHP) sh -c "cd $(THEME) && composer $(ARGS)"

theme-install: ## Zależności motywu (composer w kontenerze + yarn na hoście)
	$(PHP) sh -c "cd $(THEME) && composer install"
	cd $(THEME) && yarn install

dev: ## Vite dev server + HMR (host)
	cd $(THEME) && yarn dev

build-assets: ## Build assetów motywu (host)
	cd $(THEME) && yarn build

import-db: ## Import zrzutu: make import-db FILE=dump.sql.gz
	gzip -cd $(FILE) | $(DC) exec -T db sh -c 'exec mariadb -u root -p"$$MARIADB_ROOT_PASSWORD" "$$MARIADB_DATABASE"'

dump-db: ## Zrzut bazy: make dump-db FILE=dump.sql.gz
	$(DC) exec -T db sh -c 'exec mariadb-dump -u root -p"$$MARIADB_ROOT_PASSWORD" "$$MARIADB_DATABASE"' | gzip > $(FILE)
