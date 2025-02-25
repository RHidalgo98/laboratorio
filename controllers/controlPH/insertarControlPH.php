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
    $hora = $_POST['hora'];
    $primario = isset($_POST['primario']) ? floatval($_POST['primario']) : 0;
    $mezclado = isset($_POST['mezclado']) ? floatval($_POST['mezclado']) : 0;
    $residual = isset($_POST['residual']) ? floatval($_POST['residual']) : 0;
    $sulfitado = isset($_POST['sulfitado']) ? floatval($_POST['sulfitado']) : 0;
    $filtrado = isset($_POST['filtrado']) ? floatval($_POST['filtrado']) : 0;
    $alcalizado = isset($_POST['alcalizado']) ? floatval($_POST['alcalizado']) : 0;
    $clarificado = isset($_POST['clarificado']) ? floatval($_POST['clarificado']) : 0;
    $meladura = isset($_POST['meladura']) ? floatval($_POST['meladura']) : 0;
    $observacion = $_POST['observacion'];
    $periodoZafra = $_POST['periodoZafra'];
    $fechaIngreso = $_POST['fechaIngreso'];

    // Validar campos numéricos negativos
    if (empty($periodoZafra) || empty($fechaIngreso) || empty($hora)) {
        $error = "Todos los campos son obligatorios.";
        header("Location: " . BASE_PATH . "forms/registrarControlPH.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
        exit();
    }

    // Validar campos numéricos negativos
    if ($primario < 0 || $mezclado < 0 || $residual < 0 || $sulfitado < 0 || $filtrado < 0 || $alcalizado < 0 || $clarificado < 0 || $meladura < 0) {
        $error = "Los valores de Primario, Mezclado, Residual, Sulfitado, Filtrado, Alcalizado, Clarificado y Meladura no pueden ser negativos.";
        header("Location: " . BASE_PATH . "forms/registrarControlPH.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
        exit();
    }

    try {
        // Verificar duplicados
        $checkQuery = "SELECT COUNT(*) FROM controlph WHERE hora = :hora AND periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
        $checkStmt = $conexion->prepare($checkQuery);
        $checkStmt->bindParam(':hora', $hora);
        $checkStmt->bindParam(':periodoZafra', $periodoZafra);
        $checkStmt->bindParam(':fechaIngreso', $fechaIngreso);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() > 0) {
            $error = "Ya existe un registro con la misma hora, periodo de zafra y fecha.";
            header("Location: " . BASE_PATH . "forms/registrarControlPH.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&hora=" . urlencode($hora) . "&primario=" . urlencode($primario) . "&mezclado=" . urlencode($mezclado) . "&residual=" . urlencode($residual) . "&sulfitado=" . urlencode($sulfitado) . "&filtrado=" . urlencode($filtrado) . "&alcalizado=" . urlencode($alcalizado) . "&clarificado=" . urlencode($clarificado) . "&meladura=" . urlencode($meladura) . "&observacion=" . urlencode($observacion));
            exit();
        }

        // Consulta de inserción
        $sql = "INSERT INTO controlph (hora, primario, mezclado, residual, sulfitado, filtrado, alcalizado, clarificado, meladura, observacion, periodoZafra, fechaIngreso) 
                VALUES (:hora, :primario, :mezclado, :residual, :sulfitado, :filtrado, :alcalizado, :clarificado, :meladura, :observacion, :periodoZafra, :fechaIngreso)";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':hora', $hora);
        $stmt->bindParam(':primario', $primario);
        $stmt->bindParam(':mezclado', $mezclado);
        $stmt->bindParam(':residual', $residual);
        $stmt->bindParam(':sulfitado', $sulfitado);
        $stmt->bindParam(':filtrado', $filtrado);
        $stmt->bindParam(':alcalizado', $alcalizado);
        $stmt->bindParam(':clarificado', $clarificado);
        $stmt->bindParam(':meladura', $meladura);
        $stmt->bindParam(':observacion', $observacion);
        $stmt->bindParam(':periodoZafra', $periodoZafra);
        $stmt->bindParam(':fechaIngreso', $fechaIngreso);

        if ($stmt->execute()) {
            $idRegistro = $conexion->lastInsertId();

            // Construir detalles del cambio
            $detallesCambio = "Hora: $hora, Primario: $primario, Mezclado: $mezclado, Residual: $residual, Sulfitado: $sulfitado, Filtrado: $filtrado, Alcalizado: $alcalizado, Clarificado: $clarificado,  Meladura: $meladura, Observación: $observacion";

            // Añadir entrada a la bitácora
            registrarBitacora(
            $_SESSION['nombre'],
            "Inserción de registro en Control PH", 
            $idRegistro,
            "controlph", 
            $detallesCambio
            );
            // Redirigir de vuelta a mostrar registros con filtros y mensaje de éxito
            header("Location: " . BASE_PATH . "controllers/controlPH/mostrarControlPH.php?mensaje=Registro+insertado+correctamente&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
            exit();
        } else {
            $error = "Error al crear el registro. Por favor, intente de nuevo.";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }

    // Redirigir con mensaje de error si algo falla
    if (!empty($error)) {
        header("Location: " . BASE_PATH . "forms/registrarControlPH.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&hora=" . urlencode($hora) . "&primario=" . urlencode($primario) . "&mezclado=" . urlencode($mezclado) . "&residual=" . urlencode($residual) . "&sulfitado=" . urlencode($sulfitado) . "&filtrado=" . urlencode($filtrado) . "&alcalizado=" . urlencode($alcalizado) . "&clarificado=" . urlencode($clarificado) . "&meladura=" . urlencode($meladura) . "&observacion=" . urlencode($observacion));
        exit();
    }
}
