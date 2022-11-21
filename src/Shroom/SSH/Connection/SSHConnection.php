<?php

namespace Shroom\SSH\Connection;

use \Exception;
use Shroom\SSH\Throwable\AuthenticationErrorException;
use Shroom\SSH\Throwable\ConnectionErrorException;
use Shroom\Throwable\Exception\Logic\InvalidArgumentException;

/**
 * Class SSHConnection
 *
 * @author Jacopo Valanzano
 * @package Shroom\SSH\Connection
 * @license MIT
 */
class SSHConnection
{

    /**
     * Hostname or IP.
     *
     * @var string
     */
    private $SSHConnectionHost; // = 'myserver.example.com' or '1.2.3.4';

    /**
     * @var int
     */
    private $SSHConnectionPort = 22;

    /**
     * The server fingerprint. This is kept as a string/value, not a path.
     *
     * @var string
     */
    private $SSHServerFingerprint = '';

    /**
     * Full path to the public key (optional).
     *
     * @var string
     */
    private $SSHAuthenticationPublicKeyFilePath = '/home/username/.ssh/id_rsa.pub';

    /**
     * Full path to the private key (optional).
     *
     * @var string
     */
    private $SSHAuthenticationPrivateKeyFilePath = '/home/username/.ssh/id_rsa';

    /**
     * The (optional) password for decrypting the Private Key.
     * A "null" value means that no password is needed.
     *
     * @var string|null
     */
    private $SSHPrivateKeyPassword;

    /**
     * @var string
     */
    private $SSHAuthenticationUsername;

    /**
     * The password used to authenticate the user (optional).
     *
     * @var string
     */
    private $SSHAuthenticationPassword = null;

    /**
     * The SSH session connection fingerprint. Needed to confirm server identity.
     *
     * @var string
     */
    private $fingerprint;

    /**
     * The size of the buffer; up to length number of bytes to read.
     *
     * @var int
     */
    private $bufferSize = 4096;

    /**
     * Whether or not to keep input/output commands log.
     *
     * @var bool
     */
    private $logEnabled = true;

    /**
     * The type of log (if enabled).
     * Available options are "volatile" or "file", or custom.
     *
     * @var string
     */
    private $logType = 'volatile';

    /**
     * A message that is always (echoed) printed out before a command.
     * Useful for logs.
     * The "$messageLogCommand" is always used if "$logEnabled" is set to true.
     *
     * @var string|null
     */
    private $messageLogCommand = 'echo -e "[PHP-SSH]:\r\n"; ';

    /**
     * Contains log and other log info/data.
     *
     * @var mixed
     */
    private $logStack;

    /**
     * The SSH session connection. The stream should be closed when the SSH session is exited.
     * This is a variable of type "PHP resource".
     *
     * @var resource
     */
    private $connection;

    /**
     * The SSH connection stream. It must be opened everytime input needs to be sent through the SSH
     * connection; and should be closed once the output is returned.
     * This is a variable of type "PHP resource".
     *
     * @var resource
     */
    private $stdoutStream;

    /**
     * Contains "methods" & "callbacks".
     *
     * @var array
     */
    private $options;

    /**
     * An SSH connection class: [ connection -> authentication ] -> ( stream -> command )
     *
     * Public Key & Private Key will always be preferred over password, if set.
     *
     * @param $optionsOrSSHAuthenticationHost
     * @param string $SSHConnectionUsername
     * @param string $SSHAuthenticationPassword
     * @param int $SSHConnectionPort
     * @param string $SSHAuthenticationPublicKeyFilePath
     * @param string $SSHAuthenticationPrivateKeyFilePath
     * @param string $SSHPrivateKeyPassword
     * @param string $SSHServerFingerprint
     * @param int $bufferSize
     * @param bool $enableLog
     * @param string $logType
     * @param string $messageLogCommand
     * @param array $options
     * @throws AuthenticationErrorException
     * @throws ConnectionErrorException
     * @throws InvalidArgumentException
     */
    public function __construct(
        $optionsOrSSHAuthenticationHost,
        string $SSHConnectionUsername = "",
        string $SSHAuthenticationPassword = "",
        int $SSHConnectionPort = 22,
        string $SSHAuthenticationPublicKeyFilePath = "",
        string $SSHAuthenticationPrivateKeyFilePath = "",
        string $SSHPrivateKeyPassword = "",
        string $SSHServerFingerprint = "",
        int $bufferSize = 4096,
        bool $enableLog = true,
        string $logType = 'volatile',
        string $messageLogCommand = 'echo -e "[PHP-SSH]:\r\n"; ',
        array $options = []
    )
    {

        // Initialize log stack
        $this->logStack["input"] = $this->logStack["output"] = [];

        return $this->createNewConnection(
            $optionsOrSSHAuthenticationHost,
            $SSHConnectionUsername,
            $SSHAuthenticationPassword,
            $SSHConnectionPort,
            $SSHAuthenticationPublicKeyFilePath,
            $SSHAuthenticationPrivateKeyFilePath,
            $SSHPrivateKeyPassword,
            $SSHServerFingerprint,
            $bufferSize,
            $enableLog,
            $logType,
            $messageLogCommand,
            $options
        );
    }

    /**
     * @param string|array $optionsOrHostname
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
     * @param array $options
     * @return $this
     * @throws AuthenticationErrorException
     * @throws ConnectionErrorException
     * @throws InvalidArgumentException
     */
    public function createNewConnection(
        $optionsOrHostname,
        string $username,
        string $password,
        int    $port = 22,
        string $publicKeyFilePath = "",
        string $privateKeyFilePath = "",
        string $privateKeyPassword = "",
        string $fingerprint = "",
        int    $bufferSize = 4096,
        bool   $enableLog = true,
        string $logType = 'volatile',
        string $messageLogCommand = 'echo -e "[PHP-SSH]:\r\n"; ',
        array $options = []
    )
    {
        // Destroy any previous connection
        $this->destroyConnection();

        // Parse & set new parameters
        if (is_array($optionsOrHostname)) {
            $this->parseNewConnectionOptions($optionsOrHostname);
        } else {
            $this->parseNewConnectionOptions([
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
            ]);
        }

        $this->parseOptions($options);

        return $this->connect();
    }

    /**
     * Alias of "executeCommand"
     *
     * @param string $SSHCommand
     * @return array
     * @throws ConnectionErrorException
     */
    public function cmd(string $SSHCommand)
    {
        return $this->executeCommand($SSHCommand);
    }

    /**
     * @param string $cmd
     * @return array
     * @throws ConnectionErrorException
     */
    public function exec(string $cmd)
    {
        return $this->executeCommand($cmd);
    }

    /**
     * Executes a SSH command.
     *
     * @param string $command
     * @return array
     * @throws ConnectionErrorException
     */
    public function executeCommand(string $command)
    {
        return $this->executeSecureShellCommand($command);
    }

    /**
     * Attempts to destroy the session and close the SSH connection.
     *
     * @return array
     * @throws ConnectionErrorException
     */
    public function disconnect()
    {
        return $this->disconnectConnection();
    }

    /**
     * Alias of "destroy".
     *
     * @return string
     * @throws ConnectionErrorException
     */
    public function forceDisconnect()
    {
        return $this->destroy();
    }

    /**
     * Try to destroy the session and force close the SSH connection.
     *
     * @return string
     * @throws ConnectionErrorException
     */
    public function destroy()
    {
        return $this->destroyConnection();
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        return $this->isResource($this->connection);
    }

    /**
     * Whether the SSHConnection is logging input/output.
     *
     * @return bool
     */
    public function isLogEnabled()
    {
        return $this->logEnabled === true;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getLog()
    {
        if($this->isLogEnabled() === true) {
            return $this->logStack;
        }

        throw new \Exception("Log not enabled.");
    }

    /**
     * @param int $newSize
     * @return void
     */
    public function setBufferSize(int $newSize)
    {
        $this->bufferSize = $newSize;
    }

    /**
     * @param string $newType
     * @return void
     */
    public function setMessageLogType(string $newType)
    {
        $this->logType = $newType;
    }

    /**
     * Sets a new command-log command.
     *
     * @param string $newCommand
     * @return void
     */
    public function setMessageLogCommand(string $newCommand)
    {
        $this->messageLogCommand = $newCommand;
    }

    /**
     * Enables/disables command-log.
     *
     * @param bool $newStatus
     * @return void
     */
    public function setLogStatus(bool $newStatus)
    {
        $this->logEnabled = $newStatus;
    }

    /**
     * @param string $newFingerprint
     * @return void
     */
    public function setFingerprint(string $newFingerprint)
    {
        $this->fingerprint = $newFingerprint;
    }

    /**
     * @param string $arg
     * @param $val
     * @return void
     */
    public function set(string $arg, $val)
    {
        $this->$arg = $val;
    }

    /**
     * @param string $arg
     * @return void
     */
    public function get(string $arg)
    {
        if ($this->has($arg)) {
            return $this->$arg;
        }
    }

    /**
     * @param string $arg
     * @return bool
     */
    public function has(string $arg)
    {
        if (isset($this->$arg)) {
            return !empty($this->$arg);
        }

        return false;
    }

    /**
     * @param string $arg
     * @param $val
     * @return void
     * @throws InvalidArgumentException
     */
    public function add(string $arg, $val)
    {

        if ($this->has($arg)) {
            throw new InvalidArgumentException("'$arg' is already set.");
        }

        $this->set($arg, $val);
    }

    /**
     * @param array $options
     * @return void
     */
    public function parseOptions(array $options)
    {

        foreach ($options as $option => $value) {
            switch ($option) {

                case "logMessage":
                case "logCommand":
                    $this->setMessageLogCommand($value);
                    break;

                case "method":
                case "methods":
                    $this->options["methods"] = $value;
                    break;

                case "callback":
                case "callbacks":
                    $this->options["callbacks"] = $value;
                    break;
            }
        }

    }

    /**
     * Attempts to destroy the session & close the SSH connection.
     */
    public function __destruct()
    {
        $this->destroyConnection();
    }

    /**
     * "connectionError" handles network failures and tries to properly close the connection,
     * and destroy the session.
     *
     * @param string $message
     * @return mixed
     * @throws ConnectionErrorException
     */
    protected function connectionError(string $message)
    {
            $this->destroyConnection();

            throw new ConnectionErrorException($message);
    }

    /**
     * "connectionWarning" handles network failures by throwing an exception to alert
     * of a network failure.
     *
     * @param string $message
     * @return mixed
     * @throws ConnectionErrorException
     */
    protected function connectionWarning(string $message)
    {
        throw new ConnectionErrorException($message);
    }

    /**
     * Make sure the resource is genuine.
     *
     * @param resource $resource
     * @return bool
     */
    protected function isResource($resource)
    {
        return ! \is_null(@\get_resource_type($resource));
    }

    /**
     * @return $this
     * @throws AuthenticationErrorException
     * @throws ConnectionErrorException
     */
    protected function connect()
    {
        $methods = $this->getMethods();

        // @todo Fix callbacks!
        $callbacks = [
            'disconnect' => function ($reason, $message, $language) {
                throw new Exception($reason . $message . $language);
            },
            'macerror' => function ($packet) {
                throw new Exception($packet);
            },
            'debug' => function ($message, $language, $alwaysDisplay) {
                throw new Exception($message . $language . $alwaysDisplay);
            },
            'ignore' => function ($message) {
                throw new Exception($message);
            }
        ];

        /**
         * Try connect to remote server.
         */
        $connection = \ssh2_connect(
            $this->SSHConnectionHost,
            $this->SSHConnectionPort,
            $methods,
            $callbacks
        );

        /**
         * Verify connection.
         * Note: a successful connection means NOT "authenticated"
         */
        if($connection === false) {
            throw new ConnectionErrorException("Error connecting to server");
        }

        if($this->isResource($connection) === false) {
            throw new ConnectionErrorException("Error connecting to server");
        }

        // Save the connection resource
        $this->connection = $connection;

        /**
         * Check fingerprint (see SSH fingerprint).
         */
        if(!empty($this->fingerprint)) {

            $fingerprint = \ssh2_fingerprint(
                $this->connection,
                \SSH2_FINGERPRINT_MD5 | \SSH2_FINGERPRINT_HEX
            );

            // Check fingerprint
            if (0 !== \strcmp($this->SSHServerFingerprint, $fingerprint)) {
                throw new AuthenticationErrorException(
                    "The authenticity of host '" . $this->SSHConnectionHost . "' can't be established."
                );
            }
        }

        if ($this->authenticate() !== true) {
            throw new AuthenticationErrorException('Host key verification failed.');
        }

        return $this;
    }

    /**
     * @return array
     */
    protected function getMethods()
    {
        if (\is_array($this->options["methods"])) {

            /**
             * Unset empty methods as may cause hard-to-debug server failures.
             */
            foreach ($this->options["methods"] as $method => $value) {
                if(\is_array($value) && \count($value) <= 0) {
                    unset($this->options["methods"][$method]);
                }
            }

            return $this->options["methods"];
        }

        return [];
    }

    /**
     * "executeSecureShellCommand" runs the SSH command and tries to return the output;
     * "executeSecureShellCommand" might fail to close the stream. You may try to force close the stream
     * by setting the "stream" to a null value (untested).
     *
     * @todo Update: insert a fork class/helper to fork each command, so that it can be stopped within the time limit
     * @param string $cmd
     * @return array
     * @throws ConnectionErrorException
     */
    protected function executeSecureShellCommand(string $cmd)
    {
        $logEnabled = $this->isLogEnabled();

        /**
         * This opens the connection, sends and receives data by sending a "SSH command",
         * and receives it's output after.
         */
        if($logEnabled === true) {

            $logIndex = \count($this->logStack["input"]);

            $this->logStack["input"][$logIndex] = $cmd;

            $stream = \ssh2_exec(
                $this->connection,
                $this->messageLogCommand . $cmd
            );
        } else {
            $stream = \ssh2_exec(
                $this->connection,
                $cmd
            );
        }

        /**
         * Make sure the connection was successful.
         */
        if($stream === false) {
            $this->connectionWarning("Error connecting to server. Command failed.");
        }

        if($this->isResource($stream) === false) {
            $this->connectionWarning("Error connecting to server. Command failed.");
        }

        // Save open stream
        $this->stdoutStream = $stream;

        // Init var.
        $output = [];

        /**
         * Retrieves the command output.
         *
         * If the connection fails while retrieving the output, an exception is thrown.
         * We catch the exception, print out any output that was retrieved, and return an error message with the
         * output.
         *
         * @todo Maintain two (2) separate outputs when exception is thrown: STDERR & STDIO
         */
        try {
            $output = $this->streamCommand();
        } catch (ConnectionErrorException $e) {
            $output = [ "STDIO" => $e->getMessage(), "stderr" => "" ];
        }

        if($logEnabled === true) {
            $this->logStack["output"][$logIndex] = $output;
        }

        return $output;
    }

    /**
     * Returns the output after the command was sent with "ssh2_exec".
     *
     * NOTE: if the connection is interrupted, you must handle the outcome:
     * you could either try to exit the session or just close the connection.
     *
     * @return array
     * @throws ConnectionErrorException
     */
    protected function streamCommand()
    {
        \stream_set_blocking($this->stdoutStream, true);

        $STDIOStream = \ssh2_fetch_stream($this->stdoutStream, \SSH2_STREAM_STDIO);
        $STDERRStream = \ssh2_fetch_stream($this->stdoutStream, \SSH2_STREAM_STDERR);

        // Close connection
        if(\fclose($this->stdoutStream) !== true) {

            // @todo Perhaps force-close the stream? $this->stdoutStream = null

            return $this->connectionWarning(
                "Connection to " . $this->SSHConnectionHost . " closed.");
        }

        \stream_set_blocking($STDIOStream, true);
        \stream_set_blocking($STDERRStream, true);

        $STDIOOutput = $STDERROutput = $STDERRBuffer = $STDIOBuffer = "";

        while ($STDIOBuffer = \fread($STDIOStream, $this->bufferSize)) {
            $STDIOOutput .= (string)$STDIOBuffer;
        }

        while ($STDERRBuffer = \fread($STDERRStream, $this->bufferSize)) {
            $STDERROutput .= (string)$STDERRBuffer;
        }

        // If a problem was encountered while retrieving the output
        if (
            (
                ($STDERRBuffer === false) ||
                ! \is_string($STDERRBuffer)
            ) ||
            (
                ($STDIOBuffer === false) ||
                ! \is_string($STDIOBuffer)
            )
        ) {
            return $this->connectionWarning(
                $STDIOOutput . PHP_EOL . $STDERROutput . PHP_EOL .
                "Connection to " . $this->SSHConnectionHost . " closed.");
        }

        // Close STDIO stream
        if (\fclose($STDIOStream) !== true) {

            $STDIOStream = null;
            $STDERRStream = null;

            return $this->connectionWarning(
                $STDIOOutput . PHP_EOL . $STDERROutput . PHP_EOL .
                "Connection to " . $this->SSHConnectionHost . " closed.");
        }

        // Close STDERR stream
        if (\fclose($STDERRStream) !== true) {

            $STDIOStream = null;
            $STDERRStream = null;

            return $this->connectionWarning(
                $STDIOOutput . PHP_EOL . $STDERROutput . PHP_EOL .
                "Connection to " . $this->SSHConnectionHost . " closed.");
        }

        return [ "stdio" => $STDIOOutput, "stderr" => $STDERROutput ];
    }

    /**
     * Authenticate, after connecting.
     * You must first set a username, password and/or public key and private key.
     *
     * @return bool
     * @throws AuthenticationErrorException
     */
    protected function authenticate(): bool
    {
        if(
            !empty($this->SSHAuthenticationPrivateKeyFilePath) &&
            !empty($this->SSHAuthenticationPublicKeyFilePath)
        ) {
            return $this->authenticateAsymmetric();
        } elseif (
            // If a custom authentication method exists
            isset($SSHAuthenticationMethod) &&
            \strlen($SSHAuthenticationMethod) > 0
        ) {
            throw new \Exception("Method not set");
        } else  {
            return $this->authenticatePassword();
        }
    }

    /**
     * Authenticate using a Public Key and a Private Key.
     *
     * @return bool
     * @throws AuthenticationErrorException
     */
    protected function authenticateAsymmetric()
    {
        /**
         * Authenticate using the Public Key file.
         */
        $authenticationSuccessful = \ssh2_auth_pubkey_file(
            $this->connection,
            $this->SSHAuthenticationUsername,
            $this->SSHAuthenticationPublicKeyFilePath,
            $this->SSHAuthenticationPrivateKeyFilePath,
            $this->SSHPrivateKeyPassword // Can be "null", used to decrypt Private Key
        );

        if ($authenticationSuccessful !== true) {
            throw new AuthenticationErrorException('Host key verification failed.');
        }

        return true;
    }

    /**
     * Authenticate using a password, or with an empty password.
     *
     * @return bool
     * @throws AuthenticationErrorException
     */
    protected function authenticatePassword()
    {

        $authenticationSuccessful = false;

        /**
         * Authenticate using username & password (plaintext) only.
         */
        if(
            ! \is_null($this->SSHAuthenticationPassword) &&
            \is_string($this->SSHAuthenticationPassword) &&
            (\strlen($this->SSHAuthenticationPassword) >= 1)
        ) {
            $authenticationSuccessful = \ssh2_auth_password(
                $this->connection,
                $this->SSHAuthenticationUsername,
                $this->SSHAuthenticationPassword // Cannot be "null"
            );
        } else {
            $authenticationSuccessful = \ssh2_auth_none(
                $this->connection,
                $this->SSHAuthenticationUsername
            );
        }

        if ($authenticationSuccessful !== true) {

            // Throw an exception if an alternative method is available
            if(\is_array($authenticationSuccessful)) {

                $message = "";

                foreach ($authenticationSuccessful as $key => $authenticationMethod) {
                    $message .= "'$authenticationMethod', ";
                }

                // Strip ', '
                $message = \substr($message, 0, \strlen($message) - 2);

                throw new AuthenticationErrorException('Host key verification method not allowed. Methods allowed: ' . $message);
            }

            throw new AuthenticationErrorException('Host key verification failed. Check username and password.');
        }

        return true;
    }

    /**
     * Attempts to close the session and disconnect from a non-working connection.
     *
     * @return string
     * @throws ConnectionErrorException
     */
    protected function destroyConnection()
    {

        $output = "";

        // Try to close the session
        if(
            ! \is_null($this->connection) &&
            ($this->isResource($this->connection) === true)
        ) {
            // Send "exit" signal
            $output = $this->executeCommand(
                'echo "EXITING" && exit;'
            );
        }

        // Close stream
        $this->stdoutStream = null;

        // Close connection (resource)
        $this->connection = null;

        return $output;
    }

    /**
     * Attempts to close the session and disconnect from a genuine connection.
     *
     * @return array
     * @throws ConnectionErrorException
     */
    protected function disconnectConnection()
    {
        $output = "";

        $output = $this->executeCommand('echo "EXITING" && exit;');

        $this->stdoutStream = null;

        $this->connection = null;

        return $output;
    }

    /**
     * @param array $options
     * @return array
     * @throws InvalidArgumentException
     */
    protected function parseNewConnectionOptions(array $options)
    {
        $throwNewException = function ($msg) {
            throw new InvalidArgumentException($msg);
        };

        return [
            $this->SSHConnectionHost = $options["host"] ?? $options["hostname"] ?? $options[0] ?? $throwNewException("Invalid hostname"),
            $this->SSHAuthenticationUsername = $options["username"] ?? $options[1] ?? "",
            $this->SSHAuthenticationPassword = $options["password"] ?? $options[2] ?? "",
            $this->SSHConnectionPort = $options["port"] ?? $options[3] ?? 22,
            $this->SSHAuthenticationPublicKeyFilePath = $options["publicKey"] ?? $options["publicKeyFilePath"] ?? $options[4] ?? "",
            $this->SSHAuthenticationPrivateKeyFilePath = $options["privateKey"] ?? $options["privateKeyFilePath"] ?? $options[5] ?? "",
            $this->SSHPrivateKeyPassword = $options["privateKeyPassword"] ?? $options[6] ?? "",
            $this->SSHServerFingerprint = $options["fingerprint"] ?? $options[7] ?? "",
            $this->bufferSize = $options["bufferSize"] ?? $options["buffer"] ?? $options[8] ?? 4096,
            $this->logEnabled = $options["logEnabled"] ?? $options[9] ?? true,
            $this->logType = $options["logType"] ?? $options[10] ?? "volatile",
            $this->messageLogCommand = $options["messageLogCommand"] ?? $options[11] ?? 'echo -e "[PHP-SSH]:\r\n"; '
        ];
    }
}
