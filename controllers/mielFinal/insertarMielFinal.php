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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $periodoZafra = $_POST['periodoZafra'];
    $fechaIngreso = $_POST['fechaIngreso'];
    $hora = $_POST['hora'];
    $num = $_POST['num'];
    $brix = floatval($_POST['brix']);
    $pol = floatval($_POST['pol']);
    $azucRed = isset($_POST['azucRed']) ? floatval($_POST['azucRed']) : 0;
    $observacion = $_POST['observacion'];

    // Validar que todos los campos requeridos estén presentes
    if (empty($periodoZafra) || empty($fechaIngreso) || empty($hora) || empty($num) || empty($brix) || empty($pol)) {
        $error = "Los valores de Brix y Pol no pueden estar vacios.";
        header("Location: " . BASE_PATH . "forms/registrarMielFinal.php?id=" . urlencode($idMielFinal) . "&error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
        exit();
    }

    // Validar campos numéricos negativos
    if ($brix <= 0 || $pol <= 0 || $azucRed < 0) {
        $error = "Los valores de Brix, Pol y Azúcar Reducido no pueden ser negativos.";
        header("Location: " . BASE_PATH . "forms/registrarMielFinal.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
        exit();
    }

    try {
        // Verificar duplicados
        $checkQuery = "SELECT COUNT(*) FROM mielfinal WHERE (hora = :hora OR num = :num) AND periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
        $checkStmt = $conexion->prepare($checkQuery);
        $checkStmt->bindParam(':hora', $hora);
        $checkStmt->bindParam(':num', $num);
        $checkStmt->bindParam(':periodoZafra', $periodoZafra);
        $checkStmt->bindParam(':fechaIngreso', $fechaIngreso);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() > 0) {
            $error = "Ya existe un registro con el mismo número, hora, periodo de zafra y fecha.";
            header("Location: " . BASE_PATH . "forms/registrarMielFinal.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&hora=" . urlencode($hora) . "&num=" . urlencode($num) . "&brix=" . urlencode($brix) . "&pol=" . urlencode($pol) . "&azucRed=" . urlencode($azucRed) . "&observacion=" . urlencode($observacion));
            exit();
        }

        // Insertar registro en la base de datos
        $sql = "INSERT INTO mielfinal (periodoZafra, fechaIngreso, hora, num, brix, pol, azucRed, observacion) VALUES (:periodoZafra, :fechaIngreso, :hora, :num, :brix, :pol, :azucRed, :observacion)";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':periodoZafra', $periodoZafra);
        $stmt->bindParam(':fechaIngreso', $fechaIngreso);
        $stmt->bindParam(':hora', $hora);
        $stmt->bindParam(':num', $num);
        $stmt->bindParam(':brix', $brix);
        $stmt->bindParam(':pol', $pol);
        $stmt->bindParam(':azucRed', $azucRed);
        $stmt->bindParam(':observacion', $observacion);

        if ($stmt->execute()) {
            $idRegistro = $conexion->lastInsertId();

            // Construir detalles del cambio
            $detallesCambio = "Hora: $hora, Número: $num, Brix: $brix, Pol: $pol, Azúcar Reducido: $azucRed, Observación: $observacion";

            // Añadir entrada a la bitácora
            registrarBitacora(
            $_SESSION['nombre'],
            "Inserción de registro en Miel Final", 
            $idRegistro,
            "mielfinal", 
            $detallesCambio
            );
            // Redirigir a mostrarMielFinal.php con un mensaje de éxito
            header("Location: " . BASE_PATH . "controllers/mielFinal/mostrarMielFinal.php?periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&mensaje=Registro+insertado+correctamente");
            exit();
        } else {
            throw new Exception('Error al registrar en la base de datos.');
        }
    } catch (Exception $e) {
        $error = $e->getMessage(); // Captura el mensaje real del error
        // Redirigir al formulario de registro con el mensaje de error y datos previamente ingresados
        header("Location: " . BASE_PATH . "forms/registrarMielFinal.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&hora=" . urlencode($hora) . "&num=" . urlencode($num) . "&brix=" . urlencode($brix) . "&pol=" . urlencode($pol) . "&azucRed=" . urlencode($azucRed) . "&observacion=" . urlencode($observacion));
        exit();
    }
}
?>
