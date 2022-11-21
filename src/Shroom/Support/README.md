# Shroom\Support

## Example

```php
<?php

$attempt = \Shroom\Support\Attempt::getInstance();

// Try to retrieve the user IP
$visitorIP = $attempt->getBrowserIp();

```