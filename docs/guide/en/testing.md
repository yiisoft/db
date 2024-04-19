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

```dockerfile
docker compose up -d
```

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

## Documentation

- More information can be found in the [Internals.](docs/internals.md)

## Support

If you need help or have a question, the [Yii Forum](https://forum.yiiframework.com/c/yii-3-0/63) is a good place for that.
You may also check out other [Yii Community Resources](https://www.yiiframework.com/community).

## Support the project

[![Open Collective](https://img.shields.io/badge/Open%20Collective-sponsor-7eadf1?logo=open%20collective&logoColor=7eadf1&labelColor=555555)](https://opencollective.com/yiisoft)

## Follow updates

[![Official website](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](https://www.yiiframework.com/)
[![Twitter](https://img.shields.io/badge/twitter-follow-1DA1F2?logo=twitter&logoColor=1DA1F2&labelColor=555555?style=flat)](https://twitter.com/yiiframework)
[![Telegram](https://img.shields.io/badge/telegram-join-1DA1F2?style=flat&logo=telegram)](https://t.me/yii3en)
[![Facebook](https://img.shields.io/badge/facebook-join-1DA1F2?style=flat&logo=facebook&logoColor=ffffff)](https://www.facebook.com/groups/yiitalk)
[![Slack](https://img.shields.io/badge/slack-join-1DA1F2?style=flat&logo=slack)](https://yiiframework.com/go/slack)

## License

The Yii Access is free software. It is released under the terms of the BSD License.
Please see [`LICENSE`](./LICENSE.md) for more information.

Maintained by [Yii Software](https://www.yiiframework.com/).
