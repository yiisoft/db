# Comandos de linguagem de manipulação de dados (DML)

DML é um conjunto de instruções SQL usadas para manipular dados em um banco de dados.

Você pode usar o DML para realizar as seguintes operações:

- [Inserção em lote](#inserção-em-lote)
- [Excluir linhas](#excluir-linhas)
- [Resetar uma sequência](#resetar-uma-sequência)
- [Inserir](#inserir)
- [Atualizar](#atualizar)
- [Upsert (Atualizar ou Inserir)](#upsert)

## Inserção em lote

Para inserir múltiplas linhas em uma tabela, você pode usar o método `Yiisoft\Db\Command\CommandInterface::insertBatch()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->insertBatch(
    '{{%customer}}',
    ['name', 'email'],
    [
        ['user1', 'email1@email.com'],
        ['user2', 'email2@email.com'],
        ['user3', 'email3@email.com'],
    ]
)->execute();
```

It is possible to insert rows as associative arrays, where the keys are column names.

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->insertBatch(
    '{{%customer}}',
    [
        ['name' => 'user1', 'email' => 'email1@email.com'],
        ['name' => 'user2', 'email' => 'email2@email.com'],
        ['name' => 'user3', 'email' => 'email3@email.com'],
    ],
)->execute();
```

## Excluir linhas

Para excluir linhas de uma tabela, você pode usar o método `Yiisoft\Db\Command\CommandInterface::delete()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->delete('{{%customer}}', ['id' => 1])->execute();
```

## Resetar uma sequência

Para redefinir a sequência de uma tabela, você pode usar o método `Yiisoft\Db\Command\CommandInterface::resetSequence()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->resetSequence('{{%customer}}', 1)->execute();
```

## Inserir

Para inserir uma linha em uma tabela, você pode usar o método `Yiisoft\Db\Command\CommandInterface::insert()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->insert('{{%customer}}', ['name' => 'John Doe', 'age' => 18])->execute();
```

## Atualizar

Para atualizar linhas em uma tabela, você pode usar o método `Yiisoft\Db\Command\CommandInterface::update()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$db->createCommand()->update('{{%customer}}', ['status' => 2], ['id' > 1])->execute();
```

## Upsert

Para atualizar atomicamente linhas existentes e inserir linhas não existentes,
você pode usar o método `Yiisoft\Db\Command\CommandInterface::upsert()`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Expression\Expression;

/** @var ConnectionInterface $db */
$db->createCommand()->upsert(
    'pages',
    [
        'name' => 'Front page',
        'url' => 'https://example.com/', // URL is unique
        'visits' => 0,
    ],
    updateColumns: [
        'visits' => new Expression('visits + 1'),
    ],
    params: $params,
)->execute();
```
