<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Schema\ColumnSchema;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\TestTrait;

use function fclose;
use function fopen;
use function print_r;

abstract class AbstractSchemaTest extends TestCase
{
    use TestTrait;

    public function testCreateColumnSchemaBuilder(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $columnSchemaBuilder = $schema->createColumnSchemaBuilder('string');

        $this->assertInstanceOf(ColumnSchemaBuilder::class, $columnSchemaBuilder);
        $this->assertSame('string', $columnSchemaBuilder->getType());

        $db->close();
    }

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

        $db->close();
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

        $db->close();
    }

    public function testIsReadQuery(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();

        $this->assertTrue($schema->isReadQuery('SELECT * FROM tbl'));
        $this->assertTrue($schema->isReadQuery('SELECT * FROM tbl WHERE id=1'));
        $this->assertTrue($schema->isReadQuery('SELECT * FROM tbl WHERE id=1 LIMIT 1'));
        $this->assertTrue($schema->isReadQuery('SELECT * FROM tbl WHERE id=1 LIMIT 1 OFFSET 1'));

        $db->close();
    }

    public function testRefresh(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $schema->refresh();

        $this->assertSame([], Assert::getInaccessibleProperty($schema, 'tableMetadata'));
        $this->assertSame([], Assert::getInaccessibleProperty($schema, 'tableNames'));

        $db->close();
    }
}
