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

### Join condition and an associative array

When join condition in the following methods `join()`, `innerJoin()`, `leftJoin()`,
`rightJoin()` of `Yiisoft\Db\Query\Query` class is an associative array, its string values will be quoted
as column names.

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
- `withName(string|null $name)` method is added;
- `check(string|null $check)` method is added;
- `getCheck()` method is added;
- `reference(ForeignKey|null $reference)` method is added;
- `getReference()` method is added;
- `notNull(bool $notNull = true)` method is added;
- `null()` method is added;
- `isNotNull()` method is added;
- `unique(bool $unique = true)` method is added;
- `isUnique()` method is added;
- `hasDefaultValue()` method is added;
- `getPrecision()` and `precision()` methods are removed;
- `getPhpType()` and `phpType()` methods are removed;
- all `AbstractColumn` class properties except `$type` moved to constructor;
- added `DEFAULT_TYPE` constant to `AbstractColumn` class;
- added method chaining.

### Changes in constraint classes

- Remove `Constraint` class;
- Rename classes `CheckConstraint`, `DefaultValueConstraint`, `ForeignKeyConstraint` and `IndexConstraint`
  to `Check`, `DefaultValue`, `ForeignKey` and `Index`;
- Move properties to constructor and make them `public readonly`;
- Remove all methods - use constructor to initialize values and properties to get values;

### New classes with constants

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
- `StringColumn` for columns with string type (char, string, text);
- `BinaryColumn` for columns with binary type;
- `DateTimeColumn` for columns with date and time type (timestamp, datetime, time, date);
- `ArrayColumn` for columns with array type;
- `StructuredColumn` for columns with structured type (composite type in PostgreSQL);
- `JsonColumn` for columns with json type.

### New methods

- `QuoterInterface::getRawTableName()` - returns the raw table name without quotes;
- `QueryInterface::resultCallback()` - allows to use a callback, to be called on all rows of the query result;
- `QueryInterface::getFor()` - returns the `FOR` part of the query;
- `QueryInterface::getResultCallback()` - returns the callback to be called on all rows of the query result or 
  `null` if the callback is not set;
- `QueryInterface::withTypecasting()` - enables or disables typecasting of values when retrieving records from DB;
- `QueryPartsInterface::for()` - sets the `FOR` part of the query;
- `QueryPartsInterface::addFor()` - adds more `FOR` parts to the existing ones;
- `QueryPartsInterface::setFor()` - overwrites the `FOR` part of the query;
- `QueryPartsInterface::setWhere()` - overwrites the `WHERE` part of the query;
- `QueryPartsInterface::setHaving()` - overwrites the `HAVING` part of the query;
- `QueryPartsInterface::addWithQuery()` - prepends an SQL statement using `WITH` syntax;
- `ConnectionInterface::getColumnBuilderClass()` - returns the column builder class name for concrete DBMS;
- `ConnectionInterface::getColumnFactory()` - returns the column factory object for concrete DBMS;
- `ConnectionInterface::getServerInfo()` - returns `ServerInfoInterface` instance which provides server information;
- `ConnectionInterface::createQuery()` - creates a `Query` object;
- `ConnectionInterface::select()` - creates a `Query` object with the specified columns to be selected;
- `QueryBuilderInterface::buildColumnDefinition()` - builds column definition for `CREATE TABLE` statement;
- `QueryBuilderInterface::buildValue()` - converts a value to its SQL representation with binding parameters if necessary;
- `QueryBuilderInterface::prepareParam()` - converts a `Param` object to its SQL representation;
- `QueryBuilderInterface::prepareValue()` - converts a value to its SQL representation;
- `QueryBuilderInterface::replacePlaceholders()` - replaces placeholders in the SQL string with the corresponding values;
- `QueryBuilderInterface::getColumnFactory()` - returns the column factory object for concrete DBMS;
- `QueryBuilderInterface::getServerInfo()` - returns `ServerInfoInterface` instance which provides server information;
- `DQLQueryBuilderInterface::buildFor()` - builds a SQL for `FOR` clause;
- `DMLQueryBuilderInterface::isTypecastingEnabled()` - returns whether typecasting is enabled for the query builder;
- `DMLQueryBuilderInterface::upsertReturning()` - builds a SQL to insert or update a record with returning values;
- `DMLQueryBuilderInterface::withTypecasting()` - enables or disables typecasting of values when inserting or updating 
  records in DB;
- `LikeCondition::getCaseSensitive()` - returns whether the comparison is case-sensitive;
- `SchemaInterface::hasTable()` - returns whether the specified table exists in database;
- `SchemaInterface::hasSchema()` - returns whether the specified schema exists in database;
- `SchemaInterface::hasView()` - returns whether the specified view exists in database;
- `DbArrayHelper::arrange()` - arranges an array by specified keys;
- `CommandInterface::upsertReturning()` - inserts or updates a record returning its values;
- `CommandInterface::upsertReturningPks()` - inserts or updates a record returning its primary keys;
- `CommandInterface::withDbTypecasting()` - enables or disables typecasting of values when inserting or updating records;
- `CommandInterface::withPhpTypecasting()` - enables or disables typecasting of values when retrieving records from DB;
- `CommandInterface::withTypecasting()` - enables or disables typecasting of values when inserting, updating 
  or retrieving records from DB;
- `SchemaInterface::getResultColumn()` - returns the column instance for the column metadata received from the query;
- `AbstractSchema::getResultColumnCacheKey()` - returns the cache key for the column metadata received from the query;
- `AbstractSchema::loadResultColumn()` - creates a new column instance according to the column metadata from the query;
- `DataReaderInterface::typecastColumns()` - sets columns for type casting the query results;
- `AbstractSchema::resolveFullName()` - resolves the full name of the table, view, index, etc.;
- `AbstractSchema::clarifyFullName()` - clarifies the full name of the table, view, index, etc.;

### Remove methods

- `AbstractQueryBuilder::getColumnType()` - use `AbstractQueryBuilder::buildColumnDefinition()` instead;
- `AbstractDMLQueryBuilder::getTypecastValue()`;
- `TableSchemaInterface::compositeForeignKey()`;
- `SchemaInterface::createColumn()` - use `ColumnBuilder` instead;
- `SchemaInterface::isReadQuery()` - use `DbStringHelper::isReadQuery()` instead;
- `SchemaInterface::findUniqueIndexes()` - use `SchemaInterface::getTableUniques()` instead;
- `SchemaInterface::getRawTableName()` - use `QuoterInterface::getRawTableName()` instead;
- `AbstractSchema::resolveTableName()` - use `QuoterInterface::getTableNameParts()` instead;
- `AbstractSchema::isReadQuery()` - use `DbStringHelper::isReadQuery()` instead;
- `AbstractSchema::getRawTableName()` - use `QuoterInterface::getRawTableName()` instead;
- `AbstractSchema::normalizeRowKeyCase()` - use `array_change_key_case()` instead;
- `Quoter::unquoteParts()`;
- `AbstractPdoCommand::logQuery()`;
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
- `Like::getEscapingReplacements()`/`LikeCondition::setEscapingReplacements()` - use `escape` constructor parameter
  instead;

### Remove deprecated parameters

- `$table` from `AbstractDMLQueryBuilder::normalizeColumnNames()` method
- `$table` from `AbstractDMLQueryBuilder::getNormalizeColumnNames()` method
- `$withColumn` from `QuoterInterface::getTableNameParts()` method
- `$rawSql` from `AbstractCommand::internalExecute()` method
- `$rawSql` from `AbstractPdoCommand::internalExecute()` method

### Remove constants

- `SchemaInterface::INDEX_*`
- `SchemaInterface::PHP_TYPE_*`
- `SchemaInterface::TYPE_*`

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
- Remove parameters and nullable result from `PdoConnectionInterface::getActivePdo()`;
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
- Add `indexBy()` method to `BatchQueryResultInterface` and `BatchQueryResult` class;
- Remove parameters from `each()` method in `QueryInterface` and `Query` class;
- Change return type of `each()` method to `DataReaderInterface` in `QueryInterface` and `Query` class;
- Add `$columnFactory` parameter to `AbstractPdoConnection::__construct()` constructor;
- Change `Query::$distinct` type to `bool` with `false` as default value;
- Change `QueryInterface::getDistinct()` result type to `bool`;
- Change `QueryPartsInterface::distinct()` parameter type to `bool`;
- Change `$distinct` parameter type in `DQLQueryBuilderInterface::buildSelect()` to `bool`;
- Add `QueryInterface` to type of `$columns` parameter of `insertWithReturningPks()` method in `CommandInterface` and 
  `AbstractCommand` class;
- Rename `insertWithReturningPks()` to `insertReturningPks()` method in `CommandInterface` and `DMLQueryBuilderInterface`;
- Remove `$params` parameter from `upsert()` method in `CommandInterface` and `AbstractCommand` class;
- Add default value to `$updateColumns` and `$params` parameters of `upsert()` method in `DMLQueryBuilderInterface` and 
  `AbstractDMLQueryBuilder` and `AbstractQueryBuilder` classes;
- Remove `ParamInterface`, use `Param` class instead;
- Remove `getType()` and `getValue()` methods from `Param` class, use `$type` and `$value` properties instead;
- Remove specific condition interfaces: `BetweenColumnsConditionInterface`, `BetweenConditionInterface`,
  `ConjunctionConditionInterface`, `ExistConditionInterface`, `HashConditionInterface`, `InConditionInterface`,
  `LikeConditionInterface`, `NotConditionInterface`, `OverlapsConditionInterface`, `SimpleConditionInterface`;
- `ConditionInterface` moved to `Yiisoft\Db\QueryBuilder\Condition` namespace;
- Remove `AbstractConjunctionCondition` and `AbstractOverlapsConditionBuilder`;
- Change namespace of condition and condition builder classes;
- Change namespace of expression and expression builder classes;
- Remove `AbstractDsn` and `AbstractDsnSocket` classes and `DsnInterface` interface;
- Remove `Hash` condition;
- Remove `AbstractTableSchema` and add `TableSchema` instead;
- Remove `BetweenColumns` condition;
- Change `QueryBuilderInterface::getExpressionBuilder()` result type to `ExpressionBuilderInterface`;
- Change `DQLQueryBuilderInterface::getExpressionBuilder()` result type to `ExpressionBuilderInterface`;
- Change type of `$tables` to `array` in `DQLQueryBuilderInterface::buildFrom()`;
- Rename `ArrayExpression` to `ArrayValue` and move it to `Yiisoft\Db\Expression\Value` namespace;
- Rename `ArrayExpressionBuilder` to `ArrayValueBuilder` and move it to `Yiisoft\Db\Expression\Value\Builder` namespace;
- Rename `JsonExpression` to `JsonValue` and move it to `Yiisoft\Db\Expression\Value` namespace;
- Rename `JsonExpressionBuilder` to `JsonValueBuilder` and move it to `Yiisoft\Db\Expression\Value\Builder` namespace;
- Rename `StructuredExpression` to `StructuredValue` and move it to `Yiisoft\Db\Expression\Value` namespace;`
- Rename `StructuredExpressionBuilder` to `StructuredValueBuilder` and move it to `Yiisoft\Db\Expression\Value\Builder`
  namespace;
- Remove `StaleObjectException`, `UnknownMethodException`, `UnknownPropertyException` classes;
- Remove `Expression::getParams()` method, use `$params` property instead;
- Allow `ExpressionInterface` for `$table` and `$on` parameters of `QueryPartsInterface` methods: `join()`,
  `innerJoin()`, `leftJoin()`, `rightJoin()`;
- `QueryInterface::getWithQueries()` method returns array of `WithQuery` instances;
- `QueryPartsInterface::withQuery()` method replace "WITH" clause instead of adding before;
- Change `QueryPartsInterface::withQueries()` parameter to variadic with type `WithQuery`;
- In `DQLQueryBuilderInterface::buildWithQueries()` method change first parameter type form `array[]` to `WithQuery[]`;
- Remove `AbstractCommand::refreshTableSchema()` method;
