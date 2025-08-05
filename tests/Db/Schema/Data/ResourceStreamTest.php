<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema\Data;

use LogicException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constant\GettypeResult;
use Yiisoft\Db\Schema\Data\StringableStream;

use function fclose;
use function fopen;
use function gettype;
use function serialize;
use function unserialize;

/**
 * @group db
 */
final class ResourceStreamTest extends TestCase
{
    public function testConstruct(): void
    {
        $resource = fopen(__DIR__ . '/../../../Support/string.txt', 'rb');
        $stringableSteam = new StringableStream($resource);

        $this->assertSame($resource, $stringableSteam->getValue());
    }

    public function testDestruct(): void
    {
        $resource = fopen(__DIR__ . '/../../../Support/string.txt', 'rb');
        $stringableSteam = new StringableStream($resource);

        $this->assertSame(GettypeResult::RESOURCE, gettype($resource));

        unset($stringableSteam);

        $this->assertSame(GettypeResult::RESOURCE_CLOSED, gettype($resource));
    }

    public function testSerialize(): void
    {
        $resource = fopen(__DIR__ . '/../../../Support/string.txt', 'rb');
        $stringableSteam = new StringableStream($resource);
        $serialized = serialize($stringableSteam);

        $this->assertSame('O:39:"Yiisoft\Db\Schema\Data\StringableStream":1:{s:5:"value";s:6:"string";}', $serialized);
        $this->assertEquals($stringableSteam, unserialize($serialized));
    }

    public function testToString(): void
    {
        $resource = fopen(__DIR__ . '/../../../Support/string.txt', 'rb');
        $stringableSteam = new StringableStream($resource);

        // Can be read twice and more
        $this->assertSame('string', (string) $stringableSteam);
        $this->assertSame('string', (string) $stringableSteam);
    }

    public function testToStringClosedResource(): void
    {
        $resource = fopen(__DIR__ . '/../../../Support/string.txt', 'rb');
        $stringableSteam = new StringableStream($resource);

        fclose($resource);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Resource is closed.');

        (string) $stringableSteam;
    }

    public function testGetValue(): void
    {
        $resource = fopen(__DIR__ . '/../../../Support/string.txt', 'rb');
        $stringableSteam = new StringableStream($resource);

        $this->assertSame($resource, $stringableSteam->getValue());
        $this->assertSame('string', (string) $stringableSteam);
        $this->assertSame('string', $stringableSteam->getValue());
    }
}
