<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Builder\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Exists;
use Yiisoft\Db\QueryBuilder\Condition\NotExists;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

/**
 * Build an object of {@see Exists} or {@see NotExists} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<Exists|NotExists>
 */
class ExistsBuilder implements ExpressionBuilderInterface
{
    public function __construct(private readonly QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * Build SQL for {@see Exists} or {@see NotExists}.
     *
     * @param Exists|NotExists $expression
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $operator = match ($expression::class) {
            Exists::class => 'EXISTS',
            NotExists::class => 'NOT EXISTS',
        };

        $sql = $this->queryBuilder->buildExpression($expression->query, $params);

        return "$operator $sql";
    }
}
