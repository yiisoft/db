<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value\Builder;

use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\Value\ArrayValue;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Data\LazyArrayInterface;

use function is_string;

/**
 * Abstract expression builder for {@see ArrayValue}.
 *
 * @implements ExpressionBuilderInterface<ArrayValue>
 */
abstract class AbstractArrayValueBuilder implements ExpressionBuilderInterface
{
    /**
     * Builds an SQL expression for a string value.
     *
     * @param string $value The valid SQL string representation of the array value.
     * @param ArrayValue $expression The array expression.
     * @param array $params The binding parameters.
     *
     * @return string The SQL expression representing the array value.
     */
    abstract protected function buildStringValue(string $value, ArrayValue $expression, array &$params): string;

    /**
     * Build an array expression from a sub-query object.
     *
     * @param QueryInterface $query The sub-query object.
     * @param ArrayValue $expression The array expression.
     * @param array $params The binding parameters.
     *
     * @return string The sub-query SQL expression representing an array.
     */
    abstract protected function buildSubquery(
        QueryInterface $query,
        ArrayValue $expression,
        array &$params
    ): string;

    /**
     * Builds a SQL expression for an array value.
     *
     * @param iterable $value The array value.
     * @param ArrayValue $expression The array expression.
     * @param array $params The binding parameters.
     *
     * @return string The SQL expression representing the array value.
     */
    abstract protected function buildValue(iterable $value, ArrayValue $expression, array &$params): string;

    /**
     * Returns the value of the lazy array as an array or a raw string depending on the implementation.
     *
     * @param LazyArrayInterface $value The lazy array value.
     *
     * @return array|string The value of the lazy array.
     */
    abstract protected function getLazyArrayValue(LazyArrayInterface $value): array|string;

    public function __construct(protected readonly QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * The Method builds the raw SQL from the `$expression` that won't be additionally escaped or quoted.
     *
     * @param ArrayValue $expression The expression to build.
     * @param array $params The binding parameters.
     *
     * @return string The raw SQL that won't be additionally escaped or quoted.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $value = $expression->value;

        if ($value === null) {
            return 'NULL';
        }

        if ($value instanceof LazyArrayInterface) {
            $value = $this->getLazyArrayValue($value);
        }

        if (is_string($value)) {
            return $this->buildStringValue($value, $expression, $params);
        }

        if ($value instanceof QueryInterface) {
            return $this->buildSubquery($value, $expression, $params);
        }

        return $this->buildValue($value, $expression, $params);
    }
}
