<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PDO;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Command\ParamInterface;

abstract class CommonCommandPDOtest extends TestCase
{
    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandPDOProvider::bindParam()
     */
    public function testBindParam(
        string $field,
        string $name,
        mixed $value,
        int $dataType,
        int|null $length,
        mixed $driverOptions,
        array $expected,
    ): void {
        $db = $this->getConnection('customer');

        $sql = "SELECT * FROM customer WHERE $field = $name";
        $command = $db->createCommand();
        $command->setSql($sql);
        $command->bindParam($name, $value, $dataType, $length, $driverOptions);

        $this->assertSame($sql, $command->getSql());
        $this->assertSame($expected, $command->queryOne());
    }

    /**
     * Test whether param binding works in other places than WHERE.
     *
     * @dataProvider \Yiisoft\Db\Tests\Provider\CommandPDOProvider::bindParamsNonWhere()
     */
    public function testBindParamsNonWhere(string $sql): void
    {
        $db = $this->getConnection('customer');

        $db->createCommand()->insert(
            'customer',
            [
                'name' => 'testParams',
                'email' => 'testParams@example.com',
                'address' => '1',
            ]
        )->execute();
        $params = [':email' => 'testParams@example.com', ':len' => 5];
        $command = $db->createCommand($sql, $params);

        $this->assertSame('Params', $command->queryScalar());
    }

    public function testBindParamValue(): void
    {
        $db = $this->getConnection('customer');

        $command = $db->createCommand();

        // bindParam
        $command = $command->setSql(
            <<<SQL
            INSERT INTO customer(email, name, address) VALUES (:email, :name, :address)
            SQL
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
            SELECT name FROM customer WHERE email=:email
            SQL,
        );
        $command->bindParam(':email', $email);

        $this->assertSame($name, $command->queryScalar());

        // bindValue
        $command = $command->setSql(
            <<<SQL
            INSERT INTO customer(email, name, address) VALUES (:email, 'user5', 'address5')
            SQL
        );
        $command->bindValue(':email', 'user5@example.com');
        $command->execute();
        $command = $command->setSql(
            <<<SQL
            SELECT email FROM customer WHERE name=:name
            SQL
        );

        $command->bindValue(':name', 'user5');

        $this->assertSame('user5@example.com', $command->queryScalar());
    }

    public function testBindValues(): void
    {
        $db = $this->getConnection();

        $command = $db->createCommand();

        $values = ['int' => 1, 'string' => 'str'];
        $command->bindValues($values);
        $bindedValues = $command->getParams(false);

        $this->assertIsArray($bindedValues);
        $this->assertContainsOnlyInstancesOf(ParamInterface::class, $bindedValues);
        $this->assertCount(2, $bindedValues);

        $param = new Param('str', 99);
        $command->bindValues(['param' => $param]);
        $bindedValues = $command->getParams(false);

        $this->assertIsArray($bindedValues);
        $this->assertContainsOnlyInstancesOf(ParamInterface::class, $bindedValues);
        $this->assertCount(3, $bindedValues);
        $this->assertSame($param, $bindedValues['param']);
        $this->assertNotEquals($param, $bindedValues['int']);

        /* Replace test */
        $command->bindValues(['int' => $param]);
        $bindedValues = $command->getParams(false);

        $this->assertIsArray($bindedValues);
        $this->assertContainsOnlyInstancesOf(ParamInterface::class, $bindedValues);
        $this->assertCount(3, $bindedValues);
        $this->assertSame($param, $bindedValues['int']);
    }

    public function testColumnCase(): void
    {
        $db = $this->getConnection('order');

        $this->assertSame(PDO::CASE_NATURAL, $db->getActivePDO()->getAttribute(PDO::ATTR_CASE));

        $command = $db->createCommand();
        $sql = <<<SQL
        SELECT [[customer_id]], [[total]] FROM {{order}}
        SQL;
        $rows = $command->setSql($sql)->queryAll();

        $this->assertTrue(isset($rows[0]));
        $this->assertTrue(isset($rows[0]['customer_id']));
        $this->assertTrue(isset($rows[0]['total']));

        $db->getActivePDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
        $rows = $command->setSql($sql)->queryAll();

        $this->assertTrue(isset($rows[0]));
        $this->assertTrue(isset($rows[0]['customer_id']));
        $this->assertTrue(isset($rows[0]['total']));

        $db->getActivePDO()->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);
        $rows = $command->setSql($sql)->queryAll();

        $this->assertTrue(isset($rows[0]));
        $this->assertTrue(isset($rows[0]['CUSTOMER_ID']));
        $this->assertTrue(isset($rows[0]['TOTAL']));
    }
}
