<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Tests\Provider\ColumnBuilderProvider;
use Yiisoft\Db\Tests\Support\TestTrait;

use function array_merge;

abstract class AbstractColumnBuilderTest extends TestCase
{
    use TestTrait;

    #[DataProviderExternal(ColumnBuilderProvider::class, 'buildingMethods')]
    public function testBuildingMethods(
        string $buildingMethod,
        array $args,
        string $expectedInstanceOf,
        string $expectedType,
        array $expectedMethodResults = [],
    ): void {
        $columnBuilderClass = $this->getConnection()->getColumnBuilderClass();

        $column = $columnBuilderClass::$buildingMethod(...$args);

        $this->assertInstanceOf($expectedInstanceOf, $column);
        $this->assertSame($expectedType, $column->getType());

        $columnMethodResults = array_merge(
            ColumnBuilderProvider::DEFAULT_COLUMN_METHOD_RESULTS,
            $expectedMethodResults,
        );

        foreach ($columnMethodResults as $method => $result) {
            $this->assertSame($result, $column->$method());
        }
    }
}
