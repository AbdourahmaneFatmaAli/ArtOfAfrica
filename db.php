<?php
$host = 'localhost';
$dbname = 'webtech_2025A_fatma_abdourahmane';
$username = 'fatma.abdourahmane';     
$password = 'Fatou019@';  

try {
    $pdo = new PDO("mysql: host=$host; dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("connection failed: " . $e->getMessage());
}
?>