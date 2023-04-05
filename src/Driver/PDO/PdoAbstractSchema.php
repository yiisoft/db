<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\PDO;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Schema\AbstractSchema;

/**
 * Represents a schema for a PDO (PHP Data Object) connection.
 */
abstract class PdoAbstractSchema extends AbstractSchema
{
    /**
     * Generates the cache key for the current connection.
     *
     * @throws NotSupportedException If the connection is not a PDO connection.
     *
     * @return array The cache key.
     */
    protected function generateCacheKey(): array
    {
        $cacheKey = [];

        if ($this->db instanceof ConnectionPDOInterface) {
            $cacheKey = [$this->db->getDriver()->getDsn(), $this->db->getDriver()->getUsername()];
        } else {
            throw new NotSupportedException('Only PDO connections are supported.');
        }

        return $cacheKey;
    }
}
