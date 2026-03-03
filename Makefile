PHP_83   = docker run --rm -v $$(pwd):/app -w /app php:8.3-cli
COMPOSER = docker run --rm -v $$(pwd):/app -w /app composer:latest

## Run tests on Symfony 7.4 (PHP 8.3) — 2 tests should FAIL
.PHONY: test-7.4
test-7.4: clean
	$(COMPOSER) update --ignore-platform-reqs
	$(PHP_83) vendor/bin/phpunit

## Run tests on Symfony 7.3 (PHP 8.3) — all tests should PASS
.PHONY: test-7.3
test-7.3: clean
	$(COMPOSER) update --ignore-platform-reqs \
		--with symfony/serializer:7.3.* \
		--with symfony/property-info:7.3.* \
		--with symfony/property-access:7.3.*
	$(PHP_83) vendor/bin/phpunit

.PHONY: clean
clean:
	docker run --rm -v $$(pwd):/app -w /app php:8.3-cli rm -rf vendor composer.lock
