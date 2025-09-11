# Expression

The `Yiisoft\Db\Expression\Expression` class represents a DB expression that does not require escaping or quoting 
of its content. It is useful when you need to include database functions, operators, or other SQL constructs
that cannot be represented by the query builder's methods.

```php
use Yiisoft\Db\Expression\Expression;

new Expression('NOW()');
new Expression('[[column1]] + [[column2]]');
```

It allows you to specify a raw SQL expression that will be included in the generated SQL statement as is.

## Parameters

When using parameters in an expression, you can provide them as an associative array where the keys are the parameter
names and the values are the corresponding values to be bound.

```php
new Expression('[[column]] = :value', [':value' => 123]);
```

## Nested expressions

You can also nest other expressions via parameters. For example:

```php
new Expression('[[column]] = :value', [':value' => new Expression('NOW()')]);
```

This will generate the following SQL:

```sql
"column" = NOW()
```

## Usage

The following example shows how to use the `Expression` class in a query:

```php
/** @var Yiisoft\Db\Connection\ConnectionInterface $db */
$rows = $db->select(['id', 'created_at' => new Expression('NOW()')])
    ->from('{{%user}}')
    ->where(new Expression('[[status]] = :status', [':status' => 1]))
    ->all();
```

The above code generates and executes the following SQL query:

```sql
SELECT "id", NOW() AS "created_at" FROM "user" WHERE "status" = :status
```
