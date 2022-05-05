<?php

namespace Shroom\Traits;

/**
 * Trait Instantiable
 *
 * A trait for instantiable classes.
 *
 * @author Jacopo Valanzano
 * @package Shroom\Traits
 * @license MIT
 */
trait Instantiable
{
    /**
     * The class singleton.
     *
     * @var $instance
     */
    private static $instance;

    /**
     * Returns a singleton instance of the class.
     *
     * @see https://stackoverflow.com/questions/5197300/new-self-vs-new-static
     * @return static
     */
    public static function getInstance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

}
