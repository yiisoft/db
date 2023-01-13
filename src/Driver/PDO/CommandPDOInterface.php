<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\PDO;

use PDOStatement;
use Yiisoft\Db\Command\CommandInterface;

/**
 * The CommandPDOInterface defines a method `getPdoStatement()` that must be implemented by PDO command classes.
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
