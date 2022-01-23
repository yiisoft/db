<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema;

interface SchemaInterface
{
    public const SCHEMA = 'schema';
    public const PRIMARY_KEY = 'primaryKey';
    public const INDEXES = 'indexes';
    public const CHECKS = 'checks';
    public const FOREIGN_KEYS = 'foreignKeys';
    public const DEFAULT_VALUES = 'defaultValues';
    public const UNIQUES = 'uniques';
}
