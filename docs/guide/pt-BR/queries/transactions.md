# Executar vários comandos em uma transação

Para que os dados sejam consistentes quando vários comandos estiverem envolvidos, você pode usar transações.

```php
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Constant\DataType;

/** @var ConnectionInterface $db */

$transaction = $db->beginTransaction();

try {
    $db
        ->createCommand('INSERT INTO {{%posts}} ([[id]], [[title]]) VALUES (:id, :title)')
        ->bindValue(':id', 1)
        ->bindValue(':title', 'This is a post')
        ->execute();

    $insertTagCommand = $db
        ->createCommand("INSERT INTO {{%tags}} ([[id]], [[name]]) VALUES (:id, :name)")
        ->bindParam(':id', $id, DataType::INTEGER)
        ->bindParam(':name', $name, DataType::STRING);
        
    $insertPostTagCommand = $db
        ->createCommand("INSERT INTO {{%post_tag}} ([[tag_id]], [[post_id]]) VALUES (:tag_id, :post_id)")
        ->bindParam(':tag_id', $id, DataType::INTEGER)
        ->bindValue(':post_id', 1);
        
    $tags = [
        [1, 'php'],
        [2, 'yii'],
        [3, 'db'],
    ];
        
    foreach ($tags as list($id, $name)) {
        $insertTagCommand->execute();
        $insertPostTagCommand->execute();
    }    
} catch (Exception $e) {
    $transaction->rollBack();
    // to get a slightest info about the Exception
    var_dump($e->getMessage());
}
```

Dessa forma, você obtém todos os dados ou nenhum dado, para que seu banco de dados permaneça consistente.

Você também pode fazer isso sem `try ... catch`:

```php
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/** @var ConnectionInterface $db */

$db->transaction(function (ConnectionInterface $db) {
    $db
        ->createCommand('INSERT INTO {{%posts}} ([[id]], [[title]]) VALUES (:id, :title)')
        ->bindValue(':id', 1)
        ->bindValue(':title', 'This is a post')
        ->execute();

    $insertTagCommand = $db
        ->createCommand("INSERT INTO {{%tags}} ([[id]], [[name]]) VALUES (:id, :name)")
        ->bindParam(':id', $id, DataType::INTEGER)
        ->bindParam(':name', $name, DataType::STRING);
        
    $insertPostTagCommand = $db
        ->createCommand("INSERT INTO {{%post_tag}} ([[tag_id]], [[post_id]]) VALUES (:tag_id, :post_id)")
        ->bindParam(':tag_id', $id, DataType::INTEGER)
        ->bindValue(':post_id', 1);
        
    $tags = [
        [1, 'php'],
        [2, 'yii'],
        [3, 'db'],
    ];
        
    foreach ($tags as list($id, $name)) {
        $insertTagCommand->execute();
        $insertPostTagCommand->execute();
    }
});
```

Ao usar transações, você pode especificar o nível de isolamento como um segundo argumento de `transaction()`, `beginTransaction()`
ou `createTransaction()`.
