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
    $turno = $_POST['turno'];
    $tnCanaACHSA = $_POST['tnCanaACHSA'] ? floatval($_POST['tnCanaACHSA']) : 0;;
    $moliendaTn = $_POST['moliendaTn'] ? floatval($_POST['moliendaTn']) : 0;;
    $sacos50AzucarBlanco = $_POST['sacos50AzucarBlanco'] ? floatval($_POST['sacos50AzucarBlanco']) : 0;;
    $sacosAzucarMorena = $_POST['sacosAzucarMorena'] ? floatval($_POST['sacosAzucarMorena']) : 0;;
    $jumboAzucarBlanco = $_POST['jumboAzucarBlanco'] ? floatval($_POST['jumboAzucarBlanco']) : 0;;
    $observacion = $_POST['observacion'];
    $periodoZafra = $_POST['periodoZafra'];
    $fechaIngreso = $_POST['fechaIngreso'];
    
    // Validar campos numéricos negativos
    if ($tnCanaACHSA < 0 || $moliendaTn < 0 || $sacos50AzucarBlanco < 0 || $sacosAzucarMorena < 0 || $jumboAzucarBlanco < 0) {
        $error = "Los valores de Toneladas, Sacos y Jumbo no pueden ser negativos.";
        header("Location: " . BASE_PATH . "forms/registrarSacoAzucar.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&turno=" . urlencode($turno) . "&tnCanaACHSA=" . urlencode($tnCanaACHSA) . "&moliendaTn=" . urlencode($moliendaTn) . "&sacos50AzucarBlanco=" . urlencode($sacos50AzucarBlanco) . "&sacosAzucarMorena=" . urlencode($sacosAzucarMorena) . "&jumboAzucarBlanco=" . urlencode($jumboAzucarBlanco) . "&observacion=" . urlencode($observacion));
        exit();
    }

    try {
        // Consulta de inserción
        $sql = "INSERT INTO sacoAzucar (turno, tnCanaACHSA, moliendaTn, sacos50AzucarBlanco, sacosAzucarMorena, jumboAzucarBlanco, observacion, periodoZafra, fechaIngreso) 
                VALUES (:turno, :tnCanaACHSA, :moliendaTn, :sacos50AzucarBlanco, :sacosAzucarMorena, :jumboAzucarBlanco, :observacion, :periodoZafra, :fechaIngreso)";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':turno', $turno);
        $stmt->bindParam(':tnCanaACHSA', $tnCanaACHSA);
        $stmt->bindParam(':moliendaTn', $moliendaTn);
        $stmt->bindParam(':sacos50AzucarBlanco', $sacos50AzucarBlanco);
        $stmt->bindParam(':sacosAzucarMorena', $sacosAzucarMorena);
        $stmt->bindParam(':jumboAzucarBlanco', $jumboAzucarBlanco);
        $stmt->bindParam(':observacion', $observacion);
        $stmt->bindParam(':periodoZafra', $periodoZafra);
        $stmt->bindParam(':fechaIngreso', $fechaIngreso);

        if ($stmt->execute()) {
            $idRegistro = $conexion->lastInsertId();

            // Construir detalles del cambio
            $detallesCambio = "Turno: $turno, Tn Caña ACHSA: $tnCanaACHSA, Tn Molienda: $moliendaTn, Sacos Azúcar Blanco: $sacos50AzucarBlanco, Sacos Azúcar Morena: $sacosAzucarMorena, Jumbo Azúcar Blanco: $jumboAzucarBlanco, Observación: $observacion";

            // Añadir entrada a la bitácora
            registrarBitacora(
            $_SESSION['nombre'],
            "Inserción de registro en Saco de Azúcar", 
            $idRegistro,
            "sacoazucar", 
            $detallesCambio
            );
            // Redirigir de vuelta a mostrar registros con filtros y mensaje de éxito
            header("Location: " . BASE_PATH . "controllers/sacoAzucar/mostrarSacoAzucar.php?mensaje=Registro+insertado+correctamente&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
            exit();
        } else {
            $error = "Error al crear el registro. Por favor, intente de nuevo.";
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Código de error para duplicados
            $error = "Ya existe un registro con el mismo turno, periodo y fecha.";
        } else {
            $error = "Error: " . $e->getMessage();
        }
    }

    // Redirigir con mensaje de error si algo falla
    if (!empty($error)) {
        header("Location: " . BASE_PATH . "forms/registrarSacoAzucar.php?error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "&turno=" . urlencode($turno) . "&tnCanaACHSA=" . urlencode($tnCanaACHSA) . "&moliendaTn=" . urlencode($moliendaTn) . "&sacos50AzucarBlanco=" . urlencode($sacos50AzucarBlanco) . "&sacosAzucarMorena=" . urlencode($sacosAzucarMorena) . "&jumboAzucarBlanco=" . urlencode($jumboAzucarBlanco) . "&observacion=" . urlencode($observacion));
        exit();
    }
}
?>
