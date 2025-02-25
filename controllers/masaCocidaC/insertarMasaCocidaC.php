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

// Verificar si se ha enviado el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibir y sanitizar las entradas del formulario
    $num = $_POST['num'];
    $hora = $_POST['hora'];
    $tacho = $_POST['tacho'];
    $volFt3 = $_POST['volFt3'];
    $brix = floatval($_POST['brix']);
    $pol = floatval($_POST['pol']);
    $observacion = $_POST['observacion'];
    $periodoZafra = $_POST['periodoZafra'];
    $fechaIngreso = $_POST['fechaIngreso'];

    // Validar que todos los campos requeridos estén presentes
    if (empty($periodoZafra) || empty($fechaIngreso) || empty($num) || empty($hora) || empty($tacho) || empty($volFt3) || empty($brix) || empty($pol)) {
        $error = "Todos los campos son obligatorios.";
        header("Location: " . BASE_PATH . "forms/registrarMasaCocidaC.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
        exit();
    }

    // Validar campos numéricos negativos
    if ($tacho < 0 || $volFt3 <= 0 || $brix <= 0 || $pol <= 0) {
        $error = "Los valores de Tacho, Vol Ft³, Brix y Pol no pueden ser negativos.";
        header("Location: " . BASE_PATH . "forms/registrarMasaCocidaC.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
        exit();
    }

    try {
        // Verificar duplicados
        $checkQuery = "SELECT COUNT(*) FROM masacocidac WHERE (hora = :hora OR num = :num) AND periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
        $checkStmt = $conexion->prepare($checkQuery);
        $checkStmt->bindParam(':hora', $hora);
        $checkStmt->bindParam(':num', $num);
        $checkStmt->bindParam(':periodoZafra', $periodoZafra);
        $checkStmt->bindParam(':fechaIngreso', $fechaIngreso);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() > 0) {
            $error = "Ya existe un registro con la misma hora o número, periodo de zafra y fecha.";
            header("Location: " . BASE_PATH . "forms/registrarMasaCocidaC.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&num=" . urlencode($num) . "&hora=" . urlencode($hora) . "&tacho=" . urlencode($tacho) . "&volFt3=" . urlencode($volFt3) . "&brix=" . urlencode($brix) . "&pol=" . urlencode($pol) . "&observacion=" . urlencode($observacion));
            exit();
        }

        // Consulta de inserción
        $sql = "INSERT INTO masacocidac (num, hora, tacho, volFt3, brix, pol, observacion, periodoZafra, fechaIngreso) 
                VALUES (:num, :hora, :tacho, :volFt3, :brix, :pol, :observacion, :periodoZafra, :fechaIngreso)";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':num', $num);
        $stmt->bindParam(':hora', $hora);
        $stmt->bindParam(':tacho', $tacho);
        $stmt->bindParam(':volFt3', $volFt3);
        $stmt->bindParam(':brix', $brix);
        $stmt->bindParam(':pol', $pol);
        $stmt->bindParam(':observacion', $observacion);
        $stmt->bindParam(':periodoZafra', $periodoZafra);
        $stmt->bindParam(':fechaIngreso', $fechaIngreso);

        if ($stmt->execute()) {
            $idRegistro = $conexion->lastInsertId();

            // Construir detalles del cambio
            $detallesCambio = "Num: $num, Hora: $hora, Tacho: $tacho, VolFt3: $volFt3, Brix: $brix, Pol: $pol, Observación: $observacion";

            // Añadir entrada a la bitácora
            registrarBitacora(
            $_SESSION['nombre'],
            "Inserción de registro en Masa Cocida C", 
            $idRegistro,
            "masacocidac", 
            $detallesCambio
            );
            // Redirigir de vuelta a mostrar registros con filtros y mensaje de éxito
            header("Location: " . BASE_PATH . "controllers/masaCocidaC/mostrarMasaCocidaC.php?mensaje=Registro+insertado+correctamente&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
            exit();
        } else {
            $error = "Error al crear el registro. Por favor, intente de nuevo.";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }

    // Redirigir con mensaje de error si algo falla
    if (!empty($error)) {
        header("Location: " . BASE_PATH . "forms/registrarMasaCocidaC.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&num=" . urlencode($num) . "&hora=" . urlencode($hora) . "&tacho=" . urlencode($tacho) . "&volFt3=" . urlencode($volFt3) . "&brix=" . urlencode($brix) . "&pol=" . urlencode($pol) . "&observacion=" . urlencode($observacion));
        exit();
    }
}
?>
