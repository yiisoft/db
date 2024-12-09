# Internals

## Github actions

All our packages have github actions by default, so you can test your [contribution](https://github.com/yiisoft/db/blob/master/.github/CONTRIBUTING.md) in the cloud.

> Note: We recommend pull requesting in draft mode until all tests pass.

## Local development

Docker is used to ease the local development.

### Setup

Prerequisites:

- [Docker](https://docs.docker.com/get-started/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/install/)

Clone all repos of drivers' packages:

- [SQLite](https://github.com/yiisoft/db-sqlite)
- [MySQL](https://github.com/yiisoft/db-mysql)
- [PostgreSQL](https://github.com/yiisoft/db-pgsql)
- [Microsoft SQL Server](https://github.com/yiisoft/db-mssql)
- [Oracle](https://github.com/yiisoft/oracle)

Create `docker/docker-compose.override.yml` file with this content:

```yaml
services:
  php:
    volumes:
      - /path/to/packages/db-sqlite:/code/vendor/yiisoft/db-sqlite
      - /path/to/packages/db-mysql:/code/vendor/yiisoft/db-mysql
      - /path/to/packages/db-pgsql:/code/vendor/yiisoft/db-pgsql
      - /path/to/packages/db-mssql:/code/vendor/yiisoft/db-mssql
      - /path/to/packages/db-oracle:/code/vendor/yiisoft/db-oracle
```

Adjust the `/path/to/packages` to the path where packages are installed on your host machine.

In case of ports' collisions, the mapping and enviroment variables can also be adjusted here.

### Unit testing

#### Available commands

`make test-all` - run all available tests.
`make test-db` - run tests for base db package only.
`make test-driver-all` - run tests for all drivers.
`make test-driver-sqlite` - run tests for SQLite driver only.
`make test-driver-mysql` - run tests for MySQL driver only (using MySQL database).
`make test-driver-mariadb` - run tests for MySQL driver only (using MariaDB database).
`make test-driver-pgsql` - run tests for PostgreSQL driver only.
`make test-driver-mssql` - run tests for Microsoft SQL Server driver only.
`make test-driver-oracle`- run tsets for Oracle driver only.

#### Testing different versions

Docker Compose services use the following stack:

- PHP 8.3.
- MySQL 9.
- MariaDB 11.
- PostgreSQL 19.
- Microsoft SQL Server 2022.
- Oracle Free 23.

Different versions are available in GitHub Actions. Other versions of RDBMS might be added to Docker Compose in the 
future.

#### Slow execution time

Running `make` command for the first time can take some time due to building and/or starting all required Docker Compose 
services. All subsequent calls will be faster.

The execution time of Oracle tests is the longest. The recommended flow is to run only changed / added tests. Add 
`@group temp` PHPDoc annotation to changed / added tests temporarily. Then you can limit running tests with the 
following command:

```shell
make test-driver-oracle RUN_ARGS="--group temp"
```

Don't forget to remove the temporary `@group` tags before marking PR as ready for review.

Avoid mixing changes for altering test structure with actual changes in test code.

### Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
make static-analysis
```

### Code style

Use [Rector](https://github.com/rectorphp/rector) to make codebase follow some specific rules or
use either newest or any specific version of PHP:

```shell
make rector
```

### Dependencies

This package uses [composer-require-checker](https://github.com/maglnet/ComposerRequireChecker) to check if all
dependencies are correctly defined in `composer.json`. To run the checker, execute the following command:

```shell
make composer-require-checker
```

### Miscellaneous commands

`make shell` - open interactive shell.
`make run command` - run arbitrary command.
