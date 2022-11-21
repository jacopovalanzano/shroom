<?php

namespace Shroom\Session\Drivers;

use RuntimeException;

/**
 * Class ApcSession
 *
 * This class uses APC, or APCu if available, to store the session data.
 * See https://www.php.net/manual/en/book.apcu.php
 *
 * Implements \SessionHandler and is compatible with the PHP function "session_set_save_handler".
 *
 * @author Jacopo Valanzano
 * @package Shroom\Session\Drivers
 * @license MIT
 */
class ApcSession implements \SessionHandlerInterface
{

    /**
     * The name of the APC opcode(=driver) used.
     *
     * @var string
     */
    private $apc_driver;

    /**
     * ApcSession constructor.
     */
    public function __construct()
    {
        // Choose available driver
        \function_exists("apcu_fetch") ? $this->apc_driver = "apcu" : $this->apc_driver = "apc";
    }

    /**
     * Checks if the APC extension is available when the session is starting. If APC is not available, an
     * exception is thrown.
     *
     * @param string $savePath
     * @param string $sessionName
     * @return bool
     * @throws RuntimeException
     */
    public function open($savePath, $sessionName)
    {
        if (\function_exists("apc_fetch") || \function_exists("apcu_fetch")) {
            return true;
        }

        throw new RuntimeException(\get_called_class() . " No APCu or APC module available.");
    }

    /**
     * Closes the session.
     * This method is needed as fallback for the SessionHandler.
     *
     * @return bool
     */
    public function close():bool
    {
        return true;
    }

    /**
     * Reads the entire session of the current client.
     * Type-hint "apc_fetch" to ensure a string is returned (even if empty).
     *
     * @param string $sessionId
     * @return string
     */
    public function read($sessionId):string
    {
        return (string)$this->apc_fetch($sessionId);
    }

    /**
     * Writes the session data to file.
     *
     * @param string $sessionId
     * @param string $data
     * @return bool|mixed
     */
    public function write($sessionId, $data)
    {
        // Build the apc_sess_list entry. This keeps a list of all the current (active)
        // sessions with timestamps and is needed to keep track of the sessions that
        // have expired and needs to be garbage-collected.
        $session = [
            "session_id" => $sessionId,
            "last_update" => \time()
        ];

        // Gets a copy of the attributes of each session from the apc_sess_list
        $sessions = $this->apc_fetch("apc_sess_list");

        // Add the current session to the apc_sess_list ($sessions).
        $sessions[] = $session;

        // Updates the session list
        $this->apc_store("apc_sess_list", $sessions);

        $this->apc_store($sessionId, $data);

        return true;
    }

    /**
     * Deletes the session by id.
     *
     * @param string $sessionId
     * @return bool|string
     */
    public function destroy($sessionId)
    {
        return $this->apc_delete($sessionId);
    }

    /**
     * Garbage collector. Deletes expired sessions.
     *
     * @param int $maxLifetime
     * @return bool
     */
    public function gc($maxLifetime):bool
    {
        // Retrieves a list of all the existing sessions
        $sessions = $this->apc_fetch("apc_sess_list");

        // For each session in list
        foreach ($sessions as $key => $session) {
            // If the session time is expired ( reached session life + max lifetime )...
            if($session["last_update"] + $maxLifetime <= \time()) {
                // ...destroy the session and unset the apc_sess_list entry
                // which is updated at the end of the loop.
                $this->destroy($session["session_id"]);
                unset($sessions[$key]);
            }
        }

        // *Updates the session list. "apc_store" is used to overwrite(=update) the "apc_sess_list".
        // *see above
        $this->apc_store("apc_sess_list", $sessions);

        return true;
    }

    /**
     * Stores an entry in the APC store (replacing existing ones).
     *
     * @param $key
     * @param $var
     * @param int $ttl
     * @return mixed
     */
    protected function apc_store($key, $var, int $ttl = 0)
    {
        $apc_store = $this->apc_driver."_store";
        return $apc_store($key, $var, $ttl);
    }

    /**
     * Stores an entry in the APC store, if the key does not exist.
     *
     * @param $key
     * @param $var
     * @param int $ttl
     * @return mixed
     */
    protected function apc_add($key, $var, int $ttl = 0)
    {
        $apc_add = $this->apc_driver."_add";
        return $apc_add($key, $var, $ttl);
    }

    /**
     * Returns an entry from the APC storage.
     *
     * @param $key
     * @param null $success
     * @return mixed
     */
    protected function apc_fetch($key, $success = null)
    {
        $apc_fetch = $this->apc_driver."_fetch";
        return $apc_fetch($key, $success);
    }

    /**
     * Deletes an entry from the APC storage.
     *
     * @param $sessionId
     * @return mixed
     */
    protected function apc_delete($sessionId)
    {
        $apc_delete = $this->apc_driver."_delete";
        return $apc_delete($sessionId);
    }
}
