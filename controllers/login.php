<?php
// Habilitar reporte de errores (solo para desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Iniciar buffer de salida
ob_start();

// Establecer cabecera JSON
header('Content-Type: application/json');

try {
    include $_SERVER['DOCUMENT_ROOT'] . '/AnalisisLaboratorio/config/config.php';
    include __DIR__ . '/../config/conexion.php';

    // Validar método POST
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Método no permitido", 405);
    }

    // Obtener datos del POST
    $usuario = $_POST['usuario'] ?? '';
    $contraseña = $_POST['contraseña'] ?? '';

    // Validar campos vacíos
    if (empty($usuario) || empty($contraseña)) {
        throw new Exception("Usuario y contraseña son obligatorios", 400);
    }

    // Consulta preparada
    $stmt = $conexion->prepare("SELECT idUsuario, contrasena, nombre, rol, estado FROM usuarios WHERE correoElectronico = ?");
    $stmt->execute([$usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC); // Usar fetch() en lugar de fetchAll()

    // Verificar si el usuario existe
    if (!$user) {
        throw new Exception("Usuario no encontrado", 404);
    }

    // Verificar contraseña (¡usa password_verify() en producción!)
    if ($contraseña !== $user['contrasena']) {
        throw new Exception("Contraseña incorrecta", 401);
    }

    // Verificar estado del usuario
    if ($user['estado'] != 1) {
        throw new Exception("Cuenta desactivada. Contacte al administrador", 403);
    }

    // Iniciar sesión
    session_start();
    $_SESSION['idUsuario'] = $user['idUsuario'];
    $_SESSION['nombre'] = $user['nombre'];
    $_SESSION['correoElectronico'] = $usuario;
    $_SESSION['rol'] = $user['rol'];
    $_SESSION['estado'] = $user['estado'];

    // Respuesta exitosa
    echo json_encode([
        "success" => true,
        "idUsuario" => $user['idUsuario']
    ]);

} catch (Exception $e) {
    // Manejar errores
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
} finally {
    // Limpiar buffer y cerrar conexión
    ob_end_flush();
    $stmt = null;
    $conexion = null;
}
?>