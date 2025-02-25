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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correoElectronico = $_POST['correoElectronico'];
    $contrasena = ($_POST['contrasena']);
    $rol = $_POST['rol'];
    $estado = $_POST['estado'];

    try {
        // Validación de correo electrónico
        if (!filter_var($correoElectronico, FILTER_VALIDATE_EMAIL)) {
            $error = "El correo electrónico no es válido.";
            header("Location: " . BASE_PATH . "forms/registrarUsuario.php?error=" . urlencode($error));
            exit();
        }

        // Consulta de inserción
        $sql = "INSERT INTO usuarios (nombre, apellido, correoElectronico, contrasena, rol, estado) VALUES (:nombre, :apellido, :correoElectronico, :contrasena, :rol, :estado)";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido', $apellido);
        $stmt->bindParam(':correoElectronico', $correoElectronico);
        $stmt->bindParam(':contrasena', $contrasena);
        $stmt->bindParam(':rol', $rol);
        $stmt->bindParam(':estado', $estado);

        if ($stmt->execute()) {
            $idRegistro = $conexion->lastInsertId();

            // Construir detalles del cambio
            $detallesCambio = "Nombre: $nombre, Apellido: $apellido, Correo Electrónico: $correoElectronico, Rol: $rol, Estado: $estado";

            // Añadir entrada a la bitácora
            registrarBitacora(
            $_SESSION['nombre'],
            "Inserción de nuevo usuario", 
            $idRegistro,
            "usuarios", 
            $detallesCambio
            );
            // Redirigir al mostrar usuarios con mensaje de éxito
            header("Location: " . BASE_PATH . "controllers/usuario/mostrarUsuario.php?mensaje=Registro+insertado+correctamente");
            exit();
        } else {
            $error = "Error al agregar el usuario";
        }
    } catch (PDOException $e) {
        $error = "Error de base de datos: " . $e->getMessage();
    }
}
?>
