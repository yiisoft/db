# With query

O método `\Yiisoft\Db\Query\Query::withQuery()` especifica o prefixo  `WITH` de uma consulta SQL.
Você pode usá-lo em vez da subconsulta para obter mais legibilidade e alguns recursos exclusivos (CTE recursivo).
[Leia mais em Modern SQL](https://modern-sql.com/).

Por exemplo, esta consulta selecionará todas as permissões aninhadas de admin com seus filhos recursivamente.

```php
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/** @var ConnectionInterface $db */

$initialQuery = (new Query($db))
    ->select(['parent', 'child'])
    ->from(['aic' => '{{%auth_item_child}}'])
    ->where(['parent' => 'admin']);

$recursiveQuery = (new Query($db))
    ->select(['aic.parent', 'aic.child'])
    ->from(['aic' => '{{%auth_item_child}}'])
    ->innerJoin('t1', 't1.child = aic.parent');

$mainQuery = (new Query($db))
    ->select(['parent', 'child'])
    ->from('{{%t1}}')
    ->withQuery($initialQuery->union($recursiveQuery), 't1', true);
```

`\Yiisoft\Db\Query\Query::withQuery()` pode ser chamado múltiplas vezes para acrescentar mais CTEs à consulta principal.
As consultas serão acrescentadas na mesma ordem em que o método foi chamado.
Se uma das consultas for recursiva, todo o CTE se tornará recursivo.
