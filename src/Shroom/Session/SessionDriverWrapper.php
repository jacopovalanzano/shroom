<?php

namespace Shroom\Session;

use Shroom\Throwable\Exception\Logic\MethodNotFoundException;

/**
 * Class SessionDriverWrapper
 *
 * @author Jacopo Valanzano
 * @package Shroom\Session
 * @license MIT
 */
class SessionDriverWrapper implements \SessionHandlerInterface
{

    /**
     * Uses the Session trait for syntax support.
     */
    use \Shroom\Traits\Session;

    /**
     * @var \SessionHandlerInterface
     */
    protected $sessionDriver;

    /**
     * SessionDriverWrapper constructor.
     *
     * @param \SessionHandlerInterface $sessionDriver
     */
    public function __construct(\SessionHandlerInterface $sessionDriver)
    {
        $this->sessionDriver = $sessionDriver;
    }

    /**
     * @inheritDoc
     */
    public function close()
    {
        return $this->sessionDriver->close();
    }

    /**
     * @inheritDoc
     */
    public function destroy($sessionId)
    {
        return $this->sessionDriver->destroy($sessionId);
    }

    /**
     * @inheritDoc
     */
    public function gc($maxLifetime)
    {
        return $this->sessionDriver->gc($maxLifetime);
    }

    /**
     * @inheritDoc
     */
    public function open($path, $sessionName)
    {
        return $this->sessionDriver->open($path, $sessionName);
    }

    /**
     * @inheritDoc
     */
    public function read($sessionId)
    {
        return $this->sessionDriver->read($sessionId);
    }

    /**
     * @inheritDoc
     */
    public function write($sessionId, $data)
    {
        return $this->sessionDriver->write($sessionId, $data);
    }

    /**
     * @param $method
     * @param $args
     * @return mixed
     * @throws MethodNotFoundException
     */
    public function __call($method, $args)
    {
        if(\method_exists($this->sessionDriver, $method)) {
            return $this->sessionDriver->{$method}(...$args);
        }

        throw new MethodNotFoundException(\get_called_class()." does not have a method '$method'");
    }
}
