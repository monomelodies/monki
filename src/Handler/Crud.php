<?php

namespace Monomelodies\Monki\Handler;

use Laminas\Diactoros\Response\EmptyResponse;
use Monomelodies\Monki\Response\JsonResponse;

abstract class Crud
{
    /**
     * Return a JsonResponse.
     *
     * @param mixed $data
     * @param int $code HTTP response code, defaults to 200 (OK).
     * @return Monomelodies\Monki\Response\JsonResponse
     */
    protected function jsonResponse($data, int $code = 200) : JsonResponse
    {
        return new JsonResponse($data, $code);
    }

    /**
     * Return an empty reponse.
     *
     * @param int $code HTTP response code, defaults to 200 (OK).
     * @return Laminas\Diactoros\Response\EmptyResponse
     */
    protected function emptyResponse(int $code = 200) : EmptyResponse
    {
        return new EmptyResponse($code);
    }
}

