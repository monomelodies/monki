<?php

namespace Monki\Tests;

use Monki\Api;
use Reroute\Router;
use Zend\Diactoros\ServerRequestFactory;
use PHPUnit_Extensions_Database_TestCase;
use Dabble\Adapter\Sqlite;

class MonkiApiTest extends PHPUnit_Extensions_Database_TestCase
{

    static private $pdo = null;
    private $conn = null;
    private $api;
    
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

    protected function setup()
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        parent::setup();
    }

    /**
     * @covers Monki\Api::browse
     * @covers Monki\Endpoint\Item\Controller::create
     */
    public function testBrowse()
    {
        $db = $this->getConnection()->getConnection();
        $api = new Api($db, '/');
        $api->browse();
        $_SERVER['REQUEST_URI'] = '/foo/';
        $response = $api(ServerRequestFactory::fromGlobals());
        $found = json_decode($response->getBody(), true);
        $this->assertEquals(4, count($found));
        $_POST = ['content' => 'whee'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/foo/';
        $response = $api(ServerRequestFactory::fromGlobals());
        $found = json_decode($response->getBody(), true);
        $this->assertEquals('whee', $found['content']);
    }

    /**
     * @covers Monki\Api::count
     */
    public function testCount()
    {
        $db = $this->getConnection()->getConnection();
        $api = new Api($db, '/');
        $api->count();
        $_SERVER['REQUEST_URI'] = '/foo/count/';
        $response = $api(ServerRequestFactory::fromGlobals());
        $found = json_decode($response->getBody(), true);
        $this->assertEquals(4, $found['count']);
    }

    /**
     * @covers Monki\Api::item
     * @covers Monki\Endpoint\Item\Controller::update
     * @covers Monki\Endpoint\Item\Controller::delete
     */
    public function testItem()
    {
        $db = $this->getConnection()->getConnection();
        $api = new Api($db, '/');
        $api->count();
        $api->item();
        $_SERVER['REQUEST_URI'] = '/foo/1/';
        $response = $api(ServerRequestFactory::fromGlobals());
        $found = json_decode($response->getBody(), true);
        $this->assertEquals('bar', $found['content']);
        $_POST = ['content' => 'boo'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/foo/1/';
        $response = $api(ServerRequestFactory::fromGlobals());
        $found = json_decode($response->getBody(), true);
        $this->assertEquals('boo', $found['content']);
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $response = $api(ServerRequestFactory::fromGlobals());
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/foo/count/';
        $response = $api(ServerRequestFactory::fromGlobals());
        $found = json_decode($response->getBody(), true);
        $this->assertEquals(3, $found['count']);
    }
}

