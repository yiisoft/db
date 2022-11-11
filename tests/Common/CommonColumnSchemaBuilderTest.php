<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Tests\Support\TestTrait;

use function array_shift;
use function call_user_func_array;

/**
 * @group mssql
 * @group mysql
 * @group pgsql
 * @group oracle
 * @group sqlite
 */
abstract class CommonColumnSchemaBuilderTest extends TestCase
{
    use TestTrait;

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
    }
}
