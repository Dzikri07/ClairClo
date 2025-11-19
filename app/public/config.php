<?php
// config.php - database configuration (fill with your credentials)
// Copy this file and fill in values or keep blank if you don't want DB integration.

function get_db_pdo()
{
    // Edit these credentials to match your MySQL setup
    $host = '127.0.0.1';
    $db   = 'clariocloud';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';

    try {
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        return new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // return null on failure — file upload will still succeed
        return null;
    }
}
