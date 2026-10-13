<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value;

use InvalidArgumentException;
use Stringable;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Helper\DbUuidHelper;

use function strtolower;

/**
 * Represents a UUID value that should be stored in a DBMS-independent way.
 *
 * Different DBMS expect different representations of the same UUID: MySQL, MariaDB, SQLite and Oracle store it as 16
 * raw bytes, while PostgreSQL and MSSQL expect the canonical string form. Wrapping the value removes the need to know
 * which one the current connection requires:
 *
 * ```php
 * $db->createCommand()->insert('{{%page}}', [
 *     'id' => new UuidValue(Uuid::uuid7()),
 * ])->execute();
 * ```
 *
 * The value is normalized to the canonical lowercase form on construction, so all of the following are equivalent:
 *
 * ```php
 * new UuidValue('738146be-87b1-49f2-9913-36142fb6fcbe');
 * new UuidValue('738146be87b149f2991336142fb6fcbe');
 * new UuidValue(hex2bin('738146be87b149f2991336142fb6fcbe'));
 * ```
 */
final class UuidValue implements ExpressionInterface
{
    /**
     * The UUID in the canonical lowercase form, for example `738146be-87b1-49f2-9913-36142fb6fcbe`.
     */
    public readonly string $value;

    /**
     * @param string|Stringable $value The UUID to represent. It can be:
     * - a UUID in the canonical form, for example `738146be-87b1-49f2-9913-36142fb6fcbe`;
     * - a UUID as 32 hexadecimal characters without dashes, for example `738146be87b149f2991336142fb6fcbe`;
     * - a UUID as 16 raw bytes, for example the result of `Ramsey\Uuid\UuidInterface::getBytes()`;
     * - any {@see Stringable} instance that returns one of the above, for example `Ramsey\Uuid\UuidInterface` itself.
     *
     * @throws InvalidArgumentException If the value isn't a valid UUID.
     */
    public function __construct(string|Stringable $value)
    {
        $this->value = strtolower(DbUuidHelper::toUuid((string) $value));
    }
}
