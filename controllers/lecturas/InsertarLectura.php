<?php
include $_SERVER['DOCUMENT_ROOT'] . '/AnalisisLaboratorio/config/config.php';
include ROOT_PATH . 'config/conexion.php';

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
    $lecturaTurbo9_4MW = $_POST['lecturaTurbo9_4MW'];
    $lecturaTurbo2000KW = $_POST['lecturaTurbo2000KW'];
    $lecturaTurbo1600KW = $_POST['lecturaTurbo1600KW'];
    $lecturaTurbo1500KW = $_POST['lecturaTurbo1500KW'];
    $lecturaTurbo800KW = $_POST['lecturaTurbo800KW'];
    $lecturaZonaUrbana = $_POST['lecturaZonaUrbana'];
    $lecturaENEE = $_POST['lecturaENEE'];
    $observacion = $_POST['observacion'];
    $periodoZafra = $_POST['periodoZafra'];
    $fechaIngreso = $_POST['fechaIngreso'];


    // Validar campos numéricos negativos
    if ($lecturaTurbo9_4MW < 0 || $lecturaTurbo2000KW < 0 || $lecturaTurbo1600KW < 0 || $lecturaTurbo1500KW < 0 || $lecturaTurbo800KW < 0 || $lecturaZonaUrbana < 0 || $lecturaENEE < 0) {
        $error = "Los valores de las lecturas no pueden ser negativos.";
        header("Location: " . BASE_PATH . "forms/registrarLectura.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
        exit();
    }

    try {
        // Verificar duplicados
        $checkQuery = "SELECT COUNT(*) FROM lecturas WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
        $checkStmt = $conexion->prepare($checkQuery);
        $checkStmt->bindParam(':periodoZafra', $periodoZafra);
        $checkStmt->bindParam(':fechaIngreso', $fechaIngreso);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() > 0) {
            $error = "Ya existe un registro con el mismo periodo de zafra y fecha.";
            header("Location: " . BASE_PATH . "forms/registrarLectura.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&lecturaTurbo9_4MW=" . urlencode($lecturaTurbo9_4MW) . "&lecturaTurbo2000KW=" . urlencode($lecturaTurbo2000KW) . "&lecturaTurbo1600KW=" . urlencode($lecturaTurbo1600KW) . "&lecturaTurbo1500KW=" . urlencode($lecturaTurbo1500KW) . "&lecturaTurbo800KW=" . urlencode($lecturaTurbo800KW) . "&lecturaZonaUrbana=" . urlencode($lecturaZonaUrbana) . "&lecturaENEE=" . urlencode($lecturaENEE) . "&observacion=" . urlencode($observacion));
            exit();
        }

        // Consulta de inserción
        $sql = "INSERT INTO lecturas (lecturaTurbo9_4MW, lecturaTurbo2000KW, lecturaTurbo1600KW, lecturaTurbo1500KW, lecturaTurbo800KW, lecturaZonaUrbana, lecturaENEE, periodoZafra, fechaIngreso) 
                VALUES (:lecturaTurbo9_4MW, :lecturaTurbo2000KW, :lecturaTurbo1600KW, :lecturaTurbo1500KW, :lecturaTurbo800KW, :lecturaZonaUrbana, :lecturaENEE, :periodoZafra, :fechaIngreso)";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':lecturaTurbo9_4MW', $lecturaTurbo9_4MW);
        $stmt->bindParam(':lecturaTurbo2000KW', $lecturaTurbo2000KW);
        $stmt->bindParam(':lecturaTurbo1600KW', $lecturaTurbo1600KW);
        $stmt->bindParam(':lecturaTurbo1500KW', $lecturaTurbo1500KW);
        $stmt->bindParam(':lecturaTurbo800KW', $lecturaTurbo800KW);
        $stmt->bindParam(':lecturaZonaUrbana', $lecturaZonaUrbana);
        $stmt->bindParam(':lecturaENEE', $lecturaENEE);
        $stmt->bindParam(':observacion', $observacion);
        $stmt->bindParam(':periodoZafra', $periodoZafra);
        $stmt->bindParam(':fechaIngreso', $fechaIngreso);

        if ($stmt->execute()) {
            // Redirigir de vuelta a mostrar registros con filtros y mensaje de éxito
            header("Location: " . BASE_PATH . "controllers/lecturas/mostrarLectura.php?mensaje=Registro+insertado+correctamente&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
            exit();
        } else {
            $error = "Error al crear el registro. Por favor, intente de nuevo.";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }

    // Redirigir con mensaje de error si algo falla
    if (!empty($error)) {
        header("Location: " . BASE_PATH . "forms/registrarLectura.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&lecturaTurbo9_4MW=" . urlencode($lecturaTurbo9_4MW) . "&lecturaTurbo2000KW=" . urlencode($lecturaTurbo2000KW) . "&lecturaTurbo1600KW=" . urlencode($lecturaTurbo1600KW) . "&lecturaTurbo1500KW=" . urlencode($lecturaTurbo1500KW) . "&lecturaTurbo800KW=" . urlencode($lecturaTurbo800KW) . "&lecturaZonaUrbana=" . urlencode($lecturaZonaUrbana) . "&lecturaENEE=" . urlencode($lecturaENEE) . "&observacion=" . urlencode($observacion));
        exit();
    }
}
?>
