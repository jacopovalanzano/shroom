# Shroom\Session

## Example

```php
<?php

/**
 * Start a new file session:
 */
$session = new \Shroom\Session\SessionHandler();

// Preferred driver
$sessionDriver = new \Shroom\Session\Drivers\FileSession();

$session->setDefaultDriver($sessionDriver);

// Start the session
if(! $session->isStarted()) {
    $session->start();
}

```

```php
<?php

/**
 * Start a new APC session:
 */
$session = new \Shroom\Session\SessionHandler();

$sessionDriver = new \Shroom\Session\Drivers\ApcSession();

$session->setDefaultDriver($sessionDriver);

// Start the session
if(! $session->isStarted()) {
    $session->start();
}

```