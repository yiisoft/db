# Value Expressions

The library provides classes to represent PHP values as SQL expressions.

The following expression classes are available:

- [ArrayValue](#arrayvalue)
- [ColumnName](#columnname)
- [DateTimeValue](#datetimevalue)
- [JsonValue](#jsonvalue)
- [Param](#param)
- [StructuredValue](#structuredvalue)
- [Value](#value)

## ArrayValue
The [ArrayValue](../../../../src/Expression/Value/ArrayValue.php) expression allows to represent a PHP array as an SQL
array. In PostgreSQL, the array is represented using the native `ARRAY[]` syntax. In other databases, it is represented
as a JSON-encoded string.

```php
new ArrayValue(['a', 'b', 'c']);
```

## ColumnName

The [ColumnName](../../../../src/Expression/Value/ColumnName.php) expression allows to represent a quoted column name
for use in SQL statements.

```php
new ColumnName('column_name');
new ColumnName('table_name.column_name'); // with table name
```

## DateTimeValue

The [DateTimeValue](../../../../src/Expression/Value/DateTimeValue.php) expression allows to represent a date-time value
for use in SQL statements. The value can be provided as a `float`, `int`, `string` value or as a `Stringable` 
or `DateTimeInterface` object.

```php
new DateTimeValue(1672567200.123); // Unix timestamp with microseconds
new DateTimeValue(1672567200); // Unix timestamp
new DateTimeValue('2023-01-01 12:00:00');
new DateTimeValue(new DateTime('2023-01-01 12:00:00'));
```

## JsonValue

The [JsonValue](../../../../src/Expression/Value/JsonValue.php) expression allows to represent a PHP value as 
a JSON-encoded string for use in SQL statements.

```php
new JsonValue(['key' => 'value']);
```

## Param

The [Param](../../../../src/Expression/Value/Param.php) expression allows to represent a PHP value as a parameter value
with a specified data type for use in SQL statements. The parameter is represented by a placeholder in the SQL statement
(e.g. `:paramName`). The value will be bound to the parameter in a prepared statement using `PDOStatement::bindValue()`
method with the specified data type.

```php
use Yiisoft\Db\Constant\DataType;

new Param('value', DataType::STRING);
new Param("\x10\x11\x12", DataType::LOB);
```

## StructuredValue

The [StructuredValue](../../../../src/Expression/Value/StructuredValue.php) expression allows to represent a structured
PHP value (array or object) as a structured expression for use in SQL statements. In PostgreSQL, the value is represented
as a [composite type](https://www.postgresql.org/docs/current/rowtypes.html) value using `ROW()` expression. In other
databases, it is represented as a JSON-encoded string.

```php
new StructuredValue(['value' => 10, 'currency' => 'USD']);
new StructuredValue(new Money(10, 'USD'));
```

## Value

The [Value](../../../../src/Expression/Value/Value.php) expression allows to represent a PHP value for use in SQL
statements. The value will be quoted and escaped appropriately based on its type.

```php
new Value('string'); // string value will be quoted
new Value(123); // integer value will be converted to string without quotes
new Value(123.45); // float value will be converted to string without quotes
new Value(true); // boolean value will be converted to DBMS-specific representation
new Value(null); // null value will be converted to 'NULL'
new Value([1, 2, 3]); // array value will be converted to DBMS-specific representation, e.g. JSON or ARRAY
```
