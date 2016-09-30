<?php

namespace Monomelodies\Monki\Handler;

use Zend\Diactoros\Response\EmptyResponse;
use Monomelodies\Monki\Response\JsonResponse;

abstract class Crud
{
    public function __call($fn, array $args)
    {
        return $this->emptyResponse(400);
    }

    protected function jsonResponse($data, $code = 200)
    {
        return new JsonResponse($data, $code);
    }

    protected function emptyResponse($code = 200)
    {
        return new EmptyResponse($code);
    }
}

