<?php

namespace Monomelodies\Monki\Tests;

use Monomelodies\Monki\Api;
use Monomelodies\Monki\Handler\Crud;
use Monolyth\Reroute\Router;
use Zend\Diactoros\ServerRequestFactory;
use PHPUnit_Extensions_Database_TestCase;
use Quibble\Sqlite\Adapter;
use Quibble\Query\Buildable;
use Psr\Http\Message\RequestInterface;

class ApiTest
{
    static private $pdo = null;
    private $api;
    
    public function __construct()
    {
        $this->handler = new class extends Crud {

            public function browse()
            {
                return $this->jsonResponse([['id' => 1], ['id' => 2], ['id' => 3], ['id' => 4]]);
            }

            public function create(RequestInterface $request)
            {
                return $this->jsonResponse($_POST);
            }

            public function retrieve($id)
            {
                return $this->jsonResponse(['id' => $id]);
            }

            public function update($id, RequestInterface $request)
            {
                return $this->jsonResponse($_POST);
            }

        };
    }

    public function __wakeup()
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /**
     * We can retrieve a list of json encoded items using browse {?}. We can
     * post to create a new item {?}.
     */
    public function testBrowse()
    {
        $db = self::$pdo;
        $api = new Api('/');
        $api->crud('/foo/', '/:id/', $this->handler);
        $_SERVER['REQUEST_URI'] = '/foo/';
        $response = $api(ServerRequestFactory::fromGlobals());
        $found = json_decode($response->getBody(), true);
        yield assert(count($found) == 4);
        $_POST = ['content' => 'whee'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/foo/';
        $response = $api(ServerRequestFactory::fromGlobals());
        $found = json_decode($response->getBody(), true);
        yield assert($found['content'] == 'whee');
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

