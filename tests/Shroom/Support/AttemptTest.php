<?php

require __DIR__."/../../../vendor/autoload.php";

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Constraint\IsType;

class AttemptTest extends TestCase
{

    private $instance;

    public function setUp():void
    {
        $this->instance = new \Shroom\Support\Attempt();
    }

    public function tearDown():void
    {
        $this->instance = null;
    }

    /**
     * @param array $array
     * @param callable $callback
     * @return array
     */
    public function testArrayLoop()
    {
        $attempt = new \Shroom\Support\Attempt();

        $testArray = ["a" => 1, "b" => 2,  "c" => 3, ["d",4]];

        $this->assertEquals(
            [
                ["a", 1],
                ["b", 2],
                ["c", 3],
                [ 0 => 0,
                    1 => [
                        [
                            0 => 0,
                            1 => "d",
                        ],
                        [
                            0 => 1,
                            1 => 4
                        ]
                    ]
                ]
            ],
            $attempt->arrayLoop($testArray, function ($key, $value) {
                return [$key, $value];
            })
        );
    }

    /**
     * @param $array
     * @return string
     */
    public function testArrayToString()
    {
        $this->assertEquals("Hello World! ", $this->instance->arrayToString(["Hello", "World!"]," "));
        $this->assertEquals("Hello World! ", $this->instance->arrayToString([["Hello"], ["World!"]]," "));
    }

    /**
     * @return void
     * @throws \Shroom\Throwable\Exception\Logic\InvalidArgumentException
     */
    public function testArrayToStringEncapsulate()
    {
        $this->assertEquals("{Hello },{World!}", $this->instance->arrayToStringEncapsulate(["Hello ", "World!"], ["{", "}"], false, ",", true));
        $this->assertEquals("{Hello}, World!", $this->instance->arrayToStringEncapsulate(["Hello", " World!"], ["{", "}"], true, ",", true));
        $this->assertEquals("{Hello}, World!,", $this->instance->arrayToStringEncapsulate(["Hello", " World!"], ["{", "}"], true, ",", false));
    }

    /**
     * @param array $array
     * @return false|float|int
     */
    public function testGetArrayDepth()
    {
        $d1 = [];
        $d2 = [[]];
        $d5 = [[[[[1]]]]];

        $this->assertEquals(1, $this->instance->getArrayDepth($d1));
        $this->assertEquals(2, $this->instance->getArrayDepth($d2));
        $this->assertEquals(5, $this->instance->getArrayDepth($d5));

    }

    /**
     * @param array|string $param
     * @param string $chars
     * @return array|string|string[]
     */
    public function testStripChar()
    {
        $this->assertEquals("Hello World!", $this->instance->stripChar("[Hello World!]", "[", "]"));
        $this->assertEquals("Hello World!", $this->instance->stripChar("\Hello World!\\", "\\"));
    }

    /**
     * @param array $arr
     * @return array
     */
    public function testResurfaceArray()
    {
        $this->assertEquals(["Hello World!"], $this->instance->resurfaceArray([["Hello World!"]]));
        $this->assertEquals([["Hello World!"]], $this->instance->resurfaceArray([[["Hello World!"]]]));
        $this->assertEquals([[["Hello World!"]]], $this->instance->resurfaceArray([[[["Hello World!"]]]]));
    }

    /**
     * @param $exception
     * @param ...$args
     * @beta
     */
    public function testThrow()
    {
        #$this->assertTrue($this->instance->throw("CUSTOM THROWN EXCEPTION", "TEST"));
    }

    /**
     * @param $type
     * @param $arg
     * @param null $default
     * @return array|bool|\Closure|int|mixed|object|string|null
     */
    public function testTypehint()
    {

        $int = "int";

        $this->assertIsInt(
            $this->instance->typehint("int", $int)
        );

        $str = "str";

        $this->assertIsString(
            $this->instance->typehint("str", $str)
        );

        $object = "object";

        $this->assertIsObject(
            $this->instance->typehint("object", $object)
        );

        $array = "array";

        $this->assertIsArray(
            $this->instance->typehint("array", $array)
        );

        $bool = "bool";

        $this->assertIsBool(
            $this->instance->typehint("bool", $bool)
        );

        $float = "1.5";

        $this->assertIsFloat(
            $this->instance->typehint("float", $float)
        );

        $closure = "closure";

        $this->assertIsCallable(
            $this->instance->typehint("closure", $closure)
        );

        $callable = "callable";

        $this->assertIsCallable(
            $this->instance->typehint("callable", $callable)
        );
    }

    /**
     * @todo
     * @param string $url
     * @param string $method
     * @param string|array $content
     * @param array $headers
     * @return false|string Returns the response (string)
     */
    public function sendRequest(string $url, string $method, $content, array $headers = [])
    {
        //
    }

    /**
     * @todo
     * @param stirng $url
     * @param array $content
     * @param array $headers
     * @param false $json
     * @return false|string The request response or false.
     */
    public function sendPostRequest(string $url, array $content, $headers = [], $json = false)
    {
        //
    }

    /**
     * @param string $dir
     * @return int
     */
    public function testGetDirectorySize()
    {
        $this->assertGreaterThan(10000, $this->instance->getDirectorySize("."));
    }

    /**
     * @todo
     * @return string
     */
    public function getBrowserIp()
    {
        //
    }

    /**
     * @todo
     * @return int
     */
    public function logLine()
    {
        //
    }

    /**
     * @todo
     * @return string
     */
    public function logFile()
    {
        //
    }

    /**
     * @param callable ...$closures
     * @return array|string
     * @throws \ReflectionException
     */
    public function testGetTypehint(callable ...$closures)
    {

        $a = function (\ArrayObject $a) {
            return $a;
        };

        $this->assertEquals(
            "ArrayObject",
            $this->instance->getTypehint($a)
        );

        $b = function (\BadMethodCallException $b) {
            return $b;
        };

        $this->assertEquals(
            "BadMethodCallException",
            $this->instance->getTypehint($b)
        );

        $c = function (\Closure $c) {
            return $c;
        };

        $this->assertEquals(
            "Closure",
            $this->instance->getTypehint($c)
        );

        $e = function (\Exception $e) {
            return $e;
        };

        $this->assertEquals(
            "Exception",
            $this->instance->getTypehint($e)
        );
    }


}
