<?php

namespace Monki\Tests;

use Monki\Api;
use Reroute\Router;
use Zend\Diactoros\ServerRequestFactory;

class MonkiApiTest extends AbstractTest
{
    protected function setup()
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
        $db = $this->getConnection()->getConnection();
        $router = new Router;
        $api = new Api($db, $router);
        $api->browse();
        $_SERVER['REQUEST_URI'] = '/foo/';
        $state = $router(ServerRequestFactory::fromGlobals());
        var_dump($state->getBody()->__toString());
        $found = json_decode($state->getBody(), true);
        $this->assertEquals(4, count($found));
        $_POST = ['action' => 'create', 'data' => ['content' => 'whee']];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/foo/';
        $state = $router(ServerRequestFactory::fromGlobals());
        $found = json_decode($state->getBody(), true);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertEquals('whee', $found['content']);
    }

    /**
     * @covers Monki\Api::count
     */
    public function testCount()
    {
        $db = $this->getConnection()->getConnection();
        $router = new Router;
        $api = new Api($db, $router);
        $api->count();
        $_SERVER['REQUEST_URI'] = '/foo/count/';
        $state = $router(ServerRequestFactory::fromGlobals());
        $found = json_decode($state->getBody(), true);
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
        $router = new Router;
        $api = new Api($db, $router);
        $api->count();
        $api->item();
        $_SERVER['REQUEST_URI'] = '/foo/1/';
        $state = $router(ServerRequestFactory::fromGlobals());
        $found = json_decode($state->getBody(), true);
        $this->assertEquals('bar', $found['content']);
        $_POST = ['action' => 'update', 'data' => ['content' => 'boo']];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/foo/1/';
        $state = $router(ServerRequestFactory::fromGlobals());
        $found = json_decode($state->getBody(), true);
        $this->assertEquals('boo', $found['content']);
        $_POST = ['action' => 'delete'];
        $state = $router(ServerRequestFactory::fromGlobals());
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/foo/count/';
        $state = $router(ServerRequestFactory::fromGlobals());
        $found = json_decode($state->getBody(), true);
        $this->assertEquals(3, $found['count']);
    }
}

