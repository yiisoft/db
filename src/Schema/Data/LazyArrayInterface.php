<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Data;

/**
 * The interface for classes that represent an array with lazy parsing of data retrieved from the database.
 */
interface LazyArrayInterface
{
    /**
     * The raw value that can be represented as:
     * - a string retrieved value from the database that can be parsed into an array;
     * - an array of values if the value is already parsed.
     */
    public function getRawValue(): array|string;

    /**
     * Returns parsed and typecasted value.
     */
    public function getValue(): array;
}
