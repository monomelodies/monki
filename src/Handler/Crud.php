<?php

namespace Monomelodies\Monki\Handler;

use Zend\Diactoros\Response\EmptyResponse;
use Monomelodies\Monki\Response\JsonResponse;

abstract class Crud
{
    protected function jsonResponse($data, $code = 200)
    {
        return new JsonResponse($data, $code);
    }

    protected function emptyResponse($code = 200)
    {
        return new EmptyResponse($code);
    }
}

