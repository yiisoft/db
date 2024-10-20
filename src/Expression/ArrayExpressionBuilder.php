<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use JsonException;
use Traversable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

use function array_map;
use function is_array;
use function is_iterable;
use function is_string;
use function iterator_to_array;
use function json_encode;

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
     * @throws JsonException
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string The raw SQL that won't be additionally escaped or quoted.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $value = $expression->getValue();

        if (is_iterable($value)) {
            $value = $this->dbTypecast($value, $expression);

            return $this->queryBuilder->bindParam(json_encode($value, JSON_THROW_ON_ERROR), $params);
        }

        if (is_string($value)) {
            return $value;
        }

        return $this->queryBuilder->buildExpression($value, $params);
    }

    protected function dbTypecast(iterable $value, ArrayExpression $expression): array
    {
        $column = $expression->getColumn();

        if ($column === null) {
            if (is_array($value)) {
                return $value;
            }

            return iterator_to_array($value, false);
        }

        $dimension = $expression->getDimension();

        if (is_array($value) && $dimension === 1) {
            return array_map($column->dbTypecast(...), $value);
        }

        /** @var array */
        return $this->dbTypecastArray($value, $dimension, $column);
    }

    /**
     * Recursively converts array values for use in a db query.
     *
     * @param mixed $value The array or iterable object.
     * @param int $dimension The array dimension. Should be more than 0.
     * @param ColumnSchemaInterface $column The column schema to typecast values.
     *
     * @psalm-param positive-int $dimension
     *
     * @return array|null Converted values.
     */
    protected function dbTypecastArray(mixed $value, int $dimension, ColumnSchemaInterface $column): array|null
    {
        if ($value === null) {
            return null;
        }

        if (!is_iterable($value)) {
            return [];
        }

        if ($dimension <= 1) {
            return array_map(
                $column->dbTypecast(...),
                is_array($value)
                    ? $value
                    : iterator_to_array($value, false)
            );
        }

        $items = [];

        foreach ($value as $val) {
            $items[] = $this->dbTypecastArray($val, $dimension - 1, $column);
        }

        return $items;
    }
}
