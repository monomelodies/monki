<?php

namespace Monki;

use PDO;
use PDOException;
use Reroute\Router;
use Reroute\Url\Regex;
use Monki\Endpoint\Item;
use Monki\Endpoint\Browse;

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

    public function browse(callable $validate = null)
    {
        $this->router
             ->when("/(?'table'\w+)/", $validate)
             ->then('monki-browse', function ($table, $VERB) {
                if ($VERB == 'POST') {
                    $controller = new Item\Controller($this->adapter, $table);
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
                            return new Item\View($item);
                        } catch (PDOException $e) {
                            return $this->error(500);
                        }
                    }
                }
                return new Browse\View($this->adapter, $table);
             });
    }

    public function count(callable $validate = null)
    {
        $this->router
             ->when("/(?'table'\w+)/count/", $validate)
             ->then('monki-count', function ($table) {
                return new Item\Cnt($this->adapter, $table);
             });
    }

    public function item(callable $validate = null)
    {
        $this->router
             ->when("/(?'table'\w+)/(?'id'\d+)/", $validate)
             ->then('monki-item', function ($table, $id, $VERB) {
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
                if ($VERB == 'POST') {
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
                return new Item\View($item);
             });
    }
}

