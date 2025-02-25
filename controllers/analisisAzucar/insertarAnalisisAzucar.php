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
    $templa = $_POST['templa'];
    $color = $_POST['color'];
    $turbidez = $_POST['turbidez'];
    $vitaminaA = isset($_POST['vitaminaA']) && $_POST['vitaminaA'] !== '' ? floatval($_POST['vitaminaA']) : 0;
    $pol = floatval($_POST['pol']);
    $humedad = floatval($_POST['humedad']);
    $cenizas = floatval($_POST['cenizas']);
    $particulasFe = isset($_POST['particulasFe']) && $_POST['particulasFe'] === 'Si' ? 1 : 0;
    $observacion = $_POST['observacion'];
    $periodoZafra = $_POST['periodoZafra'];
    $fechaIngreso = $_POST['fechaIngreso'];

    // Validar que todos los campos requeridos estén presentes
    if (empty($periodoZafra) || empty($fechaIngreso) || empty($hora) || empty($templa)) {
        $error = "Todos los campos son obligatorios.";
    }
    
    // Validar que los valores no sean negativos o cero
    if (empty($error)) {
        if ($color <= 0) {
            $error = "El valor de Color debe ser mayor que cero.";
        } elseif ($turbidez <= 0) {
            $error = "El valor de Turbidez debe ser mayor que cero.";
        } elseif ($vitaminaA < 0) {
            $error = "El valor de Vitamina A no puede ser negativo.";
        } elseif ($pol <= 0) {
            $error = "El valor de Pol debe ser mayor que cero.";
        } elseif ($humedad <= 0) {
            $error = "El valor de Humedad debe ser mayor que cero.";
        } elseif ($cenizas <= 0) {
            $error = "El valor de Cenizas debe ser mayor que cero.";
        }
    }

    if (empty($error)) {
        try {
            // Verificar duplicados
            $checkQuery = "SELECT COUNT(*) FROM analisisazucar WHERE (templa = :templa OR hora = :hora) AND periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
            $checkStmt = $conexion->prepare($checkQuery);
            $checkStmt->bindParam(':templa', $templa);
            $checkStmt->bindParam(':hora', $hora);
            $checkStmt->bindParam(':periodoZafra', $periodoZafra);
            $checkStmt->bindParam(':fechaIngreso', $fechaIngreso);
            $checkStmt->execute();

            if ($checkStmt->fetchColumn() > 0) {
                $error = "Ya existe un registro con la misma hora o templa, periodo de zafra y fecha.";
                header("Location: " . BASE_PATH . "forms/registrarAnalisisAzucar.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso). "&hora=" . urlencode($hora) . "&templa=" . urlencode($templa) . "&color=" . urlencode($color) . "&turbidez=" . urlencode($turbidez) . "&vitaminaA=" . urlencode($vitaminaA) . "&pol=" . urlencode($pol) . "&humedad=" . urlencode($humedad) . "&cenizas=" . urlencode($cenizas) . "&particulasFe=" . urlencode($particulasFe) . "&observacion=" . urlencode($observacion));
                exit();
            }

            // Consulta de inserción
            $sql = "INSERT INTO analisisazucar (hora, templa, color, turbidez, vitaminaA, pol, humedad, cenizas, particulasFe, observacion, periodoZafra, fechaIngreso) 
                    VALUES (:hora, :templa, :color, :turbidez, :vitaminaA, :pol, :humedad, :cenizas, :particulasFe, :observacion, :periodoZafra, :fechaIngreso)";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':hora', $hora);
            $stmt->bindParam(':templa', $templa);
            $stmt->bindParam(':color', $color);
            $stmt->bindParam(':turbidez', $turbidez);
            $stmt->bindParam(':vitaminaA', $vitaminaA);
            $stmt->bindParam(':pol', $pol);
            $stmt->bindParam(':humedad', $humedad);
            $stmt->bindParam(':cenizas', $cenizas);
            $stmt->bindParam(':particulasFe', $particulasFe);
            $stmt->bindParam(':observacion', $observacion);
            $stmt->bindParam(':periodoZafra', $periodoZafra);
            $stmt->bindParam(':fechaIngreso', $fechaIngreso);

            if ($stmt->execute()) {
                $idRegistro = $conexion->lastInsertId();

                // Construir detalles del cambio
                $detallesCambio = "Hora: $hora, Templa: $templa, Color: $color, Turbidez: $turbidez, Vitamina A: $vitaminaA, Pol: $pol, Humedad: $humedad, Cenizas: $cenizas, Partículas Fe: $particulasFe, Observación: $observacion";

                // Añadir entrada a la bitácora
                registrarBitacora(
                    $_SESSION['nombre'],
                    "Inserción de registro en Análisis de Azúcar", 
                    $idRegistro,
                    "analisisazucar", 
                    $detallesCambio
                );
                // Redirigir de vuelta a mostrar registros con filtros y mensaje de éxito
                header("Location: " . BASE_PATH . "controllers/analisisAzucar/mostrarAnalisisAzucar.php?mensaje=Registro+insertado+correctamente&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
                exit();
            } else {
                $error = "Error al crear el registro. Por favor, intente de nuevo.";
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }

    // Redirigir con mensaje de error si algo falla
    if (!empty($error)) {
        header("Location: " . BASE_PATH . "forms/registrarAnalisisAzucar.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&hora=" . urlencode($hora) . "&templa=" . urlencode($templa) . "&color=" . urlencode($color) . "&turbidez=" . urlencode($turbidez) . "&vitaminaA=" . urlencode($vitaminaA) . "&pol=" . urlencode($pol) . "&humedad=" . urlencode($humedad) . "&cenizas=" . urlencode($cenizas) . "&particulasFe=" . urlencode($particulasFe) . "&observacion=" . urlencode($observacion));
        exit();
    }
}
?>
