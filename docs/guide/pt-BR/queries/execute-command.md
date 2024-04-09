# Executar um comando

Todos os métodos introduzidos em [Criar um comando e buscar dados](create-command-fetch-data.md) lidam com
consultas `SELECT` que buscam dados de bancos de dados.

Para consultas que não retornam nenhum dado, você deve chamar o método `Yiisoft\Db\Command\CommandInterface::execute()`:

- Se a consulta for bem-sucedida, `Yiisoft\Db\Command\CommandInterface::execute()` retornará o número de linhas afetadas
pela execução do comando.
- Se nenhuma linha foi afetada pela execução do comando retornará `0`.
- Se a consulta falhar, ela lançará um `Yiisoft\Db\Exception\Exception`.

Digamos que haja uma tabela de clientes, com uma linha com id `1` presente e uma linha com id `1000` ausente. E
consultas não `SELECT` estão sendo executadas para ambos.

Então, no código a seguir, a contagem de linhas afetadas será `1`, porque a linha foi encontrada e atualizada com sucesso:

```php
declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand("UPDATE {{%customer}} SET [[name]] = 'John Doe' WHERE [[id]] = 1");
$rowCount = $command->execute(); // 1
```

Esta consulta, entretanto, não afeta nenhuma linha, porque nenhuma linha foi encontrada pela condição fornecida na cláusula `WHERE`:

```php
declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand("UPDATE {{%customer}} SET [[name]] = 'John Doe' WHERE [[id]] = 1000");
$rowCount = $command->execute(); // 0
```

No caso de SQL inválido, a exceção correspondente será lançada.

```php
declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$db->createCommand('bad SQL')->execute();
```
