# Expressions

The library provides a set of classes to create SQL expressions. Expressions are represented by classes implementing
the `Yiisoft\Db\Expression\ExpressionInterface`. These classes allow you to build complex SQL expressions easily.

They can be used in various places, such as in the `select()`, `where()`, `having()`, `orderBy()`, and other methods 
of the `Yiisoft\Db\Query\QueryInterface` and the `insert()`, `update()`, `upsert()`, and other methods of the
`Yiisoft\Db\Command\CommandInterface` to represent SQL expressions.

## Available expressions
- [Expression](expression.md) that does not require escaping or quoting of its content;
- [Function expressions](functions.md) `ArrayMerge`, `Greatest`, `Least`, `Length`, `Longest`, `Shortest`;
- [Statement expressions](statements.md) `CaseX`;
- [Value expressions](values.md) `ArrayValue`, `ColumnName`, `DateTimeValue`, `JsonValue`, `Param`, `StructuredValue`,
  `Value`.
