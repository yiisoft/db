<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\Pdo;

use PDO;
use Yiisoft\Db\Connection\ServerInfoInterface;

class PdoServerInfo implements ServerInfoInterface
{
    protected string|null $version = null;

    public function __construct(protected PdoConnectionInterface $db)
    {
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
