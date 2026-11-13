<?php

// Copy this file to q5_security_config.php
// and replace the placeholder with a private random key.

define(
    'Q5_SECURITY_HMAC_KEY',
    'REPLACE_WITH_YOUR_PRIVATE_64_CHARACTER_KEY'
);

define('Q5_MAX_FAILED_ATTEMPTS', 5);
define('Q5_LOGIN_WINDOW_MINUTES', 10);
define('Q5_LOCKOUT_MINUTES', 15);