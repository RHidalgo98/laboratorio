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
    $humedad = isset($_POST['humedad']) ? floatval($_POST['humedad']) : 0;
    $fibra = isset($_POST['fibra']) ? floatval($_POST['fibra']) : 0;
    $observacion = $_POST['observacion'];
    $periodoZafra = $_POST['periodoZafra'];
    $fechaIngreso = $_POST['fechaIngreso'];

    // Validar que todos los campos requeridos estén presentes
    if (empty($periodoZafra) || empty($fechaIngreso) || empty($hora)) {
        $error = "Todos los campos son obligatorios.";
        header("Location: " . BASE_PATH . "forms/registrarCachaza.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
        exit();
    }
    
    // Validar campos numéricos negativos
    $valores = [$humedad, $fibra];
    foreach ($valores as $valor) {
        if ($valor < 0) {
            $error = "Los valores de Humedad y Fibra no pueden ser negativos.";
            header("Location: " . BASE_PATH . "forms/registrarCachaza.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
            exit();
        }
    }

    try {
        // Verificar duplicados
        $checkQuery = "SELECT COUNT(*) FROM cachaza WHERE hora = :hora AND periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
        $checkStmt = $conexion->prepare($checkQuery);
        $checkStmt->bindParam(':hora', $hora);
        $checkStmt->bindParam(':periodoZafra', $periodoZafra);
        $checkStmt->bindParam(':fechaIngreso', $fechaIngreso);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() > 0) {
            $error = "Ya existe un registro con la misma hora, periodo de zafra y fecha.";
            header("Location: " . BASE_PATH . "forms/registrarCachaza.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&hora=" . urlencode($hora) . "&humedad=" . urlencode($humedad) . "&fibra=" . urlencode($fibra) . "&observacion=" . urlencode($observacion));
            exit();
        }

        // Consulta de inserción
        $sql = "INSERT INTO cachaza (hora, humedad, fibra, observacion, periodoZafra, fechaIngreso) 
                VALUES (:hora, :humedad, :fibra, :observacion, :periodoZafra, :fechaIngreso)";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':hora', $hora);
        $stmt->bindParam(':humedad', $humedad);
        $stmt->bindParam(':fibra', $fibra);
        $stmt->bindParam(':observacion', $observacion);
        $stmt->bindParam(':periodoZafra', $periodoZafra);
        $stmt->bindParam(':fechaIngreso', $fechaIngreso);

        if ($stmt->execute()) {
            $idRegistro = $conexion->lastInsertId();

            // Construir detalles del cambio
            $detallesCambio = "Hora: $hora, Humedad: $humedad, Fibra: $fibra, Observación: $observacion";

            // Añadir entrada a la bitácora
            registrarBitacora(
            $_SESSION['nombre'],
            "Inserción de registro en Cachaza", 
            $idRegistro,
            "cachaza", 
            $detallesCambio
            );
            // Redirigir de vuelta a mostrar registros con filtros y mensaje de éxito
            header("Location: " . BASE_PATH . "controllers/cachaza/mostrarCachaza.php?mensaje=Registro+insertado+correctamente&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
            exit();
        } else {
            $error = "Error al crear el registro. Por favor, intente de nuevo.";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }

    // Redirigir con mensaje de error si algo falla
    if (!empty($error)) {
        header("Location: " . BASE_PATH . "forms/registrarCachaza.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&hora=" . urlencode($hora) . "&humedad=" . urlencode($humedad) . "&fibra=" . urlencode($fibra) . "&observacion=" . urlencode($observacion));
        exit();
    }
}
?>
