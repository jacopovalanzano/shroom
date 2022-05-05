<?php

namespace Shroom\Throwable\Exception;

/**
 * Interface ExceptionInterface
 *
 * @author Jacopo Valanzano
 * @package Shroom\Throwable\Exception
 * @license MIT
 */
interface ExceptionInterface extends \Throwable
{
    /* Protected methods inherited from Exception class */

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @return int
     */
    public function getCode();

    /**
     * @return string
     */
    public function getFile();

    /**
     * @return int
     */
    public function getLine();

    /**
     * @return array
     */
    public function getTrace();

    /**
     * @return string
     */
    public function getTraceAsString();

    /**
     * @return ?\Throwable
     */
    public function getPrevious();

    /**
     * @return string
     */
    public function __toString();

    /**
     * ExceptionInterface constructor.
     *
     * @param string|null $message
     * @param int $code
     * @param \Throwable $previous
     *
     * public function __construct(string $message = null, int $code = 0, \Throwable $previous);
     */
}
