# Statement Expressions

The library provides classes to represent SQL statements as expressions.
 
The following expression classes are available:
 
- [CaseX](#casex)
 
## CaseX
 
The [CaseX](../../../../src/Expression/Statement/CaseX.php) expression allows you to create SQL `CASE` statements.

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
