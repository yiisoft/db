# Join

O método `Yiisoft\Db\Query\Query::join()` especifica o fragmento `JOIN` de uma consulta SQL.

```php
$query->join('LEFT JOIN', 'post', ['post.user_id' => 'user.id']);
```

A parte relevante do SQL é:

```sql
LEFT JOIN `post` ON `post`.`user_id` = `user`.`id`
```

O método `Yiisoft\Db\Query\Query::join()` usa quatro parâmetros:

- `type`: tipo de junção como `INNER JOIN`, `LEFT JOIN`.
- `table`: o nome da tabela a ser unida.
- `on`: condição de junção opcional, esse é o fragmento `ON`.
   Consulte `Yiisoft\Db\Query\Query::where()` para obter detalhes sobre como especificar uma condição.
  > [!IMPORTANT]
  > Chaves e valores de uma matriz associativa são tratados como nomes de colunas e serão citados antes de serem usados em uma consulta SQL.
- `params`: parâmetros opcionais para vincular à condição de junção.

Você pode usar os seguintes métodos de atalho para especificar `INNER JOIN`, `LEFT JOIN` e `RIGHT JOIN`, respectivamente.

- `innerJoin()`.
- `leftJoin()`.
- `rightJoin()`.

Por exemplo:

```php
$query->leftJoin('post', ['post.user_id' => 'user.id']);
```

Para unir muitas tabelas, chame os métodos de junção várias vezes, uma vez para cada tabela.

Além de juntar tabelas, você também pode juntar subconsultas.
Para fazer isso, especifique as subconsultas a serem unidas como objetos `Yiisoft\Db\Query\Query`.

Por exemplo:

```php
/** @var Yiisoft\Db\Connection\ConnectionInterface $db */

$subQuery = $db->select()->from('post');
$query->leftJoin(['u' => $subQuery], ['u.id' => 'author_id']);
```

Nesse caso, você deve colocar a subconsulta no array e usar a chave do array para especificar o alias.
