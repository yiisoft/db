run:
	docker compose run \
	--rm \
	--entrypoint $(CMD) \
	php

test-all: test-base \
	test-driver-all
test-driver-all: test-driver-sqlite \
	test-driver-mysql \
	test-driver-mariadb \
	test-driver-pgsql \
	test-driver-mssql \
	test-driver-oracle
test-base: testsuite-Db
test-driver-sqlite: testsuite-Sqlite
test-driver-mysql: testsuite-Mysql
test-driver-mariadb:
	docker compose run \
	--rm \
	--entrypoint "vendor/bin/phpunit --testsuite Mysql $(RUN_ARGS)" \
	-e YII_MYSQL_TYPE=mariadb \
	php
test-driver-pgsql: testsuite-Pgsql
test-driver-mssql: testsuite-Mssql
test-driver-oracle:
	docker compose run \
	--rm \
	--entrypoint "bash -c -l 'vendor/bin/phpunit --testsuite Oracle $(RUN_ARGS)'" \
	php

testsuite-%:
	docker compose run \
	--rm \
	--entrypoint "vendor/bin/phpunit --testsuite $(subst testsuite-,,$@) $(RUN_ARGS)" \
	php

mutation: CMD="\
vendor/bin/roave-infection-static-analysis-plugin \
--threads=2 \
--min-msi=0 \
--min-covered-msi=100 \
--ignore-msi-with-no-mutations \
--only-covered"
mutation: run

static-analysis: CMD="vendor/bin/psalm --no-cache"
static-analysis: run

rector: CMD="vendor/bin/rector"
rector: run

composer-require-checker: CMD="vendor/bin/composer-require-checker"
composer-require-checker: run

shell: CMD="bash"
shell: run
