# CompositeExpression

The `Yiisoft\Db\Expression\CompositeExpression` class represents a composite expression consisting of multiple 
expressions. It is useful when you need to combine several SQL expressions into a single expression that will be joined
with a specified separator.

```php
use Yiisoft\Db\Expression\CompositeExpression;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\Value\ColumnName;

new CompositeExpression([new ColumnName('id'), 'DESC']);
new CompositeExpression(['x = 1', 'y = 2'], ' AND '); // Custom separator
```

- Expressions are joined using the specified separator (space by default).
- Parameters from nested expressions are properly collected and merged.
- String expressions are not escaped, so be careful with user input.
- All expressions are processed in the order they were provided in the array.

## Usage

The following example shows how to use the `CompositeExpression` class in a query:

```php
/** @var Yiisoft\Db\Connection\ConnectionInterface $db */
$order = new CompositeExpression([new ColumnName('id'), 'DESC']);
$rows = $db->select(['*'])
    ->from('{{%user}}')
    ->orderBy($order)
    ->all();
```

The above code generates and executes the following SQL query:

```sql
SELECT * FROM "user" ORDER BY "id" DESC
```
