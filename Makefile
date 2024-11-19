run:
	docker compose run --rm --entrypoint $(CMD) php

test-all: test-sqlite \
	test-mysql \
	test-mariadb \
	test-pgsql \
	test-mssql \
	test-oracle
test-sqlite: testsuite-Sqlite
test-mysql: testsuite-Mysql
test-mariadb:
	docker compose run \
	--rm \
	--entrypoint "vendor/bin/phpunit --testsuite Mysql" \
	-e YII_MYSQL_TYPE=mariadb \
	php
test-pgsql: testsuite-Pgsql
test-mssql: testsuite-Mssql
test-oracle:
	docker compose run \
	--rm \
	--entrypoint 'bash -c -l "vendor/bin/phpunit --testsuite Oracle"' \
	php

testsuite-%:
	docker compose run \
	--rm \
	--entrypoint "vendor/bin/phpunit --testsuite $(subst testsuite-,,$@)" \
	php

static-analysis: CMD="vendor/bin/psalm --no-cache"
static-analysis: run

mutation: CMD="\
vendor/bin/roave-infection-static-analysis-plugin \
--threads=2 \
--min-msi=0 \
--min-covered-msi=100 \
--ignore-msi-with-no-mutations \
--only-covered"
mutation: run

composer-require-checker: CMD="vendor/bin/composer-require-checker"
composer-require-checker: run

shell: CMD="bash"
shell: run
