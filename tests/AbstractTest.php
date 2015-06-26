<?php

use Dabble\Adapter\Sqlite;
use Disclosure\Container;
use Reroute\State;

abstract class AbstractTest extends PHPUnit_Extensions_Database_TestCase
{
    static private $pdo = null;
    private $conn = null;

    public function __construct()
    {
        parent::__construct();
        $base = realpath(dirname(__DIR__).'/../');
        set_include_path(join(PATH_SEPARATOR, [
            "$base/httpdocs",
            "$base/src",
            "$base/vendor",
        ]));
    }

    public function getConnection()
    {
        if ($this->conn === null) {
            if (self::$pdo === null) {
                self::$pdo = new Sqlite(':memory:');
                $schema = file_get_contents(
                    dirname(__FILE__).'/_files/schema.sql'
                );
                self::$pdo->exec($schema);
            }
            $this->conn = $this->createDefaultDBConnection(
                self::$pdo,
                'monki_test'
            );
        }
        return $this->conn;
    }

    public function getDataSet()
    {
        return $this->createXMLDataset(dirname(__FILE__).'/_files/data.xml');
    }
}

