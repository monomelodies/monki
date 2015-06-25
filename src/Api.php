<?php

namespace Monki;

use PDO;
use Reroute\Router;
use Reroute\Url\Regex;

class Api
{
    protected $adapter;
    protected $router;

    public function __construct(PDO $adapter, Router $router)
    {
        $this->adapter = $adapter;
        $this->router = $router;
    }

    public function browse($regex)
    {
        $this->router->group('monki', function () use ($regex) {
            $this->router->state(
                'monki-browse',
                new Regex($regex, ['GET', 'POST']),
                function ($table, $VERB) {
                    if ($VERB == 'POST') {
                        $controller = new Endpoint\Item\Controller($this->adapter);
                        if (!isset($_POST['action'])) {
                            $_POST['action'] = 'create';
                        }
                        if (method_exists($controller, $_POST['action'])) {
                            $controller->{$_POST['action']}($table);
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
        });
    }

    public function count($regex)
    {
        $this->router->group('monki', function () use ($regex) {
            $this->router->state(
                'monki-count',
                new Regex($regex),
                function ($table) {
                    return new Endpoint\Item\Cnt($this->adapter, $table);
                }
            );
        });
    }

    public function item($regex)
    {
        $this->router->group('monki', function () use ($regex) {
            $this->router->state(
                'monki-item',
                new Regex($regex, ['GET', 'POST']),
                function ($table, $id, $VERB) {
                    if ($VERB == 'POST') {
                        $controller = new Endpoint\Item\Controller($this->adapter);
                        if (!isset($_POST['action'])) {
                            $_POST['action'] = 'update';
                        }
                        if (method_exists($controller, $_POST['action'])) {
                            $controller->{$_POST['action']}($table, $id);
                        }
                    }
                    return new Endpoint\Item\View($this->adapter, $table, $id);
                }
            );
        });
    }
}

