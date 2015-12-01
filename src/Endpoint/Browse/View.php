<?php

namespace Monki\Endpoint\Browse;

use Dabble\Query\Where;
use Dabble\Query\Options;
use Dabble\Query\Raw;
use PDO;
use PDOException;
use Monki\Response\JsonResponse;
use Zend\Diactoros\Response\EmptyResponse;

class View
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

