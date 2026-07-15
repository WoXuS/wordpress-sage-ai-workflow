DC         = docker compose
PHP        = $(DC) exec php
THEME      = wp-content/themes/arpi
THEME_HOST = public_html/$(THEME)

.DEFAULT_GOAL := help

help: ## Ten ekran pomocy
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN{FS=":.*?## "}{printf "\033[32m%-16s\033[0m %s\n",$$1,$$2}'

setup: ## Bootstrap na nowej maszynie (.env + sole, kontenery, rdzeń WP, motyw, instalacja)
	@test -f .env || { \
		cp .env.example .env; \
		echo "-> .env utworzony z .env.example, generuję sole..."; \
		for k in AUTH_KEY SECURE_AUTH_KEY LOGGED_IN_KEY NONCE_KEY AUTH_SALT SECURE_AUTH_SALT LOGGED_IN_SALT NONCE_SALT; do \
			salt=$$(LC_ALL=C tr -dc 'A-Za-z0-9_@%+=' < /dev/urandom | head -c 64); \
			tmp=$$(mktemp) && sed "s|^$$k=.*|$$k=$$salt|" .env > $$tmp && mv $$tmp .env; \
		done; \
	}
	$(DC) up -d
	$(PHP) sh -c "cd /var/app && composer install --no-interaction"
	@test -f public_html/wp-load.php || $(PHP) wp core download --allow-root
	$(MAKE) theme-install
	@$(PHP) wp core is-installed --allow-root 2>/dev/null || $(PHP) wp core install --allow-root \
		--url="http://localhost:8080" --title="ARPI Accounting" \
		--admin_user=admin --admin_password=admin --admin_email=admin@example.com --skip-email
	@$(PHP) wp theme activate arpi --allow-root 2>/dev/null || true
	@echo "✓ Gotowe: http://localhost:8080  (admin/admin) — teraz: make dev"

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
	cd $(THEME_HOST) && yarn install

dev: ## Vite dev server + HMR (host)
	cd $(THEME_HOST) && yarn dev

build-assets: ## Build assetów motywu (host)
	cd $(THEME_HOST) && yarn build

import-db: ## Import zrzutu: make import-db FILE=dump.sql.gz
	gzip -cd $(FILE) | $(DC) exec -T db sh -c 'exec mariadb -u root -p"$$MARIADB_ROOT_PASSWORD" "$$MARIADB_DATABASE"'

dump-db: ## Zrzut bazy: make dump-db FILE=dump.sql.gz
	$(DC) exec -T db sh -c 'exec mariadb-dump -u root -p"$$MARIADB_ROOT_PASSWORD" "$$MARIADB_DATABASE"' | gzip > $(FILE)

push-db-staging: ## Wypchnij bazę dev → staging (nadpisuje staging DB, search-replace URL)
	./scripts/deploy/push-db-to-staging.sh

sync-plugins-staging: ## Zsynchronizuj wtyczki dev → staging (lustro plików, --delete)
	./scripts/deploy/sync-plugins-to-staging.sh
