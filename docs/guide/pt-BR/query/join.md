# Join

O método `Yiisoft\Db\Query\Query::join()` especifica o fragmento `JOIN` de uma consulta SQL.

```php
$query->join('LEFT JOIN', 'post', 'post.user_id = user.id');
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
   > Nota: A sintaxe de array não funciona para especificar uma condição baseada em coluna.
   > `['user.id' => 'comment.userId']` resultará em uma condição
   > onde o ID do usuário deve ser igual à string `comment.userId`.
   > Você deve usar a sintaxe de string e especificar a condição como `user.id = comment.userId`.
- `params`: parâmetros opcionais para vincular à condição de junção.

Você pode usar os seguintes métodos de atalho para especificar `INNER JOIN`, `LEFT JOIN` e `RIGHT JOIN`, respectivamente.

- `innerJoin()`.
- `leftJoin()`.
- `rightJoin()`.

Por exemplo:

```php
$query->leftJoin('post', 'post.user_id = user.id');
```

Para unir muitas tabelas, chame os métodos de junção várias vezes, uma vez para cada tabela.

Além de juntar tabelas, você também pode juntar subconsultas.
Para fazer isso, especifique as subconsultas a serem unidas como objetos `Yiisoft\Db\Query\Query`.

Por exemplo:

```php
use Yiisoft\Db\Query\Query;

$subQuery = (new Query())->from('post');
$query->leftJoin(['u' => $subQuery], 'u.id = author_id');
```

Nesse caso, você deve colocar a subconsulta no array e usar a chave do array para especificar o alias.
