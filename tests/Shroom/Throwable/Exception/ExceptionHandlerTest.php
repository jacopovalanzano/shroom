<?php

require __DIR__."/../../../../vendor/autoload.php";

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Constraint\IsType;

class ExceptionHandlerTest extends TestCase
{

    private $exceptionHandler;

    public function setUp()
    {
        $this->exceptionHandler = new \Shroom\Throwable\Exception\ExceptionHandler("EXCEPTION HANDLER TEST", 123);
    }

    public function tearDown()
    {
        $this->exceptionHandler = null;
    }

    public function test()
    {

        $this->assertInstanceOf(
            \Throwable::class,
            $this->exceptionHandler
        );

        $this->assertEquals(
            "EXCEPTION HANDLER TEST",
            $this->exceptionHandler->getMessage()
        );

        $this->assertEquals(
            123,
            $this->exceptionHandler->getCode()
        );

        $shroomLogicException = new \Shroom\Throwable\Exception\Logic\LogicException("LOGIC EXCEPTION TEST", 321);

        $this->assertEquals(
            "LOGIC EXCEPTION TEST",
            $shroomLogicException->getMessage()
        );

        $this->assertEquals(
            321,
            $shroomLogicException->getCode()
        );

        $this->assertInstanceOf(
            \Throwable::class,
            $shroomLogicException
        );

        $this->assertEquals(
            $this->exceptionHandler->getId($shroomLogicException),
            $shroomLogicException->getId()
        );

    }
}
