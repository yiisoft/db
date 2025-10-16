# Function Expressions

The library provides several classes to represent SQL functions as expressions.

> [!IMPORTANT]
> The functions quote string values as column names, except when they contain a parentheses `(`, in which case they
> will be treated as raw SQL expressions. Use [Value](../../../../src/Expression/Value/Value.php) object for string values.
> 
> For example, `new Longest(new Value('short'), 'column_name')` will be rendered as `LONGEST('short', "column_name")`.

The following expression classes are available:

- [ArrayMerge](#arraymerge)
- [Greatest](#greatest)
- [Least](#least)
- [Length](#length)
- [Longest](#longest)
- [Shortest](#shortest)

## ArrayMerge

The [ArrayMerge](../../../../src/Expression/Function/ArrayMerge.php) expression allows you to merge two or more arrays 
into one array.

```php
new ArrayMerge(['a', 'b'], 'array_col', $db->select(['array_col'])->from('table'));
```

## Greatest
The [Greatest](../../../../src/Expression/Function/Greatest.php) expression allows you to find the largest value 
in a list of expressions.

```php
new Greatest(1, 'int_col', $db->select(['int_col'])->from('table'));
```

## Least
The [Least](../../../../src/Expression/Function/Least.php) expression allows you to find the smallest value 
in a list of expressions.

```php
new Least(1, 'int_col', $db->select(['int_col'])->from('table'));
```

## Length
The [Length](../../../../src/Expression/Function/Length.php) expression allows you to find the length of a string.

```php
new Length(new Value('string'));
new Length('string_col');
new Length($db->select(['string_col'])->from('table'));
```

## Longest
The [Longest](../../../../src/Expression/Function/Longest.php) expression allows you to find the longest string 
in a list of expressions.

```php
new Longest(new Value('short'), 'string_col', $db->select(['string_col'])->from('table'));
```

## Shortest
The [Shortest](../../../../src/Expression/Function/Shortest.php) expression allows you to find the shortest string 
in a list of expressions.

```php
new Shortest(new Value('short'), 'string_col', $db->select(['string_col'])->from('table'));
```
