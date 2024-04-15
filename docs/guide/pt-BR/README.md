# Começando

Yii DB é uma camada DAO (Data Access Object) para aplicações que usam [PHP](https://www.php.net/).
Ele fornece um conjunto de classes que ajudam você a acessar bancos de dados relacionais.
Ele foi projetado para ser flexível e extensível,
para que possa ser usado com diferentes bancos de dados e diferentes esquemas de banco de dados.
Sua natureza independente de banco de dados facilita a mudança de um banco de dados para outro.

Yii DB fornece uma API orientada a objetos para acessar bancos de dados relacionais.
É a base para outros métodos mais avançados de acesso ao banco de dados, incluindo o [Query Builder](query-builder.md).

Ao usar o Yii DB, você precisa lidar principalmente com SQLs simples e arrays PHP.
Como resultado, é a forma mais eficiente de acessar bancos de dados.
Entretanto, como a sintaxe SQL pode variar para diferentes bancos de dados, usar o Yii DB também significa que você terá que fazer um esforço extra para
criar um aplicativo independente de banco de dados.

Yii DB suporta os seguintes bancos de dados prontos para uso:

- [MSSQL](https://www.microsoft.com/en-us/sql-server/sql-server-2019) das versões **2017, 2019, 2022**.
- [MySQL](https://www.mysql.com/) das versões **5.7 - 8.0**.
- [MariaDB](https://mariadb.org/) das versões **10.4 - 10.9**.
- [Oracle](https://www.oracle.com/database/) das versões **12c - 21c**.
- [PostgreSQL](https://www.postgresql.org/) das versões **9.6 - 15**.
- [SQLite](https://www.sqlite.org/) da versão **3.3 e superior**.

## Instalação

Para instalar o Yii DB, você deve selecionar o driver que deseja usar e instalá-lo com o [Composer](https://getcomposer.org/).

Para [MSSQL](https://github.com/yiisoft/db-mssql):

```shell
composer require yiisoft/db-mssql
```

Para [MySQL/MariaDB](https://github.com/yiisoft/db-mysql):

```shell
composer require yiisoft/db-mysql
```

Para [Oracle](https://github.com/yiisoft/db-oracle):

```shell
composer require yiisoft/db-oracle
```

Para [PostgreSQL](https://github.com/yiisoft/db-pgsql):

```shell
composer require yiisoft/db-pgsql
```

Para [SQLite](https://github.com/yiisoft/db-sqlite):

```shell
composer require yiisoft/db-sqlite
```

## Pré-requisitos

## Configurar cache de esquema

Primeiro, você precisa [configurar o cache do esquema do banco de dados](schema/cache.md).

## Criar conexão

Você pode criar uma instância de conexão de banco de dados usando um [contêiner DI](https://github.com/yiisoft/di) ou sem ele.

- [Servidor MSSQL](connection/mssql.md)
- [Servidor MySQL/MariaDB](connection/mysql.md)
- [Servidor Oracle](connection/oracle.md)
- [Servidor PostgreSQL](connection/pgsql.md)
- [Servidor SQLite](connection/sqlite.md)

> Info: Quando você cria uma instância de conexão de banco de dados, a conexão real com o banco de dados não é estabelecida até
> você executar o primeiro SQL ou chamar o método `Yiisoft\Db\Connection\ConnectionInterface::open()` explicitamente.

### Logger e profiler (criador de perfil)

O Logger e o profiler são opcionais. Você pode usá-los se precisar registrar e criar um perfil de suas consultas.

- [Logger](connection/logger.md)
- [profiler](connection/profiler.md)

## Executar consultas SQL

Depois de ter uma instância de conexão com o banco de dados, você pode executar uma consulta SQL seguindo as seguintes etapas:

1. [Crie um comando e busque dados](queries/create-command-fetch-data.md)
2. [Parâmetros de vinculação (Bind parameters)](queries/bind-parameters.md)
3. [Executar um comando](queries/execute-command.md)
4. [Executar muitos comandos em uma transação](queries/transactions.md)

## Citar nomes de tabelas e colunas

Ao escrever um código independente de banco de dados, citar nomes de tabelas e colunas costuma ser uma dor de cabeça porque diferentes bancos de dados
têm nomes diferentes conforme suas regras.

Para superar esse problema, você pode usar a seguinte sintaxe de nomeação introduzida pelo Yii DB:

- `[[nome da coluna]]`: coloque o *nome da coluna* entre *colchetes duplos*.
- `{{%nome da tabela}}`: coloque o *nome da tabela* entre *colchetes duplos* e o caractere de porcentagem `%`
   será substituído pelo *prefixo da tabela*.

O Yii DB converterá automaticamente tais construções nas colunas ou nomes de tabelas entre aspas correspondentes usando a sintaxe específica do DBMS.

Por exemplo, o código a seguir gerará uma instrução SQL válida para todos os bancos de dados suportados:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$result = $db->createCommand('SELECT COUNT([[id]]) FROM {{%employee}}')->queryScalar()
```

## Construtor de consultas (Query Builder)

Yii DB fornece um [Query Builder](query-builder.md) que ajuda você a criar instruções SQL de uma maneira mais conveniente.
É uma ferramenta poderosa para criar instruções SQL complexas de forma simples.

## Comandos

Yii DB fornece uma classe `Command` que representa uma instrução **SQL** a ser executada em um banco de dados.

Você pode usá-lo para executar instruções **SQL** que não retornam nenhum conjunto de resultados, como `INSERT`, `UPDATE`, `DELETE`,
`CREATE TABLE`, `DROP TABLE`, `CREATE INDEX`, `DROP INDEX`, etc.

- [comandos DDL](command/ddl.md)
- [comandos DML](command/dml.md)

## Esquema

Yii DB fornece uma maneira de inspecionar os metadados de um banco de dados, como nomes de tabelas, nomes de colunas, etc.
através do esquema:

- [Leitura do esquema do banco de dados](schema/usage.md)
- [Configurando cache do esquema](schema/cache.md)

## Extensões

As seguintes extensões estão disponíveis para Yii DB.

- [Active Record](https://github.com/yiisoft/active-record).
- [Cache DB](https://github.com/yiisoft/cache-db)
- [Data DB](https://github.com/yiisoft/data-db)
- [Log Target DB](https://github.com/yiisoft/log-target-db)
- [Rbac DB](https://github.com/yiisoft/rbac-db)
- [Translator Message DB](https://github.com/yiisoft/translator-message-db)
- [Yii DB Migration](https://github.com/yiisoft/yii-db-migration)
