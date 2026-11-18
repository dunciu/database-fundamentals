<?php
// db.php: conduit between website and database 
// Defaults suit XAMPP. Use db_config.php to override them.
$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';
$db_port = 3306;

if (file_exists(__DIR__ . '/db_config.php')) {
    require __DIR__ . '/db_config.php';
}

$conn = new mysqli($db_host, $db_user, $db_pass, 'auction', $db_port);
$conn->set_charset('utf8mb4');