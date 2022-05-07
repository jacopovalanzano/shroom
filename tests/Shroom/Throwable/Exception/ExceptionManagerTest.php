<?php

require __DIR__."/../../../../vendor/autoload.php";

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Constraint\IsType;

class ExceptionManagerTest extends TestCase
{

    private $exceptionHandler;

    public function setUp():void
    {
        $this->exceptionHandler = new \Shroom\Throwable\Exception\ExceptionManager("EXCEPTION MANAGER TEST", 321);
    }

    public function tearDown():void
    {
        $this->exceptionHandler = null;
    }

    /**
     * @param array $array
     * @param callable $callback
     * @return array
     */
    public function testThrow()
    {
        $this->assertEquals(
            "EXCEPTION MANAGER TEST",
            $this->exceptionHandler->getMessage()
        );

        $this->assertEquals(
            321,
            $this->exceptionHandler->getCode()
        );
    }
}
