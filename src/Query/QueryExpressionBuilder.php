<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;

/**
 * Class QueryExpressionBuilder is used internally to build {@see Query} object using unified {@see QueryBuilder}
 * expression building interface.
 */
class QueryExpressionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * @throws InvalidConfigException|InvalidArgumentException|NotSupportedException|Exception
     */
    public function build(QueryInterface $expression, array &$params = []): string
    {
        [$sql, $params] = $this->queryBuilder->build($expression, $params);
        return "($sql)";
    }
}
