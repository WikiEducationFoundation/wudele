# This file is part of Pollaris.
# Copyright 2024 Marien Fressinaud
# SPDX-License-Identifier: AGPL-3.0-or-later

.DEFAULT_GOAL := help

USER = $(shell id -u):$(shell id -g)

DOCKER_COMPOSE = docker compose -f docker/development/docker-compose.yml

ifdef NODOCKER
	PHP = php
	COMPOSER = composer
	CONSOLE = php bin/console
	NPM = npm
else
	PHP = ./docker/bin/php
	COMPOSER = ./docker/bin/composer
	CONSOLE = ./docker/bin/php bin/console
	NPM = ./docker/bin/npm
endif

ifndef COVERAGE
	COVERAGE = --coverage-html ./coverage
endif

ifdef FILE
	PHPUNIT_FILE = $(FILE)
else
	PHPUNIT_FILE = ./tests
endif

ifdef FILTER
	PHPUNIT_FILTER = --filter=$(FILTER)
else
	PHPUNIT_FILTER =
endif

.PHONY: docker-start
docker-start: ## Start a development server
	@echo "Running webserver on http://localhost:8000"
	$(DOCKER_COMPOSE) up

.PHONY: docker-build
docker-build: ## Rebuild the Docker containers
	$(DOCKER_COMPOSE) build

.PHONY: docker-clean
docker-clean: ## Clean the Docker stuff
	$(DOCKER_COMPOSE) down

.PHONY: install
install: ## Install the dependencies
	$(COMPOSER) install
	$(NPM) install

.PHONY: db-setup
db-setup: ## Setup the database
	$(CONSOLE) doctrine:database:create
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

.PHONY: db-migrate
db-migrate: ## Migrate the database
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

.PHONY: db-rollback
db-rollback: ## Rollback the database to the previous version
	$(CONSOLE) doctrine:migrations:migrate --no-interaction prev

.PHONY: db-reset
db-reset: ## Reset the database
ifndef FORCE
	$(error Please run the operation with FORCE=true)
endif
	$(CONSOLE) doctrine:database:drop --force --if-exists
	$(CONSOLE) doctrine:database:create
	$(CONSOLE) doctrine:migrations:migrate --no-interaction
	$(CONSOLE) cache:clear

.PHONY: migration
migration: ## Generate a database migration from entities changes
	$(CONSOLE) make:migration

.PHONY: translations
translations: ## Update the translations from the code
	$(CONSOLE) translation:extract --format=yaml --force --clean en_GB
	$(CONSOLE) translation:extract --format=yaml --force --clean fr_FR

.PHONY: icons
icons: ## Build the icons asset
	$(NPM) run build:icons

.PHONY: test
test: ## Run the test suite
	$(PHP) ./vendor/bin/phpunit \
		-c .phpunit.xml.dist \
		$(COVERAGE) \
		$(PHPUNIT_FILTER) \
		$(PHPUNIT_FILE)

.PHONY: lint
lint: ## Execute the linters
	$(PHP) vendor/bin/phpstan analyse -c .phpstan.neon
	$(PHP) vendor/bin/rector process --dry-run --config .rector.php
	$(PHP) vendor/bin/phpcs
	$(CONSOLE) lint:container

.PHONY: lint-fix
lint-fix: ## Fix the errors detected by the linters
	$(PHP) vendor/bin/rector process --config .rector.php
	$(PHP) vendor/bin/phpcbf

.PHONY: release
release: ## Release a new version (take a VERSION argument)
ifndef VERSION
	$(error You need to provide a "VERSION" argument)
endif
	echo $(VERSION) > VERSION.txt
	$(NPM) run build
	$(EDITOR) CHANGELOG.md
	git add .
	git commit -m "release: Publish version $(VERSION)"
	git tag -a $(VERSION) -m "Release version $(VERSION)"

.PHONY: help
help:
	@grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
