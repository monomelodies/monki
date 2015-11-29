<?php

namespace Monki\Endpoint\Item;

use Zend\Diactoros\Response\HtmlResponse;

class View
{
    protected $item;

    public function __construct($item)
    {
        $this->item = $item;
    }

    public function __invoke()
    {
        return (new HtmlResponse(json_encode($this->item)))
            ->withHeader('Content-Type', 'application/json');
    }
}

