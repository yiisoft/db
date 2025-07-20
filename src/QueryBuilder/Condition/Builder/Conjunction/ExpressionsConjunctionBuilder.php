<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder\Conjunction;

use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function count;
use function implode;
use function is_array;
use function reset;

/**
 * @internal
 *
 * Build an array of expressions' objects into SQL expressions.
 */
final class ExpressionsConjunctionBuilder
{
    public function __construct(
        private readonly string $operator,
        private readonly QueryBuilderInterface $queryBuilder
    ) {
    }

    /**
     * @param array $expressions The expressions to be built.
     * @param array $params The binding parameters.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @psalm-param array<array|ExpressionInterface|scalar> $expressions
     */
    public function build(array $expressions, array &$params = []): string
    {
        $parts = $this->buildExpressions($expressions, $params);

        if (empty($parts)) {
            return '';
        }

        if (count($parts) === 1) {
            return (string) reset($parts);
        }

        return '(' . implode(") $this->operator (", $parts) . ')';
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @psalm-param array<array|ExpressionInterface|scalar> $expressions
     * @psalm-return list<scalar>
     */
    private function buildExpressions(array $expressions, array &$params = []): array
    {
        $parts = [];

        foreach ($expressions as $conditionValue) {
            if (is_array($conditionValue)) {
                $conditionValue = $this->queryBuilder->buildCondition($conditionValue, $params);
            }

            if ($conditionValue instanceof ExpressionInterface) {
                $conditionValue = $this->queryBuilder->buildExpression($conditionValue, $params);
            }

            if ($conditionValue !== '') {
                $parts[] = $conditionValue;
            }
        }

        return $parts;
    }
}
