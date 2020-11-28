<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionBuilderTrait;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Class QueryExpressionBuilder is used internally to build {@see Query} object using unified {@see QueryBuilder}
 * expression building interface.
 */
class QueryExpressionBuilder implements ExpressionBuilderInterface
{
    use ExpressionBuilderTrait;

    /**
     * Method builds the raw SQL from the $expression that will not be additionally escaped or quoted.
     *
     * @param ExpressionInterface|Query $expression the expression to be built.
     * @param array $params the binding parameters.
     *
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @return string the raw SQL that will not be additionally escaped or quoted.
     */
    public function build($expression, array &$params = []): string
    {
        [$sql, $params] = $this->queryBuilder->build($expression, $params);

        return "($sql)";
    }
}
