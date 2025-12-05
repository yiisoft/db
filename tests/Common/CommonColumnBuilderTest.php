<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Tests\Provider\ColumnBuilderProvider;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

use function array_merge;

abstract class CommonColumnBuilderTest extends IntegrationTestCase
{
    #[DataProviderExternal(ColumnBuilderProvider::class, 'buildingMethods')]
    public function testBuildingMethods(
        string $buildingMethod,
        array $args,
        string $expectedInstanceOf,
        string $expectedType,
        array $expectedMethodResults = [],
    ): void {
        $db = $this->getSharedConnection();
        $columnBuilderClass = $db->getColumnBuilderClass();

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
