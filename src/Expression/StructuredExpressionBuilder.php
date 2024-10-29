<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use JsonSerializable;
use Traversable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;
use Yiisoft\Db\Schema\Data\LazyArrayInterface;
use Yiisoft\Db\Schema\Data\LazyArrayJson;

use function array_key_exists;
use function array_keys;
use function get_object_vars;
use function is_object;
use function iterator_to_array;
use function json_encode;

/**
 * Default expression builder for {@see StructuredExpression}. Builds an expression as a JSON.
 */
class StructuredExpressionBuilder implements ExpressionBuilderInterface
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
     * @param StructuredExpression $expression The expression to build.
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

        $columns = $expression->getColumns();
        $value = $this->prepareValues($value, $columns);

        return $this->buildValue($value, $expression, $params);
    }

    /**
     * Build a structured expression from a sub-query object.
     *
     * @param QueryInterface $query The sub-query object.
     * @param StructuredExpression $expression The structured expression.
     * @param array $params The binding parameters.
     *
     * @return string The sub-query array expression.
     */
    protected function buildSubquery(
        QueryInterface $query,
        StructuredExpression $expression,
        array &$params
    ): string {
        throw new NotSupportedException('Sub-query for structured expression is not supported by this query builder.');
    }

    protected function buildValue(array $value, StructuredExpression $expression, array &$params): string
    {
        return $this->queryBuilder->bindParam(json_encode($value, JSON_THROW_ON_ERROR), $params);
    }

    /**
     * Returns the prepared value of the structured type, where:
     * - object are converted to an array;
     * - array elements are sorted according to the order of structured type columns;
     * - indexed keys are replaced with column names;
     * - missing elements are filled in with default values;
     * - excessive elements are ignored.
     *
     * If the structured type columns are not specified it will only convert the object to an array.
     *
     * @param array|object $value The structured type value.
     * @param ColumnSchemaInterface[] $columns The structured type columns.
     *
     * @psalm-param array<string, ColumnSchemaInterface> $columns
     */
    protected function prepareValues(array|object $value, array $columns): array
    {
        if (is_object($value)) {
            if ($value instanceof JsonSerializable) {
                /** @var array */
                $value = $value->jsonSerialize();
            } elseif ($value instanceof Traversable) {
                $value = iterator_to_array($value);
            } else {
                $value = get_object_vars($value);
            }
        }

        if (empty($columns)) {
            return $value;
        }

        $prepared = [];
        $columnNames = array_keys($columns);

        foreach ($columnNames as $i => $columnName) {
            $prepared[$columnName] = match (true) {
                array_key_exists($columnName, $value) => $value[$columnName],
                array_key_exists($i, $value) => $value[$i],
                default => $columns[$columnName]->getDefaultValue(),
            };
        }

        return $prepared;
    }
}
