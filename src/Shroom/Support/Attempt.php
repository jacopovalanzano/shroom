<?php

namespace Shroom\Support;

use Shroom\Traits\Instantiable;

/**
 * Class Attempt
 *
 * @author Jacopo Valanzano
 * @package Shroom\Session
 * @license MIT
 * @todo Add "non-blockable" attempts.
 */
class Attempt
{

    use Instantiable;

    /**
     * @param array $array
     * @param callable $callback
     * @return array
     */
    public function arrayLoop(array $array, callable $callback): array
    {
            foreach ($array as $key => $item) {
                if(is_array($key)) {
                    $key = $this->arrayLoop($key, $callback);
                }

                if(is_array($item)) {
                    $item = $this->arrayLoop($item, $callback);
                }

                $p[] = $callback($key, $item);
            }

            return $p;
    }

    /**
     * Wind up the $callback for each array key
     *
     * @param array $array (typehint left blank on purpose)
     * @param callable $callback
     */
    public function arrayWindCallback($array, callable $callback)
    {
        foreach ((array)$array as $item) {
            $callback($item);
        }
    }

    /**
     * @param $array
     * @return string
     */
    public function arrayToString($array, string $separator = ""): string
    {

        $s = "";

        foreach ((array)$array as $item) {
            $s .= (is_array($item) ? $this->arrayToString($item) : (string)$item) . $separator;
        }

        return $s;
    }

    /**
     * Tries to return the "depth" of an array.
     *
     * @see https://stackoverflow.com/questions/262891/is-there-a-way-to-find-out-how-deep-a-php-array-is
     * @param array $array
     * @return false|float|int
     */
    public function getArrayDepth(array $array)
    {
        $max_indentation = 1;

        $array_str = print_r($array, true);
        $lines = explode("\n", $array_str);

        foreach ($lines as $line) {
            $indentation = (strlen($line) - strlen(ltrim($line))) / 4;

            if ($indentation > $max_indentation) {
                $max_indentation = $indentation;
            }
        }

        return (int) round(ceil(($max_indentation - 1) / 2) + 1);
    }

    /**
     * Strips one or more characters from a string or array.
     *
     * Usage: stripChar("{my string}", "{", "}") result: "my string"
     *
     * @param array|string $param
     * @param string $chars
     * @return array|string|string[]
     */
    public function stripChar(string $param, string ...$chars)
    {
        if(is_array($param)) {

            foreach ($param as $key => $item) {
                if(is_array($key)) {
                    $key = $this->stripChar($key, ...$chars);
                }

                if(is_array($item)) {
                    $item = $this->stripChar($item, ...$chars);
                }

                $p[str_replace($chars, '', $key)] = str_replace($chars, '', $item);
            }

            return $p;

        } else {
            return $param = str_replace($chars, '', $param);
        }
    }

    /**
     * Removes a (one) level from a multidimensional array.
     *
     * @param array $arr
     * @return array
     */
    public function resurfaceArray(array $arr): array
    {
        $new = [];

        foreach($arr as $key => $value) {
            foreach ($value as $item => $content) {
                $new[$item] = $content;
            }
        }

        return (array)$new;
    }

    /**
     * Throws a custom exception and halts the application.
     *
     * @param $exception
     * @param ...$args
     * @beta
     * @todo Make the method "throwable" (catch-able)
     */
    public function throw($exception, ...$args)
    {
        // "$ofCaller" represents the caller (file & line) of this method.
        $ofCaller = [];
        $ofCaller["line"] = Attempt::getInstance()->logLine();
        $ofCaller["file"] = Attempt::getInstance()->logFile();

        // Generates a new '\RuntimeException'
        $newException = new class($exception, $args, $ofCaller) extends \RuntimeException
        {

            /**
             * Stores the new exception as a "RuntimeException"
             *
             * @var \RuntimeException
             */
            protected $newException;

            /**
             * An array containing the file and line of the caller.
             *
             * @var array
             */
            private $ofCallerBacktrace;

            /**
             * Constructs the new exception.
             *
             * @param $exception
             * @param $args
             */
            public function __construct($exception, $args, $ofCaller)
            {

                $args[0] = $exception . ": '" . $args[0] . "'";

                $ex = new parent(...$args);

                $this->newException = $ex;

                $this->ofCallerBacktrace = $ofCaller;

                $this->throw();
            }

            /**
             * Throws the exception, seamlessly.
             */
            public function throw()
            {
                echo
                    '<br />'.PHP_EOL.'<b>Fatal error</b>: Uncaught '.
                    $this->newException->getMessage().
                    ' in '.
                    $this->ofCallerBacktrace["file"].
                    ':'.$this->ofCallerBacktrace["line"].
                    PHP_EOL.'Stack trace:'.PHP_EOL.
                    $this->newException->getTraceAsString();

                // Halts the application
                exit();
            }

        };

        throw $newException;
    }

    /**
     * Tries to convert the argument to the given type.
     *
     * @param $type
     * @param $arg
     * @param null $default
     * @return array|bool|\Closure|int|mixed|object|string|null
     */
    public function typehint($type, $arg, $default = null)
    {

        switch ($type)
        {
            case "int":
            case "integer":
                return (int) $arg;

            case "str":
            case "string":
                return (string) $arg;


            case "array":
                return (array) $arg;


            case "object":
                return (object) $arg;


            case "bool":
                return (bool) $arg;


            case "float":
                return (float) $arg;

            case "callable":
            case "closure":
                $arg = function () use ($arg) { return $arg; };
                return $arg;

            case "*":
                return $arg;


            case "true":
                return true;


            case "false":
                return false;


            default:
                return $default;
        }
    }

    /**
     * Sends an HTTP request (POST, GET etc..)
     *
     * @param string $url
     * @param string $method
     * @param string|array $content
     * @param array $headers
     * @return false|string Returns the response (string)
     */
    public function sendRequest(string $url, string $method, $content, array $headers = [])
    {

        foreach ($headers as $key => $value) {
            $header .= $key . ": " . $value . "\r\n";
        }

        // Strip last '\r\n\'
        $header = substr($header, 0, -4);

        $options = array(
            'http' => array(
                'header'  => $header,
                'method'  => strtoupper($method),
                'content' => $content
            )
        );

        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        return $result;
    }

    /**
     * Sends a POST request.
     *
     * @param stirng $url
     * @param array $content
     * @param array $headers
     * @param false $json
     * @return false|string The request response, or false.
     */
    public function sendPostRequest(string $url, array $content, $headers = [], $json = false)
    {
        if($json === true) {
            $header["Content-Type"] = "application/json";
            $content = json_encode($content);
        } else {
            $header["Content-type"] = "application/x-www-form-urlencoded";
            $content = http_build_query($content);
        }

        foreach ($headers as $key => $value) {
            $header[$key] = $value;
        }

        return $this->sendRequest($url, "POST, $content", $header);
    }

    /**
     * Returns the sum of the size of each file in a given directory
     * (and its subdirectories) as an integer.
     *
     * @param string $dir
     * @return int $size the complex size of the path content in BYTES
     */
    public function getDirectorySize(string $dir): int
    {
        $size = 0;

        foreach (glob(rtrim($dir, '/') . '/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : $this->getDirectorySize($each);
        }

        return (int)$size;
    }

    /**
     * Tries to retrieve the browser ip.
     *
     * @return string
     */
    public function getBrowserIp()
    {
        // Get real visitor IP behind CloudFlare network
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }

        $client = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote = @$_SERVER['REMOTE_ADDR'];

        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }

        return (string)$ip;
    }

    /**
     * Returns the line of the caller.
     *
     * @return int
     */
    public function logLine()
    {
        $backtrace = debug_backtrace();

        return (int)$backtrace[1]["line"];
    }

    /**
     * Returns the file path of the caller.
     *
     * @return string
     */
    public function logFile()
    {
        $backtrace = debug_backtrace();

        return (string)$backtrace[1]["file"];
    }

    /**
     * @param callable ...$closures
     * @return array|string
     * @throws \ReflectionException
     */
    public function getTypehint(callable ...$closures)
    {
        $typehints = [];

        foreach ($closures as $closure) {
            $typehintClasses = [];
            $reflector = new \ReflectionFunction($closure);

            $params = $reflector->getParameters();

            if(count($params) < 1) {
                break;
            }

            foreach($params as $param) {
                $typehintClasses[] = $param->getType()->getName();
            }

            $typehints[] = $typehintClasses;
        }

        return (count($typehints) > 1) ? $typehints : ( (count($typehints[0]) > 1) ? $typehints[0] : $typehints[0][0] );
    }

}
