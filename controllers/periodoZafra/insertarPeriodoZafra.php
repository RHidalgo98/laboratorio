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
    $periodo = $_POST['periodo'];
    $inicio = $_POST['inicio'];
    $fin = $_POST['fin'];
    $activo = $_POST['activo'];

    try {
        // Validación de formato AAAA-AAAA
        if (!preg_match('/^\d{4}-\d{4}$/', $periodo)) {
            $error = "El periodo debe estar en el formato AAAA-AAAA. Ejemplo: 2025-2026.";
            header("Location: " . BASE_PATH . "forms/registrarPeriodoZafra.php?error=" . urlencode($error));
            exit();
        }

        // Consulta de inserción
        $sql = "INSERT INTO periodoszafra (periodo, inicio, fin, activo) VALUES (:periodo, :inicio, :fin, :activo)";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':periodo', $periodo);
        $stmt->bindParam(':inicio', $inicio);
        $stmt->bindParam(':fin', $fin);
        $stmt->bindParam(':activo', $activo);

        if ($stmt->execute()) {
            $idRegistro = $conexion->lastInsertId();

            // Construir detalles del cambio
            $detallesCambio = "Periodo: $periodo, Inicio: $inicio, Fin: $fin, Activo: $activo";

            // Añadir entrada a la bitácora
            registrarBitacora(
            $_SESSION['nombre'],
            "Inserción de registro en Periodos de Zafra", 
            $idRegistro,
            "periodoszafra", 
            $detallesCambio
            );
            // Redirigir al mostrar periodos con mensaje de éxito
            header("Location: " . BASE_PATH . "controllers/periodoZafra/mostrarPeriodoZafra.php?mensaje=Registro+insertado+correctamente");
            exit();
        } else {
            $error = "Error al agregar el periodo de zafra";
        }
    } catch (PDOException $e) {
        $error = "Error de base de datos: " . $e->getMessage();
    }
}