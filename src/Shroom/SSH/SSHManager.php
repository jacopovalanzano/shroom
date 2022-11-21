<?php

namespace Shroom\SSH;

use Shroom\SSH\Connection\SSHConnection;
use Shroom\SSH\Throwable\OverwriteException;

/**
 * Class SSHManager
 *
 * @author Jacopo Valanzano
 * @package Shroom\SSH
 * @license MIT
 */
class SSHManager
{

    /**
     * Associative array; contains the session name and it's session object.
     *
     * @var array $sessionsStack
     */
    public $sessionsStack = array();

    /**
     * SSHManager Class.
     */
    public function __construct()
    {
        //
    }

    /**
     * Alias of 'newSSHSession'.
     */
    public function newSSH(
        $optionsOrHostname,
        string $username,
        string $password,
        int $port = 22,
        string $publicKeyFilePath = "",
        string $privateKeyFilePath = "",
        string $privateKeyPassword = "",
        string $fingerprint = "",
        int $bufferSize = 4096,
        bool $enableLog = true,
        string $logType = 'volatile',
        string $messageLogCommand = 'echo -e "[PHP-SSH]:\r\n"; '
    )
    {
        return $this->newSSHSession(
            $optionsOrHostname, $username, $password, $port, $publicKeyFilePath, $privateKeyFilePath, $privateKeyPassword, $fingerprint, $bufferSize, $enableLog, $logType, $messageLogCommand
        );
    }

    /**
     * Opens a new SSH session by using username and password. An empty password means no password is needed.
     *
     * @param string $hostname
     * @param string $username
     * @param string $password
     * @param int $port
     * @param string $sshServerFingerprint
     * @param string $sessionName
     * @param array $options
     * @return mixed
     * @throws OverwriteException
     * @throws Throwable\AuthenticationErrorException
     * @throws Throwable\ConnectionErrorException
     * @throws \Shroom\Throwable\Exception\Logic\InvalidArgumentException
     * @throws \Shroom\Throwable\Exception\Logic\LogicException
     */
    public function newSSHPasswordSession(
        string $hostname,
        string $username,
        string $password, // An empty string means no password.
        int $port = 22,
        string $sshServerFingerprint = "",
        string $sessionName = "",
        array $options = []
    ) {
        // Create new SSH session
        $sessionName = $this->addNewSession(
            $sessionName ?: \count($this->sessionsStack),
            $this->createSSHPasswordSession(
                $hostname,
                $username,
                $password, // An empty string means no password.
                $port,
                $sshServerFingerprint,
                $options
            )
        );

        return $this->getSession($sessionName);
    }

    /**
     * Opens a new SSH session by using a username and public key & private key.
     *
     * @param string $hostname
     * @param string $username
     * @param string $publicKeyFilePath
     * @param string $privateKeyFilePath
     * @param string $privateKeyPassword
     * @param int $port
     * @param string $sshServerFingerprint
     * @param string $sessionName
     * @return mixed
     * @throws OverwriteException
     * @throws Throwable\AuthenticationErrorException
     * @throws Throwable\ConnectionErrorException
     * @throws \Shroom\Throwable\Exception\Logic\InvalidArgumentException
     * @throws \Shroom\Throwable\Exception\Logic\LogicException
     */
    public function newSSHAsymmetricSession(
        string $hostname,
        string $username,
        string $publicKeyFilePath,
        string $privateKeyFilePath,
        string $privateKeyPassword,
        int $port = 22,
        string $sshServerFingerprint = "",
        string $sessionName = ""
    )
    {
        // Create new SSH session
        $sessionName = $this->addNewSession(
            $sessionName,
            $this->createSSHAsymmetricSession(
                $hostname,
                $username,
                $publicKeyFilePath,
                $privateKeyFilePath,
                $privateKeyPassword,
                $port,
                $sshServerFingerprint
            )
        );

        return $this->getSession($sessionName);
    }

    /**
     * @param string $cmd
     * @return array
     */
    public function executeAll(string $cmd)
    {
        $executionStack = [];

        foreach ($this->sessionsStack as $sessionName => $sessionObject) {
            $executionStack[$sessionName] = $sessionObject->executeCommand($cmd);
        }

        return $executionStack;
    }

    /**
     * @return array
     */
    public function listAllConnections()
    {
        $sessionStack = [];

        foreach ($this->sessionsStack as $sessionName => $sessionObject) {
            $sessionStack[$sessionName] = [
                "hostname" => $sessionObject->get("SSHConnectionHost"),
                "port" => $sessionObject->get("SSHConnectionPort"),
                "username" => $sessionObject->get("SSHAuthenticationUsername"),
                "password" => $sessionObject->get("SSHAuthenticationPassword"),
                "publicKey" => $sessionObject->get("SSHAuthenticationPublicKeyFilePath"),
                "privateKey" => $sessionObject->get("SSHAuthenticationPrivateKeyFilePath"),
                "privateKeyPassword" => $sessionObject->get("SSHPrivateKeyPassword"),
                "isConnected" => $sessionObject->isConnected()
            ];
        }

        return $sessionStack;
    }

    /**
     * @return array
     */
    public function getAllSessions()
    {
        return $this->sessionsStack;
    }

    /**
     * @param string $sessionName
     * @return void
     * @throws Throwable\ConnectionErrorException
     * @throws \Shroom\Throwable\Exception\Logic\LogicException
     */
    public function disconnect(string $sessionName)
    {
        $this->getSession($sessionName)->disconnect();
    }

    /**
     * Attempts to disconnect all SSH sessions.
     *
     * @return void
     */
    public function disconnectAll()
    {
        foreach ($this->sessionsStack as $sessionName => $sessionSSHObject) {
            $sessionSSHObject->disconnect();
        }
    }

    /**
     * @param string $sessionName
     * @return void
     * @throws Throwable\ConnectionErrorException
     * @throws \Shroom\Throwable\Exception\Logic\LogicException
     */
    public function destroy(string $sessionName)
    {
        $this->getSession($sessionName)->destroy();
        unset($this->sessionsStack[$sessionName]);
    }

    /**
     * Attempts to destroy all SSH sessions, and objects (unsets all objects).
     *
     * @return void
     */
    public function destroyAll()
    {
        foreach ($this->sessionsStack as $sessionName => $sessionSSHObject) {
            $sessionSSHObject->destroy();
            unset($this->sessionsStack[$sessionName]);
        }
    }


    /**
     * When the class is "destroyed", try to close every (open) SSH connection.
     */
    public function __destruct()
    {
        foreach ($this->sessionsStack as $sessionName => $sessionSSHObject) {
            $sessionSSHObject->destroy();
        }
    }

    /**
     * Adds a session to the stack, WITHOUT overriding a session with the same "sessionName".
     *
     * @param string $sessionName
     * @param SSHConnection $SSHConnection
     * @return string
     * @throws OverwriteException
     */
    protected function addNewSession(string $sessionName, \Shroom\SSH\Connection\SSHConnection $SSHConnection)
    {
        if($this->has($sessionName)) {
            throw new OverwriteException("A SSH session '$sessionName' already exists");
        }

        $this->add($sessionName, $SSHConnection);

        return $sessionName;
    }

    /**
     * Adds a session to the stack, WITHOUT overriding a session with the same "sessionName".
     *
     * @param string $sessionName
     * @param SSHConnection $SSHConnection
     * @return string
     * @throws OverwriteException
     */
    protected function addSession(string $sessionName, \Shroom\SSH\Connection\SSHConnection $SSHConnection)
    {
        return $this->addNewSession($sessionName, $SSHConnection);
    }

    /**
     * @param string $sessionName
     * @return mixed
     * @throws \Shroom\Throwable\Exception\Logic\LogicException
     */
    protected function getSession(string $sessionName)
    {
        if(! $this->has($sessionName)) {
            throw new \Shroom\Throwable\Exception\Logic\LogicException("No SSH session '$sessionName' exists");
        }

        return $this->sessionsStack[$sessionName];
    }

    /**
     * @param string $sessionName
     * @return bool
     */
    protected function hasSession(string $sessionName)
    {
        return isset($this->sessionsStack[$sessionName]);
    }

    /**
     * Adds a new session to the stack, OVERRIDING a session with the same "sessionName".
     *
     * @param string $sessionName
     * @param SSHConnection $SSHConnection
     * @return SSHConnection|null
     * @throws Throwable\ConnectionErrorException
     * @throws \Shroom\Throwable\Exception\Logic\LogicException
     */
    protected function setSession(string $sessionName, \Shroom\SSH\Connection\SSHConnection $SSHConnection)
    {
        // Attempt to destroy session before overriding
        if($this->has($sessionName)) {
            $this->destroy($sessionName);
        }

        return $this->set($sessionName, $SSHConnection);
    }

    /**
     * @param string $sessionName
     * @param \Shroom\SSH\Connection\SSHConnection $SSHConnection
     * @return string
     * @throws OverwriteException
     */
    protected function add(string $sessionName, \Shroom\SSH\Connection\SSHConnection $SSHConnection)
    {
        if($this->has($sessionName)) {
            throw new OverwriteException("A SSH session '$sessionName' already exists");
        }

        $this->sessionsStack[$sessionName] = $SSHConnection;

        return $sessionName;
    }

    /**
     * @param string $sessionName
     * @return mixed
     */
    protected function get(string $sessionName)
    {
        return $this->sessionsStack[$sessionName];
    }

    /**
     * @param string $sessionName
     * @return bool
     */
    protected function has(string $sessionName)
    {
        return isset($this->sessionsStack[$sessionName]);
    }

    /**
     * @param string $sessionName
     * @param SSHConnection $SSHConnection
     * @return SSHConnection
     */
    protected function set(string $sessionName, \Shroom\SSH\Connection\SSHConnection $SSHConnection)
    {
        return $this->sessionsStack[$sessionName] = $SSHConnection;
    }

    /**
     * @param string $hostname
     * @param string $username
     * @param string $password
     * @param int $port
     * @param string $sshServerFingerprint
     * @param array $options
     * @return SSHConnection
     * @throws Throwable\AuthenticationErrorException
     * @throws Throwable\ConnectionErrorException
     * @throws \Shroom\Throwable\Exception\Logic\InvalidArgumentException
     */
    protected function createSSHPasswordSession(
        string $hostname,
        string $username,
        string $password,
        int $port = 22,
        string $sshServerFingerprint = "",
        array $options = []
    ) {
        return new \Shroom\SSH\Connection\SSHConnection(
            $hostname,
            $username,
            $password,
            $port,
            $publicKeyFilePath = "",
            $privateKeyFilePath = "",
            $privateKeyPassword = "",
            $sshServerFingerprint,

            4096,
            true,
            "volatile",
            'echo -e "[PHP-SSH]:\r\n"; ',

            $options
        );
    }

    /**
     * @param string $hostname
     * @param string $username
     * @param string $publicKeyFilePath
     * @param string $privateKeyFilePath
     * @param string $privateKeyPassword
     * @param int $port
     * @param string $sshServerFingerprint
     * @return SSHConnection
     * @throws Throwable\AuthenticationErrorException
     * @throws Throwable\ConnectionErrorException
     * @throws \Shroom\Throwable\Exception\Logic\InvalidArgumentException
     */
    protected function createSSHAsymmetricSession(
        string $hostname,
        string $username,
        string $publicKeyFilePath,
        string $privateKeyFilePath,
        string $privateKeyPassword,
        int $port = 22,
        string $sshServerFingerprint = ""

    ) {
        return new \Shroom\SSH\Connection\SSHConnection(
            $hostname,
            $username,
            $password = "",
            $port,
            $publicKeyFilePath,
            $privateKeyFilePath,
            $privateKeyPassword,
            $sshServerFingerprint
        );
    }

    /**
     * @param string $sessionName
     * @param $optionsOrHostname
     * @param string $username
     * @param string $password
     * @param int $port
     * @param string $publicKeyFilePath
     * @param string $privateKeyFilePath
     * @param string $privateKeyPassword
     * @param string $fingerprint
     * @param int $bufferSize
     * @param bool $enableLog
     * @param string $logType
     * @param string $messageLogCommand
     * @return mixed
     * @throws OverwriteException
     * @throws Throwable\AuthenticationErrorException
     * @throws Throwable\ConnectionErrorException
     * @throws \Shroom\Throwable\Exception\Logic\InvalidArgumentException
     * @throws \Shroom\Throwable\Exception\Logic\LogicException
     */
    protected function newSSHSession(
        string $sessionName,
               $optionsOrHostname,
        string $username,
        string $password,
        int $port = 22,
        string $publicKeyFilePath = "",
        string $privateKeyFilePath = "",
        string $privateKeyPassword = "",
        string $fingerprint = "",
        int $bufferSize = 4096,
        bool $enableLog = true,
        string $logType = 'volatile',
        string $messageLogCommand = 'echo -e "[PHP-SSH]:\r\n"; '
    )
    {
        $sshSessionObject = new SSHConnection(
            $optionsOrHostname,
            $username,
            $password,
            $port,
            $publicKeyFilePath,
            $privateKeyFilePath,
            $privateKeyPassword,
            $fingerprint,
            $bufferSize,
            $enableLog,
            $logType,
            $messageLogCommand
        );

        // Create new SSH session
        $sessionName = $this->addNewSession(
            $sessionName ?: \count($this->sessionsStack),
            $sshSessionObject
        );

        return $this->getSession($sessionName);
    }
}

