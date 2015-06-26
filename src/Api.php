<?php

namespace Monki;

use PDO;
use PDOException;
use Reroute\Router;
use Reroute\Url\Regex;

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

    private function error($code)
    {
        header("HTTP/1.1 $code {$this->status[$code]}", true, $code);
    }

    private function valid($validate, $table, $VERB)
    {
        if (isset($validate)) {
            if (is_callable($validate)) {
                if ($error = call_user_func($validate, $table, $VERB)) {
                    $this->error($error);
                    return false;
                }
            } else {
                $this->error(500);
                return false;
            }
        }
        return true;
    }

    public function browse($regex, $validate = null, $ctrl = null)
    {
        $this->router->group(
            'monki',
            function () use ($regex, $validate, $ctrl) {
                $this->router->state(
                    'monki-browse',
                    new Regex($regex, ['GET', 'POST']),
                    function ($table, $VERB) use ($validate, $ctrl) {
                        if (!$this->valid($validate, $table, $VERB)) {
                            return;
                        }
                        if ($VERB == 'POST') {
                            if (!isset($ctrl)) {
                                $ctrl = 'Monki\Endpoint\Item\Controller';
                            }
                            $controller = new $ctrl($this->adapter, $table);
                            if (!isset($_POST['action'])) {
                                $_POST['action'] = 'create';
                            }
                            if (method_exists($controller, $_POST['action'])) {
                                $controller->{$_POST['action']}($_POST['data']);
                            }
                            if ($_POST['action'] == 'create') {
                                return new Endpoint\Item\View(
                                    $this->adapter,
                                    $table,
                                    $this->adapter->lastInsertId()
                                );
                            }
                        }
                        return new Endpoint\Browse\View($this->adapter, $table);
                    }
                );
            }
        );
    }

    public function count($regex, $validate = null)
    {
        $this->router->group('monki', function () use ($regex, $validate) {
            $this->router->state(
                'monki-count',
                new Regex($regex),
                function ($table) use ($validate) {
                    if (!$this->valid($validate, $table, 'GET')) {
                        return;
                    }
                    return new Endpoint\Item\Cnt($this->adapter, $table);
                }
            );
        });
    }

    public function item($regex, callable $validate = null, $ctrl = null)
    {
        $this->router->group(
            'monki',
            function () use ($regex, $validate, $ctrl) {
                $this->router->state(
                    'monki-item',
                    new Regex($regex, ['GET', 'POST']),
                    function ($table, $id, $VERB) use ($validate, $ctrl) {
                        $where = new Where(compact('id'));
                        $stmt = $this->adapter->prepare(sprintf(
                            "SELECT * FROM %s WHERE id = ?",
                            $table
                        ));
                        try {
                            $stmt->execute([$id]);
                            $item = $stmt->fetch(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                        }
                        if (!$this->valid($validate, $table, $item, $VERB)) {
                            return;
                        }
                        if (!$item) {
                            $this->error(404);
                            return;
                        }
                        if ($VERB == 'POST') {
                            if (!isset($ctrl)) {
                                $ctrl = 'Monki\Endpoint\Item\Controller';
                            }
                            $controller = new $ctrl(
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
                            } else {
                                header(
                                    "HTTP/1.1 405 {$this->status[405]}",
                                    true,
                                    405
                                );
                                return;
                            }
                        }
                        return new Endpoint\Item\View($item);
                    }
                );
            }
        );
    }
}

