<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use JsonSerializable;
use Traversable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

use function array_key_exists;
use function array_keys;
use function get_object_vars;
use function is_object;
use function iterator_to_array;
use function json_encode;

/**
 * Default expression builder for {@see StructuredExpression}. Builds an expression as a JSON.
 */
final class StructuredExpressionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private readonly QueryBuilderInterface $queryBuilder)
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

        if (is_string($value)) {
            return $value;
        }

        if ($value instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($value, $params);
        }

        $columns = $expression->getColumns();
        $value = $this->prepareValues($value, $columns);

        return $this->queryBuilder->bindParam(json_encode($value, JSON_THROW_ON_ERROR), $params);
    }

    /**
     * Returns the prepared value of the structured type, where:
     * - object are converted to an array;
     * - array elements are sorted according to the order of structured type columns;
     * - indexed keys are replaced with column names;
     * - missing elements are filled in with default values;
     * - excessive elements are ignored;
     * - values are type-casted according to the column types.
     *
     * If the structured type columns are not specified it will only convert the object to an array.
     *
     * @param array|object $value The structured type value.
     * @param ColumnSchemaInterface[] $columns The structured type columns.
     * @param array $params The binding parameters.
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
            $item = match (true) {
                array_key_exists($columnName, $value) => $value[$columnName],
                array_key_exists($i, $value) => $value[$i],
                default => $columns[$columnName]->getDefaultValue(),
            };

            $prepared[$columnName] = $columns[$columnName]->dbTypecast($item);
        }

        return $prepared;
    }
}
