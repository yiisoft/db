<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Command;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Tests\AbstractCommandTest;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class CommandTest extends AbstractCommandTest
{
    use TestTrait;

    public function testAddDefaultValue(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\DDLQueryBuilder does not support adding default value constraints.'
        );

        $command->addDefaultValue('name', 'table', 'column', 'value');
    }

    public function testBatchInsert(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Schema::loadTableSchema() is not supported by core-db.'
        );

        $command->batchInsert('table', ['column1', 'column2'], [['value1', 'value2'], ['value3', 'value4']]);
    }

    public function testCheckIntegrity(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\DDLQueryBuilder does not support enabling/disabling integrity check.'
        );

        $command->checkIntegrity('schema', 'table')->execute();
    }

    public function testCreateTable(): void
    {
        $this->db = $this->getConnectionWithData();

        $command = $this->db->createCommand();

        $expected = <<<SQL
        CREATE TABLE `test_table` (
        \t`id` pk,
        \t`name` string(255) NOT NULL,
        \t`email` string(255) NOT NULL,
        \t`address` string(255) NOT NULL,
        \t`status` integer NOT NULL,
        \t`profile_id` integer NOT NULL,
        \t`created_at` timestamp NOT NULL,
        \t`updated_at` timestamp NOT NULL
        ) CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB
        SQL;
        $columns = [
            'id' => $this->primaryKey(5),
            'name' => $this->string(255)->notNull(),
            'email' => $this->string(255)->notNull(),
            'address' => $this->string(255)->notNull(),
            'status' => $this->integer()->notNull(),
            'profile_id' => $this->integer()->notNull(),
            'created_at' => $this->timestamp()->notNull(),
            'updated_at' => $this->timestamp()->notNull(),
        ];
        $options = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        $sql = $command->createTable('test_table', $columns, $options)->getSql();

        Assert::equalsWithoutLE($expected, $sql);
    }

    public function testDropDefaultValue(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\DDLQueryBuilder does not support dropping default value constraints.'
        );

        $command->dropDefaultValue('column', 'table');
    }

    public function testExecute(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}} WHERE id=:id
            SQL,
            [
                ':id' => 1,
            ]
        );

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Command does not support internalExecute() by core-db.'
        );

        $command->execute();
    }

    public function testExecuteResetSequence(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\DMLQueryBuilder does not support resetting sequence.'
        );

        $command->executeResetSequence('table');
    }

    public function testInsert(): void
    {
        $db = $this->getConnectionWithData();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Schema::loadTableSchema() is not supported by core-db.'
        );

        $command = $db->createCommand();
        $command
            ->insert('{{customer}}', ['email' => 't1@example.com', 'name' => 'test', 'address' => 'test address'])
            ->execute();
    }

    public function testQuery(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}} WHERE id=:id
            SQL,
            [
                ':id' => 1,
            ]
        );

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Command does not support internalExecute() by core-db.'
        );

        $command->query();
    }

    public function testQueryAll(): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand(
            <<<SQL
            SELECT * FROM {{customer}} WHERE id=:id
            SQL,
            [
                ':id' => 1,
            ]
        );

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Command does not support internalExecute() by core-db.'
        );

        $command->queryAll();
    }

    public function testRenameTable(): void
    {
        $db = $this->getConnection();

        $sql = $db->createCommand()->renameTable('table', 'newname')->getSql();

        $this->assertSame(
            <<<SQL
            RENAME TABLE `table` TO `newname`
            SQL,
            $sql,
        );
    }

    public function testResetSequence(): void
    {
        $db = $this->getConnection();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\DMLQueryBuilder does not support resetting sequence.'
        );

        $db->createCommand()->resetSequence('table', 5);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandProvider::upsert()
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function testUpsert(array $firstData, array $secondData): void
    {
        $db = $this->getConnectionWithData();

        $command = $db->createCommand();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\DMLQueryBuilder does not support upsert.'
        );

        $command->upsert('table', $firstData);
    }
}
