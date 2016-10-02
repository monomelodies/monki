<?php

namespace Monomelodies\Monki;

use StdClass;
use PDO;
use PDOException;
use ReflectionClass;
use ReflectionMethod;
use Monolyth\Reroute\Router;
use Monomelodies\Monki\Endpoint\Item;
use Monomelodies\Monki\Endpoint\Browse;
use Monomelodies\Monki\Response\JsonResponse;
use Psr\Http\Message\RequestInterface;
use Zend\Diactoros\Response\EmptyResponse;
use League\Pipeline\StageInterface;
use zpt\anno\Annotations;

/**
 * Main Api class. This is what you'll usually work with.
 */
class Api implements StageInterface
{
    /**
     * @var Reroute\Router
     * Monki uses Reroute internally to resolve URLs. This has no bearing on
     * your own routing solution.
     */
    protected $router;

    /**
     * Constructor. Pass in the base URL your API should live under (e.g.
     * `"/api/"`).
     *
     * @param string $url Optional base URL. Defaults to '/'.
     */
    public function __construct($url = '/')
    {
        $this->router = new Router($url);
    }

    public function crud($url, $param, Handler\Crud $handler)
    {
        $router = $this->router->when($url);
        $reflection = new ReflectionClass($handler);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $annotations = new Annotations($method);
            $uri = $url;
            if (isset($annotations['url'])) {
                $uri .= $annotations['url'];
            } else {
                $uri .= $this->defaultUrlSuffixForAction($method->name);
            }
            $subrouter = $router->when($uri);
            $httpMethod = isset($annotations['method']) ?
                strtolower($annotations['method']) :
                $this->defaultHttpMethodForAction($method->name);
            if ($httpMethod == 'get') {
                $httpMethod = 'then';
            }
            call_user_func(
                [$uri == $url ? $router : $router, $httpMethod],
                [$handler, $method->name]
            );
        }
        return $router;
    }

    /**
     * Proxy to the internal `when` method of the Reroute router. Use this to
     * specify custom routes/responses for your API.
     *
     * @param string $url The URL to intercept.
     * @param callable $validate Optional validation pipeline.
     * @return Reroute\Router A Reroute router.
     * @see Reroute\Router::when
     */
    public function when($url, callable $validate = null)
    {
        $validate = $this->validate($validate);
        return $this->router->when($url)->pipe($validate);
    }

    /**
     * Proxy to the `pipe` method of the Reroute router.
     *
     * @param callable $callback The callback to pipe.
     * @return Reroute\Router A Reroute router.
     * @see Reroute\Router::pipe
     */
    public function pipe(callable $callback)
    {
        return $this->router->pipe($callback);
    }

    /**
     * Wrap the validation callable in a Monki\Stage object to respect the
     * League\Pipeline\StageInterface contract.
     *
     * @param callable $validate Optional callback used to check if current user
     *  has access to this feature.
     * @return Monki\Stage The callable wrapped in a Stage object.
     */
    protected function validate(callable $validate = null)
    {
        if (!isset($validate)) {
            $validate = function ($payload) { return $payload; };
        }
        return new Stage($validate);
    }

    /**
     * Process the payload. A front to __invoke.
     *
     * @param mixed $payload
     * @return mixed Whatever our router comes up with.
     */
    public function process($payload = null)
    {
        return $this($payload);
    }

    /**
     * Process the payload. If it is already an instance of RequestInterface, we
     * assume the pipeline was already resolved. Otherwise delegate control to
     * the internal router.
     *
     * @param mixed $payload
     * @return mixed Whatever our router comes up with.
     */
    public function __invoke($payload = null)
    {
        if (isset($payload) && !($payload instanceof RequestInterface)) {
            return $payload;
        }
        return $this->router->__invoke($payload);
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
                return '';
        }
    }
}

