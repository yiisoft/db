<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder\Conjunction;

use InvalidArgumentException;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\OrCondition;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * @implements ExpressionBuilderInterface<OrCondition>
 */
final class OrConditionBuilder implements ExpressionBuilderInterface
{
    private ExpressionsConjunctionBuilder $expressionsConjunctionBuilder;

    public function __construct(
        QueryBuilderInterface $queryBuilder,
    ) {
        $this->expressionsConjunctionBuilder = new ExpressionsConjunctionBuilder('OR', $queryBuilder);
    }

    /**
     * Build SQL for {@see OrCondition}.
     *
     * @param OrCondition $expression
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        return $this->expressionsConjunctionBuilder->build($expression->expressions, $params);
    }
}

