<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Helper;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Helper\DbUuidHelper;

/**
 * @group db
 */
final class DbUuidHelperTest extends TestCase
{
    /**
     * @dataProvider successUuids
     */
    public function testConvert(string $uuid): void
    {
        $blobUuid = DbUuidHelper::uuidToBlob($uuid);
        $this->assertEquals($uuid, DbUuidHelper::toUuid($blobUuid));
    }

    /**
     * @dataProvider incorrectUuids
     */
    public function testToBlobIncorrectUuid(string $uuid): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Incorrect UUID.');

        $blobUuid = DbUuidHelper::uuidToBlob($uuid);
        $this->assertEquals($uuid, DbUuidHelper::toUuid($blobUuid));
    }

    /**
     * @dataProvider blobUuids
     */
    public function testToUuid($blobUuid, $expected): void
    {
        $uuid = DbUuidHelper::toUuid($blobUuid);
        $this->assertEquals($expected, $uuid);
    }

    /**
     * @dataProvider incorrectBlobUuids
     */
    public function testToUuidFailed($blobUuid, $expected): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Length of source data is should be 16 or 32 bytes.');

        $uuid = DbUuidHelper::toUuid($blobUuid);
        $this->assertEquals($expected, $uuid);
    }

    public static function successUuids(): array
    {
        return [
            ['738146be-87b1-49f2-9913-36142fb6fcbe'],
        ];
    }

    public static function incorrectUuids(): array
    {
        return [
            ['738146be-87b149f2-9913-36142fb6fcbe'],
            ['738146be-87b1-K9f2-9913-36142fb6fcbe'],
            ['738146be+87b1-K9f2-9913-36142fb6fcbe'],
        ];
    }

    public static function blobUuids(): array
    {
        return [
            ['738146be-87b1-49f2-9913-36142fb6fcbe', '738146be-87b1-49f2-9913-36142fb6fcbe'],
            ['738146be87b149f2991336142fb6fcbe', '738146be-87b1-49f2-9913-36142fb6fcbe'],
            [hex2bin('738146be87b149f2991336142fb6fcbe'), '738146be-87b1-49f2-9913-36142fb6fcbe'],
        ];
    }

    public static function incorrectBlobUuids(): array
    {
        return [
            ['738146be-87b1-49f2-9913-36142fbfcbe', '738146be-87b1-49f2-9913-36142fb6fcbe'],
            ['738146be87b149f2991336142fb6fcb', '738146be-87b1-49f2-9913-36142fb6fcbe'],
            [hex2bin('738146be87b149f291336142fb6fcb'), '738146be-87b1-49f2-9913-36142fb6fcbe'],
        ];
    }
}
