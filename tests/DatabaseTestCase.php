<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use Yiisoft\Cache\NullCache;
use Yiisoft\Db\Drivers\Connection;
use Yiisoft\Db\Drivers\ConnectionPool;
use Yiisoft\Db\Helper\Dsn;

abstract class DatabaseTestCase extends TestCase
{
    private ?Connection $db = null;
    protected array $databases = [];
    protected ?Dsn $dsn = null;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->driverName === null) {
            throw new \Exception('driverName is not set for a DatabaseTestCase.');
        }
    }

    protected function tearDown(): void
    {
        if ($this->db) {
            $this->db->close();
        }

        parent::tearDown();
    }

    /**
     * @param bool $reset whether to clean up the test database
     * @param bool $open whether to open and populate test database
     * @param bool $fixture
     *
     * @return Connection
     */
    public function getConnection(bool $reset = true, bool $open = true, $fixture = false): Connection
    {
        if (!$reset && $this->db) {
            return $this->db;
        }

        try {
            $this->db = $this->prepareDatabase($fixture, $open);
        } catch (\Exception $e) {
            $this->markTestSkipped('Something wrong when preparing database: ' . $e->getMessage());
        }

        return $this->db;
    }

    public function createConnection(array $config = []): Connection
    {
        $this->configContainer();

        $this->databases = self::getParam('databases');

        $this->databases = $this->databases[$this->driverName];

        $pdo_database = 'pdo_' . $this->driverName;

        if ($this->driverName === 'oci') {
            $pdo_database = 'oci8';
        }

        if (!\extension_loaded('pdo') || !\extension_loaded($pdo_database)) {
            $this->markTestSkipped('pdo and ' . $pdo_database . ' extension are required.');
        }

        if ($this->driverName !== 'sqlite') {
            $this->dsn = new Dsn(
                $this->databases['dsn']['driver'],
                $this->databases['dsn']['host'],
                $this->databases['dsn']['dbname'],
                $this->databases['dsn']['port'],
            );
            $dsn = $this->dsn->getDsn();
        } else {
            $dsn = $config['dsn'] ?? $this->databases['dsn'];
        }

        $db = new Connection($this->cache, $this->logger, $this->profiler, $dsn);

        if ($this->driverName !== 'sqlite') {
            $db->setUsername($this->databases['username']);
            $db->setPassword($this->databases['password']);
        }


        ConnectionPool::setConnectionsPool($this->driverName, $db);

        return $db;
    }

    protected function prepareDatabase(bool $fixture, bool $open, array $config = []): Connection
    {
        $db = $this->createConnection($config);

        if (!$open) {
            return $db;
        }

        $db->open();

        if ($fixture) {
            if ($this->driverName === 'oci') {
                [$drops, $creates] = explode('/* STATEMENTS */', file_get_contents($this->databases['fixture']), 2);
                [$statements, $triggers, $data] = explode('/* TRIGGERS */', $creates, 3);
                $lines = array_merge(
                    explode('--', $drops),
                    explode(';', $statements),
                    explode('/', $triggers),
                    explode(';', $data)
                );
            } else {
                $lines = explode(';', file_get_contents($this->databases['fixture']));
            }

            foreach ($lines as $line) {
                if (trim($line) !== '') {
                    $db->getPDO()->exec($line);
                }
            }
        }

        return $db;
    }

    /**
     * Returns a test configuration params from /config/params.php.
     *
     * @param string $name params name
     * @param mixed $default default value to use when param is not set.
     *
     * @return mixed  the value of the configuration param
     */
    protected static function getParam($name, $default = null)
    {
        if (empty(static::$params)) {
            static::$params = require __DIR__ . '/data/config.php';
        }

        return static::$params[$name] ?? $default;
    }

    /**
     * Adjust dbms specific escaping.
     *
     * @param $sql
     *
     * @return mixed
     */
    protected function replaceQuotes($sql)
    {
        switch ($this->driverName) {
            case 'mysql':
            case 'sqlite':
                return str_replace(['[[', ']]'], '`', $sql);
            case 'oci':
                return str_replace(['[[', ']]'], '"', $sql);
            case 'pgsql':
                // more complex replacement needed to not conflict with postgres array syntax
                return str_replace(['\\[', '\\]'], ['[', ']'], preg_replace('/(\[\[)|((?<!(\[))\]\])/', '"', $sql));
            case 'sqlsrv':
                return str_replace(['[[', ']]'], ['[', ']'], $sql);
            default:
                return $sql;
        }
    }

    /**
     * @return Connection
     */
    protected function getConnectionWithInvalidSlave(): Connection
    {
        $config = array_merge($this->database, [
            'serverStatusCache' => new NullCache(),
            'slaves'            => [
                [], // invalid config
            ],
        ]);

        if (isset($config['fixture'])) {
            $fixture = $config['fixture'];
            unset($config['fixture']);
        } else {
            $fixture = null;
        }

        return $this->prepareDatabase($config, $fixture, true);
    }
}
