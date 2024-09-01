# Upgrading Instructions for Yii Database

This file contains the upgrade notes. These notes highlight changes that could break your
application when you upgrade the package from one version to another.

> **Important!** The following upgrading instructions are cumulative. That is, if you want
> to upgrade from version A to version C and there is version B between A and C, you need
> to following the instructions for both A and B.

## Upgrade from 1.x to 2.x

### `ColumnInterface` as column type

Add `ColumnInterface` support and change type of parameter `$type` from `string` to `ColumnInterface|string` 
in `addColumn()` method of your classes that implement the following interfaces:

- `Yiisoft\Db\Command\CommandInterface`;
- `Yiisoft\Db\QueryBuilder\DDLQueryBuilderInterface`;

… or inherit from the following classes:

- `Yiisoft\Db\Command\AbstractCommand`;
- `Yiisoft\Db\QueryBuilder\AbstractDDLQueryBuilder`;
- `Yiisoft\Db\QueryBuilder\AbstractQueryBuilder`.

### Scalar values for columns in `Query`

Change `$columns` parameter type from `array|string|ExpressionInterface` to `array|bool|float|int|string|ExpressionInterface`
in methods `select()` and `addSelect()` of your classes that implement `Yiisoft\Db\Query\QueryPartsInterface`.

Add support any scalar values for `$columns` parameter of these methods in your classes that implement
`Yiisoft\Db\Query\QueryPartsInterface` or inherit `Yiisoft\Db\Query\Query`.

### Build `Expression` instances inside `Expression::$params`

`ExpressionBuilder` is replaced by an abstract class `AbstractExpressionBuilder` with an instance of the 
`QueryBuilderInterface` parameter in the constructor. Each DBMS driver should implement its own expression builder.

`Expression::$params` can contain:
- non-unique placeholder names, they will be replaced with unique names;
- `Expression` instances, they will be built when building a query using `QueryBuilder`.

### Rename `batchInsert()` to `insertBatch()`

`batchInsert()` method is renamed to `insertBatch()` in `DMLQueryBuilderInterface` and `CommandInterface`.
The parameters are changed from `$table, $columns, $rows` to `$table, $rows, $columns = []`.
It allows to use the method without columns, for example:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

$values = [
    ['name' => 'Tom', 'age' => 30],
    ['name' => 'Jane', 'age' => 20],
    ['name' => 'Linda', 'age' => 25],
];

/** @var ConnectionInterface $db */
$db->createCommand()->insertBatch('user', $values)->execute();
```

### `ColumnSchemaInterface` changes

The interface and the abstract implementation `AbstractColumnSchema` were moved to `Yiisoft\Db\Schema\Column` namespace 
and the following changes were made:

- `getName()` method can return `string` or `null`;
- `getPhpType()` method must return `string` PHP type of the column which used for generating related model properties;
- `name(string|null $name)` method is added;
- `load(array $info)` method is added;
- constructor of `AbstractColumnSchema` class is changed to `__construct(string $type, string|null $phpType = null)`;
- added method chaining.

### New classes with constants

- `Yiisoft\Db\Constant\PhpType` with PHP types constants;
- `Yiisoft\Db\Constant\GettypeResult` with `gettype()` function results constants.

### New classes for table columns

Each table column has its own class in the `Yiisoft\Db\Schema\Column` namespace according to the data type:

- `BooleanColumnSchema` for columns with boolean type;
- `BitColumnSchema` for columns with bit type;
- `IntegerColumnSchema` for columns with integer type (tinyint, smallint, integer, bigint);
- `BigIntColumnSchema` for columns with integer type with range outside `PHP_INT_MIN` and `PHP_INT_MAX`;
- `DoubleColumnSchema` for columns with fractional number type (float, double, decimal, money);
- `StringColumnSchema` for columns with string or datetime type (char, string, text, datetime, timestamp, date, time);
- `BinaryColumnSchema` for columns with binary type;
- `JsonColumnSchema` for columns with json type.

### New methods

- `QuoterInterface::getRawTableName()` - returns the raw table name without quotes;
- `SchemaInterface::getColumnFactory()` - returns the column factory.

### Remove methods

- `AbstractDMLQueryBuilder::getTypecastValue()`
- `TableSchemaInterface::compositeForeignKey()`
- `SchemaInterface::isReadQuery()`
- `AbstractSchema::isReadQuery()`
- `SchemaInterface::getRawTableName()`
- `AbstractSchema::getRawTableName()`
- `AbstractSchema::normalizeRowKeyCase()`
- `Quoter::unquoteParts()`
- `AbstractPdoCommand::logQuery()`
- `ColumnSchemaInterface::phpType()`

### Remove deprecated parameters

- `$table` from `AbstractDMLQueryBuilder::normalizeColumnNames()` method
- `$table` from `AbstractDMLQueryBuilder::getNormalizeColumnNames()` method
- `$withColumn` from `QuoterInterface::getTableNameParts()` method
- `$rawSql` from `AbstractCommand::internalExecute()` method
- `$rawSql` from `AbstractPdoCommand::internalExecute()` method

### Remove constants

- `SchemaInterface::TYPE_JSONB`
- `SchemaInterface::PHP_TYPE_INTEGER`
- `SchemaInterface::PHP_TYPE_STRING`
- `SchemaInterface::PHP_TYPE_BOOLEAN`
- `SchemaInterface::PHP_TYPE_DOUBLE`
- `SchemaInterface::PHP_TYPE_RESOURCE`
- `SchemaInterface::PHP_TYPE_ARRAY`
- `SchemaInterface::PHP_TYPE_NULL`

### Other changes

- Allow `ExpressionInterface` for `$alias` parameter of `QueryPartsInterface::withQuery()` method;
- Allow `QueryInterface::one()` to return an object;
- Allow `QueryInterface::all()` to return array of objects;
