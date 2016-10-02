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

            /**
             * @Method PUT
             * @Url /:id/custom/
             */
            public function custom($id)
            {
                return $this->jsonResponse(['type' => 'custom']);
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
        $api = new Api('/');
        $api->crud('/foo/', $this->handler);
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
     * We can call our custom PUT method {?} and it gives the expected
     * custom result {?}.
     */
    public function testCustom()
    {
        $api = new Api('/');
        $api->crud('/foo/', $this->handler);
        $_SERVER['REQUEST_URI'] = '/foo/1/custom/';
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $response = $api(ServerRequestFactory::fromGlobals());
        if ($response) {
            $found = json_decode($response->getBody(), true);
        }
        yield assert($found['type'] == 'custom');
    }
}

