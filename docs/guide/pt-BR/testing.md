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

## Testes Unit

O pacote é testado com [PHPUnit](https://phpunit.de/).

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

## Análise estática

O código é analisado estaticamente com [Psalm](https://psalm.dev/). Para executar análise estática:

```shell
./vendor/bin/psalm
```

## Rector

Use [Rector](https://github.com/rectorphp/rector) para fazer a base de código seguir algumas regras específicas ou use a versão mais recente ou qualquer versão específica do PHP:

```shell
./vendor/bin/rector
```

## composer requer checker

Este pacote usa [composer-require-checker](https://github.com/maglnet/ComposerRequireChecker) para verificar se todas as dependências estão definidas corretamente em `composer.json`.

Para executar o verificador, execute o seguinte comando:

```shell
./vendor/bin/composer-require-checker
```
