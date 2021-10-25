<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * @example
 * (new Query)->select('col1')->from(TableName::create('table1', 't', 'dbo'))
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
    private function __construct($name, ?string $alias, $schema = null)
    {
        $this->name = $name;
        $this->alias = $alias;
        $this->schema = $schema;
    }

    /**
     * @param ExpressionInterface|string $name
     * @param string|null $alias
     * @param ExpressionInterface|string|null $schema
     *
     * @return $this
     */
    public static function create($name, ?string $alias = null, $schema = null): self
    {
        assert(is_string($name) || $name instanceof ExpressionInterface);
        assert(is_string($schema) || $schema instanceof ExpressionInterface || $schema === null);

        return new self($name, $alias, $schema);
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
        return !($this->alias === null || $this->alias === '');
    }

    public function hasSchema(): bool
    {
        return !($this->schema === null || $this->schema === '');
    }
}
