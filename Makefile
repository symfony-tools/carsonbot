.PHONY: build coverage help test

help:
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

build: test ## Runs test targets

test: vendor/autoload.php ## Runs tests with phpunit
	vendor/bin/phpunit --testsuite Unit

vendor/autoload.php:
	composer install --no-interaction
