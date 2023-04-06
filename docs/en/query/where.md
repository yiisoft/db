# Where

The `Yiisoft\Db\Query\Query::where()` method specifies the `WHERE` fragment of a SQL query.
You can use one of the four formats to specify a `WHERE` condition.

- string format, `status=1`.
- hash format, `['status' => 1, 'type' => 2]`.
- operator format, `['like', 'name', 'test']`.
- object format, `new LikeCondition('name', 'LIKE', 'test')`.

## String format

String format is best used to specify basic conditions or if you need to use built-in functions of the DBMS.
It works as if you're writing a raw SQL.

For example, the following code will select all users whose status is 1.

```php
$query->where('status = 1');

// or use parameter binding to bind dynamic parameter values
$query->where('status = :status', [':status' => $status]);

// raw SQL using MySQL "YEAR()" function on a date field
$query->where('YEAR(somedate) = 2015');
```

Don't embed variables directly in the condition like the following, especially if the variable values come
from end user inputs, because this will make your application a subject to SQL injection attacks.

```php
// Dangerous! Don't do this unless you are certain $status must be an integer.
$query->where("status = $status");
```

When using parameter binding, you may call `Yiisoft\Db\Query\Query::params()` or `Yiisoft\Db\Query\Query::addParams()`
and pass parameters as a separate argument.

```php
$query->where('status = :status')->addParams([':status' => $status]);
```

As with all places where raw SQL involved,
you may use the DBMS agnostic quoting syntax for table and column names when writing conditions in string format.

## Hash format

Hash format is best used to specify many `AND`-concatenated subconditions each being a simple equality assertion.
It's written as an array whose keys are column names and values are their corresponding values.

```php
$query->where(['status' => 10, 'type' => null, 'id' => [4, 8, 15]]);
```

The relevant part of SQL is:

```sql
WHERE (`status` = 10) AND (`type` IS NULL) AND (`id` IN (4, 8, 15))
```

As you can see, the query builder is intelligent enough to handle values that are nulls or arrays.

You can also use subqueries with hash format like the following.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/** @var ConnectionInterface $db */

$userQuery = (new Query($db))->select('id')->from('user');
$query->where(['id' => $userQuery]);
```

The relevant part of SQL is:

```sql
WHERE `id` IN (SELECT `id` FROM `user`)
```

Using the hash format, Yii DB internally applies parameter binding for values, so in contrast to the string format,
here you don't have to add parameters manually.

However, note that Yii DB never escapes column names, so if you pass a variable obtained from the user side as a column
name without any more checks, the application will become vulnerable to SQL injection attacks.

To keep the application secure, either don't use variables as column names or filter variables with whitelist.

For example, the following code is vulnerable.

```php
// Vulnerable code:
$column = $request->get('column');
$value = $request->get('value');
$query->where([$column => $value]);
// $value is safe, but the $column name won't be encoded!
```

## Operator format

Operator format allows you to specify arbitrary conditions in a programmatic way. It takes the following format.

```php
['operator', 'operand1', 'operand2', ...]
```

Where the operands can each be specified in string format, hash format or operator format recursively,
while the operator can be one of the following:

- `and`: The operands should be concatenated together using `AND`.
  For example, `['and', 'id=1', 'id=2']` will generate `id=1 AND id=2`.
  If an operand is an array, it will be converted into a string using the rules described here.
  For example, `['and', 'type=1', ['or', 'id=1', 'id=2']]` will generate `type=1 AND (id=1 OR id=2)`.
  The method won't do any quoting or escaping.
- `or`: Similar to the `and` operator except that the operands are concatenated using `OR`.
- `not`: Requires only 1 operand, which will be wrapped in `NOT()`.
  For example, `['not', 'id=1']` will generate `NOT (id=1)`.
  Operand may also be an array to describe many expressions.
  For example `['not', ['status' => 'draft', 'name' => 'example']]` will generate
  `NOT ((status='draft') AND (name='example'))`.
- `between`: Operand 1 should be the column name, and operand 2 and 3 should be the starting and ending values of
  the range that the column is in.
  For example, `['between', 'id', 1,10]` will generate `id BETWEEN 1 AND 10`.
  In case you need to build a condition where value is between two columns `(like 11 BETWEEN min_id AND max_id)`,
  you should use `Yiisoft\Db\QueryBuilder\Condition\BetweenColumnsCondition`.
- `not between`: Similar to `between` except the `BETWEEN` is replaced with `NOT BETWEEN` in the generated condition.
- `in`: Operand 1 should be a column or DB expression.
  Operand 2 can be either an array or a `Yiisoft\Db\Query\Query`. 
  It will generate an `IN` condition.
  If Operand 2 is an array, it will represent the range of the values that the column or DB expression should be;
  If Operand 2 is a `Yiisoft\Db\Query\Query` object,
  a subquery will be generated and used as the range of the column or DB expression.
  For example, `['in', 'id', [1, 2, 3]]` will generate id `IN (1, 2, 3)`.
  The method will quote the column name and escape values in the range.
  The in operator also supports composite columns.
  In this case, operand 1 should be an array of the columns,
  while operand 2 should be an array of arrays or a `Yiisoft\Db\Query\Query` object representing the range of the columns.
  For example, `['in', ['id', 'name'], [['id' => 1, 'name' => 'oy']]]` will generate `(id, name) IN ((1, 'oy'))`.
- `not in`: Similar to the in operator except that `IN` is replaced with `NOT IN` in the generated condition.
- `like`: Operand 1 should be a column or DB expression, and operand 2 be a string or an array representing the values
  that the column or DB expression should be like.
  For example, `['like', 'name', 'tester']` will generate `name LIKE '%tester%'`.
  When the value range is given as an array, many `LIKE` predicates will be generated and concatenated using `AND`.
  For example, `['like', 'name', ['test', 'sample']]` will generate `name LIKE '%test%' AND name LIKE '%sample%'`.
  You may also give an optional third operand to specify how to escape special characters in the values.
  The operand should be an array of mappings from the special characters to their escaped counterparts.
  If this operand isn't provided, a default escape mapping will be used.
  You may use false or an empty array to indicate the values are already escaped and no escape should be applied.
  Note that when using an escape mapping (or the third operand isn't provided),
  the values will be automatically inside within a pair of percentage characters.

> Note: When using PostgreSQL, you may also use `ilike` instead of `like` for case-insensitive matching.

- `or like`: Similar to the `like` operator except that `OR` is used to concatenate the `LIKE` predicates when second
  operand is an array.
- `not like`: Similar to the `like` operator except that `LIKE` is replaced with `NOT LIKE` in the generated condition.
- `or not like`: Similar to the `not like` operator except that `OR` is used to concatenate the `NOT LIKE` predicates.
- `exists`: Requires one operand which must be an instance of `Yiisoft\Db\Query\Query` representing the sub-query.
  It will build an `EXISTS` (sub-query) expression.
- `not exists`: Similar to the `exists` operator and builds a `NOT EXISTS` (sub-query) expression.
- `>`, `<=`, or any other valid DB operator that takes two operands: The first operand must be a `column name` while
  the second operand a `value`. For example, `['>', 'age', 10]` will generate `age > 10`.

Using the operator format, Yii DB internally uses parameter binding for values, so in contrast to the string format,
here you don't have to add parameters manually.

However, note that Yii DB never escapes column names, so if you pass a variable as a column name, the application will
likely become vulnerable to SQL injection attack.

To keep application secure, either don't use variables as column names or filter variables against allow-list.
In case you need to get a column name from user.

For example, the following code is vulnerable.

```php
// Vulnerable code:
$column = $request->get('column');
$value = $request->get('value');
$query->where(['=', $column, $value]);
// $value is safe, but $column name won't be encoded!
```

## Object format

Object format is most powerful yet the most complex way to define conditions.
You need to follow it either if you want to build your own abstraction over query builder
or if you want to implement your own complex conditions.

Instances of condition classes are immutable.
Their only purpose is to store condition data and provide getters for condition builders.
Condition builder is a class that holds the logic that transforms data stored in condition into the SQL expression.

Internally, the formats described are implicitly converted to object format before building raw SQL,
so it's possible to combine formats in a single condition:

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\QueryBuilder\Condition\InCondition;
use Yiisoft\Db\QueryBuilder\Condition\OrCondition;
use Yiisoft\Db\Query\Query;

/** @var Query $query */

$query->andWhere(
    new OrCondition(
        [
            new InCondition('type', 'in', $types),
            ['like', 'name', '%good%'],
            'disabled=false',
        ],
    ),
);
```

Conversion from operator format into object format is performed according
to `Yiisoft\Db\QueryBuilder\AbstractDQLQueryBuilder::conditionClasses` property
that maps operator names to representative class names.

- `AND`, `OR` => `Yiisoft\Db\QueryBuilder\Condition\ConjunctionCondition`.
- `NOT` => `Yiisoft\Db\QueryBuilder\Condition\NotCondition`.
- `IN`, `NOT IN` => `Yiisoft\Db\QueryBuilder\Condition\InCondition`.
- `BETWEEN`, `NOT BETWEEN` => `Yiisoft\Db\QueryBuilder\Condition\BetweenCondition`.

## Appending conditions

You can use `Yiisoft\Db\Query\Query::andWhere()` or `Yiisoft\Db\Query\Query::orWhere()` to append more conditions
to an existing one. You can call them multiple times to append many conditions. This is useful for conditional logic
for example:

```php
$status = 10;
$search = 'yii';

$query->where(['status' => $status]);

if ($search !== '') {
    $query->andWhere(['like', 'title', $search]);
}
```

If $search isn't empty, the following `WHERE` condition will be generated:

```sql
WHERE (`status` = 10) AND (`title` LIKE '%yii%')
```

## Filter conditions

When building `WHERE` conditions based on input from end users, you usually want to ignore those input values 
that are empty.

For example, in a search form which allows you to search by username and email, you would like
to ignore the username/email condition if the user didn't enter anything in the corresponding input field.

You can achieve this goal by using the `Yiisoft\Db\Query\Query::filterWhere()` method:

```php	
// $username and $email are from user inputs
$query->filterWhere(['username' => $username, 'email' => $email]);
```

The only difference between `Yiisoft\Db\Query\Query::filterWhere()` and `Yiisoft\Db\Query\Query::where()`
is that the former will ignore empty values provided in the condition in hash format.

So, if `$email` is empty while `$username` isn't,
the above code will result in the SQL condition `WHERE username=:username`.

> **Note:** A value is considered empty if it's either `null`, an empty array, an empty string or a string containing 
> whitespaces only.

Like with `Yiisoft\Db\Query\Query::andWhere()` and `Yiisoft\Db\Query\Query::orWhere()`,
you can use `Yiisoft\Db\Query\Query::andFilterWhere()`
and `Yiisoft\Db\Query\Query::orFilterWhere()` to append more filter conditions to the existing one.

Additionally, there is `Yiisoft\Db\Query\Query::andFilterCompare()` that can intelligently determine operator based on
what's in the value.

```php
$query
    ->andFilterCompare('name', 'John Doe');
    ->andFilterCompare('rating', '>9');
    ->andFilterCompare('value', '<=100');
```

You can also specify the operator explicitly:

```php
$query->andFilterCompare('name', 'Doe', 'like');
```
