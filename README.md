# PHP Shroom framework

Simple framework for building web applications.

## Install

Install with Git

``$ git clone https://github.com/jacopovalanzano/shroom``

Install with Composer

``$ composer require jacopovalanzano/shroom``

### Demo

``$ cd shroom``

``$ composer dump-autoload``

```php
<?php

require "vendor/autoload.php";

$attempt = \Shroom\Support\Attempt::getInstance();

$visitorIP = $attempt->getBrowserIp();

$session = new \Shroom\Session\SessionHandler();

$sessionDriver = new \Shroom\Session\Drivers\FileSession();

$session->setDefaultDriver($sessionDriver);

$session->start();

```

## Contributing

Please commit to the *dev* branch only. The main branch is dedicated to the stable version.

If you are not interested in programming but still want to help, you can translate, improve the documentation and share
this project with other people.

Some things you can do:

 - *Improve this README*
 - Write missing PHP Unit tests for classes & traits
 - Improve existing PHP Unit test documentation
 - Test the framework on different environments
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
