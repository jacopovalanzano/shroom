
# PHP Shroom framework

Framework for building web applications.

## Install

Install with Git

``$ git clone https://github.com/jacopovalanzano/shroom``

Install with Composer

``$ composer require jacopovalanzano/shroom``

### Demo

After you have downloaded <b>Shroom</b>, extract and install the package. You can use <b>Composer</b> to speed things up:

``$ cd shroom``

``$ composer dump-autoload``

In your "index.php" file:
```php
<?php

// Load Composer
require "vendor/autoload.php";

/**
 * Start a new session:
 */
$session = new \Shroom\Session\SessionHandler();

// Preferred driver
$sessionDriver = new \Shroom\Session\Drivers\FileSession();

$session->setDefaultDriver($sessionDriver);

// Starts the session
$session->start();

/**
 * Initialize the helper class "Attempt"
 */
$attempt = \Shroom\Support\Attempt::getInstance();

// Try to retrieve the user IP
$visitorIP = $attempt->getBrowserIp();

/**
* Initialize the class "SSHManager", and exchange commands with a server
*/
$SSHManager = new \Shroom\SSH\SSHManager();

$SSH1 = $SSHManager->newSSHPasswordSession([
            "127.0.0.1",
            "root",
            "toor"
         ]);
       
// Sends a shell command and retrieve the output  
$commandOutput = $SSH1->exec("cat /etc/proc/version;");
```

## Contributing

Please commit to the *dev* branch only. The main branch is dedicated to the stable version.

If you are not interested in programming but still want to help, you can translate, improve the documentation and share
this project with other people.

Some things you can do:

 - *Improve this README*
 - Write missing PHP Unit tests for classes & traits
 - Improve existing PHP Unit test documentation
 - Test the framework on different environments (Microsoft IIS)
 - Review code
 - Translate
 - Write documentation
 - Share this project

## Planned features

 - User authentication (eg. registration, login)
 - Cache (Shared memory, Redis)
 - HTTP Router
 - Plugins/add-ons
 - Database
 - Encryption
 - Facades
 - Validation
 - Action and filter hooks

## Unit tests

Tested with **PhpUnit v6.5** and **PhpUnit v9.5** 

 - ``
$ composer dump-autoload
``

   - ``
$ phpunit tests/Shroom/Throwable/Exception/ExceptionManagerTest.php
``

   - ``
$ phpunit tests/Shroom/Throwable/Exception/ExceptionHandlerTest.php
``

   - ``
$ phpunit tests/Shroom/Session/SessionHandlerTest.php --stderr
``

   - ``
$ phpunit tests/Shroom/Support/AttemptTest.php
``

   - ``
$ phpunit tests/Shroom/SSH/SSHManagerTest.php
``

   - ``
$ phpunit tests/Shroom/SSH/Connection/SSHConnectionTest.php
``