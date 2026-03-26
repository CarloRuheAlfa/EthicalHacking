<?php
// Applicatieconfiguratie en constanten
// Transactielimieten (in euro)
if (!defined('MIN_TRANSFER')) {
    define('MIN_TRANSFER', 0.01);
}
if (!defined('MAX_TRANSFER')) {
    define('MAX_TRANSFER', 10000.00);
}

// Input limits
if (!defined('MAX_USERNAME_LENGTH')) {
    define('MAX_USERNAME_LENGTH', 50);
}
if (!defined('MAX_DESCRIPTION_LENGTH')) {
    define('MAX_DESCRIPTION_LENGTH', 500);
}

// Andere configuratiewaarden kunnen hier in de toekomst worden toegevoegd

// Geheim sleutel voor het maken van korte, HMAC-ondertekende tokens
// Belangrijk: in productie zet je hier een veilige, onveranderlijke sleutel via environment of config
if (!defined('APP_SECRET')) {
    define('APP_SECRET', 'change_this_to_a_secure_random_secret');
}

?>
