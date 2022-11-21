<?php

require __DIR__ . "/../../../vendor/autoload.php";

use PHPUnit\Framework\TestCase;
use Shroom\SSH\SSHManager;

class SSHManagerTest extends TestCase
{

    private $SSHManager;

    private $SSHConnection;

    public function setUp(): void
    {
        $SSHManager = new SSHManager();

        $this->SSHManager = $SSHManager;
        $this->assertInstanceOf("\Shroom\SSH\SSHManager", $this->SSHManager);

        // Create an instance of a SSHConnectionexecuteAll
        $SSHConnection = $this->SSHManager->newSSHPasswordSession(
            "127.0.0.1",
            "test",
            "test",
            22,
            "",
            "sessionName1",
            [
                "logMessage" => ""
            ]
        );

        $this->SSHConnection["sessionName1"] = $SSHConnection;
        $this->assertInstanceOf("\Shroom\SSH\Connection\SSHConnection", $SSHConnection);
    }

    public function tearDown(): void
    {
//        $this->SSHConnection["sessionName1"]->destroy();
//        $this->SSHConnection = null;

//        $this->SSHConnection["sessionName2"]->destroy();
//        $this->SSHConnection["sessionName2"] = null;

//        $this->SSHManager->destroyAll();
//        $this->SSHManager = null;
    }

    public function testNewSSHPasswordSession()
    {
        $this->assertInstanceOf("\Shroom\SSH\Connection\SSHConnection", $this->SSHManager->newSSHPasswordSession(
            "127.0.0.1",
            "test",
            "test",
            22,
            "",
            "sessionName2",
            ["logMessage" => ""]
        ));
    }

    public function testExecuteAll()
    {
        $this->assertEquals(
            [
                'sessionName1' => [
                    'stdio' => 'Hello World!
',
                    'stderr' => 'Could not chdir to home directory /home/test: No such file or directory
'
                ]
            ],
            $this->SSHManager->executeAll("echo 'Hello World!';"));
    }

    public function testListAllConnections()
    {
        $sessionStack = $this->SSHManager->listAllConnections();

        $this->assertEquals(
            [
                "sessionName1"=> [
                    "hostname" => "127.0.0.1",
                    "port" => 22,
                    "username" => "test",
                    "password" => "test",
                    "publicKey" => NULL,
                    "privateKey" => NULL,
                    "privateKeyPassword" => NULL,
                    "isConnected" => true
                ]
            ],
            $sessionStack
        );
    }

    public function testDisconnect()
    {
        $this->assertEquals(null, $this->SSHManager->disconnect("sessionName1"));
        $this->assertEquals([ 'sessionName1' => $this->SSHConnection["sessionName1"] ], $this->SSHManager->getAllSessions());
    }

    public function testDisconnectAll()
    {
        $this->assertEquals(null, $this->SSHManager->disconnectAll());
        $this->assertEquals([ 'sessionName1' => $this->SSHConnection["sessionName1"] ], $this->SSHManager->getAllSessions());
    }

    public function testDestroy()
    {
        $this->assertEquals(null, $this->SSHManager->destroyAll());
        $this->assertEquals([], $this->SSHManager->getAllSessions());
    }
}
