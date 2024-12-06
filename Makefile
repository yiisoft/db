run: ## Run arbitrary command.
	docker compose -f docker/docker-compose.yml -f docker/docker-compose.override.yml run \
	--rm \
	--entrypoint $(CMD) \
	php

test-all: test-base \ ## Run all available tests.
	test-driver-all
test-driver-all: test-driver-sqlite \ ## Run tests for all drivers.
	test-driver-mysql \
	test-driver-mariadb \
	test-driver-pgsql \
	test-driver-mssql \
	test-driver-oracle
test-base: testsuite-Db ## Run tests for base db package only.
test-driver-sqlite: testsuite-Sqlite ## Run tests for SQLite driver only.
test-driver-mysql: testsuite-Mysql ## Run tests for MySQL driver only (using MySQL database).
test-driver-mariadb: ## Run tests for MySQL driver only (using MariaDB database).
	docker compose -f docker/docker-compose.yml -f docker/docker-compose.override.yml run \
	--rm \
	--entrypoint "vendor/bin/phpunit --testsuite Mysql $(RUN_ARGS)" \
	-e YII_MYSQL_TYPE=mariadb \
	php
test-driver-pgsql: testsuite-Pgsql ## Run tests for PostgreSQL driver only.
test-driver-mssql: testsuite-Mssql ## Run tests for Microsoft SQL Server driver only.
test-driver-oracle: ## Run tsets for Oracle driver only.
	docker compose -f docker/docker-compose.yml -f docker/docker-compose.override.yml run \
	--rm \
	--entrypoint "bash -c -l 'vendor/bin/phpunit --testsuite Oracle $(RUN_ARGS)'" \
	php

testsuite-%:
	docker compose -f docker/docker-compose.yml -f docker/docker-compose.override.yml run \
	--rm \
	--entrypoint "vendor/bin/phpunit --testsuite $(subst testsuite-,,$@) $(RUN_ARGS)" \
	php

mutation: CMD="\
vendor/bin/roave-infection-static-analysis-plugin \
--threads=2 \
--min-msi=0 \
--min-covered-msi=100 \
--ignore-msi-with-no-mutations \
--only-covered" ## Run mutation tests using Infection.
mutation: run

static-analysis: CMD="vendor/bin/psalm --no-cache" ## Run static analysis using Psalm.
static-analysis: run

rector: CMD="vendor/bin/rector" ## Check code style using Rector.
rector: run

composer-require-checker: CMD="vendor/bin/composer-require-checker" ## Check dependencies using Composer Require Checker.
composer-require-checker: run

shell: CMD="bash" ## Open interactive shell.
shell: run

.PHONY: help
help: ## This help.
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
.DEFAULT_GOAL := help
