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
        $defaults = [
            '' => [
                'then' => 'browse',
                'post' => 'create',
            ],
            $param => [
                'then' => 'retrieve',
                'post' => 'update',
                'delete' => 'delete',
            ]
        ];
        $router = $this->router->when($url);
        foreach ($defaults as $suffix => $methods) {
            if ($suffix) {
                $subrouter = $router->when("$url$suffix");
            }
            foreach ($methods as $httpMethod => $method) {
                call_user_func([$suffix ? $subrouter : $router, $httpMethod], [$handler, $method]);
            }
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
}

