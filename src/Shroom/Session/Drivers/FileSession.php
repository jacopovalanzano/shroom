<?php

namespace Shroom\Session\Drivers;

use RuntimeException;

/**
 * Class FileSession
 *
 * Session handler (wrapper) class for storing session to files with the default PHP session handler.
 *
 * Implements \SessionHandler and is compatible with PHP's native "session_set_save_handler".
 *
 * @author Jacopo Valanzano
 * @package Shroom\Session\Drivers
 * @license MIT
 */
class FileSession implements \SessionHandlerInterface
{
    /**
     * The path used to save session files. This should be set in your php.ini
     *
     * @var string
     */
    private $path;

    /**
     * Opens/creates the directory where all the session files are stored.
     *
     * @param string $path
     * @param string $sessionName
     * @return bool
     * @throws RuntimeException
     */
    public function open($path, $sessionName):bool
    {
        $this->path = $path;
        if (!is_dir($this->path)) {
            if (!mkdir($concurrentDirectory = $this->path, 0777) && !is_dir($concurrentDirectory)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }

        return true;
    }

    /**
     * Closes the session. No further action is required, but can be extended.
     *
     * @return bool
     */
    public function close():bool
    {
        return true;
    }

    /**
     * Reads the entire session of the current browser.
     * Returns an empty string if fault occurs (false, null etc.).
     *
     * @param string $sessionId
     * @return string
     */
    public function read($sessionId):string
    {
        return (string)@file_get_contents("$this->path/tdr_sess_$sessionId");
    }

    /**
     * Writes the session to a file.
     *
     * @param string $sessionId
     * @param string $data
     * @return bool
     */
    public function write($sessionId, $data):bool
    {
        return file_put_contents("$this->path/tdr_sess_$sessionId", $data) !== false;
    }

    /**
     * Destroys the session by session id.
     *
     * @param string $sessionId
     * @return bool
     */
    public function destroy($sessionId):bool
    {
        $file = "$this->path/tdr_sess_$sessionId";

        if (is_file($file) === true) {
            unlink($file);
        }

        return true;
    }

    /**
     * Garbage-collect expired sessions.
     *
     * @param int $maxLifetime
     * @return bool
     */
    public function gc($maxLifetime):bool
    {
        foreach (glob("$this->path/tdr_sess_*") as $file) {
            if ((is_file($file) === true) && (filemtime($file) + $maxLifetime < time())) {
                unlink($file);
            }
        }

        return true;
    }

}
