<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\Pdo;

use PDO;
use Yiisoft\Db\Connection\ServerInfoInterface;
use Yiisoft\Db\Exception\NotSupportedException;

class PdoServerInfo implements ServerInfoInterface
{
    protected string|null $version = null;

    public function __construct(protected PdoConnectionInterface $db)
    {
    }

    public function getTimezone(bool $refresh = false): string
    {
        throw new NotSupportedException(__METHOD__ . ' is not supported by this DBMS.');
    }

    public function getVersion(): string
    {
        if ($this->version === null) {
            /** @var string */
            $this->version = $this->db->getActivePdo()->getAttribute(PDO::ATTR_SERVER_VERSION) ?? '';
        }

        return $this->version;
    }
}
