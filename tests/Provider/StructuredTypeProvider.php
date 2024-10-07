<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use ArrayIterator;
use Yiisoft\Db\Schema\Column\ColumnBuilder;
use Yiisoft\Db\Tests\Support\TraversableObject;

class StructuredTypeProvider
{
    public static function normolizedValues()
    {
        $price5UsdColumns = [
            'value' => ColumnBuilder::money(10, 2)->defaultValue(5.0),
            'currency_code' => ColumnBuilder::char(3)->defaultValue('USD'),
        ];

        return [
            'Sort according to `$columns` order' => [
                ['currency_code' => 'USD', 'value' => 10.0],
                ['value' => 10.0, 'currency_code' => 'USD'],
                $price5UsdColumns,
            ],
            'Remove excessive elements' => [
                ['value' => 10.0, 'currency_code' => 'USD', 'excessive' => 'element'],
                ['value' => 10.0, 'currency_code' => 'USD'],
                $price5UsdColumns,
            ],
            'Fill default values for skipped fields' => [
                ['currency_code' => 'CNY'],
                ['value' => 5.0, 'currency_code' => 'CNY'],
                $price5UsdColumns,
            ],
            'Fill default values and column names for skipped indexed fields' => [
                [10.0],
                ['value' => 10.0, 'currency_code' => 'USD'],
                $price5UsdColumns,
            ],
            'Fill default values and column names for iterable object' => [
                new TraversableObject([10.0]),
                ['value' => 10.0, 'currency_code' => 'USD'],
                $price5UsdColumns,
            ],
            'Fill default values for iterable object' => [
                new ArrayIterator(['currency_code' => 'CNY']),
                ['value' => 5.0, 'currency_code' => 'CNY'],
                $price5UsdColumns,
            ],
            'Fill default values for empty array' => [
                [],
                ['value' => 5.0, 'currency_code' => 'USD'],
                $price5UsdColumns,
            ],
            'Do not normalize scalar values' => [
                1,
                1,
                $price5UsdColumns,
            ],
            'Do not normalize with empty columns' => [
                [10.0],
                [10.0],
                [],
            ],
        ];
    }
}
