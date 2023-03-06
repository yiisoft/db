<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\PDO;

use PDOStatement;
use Yiisoft\Db\Command\CommandInterface;

/**
 * This interface defines the method {@see getPdoStatement()} that must be implemented by {@see \PDO}.
 *
 * @see CommandInterface
 */
interface CommandPDOInterface extends CommandInterface
{
    /**
     * @return PDOStatement|null The PDO statement.
     */
    public function getPdoStatement(): PDOStatement|null;
}
