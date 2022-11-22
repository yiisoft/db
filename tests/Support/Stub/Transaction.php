<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Transaction\TransactionInterface;

final class Transaction extends \Yiisoft\Db\Driver\PDO\TransactionPDO implements TransactionInterface
{
}
