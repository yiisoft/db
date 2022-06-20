<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Conditions\Builder;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Conditions\InCondition;
use Yiisoft\Db\QueryBuilder\Conditions\Interface\HashConditionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Query\QueryInterface;

use function count;
use function implode;
use function str_contains;

/**
 * Class HashConditionBuilder builds objects of {@see HashCondition}.
 */
class HashConditionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * @throws Exception|InvalidArgumentException|InvalidConfigException|NotSupportedException
     *
     * @psalm-suppress MixedAssignment
     */
    public function build(HashConditionInterface $expression, array &$params = []): string
    {
        $hash = $expression->getHash() ?? [];
        $parts = [];

        /**
         * @psalm-var array<string, array|mixed> $hash
         */
        foreach ($hash as $column => $value) {
            if (is_iterable($value) || $value instanceof QueryInterface) {
                /** IN condition */
                $parts[] = $this->queryBuilder->buildCondition(new InCondition($column, 'IN', $value), $params);
            } else {
                if (!str_contains($column, '(')) {
                    $column = $this->queryBuilder->quoter()->quoteColumnName($column);
                }

                if ($value === null) {
                    $parts[] = "$column IS NULL";
                } elseif ($value instanceof ExpressionInterface) {
                    $parts[] = "$column=" . $this->queryBuilder->buildExpression($value, $params);
                } else {
                    $phName = $this->queryBuilder->bindParam($value, $params);
                    $parts[] = "$column=$phName";
                }
            }
        }

        return (count($parts) === 1) ? $parts[0] : ('(' . implode(') AND (', $parts) . ')');
    }
}
