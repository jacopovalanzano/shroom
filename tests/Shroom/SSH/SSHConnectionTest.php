<?php

require __DIR__ . "/../../../vendor/autoload.php";

use PHPUnit\Framework\TestCase;
use Shroom\SSH\Connection\SSHConnectionManager;

class SSHConnectionTest extends TestCase
{

    private $SSHConnection;

    public function setUp(): void
    {

        $SSHConnectionObject = new \Shroom\SSH\Connection\SSHConnection(
            "127.0.0.1",
            "test",
            "test",
            22,
            "",
            "",
            "",
            "",
            4096,
            true,
            "volatile",
            ""
        );

        $this->SSHConnection["sessionName1"] = $SSHConnectionObject;
        $this->assertInstanceOf("\Shroom\SSH\Connection\SSHConnection", $SSHConnectionObject);
    }

    public function tearDown(): void
    {
        #$this->SSHConnection["sessionName1"]->destroy();
        #$this->SSHConnection["sessionName1"] = null;
    }

    public function testCmd()
    {
        $this->assertEquals(
                 [
                    'stdio' => 'Hello World!
',
                    'stderr' => 'Could not chdir to home directory /home/test: No such file or directory
'
                ],
            $this->SSHConnection["sessionName1"]->cmd("echo 'Hello World!';")
        );
    }

    public function testExec()
    {
        $this->assertEquals(
            "Hello World!\n",
            $this->SSHConnection["sessionName1"]->exec("echo 'Hello World!';")["stdio"]
        );
    }

}
