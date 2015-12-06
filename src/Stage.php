<?php

namespace Monki;

use League\Pipeline\StageInterface;

/**
 * Simple wrapper to satisfy StageInterface contract.
 */
class Stage implements StageInterface
{
    /**
     * @var callable
     * The callable we're wrapping.
     */
    private $callable;

    /**
     * @param callable $callable The callable to wrap.
     */
    public function __construct(callable $callable)
    {
        $this->callable = $callable;
    }

    /**
     * Invoke pipeline stage using $payload.
     *
     * @param mixed $payload The payload.
     * @return mixed Whatever the wrapped callable returns.
     */
    public function __invoke($payload)
    {
        return call_user_func($this->callable, $payload);
    }

    /**
     * Process payload. Just a front to __invoke.
     *
     * @param mixed $payload
     * @return mixed
     * @see Monki\Stage::__invoke
     */
    public function process($payload)
    {
        return $this($payload);
    }
}

