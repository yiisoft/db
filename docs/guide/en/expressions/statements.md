# Statement Expressions

The library provides classes to represent SQL statements as expressions.

> [!WARNING]
> The statements do not quote string values or column names, use [Value](../../../../src/Expression/Value/Value.php)
> object for string values and [ColumnName](../../../../src/Expression/Value/ColumnName.php) object for column names 
> or quote the values directly.
 
The following expression classes are available:
 
- [CaseX](#casex)
 
## CaseX
 
The [CaseX](../../../../src/Expression/Statement/CaseX.php) expression allows you to create SQL `CASE` statements.

```php
$case = new CaseX(
    new Column('status'),
    when1: new Wnen(new Value('active'), new Value('Active User')),
    when2: new Wnen("'inactive'", "'Inactive User'"),
    else: new Value('Unknown Status'),
);
```
