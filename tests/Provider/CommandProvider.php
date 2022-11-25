<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Tests\Support\TestTrait;

final class CommandProvider
{
    use TestTrait;

    public function addForeignKey(): array
    {
        $baseCommandProvider = new BaseCommandProvider();

        return $baseCommandProvider->addForeignKey();
    }

    public function addForeignKeySql(): array
    {
        $baseCommandProvider = new BaseCommandProvider();

        return $baseCommandProvider->addForeignKeySql($this->getConnection());
    }

    public function addPrimaryKey(): array
    {
        $baseCommandProvider = new BaseCommandProvider();

        return $baseCommandProvider->addPrimaryKey();
    }

    public function addPrimaryKeySql(): array
    {
        $baseCommandProvider = new BaseCommandProvider();

        return $baseCommandProvider->addPrimaryKeySql($this->getConnection());
    }

    public function addunique(): array
    {
        $baseCommandProvider = new BaseCommandProvider();

        return $baseCommandProvider->addUnique();
    }

    public function adduniqueSql(): array
    {
        $baseCommandProvider = new BaseCommandProvider();

        return $baseCommandProvider->addUniqueSql($this->getConnection());
    }

    public function batchInsert(): array
    {
        $baseCommandProvider = new BaseCommandProvider();

        return $baseCommandProvider->batchInsert($this->getConnection());
    }

    public function createIndex(): array
    {
        $baseCommandProvider = new BaseCommandProvider();

        return $baseCommandProvider->createIndex();
    }

    public function createIndexSql(): array
    {
        $baseCommandProvider = new BaseCommandProvider();

        return $baseCommandProvider->createIndexSql($this->getConnection());
    }

    public function invalidSelectColumns(): array
    {
        $baseCommandProvider = new BaseCommandProvider();

        return $baseCommandProvider->invalidSelectColumns();
    }

    public function rawSql(): array
    {
        $commandProvider = new BaseCommandProvider();

        return $commandProvider->rawSql();
    }

    public function update(): array
    {
        $commandProvider = new BaseCommandProvider();

        return $commandProvider->update($this->getConnection());
    }

    public function upsert(): array
    {
        $commandProvider = new BaseCommandProvider();

        return $commandProvider->upsert($this->getConnection());
    }
}
