# Yii Database Change Log

## 2.0.0 under development

- Enh #820: Support `Traversable` values for `AbstractDMLQueryBuilder::batchInsert()` method with empty columns (@Tigrov)
- Enh #815: Refactor `Query::column()` method (@Tigrov) 
- Enh #816: Allow scalar values for `$columns` parameter of `Query::select()` and `Query::addSelect()` methods (@Tigrov)
- Enh #806: Non-unique placeholder names inside `Expression::$params` will be replaced with unique names (@Tigrov)
- Enh #806: Build `Expression` instances inside `Expression::$params` when build a query using `QueryBuilder` (@Tigrov)
- Enh #766: Allow `ColumnInterface` as column type. (@Tigrov)
- Bug #828: Fix `float` type when use `AbstractCommand::getRawSql()` method (@Tigrov)
- Enh #752: Implement `ColumnSchemaInterface` classes according to the data type of database table columns
  for type casting performance (@Tigrov)
- Enh #829: Rename `batchInsert()` to `insertBatch()` in `DMLQueryBuilderInterface` and `CommandInterface`
  and change parameters from `$table, $columns, $rows` to `$table, $rows, $columns = []` (@Tigrov)
- Enh #834: Refactor `AbstractCommand::insertBatch()`, add `Quoter::getRawTableName()` to `QuoterInterface` (@Tigrov)
- Chg #836: Remove `AbstractDMLQueryBuilder::getTypecastValue()` method (@Tigrov)
- Chg #837: Remove `$table` parameter from `normalizeColumnNames()` and `getNormalizeColumnNames()` methods 
  of `AbstractDMLQueryBuilder` class (@Tigrov)
- Chg #838: Remove `SchemaInterface::TYPE_JSONB` constant (@Tigrov)
- Chg #839: Remove `TableSchemaInterface::compositeForeignKey()` method (@Tigrov)
- Chg #840: Remove parameter `$withColumn` from `QuoterInterface::getTableNameParts()` method (@Tigrov)
- Enh #840: Remove `Quoter::unquoteParts()` method (@Tigrov)
- Chg #841: Remove `$rawSql` parameter from `AbstractCommand::internalExecute()` method
  and `AbstractPdoCommand::internalExecute()` method (@Tigrov)
- Enh #842: Allow `ExpressionInterface` for `$alias` parameter of `QueryPartsInterface::withQuery()` method (@Tigrov)
- Enh #843: Remove `AbstractPdoCommand::logQuery()` method (@Tigrov)
- Chg #845: Remove `AbstractSchema::normalizeRowKeyCase()` method (@Tigrov)
- Chg #846: Remove `SchemaInterface::isReadQuery()` and `AbstractSchema::isReadQuery()` methods (@Tigrov)
- Chg #847: Remove `SchemaInterface::getRawTableName()` and `AbstractSchema::getRawTableName()` methods (@Tigrov)
- Enh #852: Add method chaining for column classes (@Tigrov)
- Enh #855: Add array and JSON overlaps conditions (@Tigrov)
- Enh #860: Add `bit` abstract type (@Tigrov)
- Enh #862: Refactor PHP type of `ColumnSchemaInterface` instances (@Tigrov)
- Enh #865: Raise minimum PHP version to `^8.1` with minor refactoring (@Tigrov, @vjik)
- Enh #798: Allow `QueryInterface::one()` and `QueryInterface::all()` to return objects (@darkdef, @Tigrov)
- Enh #875: Ignore "Packets out of order..." warnings in `AbstractPdoCommand::internalExecute()` method (@Tigrov)

## 1.3.0 March 21, 2024

- Enh #778: Deprecate unnecessary argument `$rawSql` of `AbstractCommand::internalExecute()` (@Tigrov)
- Enh #779: Specify result type of `QueryInterface::all()`, `CommandInterface::queryAll()` and
  `DbArrayHelper::populate()` methods to `array[]` (@vjik)
- Enh #779: Specify populate closure type in `BatchQueryResultInterface` (@vjik)
- Enh #781: Skip calling `CommandInterface::getRawSql()` if no `logger` or `profiler` is set (@Tigrov)
- Enh #784: Specify result type of `ConstraintSchemaInterface::getTableIndexes()` method to `IndexConstraint[]` (@vjik)
- Enh #784: Remove unused code in `AbstractSchema::getTableIndexes()` (@vjik)
- Enh #785: Refactor `AbstractCommand::getRawSql()` (@Tigrov)
- Enh #786: Refactor `AbstractSchema::getDataType()` (@Tigrov)
- Enh #789: Remove unnecessary type casting to array in `AbstractDMLQueryBuilder::getTableUniqueColumnNames()` (@Tigrov)
- Enh #794: Add message type to log context (@darkdef)
- Enh #795: Allow to use `DMLQueryBuilderInterface::batchInsert()` method with empty columns (@Tigrov)
- Enh #801: Deprecate `AbstractSchema::normalizeRowKeyCase()` method (@Tigrov)
- Enh #801: Deprecate `SchemaInterface::getRawTableName()` and add `Quoter::getRawTableName()` method (@Tigrov)
- Enh #801: Deprecate `SchemaInterface::isReadQuery()` and add `DbStringHelper::isReadQuery()` method (@Tigrov)
- Enh #801: Remove unnecessary symbol `\\` from `rtrim()` function inside `DbStringHelper::baseName()` method (@Tigrov)
- Enh #802: Minor refactoring of `SchemaCache`, `AbstractPdoCommand` and `AbstractDDLQueryBuilder` (@Tigrov)
- Enh #809: Add psalm type for parameters to bind to the SQL statement (@vjik)
- Enh #810: Add more specific psalm type for `QueryFunctionsInterface::count()` result (@vjik)
- Bug #777: Fix `Query::count()` when it returns an incorrect value if the result is greater
  than `PHP_INT_MAX` (@Tigrov)
- Bug #785: Fix bug of `AbstractCommand::getRawSql()` when a param value is `Stringable` object (@Tigrov)
- Bug #788: Fix casting integer to string in `AbstractCommand::getRawSql()` (@Tigrov)
- Bug #801: Fix bug with `Quoter::$tablePrefix` when change `AbstractConnection::$tablePrefix` property (@Tigrov)

## 1.2.0 November 12, 2023

- Chg #755: Deprecate `TableSchemaInterface::compositeForeignKey()` (@Tigrov)
- Chg #765: Deprecate `SchemaInterface::TYPE_JSONB` (@Tigrov)
- Enh #746: Enhanced documentation of `batchInsert()` and `update()` methods of `DMLQueryBuilderInterface` interface (@Tigrov)
- Enh #756: Refactor `Quoter` (@Tigrov)
- Enh #770: Move methods from concrete `Command` class to `AbstractPdoCommand` class (@Tigrov)
- Bug #746: Typecast values in `AbstractDMLQueryBuilder::batchInsert()` if column names with table name and brackets (@Tigrov)
- Bug #746, #61: Typecast values in `AbstractDMLQueryBuilder::batchInsert()` if values with string keys (@Tigrov)
- Bug #751: Fix collected debug actions (@xepozz)
- Bug #756: Fix `Quoter::quoteTableName()` for sub-query with alias (@Tigrov)
- Bug #761: Quote aliases of CTE in `WITH` queries (@Tigrov)
- Bug #769, #61: Fix `AbstractDMLQueryBuilder::batchInsert()` for values as associative arrays (@Tigrov)

## 1.1.1 August 16, 2023

- New #617: Add debug collector for `yiisoft/yii-debug` (@xepozz)
- Enh #617, #733: Add specific psalm annotation of `$closure` parameter in `ConnectionInterface::transaction()` 
  method (@xepozz, @vjik)
- Bug #741: Fix `alterColumn()` method to accept `ColumnInterface::class` in argument `$type` (@terabytesoftw)

## 1.1.0 July 24, 2023

- Chg #722: Remove legacy array syntax for typecast. Use `Param` instead (@terabytesoftw)
- Chg #724: Typecast refactoring (@Tigrov)
- Chg #728: Refactor `AbstractSchema::getColumnPhpType()` (@Tigrov)

## 1.0.0 April 12, 2023

- Initial release.
