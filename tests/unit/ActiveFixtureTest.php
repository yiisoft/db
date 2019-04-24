<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\db\tests\unit;

use yii\activerecord\tests\data\ActiveRecord;
use yii\activerecord\tests\data\Customer;
use yii\helpers\Yii;
use yii\test\ActiveFixture;
use yii\test\FixtureTrait;

class ProfileFixture extends ActiveFixture
{
    public $modelClass = \yii\activerecord\tests\data\Profile::class;

    public $dataDirectory = '@yii/tests/framework/test/data';
}

class CustomerFixture extends ActiveFixture
{
    public $modelClass = \yii\activerecord\tests\data\Customer::class;

    public $dataDirectory = '@yii/tests/framework/test/data';

    public $depends = [
        'yii\db\tests\unit\ProfileFixture',
    ];
}

class CustomDirectoryFixture extends ActiveFixture
{
    public $modelClass = \yii\activerecord\tests\data\Customer::class;

    public $dataDirectory = '@yii/tests/framework/test/custom';
}

class AnimalFixture extends ActiveFixture
{
    public $modelClass = \yii\activerecord\tests\data\Animal::class;
}

class BaseDbTestCase
{
    use FixtureTrait;

    public function setUp()
    {
        $this->initFixtures();
    }

    public function tearDown()
    {
    }
}

class CustomerDbTestCase extends BaseDbTestCase
{
    public function fixtures()
    {
        return [
            'customers' => CustomerFixture::class,
        ];
    }
}

class CustomDirectoryDbTestCase extends BaseDbTestCase
{
    public function fixtures()
    {
        return [
            'customers' => CustomDirectoryFixture::class,
        ];
    }
}

class DataPathDbTestCase extends BaseDbTestCase
{
    public function fixtures()
    {
        return [
            'customers' => [
                '__class'  => CustomDirectoryFixture::class,
                'dataFile' => '@yii/tests/framework/test/data/customer.php',
            ],
        ];
    }
}

class TruncateTestCase extends BaseDbTestCase
{
    public function fixtures()
    {
        return [
            'animals' => [
                '__class' => AnimalFixture::class,
            ],
        ];
    }
}

/**
 * @group fixture
 * @group db
 */
class ActiveFixtureTest extends DatabaseTestCase
{
    protected $driverName = 'mysql';

    public function setUp()
    {
        parent::setUp();
        $db = $this->getConnection();
        $this->container->set('db', $db);
        ActiveRecord::$db = $db;
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function testGetData()
    {
        $test = new CustomerDbTestCase();
        $test->setUp();
        $fixture = $test->getFixture('customers');

        $this->assertEquals(CustomerFixture::class, get_class($fixture));
        $this->assertCount(2, $fixture);
        $this->assertEquals(1, $fixture['customer1']['id']);
        $this->assertEquals('customer1@example.com', $fixture['customer1']['email']);
        $this->assertEquals(1, $fixture['customer1']['profile_id']);

        $this->assertEquals(2, $fixture['customer2']['id']);
        $this->assertEquals('customer2@example.com', $fixture['customer2']['email']);
        $this->assertEquals(2, $fixture['customer2']['profile_id']);

        $test->tearDown();
    }

    public function testGetModel()
    {
        $test = new CustomerDbTestCase();
        $test->setUp();
        $fixture = $test->getFixture('customers');

        $this->assertEquals(Customer::class, get_class($fixture->getModel('customer1')));
        $this->assertEquals(1, $fixture->getModel('customer1')->id);
        $this->assertEquals('customer1@example.com', $fixture->getModel('customer1')->email);
        $this->assertEquals(1, $fixture['customer1']['profile_id']);

        $this->assertEquals(2, $fixture->getModel('customer2')->id);
        $this->assertEquals('customer2@example.com', $fixture->getModel('customer2')->email);
        $this->assertEquals(2, $fixture['customer2']['profile_id']);

        $test->tearDown();
    }

    public function testDataDirectory()
    {
        $test = new CustomDirectoryDbTestCase();

        $test->setUp();
        $fixture = $test->getFixture('customers');
        $directory = $fixture->getModel('directory');

        $this->assertEquals(1, $directory->id);
        $this->assertEquals('directory@example.com', $directory['email']);
        $test->tearDown();
    }

    public function testDataPath()
    {
        $test = new DataPathDbTestCase();

        $test->setUp();
        $fixture = $test->getFixture('customers');
        $customer = $fixture->getModel('customer1');

        $this->assertEquals(1, $customer->id);
        $this->assertEquals('customer1@example.com', $customer['email']);
        $test->tearDown();
    }

    public function testTruncate()
    {
        $test = new TruncateTestCase();

        $test->setUp();
        $fixture = $test->getFixture('animals');
        $this->assertEmpty($fixture->data);
        $test->tearDown();
    }
}
