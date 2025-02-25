<?php
include $_SERVER['DOCUMENT_ROOT'] . '/AnalisisLaboratorio/config/config.php';
include ROOT_PATH . 'config/conexion.php';
include ROOT_PATH . 'includes/bitacora.php';

// Iniciar sesión si no está iniciada
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['idUsuario'])) {
    // Si no hay sesión activa, redirigir al formulario de login
    header("Location: " . BASE_PATH . "login.html");
    exit(); // Asegurar que el script se detenga después de redirigir
}

$error = ""; // Variable para capturar errores

// Verificar si se ha enviado el ID, periodo de zafra y fecha de ingreso por la URL
if (isset($_GET['id']) && isset($_GET['periodoZafra']) && isset($_GET['fechaIngreso'])) {
    $idEfluentes = $_GET['id'];
    $periodoZafra = $_GET['periodoZafra'];
    $fechaIngreso = $_GET['fechaIngreso'];

    try {
        // Obtener datos del registro antes de eliminar
        $stmtSelect = $conexion->prepare("SELECT * FROM efluentes WHERE idEfluentes = :id");
        $stmtSelect->bindParam(':id', $idEfluentes, PDO::PARAM_INT);
        $stmtSelect->execute();
        $registro = $stmtSelect->fetch(PDO::FETCH_ASSOC);

        if ($registro) {
            // Preparar detalles del cambio
            $detalles = "Hora: {$registro['hora']}, ";
            $detalles .= "Enfriamiento: {$registro['enfriamiento']}, ";
            $detalles .= "Retorno: {$registro['retorno']}, ";
            $detalles .= "Desechos: {$registro['desechos']}, ";
            $detalles .= "Observación: {$registro['observacion']}";
            
            // Eliminar el registro
            $stmtDelete = $conexion->prepare("DELETE FROM efluentes WHERE idEfluentes = :id");
            $stmtDelete->bindParam(':id', $idEfluentes, PDO::PARAM_INT);

            if ($stmtDelete->execute()) {
                // Registrar en bitácora
                registrarBitacora(
                    $_SESSION['nombre'], // Asegurar que existe en la sesión
                    "Eliminación de registro en Efluentes",
                    $idEfluentes,
                    "efluentes",
                    $detalles
                );

                // Redirección con parámetros
                $redirectUrl = BASE_PATH . "controllers/efluentes/mostrarEfluentes.php?mensaje=Registro+eliminado+correctamente";
                $redirectUrl .= "&periodoZafra=" . urlencode($periodoZafra);
                $redirectUrl .= "&fechaIngreso=" . urlencode($fechaIngreso);
                header("Location: $redirectUrl");
                exit();
            }
        } else {
            $error = "El registro no existe";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
} else {
    $error = "Parámetros incompletos";
}

// Manejo de errores
if (!empty($error)) {
    $redirectUrl = BASE_PATH . "controllers/efluentes/mostrarEfluentes.php?error=" . urlencode($error);
    $redirectUrl .= "&periodoZafra=" . urlencode($periodoZafra);
    $redirectUrl .= "&fechaIngreso=" . urlencode($fechaIngreso);
    header("Location: $redirectUrl");
    exit();
}
?>