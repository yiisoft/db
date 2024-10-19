<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Default expression builder for {@see ArrayExpression}. Builds an expression as a JSON.
 */
final class ArrayExpressionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private readonly QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * The Method builds the raw SQL from the $expression that won't be additionally escaped or quoted.
     *
     * @param ArrayExpression $expression The expression to build.
     * @param array $params The binding parameters.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string The raw SQL that won't be additionally escaped or quoted.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        return $this->queryBuilder->buildExpression(new JsonExpression($expression->getValue()), $params);
    }
}
