<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Migration;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class MigrationBuilderTest extends TestCase
{
    use TestTrait;

    public function testBigInteger(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('bigint', $migrationBuilder->bigInteger()->asString());
    }

    public function testBigPrimaryKey(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('bigpk', $migrationBuilder->bigPrimaryKey()->asString());
    }

    public function testBinary(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('binary', $migrationBuilder->binary()->asString());
    }

    public function testBoolean(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('boolean', $migrationBuilder->boolean()->asString());
    }

    public function testChar(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('char', $migrationBuilder->char()->asString());
    }

    public function testDate(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('date', $migrationBuilder->date()->asString());
    }

    public function testDateTime(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('datetime', $migrationBuilder->dateTime()->asString());
    }

    public function testDecimal(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('decimal', $migrationBuilder->decimal()->asString());
    }

    public function testDouble(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('double', $migrationBuilder->double()->asString());
    }

    public function testFloat(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('float', $migrationBuilder->float()->asString());
    }

    public function testInteger(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('integer', $migrationBuilder->integer()->asString());
    }

    public function testJson(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('json', $migrationBuilder->json()->asString());
    }

    public function testMoney(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('money', $migrationBuilder->money()->asString());
    }

    public function testPrimaryKey(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('pk', $migrationBuilder->primaryKey()->asString());
    }

    public function testSmallInteger(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('smallint', $migrationBuilder->smallInteger()->asString());
    }

    public function testString(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('string', $migrationBuilder->string()->asString());
    }

    public function testText(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('text', $migrationBuilder->text()->asString());
    }

    public function testTime(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('time', $migrationBuilder->time()->asString());
    }

    public function testTimestamp(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('timestamp', $migrationBuilder->timestamp()->asString());
    }

    public function testTinyInteger(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('tinyint', $migrationBuilder->tinyInteger()->asString());
    }
}
