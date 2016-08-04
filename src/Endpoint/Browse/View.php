<?php

namespace Monomelodies\Monki\Endpoint\Browse;

use Monolyth\Dabble\Query\Where;
use Monolyth\Dabble\Query\Options;
use Monolyth\Dabble\Query\Raw;
use PDO;
use PDOException;
use Monomelodies\Monki\Response\JsonResponse;
use Zend\Diactoros\Response\EmptyResponse;

/**
 * A view representing a list of items. Normally only used internally.
 */
class View
{
    /**
     * @var PDO
     * Database adapter.
     */
    protected $adapter;

    /**
     * @var string
     * The table to work on.
     */
    protected $table;

    /**
     * @param PDO $adapter Database adapter to use.
     * @param string $table The table to work on.
     */
    public function __construct(PDO $adapter, $table)
    {
        $this->adapter = $adapter;
        $this->table = $table;
    }

    /**
     * Invoke the view to actually perform getting data etc.
     *
     * @return Psr\Http\Message\ResponseInterface An HTTP response. It will be
     *  of the type Monki\Response\JsonResponse on success, or of the type
     *  Zend\Diactoros\Response\EmptyResponse(500) on failure.
     */
    public function __invoke()
    {
        $where = isset($_GET['filter']) ?
            new Where($this->filter($_GET['filter'])) :
            '1=1';
        $options = isset($_GET['options']) ?
            new Options(json_decode($_GET['options'], true)) :
            '';
        $stmt = $this->adapter->prepare(sprintf(
            "SELECT * FROM %s WHERE %s %s",
            $this->table,
            $where,
            $options
        ));
        $bindings = [];
        if (is_object($where)) {
            $bindings = array_merge($bindings, $where->getBindings());
        }
        if (is_object($options)) {
            $bindings = array_merge($bindings, $options->getBindings());
        }
        try {
            $stmt->execute($bindings);
            $out = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return new JsonResponse($out);
        } catch (PDOException $e) {
            return new EmptyResponse(500);
        }
    }

    /**
     * Internal method to recursively check for "raw" values in a filter.
     *
     * @param string $f Json_encoded filter string (typically
     *  `$_GET['filter']`).
     * @return array Decoded filter.
     */
    protected function filter($f)
    {
        $f = json_decode($f, true);
        
        $walker = function ($arr) use (&$walker) {
            foreach ($arr as $key => &$value) {
                if (is_array($value)) {
                    if (isset($value['raw'])) {
                        $value = new Raw($value['raw']);
                    } else {
                        $value = $walker($value);
                    }
                }
            }
            return $arr;
        };
        
        return $walker($f);
    }
}

