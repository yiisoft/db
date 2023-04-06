# Testing

This package can be tested globally or individually for each DBMS.

- [MSSQL](https://github.com/yiisoft/db-mssql)
- [MySQL/MariaDB](https://github.com/yiisoft/db-mysql)
- [Oracle](https://github.com/yiisoft/db-oracle)
- [PostgreSQL](https://github.com/yiisoft/db-pgsql)
- [SQLite](https://github.com/yiisoft/db-sqlite)

## Github actions

All our packages have github actions by default, so you can test your [contribution](https://github.com/yiisoft/db/blob/master/.github/CONTRIBUTING.md) in the cloud.

> Note: We recommend pull requesting in draft mode until all tests pass.

## Docker images

For greater ease it is recommended to use Docker containers for each DBMS, for this you can use the [docker-compose.yml](https://docs.docker.com/compose/compose-file/) file that in the root directory of each package.

- [MSSQL 2022](https://github.com/yiisoft/db-mssql/blob/master/docker-compose.yml)
- [MySQL 8](https://github.com/yiisoft/db-mysql/blob/master/docker-compose.yml)
- [MariaDB 10.11](https://github.com/yiisoft/db-mysql/blob/master/docker-compose-mariadb.yml)
- [Oracle 21](https://github.com/yiisoft/db-oracle/blob/master/docker-compose.yml)
- [PostgreSQL 15](https://github.com/yiisoft/db-pgsql/blob/master/docker-compose.yml)

For running the Docker containers you can use the following command:

```shell
docker compose up -d
```

## Unit testing

The package is tested with [PHPUnit](https://phpunit.de/).

### Global testing

The following steps are required to run the tests.

1. Run all Docker containers for each dbms.
2. Install the dependencies of the project with composer.
3. Run the tests.

```shell
vendor/bin/phpunit
```

### Individual testing

The following steps are required to run the tests.

1. Run the Docker container for the dbms you want to test.
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
