<?php

namespace Monki\Endpoint\Item;

use Improse\Json;
use PDO;
use PDOException;

class Cnt extends Json
{
    protected $adapter;
    protected $table;

    public function __construct(PDO $adapter, $table)
    {
        $this->adapter = $adapter;
        $this->table = $table;
    }

    public function __invoke(array $__viewdata = [])
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
            $__viewdata += ['count' => $stmt->fetchColumn()];
        } catch (PDOException $e) {
        }
        return parent::__invoke($__viewdata);
    }
}

