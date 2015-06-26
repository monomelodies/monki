<?php

namespace Monki\Endpoint\Item;

use Improse\Json;

class View extends Json
{
    protected $item;

    public function __construct($item)
    {
        $this->item = $item;
    }

    public function __invoke(array $__viewdata = [])
    {
        return parent::__invoke($this->item);
    }
}

