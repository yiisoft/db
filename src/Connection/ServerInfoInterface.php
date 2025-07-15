<?php

declare(strict_types=1);

namespace Yiisoft\Db\Connection;

/**
 * Should be implemented by a class that provides information about the database server.
 */
interface ServerInfoInterface
{
    /**
     * Returns the server's session timezone.
     *
     * @param bool $refresh Whether to reload the server's session timezone. If `false`, the timezone fetched before
     * will be returned if available.
     */
    public function getTimezone(bool $refresh = false): string;

    /**
     * Returns a server version as a string comparable by {@see version_compare()}.
     */
    public function getVersion(): string;
}
