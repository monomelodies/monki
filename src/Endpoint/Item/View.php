<?php

namespace Monki\Endpoint\Item;

use Improse\Json;
use PDO;
use PDOException;
use Dabble\Query\Where;

class View extends Json
{
    protected $adapter;
    protected $table;
    protected $id;

    public function __construct(PDO $adapter, $table, $id)
    {
        $this->adapter = $adapter;
        $this->table = $table;
        $this->id = $id;
    }

    public function __invoke(array $__viewdata = [])
    {
        $where = new Where(['id' => $this->id]);
        $stmt = $this->adapter->prepare(sprintf(
            "SELECT * FROM %s WHERE %s",
            $this->table,
            $where
        ));
        try {
            $stmt->execute($where->getBindings());
            $__viewdata = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
        }
        return parent::__invoke($__viewdata);
    }
}

