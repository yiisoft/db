<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PDO;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Yiisoft\Db\Expression\Value\Param;
use Yiisoft\Db\Driver\Pdo\AbstractPdoCommand;
use InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Tests\Provider\CommandPdoProvider;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

abstract class CommonPdoCommandTest extends IntegrationTestCase
{
    #[DataProviderExternal(CommandPdoProvider::class, 'bindParam')]
    public function testBindParam(
        string $field,
        string $name,
        mixed $value,
        int $dataType,
        ?int $length,
        mixed $driverOptions,
        array $expected,
    ): void {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $sql = $this->replaceQuotes(
            <<<SQL
            SELECT * FROM [[customer]] WHERE $field = $name
            SQL,
        );
        $command = $db->createCommand();
        $command->setSql($sql);
        $command->bindParam($name, $value, $dataType, $length, $driverOptions);

        $this->assertSame($sql, $command->getSql());
        $this->assertSame($expected, $command->queryOne());
    }

    /**
     * Test whether param binding works in other places than WHERE.
     */
    #[DataProviderExternal(CommandPdoProvider::class, 'bindParamsNonWhere')]
    public function testBindParamsNonWhere(string $sql): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $db->createCommand()->insert(
            '{{customer}}',
            [
                'name' => 'testParams',
                'email' => 'testParams@example.com',
                'address' => '1',
            ],
        )->execute();
        $params = [':email' => 'testParams@example.com', ':len' => 5];
        $command = $db->createCommand($sql, $params);

        $this->assertSame('Params', $command->queryScalar());
    }

    public function testBindParamValue(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $command = $db->createCommand();

        // bindParam
        $command->setSql(
            <<<SQL
            INSERT INTO [[customer]] ([[name]], [[email]], [[address]]) VALUES (:name, :email, :address)
            SQL,
        );
        $email = 'user4@example.com';
        $name = 'user4';
        $address = 'address4';
        $command->bindParam(':email', $email);
        $command->bindParam(':name', $name);
        $command->bindParam(':address', $address);
        $command->execute();
        $command = $command->setSql(
            <<<SQL
            SELECT [[name]] FROM [[customer]] WHERE [[email]] = :email
            SQL,
        );
        $command->bindParam(':email', $email);

        $this->assertSame($name, $command->queryScalar());

        // bindValue
        $command->setSql(
            <<<SQL
            INSERT INTO [[customer]] ([[email]], [[name]], [[address]]) VALUES (:email, 'user5', 'address5')
            SQL,
        );
        $command->bindValue(':email', 'user5@example.com');
        $command->execute();
        $command->setSql(
            <<<SQL
            SELECT [[email]] FROM [[customer]] WHERE [[name]] = :name
            SQL,
        );
        $command->bindValue(':name', 'user5');

        $this->assertSame('user5@example.com', $command->queryScalar());
    }

    public function testBindValues(): void
    {
        $db = $this->getSharedConnection();

        $command = $db->createCommand();

        $values = ['int' => 1, 'string' => 'str'];
        $command->bindValues($values);
        $bindedValues = $command->getParams(false);

        $this->assertIsArray($bindedValues);
        $this->assertContainsOnlyInstancesOf(Param::class, $bindedValues);
        $this->assertCount(2, $bindedValues);

        $param = new Param('str', 99);
        $command->bindValues(['param' => $param]);
        $bindedValues = $command->getParams(false);

        $this->assertIsArray($bindedValues);
        $this->assertContainsOnlyInstancesOf(Param::class, $bindedValues);
        $this->assertCount(3, $bindedValues);
        $this->assertSame($param, $bindedValues['param']);
        $this->assertNotEquals($param, $bindedValues['int']);

        /* Replace test */
        $command->bindValues(['int' => $param]);
        $bindedValues = $command->getParams(false);

        $this->assertIsArray($bindedValues);
        $this->assertContainsOnlyInstancesOf(Param::class, $bindedValues);
        $this->assertCount(3, $bindedValues);
        $this->assertSame($param, $bindedValues['int']);
    }

    public function testColumnCase(): void
    {
        $db = $this->createConnection();
        $this->loadFixture();

        $this->assertSame(PDO::CASE_NATURAL, $db->getActivePdo()->getAttribute(PDO::ATTR_CASE));

        $command = $db->createCommand();
        $sql = <<<SQL
        SELECT [[customer_id]], [[total]] FROM [[order]]
        SQL;
        $rows = $command->setSql($sql)->queryAll();

        $this->assertTrue(isset($rows[0]));
        $this->assertTrue(isset($rows[0]['customer_id']));
        $this->assertTrue(isset($rows[0]['total']));

        $db->getActivePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        $this->assertSame(PDO::CASE_LOWER, $db->getActivePdo()->getAttribute(PDO::ATTR_CASE));

        $rows = $command->setSql($sql)->queryAll();

        $this->assertTrue(isset($rows[0]));
        $this->assertTrue(isset($rows[0]['customer_id']));
        $this->assertTrue(isset($rows[0]['total']));

        $db->getActivePdo()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);

        $this->assertSame(PDO::CASE_UPPER, $db->getActivePdo()->getAttribute(PDO::ATTR_CASE));

        $rows = $command->setSql($sql)->queryAll();

        $this->assertTrue(isset($rows[0]));
        $this->assertTrue(isset($rows[0]['CUSTOMER_ID']));
        $this->assertTrue(isset($rows[0]['TOTAL']));

        $db->close();
    }

    public function testIncorrectQueryMode(): void
    {
        $db = $this->getSharedConnection();
        $this->loadFixture();

        $command = new class ($db) extends AbstractPdoCommand {
            public function testExecute(): void
            {
                $this->internalGetQueryResult(1024);
            }

            public function showDatabases(): array
            {
                $this->showDatabases();
            }

            protected function getQueryBuilder(): QueryBuilderInterface {}

            protected function internalExecute(): void {}
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown query mode '1024'");
        $command->testExecute();
    }

    protected function createQueryLogger(string $sql, array $params = []): LoggerInterface
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(
                LogLevel::INFO,
                $sql,
                $params + ['type' => 'query'],
            );
        return $logger;
    }
}
