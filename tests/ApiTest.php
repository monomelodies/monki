<?php

namespace Monomelodies\Monki\Tests;

use Monomelodies\Monki\Api;
use Monolyth\Reroute\Router;
use Zend\Diactoros\ServerRequestFactory;
use PHPUnit_Extensions_Database_TestCase;
use Quibble\Sqlite\Adapter;
use Quibble\Query\Buildable;

class ApiTest
{
    static private $pdo = null;
    private $api;
    
    public function __construct()
    {
        $base = realpath(dirname(__DIR__).'/../');
        set_include_path(join(PATH_SEPARATOR, [
            "$base/httpdocs",
            "$base/src",
            "$base/vendor",
        ]));
        if (self::$pdo === null) {
            self::$pdo = new class(':memory:') extends Adapter {
                use Buildable;
            };
            $schema = file_get_contents(
                dirname(__FILE__).'/_files/schema.sql'
            );
            self::$pdo->exec($schema);
        }
    }

    public function __wakeup()
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /**
     * @covers Monki\Api::browse
     * @covers Monki\Endpoint\Item\Controller::create
     */
    public function testBrowse()
    {
        $db = self::$pdo;
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
    public function testCount()
    {
        $db = self::$pdo;
        $api = new Api($db, '/');
        $api->count();
        $_SERVER['REQUEST_URI'] = '/foo/count/';
        $response = $api(ServerRequestFactory::fromGlobals());
        $found = json_decode($response->getBody(), true);
        $this->assertEquals(4, $found['count']);
    }
     */

    /**
     * @covers Monki\Api::item
     * @covers Monki\Endpoint\Item\Controller::update
     * @covers Monki\Endpoint\Item\Controller::delete
    public function testItem()
    {
        $db = self::$pdo;
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
     */
}

