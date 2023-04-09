# Execute many commands in a transaction

In order for the data to be consistent when multiple commands are involved, you can use transactions.

```php
<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

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
        ->bindParam(':id', $id = 0)
        ->bindParam(':name', $name = '');
        
    $insertPostTagCommand = $db
        ->createCommand("INSERT INTO {{%post_tag}} ([[tag_id]], [[post_id]]) VALUES (:tag_id, :post_id)")
        ->bindParam(':tag_id', $id)
        ->bindValue(':post_id', 1);
        
    $tags = [
        [1, 'php'],
        [2, 'yii'],
        [3, 'db'],
    ];
        
    foreach ($tags as [$id, $name] => $tag) {
        $insertTagCommand->execute();
        $insertPostTagCommand->execute();
    }    
} catch () {
    $transaction->rollBack();
}
```

This way either you get all the data in place or no data at all, so your database stays consistent.

You can do it without `try`-`catch` as well:

```php
<?php

declare(strict_types=1);

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
        ->bindParam(':id', $id = 0)
        ->bindParam(':name', $name = '');
        
    $insertPostTagCommand = $db
        ->createCommand("INSERT INTO {{%post_tag}} ([[tag_id]], [[post_id]]) VALUES (:tag_id, :post_id)")
        ->bindParam(':tag_id', $id)
        ->bindValue(':post_id', 1);
        
    $tags = [
        [1, 'php'],
        [2, 'yii'],
        [3, 'db'],
    ];
        
    foreach ($tags as [$id, $name] => $tag) {
        $insertTagCommand->execute();
        $insertPostTagCommand->execute();
    }
});
```

When using transactions, you could specify isolation level as a second argument of `transaction()`, `beginTransaction()`
or `createTransaction()`.

