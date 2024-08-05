.DEFAULT_GOAL := help

USER = $(shell id -u):$(shell id -g)

DOCKER_COMPOSE = docker compose -p pollaris -f docker/docker-compose.yml

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

.PHONY: help
help:
	@grep -h -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
