<?php
include $_SERVER['DOCUMENT_ROOT'] . '/AnalisisLaboratorio/config/config.php';
include ROOT_PATH . 'config/conexion.php';
include ROOT_PATH . 'includes/bitacora.php';

// Iniciar sesión si no está iniciada
session_start();

// Verificar autenticación
if (!isset($_SESSION['idUsuario'])) {
    header("Location: " . BASE_PATH . "controllers/login.php");
    exit();
}

try {
    // Obtener datos del usuario por su ID
    $stmt = $conexion->prepare("SELECT rol, nombre FROM usuarios WHERE idUsuario = ?");
    $stmt->execute([$_SESSION['idUsuario']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar si el usuario existe
    if (!$usuario) {
        header("Location: " . BASE_PATH . "controllers/login.php");
        exit();
    }

    // Validar rol (ajusta según cómo se almacene en tu BD)
    if (strtolower($usuario['rol']) !== 'administrador') {
        echo "<script>
                alert('Acceso denegado: No tienes permisos de administrador.');
                window.location.href = '/AnalisisLaboratorio/index.php';
              </script>";
        exit();
    }

    // Asignar nombre de usuario
    $user_name = $usuario['nombre'] ?? 'Usuario';

} catch (PDOException $e) {
    error_log("Error de base de datos: " . $e->getMessage());
    header("Location: " . BASE_PATH . "error.php");
    exit();
}

$error = ""; // Variable para capturar errores

// Verificar si se ha enviado el ID por la URL
if (isset($_GET['id'])) {
    $idUsuario = $_GET['id'];

    try {
        // Obtener datos del usuario antes de eliminar
        $stmtSelect = $conexion->prepare("SELECT * FROM usuarios WHERE idUsuario = :id");
        $stmtSelect->bindParam(':id', $idUsuario, PDO::PARAM_INT);
        $stmtSelect->execute();
        $usuario = $stmtSelect->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Preparar detalles del cambio
            $detalles = "Usuario: {$usuario['nombre']}, ";
            $detalles .= "Email: {$usuario['correoElectronico']}, ";
            $detalles .= "Rol: {$usuario['rol']}";

            // Eliminar el usuario
            $stmtDelete = $conexion->prepare("DELETE FROM usuarios WHERE idUsuario = :id");
            $stmtDelete->bindParam(':id', $idUsuario, PDO::PARAM_INT);

            if ($stmtDelete->execute()) {
                // Registrar en bitácora
                registrarBitacora(
                    $_SESSION['nombre'], // Asegurar que existe en la sesión
                    "Eliminación de usuario",
                    $idUsuario,
                    "usuarios",
                    $detalles
                );

                // Redirección con parámetros
                $redirectUrl = BASE_PATH . "controllers/usuario/mostrarUsuario.php?mensaje=Usuario+eliminado+correctamente";
                header("Location: $redirectUrl");
                exit();
            }
        } else {
            $error = "El usuario no existe";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
} else {
    $error = "Parámetros incompletos";
}

// Manejo de errores
if (!empty($error)) {
    $redirectUrl = BASE_PATH . "controllers/usuario/mostrarUsuario.php?error=" . urlencode($error);
    header("Location: $redirectUrl");
    exit();
}
