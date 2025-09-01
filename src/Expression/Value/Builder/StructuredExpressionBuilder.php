<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value\Builder;

use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\Value\StructuredExpression;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Data\JsonLazyArray;
use Yiisoft\Db\Schema\Data\LazyArray;
use Yiisoft\Db\Schema\Data\LazyArrayInterface;
use Yiisoft\Db\Schema\Data\StructuredLazyArray;

use function array_values;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * Default expression builder for {@see StructuredExpression}. Builds an expression as a JSON.
 */
final class StructuredExpressionBuilder extends AbstractStructuredExpressionBuilder
{
    protected function buildStringValue(string $value, StructuredExpression $expression, array &$params): string
    {
        $param = new Param($value, DataType::STRING);

        return $this->queryBuilder->bindParam($param, $params);
    }

    protected function buildSubquery(QueryInterface $query, StructuredExpression $expression, array &$params): string
    {
        [$sql, $params] = $this->queryBuilder->build($query, $params);

        return "($sql)";
    }

    protected function buildValue(array|object $value, StructuredExpression $expression, array &$params): string
    {
        $value = $this->prepareValues($value, $expression);
        $param = new Param(json_encode(array_values($value), JSON_THROW_ON_ERROR), DataType::STRING);

        return $this->queryBuilder->bindParam($param, $params);
    }

    protected function getLazyArrayValue(LazyArrayInterface $value): array|string
    {
        return match ($value::class) {
            LazyArray::class, JsonLazyArray::class, StructuredLazyArray::class => $value->getRawValue(),
            default => $value->getValue(),
        };
    }
}
