<?php

namespace Monki\Endpoint\Item;

use PDO;
use PDOException;
use Dabble\Query\Where;
use Monki\Response\JsonResponse;
use Zend\Diactoros\Response\EmptyResponse;

class Cnt
{
    protected $adapter;
    protected $table;

    public function __construct(PDO $adapter, $table)
    {
        $this->adapter = $adapter;
        $this->table = $table;
    }

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

