# Lendo esquemas do banco de dados

Yii DB fornece uma maneira de inspecionar os metadados de um banco de dados, como nomes de tabelas, nomes de colunas, etc.
por meio de objetos de esquema.

## Obtenha tabelas disponíveis

Para obter esquemas para todas as tabelas disponíveis, você pode usar o seguinte código:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$schemas = $db->getSchema()->getTableSchemas();
foreach ($schemas as $schema) {
    echo $schema->getFullName();
}
```

Se você deseja obter tabelas apenas de um determinado esquema de banco de dados:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * When schema name is set, the default schema name will be ignored.
 * 
 * @var ConnectionInterface $db
 */
$schemas = $db->getSchema()->getTableSchemas('public', true);
foreach ($schemas as $schema) {
    echo $schema->getFullName();
}
```

> Nota: Quando `refresh` for `true`, o esquema da tabela será recriado mesmo se for encontrado no cache.

## Inspecione uma tabela

Para obter um esquema para uma determinada tabela, use o seguinte código:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$tableSchema = $db->getTableSchema('customer');
```

Se não existir nenhuma tabela, o método retornará `null`. Então, para verificar se a tabela existe você pode fazer:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
if ($db->getTableSchema('customer') === null) {
    // there is no "customer" table
}
```

> Nota: `getTableSchema()` pode retornar informações do esquema em cache. Se você precisar ter certeza de que as informações estão
> atualizadas, passe `true` como segundo argumento.

Tendo um esquema de tabela, você pode obter várias informações sobre a tabela:

```php
use \Yiisoft\Db\Schema\TableSchemaInterface;

/** @var TableSchemaInterface $tableSchema */

echo 'To create ' . $tableSchema->getFullName() . " use the following SQL:\n";
echo $tableSchema->getCreateSql(); 
```

No nome completo o nome da tabela é prefixado pelo esquema do banco de dados.
Se o nome do esquema for igual ao esquema padrão, o nome completo não incluirá o nome do esquema.

### Recuperando esquemas de coluna

Você pode recuperar os metadados da coluna para uma determinada tabela usando o método `getColumns()` ou o método `getColumn()`
da classe `TableSchema`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$tableSchema = $db->getTableSchema('customer');
$columns = $tableSchema->getColumns();
foreach ($columns as $name => $column) {
    echo $name . ' (' . $column->getDbType() . ')';
}

// or a single column by name

$column = $tableSchema->getColumn('id');
echo 'id (' . $column->getDbType() . ')';
```

Em ambos os casos, você obtém a instância ou instâncias
ou a `ColumnSchemaInterface` que você pode usar para obter todas as informações sobre a coluna.
