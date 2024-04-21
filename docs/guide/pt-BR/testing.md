# Testes

Este pacote pode ser testado globalmente ou individualmente para cada SGBD.

- [MSSQL](https://github.com/yiisoft/db-mssql)
- [MySQL/MariaDB](https://github.com/yiisoft/db-mysql)
- [Oracle](https://github.com/yiisoft/db-oracle)
- [PostgreSQL](https://github.com/yiisoft/db-pgsql)
- [SQLite](https://github.com/yiisoft/db-sqlite)

## Ações do Github

Todos os nossos pacotes possuem ações no GitHub por padrão, então você pode testar sua [contribuição](https://github.com/yiisoft/db/blob/master/.github/CONTRIBUTING.md) na nuvem.

> Observação: recomendamos a solicitação pull no modo rascunho até que todos os testes sejam aprovados.

## Imagens Docker

Para maior facilidade é recomendado utilizar containers Docker para cada SGBD, para isso você pode utilizar o arquivo [docker-compose.yml](https://docs.docker.com/compose/compose-file/) que está na raiz do diretório de cada pacote.

- [MSSQL 2022](https://github.com/yiisoft/db-mssql/blob/master/docker-compose.yml)
- [MySQL 8](https://github.com/yiisoft/db-mysql/blob/master/docker-compose.yml)
- [MariaDB 10.11](https://github.com/yiisoft/db-mysql/blob/master/docker-compose-mariadb.yml)
- [Oracle 21](https://github.com/yiisoft/db-oracle/blob/master/docker-compose.yml)
- [PostgreSQL 15](https://github.com/yiisoft/db-pgsql/blob/master/docker-compose.yml)

Para executar os contêineres Docker você pode usar o seguinte comando:

```dockerfile
docker compose up -d
```

### Testes globais

As etapas a seguir são necessárias para executar os testes.

1. Execute todos os contêineres Docker para cada banco de dados.
2. Instale as dependências do projeto com o composer.
3. Execute os testes.

```shell
vendor/bin/phpunit
```

### Testes individuais

As etapas a seguir são necessárias para executar os testes.

1. Execute o contêiner Docker para o dbms que deseja testar.
2. Instale as dependências do projeto com o composer.
3. Execute os testes.

```shell
vendor/bin/phpunit --testsuite=Pgsql
```

Suítes disponíveis:

- MSSQL
- Mysql
- Oracle
- Pgsql
- Sqlite

## Documentação

- [Internals](docs/internals.md)

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
