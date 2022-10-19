<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;

use function array_shift;
use function call_user_func_array;

abstract class AbstractColumnSchemaBuilderTest extends TestCase
{
    public function getColumnSchemaBuilder(string $type, int|null $length = null): ColumnSchemaBuilder
    {
        return new ColumnSchemaBuilder($type, $length);
    }

    public function checkBuildString(string $expected, string $type, int|null $length, $calls): void
    {
        $builder = $this->getColumnSchemaBuilder($type, $length);

        foreach ($calls as $call) {
            $method = array_shift($call);
            call_user_func_array([$builder, $method], $call);
        }

        $this->assertEquals($expected, $builder->__toString());
    }
}
