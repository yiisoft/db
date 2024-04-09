# Query builder (Construtor de consultas)

Construído sobre o Yii DB, o construtor de consultas permite que você construa uma consulta SQL de maneira programática e independente do DBMS.

Em comparação com a escrita de instruções SQL brutas, o uso do construtor de consultas ajudará você a escrever códigos relacionados SQL mais legíveis
e gerar instruções SQL mais seguras.

Usar um construtor de consultas geralmente envolve duas etapas:

1. Construa uma instância de classe `Yiisoft\Db\Query\Query` para representar diferentes partes (como `SELECT`, `FROM`) de uma
instrução SQL `SELECT`.
2. Execute um **método de consulta**, por exemplo, `all()`, `one()`, `scalar()`, `column()`, `query()` de
    `Yiisoft\Db\Query\Query` para recuperar dados do banco de dados.

O código a seguir mostra uma maneira típica de usar um construtor de consultas.

```php
declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/** @var ConnectionInterface $db */

$rows = (new Query($db))
    ->select(['id', 'email'])
    ->from('{{%user}}')
    ->where(['last_name' => 'Smith'])
    ->limit(10)
    ->all();
```

O código acima gera e executa a seguinte consulta SQL, onde o parâmetro `:last_name` está vinculado a
à string `Smith`:

```sql
SELECT `id`, `email` 
FROM `user`
WHERE `last_name` = :last_name
LIMIT 10
```

> Nota: `Yiisoft\Db\Query\Query` destina-se a ser usado mais ao invés de `Yiisoft\Db\QueryBuilder\QueryBuilder`.
> O primeiro invoca o último implicitamente quando você chama um dos métodos de consulta.
> `Yiisoft\Db\QueryBuilder\QueryBuilder` é a classe responsável por gerar instruções SQL dependentes de SGBD, como
> `SELECT`, `FROM`, `WHERE`, `ORDER BY` do `Yiisoft\Db\Query\Query`.

## Uso

- [Construindo consultas](/docs/guide/pt-BR/query-builder/building-queries.md).
