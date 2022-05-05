<?php

declare(strict_types = 1);

namespace Shroom\Throwable\Exception\Runtime;

/**
 * Class HttpApiResponseException
 *
 * @author Jacopo Valanzano
 * @package Shroom\Throwable\Exception\Runtime
 * @license MIT
 */
class HttpApiResponseException extends HttpResponseException
{

    /**
     * @var string
     */
    protected $domain;

    /**
     * @var string
     */
    protected $location;

    /**
     * @var string
     */
    protected $locationType;

    /**
     * HttpApiResponseException constructor.
     *
     * @param int $statusCode
     * @param string $message
     * @param array $headers
     * @param \Throwable|null $previous
     * @param int $code
     */
    public function __construct(int $statusCode, string $message = "", array $headers = [], \Throwable $previous = null, int $code = 0)
    {
        parent::__construct($statusCode, $message, $headers, $previous, $code);
    }

    /**
     * @param string $domain
     */
    public function setDomain(string $domain)
    {
        $this->domain = $domain;
    }

    /**
     * @return mixed
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param $location
     */
    public function setLocation(string $location)
    {
        $this->location = $location;
    }

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param string $locationType
     */
    public function setLocationType(string $locationType)
    {
        $this->locationType = $locationType;
    }

    /**
     * @return mixed
     */
    public function getLocationType()
    {
        return $this->locationType;
    }

}
