<?php

declare(strict_types = 1);

namespace Shroom\Throwable\Exception\Runtime;

/**
 * Class NotFoundHttpException
 *
 * @author Jacopo Valanzano
 * @package Shroom\Throwable\Exception\Runtime
 * @license MIT
 */
class NotFoundHttpException extends HttpResponseException {

    /**
     * 404 not found
     */
    const statusCode = 404;

    public function __construct(string $message = '',  array $headers = [], Throwable $previous = null, int $code = 0)
    {
        $this->message = $message;
        $this->headers = $headers;
        $this->statusCode = self::statusCode;

        parent::__construct(self::statusCode, $message, $headers, $previous, $code);
    }
}
