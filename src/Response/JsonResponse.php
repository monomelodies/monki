<?php

namespace Monki\Response;

use Zend\Diactoros\Response\HtmlResponse;

class JsonResponse extends HtmlResponse
{
    public function __construct($data, $status = 200, array $headers = [])
    {
        $json = json_encode($data);
        $headers['content-type'] = 'application/json; charset=utf-8';
        parent::__construct($json, $status, $headers);
    }
}

