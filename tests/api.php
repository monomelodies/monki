<?php

use Monomelodies\Monki\Api;
use Monomelodies\Monki\Handler\Crud;
use Monolyth\Reroute\Router;
use Laminas\Diactoros\ServerRequestFactory;
use Quibble\Sqlite\Adapter;
use Quibble\Query\Buildable;
use Psr\Http\Message\RequestInterface;

return function () : Generator {
    $this->beforeEach(function () use (&$handler) {
        $handler = new class extends Crud {

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
    });

    $this->beforeEach(function () {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';
    });

    /** We can retrieve a list of json encoded items using browse. We can post to create a new item. */
    yield function () use (&$handler) {
        $router = new Router('/');
        $api = new Api($router);
        $api->crud('/foo/', $handler);
        $_SERVER['REQUEST_URI'] = '/foo/';
        $response = $router(ServerRequestFactory::fromGlobals());
        $found = json_decode($response->getBody(), true);
        assert(count($found) == 4);
        $_POST = ['content' => 'whee'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/foo/';
        $response = $router(ServerRequestFactory::fromGlobals());
        $found = json_decode($response->getBody(), true);
        assert($found['content'] == 'whee');
    };

    /** We can call our custom PUT method and it gives the expected custom result. */
    yield function () use (&$handler) {
        $router = new Router('/');
        $api = new Api($router);
        $api->crud('/foo/', $handler);
        $_SERVER['REQUEST_URI'] = '/foo/1/custom/';
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $response = $router(ServerRequestFactory::fromGlobals());
        if ($response) {
            $found = json_decode($response->getBody(), true);
        }
        assert($found['type'] == 'custom');
    };
};

