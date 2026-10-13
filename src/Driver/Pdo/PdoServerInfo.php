<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\Pdo;

use PDO;
use Yiisoft\Db\Connection\ServerInfoInterface;
use Yiisoft\Db\Exception\NotSupportedException;

class PdoServerInfo implements ServerInfoInterface
{
    protected ?string $version = null;

    public function __construct(protected PdoConnectionInterface $db) {}

    public function getTimezone(bool $refresh = false): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }

    public function getVersion(): string
    {
        /** @psalm-suppress PossiblyInvalidCast Psalm types PDO::getAttribute() as possibly returning an array. */
        return $this->version ??= (string) $this->db->getActivePdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
    }
}
