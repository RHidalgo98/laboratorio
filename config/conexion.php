<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "analisislaboratorio";

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $conexion = new PDO("mysql:host=$servername;port=3308;dbname=$dbname", $username, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
    exit();
}
?>