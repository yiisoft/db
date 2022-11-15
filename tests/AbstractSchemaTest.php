<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Schema\ColumnSchema;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Tests\Support\TestTrait;

use function fclose;
use function fopen;
use function print_r;

abstract class AbstractSchemaTest extends TestCase
{
    use TestTrait;

    public function testColumnSchemaDbTypecastWithEmptyCharType(): void
    {
        $columnSchema = new ColumnSchema();
        $columnSchema->setType(Schema::TYPE_CHAR);

        $this->assertSame('', $columnSchema->dbTypecast(''));
    }

    public function testGetDefaultSchema(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->assertNull($schema->getDefaultSchema());
    }

    public function testGetPDOType(): void
    {
        $values = [
            [null, PDO::PARAM_NULL],
            ['', PDO::PARAM_STR],
            ['hello', PDO::PARAM_STR],
            [0, PDO::PARAM_INT],
            [1, PDO::PARAM_INT],
            [1337, PDO::PARAM_INT],
            [true, PDO::PARAM_BOOL],
            [false, PDO::PARAM_BOOL],
            [$fp = fopen(__FILE__, 'rb'), PDO::PARAM_LOB],
        ];

        $db = $this->getConnection();

        $schema = $db->getSchema();

        foreach ($values as $value) {
            $this->assertSame(
                $value[1],
                $schema->getPdoType($value[0]),
                'type for value ' . print_r($value[0], true) . ' does not match.',
            );
        }

        fclose($fp);
    }
}
