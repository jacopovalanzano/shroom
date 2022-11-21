# Shroom\SSH

## Example

```php
<?php

// Create a SSHManager instance
$SSHManager = new \Shroom\SSH\SSHManager();

// Connect to remote server
$SSHConnection = $SSHManager->newSSHPasswordSession(
    "127.0.0.1",
    "root",
    "toor",
    22
);

// Execute command and retrieve output
$output = $SSHConnection->exec('echo -e "Hello World!\r\n"');
```