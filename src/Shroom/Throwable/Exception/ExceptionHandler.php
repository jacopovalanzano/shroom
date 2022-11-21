<?php

namespace Shroom\Throwable\Exception;

use Shroom\Support\Attempt;

/**
 * Interface ExceptionHandler
 *
 * @author Jacopo Valanzano
 * @package Shroom\Throwable\Exception
 * @license MIT
 */
trait ExceptionHandler
{

    /**
     * ExceptionHandler constructor.
     *
     * @param string|null $message
     * @param int $code
     * @param \Throwable $previous
     */
    public function __construct(string $message, int $code, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Echoes out the exception trace and halts the compiler.
     *
     * @param string|null $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function throw(string $name, string $message = null, int $code = 0, \Throwable $previous = null)
    {
        $e = new $this($message, $code, $previous);

        // "$ofCaller" represents the caller (file & line) of this method.
        $ofCaller = [];
        $ofCaller["line"] = Attempt::getInstance()->logLine();
        $ofCaller["file"] = Attempt::getInstance()->logFile();

        echo
            '<br />'.PHP_EOL.'<b>Fatal error</b>: Uncaught '.
            $name . ': ' . $message.
            ' in '.
            $ofCaller["file"].
            ':'.$ofCaller["line"].
            PHP_EOL.'Stack trace:'.PHP_EOL.
            $e->getTraceAsString();

        exit(); // Halts compiler!
    }

    /**
     * @return string
     */
    public function toString()
    {
        return parent::__toString();
    }

}
