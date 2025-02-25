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
    $enfriamiento = floatval($_POST['enfriamiento']);
    $retorno = floatval($_POST['retorno']);
    $desechos = floatval($_POST['desechos']);
    $observacion = $_POST['observacion'];
    $periodoZafra = $_POST['periodoZafra'];
    $fechaIngreso = $_POST['fechaIngreso'];

    // Validar que todos los campos requeridos estén presentes
    if (empty($periodoZafra) || empty($fechaIngreso) || empty($hora)) {
        $error = "Todos los campos son obligatorios.";
    }
    
    // Validar campos numéricos negativos o cero
    if (empty($error)) {
        if ($enfriamiento <= 0) {
            $error = "El valor de Enfriamiento debe ser mayor que cero.";
        } elseif ($retorno <= 0) {
            $error = "El valor de Retorno debe ser mayor que cero.";
        } elseif ($desechos < 0) {
            $error = "El valor de Desechos no puede ser negativo.";
        }
    }

    // Verificar si hay errores antes de proceder con la inserción
    if (empty($error)) {
        try {
            // Verificar duplicados
            $checkQuery = "SELECT COUNT(*) FROM efluentes WHERE hora = :hora AND periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
            $checkStmt = $conexion->prepare($checkQuery);
            $checkStmt->bindParam(':hora', $hora);
            $checkStmt->bindParam(':periodoZafra', $periodoZafra);
            $checkStmt->bindParam(':fechaIngreso', $fechaIngreso);
            $checkStmt->execute();

            if ($checkStmt->fetchColumn() > 0) {
                $error = "Ya existe un registro con la misma hora, periodo de zafra y fecha.";
                header("Location: " . BASE_PATH . "forms/registrarEfluentes.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&hora=" . urlencode($hora) . "&enfriamiento=" . urlencode($enfriamiento) . "&retorno=" . urlencode($retorno) . "&desechos=" . urlencode($desechos) . "&observacion=" . urlencode($observacion));
                exit();
            }

            // Consulta de inserción
            $sql = "INSERT INTO efluentes (hora, enfriamiento, retorno, desechos, observacion, periodoZafra, fechaIngreso) 
                    VALUES (:hora, :enfriamiento, :retorno, :desechos, :observacion, :periodoZafra, :fechaIngreso)";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':hora', $hora);
            $stmt->bindParam(':enfriamiento', $enfriamiento);
            $stmt->bindParam(':retorno', $retorno);
            $stmt->bindParam(':desechos', $desechos);
            $stmt->bindParam(':observacion', $observacion);
            $stmt->bindParam(':periodoZafra', $periodoZafra);
            $stmt->bindParam(':fechaIngreso', $fechaIngreso);

            if ($stmt->execute()) {
                $idRegistro = $conexion->lastInsertId();

                // Construir detalles del cambio
                $detallesCambio = "Hora: $hora, Enfriamiento: $enfriamiento, Retorno: $retorno, Desechos: $desechos, Observación: $observacion";

                // Añadir entrada a la bitácora
                registrarBitacora(
                    $_SESSION['nombre'],
                    "Inserción de registro en Efluentes", 
                    $idRegistro,
                    "efluentes", 
                    $detallesCambio
                );
                // Redirigir de vuelta a mostrar registros con filtros y mensaje de éxito
                header("Location: " . BASE_PATH . "controllers/efluentes/mostrarEfluentes.php?mensaje=Registro+insertado+correctamente&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
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
        header("Location: " . BASE_PATH . "forms/registrarEfluentes.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&hora=" . urlencode($hora) . "&enfriamiento=" . urlencode($enfriamiento) . "&retorno=" . urlencode($retorno) . "&desechos=" . urlencode($desechos) . "&observacion=" . urlencode($observacion));
        exit();
    }
}
?>
