<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pdo;

use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Class PdoValue represents a $value that should be bound to PDO with exact $type.
 *
 * For example, it will be useful when you need to bind binary data to BLOB column in DBMS:
 *
 * ```php
 * [':name' => 'John', ':profile' => new PdoValue($profile, \PDO::PARAM_LOB)]`.
 * ```
 *
 * To see possible types, check [PDO::PARAM_* constants](http://php.net/manual/en/pdo.constants.php).
 *
 * {@see http://php.net/manual/en/pdostatement.bindparam.php}
 */
final class PdoValue implements ExpressionInterface
{
    private ?string $value;
    private ?int $type;

    public function __construct(string $value = null, int $type = null)
    {
        $this->value = $value;
        $this->type = $type;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @return int One of PDO_PARAM_* constants
     *
     * {@see http://php.net/manual/en/pdo.constants.php}
     */
    public function getType(): ?int
    {
        return $this->type;
    }
}
