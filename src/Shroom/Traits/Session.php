<?php

namespace Shroom\Traits;

use Shroom\Throwable\Exception\Logic\InvalidArgumentException;

/**
 * Trait Session
 *
 * A default session template.
 *
 * @author Jacopo Valanzano
 * @package Shroom\Traits
 * @license MIT
 */
trait Session
{

    /**
     * Sets a value with the associated key in the session storage.
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Removes a session parameter from the session globals.
     *
     * @param string $key
     * @return void
     */
    public function forget($key)
    {
        unset($_SESSION[$key]);
    }

    /**
     * Returns the session data associated with the given key.
     *
     * @param string $key
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function get($key)
    {

        if ($this->has($key)) {
            return $_SESSION[$key];
        }

        throw new InvalidArgumentException(get_called_class() . " tried to get the \$_SESSION['$key'] parameter. Try isset first.", 1974);
    }

    /**
     * Adds data to an existing session key.
     *
     * @param string $key
     * @param string $value
     */
    public function add($key, $value)
    {
        $_SESSION[$key][] = $value;
    }

    /**
     * Checks if the given (string) key exists among the session globals.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return ( isset($_SESSION[$key]) === true );
    }

    /**
     * Retrieves the session id.
     *
     * @return false|string
     */
    public function getId()
    {
        return \session_id();
    }

    /**
     * Sets/replaces the session id.
     *
     * @param string $id
     * @return false|string
     */
    public function setId($id)
    {
        return \session_id($id);
    }

    /**
     * Retrieves the session name.
     *
     * @return string
     */
    public function getName()
    {
        return \session_name();
    }

    /**
     * Sets the session name. Must be called before starting the session.
     *
     * @param string $name
     * @return false|string
     */
    public function setName($name = "__TODDLER_DEFAULT_SESSION")
    {
        return \session_name($name);
    }

    /**
     * Checks if the session is started.
     *
     * @return bool
     */
    public function isStarted()
    {
        return $this->sessionStatus() === PHP_SESSION_ACTIVE;
    }

    /**
     * Starts a (new) session.
     *
     * @param array $options
     * @return bool
     */
    public function sessionStart(array $options = [])
    {
        return \session_start($options);
    }

    /**
     * Destroys all the session data (including from storage, and the session id)
     *
     * @return bool
     */
    public function sessionDestroy()
    {
        return \session_destroy();
    }

    /**
     * Generates a new id for the session.
     *
     * @param bool $delete_old_session
     * @return bool
     */
    public function sessionRegenerateId($delete_old_session = false)
    {
        return \session_regenerate_id($delete_old_session); // "true" would delete the old session data
    }

    /**
     * Frees all the session globals (variables).
     *
     * @return bool
     */
    public function sessionUnset()
    {
        $_SESSION = array();

        return \session_unset();
    }

    /**
     * Closes the session and writes the session data to storage.
     *
     * @return bool
     */
    public function sessionClose()
    {
        return \session_write_close();
    }

    /**
     * Closes the session without writing to storage, but maintaining the data in the session global.
     *
     * @return bool
     */
    public function sessionDiscard()
    {
        return \session_abort();
    }

    /**
     * Returns the current session status.
     *
     * @return int
     */
    public function sessionStatus()
    {
        return \session_status();
    }

}
