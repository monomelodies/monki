<?php

namespace Api\Table;

use Disclosure\Injector;
use Disclosure\Container;
use Improse\Json;
use Dabble\Query\SelectException;
use Dabble\Adapter;

class View extends Json
{
    use Injector;

    protected $adapter;
    protected $table;

    public function __construct($table)
    {
        $this->inject(function (Adapter $adapter) {});
        $this->table = $table;
    }

    public function __invoke(array $__viewdata = [])
    {
        try {
            $__viewdata += $this->adapter->fetchAll(
                $this->table,
                '*',
                isset($_GET['filter']) ?
                    json_decode($_GET['filter'], true) :
                    [],
                isset($_GET['options']) ?
                    json_decode($_GET['options'], true) :
                    []
            );
        } catch (SelectException $e) {
        }
        return parent::__invoke($__viewdata);
    }
}

