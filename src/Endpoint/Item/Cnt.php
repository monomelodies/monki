<?php

namespace Monki\Endpoint\Item;

use PDO;
use PDOException;
use Dabble\Query\Where;
use Monki\Response\JsonResponse;
use Zend\Diactoros\Response\EmptyResponse;

/**
 * Count the number of items in the specified table, optionally filotered.
 * Useful for stuff like pagination.
 *
 * Note: COUNT(*) is a slooooooow operation on large tables. Use with caution.
 */
class Cnt
{
    /**
     * @var PDO
     * The database adapter.
     */
    protected $adapter;

    /**
     * @var string
     * Table name to work on.
     */
    protected $table;

    /**
     * @param PDO $adapter The database adapter.
     * @param string $table Table name to work on.
     */
    public function __construct(PDO $adapter, $table)
    {
        $this->adapter = $adapter;
        $this->table = $table;
    }

    /**
     * Invoke the view.
     *
     * @return Psr\Http\Message\ReponseInterface Either a
     *  Monki\Reponse\JsonResponse containing an object with a "count" key on
     *  success, or a Zend\Diactoros\Response\EmptyReponse(500) on failure.
     */
    public function __invoke()
    {
        $where = isset($_GET['filter']) ?
            new Where(json_decode($_GET['filter'], true)) :
            '1=1';
        $stmt = $this->adapter->prepare(sprintf(
            "SELECT COUNT(*) FROM %s WHERE %s",
            $this->table,
            $where
        ));
        $bindings = is_object($where) ? $where->getBindings() : [];
        try {
            $stmt->execute($bindings);
            $data = ['count' => $stmt->fetchColumn()];
            return new JsonResponse($data);
        } catch (PDOException $e) {
            return new EmptyResponse(500);
        }
    }
}

