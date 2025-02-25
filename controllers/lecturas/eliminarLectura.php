<?php
include $_SERVER['DOCUMENT_ROOT'] . '/AnalisisLaboratorio/config/config.php';
include ROOT_PATH . 'config/conexion.php';

// Iniciar sesión si no está iniciada
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['idUsuario'])) {
    // Si no hay sesión activa, redirigir al formulario de login
    header("Location: " . BASE_PATH . "login.html");
    exit(); // Asegurar que el script se detenga después de redirigir
}

// Verificar si se ha enviado el ID, periodo de zafra y fecha de ingreso por la URL
if (isset($_GET['id']) && isset($_GET['periodoZafra']) && isset($_GET['fechaIngreso'])) {
    $idLectura = $_GET['id'];
    $periodoZafra = $_GET['periodoZafra'];
    $fechaIngreso = $_GET['fechaIngreso'];

    try {
        // Preparar la consulta de eliminación
        $stmt = $conexion->prepare("DELETE FROM lecturas WHERE idLectura = :idLectura");
        $stmt->bindParam(':idLectura', $idLectura, PDO::PARAM_INT);

        // Ejecutar la consulta
        if ($stmt->execute()) {
            // Redirigir a la página de mostrar registros con los filtros seleccionados
            $redirectUrl = BASE_PATH . "controllers/lecturas/mostrarLectura.php?mensaje=Registro+eliminado+correctamente";
            $redirectUrl .= "&periodoZafra=" . urlencode($periodoZafra);
            $redirectUrl .= "&fechaIngreso=" . urlencode($fechaIngreso);
            header("Location: $redirectUrl");
            exit();
        } else {
            echo "Error al eliminar el registro.";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "ID o filtros no recibidos.";
}
?>
