# Yii Database Change Log

## 1.1.2 under development

- Bug #751: Fix collected debug actions (@xepozz)
- Enh #756: Refactor Quoter (@Tigrov) 
- Bug #756: Fix `quoteSql()` for sql containing table with prefix (@Tigrov)
- Bug #756: Fix `getTableNameParts()` for cases when different quotes for tables and columns (@Tigrov)
- Bug #756: Fix `quoteTableName()` for sub-query with alias (@Tigrov)

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
