<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

/**
 * Should be implemented by a class that provides information about the database server.
 */
interface ServerInfoInterface
{
    /**
     * Returns a server version as a string comparable by {@see version_compare()}.
     */
    public function getVersion(): string;
}
