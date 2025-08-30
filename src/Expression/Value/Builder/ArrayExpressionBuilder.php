<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value\Builder;

use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\Value\ArrayExpression;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Data\JsonLazyArray;
use Yiisoft\Db\Schema\Data\LazyArray;
use Yiisoft\Db\Schema\Data\LazyArrayInterface;
use Yiisoft\Db\Schema\Data\StructuredLazyArray;

use function is_array;
use function iterator_to_array;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * Default expression builder for {@see ArrayExpression}. Builds an expression as a JSON.
 */
final class ArrayExpressionBuilder extends AbstractArrayExpressionBuilder
{
    protected function buildStringValue(string $value, ArrayExpression $expression, array &$params): string
    {
        $param = new Param($value, DataType::STRING);

        return $this->queryBuilder->bindParam($param, $params);
    }

    protected function buildSubquery(QueryInterface $query, ArrayExpression $expression, array &$params): string
    {
        [$sql, $params] = $this->queryBuilder->build($query, $params);

        return "($sql)";
    }

    protected function buildValue(iterable $value, ArrayExpression $expression, array &$params): string
    {
        if (!is_array($value)) {
            $value = iterator_to_array($value, false);
        }

        return $this->buildStringValue(json_encode($value, JSON_THROW_ON_ERROR), $expression, $params);
    }

    protected function getLazyArrayValue(LazyArrayInterface $value): array|string
    {
        return match ($value::class) {
            LazyArray::class, JsonLazyArray::class, StructuredLazyArray::class => $value->getRawValue(),
            default => $value->getValue(),
        };
    }
}
