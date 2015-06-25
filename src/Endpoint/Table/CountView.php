<?php

namespace Api\Table;

use Disclosure\Injector;
use Disclosure\Container;
use Improse\Json;
use Dabble\Query\SelectException;

class CountView extends Json
{
    use Injector;

    protected $adapter;
    protected $table;

    public function __construct($table)
    {
        $this->inject(function ($adapter) {});
        $this->table = $table;
    }

    public function __invoke(array $__viewdata = [])
    {
        try {
            $__viewdata += ['count' => $this->adapter->count(
                $this->table,
                isset($_GET['filter']) ?
                    json_decode($_GET['filter'], true) :
                    []
            )];
        } catch (SelectException $e) {
        }
        return parent::__invoke($__viewdata);
    }
}

