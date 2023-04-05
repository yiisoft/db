<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\Pdo;

use PDOStatement;

/**
 * This interface defines the method {@see getPdoStatement()} that must be implemented by {@see \PDO}.
 *
 * @see CommandInterface
 */
interface CommandInterface extends \Yiisoft\Db\Command\CommandInterface
{
    /**
     * @return PDOStatement|null The PDO statement.
     */
    public function getPdoStatement(): PDOStatement|null;
}
