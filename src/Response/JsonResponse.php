<?php

namespace Monomelodies\Monki\Response;

use Laminas\Diactoros\Response\HtmlResponse;

/**
 * Simple extension to HtmlResponse to easily return Json responses.
 */
class JsonResponse extends HtmlResponse
{
    /**
     * @param mixed $data The data to respond with. Will be json_encoded first.
     * @param int $status Status code, typically 200 (the default).
     * @param array $headers Optional custom headers.
     */
    public function __construct($data, int $status = 200, array $headers = [])
    {
        $json = json_encode($data);
        $headers['content-type'] = 'application/json; charset=utf-8';
        $headers['access-control-allow-methods'] = 'GET, POST, OPTIONS';
        $headers['access-control-allow-credentials'] = 'true';
        $headers['access-control-allow-headers'] =
            "Content-Type, Authorization, Content-Length, X-Requested-With";
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            $headers['access-control-allow-origin'] = $_SERVER['HTTP_ORIGIN'];
        }
        parent::__construct($json, $status, $headers);
    }
}

