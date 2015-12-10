<?php

namespace Monki;

use StdClass;
use PDO;
use PDOException;
use Reroute\Router;
use Monki\Endpoint\Item;
use Monki\Endpoint\Browse;
use Monki\Response\JsonResponse;
use Psr\Http\Message\RequestInterface;
use Zend\Diactoros\Response\EmptyResponse;
use League\Pipeline\StageInterface;

/**
 * For all posted data, check if a callback was defined.
 */
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

if (isset($_POST['data'])) {
    $recurse($_POST['data']);
}

/**
 * Main Api class. This is what you'll usually work with.
 */
class Api implements StageInterface
{
    /**
     * @var PDO
     * The PDO database adapter to use.
     */
    protected $adapter;

    /**
     * @var Reroute\Router
     * Monki uses Reroute internally to resolve URLs. This has no bearing on
     * your own routing solution.
     */
    protected $router;

    /**
     * @var array
     * Hash of route names and associated URLs. You're free to extend the Api
     * class and offer other options.
     */
    protected $routes = [
        'browse' => "/(?'table'\w+)/",
        'count' => "/(?'table'\w+)/count/",
        'item' => "/(?'table'\w+)/(?'id'\d+)/",
    ];

    /**
     * Constructor. Pass in a PDO database object and the base URL your API
     * should live under (e.g. `"/api/"`).
     *
     * @param PDO $adapter PDO database object.
     * @param string $url Optional base URL. Defaults to '/'.
     */
    public function __construct(PDO $adapter, $url = '/')
    {
        $this->adapter = $adapter;
        $this->router = new Router($url);
    }

    /**
     * Define a regex URL to match for a certain action. Out of the box Monki
     * supports 'browse', 'count' and 'item'; extending classes can define their
     * own actions.
     *
     * @param string $name Name of the action (e.g. 'browse' etc.).
     * @param string $route Regular expression to match. Note that the three
     *  default routes expect certain named parameters.
     * @return void
     */
    public function setRoute($name, $route)
    {
        $this->routes[$name] = $route;
    }

    /**
     * Proxy to the internal `when` method of the Reroute router. Use this to
     * specify custom routes/responses for your API.
     *
     * @param string url The URL to intercept.
     * @return Reroute\Router A Reroute router.
     * @see Reroute\Router::when
     */
    public function when($url)
    {
        return $this->router->when($url);
    }


    /**
     * Register 'browse' as a valid action for this API. Browsing is essentially
     * "SELECT * FROM {table} WHERE {condition} {options}".
     *
     * If the request is POST, it tries to create a new item in the specified
     * table. This requires `$_POST['action']` to contain `"create"` and
     * `$_POST['data']` to contain key/value pairs of the new entity. In this
     * case it will return either the new entity (as if `item` were called), or
     * an empty 500 response on failure.
     *
     * @param callable $validate Optional callback used to check if current user
     *  has access to this feature.
     * @return void
     */
    public function browse(callable $validate = null)
    {
        $validate = $this->validate($validate);
        $this->router
            ->when($this->routes['browse'])
            ->pipe($validate)
            ->then('monki-browse', function ($table) {
                return new Browse\View($this->adapter, $table);
            })
            ->post(function ($table, callable $GET) {
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
                        return new JsonResponse(new StdClass);
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
                return $GET;
            });
    }

    /**
     * Register 'count' as a valid action for this API. Counting is essentially
     * "SELECT COUNT(*) FROM {table} WHERE {condition} {options}".
     *
     * @param callable $validate Optional callback used to check if current user
     *  has access to this feature.
     * @return void
     */
    public function count(callable $validate = null)
    {
        $validate = $this->validate($validate);
        $this->router
            ->when($this->routes['count'])
            ->pipe($validate)
            ->then('monki-count', function ($table) {
                return new Item\Cnt($this->adapter, $table);
            });
    }

    /**
     * Register 'item' as a valid action for this API. The item action is
     * an operation depending on the request type and parameters passed.
     *
     * For GET, it simply returns the item (if found) identified by the `id` in
     * the URL.
     *
     * For POST, it checks if the `"action"` key exists. The action key can be
     * either:
     * - `"update"`: Update this item according to the data in the `"data"` key.
     *   Respond with the updated item.
     * - `"delete"`: Delete this item. Respond with an empty 204 response.
     *
     * @param callable $validate Optional callback used to check if current user
     *  has access to this feature.
     * @return void
     */
    public function item(callable $validate = null)
    {
        $validate = $this->validate($validate);
        $getitem = function ($table, $id) {
            static $stmt;
            if (!isset($stmt)) {
                $stmt = $this->adapter->prepare(sprintf(
                    "SELECT * FROM %s WHERE id = ?",
                    $table
                ));
            }
            try {
                $stmt->execute([$id]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $item = new EmptyResponse(404);
            }
            return $item;
        };
        $this->router->when($this->routes['item'])->pipe($validate)
            ->then('monki-item', function ($table, $id) use ($getitem) {
                $item = $getitem($table, $id);
                if (is_array($item)) {
                    $item = new JsonResponse($item);
                }
                return $item;
            })->post(function ($table, $id, callable $GET) use ($getitem) {
                $item = $getitem($table, $id);
                if (!is_array($item)) {
                    return $item;
                }
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
                    if ($_POST['action'] == 'delete') {
                        return new EmptyResponse(204);
                    }
                    return $GET;
                } else {
                    return new EmptyResponse(405);
                }
            });
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

