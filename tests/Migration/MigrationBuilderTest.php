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

        $this->assertSame('bigint', (string) $migrationBuilder->bigInteger());
    }

    public function testBigPrimaryKey(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('bigpk', (string) $migrationBuilder->bigPrimaryKey());
    }

    public function testBinary(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('binary', (string) $migrationBuilder->binary());
    }

    public function testBoolean(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('boolean', (string) $migrationBuilder->boolean());
    }

    public function testChar(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('char', (string) $migrationBuilder->char());
    }

    public function testDate(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('date', (string) $migrationBuilder->date());
    }

    public function testDateTime(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('datetime', (string) $migrationBuilder->dateTime());
    }

    public function testDecimal(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('decimal', (string) $migrationBuilder->decimal());
    }

    public function testDouble(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('double', (string) $migrationBuilder->double());
    }

    public function testFloat(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('float', (string) $migrationBuilder->float());
    }

    public function testInteger(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('integer', (string) $migrationBuilder->integer());
    }

    public function testJson(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('json', (string) $migrationBuilder->json());
    }

    public function testMoney(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('money', (string) $migrationBuilder->money());
    }

    public function testPrimaryKey(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('pk', (string) $migrationBuilder->primaryKey());
    }

    public function testSmallInteger(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('smallint', (string) $migrationBuilder->smallInteger());
    }

    public function testString(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('string', (string) $migrationBuilder->string());
    }

    public function testText(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('text', (string) $migrationBuilder->text());
    }

    public function testTime(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('time', (string) $migrationBuilder->time());
    }

    public function testTimestamp(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('timestamp', (string) $migrationBuilder->timestamp());
    }

    public function testTinyInteger(): void
    {
        $db = $this->getConnection();

        $migrationBuilder = $db->getMigrationBuilder();

        $this->assertSame('tinyint', (string) $migrationBuilder->tinyInteger());
    }
}
