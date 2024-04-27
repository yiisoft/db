# Group by

O método `\Yiisoft\Db\Query\Query::groupBy()` especifica o fragmento `\Yiisoft\Db\Query\Query::groupBy()` de uma consulta SQL.

Por exemplo, o código a seguir irá gerar uma consulta que agrupa os resultados pela coluna `id` e pela coluna `status`.

```php
$query->groupBy(['id', 'status']);
```

A parte relevante do SQL é:

```sql
GROUP BY `id`, `status`
```

Se um `GROUP BY` envolve apenas nomes de colunas simples, você pode especificá-lo usando uma string, assim como faz ao escrever
instruções SQL brutas.

Por exemplo, o código a seguir irá gerar uma consulta que agrupa os resultados pela coluna `id` e pela coluna `status`.

```php
$query->groupBy('id, status');
```

> Dica: Prefira o formato array se `GROUP BY` envolver alguma expressão de banco de dados.

Você pode chamar `\Yiisoft\Db\Query\Query::addGroupBy()` para adicionar mais colunas ao fragmento `GROUP BY`.

Por exemplo, o código a seguir irá gerar uma consulta que agrupa os resultados pela coluna `id`, a coluna `status`
e a coluna `age`.

```php
$query->groupBy(['id', 'status'])->addGroupBy('age');
```
