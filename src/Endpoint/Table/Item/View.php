<?php

namespace Api\Table\Item;

use Disclosure\Injector;
use Disclosure\Container;
use Improse\Json;
use Improse\Render\Html;
use Dabble\Query\SelectException;

class View extends Json
{
    use Injector;

    protected $adapter;
    protected $table;
    protected $id;

    public function __construct($table, $id)
    {
        $this->inject(function ($adapter) {});
        $this->table = $table;
        $this->id = compact('id');
    }

    public function __invoke(array $__viewdata = [])
    {
        try {
            $__viewdata += $this->adapter->fetch($this->table, '*', $this->id);
        } catch (SelectException $e) {
        }
        return parent::__invoke($__viewdata);
    }
}

