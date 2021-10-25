<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * @example
 * (new Query)->select('col1')->from(new TableName('table1', 't', 'dbo'))
 *
 * Note: We must use prefix (from connection) with tables with schema equal defaultSchema or without schema and don't use with other schemas
 * Note: With ExpressionInterface as tablename - we cannot add prefixes and quoting of table names.
 * For example with Oracle: (new Query)->select('*')->from(new Expression('dblink1.dbo.table')) for build `select * from dblink1.dbo.table1`
 */
final class TableName
{
    /**
     * @var ExpressionInterface|string
     */
    private $name;

    /**
     * @var string|null
     */
    private ?string $alias;

    /**
     * @var ExpressionInterface|string|null
     */
    private $schema;

    /**
     * @param ExpressionInterface|string $name
     * @param string|null $alias
     * @param ExpressionInterface|string|null $schema
     */
    public function __construct($name, ?string $alias = null, $schema = null)
    {
        if (!is_string($name) && !$name instanceof ExpressionInterface) {
            throw new InvalidArgumentException(
                'Name of table should be string or instanceof ExpressionInterface'
            );
        }

        if ($schema !== null && !is_string($schema) && !$schema instanceof ExpressionInterface) {
            throw new InvalidArgumentException(
                'Schema should be null, string or instanceof ExpressionInterface'
            );
        }

        $this->name = $name;
        $this->alias = empty($alias) ? null : $alias;
        $this->schema = empty($schema) ? null : $schema;
    }

    /**
     * @return ExpressionInterface|string
     */
    public function getName()
    {
        return $this->name;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @return ExpressionInterface|string|null
     */
    public function getSchema()
    {
        return $this->schema;
    }

    public function hasAlias(): bool
    {
        return $this->alias !== null;
    }

    public function hasSchema(): bool
    {
        return $this->schema !== null;
    }
}
