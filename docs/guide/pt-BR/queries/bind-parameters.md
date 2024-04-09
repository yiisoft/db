# Bind parameters (Vincular parâmetros)

Existem dois casos de uso para vinculação de parâmetros:

- Quando você faz a mesma consulta com dados diferentes muitas vezes.
- Quando você precisa inserir valores na string SQL para evitar **ataques de injeção de SQL**.

Você pode fazer a vinculação usando espaços reservados nomeados (`:name`) ou espaços reservados posicionais (`?`) no lugar de valores e
passar valores como um argumento separado.

> Nota: Em muitos lugares em camadas de abstração superiores, como **query builder**, você geralmente especifica uma
**matriz de valores** e o Yii DB faz ligação de parâmetros para você, então não há necessidade de especificar os
parâmetros manualmente.

## Vincule um único valor

Você pode usar `bindValue()` para vincular um valor a um parâmetro.
Por exemplo, o código a seguir vincula o valor `42` ao espaço reservado nomeado `:id`.

```php
declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('SELECT * FROM {{%customer}} WHERE [[id]] = :id');
$command->bindValue(':id', 42);
$command->queryOne();
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

## Vincule muitos valores de uma vez

`bindValues()` vincula uma lista de valores aos espaços reservados nomeados correspondentes na instrução SQL.

Por exemplo, o código a seguir vincula os valores `3` e `user3` aos espaços reservados nomeados `:id` e `:name`.

```php
declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('SELECT * FROM {{%customer}} WHERE [[id]] = :id AND [[name]] = :name');
$command->bindValues([':id' => 3, ':name' => 'user3']);
$command->queryOne();
```

O resultado é:

```php
[
    'id' => '3',
    'email' => 'user3@example.com',
    'name' => 'user3',
    'address' => 'address3',
    'status' => '2',
    'profile_id' => '2',
]
```

## Vincular um parâmetro

`bindParam()` vincula um parâmetro à **variável** especificada.
A diferença com `bindValue()` é que a variável pode mudar.

Por exemplo, o código a seguir vincula o valor `2` e `user2` aos espaços reservados nomeados `:id` e `:name` e
então altera o valor:

```php
declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/** @var ConnectionInterface $db */

$command = $db->createCommand('SELECT * FROM {{%customer}} WHERE [[id]] = :id AND [[name]] = :name');
$id = 2;
$name = 'user2';
$command->bindParam(':id', $id);
$command->bindParam(':name', $name);
$user2 = $command->queryOne();

$id = 3;
$name = 'user3';
$user3 = $command->queryOne();
```

Os resultados são:

```php
[
    'id' => '2',
    'email' => 'user2@example.com',
    'name' => 'user2',
    'address' => 'address2',
    'status' => '1',
    'profile_id' => '1',
]

[
    'id' => '3',
    'email' => 'user3@example.com',
    'name' => 'user3',
    'address' => 'address3',
    'status' => '2',
    'profile_id' => '2',
]
```
