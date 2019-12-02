<?php

namespace Monomelodies\Monki;

use StdClass;
use PDO;
use PDOException;
use ReflectionClass;
use ReflectionMethod;
use Monolyth\Reroute\Router;
use Monolyth\Reroute\State;
use zpt\anno\Annotations;

/**
 * Main Api class. This is what you'll usually work with.
 */
class Api
{
    /**
     * @var Reroute\Router
     *
     * Monki uses Reroute internally to resolve URLs. This has no bearing on
     * your own routing solution.
     */
    protected $router;

    /**
     * Constructor. Pass in a Router instance.
     *
     * @param Monolyth\Reroute\Router $router
     * @return void
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
        $this->stage = $this->router->when('/');
    }

    /**
     * Register CRUD operations for a handler.
     *
     * @param string $url Base URL (appended to API-wide base URL).
     * @param Monomelodies\Monki\Handler\Crud $handler
     * @return Monolyth\Reroute\State
     */
    public function crud(string $url, Handler\Crud $handler) : State
    {
        return $this->router->when($url, null, function ($router) use ($handler, $url) {
            $reflection = new ReflectionClass($handler);
            $stages = [];
            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->name{0} == '_') {
                    continue;
                }
                $annotations = new Annotations($method);
                $uri = '';
                if (isset($annotations['url'])) {
                    $uri = $annotations['url'];
                } else {
                    $uri = $this->defaultUrlSuffixForAction($method->name);
                }
                if ($uri && !isset($stages[$uri])) {
                    $stages[$uri] = $router->when($uri);
                }
                $httpMethod = strtolower(isset($annotations['method']) ?
                    $annotations['method'] :
                    $this->defaultHttpMethodForAction($method->name));
                call_user_func(
                    [$stages[$uri], $httpMethod],
                    [$handler, $method->name]
                );
            }
        })->pipe(...$this->stage->getPipeline());;
    }

    /**
     * Proxy to the `pipe` method of the underlying base stage.
     *
     * @param callable ...$callbacks The callbacks to pipe.
     * @return Monolyth\Reroute\Router A Reroute router.
     * @see Reroute\Router::pipe
     */
    public function pipe(callable ...$callbacks) : Router
    {
        return $this->stage->pipe(...$callbacks);
    }

    /**
     * Private helper to get default HTTP methods.
     *
     * @param string $methodName
     * @return string
     */
    private function defaultHttpMethodForAction(string $methodName) : string
    {
        switch ($methodName) {
            case 'create':
            case 'update':
                return 'POST';
            case 'delete':
                return 'DELETE';
            default:
                return 'GET';
        }
    }

    /**
     * Private helper to get default URL suffixes.
     *
     * @param string $methodName
     * @return string
     */
    private function defaultUrlSuffixForAction(string $methodName) : string
    {
        switch ($methodName) {
            case 'update':
            case 'retrieve':
            case 'delete':
                return '/:id/';
            default:
                return '/';
        }
    }
}

