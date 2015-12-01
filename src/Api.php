<?php

namespace Monki;

use StdClass;
use PDO;
use PDOException;
use Reroute\Router;
use Monki\Endpoint\Item;
use Monki\Endpoint\Browse;
use Monki\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;
use League\Pipeline\StageInterface;

$recurse = function (array &$data) use (&$recurse) {
    foreach ($data as &$value) {
        if (is_array($value)) {
            $recurse($value);
        } else {
            if (preg_match('@^\$\((\w+)\)$@', $value, $fn)) {
                $fn = $fn[1];
                if (function_exists($fn)) {
                    $value = $fn();
                }
            }
        }
    }
};

$recurse($_POST);

class Api implements StageInterface
{
    protected $adapter;
    protected $router;

    public function __construct(PDO $adapter, $url = '/')
    {
        $this->adapter = $adapter;
        $this->router = new Router($url);
    }

    public function browse(callable $validate = null)
    {
        $validate = $this->validate($validate);
        $this->router
             ->when("/(?'table'\w+)/")
             ->pipe($validate)
             ->then(
                'monki-browse',
                function ($table, ServerRequestInterface $request) {
                    if ($request->getMethod() == 'POST') {
                        if (!isset($_POST['action'])) {
                            $_POST['action'] = 'create';
                        }
                        $controller = new Item\Controller(
                            $this->adapter,
                            $table
                        );
                        if (method_exists($controller, $_POST['action'])) {
                            $id = $controller->{$_POST['action']}(
                                isset($_POST['data']) ? $_POST['data'] : []
                            );
                        }
                        if ($_POST['action'] == 'create') {
                            if ($id == 0) {
                                return json_encode(new StdClass);
                            }
                            $stmt = $this->adapter->prepare(sprintf(
                                "SELECT * FROM %s WHERE id = ?",
                                $table
                            ));
                            try {
                                $stmt->execute([$id]);
                                $item = $stmt->fetch(PDO::FETCH_ASSOC);
                                return new JsonResponse($item);
                            } catch (PDOException $e) {
                                return new EmptyResponse(500);
                            }
                        }
                    }
                    return new Browse\View($this->adapter, $table);
                 });
    }

    public function count(callable $validate = null)
    {
        $validate = $this->validate($validate);
        $this->router
             ->when("/(?'table'\w+)/count/")
             ->pipe($validate)
             ->then('monki-count', function ($table) {
                var_dump($table);
                return new Item\Cnt($this->adapter, $table);
             });
    }

    public function item(callable $validate = null)
    {
        $validate = $this->validate($validate);
        $this->router
             ->when("/(?'table'\w+)/(?'id'\d+)/")
             ->pipe($validate)
             ->then(
                'monki-item',
                function ($table, $id, ServerRequestInterface $request) {
                    $stmt = $this->adapter->prepare(sprintf(
                        "SELECT * FROM %s WHERE id = ?",
                        $table
                    ));
                    try {
                        $stmt->execute([$id]);
                        $item = $stmt->fetch(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        header("HTTP/1.1 404 Not found", true, 404);
                        return;
                    }
                    if ($request->getMethod() == 'POST') {
                        $controller = new Item\Controller(
                            $this->adapter,
                            $table,
                            $item
                        );
                        if (!isset($_POST['action'])) {
                            $_POST['action'] = 'update';
                        }
                        if (method_exists($controller, $_POST['action'])) {
                            $controller->{$_POST['action']}(
                                isset($_POST['data']) ? $_POST['data'] : null
                            );
                            $stmt->execute([$id]);
                            if ($_POST['action'] == 'delete') {
                                return new EmptyResponse(200);
                            }
                            $item = $stmt->fetch(PDO::FETCH_ASSOC);
                        } else {
                            return new EmptyResponse(405);
                        }
                    }
                    return new JsonResponse($item);
                 }
            );
    }

    protected function validate(callable $validate = null)
    {
        if (!isset($validate)) {
            $validate = function ($payload) { return $payload; };
        }
        return new Stage($validate);
    }

    public function process($payload = null)
    {
        return $this($payload);
    }

    public function __invoke($payload = null)
    {
        if (isset($payload) && !($payload instanceof ServerRequestInterface)) {
            return $payload;
        }
        return $this->router->process($payload);
    }
}

