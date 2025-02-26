<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Data\LazyArrayInterface;
use Yiisoft\Db\Schema\Data\LazyArray;

use function is_string;

/**
 * Abstract expression builder for {@see ArrayExpression}.
 */
abstract class AbstractArrayExpressionBuilder implements ExpressionBuilderInterface
{
    /**
     * @var string The class name of the {@see LazyArrayInterface} object. This constant is used to determine if the
     * value can be used as a raw string. If the value is an instance of this class, the value will be used as a raw
     * string. It is used to convert values between different DBMS.
     *
     * @psalm-var class-string<LazyArrayInterface>
     */
    protected const LAZY_ARRAY_CLASS = LazyArray::class;

    /**
     * Builds a SQL expression for a string value.
     *
     * @param string $value The valid SQL string representation of the array value.
     * @param ArrayExpression $expression The array expression.
     * @param array $params The binding parameters.
     *
     * @return string The SQL expression representing the array value.
     */
    abstract protected function buildStringValue(string $value, ArrayExpression $expression, array &$params): string;

    /**
     * Build an array expression from a sub-query object.
     *
     * @param QueryInterface $query The sub-query object.
     * @param ArrayExpression $expression The array expression.
     * @param array $params The binding parameters.
     *
     * @return string The sub-query SQL expression representing an array.
     */
    abstract protected function buildSubquery(
        QueryInterface $query,
        ArrayExpression $expression,
        array &$params
    ): string;

    /**
     * Builds a SQL expression for an array value.
     *
     * @param iterable $value The array value.
     * @param ArrayExpression $expression The array expression.
     * @param array $params The binding parameters.
     *
     * @return string The SQL expression representing the array value.
     */
    abstract protected function buildValue(iterable $value, ArrayExpression $expression, array &$params): string;

    public function __construct(protected readonly QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * The Method builds the raw SQL from the `$expression` that won't be additionally escaped or quoted.
     *
     * @param ArrayExpression $expression The expression to build.
     * @param array $params The binding parameters.
     *
     * @return string The raw SQL that won't be additionally escaped or quoted.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $value = $expression->getValue();

        if ($value === null) {
            return 'NULL';
        }

        if ($value instanceof LazyArrayInterface) {
            if ($value::class === static::LAZY_ARRAY_CLASS) {
                $value = $value->getRawValue();
            } else {
                $value = $value->getValue();
            }
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
