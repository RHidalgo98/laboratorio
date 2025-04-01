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
    $observacion = $_POST['observacion'];

    // Validar que todos los campos requeridos estén presentes
    if (empty($periodoZafra) || empty($fechaIngreso) || empty($hora) || empty($num) || empty($brix) || empty($pol)) {
        $error = "Los valores de Brix y Pol no pueden estar vacios.";
        header("Location: " . BASE_PATH . "forms/registrarMielB.php?id=" . urlencode($idMielB) . "&error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
        exit();
    }
    
    // Validar campos numéricos negativos
    if ($brix <= 0 || $pol <= 0) {
        $error = "Los valores de Brix y Pol debe ser mayor que cero.";
        header("Location: " . BASE_PATH . "forms/registrarMielB.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
        exit();
    }

    try {
        // Verificar duplicados
        $checkQuery = "SELECT COUNT(*) FROM mielb WHERE (hora = :hora OR num = :num) AND periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
        $checkStmt = $conexion->prepare($checkQuery);
        $checkStmt->bindParam(':num', $num);
        $checkStmt->bindParam(':hora', $hora);
        $checkStmt->bindParam(':periodoZafra', $periodoZafra);
        $checkStmt->bindParam(':fechaIngreso', $fechaIngreso);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() > 0) {
            $error = "Ya existe un registro con el mismo número, hora, periodo de zafra y fecha.";
            header("Location: " . BASE_PATH . "forms/registrarMielB.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&hora=" . urlencode($hora) . "&num=" . urlencode($num) . "&brix=" . urlencode($brix) . "&pol=" . urlencode($pol) . "&observacion=" . urlencode($observacion));
            exit();
        }

        // Verificar que el num existe en Masa Cocida B
        $query = "SELECT brix, pol FROM masacocidab 
                  WHERE periodoZafra = :periodoZafra 
                  AND fechaIngreso = :fechaIngreso 
                  AND num = :num";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':periodoZafra', $periodoZafra);
        $stmt->bindParam(':fechaIngreso', $fechaIngreso);
        $stmt->bindParam(':num', $num);
        $stmt->execute();
        $masaData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$masaData) {
            throw new Exception('No se encontró el num en Masa Cocida B');
        }

        // Insertar registro en la base de datos
        $sql = "INSERT INTO mielb (periodoZafra, fechaIngreso, hora, num, brix, pol, observacion) VALUES (:periodoZafra, :fechaIngreso, :hora, :num, :brix, :pol, :observacion)";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':periodoZafra', $periodoZafra);
        $stmt->bindParam(':fechaIngreso', $fechaIngreso);
        $stmt->bindParam(':hora', $hora);
        $stmt->bindParam(':num', $num);
        $stmt->bindParam(':brix', $brix);
        $stmt->bindParam(':pol', $pol);
        $stmt->bindParam(':observacion', $observacion);

        if ($stmt->execute()) {
            $idRegistro = $conexion->lastInsertId();

            // Construir detalles del cambio
            $detallesCambio = "Hora: $hora, Num: $num, Brix: $brix, Pol: $pol, Observación: $observacion";

            // Añadir entrada a la bitácora
            registrarBitacora(
            $_SESSION['nombre'],
            "Inserción de registro en Miel B", 
            $idRegistro,
            "mielb", 
            $detallesCambio
            );
            // Redirigir a mostrarMielB.php con un mensaje de éxito
            header("Location: " . BASE_PATH . "controllers/mielB/mostrarMielB.php?periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&mensaje=Registro+insertado+correctamente");
            exit();
        } else {
            throw new Exception('Error al registrar en la base de datos.');
        }
    } catch (Exception $e) {
        $error = $e->getMessage(); // Captura el mensaje real del error
        // Redirigir al formulario de registro con el mensaje de error y datos previamente ingresados
        header("Location: " . BASE_PATH . "forms/registrarMielB.php?periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&error=" . urlencode($error) . "&hora=" . urlencode($hora) . "&num=" . urlencode($num) . "&brix=" . urlencode($brix) . "&pol=" . urlencode($pol) . "&observacion=" . urlencode($observacion));
        exit();
    }
}
?>
