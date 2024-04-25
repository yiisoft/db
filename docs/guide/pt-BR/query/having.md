# Having

O método `Yiisoft\Db\Query\Query::having()` especifica o fragmento `HAVING` de uma consulta SQL.
É necessária uma condição que você pode especificar da mesma forma que  `Yiisoft\Db\Query\Query::where()`.

Por exemplo, o código a seguir irá gerar uma consulta que filtra os resultados pela coluna `status`:

```php
$query->having(['status' => 1]);
```

A parte relevante do SQL é:

```sql
HAVING `status` = 1
```

Consulte a documentação de [Where](/docs/guide/pt-BR/query/where.md) para obter mais detalhes sobre como especificar uma condição.

Você pode chamar `Yiisoft\Db\Query\Query::andHaving()` ou `Yiisoft\Db\Query\Query::orHaving()` para anexar mais condições
para o fragmento `HAVING`.

Por exemplo, o código a seguir irá gerar uma consulta que filtra os resultados pela coluna `status` e pela coluna `age`:

```php
$query->having(['status' => 1])->andHaving(['>', 'age', 30]);
```

A parte relevante do SQL é:

```sql
HAVING (`status` = 1) AND (`age` > 30)
```
