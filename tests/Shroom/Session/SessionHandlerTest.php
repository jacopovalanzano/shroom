<?php

require __DIR__."/../../../vendor/autoload.php";

use PHPUnit\Framework\TestCase;

class SessionHandlerTest extends TestCase
{

    private $sessionHandler;

    private $fileSessionDriver;

    private $apcSessionDriver;

    public function setUp():void {

        // Create a file based session driver
        #$fileSessionDriver = $this->createMock("\Shroom\Session\Drivers\FileSession");
        $this->fileSessionDriver = new \Shroom\Session\Drivers\FileSession();
        $this->assertInstanceOf("\Shroom\Session\Drivers\FileSession", $this->fileSessionDriver);

        // Create an "apc" based session driver
        $this->apcSessionDriver = $this->createMock("\Shroom\Session\Drivers\ApcSession");
        $this->assertInstanceOf("\Shroom\Session\Drivers\ApcSession", $this->apcSessionDriver);

        // Get the session handler
        $this->sessionHandler = \Shroom\Session\SessionHandler::getInstance();
        $this->assertInstanceOf("\Shroom\Session\SessionHandler", $this->sessionHandler);

        // Sets the default session (file)
        $this->assertInstanceOf("\SessionHandlerInterface", $this->sessionHandler->setDefaultDriver($this->fileSessionDriver, "file"));
    }

    public function tearDown():void {
        // Destroy captcha instance
        $this->sessionHandler->destroy();
        $this->sessionHandler = null;
    }

    public function testDriver()
    {
        $this->assertInstanceOf("\Shroom\Session\SessionDriverWrapper", $this->sessionHandler->driver());
    }

    public function testGetDefaultDriverName()
    {
        $this->assertSame("file", $this->sessionHandler->getDefaultDriverName());
    }

    public function testSessionStart()
    {
        $sessionHandler = $this->sessionHandler;
        $this->assertTrue($sessionHandler->start());
        $this->sessionHandler->destroy();
    }

    /**
     * The "destroy" method requires the $sessionId parameter, it is filled automatically by the SessionHandler
     * when an empty value is supplied.
     *
     * @param string $sessionId
     * @return bool
     */
    public function testDestroy()
    {
        $sessionHandler = $this->sessionHandler;

        if($sessionHandler->isStarted()) {
            $this->assertTrue($sessionHandler->destroy());
        } else {
            $this->assertTrue($sessionHandler->start());
            $this->assertTrue($sessionHandler->destroy());
        }
    }

    public function testIsStarted()
    {
        // If a session was left hanging from a previous (failed) unit-test, destroy the session first
        if($this->sessionHandler->isStarted() === true) {
            $this->sessionHandler->destroy();
            $this->sessionHandler->start();
            $this->assertTrue($this->sessionHandler->isStarted());
            $this->sessionHandler->destroy();
        } else {
            $this->sessionHandler->start();
            $this->assertTrue($this->sessionHandler->isStarted());
            $this->sessionHandler->destroy();
        }
    }

    public function testHas()
    {
        $this->assertFalse($this->sessionHandler->has("NON_EXISTENT_KEY"));
        $this->assertNull($this->sessionHandler->set("EXISTENT_KEY", "EXISTENT_KEY"));
        $this->assertTrue($this->sessionHandler->has("EXISTENT_KEY"));
    }

    public function testAdd()
    {
        $this->assertNull($this->sessionHandler->set("EXISTENT_KEY", []));
        $this->assertNull($this->sessionHandler->add("EXISTENT_KEY", "EXISTENT_VALUE",));
        $this->assertEquals(["EXISTENT_VALUE"], $this->sessionHandler->get("EXISTENT_KEY"));
    }

    public function testSet()
    {
        $this->assertNull($this->sessionHandler->set("EXISTENT_KEY", []));
        $this->assertEquals([], $this->sessionHandler->get("EXISTENT_KEY"));
        $this->assertNull($this->sessionHandler->set("EXISTENT_KEY", ["EXISTENT_VALUE"]));
        $this->assertEquals(["EXISTENT_VALUE"], $this->sessionHandler->get("EXISTENT_KEY"));
    }

    public function testSessionClose()
    {
        $this->assertTrue($this->sessionHandler->close());
    }

    public function testGetCurrentDriver()
    {
        $this->assertEquals("file", $this->sessionHandler->getCurrentDriver());
    }

    public function testSessionUnset()
    {
        $this->assertEquals(["EXISTENT_VALUE"], $this->sessionHandler->get("EXISTENT_KEY"));
        $this->sessionHandler->sessionUnset();
        $this->expectException(Shroom\Throwable\Exception\Logic\InvalidArgumentException::class);
        $this->assertEquals([], $this->sessionHandler->driver()->get("EXISTENT_KEY"));
    }

    public function testSessionDestroy()
    {
        $this->sessionHandler->set("EXISTENT_KEY", ["EXISTENT_VALUE"]);
        $this->assertEquals(["EXISTENT_VALUE"], $this->sessionHandler->get("EXISTENT_KEY"));
        $this->sessionHandler->sessionDestroy($this->sessionHandler->getId());
        $this->assertEquals(["EXISTENT_VALUE"], $this->sessionHandler->driver()->get("EXISTENT_KEY"));
        #$this->assertFalse(is_file($this->sessionHandler->driver()->sessionDriver->path . "/tdr_sess_" . $this->sessionHandler->getId())); // members must be declared "public" for this test to work
    }

    public function testSetNewDriver()
    {
        $this->sessionHandler->setNewDriver("file2", new \Shroom\Session\Drivers\FileSession());
        $this->assertInstanceOf("\SessionHandlerInterface", $this->sessionHandler->driver("file2"));
    }

    public function testSetName()
    {
        $this->assertEquals("__SHROOM_DEFAULT_SESSION", $this->sessionHandler->getName());
        $this->sessionHandler->destroy();
        $this->sessionHandler->setName("NEW_SESSION_NAME");
        $this->assertEquals("NEW_SESSION_NAME", $this->sessionHandler->getName());
    }

    public function testGetName()
    {
        $this->assertEquals("NEW_SESSION_NAME", $this->sessionHandler->getName());
    }

    public function testStart()
    {
        $this->assertTrue($this->sessionHandler->start());
        $this->sessionHandler->destroy();
    }

    public function testClear()
    {
        $this->sessionHandler->set("EXISTENT_KEY", ["EXISTENT_VALUE"]);
        $this->assertEquals(["EXISTENT_VALUE"], $this->sessionHandler->get("EXISTENT_KEY"));
        $this->sessionHandler->clear();
        $this->expectException(Shroom\Throwable\Exception\Logic\InvalidArgumentException::class);
        $this->assertEquals([], $this->sessionHandler->driver()->get("EXISTENT_KEY"));
    }

    public function testInvalidate()
    {
        $this->sessionHandler->start();
        $sessionId = $this->sessionHandler->getId();
        $this->sessionHandler->set("EXISTENT_KEY", ["EXISTENT_VALUE"]);
        $this->assertEquals(["EXISTENT_VALUE"], $this->sessionHandler->get("EXISTENT_KEY"));
        $this->sessionHandler->invalidate();
        $this->assertNotEquals($sessionId, $this->sessionHandler->getId());
        $this->sessionHandler->destroy();
        $this->sessionHandler->destroy($sessionId);
        $this->assertNotEquals($sessionId, $this->sessionHandler->getId());
    }

    public function testRegenerate()
    {
        $currentId = $this->sessionHandler->getId();
        $this->sessionHandler->start();
        $this->assertEquals($currentId, $this->sessionHandler->getId());
        $this->sessionHandler->regenerate();
        $this->assertNotEquals($currentId, $this->sessionHandler->getId());
        $this->sessionHandler->destroy();
        $this->sessionHandler->destroy($currentId);
    }

    public function testInit()
    {
        $this->assertTrue($this->sessionHandler->destroy());
        $this->assertTrue($this->sessionHandler->init());
        $this->assertTrue($this->sessionHandler->destroy());
    }

    public function testSessionRegenerateId()
    {
        $currentId = $this->sessionHandler->getId();
        $this->sessionHandler->start();
        $this->assertEquals($currentId, $this->sessionHandler->getId());
        $this->sessionHandler->sessionRegenerateId();
        $this->assertNotEquals($currentId, $this->sessionHandler->getId());
        $this->sessionHandler->destroy();
        $this->sessionHandler->destroy($currentId);
    }

    public function testSessionStatus()
    {
        $this->assertEquals(1, $this->sessionHandler->sessionStatus());
        $this->sessionHandler->start();
        $this->assertEquals(2, $this->sessionHandler->sessionStatus());
        $this->sessionHandler->destroy();
    }

    public function testGetId()
    {
        $currentId = $this->sessionHandler->getId();
        $this->sessionHandler->start();
        $this->assertEquals($currentId, $this->sessionHandler->getId());
        $this->sessionHandler->destroy();
        $this->sessionHandler->destroy($currentId);
    }

    public function testSetId()
    {
        $currentId = $this->sessionHandler->getId();
        $this->sessionHandler->start();
        $this->assertEquals($currentId, $this->sessionHandler->getId());
        $this->sessionHandler->destroy();
        $this->sessionHandler->destroy($currentId);

        $this->sessionHandler->setId("TEST_SESSION_ID");
        $currentId = $this->sessionHandler->getId();
        $this->sessionHandler->start();
        $this->assertEquals($currentId, "TEST_SESSION_ID");
        $this->sessionHandler->destroy();
        $this->sessionHandler->destroy($currentId);
    }

    public function testSetDefaultDriver()
    {
        $this->assertEquals("file", $this->sessionHandler->getDefaultDriverName());
        $newSessionHandler = new \Shroom\Session\SessionHandler();
        $this->assertEquals("default", $newSessionHandler->getDefaultDriverName());
        $newSessionHandler->setDefaultDriver(new \Shroom\Session\Drivers\FileSession(), "newTestDriver");
        $this->assertEquals("newTestDriver", $newSessionHandler->getDefaultDriverName());
    }

}
