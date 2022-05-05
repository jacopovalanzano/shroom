<?php

namespace Shroom\Throwable\Exception;

use \Exception;

/**
 * Class AbstractException
 *
 * This class extends the native Exception class with custom
 * ad-hoc exceptions.
 *
 * @author Jacopo Valanzano
 * @package Shroom\Throwable\Exception
 * @license MIT
 */
abstract class AbstractException extends Exception implements \Shroom\Throwable\Exception\ExceptionInterface
{

    /**
     * @var string
     */
    protected $message = '';

    /**
     * @var int
     */
    protected $code = 0;

    /**
     * @var string
     */
    protected $file;

    /**
     * @var int
     */
    protected $line;

    /**
     * @var string
     */
    protected $trace;

    /**
     * @var \Throwable|null
     */
    protected $previous;

    /**
     * AbstractException constructor.
     *
     * @param null $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct($message = null, int $code = 0, \Throwable $previous = null)
    {
        if ($message === null) {
            $message = "";
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return spl_object_hash(self::class);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)
            get_class($this) . " '{$this->message}' in {$this->file}:{$this->line}" .
            PHP_EOL . 'Stack trace:' . PHP_EOL .
            "{$this->getTraceAsString()}";
    }

}
