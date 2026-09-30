#!/usr/bin/make -f
SHELL := /bin/bash

DOCKER_COMPOSE := docker compose
# User inside the PHP container. www-data matches the image's PHP-FPM user; CI sets root because
# the checkout on a GitHub runner belongs to another uid and a bind mount does not translate that.
DOCKER_USER ?= www-data

define run_in_workspace
	$(DOCKER_COMPOSE) exec -T --user $(DOCKER_USER) php /bin/bash -c "cd /php && $(1)"
endef

.PHONY: help
help: ## Show help
	@grep -hE '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "%-22s %s\n", $$1, $$2}'

## ── Rig ──────────────────────────────────────────────────────────────
.PHONY: setup
setup: start composer-install pimcore-install ## Start the rig, install dependencies and Pimcore

.PHONY: start
start: ## Start the containers (PHP + MariaDB)
	$(DOCKER_COMPOSE) up -d --wait

.PHONY: stop
stop: ## Stop the containers
	$(DOCKER_COMPOSE) stop

.PHONY: destroy
destroy: ## Remove containers and volumes
	$(DOCKER_COMPOSE) down -v --remove-orphans

.PHONY: shell
shell: ## Open a shell in the PHP container
	$(DOCKER_COMPOSE) exec --user $(DOCKER_USER) php /bin/bash

.PHONY: composer-install
composer-install: ## Install PHP dependencies (Pimcore included)
	$(call run_in_workspace,composer install --no-interaction)

.PHONY: composer-update
composer-update: ## Update PHP dependencies
	$(call run_in_workspace,composer update --no-interaction)

.PHONY: pimcore-install
pimcore-install: ## Install Pimcore into tests/App
	$(call run_in_workspace,php -d memory_limit=-1 docker/install.php)

## ── Tests ────────────────────────────────────────────────────────────
.PHONY: test
test: test-unit test-functional ## Run all tests

.PHONY: test-unit
test-unit: ## Run the unit tests
	$(call run_in_workspace,vendor/bin/phpunit --testsuite Unit)

.PHONY: test-functional
test-functional: ## Run the functional tests against the installed Pimcore
	$(call run_in_workspace,vendor/bin/phpunit --testsuite Functional)

## ── Lint ─────────────────────────────────────────────────────────────
.PHONY: lint
lint: lint-php lint-php-static ## Run all linters

.PHONY: lint-php
lint-php: ## PHP-CS-Fixer dry run
	$(call run_in_workspace,vendor/bin/php-cs-fixer fix --dry-run --diff)

.PHONY: lint-php-fix
lint-php-fix: ## PHP-CS-Fixer with fixes
	$(call run_in_workspace,vendor/bin/php-cs-fixer fix)

.PHONY: lint-php-static
lint-php-static: ## PHPStan
	$(call run_in_workspace,vendor/bin/phpstan analyse --memory-limit=-1)
