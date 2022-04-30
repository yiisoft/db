<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\PDO;

use PDOStatement;
use Yiisoft\Db\Command\CommandInterface;

interface CommandPDOInterface extends CommandInterface
{
    /**
     * Return the PDO statement.
     */
    public function getPdoStatement(): ?PDOStatement;
}
