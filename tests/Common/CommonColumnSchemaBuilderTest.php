<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Tests\Support\TestTrait;

use function array_shift;
use function call_user_func_array;

abstract class CommonColumnSchemaBuilderTest extends TestCase
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\ColumnSchemaBuilderProvider::types();
     */
    public function testCustomTypes(string $expected, string $type, int|null $length, array $calls): void
    {
        $this->checkBuildString($expected, $type, $length, $calls);
    }

    protected function checkBuildString(string $expected, string $type, int|null $length, array $calls): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $builder = $schema->createColumnSchemaBuilder($type, $length);

        foreach ($calls as $call) {
            $method = array_shift($call);
            call_user_func_array([$builder, $method], $call);
        }

        $this->assertSame($expected, $builder->__toString());

        $db->close();
    }
}
