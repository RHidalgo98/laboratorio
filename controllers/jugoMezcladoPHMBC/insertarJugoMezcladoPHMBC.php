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

// Inicializar variables de error y campos
$error = "";

// Verificar si el formulario fue enviado por método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibir y sanitizar las entradas del formulario
    $hora = $_POST['hora'];
    $marcador = isset($_POST['marcador']) && $_POST['marcador'] !== '' ? floatval($_POST['marcador']) : null;
    $totalizador = isset($_POST['totalizador']) ? floatval($_POST['totalizador']) : 0;
    $observacion = $_POST['observacion'];
    $periodoZafra = $_POST['periodoZafra'];
    $fechaIngreso = $_POST['fechaIngreso'];

    // Verificar si ya existe un valor inicial para el mismo periodo de zafra y fecha
    $valorInicialQuery = "SELECT valorInicial FROM jugomezcladophmbc WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso LIMIT 1";
    $valorInicialStmt = $conexion->prepare($valorInicialQuery);
    $valorInicialStmt->bindParam(':periodoZafra', $periodoZafra);
    $valorInicialStmt->bindParam(':fechaIngreso', $fechaIngreso);
    $valorInicialStmt->execute();
    $existingValorInicial = $valorInicialStmt->fetchColumn();

    if ($existingValorInicial !== false) {
        $valorInicial = $existingValorInicial;
    } else {
        $valorInicial = isset($_POST['valorInicial']) ? floatval($_POST['valorInicial']) : 0;
    }

    // Validar campos obligatorios
    if (empty($hora) || empty($periodoZafra) || empty($fechaIngreso)) {
        $error = "El campo hora es obligatorio.";
    }

    // Validar que los valores numéricos no sean negativos
    if ($marcador < 0 || $totalizador < 0 || $valorInicial < 0) {
        $error = "Los valores no pueden ser negativos.";
    }

    try {
        // Verificar si ya existe un registro para la misma hora, periodo de zafra y fecha
        $checkQuery = "SELECT COUNT(*) FROM jugomezcladophmbc WHERE hora = :hora AND periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
        $checkStmt = $conexion->prepare($checkQuery);
        $checkStmt->bindParam(':hora', $hora);
        $checkStmt->bindParam(':periodoZafra', $periodoZafra);
        $checkStmt->bindParam(':fechaIngreso', $fechaIngreso);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() > 0) {
            $error = "Ya existe un registro con la misma hora, periodo de zafra y fecha.";
            header("Location: " . BASE_PATH . "forms/registrarJugoMezcladoPHMBC.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&hora=" . urlencode($hora) . "&marcador=" . urlencode((string)$marcador) . "&totalizador=" . urlencode($totalizador) . "&valorInicial=" . urlencode($valorInicial) . "&observacion=" . urlencode($observacion));
            exit();
        }

        // Insertar los datos en la base de datos
        $insertQuery = "INSERT INTO jugomezcladophmbc (hora, marcador, totalizador, valorInicial, observacion, periodoZafra, fechaIngreso) 
                        VALUES (:hora, :marcador, :totalizador, :valorInicial, :observacion, :periodoZafra, :fechaIngreso)";
        $insertStmt = $conexion->prepare($insertQuery);
        $insertStmt->bindParam(':hora', $hora);
        if ($marcador === null) {
            $insertStmt->bindValue(':marcador', null, PDO::PARAM_NULL);
        } else {
            $insertStmt->bindParam(':marcador', $marcador);
        }
        $insertStmt->bindParam(':totalizador', $totalizador);
        $insertStmt->bindParam(':valorInicial', $valorInicial);
        $insertStmt->bindParam(':observacion', $observacion);
        $insertStmt->bindParam(':periodoZafra', $periodoZafra);
        $insertStmt->bindParam(':fechaIngreso', $fechaIngreso);

        // Ejecutar la consulta
        if ($insertStmt->execute()) {
            $idRegistro = $conexion->lastInsertId();

            // Construir detalles del cambio
            $detallesCambio = "Hora: $hora, Marcador: $marcador, Totalizador: $totalizador, Valor Inicial: $valorInicial, Observación: $observacion";

            // Añadir entrada a la bitácora
            registrarBitacora(
            $_SESSION['nombre'],
            "Inserción de registro en Jugo Mezclado PHMBC", 
            $idRegistro,
            "jugomezcladophmbc", 
            $detallesCambio
            );
            // Redirigir al formulario de mostrar con un mensaje de éxito
            header("Location: " . BASE_PATH . "controllers/jugoMezcladoPHMBC/mostrarJugoMezcladoPHMBC.php?periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&mensaje=Registro+insertado+correctamente");
            exit();
        } else {
            $error = "Error al registrar los datos. Por favor, intente de nuevo.";
        }
    } catch (PDOException $e) {
        $error = "Error en la base de datos: " . $e->getMessage();
    }

    // Redirigir con mensaje de error si algo falla
    if (!empty($error)) {
        header("Location: " . BASE_PATH . "forms/registrarJugoMezcladoPHMBC.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&hora=" . urlencode($hora) . "&marcador=" . urlencode((string)$marcador) . "&totalizador=" . urlencode($totalizador) . "&valorInicial=" . urlencode($valorInicial) . "&observacion=" . urlencode($observacion));
        exit();
    }
}
