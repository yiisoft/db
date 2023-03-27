# From

The `Yiisoft\Db\Query\Query::from()` method specifies the `FROM` fragment of a **SQL statement**.

For example, the following code will select all columns from the `user` table.

```php
// SELECT * FROM `user`
$query->from('{{%user}}');
```

You can specify the **table(s)** to select as either a string or an array.
The table names may contain **schema prefixes and/or table aliases**, like you do when writing **raw SQL statements**.

```php
$query->from(['{{public.%user}} u', '{{public.%post}} p']);

// equal to:

$query->from('{{public.%user}} u, {{public.%post}} p');
```

> Tip: Prefer the array format since it leaves less space for mistakes and is cleaner overall.

If you're using the array format, you can also use the array keys to specify the table aliases, like the following.

```php
$query->from(['u' => '{{public.%user}}', 'p' => '{{public.%post}}']);
```
