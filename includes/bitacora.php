<?php 
include $_SERVER['DOCUMENT_ROOT'] . '/AnalisisLaboratorio/config/config.php';
include ROOT_PATH . 'config/conexion.php';

function registrarBitacora($nombreUsuario, $descripcion, $idRegistro, $nombreTabla, $detallesCambio) {
    global $conexion; // Usa la conexión PDO definida en config/conexion.php

    try {
        $query = "INSERT INTO bitacora (
            nombreUsuario, 
            descripcion, 
            idRegistro, 
            nombreTabla, 
            detallesCambio
        ) VALUES (
            :nombreUsuario, 
            :descripcion, 
            :idRegistro, 
            :nombreTabla, 
            :detallesCambio
        )";

        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':nombreUsuario', $nombreUsuario);
        $stmt->bindParam(':descripcion', $descripcion);
        $stmt->bindParam(':idRegistro', $idRegistro, PDO::PARAM_INT);
        $stmt->bindParam(':nombreTabla', $nombreTabla);
        $stmt->bindParam(':detallesCambio', $detallesCambio);
        $stmt->execute();

        error_log("Bitácora registrada: " . $detallesCambio); // Log exitoso
    } catch (PDOException $e) {
        error_log("Error en bitácora: " . $e->getMessage()); // Log de error
    }
}
?>