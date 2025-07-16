<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use JsonException;
use JsonSerializable;
use Traversable;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Data\JsonLazyArray;
use Yiisoft\Db\Schema\Data\LazyArray;
use Yiisoft\Db\Schema\Data\LazyArrayInterface;
use Yiisoft\Db\Schema\Data\StructuredLazyArray;

use function is_string;
use function iterator_to_array;
use function json_encode;
use function strlen;

use const JSON_THROW_ON_ERROR;

/**
 * Builds expressions for {@see JsonExpression}.
 *
 * @implements ExpressionBuilderInterface<JsonExpression>
 */
final class JsonExpressionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private readonly QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * The method builds the raw SQL from the `$expression` that won't be additionally escaped or quoted.
     *
     * @param JsonExpression $expression The expression to build.
     * @param array $params The binding parameters.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws JsonException
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
            $value = match ($value::class) {
                LazyArray::class, JsonLazyArray::class, StructuredLazyArray::class => $value->getRawValue(),
                default => $value->getValue(),
            };
        }

        if (is_string($value) && strlen($value) > 1
            && ($value[0] === '{' && $value[-1] === '}' || $value[0] === '[' && $value[-1] === ']')
        ) {
            return $this->buildStringValue($value, $params);
        }

        if ($value instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($value, $params);
        }

        return $this->buildValue($value, $params);
    }

    /**
     * Builds a SQL expression for a string value.
     */
    protected function buildStringValue(string $value, array &$params): string
    {
        $param = new Param($value, DataType::STRING);

        return $this->queryBuilder->bindParam($param, $params);
    }

    /**
     * Builds a SQL expression for an array value.
     *
     * @param array $params The binding parameters.
     */
    protected function buildValue(mixed $value, array &$params): string
    {
        if ($value instanceof Traversable && !$value instanceof JsonSerializable) {
            $value = iterator_to_array($value);
        }

        return $this->buildStringValue(json_encode($value, JSON_THROW_ON_ERROR), $params);
    }
}
