<?php

namespace Monki;

use PDO;
use PDOException;
use Reroute\Router;
use Monki\Endpoint\Item;
use Monki\Endpoint\Browse;
use Psr\Http\Message\RequestInterface;
use Zend\Diactoros\Response\HtmlResponse;

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

class Api
{
    protected $adapter;
    protected $router;
    protected $status = [
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not found',
        405 => 'Method not allowed',
        500 => 'Internal server error',
    ];

    public function __construct(PDO $adapter, Router $router)
    {
        $this->adapter = $adapter;
        $this->router = $router;
    }

    public function handle($controller, $table, $verb)
    {
        if ($verb == 'POST') {
            if (!isset($_POST['action'])) {
                $_POST['action'] = 'create';
            }
            if (method_exists($controller, $_POST['action'])) {
                $id = $controller->{$_POST['action']}(
                    isset($_POST['data']) ? $_POST['data'] : []
                );
            }
            if ($_POST['action'] == 'create') {
                if ($id == 0) {
                    return json_encode(new \StdClass);
                }
                $stmt = $this->adapter->prepare(sprintf(
                    "SELECT * FROM %s WHERE id = ?",
                    $table
                ));
                try {
                    $stmt->execute([$id]);
                    $item = $stmt->fetch(PDO::FETCH_ASSOC);
                    return (new HtmlResponse(json_encode($item)))
                        ->withHeader('Content-Type', 'application/json');
                } catch (PDOException $e) {
                    return $this->error(500);
                }
            }
        }
        return new Browse\View($this->adapter, $table);
    }

    public function browse(callable $validate = null)
    {
        $validate = $this->validate($validate);
        $this->router
             ->when("/(?'table'\w+)/")
             ->pipe($validate)
             ->then(
                'monki-browse',
                function ($table, RequestInterface $request) {
                    return $this->handle(
                        new Item\Controller($this->adapter, $table),
                        $table,
                        $request->getMethod()
                    );
                 });
    }

    public function count(callable $validate = null)
    {
        $validate = $this->validate($validate);
        $this->router
             ->when("/(?'table'\w+)/count/")
             ->pipe($validate)
             ->then('monki-count', function ($table) {
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
                function ($table, $id, RequestInterface $request) {
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
                            $item = $stmt->fetch(PDO::FETCH_ASSOC);
                        } else {
                            header(
                                "HTTP/1.1 405 {$this->status[405]}",
                                true,
                                405
                            );
                            return;
                        }
                    }
                    return (new HtmlResponse(json_encode($item)))
                        ->withHeader('Content-type', 'application/json');
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
}

