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

$error = "";

// Verificar si se ha enviado el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capturar y sanitizar los datos del formulario
    $periodoZafra = isset($_POST['periodoZafra']) ? trim($_POST['periodoZafra']) : '';
    $fechaIngreso = isset($_POST['fechaIngreso']) ? trim($_POST['fechaIngreso']) : '';
    $turno = isset($_POST['turno']) ? trim($_POST['turno']) : '';
    $paro = isset($_POST['paro']) ? trim($_POST['paro']) : '';
    $arranque = isset($_POST['arranque']) && !empty($_POST['arranque']) ? $_POST['arranque'] : null;
    $motivo = isset($_POST['motivo']) ? htmlspecialchars(trim($_POST['motivo'])) : '';
    
    // Convertir las horas a minutos para facilitar la comparación
    function convertirHoraAMinutos($hora)
    {
        if (empty($hora)) {
            return 0; // O lanzar una excepción si es necesario
        }
        list($horas, $minutos) = explode(':', $hora);
        return $horas * 60 + $minutos;
    }
    
    // Validar si la hora de paro es mayor o igual que la hora de arranque
    if (!is_null($arranque) && $arranque !== '') {
        $paroMinutos = convertirHoraAMinutos($paro);
        $arranqueMinutos = convertirHoraAMinutos($arranque);
        
        // Si la hora de arranque es menor que la hora de paro, considerar la hora de arranque como 24:00
        if ($arranqueMinutos < $paroMinutos) {
            $arranqueMinutos += 24 * 60;
        }
        
        if ($paroMinutos >= $arranqueMinutos) {
            $error = "La hora de paro no puede ser igual o mayor que la hora de arranque.";
        }
        
        // Validar que el total de horas no sobrepase las 24 horas
        $totalHoras = ($arranqueMinutos - $paroMinutos) / 60;
        if ($totalHoras > 24) {
            $error = "El total de horas no puede sobrepasar las 24 horas.";
        }

        // Nueva validación para que las horas de paro y arranque no sean iguales
        if ($paro === $arranque) {
            $error = "La hora de paro y la hora de arranque no pueden ser iguales.";
        }
    }
    
    // Validar que el total de horas del turno no sobrepase las 24 horas
    if (empty($error) && !is_null($arranque) && $arranque !== '') {
        try {
            $turnoQuery = "SELECT SUM(TIMESTAMPDIFF(SECOND, paro, arranque)) AS totalSegundosTurno 
                           FROM causas 
                           WHERE turno = :turno 
                           AND periodoZafra = :periodoZafra 
                           AND fechaIngreso = :fechaIngreso";
            $turnoStmt = $conexion->prepare($turnoQuery);
            $turnoStmt->bindParam(':turno', $turno);
            $turnoStmt->bindParam(':periodoZafra', $periodoZafra);
            $turnoStmt->bindParam(':fechaIngreso', $fechaIngreso);
            $turnoStmt->execute();
            $totalSegundosTurno = $turnoStmt->fetchColumn();

            $totalSegundosNuevos = strtotime($arranque) - strtotime($paro);
            if ($totalSegundosNuevos < 0) {
                $totalSegundosNuevos += 86400; // Ajustar para tiempos que cruzan la medianoche
            }

            if ($totalSegundosTurno + $totalSegundosNuevos > 86400) {
                $horasRestantes = (86400 - $totalSegundosTurno - $totalSegundosNuevos) / 3600;
                $horas = floor($horasRestantes);
                $minutos = round(($horasRestantes - $horas) * 60);
                $horasExcedidas = abs($horasRestantes);
                $horasExcedidasHoras = floor($horasExcedidas);
                $horasExcedidasMinutos = round(($horasExcedidas - $horasExcedidasHoras) * 60);
                $error = "El total de horas del turno no puede sobrepasar las 24 horas. Te estás pasando por " . $horasExcedidasHoras . " hora(s) y " . $horasExcedidasMinutos . " minuto(s).";
            }
        } catch (PDOException $e) {
            echo "Error al comprobar las horas del turno: " . $e->getMessage();
            exit();
        }
    }

    // Validar campos obligatorios
    if (empty($periodoZafra) || empty($fechaIngreso) || empty($turno) || empty($paro)) {
        $error = "Todos los campos son obligatorios.";
        header("Location: " . BASE_PATH . "forms/registrarCausas.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&turno=" . urlencode($turno));
        exit();
    }
    
    try {
        // Verificar duplicados
        $checkQuery = "SELECT COUNT(*) FROM causas WHERE turno = :turno AND paro = :paro AND periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
        $checkStmt = $conexion->prepare($checkQuery);
        $checkStmt->bindParam(':turno', $turno);
        $checkStmt->bindParam(':paro', $paro);
        $checkStmt->bindParam(':periodoZafra', $periodoZafra);
        $checkStmt->bindParam(':fechaIngreso', $fechaIngreso);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() > 0) {
            $error = "Ya existe un registro con el mismo turno, hora de paro, periodo de zafra y fecha.";
            header("Location: " . BASE_PATH . "forms/registrarCausas.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&turno=" . urlencode($turno) . "&paro=" . urlencode($paro) . "&arranque=" . urlencode($arranque) . "&motivo=" . urlencode($motivo));
            exit();
        }

        // Consulta de inserción
        $sql = "INSERT INTO causas (periodoZafra, fechaIngreso, turno, paro, arranque, motivo) 
                VALUES (:periodoZafra, :fechaIngreso, :turno, :paro, :arranque, :motivo)";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':periodoZafra', $periodoZafra);
        $stmt->bindParam(':fechaIngreso', $fechaIngreso);
        $stmt->bindParam(':turno', $turno);
        $stmt->bindParam(':paro', $paro);
        $stmt->bindParam(':arranque', $arranque);
        $stmt->bindParam(':motivo', $motivo);

        if ($stmt->execute()) {
            $idRegistro = $conexion->lastInsertId();

            // Construir detalles del cambio
            $detallesCambio = "Turno: $turno, Paro: $paro, Arranque: $arranque, Motivo: $motivo";

            // Añadir entrada a la bitácora
            registrarBitacora(
            $_SESSION['nombre'],
            "Inserción de registro en Causas", 
            $idRegistro,
            "causas", 
            $detallesCambio
            );
            // Redirigir directamente a la página con los registros filtrados por el periodo de zafra y la fecha de ingreso actuales
            $redirectUrl = BASE_PATH . "controllers/causas/mostrarCausas.php?mensaje=Registro+insertado+correctamente";
            $redirectUrl .= "&periodoZafra=" . urlencode($periodoZafra);
            $redirectUrl .= "&fechaIngreso=" . urlencode($fechaIngreso);
            header("Location: $redirectUrl");
            exit();
        } else {
            $error = "Error al intentar guardar el registro.";
        }
    } catch (PDOException $e) {
        $error = "Error al guardar los datos: " . $e->getMessage();
    }

    // Redirigir con mensaje de error en caso de fallo
    if (!empty($error)) {
        header("Location: " . BASE_PATH . "forms/registrarCausas.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&turno=" . urlencode($turno) . "&paro=" . urlencode($paro) . "&arranque=" . urlencode($arranque) . "&motivo=" . urlencode($motivo));
        exit();
    }
}
?>
