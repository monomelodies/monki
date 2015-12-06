<?php

namespace Monki\Response;

use Zend\Diactoros\Response\HtmlResponse;

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
    public function __construct($data, $status = 200, array $headers = [])
    {
        $json = json_encode($data);
        $headers['content-type'] = 'application/json; charset=utf-8';
        parent::__construct($json, $status, $headers);
    }
}

