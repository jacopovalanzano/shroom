<?php

namespace Shroom\Session;

use Shroom\Throwable\Exception\Logic\BadMethodCallException;
use Shroom\Throwable\Exception\Logic\MethodNotFoundException;

/**
 * Class SessionManager
 *
 * @author Jacopo Valanzano
 * @package Shroom\Session
 * @license MIT
 */
class SessionManager extends AbstractSession
{

    /**
     * The singleton of this class.
     *
     * Assigned by SessionManager Instantiable trait method "getInstance".
     *
     * @var SessionHandler
     */
     public static $instance;

    /**
     * The name of the default driver.
     *
     * @var string
     */
     protected $defaultDriverName;

    /**
     * The driver (name) used during the session transaction.
     *
     * @var string
     */
    protected $currentDriverName;

    /**
     * The session driver(s) used to store and retrieve session data.
     *
     * @var array
     */
     protected $driver;

    /**
     * Starts a (new) session.
     *
     * @return bool
     * @throws \RuntimeException
     */
    public function init()
    {
        return parent::getInstance()->start();
    }

    /**
     * Sets the current driver and retrieves the driver instance.
     *
     * @param string|null $name
     * @return mixed|\Shroom\Session\SessionDriverWrapper
     * @throws BadMethodCallException
     */
    public function driver(string $name = null)
    {
        // If there is a $name, use it. If not, use the $currentDriverName.
        // If the $currentDriverName is not set, use the $defaultDriverName.
        $this->currentDriverName = $name ?: $name = ( $this->currentDriverName ?: $this->getDefaultDriverName() );

        // If no driver of this type ($currentDriverName) exists, throw an exception.
        if(!isset($this->driver[$this->currentDriverName])) {
            throw new BadMethodCallException(static::class ." does not have a definition for the driver '$name'");
        }

        // Return the driver instance ( of SessionDriverWrapper containing the actual session driver )
        return $this->driver[$this->currentDriverName];
    }

    /**
     * Returns the default driver instance name.
     *
     * @return string
     */
    public function getDefaultDriverName()
    {
        return $this->defaultDriverName ?? "default";
    }

    /**
     * Returns an instance of this class.
     *
     * @return self
     */
    protected function instance()
    {
        return parent::getInstance();
    }

    /**
     * Starts the session with all available session drivers.
     */
    protected function initAll()
    {
        foreach ($this->getAllDrivers() as $driver) {
            $driver->start();
        }
    }

    /**
     * Returns an array containing all the session drivers.
     *
     * @return array
     */
    protected function getAllDrivers()
    {
        return $this->driver;
    }

    /**
     * This method is safe for use and is only here as support. The Session handles this process automatically.
     *
     * @return bool
     * @throws BadMethodCallException
     */
    protected function close()
    {
        return $this->driver()->close();
    }

    /**
     * This method is safe for use and is only here as support. The Session handles this process automatically.
     *
     * @param string|null $ip
     * @param int|null $timestamp
     * @param float|null $prng
     * @param string|null $rand
     * @return string
     * @throws BadMethodCallException
     */
    protected function createSid(string $ip = null, int $timestamp = null, float $prng = null, string $rand = null):string
    {
        if(\method_exists($this->driver(), "createSid")) {
            // Method should be "(string)" type-hinted
            return $this->driver()->createSid($ip, $timestamp, $prng, $rand);
        }

        // Calls AbstractSession::createSid
        // Method should be "(string)" type-hinted
        return parent::createSid($ip, $timestamp, $prng, $rand);
    }

    /**
     * This method is safe for use and is only here as support. The Session handles this process automatically.
     *
     * @param string $sessionId
     * @return bool
     */
    protected function destroy(string $sessionId):bool
    {
        // IMPORTANT: this ensures any driver writes the current session (data and id) to storage before
        // destroying.
        \session_write_close();

        return parent::destroy($sessionId);
    }

    /**
     * This method is safe for use and is only here as support. The Session handles this process automatically.
     *
     * @param int $maxLifetime
     * @return int|bool
     * @throws BadMethodCallException
     */
    protected function gc($maxLifetime = 86400)
    {
        return $this->driver()->gc($maxLifetime);
    }

    /**
     * This method is safe for use and only here as support. The Session handles this process automatically.
     *
     * @param string $path
     * @param string $sessionName
     * @return bool
     * @throws BadMethodCallException
     */
    protected function open(string $path, string $sessionName):bool
    {
        return $this->driver()->open($path, $sessionName);
    }

    /**
     * This method is safe for use and is only here as support. The Session handles this process automatically.
     *
     * @param string $sessionId
     * @return string|false
     * @throws BadMethodCallException
     */
    protected function read(string $sessionId)
    {
        return $this->driver()->read($sessionId);
    }

    /**
     * This method is safe for use and is only here as support. The Session handles this process automatically.
     *
     * @param string $sessionId
     * @param string $data
     * @return bool
     * @throws BadMethodCallException
     */
    protected function write(string $sessionId, string $data):bool
    {
        return $this->driver()->write($sessionId, $data);
    }

    /**
     * Closes the session with all available session drivers.
     */
    protected function closeAll()
    {
        foreach($this->getAllDrivers() as $driver) {
            $driver->close();
        }
    }

    /**
     * Destroys the session with all available session drivers.
     */
    protected function destroyAll():bool
    {
        foreach($this->getAllDrivers() as $driver) {
            $driver->destroy();
        }
    }

    /**
     * Garbage-collection with all available session drivers.
     *
     * @param int $maxLifetime
     */
    protected function gcAll($maxLifetime = 86400)
    {
        foreach($this->getAllDrivers() as $driver) {
            $driver->gc($maxLifetime);
        }
    }

    /**
     * Opens the session with all available session drivers.
     *
     * @param string $path
     * @param string $sessionName
     * @return bool
     */
    protected function openAll(string $path, string $sessionName):bool
    {
        foreach($this->getAllDrivers() as $driver) {
            $driver->open($path, $sessionName);
        }
    }

    /**
     * Reads from session with all available session drivers.
     *
     * @param string $sessionId
     * @return array
     */
    protected function readAll(string $sessionId)
    {
        $reads = [];

        foreach($this->getAllDrivers() as $driver) {
            $reads[] = $driver->read($sessionId);
        }

        return $reads;
    }

    /**
     * Writes to the session with all available session drivers.
     *
     * @param string $sessionId
     * @param string $data
     * @return bool
     */
    protected function writeAll(string $sessionId, string $data):bool
    {
        foreach($this->getAllDrivers() as $driver) {
            $driver->write($sessionId, $data);
        }
    }

    /**
     * Executes the methods of the driver(s).
     *
     * @param $method
     * @param $parameters
     * @return mixed
     * @throws MethodNotFoundException|BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        if(\method_exists($this->driver(), $method)) {
            return $this->driver()->{$method}(...$parameters);
        }

        throw new MethodNotFoundException(\get_called_class()." does not have a method '$method'");
    }
}
