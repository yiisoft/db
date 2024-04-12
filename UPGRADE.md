# Upgrading Instructions for Yii Database

The following upgrading instructions are cumulative. That is, if you want to upgrade from version A to version C and 
there is version B between A and C, you need to following the instructions for both A and B.

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

### Build `Expression` instances inside `Expression::$params`

`ExpressionBuilder` is replaced by an abstract class `AbstractExpressionBuilder` with an instance of the 
`QueryBuilderInterface` parameter in the constructor. Each DBMS driver should implement its own expression builder.

`Expression::$params` can contain:
- non-unique placeholder names, they will be replaced with unique names.
- `Expression` instances, they will be built when building a query using `QueryBuilder`.
