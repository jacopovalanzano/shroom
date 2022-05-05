<?php

declare(strict_types = 1);

namespace Shroom\Throwable\Exception\Runtime;

/**
 * Class HttpResponseException
 *
 * @author Jacopo Valanzano
 * @package Shroom\Throwable\Exception\Runtime
 * @license MIT
 */
class HttpResponseException extends RuntimeException
{

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var array
     */
    protected $headers;

    /**
     * HttpResponseException constructor.
     * @param int $statusCode
     * @param string|array|null $message
     * @param \Throwable|null $previous
     * @param array $headers
     * @param int $code
     */
    public function __construct(int $statusCode, string $message,  array $headers = [], \Throwable $previous = null, int $code = 0 )
    {

        $this->statusCode = $statusCode;
        $this->headers = $headers;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set response headers.
     *
     * @param array $headers Response headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

}
