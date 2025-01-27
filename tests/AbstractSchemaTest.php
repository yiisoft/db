<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\TestTrait;

use function fclose;
use function fopen;
use function print_r;

abstract class AbstractSchemaTest extends TestCase
{
    use TestTrait;

    public function testGetDefaultSchema(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->assertNull($schema->getDefaultSchema());
    }

    public function testGetDataType(): void
    {
        $values = [
            [null, DataType::NULL],
            ['', DataType::STRING],
            ['hello', DataType::STRING],
            [0, DataType::INTEGER],
            [1, DataType::INTEGER],
            [1337, DataType::INTEGER],
            [true, DataType::BOOLEAN],
            [false, DataType::BOOLEAN],
            [$fp = fopen(__FILE__, 'rb'), DataType::LOB],
        ];

        $db = $this->getConnection();

        $schema = $db->getSchema();

        foreach ($values as $value) {
            $this->assertSame(
                $value[1],
                $schema->getDataType($value[0]),
                'type for value ' . print_r($value[0], true) . ' does not match.',
            );
        }

        fclose($fp);
    }

    public function testRefresh(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $schema->refresh();

        $this->assertSame([], Assert::getInaccessibleProperty($schema, 'tableMetadata'));
        $this->assertSame([], Assert::getInaccessibleProperty($schema, 'tableNames'));
    }
}
