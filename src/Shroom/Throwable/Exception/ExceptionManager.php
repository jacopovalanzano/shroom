<?php

namespace Shroom\Throwable\Exception;

/**
 * Class ExceptionManager
 *
 * @author Jacopo Valanzano
 * @package Shroom\Throwable\Exception
 * @license MIT
 */
class ExceptionManager extends AbstractException
{
    public function __construct($message = null, $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code);

        $this->previous = $previous;

        $this->trace[] = "";
    }


    /**
     * @return string
     */
    public function getId()
    {
        return spl_object_hash((object)self::class);
    }

}
