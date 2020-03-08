<?php

declare(strict_types=1);

namespace Yiisoft\Db\Querys;

use Yiisoft\Db\Exceptions\Exception;
use Yiisoft\Db\Exceptions\InvalidArgumentException;
use Yiisoft\Db\Exceptions\InvalidConfigException;
use Yiisoft\Db\Exceptions\NotSupportedException;
use Yiisoft\Db\Expressions\ExpressionBuilderTrait;
use Yiisoft\Db\Expressions\ExpressionBuilderInterface;
use Yiisoft\Db\Expressions\ExpressionInterface;

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
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string the raw SQL that will not be additionally escaped or quoted.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        [$sql, $params] = $this->queryBuilder->build($expression, $params);

        return "($sql)";
    }
}
