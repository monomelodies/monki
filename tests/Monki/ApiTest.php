<?php

class MonkiApiTest extends AbstractTest
{
    /**
     * @covers Monki\Api::browse
     * @covers Monki\Endpoint\Item\Controller::create
     */
    public function testBrowse()
    {
        $db = $this->getConnection()->getConnection();
        $router = new Reroute\Router;
        $api = new Monki\Api($db, $router);
        $api->browse();
        $state = $router->resolve('/foo/');
        $found = json_decode($state(), true);
        $this->assertEquals(4, count($found));
        $_POST = ['action' => 'create', 'data' => ['content' => 'whee']];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $state = $router->resolve('/foo/', 'POST');
        $found = json_decode($state(), true);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertEquals('whee', $found['content']);
    }

    /**
     * @covers Monki\Api::count
     */
    public function testCount()
    {
        $db = $this->getConnection()->getConnection();
        $router = new Reroute\Router;
        $api = new Monki\Api($db, $router);
        $api->count();
        $state = $router->resolve('/foo/count/');
        $found = json_decode($state(), true);
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
        $router = new Reroute\Router;
        $api = new Monki\Api($db, $router);
        $api->count();
        $api->item();
        $state = $router->resolve('/foo/1/');
        $found = json_decode($state(), true);
        $this->assertEquals('bar', $found['content']);
        $_POST = ['action' => 'update', 'data' => ['content' => 'boo']];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $state = $router->resolve('/foo/1/', 'POST');
        $found = json_decode($state(), true);
        $this->assertEquals('boo', $found['content']);
        $_POST = ['action' => 'delete'];
        $state = $router->resolve('/foo/1/', 'POST');
        echo $state();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $state = $router->resolve('/foo/count/');
        $found = json_decode($state(), true);
        $this->assertEquals(3, $found['count']);
    }
}

