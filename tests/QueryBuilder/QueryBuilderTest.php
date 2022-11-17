<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\QueryBuilder;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\SchemaBuilderTrait;
use Yiisoft\Db\Tests\AbstractQueryBuilderTest;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class QueryBuilderTest extends AbstractQueryBuilderTest
{
    use SchemaBuilderTrait;
    use TestTrait;

    public function testAddCommentOnColumn(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            COMMENT ON COLUMN `customer`.`name` IS 'This is name'
            SQL,
            $qb->addCommentOnColumn('customer', 'name', 'This is name')
        );
    }

    public function testAddCommentOnTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->assertSame(
            <<<SQL
            COMMENT ON TABLE `customer` IS 'Customer table'
            SQL,
            $qb->addCommentOnTable('customer', 'Customer table')
        );
    }

    /**
     *  @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::fromCases()
     */
    public function testBuildFrom(mixed $table, string $expectedSql, array $expectedParams = []): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->from($table);

        [$sql, $params] = $qb->build($query);

        $this->assertSame($expectedSql, $sql);
        $this->assertSame($expectedParams, $params);
    }

    public function testFromWithAliasesNoExist(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $query = (new Query($db))->from('no_exist_table');


        [$sql, $params] = $qb->build($query);

        $this->assertSame(
            <<<SQL
            SELECT * FROM `no_exist_table`
            SQL,
            $sql,
        );
        $this->assertSame([], $params);
    }

    public function testCreateTable(): void
    {
        $this->db = $this->getConnection();

        $qb = $this->db->getQueryBuilder();
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
        $sql = $qb->createTable('test_table', $columns, $options);

        Assert::equalsWithoutLE($expected, $sql);
    }

    public function testDropColumn(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->dropColumn('test_table', 'test_column');

        $this->assertSame(
            <<<SQL
            ALTER TABLE `test_table` DROP COLUMN `test_column`
            SQL,
            $sql,
        );
    }

    public function testDropCommentFromColumn(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->dropCommentFromColumn('test_table', 'test_column');

        $this->assertSame(
            <<<SQL
            COMMENT ON COLUMN `test_table`.`test_column` IS NULL
            SQL,
            $sql,
        );
    }

    public function testRenameTable(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $sql = $qb->renameTable('table_from', 'table_to');

        $this->assertSame(
            <<<SQL
            RENAME TABLE `table_from` TO `table_to`
            SQL,
            $sql,
        );
    }
}
