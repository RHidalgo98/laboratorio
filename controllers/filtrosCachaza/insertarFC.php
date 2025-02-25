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
    $hora = $_POST['hora'];
    $pol1 = isset($_POST['pol1']) ? floatval($_POST['pol1']) : 0;
    $gFt1 = isset($_POST['gFt1']) ? floatval($_POST['gFt1']) : 0;
    $pol2 = isset($_POST['pol2']) ? floatval($_POST['pol2']) : 0;
    $gFt2 = isset($_POST['gFt2']) ? floatval($_POST['gFt2']) : 0;
    $pol3 = isset($_POST['pol3']) ? floatval($_POST['pol3']) : 0;
    $gFt3 = isset($_POST['gFt3']) ? floatval($_POST['gFt3']) : 0;
    $observacion = $_POST['observacion'];
    $periodoZafra = $_POST['periodoZafra'];
    $fechaIngreso = $_POST['fechaIngreso'];

    // Validar que todos los campos requeridos estén presentes
    if (empty($periodoZafra) || empty($fechaIngreso) || empty($hora)) {
        $error = "Todos los campos son obligatorios.";
        header("Location: " . BASE_PATH . "forms/registrarFiltrosCachaza.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
        exit();
    }

    // Validar campos numéricos negativos
    $valores = [$pol1, $gFt1, $pol2, $gFt2, $pol3, $gFt3];
    foreach ($valores as $valor) {
        if ($valor < 0) {
            $error = "Los valores no pueden ser negativos.";
            header("Location: " . BASE_PATH . "forms/registrarFiltrosCachaza.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
            exit();
        }
    }

    try {
        // Verificar duplicados
        $checkQuery = "SELECT COUNT(*) FROM filtrocachaza WHERE hora = :hora AND periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
        $checkStmt = $conexion->prepare($checkQuery);
        $checkStmt->bindParam(':hora', $hora);
        $checkStmt->bindParam(':periodoZafra', $periodoZafra);
        $checkStmt->bindParam(':fechaIngreso', $fechaIngreso);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() > 0) {
            $error = "Ya existe un registro con la misma hora, periodo de zafra y fecha.";
            header("Location: " . BASE_PATH . "forms/registrarFiltrosCachaza.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&hora=" . urlencode($hora) . "&pol1=" . urlencode($pol1) . "&gFt1=" . urlencode($gFt1) . "&pol2=" . urlencode($pol2) . "&gFt2=" . urlencode($gFt2) . "&pol3=" . urlencode($pol3) . "&gFt3=" . urlencode($gFt3) . "&observacion=" . urlencode($observacion));
            exit();
        }

        $sql = "INSERT INTO filtrocachaza (hora, pol1, gFt1, pol2, gFt2, pol3, gFt3, observacion, periodoZafra, fechaIngreso) 
                VALUES (:hora, :pol1, :gFt1, :pol2, :gFt2, :pol3, :gFt3, :observacion, :periodoZafra, :fechaIngreso)";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':hora', $hora);
        $stmt->bindParam(':pol1', $pol1);
        $stmt->bindParam(':gFt1', $gFt1);
        $stmt->bindParam(':pol2', $pol2);
        $stmt->bindParam(':gFt2', $gFt2);
        $stmt->bindParam(':pol3', $pol3);
        $stmt->bindParam(':gFt3', $gFt3);
        $stmt->bindParam(':observacion', $observacion);
        $stmt->bindParam(':periodoZafra', $periodoZafra);
        $stmt->bindParam(':fechaIngreso', $fechaIngreso);

        if ($stmt->execute()) {
            $idRegistro = $conexion->lastInsertId();

            // Construir detalles del cambio
            $detallesCambio = "Hora: $hora, Pol1: $pol1, GFt1: $gFt1, Pol2: $pol2, GFt2: $gFt2, Pol3: $pol3, GFt3: $gFt3, Observación: $observacion";

            // Añadir entrada a la bitácora
            registrarBitacora(
            $_SESSION['nombre'],
            "Inserción de registro en Filtro Cachaza", 
            $idRegistro,
            "filtrocachaza", 
            $detallesCambio
            );
            // Redirigir de vuelta a mostrar registros con filtros y mensaje de éxito
            header("Location: " . BASE_PATH . "controllers/filtrosCachaza/mostrarFC.php?mensaje=Registro+insertado+correctamente&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
            exit();
        } else {
            $error = "Error al crear el registro. Por favor, intente de nuevo.";
        }

    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }

    // Redirigir con mensaje de error si algo falla
    if (!empty($error)) {
        header("Location: " . BASE_PATH . "forms/registrarFiltrosCachaza.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&hora=" . urlencode($hora) . "&pol1=" . urlencode($pol1) . "&gFt1=" . urlencode($gFt1) . "&pol2=" . urlencode($pol2) . "&gFt2=" . urlencode($gFt2) . "&pol3=" . urlencode($pol3) . "&gFt3=" . urlencode($gFt3) . "&observacion=" . urlencode($observacion));
        exit();
    }
}
