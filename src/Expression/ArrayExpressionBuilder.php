<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use Yiisoft\Db\Schema\Data\LazyArrayInterface;
use Yiisoft\Db\Schema\Data\LazyArrayJson;

use function is_array;
use function is_string;
use function iterator_to_array;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * Default expression builder for {@see ArrayExpression}. Builds an expression as a JSON.
 */
class ArrayExpressionBuilder implements ExpressionBuilderInterface
{
    /**
     * The class name of the {@see LazyArrayInterface} object. This constant is used to determine if the value can be
     * used as a raw string. If the value is an instance of this class, the value will be used as a raw string.
     *
     * @var string
     * @psalm-var class-string<LazyArrayInterface>
     */
    protected const LAZY_ARRAY_CLASS = LazyArrayJson::class;

    public function __construct(protected readonly QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * The Method builds the raw SQL from the $expression that won't be additionally escaped or quoted.
     *
     * @param ArrayExpression $expression The expression to build.
     * @param array $params The binding parameters.
     *
     * @return string The raw SQL that won't be additionally escaped or quoted.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $value = $expression->getValue();

        if ($value instanceof LazyArrayInterface) {
            if ($value instanceof (static::LAZY_ARRAY_CLASS)) {
                $value = $value->getRawValue();

                if (is_string($value)) {
                    return $value;
                }
            } else {
                $value = $value->getValue();
            }
        }

        if ($value instanceof QueryInterface) {
            return $this->buildSubquery($value, $expression, $params);
        }

        return $this->buildValue($value, $expression, $params);
    }

    /**
     * Build an array expression from a sub-query object.
     *
     * @param QueryInterface $query The sub-query object.
     * @param ArrayExpression $expression The array expression.
     * @param array $params The binding parameters.
     *
     * @return string The sub-query array expression.
     */
    protected function buildSubquery(
        QueryInterface $query,
        ArrayExpression $expression,
        array &$params
    ): string {
        throw new NotSupportedException('Sub-query for array expression is not supported by this query builder.');
    }

    /**
     * Builds a SQL expression for an array value.
     *
     * @param array $params The binding parameters.
     */
    protected function buildValue(mixed $value, ArrayExpression $expression, array &$params): string
    {
        if (!is_array($value)) {
            $value = iterator_to_array($value, false);
        }

        return $this->queryBuilder->bindParam(json_encode($value, JSON_THROW_ON_ERROR), $params);
    }
}
