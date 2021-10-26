<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Holds name of a table including schema and alias.
 * Usage is the following:
 *
 * (new Query)->select('col1')->from(new TableName('table1', 't', 'dbo'))
 *
 * Note: We use prefix from connection with tables which schema equals default schema or which has no schema.
 * Note: With `ExpressionInterface` as tablename we cannot add prefixes and quote table names.
 * For example, with Oracle: `(new Query)->select('*')->from(new Expression('dblink1.dbo.table'))` to build `select * from dblink1.dbo.table1`
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
                'Name of the table should be a string or an instanceof ExpressionInterface.'
            );
        }

        if ($schema !== null && !is_string($schema) && !$schema instanceof ExpressionInterface) {
            throw new InvalidArgumentException(
                'Schema should be null, string, or and instance of ExpressionInterface.'
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
