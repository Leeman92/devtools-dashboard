.PHONY: *

# base docker-compose command
DC := docker compose

build: build-backend

build-backend:
	$(DC) build backend --no-cache

up:
	$(DC) up -d

down:
	$(DC) down

logs:
	$(DC) logs -f

login-backend lb:
	$(DC) exec -it --user backend backend bash

# Test commands
test:
	$(DC) exec backend php bin/phpunit

test-verbose:
	$(DC) exec backend php bin/phpunit --verbose

test-coverage:
	$(DC) exec backend php bin/phpunit --coverage-html var/coverage

test-file:
	$(DC) exec backend php bin/phpunit $(TEST_FILE)

test-filter:
	$(DC) exec backend php bin/phpunit --filter=$(FILTER)

# Helper to show available test commands
test-help:
	@echo "Available test commands:"
	@echo "  make test              - Run all tests"
	@echo "  make test-verbose      - Run tests with verbose output"
	@echo "  make test-coverage     - Run tests with coverage report (HTML output in var/coverage)"
	@echo "  make test-file         - Run specific test file (set TEST_FILE=path/to/test.php)"
	@echo "  make test-filter       - Run tests matching filter (set FILTER=testName)"
	@echo ""
	@echo "Examples:"
	@echo "  make test-file TEST_FILE=tests/Unit/Services/DockerServiceTest.php"
	@echo "  make test-filter FILTER=testMethodName"


install-backend:
	$(DC) exec backend composer install

install: install-backend