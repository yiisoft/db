<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

use PDOStatement;

interface CommandPDOInterface extends CommandInterface
{
    /**
     * Return the PDO statement.
     */
    public function getPdoStatement(): ?PDOStatement;
}
