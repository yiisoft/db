# Expressions as functions

The library provides several classes to represent SQL functions as expressions.

> [!WARNING]
> The functions do not quote string values or column names, use [Value](../../../../src/Expression/Value.php)
> expression for string values and [ColumnName](../../../../src/Expression/ColumnName.php) expression for column names
> or quote the values directly.
> 
> For example, `new Longest(new Value('short'), new ColumnName('column'), "'longest'")`

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
new ArrayMerge(['a', 'b'], new Column('array_col'), $db->select(['array_col'])->from('table'));
```

## Greatest
The [Greatest](../../../../src/Expression/Function/Greatest.php) expression allows you to find the largest value 
in a list of expressions.

```php
new Greatest(1, new Column('int_col'), $db->select(['int_col'])->from('table'));
```

## Least
The [Least](../../../../src/Expression/Function/Least.php) expression allows you to find the smallest value 
in a list of expressions.

```php
new Least(1, new Column('int_col'), $db->select(['int_col'])->from('table'));
```

## Length
The [Length](../../../../src/Expression/Function/Length.php) expression allows you to find the length of a string.

```php
new Length(new Value('string'));
new Length(new Column('string_col'));
new Length($db->select(['string_col'])->from('table'));
```

## Longest
The [Longest](../../../../src/Expression/Function/Longest.php) expression allows you to find the longest string 
in a list of expressions.

```php
new Longest(new Value('short'), new Column('string_col'), $db->select(['string_col'])->from('table'));
```

## Shortest
The [Shortest](../../../../src/Expression/Function/Shortest.php) expression allows you to find the shortest string 
in a list of expressions.

```php
new Shortest(new Value('short'), new Column('string_col'), $db->select(['string_col'])->from('table'));
```
