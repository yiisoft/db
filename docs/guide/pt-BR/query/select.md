# Select

O método `Yiisoft\Db\Query\Query::select()` especifica o fragmento `SELECT` de uma instrução SQL.

Em primeiro lugar, chamar este método é opcional e pode ser ignorado completamente, resultando na seleção de todas as colunas.

```php
$query->from('{{%user}}');

// equal to:

$query->select('*')->from('{{%user}}');
```

Você pode especificar colunas para selecionar como um array ou como uma string.
Os nomes das colunas selecionadas serão automaticamente citados durante a geração da instrução SQL.

```php
$query->select(['id', 'email']);

// equal to:

$query->select('id, email');
```

Os nomes das colunas selecionados podem incluir prefixos de tabela e/ou aliases de coluna, como você faz ao escrever consultas SQL brutas.

Por exemplo, o código a seguir selecionará as colunas `id` e `email` da tabela `user`.

```php
$query->select(['user.id AS user_id', 'email']);

// equal to:

$query->select('user.id AS user_id, email');
```

Se estiver usando o formato de array para especificar colunas, você também poderá usar as chaves de array para especificar os aliases das colunas.

Por exemplo, o código acima pode ser reescrito da seguinte maneira.

```php
$query->select(['user_id' => 'user.id', 'email']);
```

Se você não chamar o método `Yiisoft\Db\Query\Query::select()` ao construir uma consulta,
ele pressupõe selecionar `*`, o que significa selecionar todas as colunas.

Além dos nomes das colunas, você também pode selecionar expressões de banco de dados.
Nesse caso, você deve usar o formato de array para evitar citações automáticas incorretas de nomes.

Por exemplo, o código a seguir selecionará as colunas `CONCAT(first_name, ' ', last_name)` com o alias `full_name`
e a coluna `email`.

```php
$query->select(["CONCAT(first_name, ' ', last_name) AS full_name", 'email']); 
```

Tal como acontece com todos os lugares com SQL bruto envolvido,
você pode usar a sintaxe de cotação (quoting) independente do DBMS para nomes de tabelas e colunas ao escrever expressões de banco de dados em select.

Você também pode selecionar subconsultas. Você deve especificar cada subconsulta em termos de um objeto `Yiisoft\Db\Query\Query`.

Por exemplo, o código a seguir selecionará a contagem de usuários em cada postagem.

```php
declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/** @var ConnectionInterface $db */

$subQuery = (new Query($db))->select('COUNT(*)')->from('{{%user}}');
$query = (new Query($db))->select(['id', 'count' => $subQuery])->from('{{%post}}');
```

O SQL equivalente é:

```sql
SELECT `id`, (SELECT COUNT(*) FROM `user`) AS `count` FROM `post`
```

Para selecionar linhas distintas, você pode chamar `distinct()`, como a seguir.

```php
$query->select('user_id')->distinct();
```

Que resulta em:

```sql
SELECT DISTINCT `user_id`
```

Você pode chamar `Yiisoft\Db\Query\Query::addSelect()` para selecionar mais colunas.

Por exemplo, o código a seguir selecionará a coluna `email`, além das colunas `id` e `username` especificadas
inicialmente:

```php
$query->select(['id', 'username'])->addSelect(['email']);
```
