# Yii Database Change Log

## 1.1.2 under development

- Bug #746: Refactor `DMLQueryBuilder` and fix: unique indexes in `upsert()`, column names with table name and brackets, `batchInsert()` with associative arrays, enhanced documentation of `batchInsert()` and `update()` (@Tigrov)
- Enh #746: Refactor `AbstractDMLQueryBuilder` (@Tigrov)
- Bug #746: Fix `AbstractDMLQueryBuilder::upsert()` when unique index is not at the first position of inserted values (@Tigrov)
- Bug #746: Typecast values in `AbstractDMLQueryBuilder::batchInsert()` if column names with table name and brackets (@Tigrov)
- Bug #746, #61: Typecast values in `AbstractDMLQueryBuilder::batchInsert()` if values with string keys (@Tigrov)
- Enh #746: Enhanced documentation of `batchInsert()` and `update()` methods of `DMLQueryBuilderInterface` interface (@Tigrov)
- Bug #751: Fix collected debug actions (@xepozz)
- Chg #755: Deprecate `TableSchemaInterface::compositeForeignKey()` (@Tigrov) 
- Enh #756: Refactor `Quoter` (@Tigrov) 
- Bug #756: Fix `Quoter::quoteSql()` for SQL containing table with prefix (@Tigrov)
- Bug #756: Fix `Quoter::getTableNameParts()` for cases when different quotes for tables and columns (@Tigrov)
- Bug #756: Fix `Quoter::quoteTableName()` for sub-query with alias (@Tigrov)
- Bug #761: Quote aliases of CTE in `WITH` queries (@Tigrov)
- Chg #765: Deprecate `SchemaInterface::TYPE_JSONB` (@Tigrov)

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
