# Yii Database Change Log

## 1.2.1 under development

- Bug #777: Fix `Query::count()` when it returns an incorrect value if the result is greater than `PHP_INT_MAX` (@Tigrov)
- Enh #778: Deprecate unnecessary argument `$rawSql` of `AbstractCommand::internalExecute()` (@Tigrov)

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
