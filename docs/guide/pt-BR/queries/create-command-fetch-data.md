# Crie um comando e busque dados

Para criar um comando, você pode usar o método `Yiisoft\Db\Connection\ConnectionInterface::createCommand()`:

```php
declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$command = $db->createCommand('SELECT * FROM {{%customer}}');
```

Nos comandos, existem diferentes métodos para **buscar dados**:

- [queryAll()](#query-all)
- [queryOne()](#query-one)
- [queryColumn()](#query-column)
- [queryScalar()](#query-scalar)
- [query()](#query)

> Nota: Para preservar a precisão, todos os dados obtidos do bancos de dados retornam no tipo string, mesmo que os
> correspondentes tipos de colunas do banco de dados sejam diferentes, numéricos, por exemplo.
> Você pode precisar usar conversão de tipo para convertê-los nos tipos PHP correspondentes.

## Query all

Retorna uma array de todas as linhas do conjunto de resultados.
Cada elemento do array é um array que representa uma linha de dados, com as chaves do array com os nomes das colunas.
Ele retorna um array vazio se a consulta não retornar nada.

Por exemplo, o código a seguir busca todas as linhas da tabela `customer`.

```php
declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$result = $db->createCommand('SELECT * FROM {{%customer}}')->queryAll();
```

O resultado é:

```php
[
    [
        'id' => '1',
        'email' => 'user1@example.com',
        'name' => 'user1',
        'address' => 'address1',
        'status' => '1',
        'profile_id' => '1',
    ],
    [
        'id' => '2',
        'email' => 'user2@example.com'
        'name' => 'user2',
        'address' => 'address2',
        'status' => '1',
        'profile_id' => null,
    ],
    [
        'id' => '3',
        'email' => 'user3@example.com',
        'name' => 'user3',
        'address' => 'address3',
        'status' => '2',
        'profile_id' => '2',
    ],
]
```

## Query one

Retorna uma única linha de dados.
O valor de retorno é uma array que representa a primeira linha do resultado da consulta.
Ele retorna `null` se a consulta não retornar nada.

Por exemplo, o código a seguir busca a primeira linha da tabela `customer`.

```php
declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */
$result = $db->createCommand('SELECT * FROM {{%customer}}')->queryOne();
```

O resultado é:

```php
[
    'id' => '1',
    'email' => 'user1@example.com',
    'name' => 'user1',
    'address' => 'address1',
    'status' => '1',
    'profile_id' => '1',
]
```

## Query column

Retorna os valores da primeira coluna do resultado da consulta.
Ele retorna um array vazio se a consulta não retornar nada.

Por exemplo, o código a seguir busca os valores da primeira coluna da tabela `customer`.

```php
declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$result = $db->createCommand('SELECT * FROM {{%customer}}')->queryColumn();
```

O resultado é:

```php
[
    '1',
    '2',
    '3',
]
```

## Query scalar

Retorna o valor da primeira coluna da primeira linha do resultado da consulta.
Ele retorna `false` se não houver valor.

Por exemplo, o código a seguir busca o valor da primeira coluna da primeira linha da tabela `customer`.

```php
declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$result = $db->createCommand('SELECT * FROM {{%customer}}')->queryScalar();
```

O resultado é:

```php
'1'
```

## Query

Retorna um objeto `Yiisoft\Db\DataReader\DataReaderInterface` para percorrer as linhas no conjunto de resultados.

Por exemplo, o código a seguir busca todas as linhas da tabela `customer`.

```php
declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$result = $db->createCommand('SELECT * FROM {{%customer}}')->query();

foreach ($result as $row) {
    // do something with $row
}
```
