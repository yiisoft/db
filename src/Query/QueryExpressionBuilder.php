<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Used internally to build a {@see Query} object using unified {@see \Yiisoft\Db\QueryBuilder\AbstractQueryBuilder}
 * expression building interface.
 */
final class QueryExpressionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        if (!$expression instanceof QueryInterface) {
            throw new InvalidConfigException('QueryExpressionBuilder can only be used with QueryInterface instance.');
        }

        [$sql, $params] = $this->queryBuilder->build($expression, $params);
        return "($sql)";
    }
}
