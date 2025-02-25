<?php
// Incluir la conexión a la base de datos
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

// Inicializar la variable de error
$error = "";

// Verificar que el formulario se haya enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recibir los datos del formulario
    $hora = $_POST['hora'];
    $observacion = $_POST['observacion'];
    $color = floatval($_POST['color']);
    $brix = floatval($_POST['brix']);
    $sac = floatval($_POST['sac']);
    $mlGastado = isset($_POST['mlGastado']) ? floatval($_POST['mlGastado']) : 0;
    $periodoZafra = $_POST['periodoZafra'];
    $fechaIngreso = $_POST['fechaIngreso'];

    // Validar que todos los campos requeridos estén presentes
    if (empty($periodoZafra) || empty($fechaIngreso) || empty($hora) || empty($brix) || empty($sac)) {
        $error = "Los valores de Brix y SAC no pueden estar vacios.";
        header("Location: " . BASE_PATH . "forms/registrarMeladura.php?id=" . urlencode($idMeladura) . "&error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
        exit();
    }

    // Validar que algunos valores no sean negativos
    if ($color < 0) {
        $error = "El valor de color no puede ser negativo.";
    } elseif ($brix <= 0) {
        $error = "El valor de Brix debe ser mayor que cero.";
    } elseif ($sac <= 0) {
        $error = "El valor de SAC debe ser mayor que cero.";
    } elseif ($mlGastado < 0) {
        $error = "El valor de ML Gastado no puede ser negativo.";
    } else {
        // Verificar que Brix y SAC son válidos
        if ($brix != 0) {
            $pureza = round(($sac * 100) / $brix, 2);
        } else {
            $pureza = 0;
        }
    }

    // Calcular Azúcar Reducido solo si mlGastado es mayor a 0
    if ($mlGastado != 0) {
        $azucRed = round(12.5 / $mlGastado, 2);
    } else {
        $azucRed = 0;
    }

    // Si hay un error, redirigir al formulario con el mensaje de error
    if (!empty($error)) {
        header("Location: " . BASE_PATH . "forms/registrarMeladura.php?error=" . urlencode($error) 
        . "&periodoZafra=" . urlencode($periodoZafra)
        . "&fechaIngreso=" . urlencode($fechaIngreso)
        . "&hora=" . urlencode($hora)
        . "&observacion=" . urlencode($observacion)
        . "&color=" . urlencode($color)
        . "&brix=" . urlencode($brix)
        . "&sac=" . urlencode($sac)
        . "&mlGastado=" . urlencode($mlGastado));
        exit();
    }

    try {
        // Comprobar si ya existe un registro con la misma combinación de hora, periodo y fecha de ingreso
        $check_sql = "SELECT COUNT(*) FROM meladura 
                      WHERE hora = :hora 
                      AND periodoZafra = :periodoZafra 
                      AND fechaIngreso = :fechaIngreso";
        $check_stmt = $conexion->prepare($check_sql);
        $check_stmt->bindParam(':hora', $hora);
        $check_stmt->bindParam(':periodoZafra', $periodoZafra);
        $check_stmt->bindParam(':fechaIngreso', $fechaIngreso);
        $check_stmt->execute();

        $count = $check_stmt->fetchColumn();

        if ($count > 0) {
            $error = "Ya existe un registro para esta hora y fecha. Por favor, elija otra hora o fecha.";
            header("Location: " . BASE_PATH . "forms/registrarMeladura.php?error=" . urlencode($error) 
            . "&periodoZafra=" . urlencode($periodoZafra) 
            . "&fechaIngreso=" . urlencode($fechaIngreso) 
            . "&hora=" . urlencode($hora) 
            . "&observacion=" . urlencode($observacion) 
            . "&color=" . urlencode($color) 
            . "&brix=" . urlencode($brix) 
            . "&sac=" . urlencode($sac) 
            . "&mlGastado=" . urlencode($mlGastado));
            exit();
        } else {
            // SQL de inserción
            $sql = "INSERT INTO meladura (hora, observacion, color, brix, sac, mlGastado, periodoZafra, fechaIngreso)
                    VALUES (:hora, :observacion, :color, :brix, :sac, :mlGastado, :periodoZafra, :fechaIngreso)";
        
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':hora', $hora);
            $stmt->bindParam(':observacion', $observacion);
            $stmt->bindParam(':color', $color);
            $stmt->bindParam(':brix', $brix);
            $stmt->bindParam(':sac', $sac);
            $stmt->bindParam(':mlGastado', $mlGastado);
            $stmt->bindParam(':periodoZafra', $periodoZafra);
            $stmt->bindParam(':fechaIngreso', $fechaIngreso);
  
            // Ejecutar la inserción
            if ($stmt->execute()) {
                $idRegistro = $conexion->lastInsertId();

                // Construir detalles del cambio
                $detallesCambio = "Hora: $hora, Observacion: $observacion, Color: $color, Brix: $brix, SAC: $sac, ML Gastado: $mlGastado";

                // Añadir entrada a la bitácora
                registrarBitacora(
                    $_SESSION['nombre'],
                    "Inserción de registro en Meladura", 
                    $idRegistro,
                    "meladura", 
                    $detallesCambio
                );
                header("Location: " . BASE_PATH . "controllers/meladura/mostrarMeladura.php?mensaje=Registro+insertado+correctamente&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
                exit();
            } else {
                $error = "Error al registrar el análisis.";
                header("Location: " . BASE_PATH . "forms/registrarMeladura.php?error=" . urlencode($error));
                exit();
            }
        }
    } catch (PDOException $e) {
        $error = "Error de base de datos: " . $e->getMessage();
        header("Location: " . BASE_PATH . "forms/registrarMeladura.php?error=" . urlencode($error));
        exit();
    }
} else {
    // Si no se recibió una solicitud POST, redirigir al formulario
    header("Location: " . BASE_PATH . "forms/registrarMeladura.php?error=" . urlencode("Solicitud no válida."));
    exit();
}
?>
