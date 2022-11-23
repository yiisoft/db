<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Driver\PDO\TransactionPDO;
use Yiisoft\Db\Transaction\TransactionInterface;

final class Transaction extends TransactionPDO implements TransactionInterface
{
}
