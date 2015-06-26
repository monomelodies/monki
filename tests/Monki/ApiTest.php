<?php

class MonkiApiTest extends AbstractTest
{
    /**
     * @covers Monki\Api::browse
     */
    public function testBrowse()
    {
        $db = $this->getConnection()->getConnection();
        $router = new Reroute\Router;
        $api = new Monki\Api($db, $router);
        $api->browse("/(?'table'foo)/");
        $state = $router->resolve('/foo/');
        $found = json_decode($state->run(), true);
        $this->assertEquals(4, count($found));
    }

    /**
     * @covers Monki\Api::count
     */
    public function testCount()
    {
        $db = $this->getConnection()->getConnection();
        $router = new Reroute\Router;
        $api = new Monki\Api($db, $router);
        $api->count("/(?'table'foo)/count/");
        $state = $router->resolve('/foo/count/');
        $found = json_decode($state->run(), true);
        $this->assertEquals(4, $found['count']);
    }

    /**
     * @covers Monki\Api::item
     */
    public function testItem()
    {
        $db = $this->getConnection()->getConnection();
        $router = new Reroute\Router;
        $api = new Monki\Api($db, $router);
        $api->item("/(?'table'foo)/(?'id'\d+)/");
        $state = $router->resolve('/foo/1/');
        $found = json_decode($state->run(), true);
        $this->assertEquals('bar', $found['content']);
        $_POST = ['action' => 'update', 'data' => ['content' => 'boo']];
        $state = $router->resolve('/foo/1/', 'POST');
        $found = json_decode($state->run(), true);
        $this->assertEquals('boo', $found['content']);
    }
}

