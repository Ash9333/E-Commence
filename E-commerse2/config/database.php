<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'synergy1');
define('DB_PASS', 'wEyUs2Vj_@vcv%;*');
define('DB_NAME', 'synergy1_Shop_Test');

function getDB() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        return $conn;
    } catch (Exception $e) {
        die("Database error: " . $e->getMessage());
    }
}

session_start();
?>