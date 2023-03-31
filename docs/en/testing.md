# Testing

This package can be tested globally or individually for each DBMS.

1. [MSSQL](https://github.com/yiisoft/db-mssql)
2. [MySQL/MariaDB](https://github.com/yiisoft/db-mysql)
3. [Oracle](https://github.com/yiisoft/db-oracle)
4. [PostgreSQL](https://github.com/yiisoft/db-pgsql)
5. [SQLite](https://github.com/yiisoft/db-sqlite)

For greater ease it is recommended to use docker containers for each DBMS, for this you can use the [docker-compose.yml](https://docs.docker.com/compose/compose-file/) file that is in the `/tests/Support/docker` folder.

## Docker images

To test all dbms at once, you can use the [docker-compose.yml](https://docs.docker.com/compose/compose-file/) file that is in the `/tests/Support/docker` folder.

1. [MSSQL 2022](/tests/Support/docker/mssql/docker-compose.yml)
2. [MySQL 8](/tests/Support/docker/mysql/docker-compose.yml)
3. [MariaDB 10.11](/tests/Support/docker/mariadb/docker-compose.yml)
4. [Oracle 21](/tests/Support/docker/oracle/docker-compose.yml)
5. [PostgreSQL 15](/tests/Support/docker/pgsql/docker-compose.yml)

For running the docker containers you can use the following command:

```shell
docker compose up -d
```

## Unit testing

The package is tested with [PHPUnit](https://phpunit.de/).

### Global testing

To test all dbms at once, you can use the [docker-compose.yml](https://docs.docker.com/compose/compose-file/) file that is in the `/tests/Support/docker` folder.

The following steps are required to run the tests:

1. Run all docker containers for each dbms.
2. Install the dependencies of the project with composer.
3. Run the tests.

```shell
vendor/bin/phpunit
```

### Individual testing

To test each dbms individually, you can use the [docker-compose.yml](https://docs.docker.com/compose/compose-file/) file that is in the `/tests/Support/docker` folder.

The following steps are required to run the tests:

1. Run the docker container for the dbms you want to test.
2. Install the dependencies of the project with composer.
3. Run the tests.

```shell
vendor/bin/phpunit --testsuite=Pgsql
```

Suites available:
- Mssql
- Mysql
- Oracle
- Pgsql
- Sqlite

## Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

## Rector

Use [Rector](https://github.com/rectorphp/rector) to make codebase follow some specific rules or use either newest or any specific version of PHP: 

```shell
./vendor/bin/rector
```

## Composer require checker

This package uses [composer-require-checker](https://github.com/maglnet/ComposerRequireChecker) to check if all dependencies are correctly defined in `composer.json`.

To run the checker, execute the following command:

```shell
./vendor/bin/composer-require-checker
```
