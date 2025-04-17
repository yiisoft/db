# Upgrading Instructions for Yii Database

This file contains the upgrade notes. These notes highlight changes that could break your
application when you upgrade the package from one version to another.

> **Important!** The following upgrading instructions are cumulative. That is, if you want
> to upgrade from version A to version C and there is version B between A and C, you need
> to following the instructions for both A and B.

## Upgrade from 1.x to 2.x

### Remove `ColumnInterface`

- Remove `ColumnInterface` and use `ColumnSchemaInterface` instead;
- Rename `ColumnSchemaInterface` to `ColumnInterface`.

### `ColumnInterface` as column type

Add `ColumnInterface` support and change type of parameter `$type` to `ColumnInterface|string` 
in the following methods: 
- `addColumn()`
- `alterColumn()`

in classes that implement the following interfaces:
- `Yiisoft\Db\Command\CommandInterface`;
- `Yiisoft\Db\QueryBuilder\DDLQueryBuilderInterface`;

or inherit from the following classes:
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

Rename `ColumnSchemaInterface` to `ColumnInterface`.

The interface and the abstract implementation `AbstractColumn` were moved to `Yiisoft\Db\Schema\Column` namespace 
and the following changes were made:

- `getName()` method can return `string` or `null`;
- `getPhpType()` method must return `string` PHP type of the column which used for generating related model properties;
- `withName(string|null $name)` method is added;
- `check(string|null $check)` method is added;
- `getCheck()` method is added;
- `reference(ForeignKeyConstraint|null $reference)` method is added;
- `getReference()` method is added;
- `notNull(bool $notNull = true)` method is added;
- `null()` method is added;
- `isNotNull()` method is added;
- `unique(bool $unique = true)` method is added;
- `isUnique()` method is added;
- `hasDefaultValue()` method is added;
- all `AbstractColumn` class properties except `$type` moved to constructor;
- added `DEFAULT_TYPE` constant to `AbstractColumn` class;
- added method chaining.

### New classes with constants

- `Yiisoft\Db\Constant\PhpType` with PHP types constants;
- `Yiisoft\Db\Constant\GettypeResult` with `gettype()` function results constants;
- `Yiisoft\Db\Constant\ColumnType` with abstract column types constants;
- `Yiisoft\Db\Constant\PseudoType` with column pseudo-types constants;
- `Yiisoft\Db\Constant\IndexType` with table index types;
- `Yiisoft\Db\Constant\ReferentialAction` with possible values of referential actions.

### New classes for table columns

Each table column has its own class in the `Yiisoft\Db\Schema\Column` namespace according to the data type:

- `BooleanColumn` for columns with boolean type;
- `BitColumn` for columns with bit type;
- `IntegerColumn` for columns with integer type (tinyint, smallint, integer, bigint);
- `BigIntColumn` for columns with integer type with range outside `PHP_INT_MIN` and `PHP_INT_MAX`;
- `DoubleColumn` for columns with fractional number type (float, double, decimal, money);
- `StringColumn` for columns with string or datetime type (char, string, text, datetime, timestamp, date, time);
- `BinaryColumn` for columns with binary type;
- `ArrayColumn` for columns with array type;
- `StructuredColumn` for columns with structured type (composite type in PostgreSQL);
- `JsonColumn` for columns with json type.

### New methods

- `QuoterInterface::getRawTableName()` - returns the raw table name without quotes;
- `QueryInterface::resultCallback()` - allows to use a callback, to be called on all rows of the query result;
- `QueryInterface::getResultCallback()` - returns the callback to be called on all rows of the query result or 
  `null` if the callback is not set;
- `QueryPartsInterface::setWhere()` - overwrites the `WHERE` part of the query;
- `ConnectionInterface::getColumnFactory()` - returns the column factory object for concrete DBMS;
- `ConnectionInterface::getServerInfo()` - returns `ServerInfoInterface` instance which provides server information;
- `QueryBuilderInterface::buildColumnDefinition()` - builds column definition for `CREATE TABLE` statement;
- `QueryBuilderInterface::prepareParam()` - converts a `ParamInterface` object to its SQL representation;
- `QueryBuilderInterface::prepareValue()` - converts a value to its SQL representation;
- `QueryBuilderInterface::getColumnFactory()` - returns the column factory object for concrete DBMS;
- `QueryBuilderInterface::getServerInfo()` - returns `ServerInfoInterface` instance which provides server information;
- `LikeConditionInterface::getCaseSensitive()` - returns whether the comparison is case-sensitive;
- `SchemaInterface::hasTable()` - returns whether the specified table exists in database;
- `SchemaInterface::hasSchema()` - returns whether the specified schema exists in database;
- `SchemaInterface::hasView()` - returns whether the specified view exists in database;
- `DbArrayHelper::arrange()` - arranges an array by specified keys;
- `CommandInterface::phpTypecasting()` - enables or disables typecasting of values when retrieving records from DB;
- `AbstractCommand::phpTypecasting()` - enables or disables typecasting of values when retrieving records from DB;
- `QueryInterface::typecasting()` - enables or disables typecasting of values when retrieving records from DB;
- `Query::typecasting()` - enables or disables typecasting of values when retrieving records from DB;
- `SchemaInterface::getResultColumn()` - returns the column instance for the column metadata received from the query;
- `AbstractSchema::getResultColumn()` - returns the column instance for the column metadata received from the query;
- `AbstractSchema::getResultColumnCacheKey()` - returns the cache key for the column metadata received from the query;
- `AbstractSchema::loadResultColumn()` - creates a new column instance according to the column metadata from the query;

### Remove methods

- `AbstractQueryBuilder::getColumnType()` - use `AbstractQueryBuilder::buildColumnDefinition()` instead;
- `AbstractDMLQueryBuilder::getTypecastValue()`;
- `TableSchemaInterface::compositeForeignKey()`;
- `SchemaInterface::createColumn()` - use `ColumnBuilder` instead;
- `SchemaInterface::isReadQuery()` - use `DbStringHelper::isReadQuery()` instead;
- `SchemaInterface::getRawTableName()` - use `QuoterInterface::getRawTableName()` instead;
- `AbstractSchema::isReadQuery()` - use `DbStringHelper::isReadQuery()` instead;
- `AbstractSchema::getRawTableName()` - use `QuoterInterface::getRawTableName()` instead;
- `AbstractSchema::normalizeRowKeyCase()` - use `array_change_key_case()` instead;
- `Quoter::unquoteParts()`;
- `AbstractPdoCommand::logQuery()`;
- `ColumnSchemaInterface::phpType()`;
- `ConnectionInterface::getServerVersion()` - use `ConnectionInterface::getServerInfo()` instead;
- `DbArrayHelper::getColumn()` - use `array_column()` instead;
- `DbArrayHelper::getValueByPath()`;
- `DbArrayHelper::populate()` - use `DbArrayHelper::index()` instead;
- `DbStringHelper::baseName()`;
- `DbStringHelper::pascalCaseToId()`;
- `AbstractDQLQueryBuilder::hasLimit()` - use `$limit !== null` instead;
- `AbstractDQLQueryBuilder::hasOffset()` - use `!empty($offset)` instead;
- `BatchQueryResultInterface::reset()` - use `BatchQueryResultInterface::rewind()` instead;
- `BatchQueryResult::reset()` - use `BatchQueryResult::rewind()` instead;

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
- Add parameters `$ifExists` and `$cascade` to `CommandInterface::dropTable()` and 
  `DDLQueryBuilderInterface::dropTable()` methods.
- Change `Quoter::quoteValue()` parameter type and return type from `mixed` to `string`;
- Move `DataType` class to `Yiisoft\Db\Constant` namespace;
- Change `DbArrayHelper::index()` parameter names and allow to accept `Closure` for `$indexBy` parameter; 
- Change return type of `CommandInterface::insertWithReturningPks()` method to `array|false`;
- Change return type of `AbstractCommand::insertWithReturningPks()` method to `array|false`;
- Rename `QueryBuilderInterface::quoter()` method to `QueryBuilderInterface::getQuoter()`;
- Change constructor parameters in `AbstractQueryBuilder` class;
- Remove nullable from `PdoConnectionInterface::getActivePdo()` result;
- Move `Yiisoft\Db\Query\Data\DataReaderInterface` interface to `Yiisoft\Db\Query` namespace;
- Move `Yiisoft\Db\Query\Data\DataReader` class to `Yiisoft\Db\Driver\Pdo` namespace and rename it to `PdoDataReader`;
- Add `indexBy()` and `resultCallback()` methods to `DataReaderInterface` and `PdoDataReader` class;
- Change return type of `DataReaderInterface::key()` method to `int|string|null`;
- Change return type of `DataReaderInterface::current()` method to `array|object|false`;
- Change `PdoDataReader` a constructor parameter;
- Remove the second parameter `$each` from `ConnectionInterface::createBatchQueryResult()`
  and `AbstractConnection::createBatchQueryResult()` methods;
- Rename `setPopulatedMethod()` to `resultCallback()` in `BatchQueryResultInterface` and `BatchQueryResult` class;
- Change return type of `key()` method to `int` in `BatchQueryResultInterface` and `BatchQueryResult` class;
- Change return type of `current()` method to `array` in `BatchQueryResultInterface` and `BatchQueryResult` class;
- Remove `null` from return type of `getQuery()` method in `BatchQueryResultInterface` and `BatchQueryResult` class;
- Remove parameters from `each()` method in `QueryInterface` and `Query` class;
- Change return type of `each()` method to `DataReaderInterface` in `QueryInterface` and `Query` class;
