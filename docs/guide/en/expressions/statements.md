# Statement Expressions

The library provides classes to represent SQL statements as expressions.
 
The following expression classes are available:
 
- [CaseX](#casex)
 
## CaseX
 
The [CaseX](../../../../src/Expression/Statement/CaseX.php) expression allows you to create SQL `CASE` statements.

The `CaseX` class accepts the following arguments:

- `value` comparison condition in the `CASE` expression:
  - `string` is treated as a table column name which will be quoted before usage in the SQL statement;
  - `array` is treated as a condition to check, see `QueryInterface::where()`;
  - other values will be converted to their string representation using `QueryBuilderInterface::buildValue()`.
  If not provided, the `CASE` expression will be a WHEN-THEN structure without a specific case value.
- `valueType` оptional data type of the `CASE` expression which can be used in some DBMS to specify the expected type;
- `...args` List of `WHEN-THEN` conditions and their corresponding results represented 
  as [WhenThen](https://github.com/yiisoft/db/blob/master/src/Expression/Statement/WhenThen.php) instances
  or `ELSE` value in the `CASE` expression. String `ELSE` value will be quoted before usage in the SQL statement.

For example:

```php
$case = new CaseX(
    'status',
    when1: new WnenThen('active', 'Active User'),
    when2: new WnenThen('inactive', 'Inactive User'),
    else: 'Unknown Status',
);
```

This will generate the following SQL:

```sql
CASE "status"
    WHEN 'active' THEN 'Active User'
    WHEN 'inactive' THEN 'Inactive User'
    ELSE 'Unknown Status'
END
```
