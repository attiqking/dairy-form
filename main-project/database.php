<?php
$db_host = "localhost";
$db_name = "sohailpk_dairy_farm_management";
$db_user = "sohailpk_dairy_user";
$db_pass = "_DxJ7^KEk!E+3bS*";

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}