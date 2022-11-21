<?php

namespace Shroom\Session;

use Shroom\Throwable\Exception\Logic\BadMethodCallException;
use Shroom\Throwable\Exception\Logic\DoingItWrongException;
use Shroom\Throwable\Exception\Logic\MethodNotFoundException;

/**
 * Class SessionHandler
 *
 * Handles the current session and provides overloading.
 * This class can be extended by other session handlers, for example a UserSession class.
 *
 * @author Jacopo Valanzano
 * @package Shroom\Session
 * @license MIT
 */
class SessionHandler extends SessionManager
{

    /**
     * The default driver name.
     *
     * @var string $defaultDriverName Inherited from SessionManager
     */

    /**
     * The driver (name) used during the session transaction.
     *
     * @var string $currentDriverName Inherited from SessionManager
     */

    /**
     * The available drivers.
     *
     * @var array $driver Inherited from SessionManager
     */

    /**
     * Sets the current driver and retrieves the driver instance.
     *
     * @param string|null $name
     * @return mixed|\Shroom\Session\SessionDriverWrapper
     * @throws BadMethodCallException
     */
    public function driver(string $name = null)
    {
        return parent::driver($name);
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
     * The current driver (name) being used for transactions.
     *
     * @return string
     */
    public function getCurrentDriver()
    {
        return $this->currentDriverName;
    }

    /**
     * Sets the default driver.
     *
     * @param string $name
     * @param \SessionHandlerInterface $driver
     * @return \SessionHandlerInterface
     */
    public function setDefaultDriver(\SessionHandlerInterface $driver, $name = "default")
    {
        $this->defaultDriverName = $name;

        return $this->driver[$name] = new SessionDriverWrapper($driver);
    }

    /**
     * Sets a new driver.
     *
     * @param string $name
     * @param \SessionHandlerInterface $driver
     * @return \SessionHandlerInterface
     * @throws DoingItWrongException
     */
    public function setNewDriver(string $name, \SessionHandlerInterface $driver)
    {
        if(isset($this->driver[$name])) {
            throw new DoingItWrongException(\get_called_class()." an entry for the Session driver ['$name'] already exists.");
        }

        return $this->driver[$name] = new SessionDriverWrapper($driver);
    }

    /**
     * {@inheritdoc }
     */
    public function start(array $options = []):bool
    {
        return parent::start($options);
    }

    /**
     * {@inheritdoc }
     */
    public function destroy(string $sessionId = null):bool
    {
        $sessionId = $sessionId ?: parent::getId();

        return parent::destroy($sessionId);
    }

    /**
     * {@inheritdoc }
     */
    public function invalidate()
    {
        return parent::invalidate();
    }

    /**
     * {@inheritdoc }
     */
    public function regenerate($delete_old_session = false)
    {
        return parent::regenerate($delete_old_session);
    }

    /**
     * {@inheritdoc }
     */
    public function clear()
    {
        return parent::clear();
    }

    /**
     * {@inheritdoc }
     */
    public function set(string $key, $value)
    {
        return parent::set($key, $value);
    }

    /**
     * {@inheritdoc }
     */
    public function forget(string $key)
    {
        return parent::forget($key);
    }

    /**
     * {@inheritdoc }
     */
    public function get(string $key)
    {
        return parent::get($key);
    }

    /**
     * {@inheritdoc }
     */
    public function add(string $key, $value)
    {
        return parent::add($key, $value);
    }

    /**
     * {@inheritdoc }
     */
    public function has(string $key)
    {
        return parent::has($key);
    }

    /**
     * {@inheritdoc }
     */
    public function getId()
    {
        return parent::getId();
    }

    /**
     * {@inheritdoc }
     */
    public function setId(string $sessionId)
    {
        return parent::setId($sessionId);
    }

    /**
     * {@inheritdoc }
     */
    public function getName()
    {
        return parent::getName();
    }

    /**
     * {@inheritdoc }
     */
    public function setName(string $sessionName)
    {
        return parent::setName($sessionName);
    }

    /**
     * Checks if the session was started.
     *
     * @return bool
     */
    public function isStarted()
    {
        return $this->sessionStatus() === \PHP_SESSION_ACTIVE;
    }

    /**
     * Starts a session.
     *
     * @param array $options
     * @return bool
     */
    public function sessionStart(array $options = [])
    {
        return parent::start($options);
    }

    /**
     * Destroys the current session.
     *
     * @param $sessionId
     * @return bool
     */
    public function sessionDestroy($sessionId)
    {
        return parent::destroy($sessionId);
    }

    /**
     * Regenerates the session id. This process implies the old session is erased.
     *
     * @return SessionHandler
     */
    public function sessionRegenerateId($delete_old_session = false)
    {
        return parent::regenerate($delete_old_session); // "true" deletes the old session parameters
    }

    /**
     * @return bool
     */
    public function sessionUnset()
    {
        return parent::clear();
    }

    /**
     * Writes the session data to storage and closes the session.
     *
     * @return bool
     * @throws BadMethodCallException
     */
    public function sessionClose()
    {
        \session_write_close();
        return $this->close();
    }

    /**
     * Returns the current status of the session.
     *
     * @return bool
     */
    public function sessionStatus()
    {
        return parent::$sessionState;
    }

    /**
     * Stores data to session.
     *
     * @param $name
     * @param $value
     * @throws BadMethodCallException
     */
    public function __set($name, $value)
    {
        $this->driver()->set($name, $value);
    }

    /**
     * Retrieves data from session.
     *
     * @param $name
     * @return mixed
     * @throws BadMethodCallException|\Shroom\Throwable\Exception\Logic\InvalidArgumentException
     */
    public function __get($name)
    {
        return $this->driver()->get($name);
    }

    /**
     * @param $name
     * @return bool
     * @throws BadMethodCallException
     */
    public function __isset($name)
    {
        return $this->driver()->has($name);
    }

    /**
     * @param $name
     * @throws BadMethodCallException
     */
    public function __unset($name)
    {
        $this->driver()->forget($name);
    }

    /**
     * @throws MethodNotFoundException
     */
    public function __call($method, $parameters)
    {
        if(\method_exists(self::$instance, $method)) {
            return \call_user_func_array([self::$instance, $method], $parameters);
        }

        throw new MethodNotFoundException(get_called_class()." does not have a method '$method'");
    }
}
