<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver\PDO;

use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Class PDOValue represents a $value that should be bound to PDO with exact $type.
 *
 * For example, it will be useful when you need to bind binary data to BLOB column in DBMS:
 *
 * ```php
 * [':name' => 'John', ':profile' => new PDOValue($profile, \PDO::PARAM_LOB)]`.
 * ```
 *
 * To see possible types, check [PDO::PARAM_* constants](http://php.net/manual/en/pdo.constants.php).
 *
 * @link http://php.net/manual/en/pdostatement.bindparam.php
 */
final class PDOValue implements ExpressionInterface
{
    public function __construct(private ?string $value = null, private ?int $type = null)
    {
    }

    public function build(
        QueryBuilderInterface $queryBuilder,
        ExpressionInterface $expression,
        array &$params = []
    ): string {
        return (new PDOValueBuilder())->build($expression, $params);
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @return int|null One of PDO_PARAM_* constants
     *
     * {@see http://php.net/manual/en/pdo.constants.php}
     */
    public function getType(): ?int
    {
        return $this->type;
    }
}
