<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stubs;

use Yiisoft\Db\Driver\PDO\TransactionPDO;
use Yiisoft\Db\Transaction\TransactionInterface;

final class Transaction extends TransactionPDO implements TransactionInterface
{
    public function __construct(Connection $db)
    {
        parent::__construct($db);
    }
}
