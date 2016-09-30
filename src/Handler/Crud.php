<?php

namespace Monomelodies\Monki\Handler;

use Zend\Diactoros\Response\EmptyResponse;
use Monomelodies\Monki\Response\JsonResponse;

abstract class Crud
{
    public function browse()
    {
        return $this->emptyResponse(400);
    }

    public function retrieve()
    {
        return $this->emptyResponse(400);
    }

    public function update()
    {
        return $this->emptyResponse(400);
    }

    public function delete()
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

