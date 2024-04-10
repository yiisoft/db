# Upgrading Instructions for Yii Database

The following upgrading instructions are cumulative. That is, if you want to upgrade from version A to version C and 
there is version B between A and C, you need to following the instructions for both A and B.

## Upgrade from 1.x to 2.x

Add `ColumnInterface` support and change type of parameter `$type` from `string` to `ColumnInterface|string` 
in `addColumn()` method of your classes that implement the following interfaces:

- `Yiisoft\Db\Command\CommandInterface`;
- `Yiisoft\Db\QueryBuilder\DDLQueryBuilderInterface`;

… or inherit from the following classes:

- `Yiisoft\Db\Command\AbstractCommand`;
- `Yiisoft\Db\QueryBuilder\AbstractDDLQueryBuilder`;
- `Yiisoft\Db\QueryBuilder\AbstractQueryBuilder`.
