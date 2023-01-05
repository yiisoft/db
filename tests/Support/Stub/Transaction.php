<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Driver\PDO\AbstractTransactionPDO;
use Yiisoft\Db\Transaction\TransactionInterface;

final class Transaction extends AbstractTransactionPDO implements TransactionInterface
{
}
