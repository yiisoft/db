<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Helper\DbArrayHelper;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Column\AbstractStructuredColumn;
use Yiisoft\Db\Schema\Data\LazyArrayInterface;
use Yiisoft\Db\Schema\Data\StructuredLazyArray;

use function array_key_exists;
use function array_keys;
use function is_string;

/**
 * Abstract expression builder for {@see StructuredExpression}.
 */
abstract class AbstractStructuredExpressionBuilder implements ExpressionBuilderInterface
{
    /**
     * The class name of the {@see LazyArrayInterface} object. This constant is used to determine if the value can be
     * used as a raw string. If the value is an instance of this class, the value will be used as a raw string.
     *
     * @var string
     * @psalm-var class-string<LazyArrayInterface>
     */
    protected const LAZY_ARRAY_CLASS = StructuredLazyArray::class;

    /**
     * Builds a SQL expression for a string value.
     *
     * @param string $value The valid SQL string representation of the structured value.
     * @param StructuredExpression $expression The structured expression.
     * @param array $params The binding parameters.
     *
     * @return string The SQL expression representing the structured value.
     */
    abstract protected function buildStringValue(
        string $value,
        StructuredExpression $expression,
        array &$params
    ): string;

    /**
     * Build a structured expression from a sub-query object.
     *
     * @param QueryInterface $query The sub-query object.
     * @param StructuredExpression $expression The structured expression.
     * @param array $params The binding parameters.
     *
     * @return string The sub-query SQL expression representing a structured value.
     */
    abstract protected function buildSubquery(
        QueryInterface $query,
        StructuredExpression $expression,
        array &$params
    ): string;

    /**
     * Builds a SQL expression for a structured value.
     *
     * @param array|object $value The structured value.
     * @param StructuredExpression $expression The structured expression.
     * @param array $params The binding parameters.
     *
     * @return string The SQL expression representing the structured value.
     */
    abstract protected function buildValue(
        array|object $value,
        StructuredExpression $expression,
        array &$params
    ): string;

    public function __construct(protected readonly QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * The method builds the raw SQL from the `$expression` that won't be additionally escaped or quoted.
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
     * @param StructuredExpression $expression The structured expression.
     */
    protected function prepareValues(array|object $value, StructuredExpression $expression): array
    {
        $value = DbArrayHelper::toArray($value);

        $type = $expression->getType();
        $columns = $type instanceof AbstractStructuredColumn ? $type->getColumns() : [];

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
