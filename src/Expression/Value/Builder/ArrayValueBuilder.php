<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value\Builder;

use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Expression\Value\ArrayValue;
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
 * Default expression builder for {@see ArrayValue}. Builds an expression as a JSON.
 */
final class ArrayValueBuilder extends AbstractArrayValueBuilder
{
    protected function buildStringValue(string $value, ArrayValue $expression, array &$params): string
    {
        $param = new Param($value, DataType::STRING);

        return $this->queryBuilder->bindParam($param, $params);
    }

    protected function buildSubquery(QueryInterface $query, ArrayValue $expression, array &$params): string
    {
        [$sql, $params] = $this->queryBuilder->build($query, $params);

        return "($sql)";
    }

    protected function buildValue(iterable $value, ArrayValue $expression, array &$params): string
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
