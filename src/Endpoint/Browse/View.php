<?php

namespace Monki\Endpoint\Browse;

use Improse\Json;
use Dabble\Query\Where;
use Dabble\Query\Options;
use PDO;
use PDOException;

class View extends Json
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
            $__viewdata += $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
        }
        return parent::__invoke($__viewdata);
    }
}

