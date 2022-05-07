<?php


namespace Shroom\Session;

use RuntimeException;
use Shroom\Traits\Instantiable;

/**
 * Class AbstractSession
 *
 * A skeleton for PHP native session.
 *
 * @author Jacopo Valanzano
 * @package Shroom\Session
 * @license MIT
 */
abstract class AbstractSession
{

    /**
     * Makes the static class an instantiable singleton class.
     */
    use Instantiable;

    /**
     * Session disabled.
     */
     const _DISABLED = PHP_SESSION_DISABLED;

    /**
     * Session enabled - but none exists.
     */
     const _NONE = PHP_SESSION_NONE;

    /**
     * Session enabled and exists.
     */
     const _ACTIVE = PHP_SESSION_ACTIVE;

    /**
     * The state of the current session.
     *
     * @var bool|int
     */
    protected static $sessionState = false;

    /**
     * The current session name.
     *
     * @var string
     */
    protected static $staticSessionName;

    /**
     * The current session id.
     *
     * @var string
     */
    protected static $staticSessionId;

    /**
     * @var string
     */
    protected $sessionId;

    /**
     * @var string
     */
    protected $sessionName;

    /**
     * Retrieves the driver instance.
     *
     * @param string $name
     * @return mixed
     */
    abstract public function driver(string $name = null);

    /**
     * (Re)starts the session.
     *
     * @param array $options
     * @return bool
     * @throws RuntimeException
     */
    protected function start(array $options = []):bool
    {

        if ((self::$sessionState === false) || (self::$sessionState === 1 )) {

            // we'll intercept the native 'files' handler, but will equally work
            // with other internal native handlers like 'sqlite', 'memcache' or 'memcached'
            // provided by PHP's extension.
            ini_set('session.save_handler', 'files');

            // Make sure use_strict_mode is enabled.
            // use_strict_mode is mandatory for security reasons.
            ini_set('session.use_strict_mode', '1');

            // Instruct php to use Shroom session handler.
            session_set_save_handler (
                function($path, $sessionName) { return $this->driver()->open($path, $sessionName); },
                function () { return $this->driver()->close(); },
                function ($sessionId) { return $this->driver()->read($sessionId); },
                function ($sessionId, $data) { return $this->driver()->write($sessionId, $data); },
                function ($sessionId) { return $this->driver()->destroy($sessionId); },
                function ($maxLifetime) { return $this->driver()->gc($maxLifetime); },
                function ($ip = null, $timestamp = null, $prng = null, $rand = null) { return method_exists($this->driver(), "createSid") ? $this->driver()->createSid($ip, $timestamp, $prng, $rand) : $this->createSid($ip, $timestamp, $prng, $rand); }
            );

            $this->sessionId = session_id() ?: (
                $this->driver()->getId() ?? (
                (method_exists($this->driver(), "createSid") === true) ? $this->driver()->createSid() : $this->createSid()
                )
            );

            // Make sure driver session name is set
            $sessionName = $this->sessionName ?: ( $this->driver()->getName() ?? session_name() ) ?: "__SHROOM_DEFAULT_SESSION";

            $this->driver()->setName($sessionName);

            // Make sure driver session id is set
            $sessionId = $this->sessionId ?: ( $this->driver()->getId() ?? session_id() ) ?: ( $_COOKIE[$sessionName] ?? null );

            if(isset($sessionId)) {
                $this->driver()->setId($sessionId);
            }

            // Starts the session or throws an exception
            if($this->driver()->sessionStart(array_merge([
                    "name" => "__SHROOM_DEFAULT_SESSION"
                ], $options)) !== true) {
                throw new \RuntimeException("Error calling new Session.", 1);
            }

            $this->sessionId = $this->driver()->getId();

            // Set the session name
            self::$staticSessionName = $this->driver()->getName();

            // Set the session id
            self::$staticSessionId = $this->driver()->getId();
        } elseif ( self::$sessionState === 0 ) {
            throw new \RuntimeException("Session is disabled. Please enable session.", 1);
        }

        return ( self::$sessionState = session_status() ) === self::_ACTIVE;
    }

    /**
     * Destroys the current session and updates the $sessionState.
     *
     * @param string $sessionId
     * @return bool
     */
    protected function destroy(string $sessionId):bool
    {
        if($this->driver()->destroy($sessionId) === true) {
            self::$sessionState = self::_NONE;
            return true;
        }

        return false;
    }

    /**
     * Clears all session attributes and generates a new session id.
     *
     * @return bool
     */
    protected function invalidate()
    {
        return $this->clear()->regenerate();
    }

    /**
     *  Generates a new session id.
     *
     * @param bool $delete_old_session
     * @return $this
     */
    protected function regenerate(bool $delete_old_session)
    {
        $this->driver()->sessionRegenerateId($delete_old_session);

        return $this;
    }

    /**
     * Clears all session attributes. Must be executed before the destroying a session.
     *
     * @return mixed
     */
    protected function clear()
    {
        $this->driver()->sessionUnset();

        return $this;
    }

    /**
     * Sets a session key to a defined value.
     *
     * @param string $key
     * @param mixed $value
     * @return bool|void
     */
    protected function set(string $key, $value)
    {
        return $this->driver()->set($key, $value);
    }

    /**
     * Unsets a session key.php unset whole array
     *
     * @param string $key
     * @return bool|void
     */
    protected function forget(string $key)
    {
        return $this->driver()->forget($key);
    }

    /**
     * Returns the value of a session key.
     *
     * @param string $key
     * @return mixed
     * @throws \InvalidArgumentException
     */
    protected function get(string $key)
    {
        return $this->driver()->get($key);
    }

    /**
     * Adds a value to a session key.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function add(string $key, $value)
    {
       return $this->driver()->add($key, $value);
    }

    /**
     * Checks if a session key is set.
     *
     * @param string $key
     * @return bool
     */
    protected function has(string $key)
    {
        return $this->driver()->has($key);
    }

    /**
     * Returns the current session id.
     *
     * @return false|string
     */
    protected function getId()
    {
        return $this->driver()->getId();
    }

    /**
     * Sets the (new) session id.
     *
     * @param string $sessionId
     * @return false|string
     */
    protected function setId(string $sessionId)
    {
        return $this->driver()->setId($sessionId);
    }

    /**
     * Returns the current session name.
     *
     * @return false|string
     */
    protected function getName()
    {
        return $this->driver()->getName();
    }

    /**
     * Sets the new session name.
     * Returns the old session name on success.
     *
     * @param string $sessionName
     * @return mixed
     * @throws \Shroom\Throwable\Exception\Runtime\RuntimeException
     */
    protected function setName(string $sessionName)
    {
        if($this->driver()->isStarted()) {
            throw new \Shroom\Throwable\Exception\Runtime\RuntimeException("Cannot change session name when session is active");
        }

        return $this->driver()->setName($sessionName);
    }

    /**
     * Provides a standard way of generating pseudo-secure session ids.
     *
     * @param string|null $ip
     * @param int|null $timestamp
     * @param float|null $prng
     * @param string|null $rand
     * @return string
     */
    protected function createSid(string $ip = null, int $timestamp = null, float $prng = null, string $rand = null)
    {
        // The visitor (client/browser) IP, or null
        $ip = $ip ?: \Shroom\Support\Attempt::getInstance()->getBrowserIp();

        // The time of request
        $timestamp = $timestamp ?: time();

        // "Linear congruence generator" value
        $prng = $prng ?: lcg_value();

        // Pseudo-random value
        $rand = $rand ?: substr(str_shuffle(MD5(microtime())), 0, 10);

        // @todo Ensure uniqueness to avoid collisions?
        // Finally, return the (unique?) session id
        return md5($ip . $timestamp . $prng . $rand);
    }

    /**
     * Check if the session was started.
     *
     * @return bool
     */
    public function isStarted()
    {
        return $this->driver()->sessionStatus() === self::_ACTIVE;
    }

}

