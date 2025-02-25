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

// Verificar si se ha enviado el ID por la URL
if (isset($_GET['id'])) {
    $idCausa = $_GET['id'];

    // Obtener los datos actuales del registro
    try {
        $stmt = $conexion->prepare("SELECT * FROM causas WHERE idCausa = :idCausa");
        $stmt->bindParam(':idCausa', $idCausa, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            echo "Registro no encontrado.";
            exit();
        }

        // Asignar valores si existen, de lo contrario, vacíos
        $periodoZafra = $registro['periodoZafra'] ?? '';
        $fechaIngreso = $registro['fechaIngreso'] ?? '';
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
} else {
    echo "ID no recibido.";
    exit();
}

// Actualizar los datos si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibir y asignar los valores enviados desde el formulario
    $turno = $_POST['turno'];
    $paro = $_POST['paro'];
    $arranque = ($_POST['arranque'] === '' ? null : $_POST['arranque']);
    $motivo = htmlspecialchars(trim($_POST['motivo']));
    $periodoZafra = $_POST['periodoZafra'] ?? $periodoZafra;
    $fechaIngreso = $_POST['fechaIngreso'] ?? $fechaIngreso;

    // Convertir las horas a minutos para facilitar la comparación
    function convertirHoraAMinutos($hora)
    {
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
    }

    // Validar que el total de horas del turno no sobrepase las 24 horas
    if (empty($error) && !is_null($arranque) && $arranque !== '') {
        try {
            $turnoQuery = "SELECT SUM(TIMESTAMPDIFF(SECOND, paro, arranque)) AS totalSegundosTurno 
                           FROM causas 
                           WHERE turno = :turno 
                           AND periodoZafra = :periodoZafra 
                           AND fechaIngreso = :fechaIngreso 
                           AND idCausa != :idCausa";
            $turnoStmt = $conexion->prepare($turnoQuery);
            $turnoStmt->bindParam(':turno', $turno);
            $turnoStmt->bindParam(':periodoZafra', $periodoZafra);
            $turnoStmt->bindParam(':fechaIngreso', $fechaIngreso);
            $turnoStmt->bindParam(':idCausa', $idCausa);
            $turnoStmt->execute();
            $totalSegundosTurno = $turnoStmt->fetchColumn();

            $totalSegundosNuevos = strtotime($arranque) - strtotime($paro);
            if ($totalSegundosNuevos < 0) {
                $totalSegundosNuevos += 86400; // Ajustar para tiempos que cruzan la medianoche
            }

            if ($totalSegundosTurno + $totalSegundosNuevos > 86400) {
                $error = "El total de horas del turno no puede sobrepasar las 24 horas.";
            }
        } catch (PDOException $e) {
            echo "Error al comprobar las horas del turno: " . $e->getMessage();
            exit();
        }
    }

    // Verificar si ya existe otro registro con el mismo turno, periodoZafra, y fechaIngreso
    if (empty($error)) {
        try {
            $check_sql = "SELECT COUNT(*) FROM causas 
                          WHERE turno = :turno 
                          AND paro = :paro 
                          AND periodoZafra = :periodoZafra 
                          AND fechaIngreso = :fechaIngreso 
                          AND idCausa != :idCausa";
            $check_stmt = $conexion->prepare($check_sql);
            $check_stmt->bindParam(':turno', $turno);
            $check_stmt->bindParam(':paro', $paro);
            $check_stmt->bindParam(':periodoZafra', $periodoZafra);
            $check_stmt->bindParam(':fechaIngreso', $fechaIngreso);
            $check_stmt->bindParam(':idCausa', $idCausa);
            $check_stmt->execute();
            $count = $check_stmt->fetchColumn();

            if ($count > 0) {
                $error = "Ya existe un registro con el mismo turno, hora de paro, periodo de zafra y fecha.";
            }
        } catch (PDOException $e) {
            echo "Error al comprobar duplicados: " . $e->getMessage();
            exit();
        }
    }

    // Si no hay errores, proceder con la actualización
    if (empty($error)) {
        try {
            $sql = "UPDATE causas 
                    SET turno = :turno, 
                        paro = :paro, 
                        arranque = :arranque, 
                        motivo = :motivo, 
                        periodoZafra = :periodoZafra, 
                        fechaIngreso = :fechaIngreso 
                    WHERE idCausa = :idCausa";

            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':turno', $turno);
            $stmt->bindParam(':paro', $paro);
            $stmt->bindParam(':arranque', $arranque, is_null($arranque) ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindParam(':motivo', $motivo);
            $stmt->bindParam(':periodoZafra', $periodoZafra);
            $stmt->bindParam(':fechaIngreso', $fechaIngreso);
            $stmt->bindParam(':idCausa', $idCausa);

            $stmt->execute();

            $detallesCambio = "Turno: " . ($registro['turno'] ?? '') . " → $turno, ";
            $detallesCambio .= "Paro: " . ($registro['paro'] ?? '') . " → $paro, ";
            $detallesCambio .= "Arranque: " . ($registro['arranque'] ?? '') . " → $arranque, ";
            $detallesCambio .= "Motivo: " . ($registro['motivo'] ?? '') . " → $motivo";
            
            registrarBitacora(
                $_SESSION['nombre'],
                "Edición de registro en Causas",
                $idCausa,
                "causas",
                $detallesCambio
            );

            // Redirigir después de la actualización
            $redirectUrl = BASE_PATH . "controllers/causas/mostrarCausas.php?mensaje=Registro+actualizado+correctamente";
            if (!empty($periodoZafra)) {
                $redirectUrl .= "&periodoZafra=" . urlencode($periodoZafra);
            }
            if (!empty($fechaIngreso)) {
                $redirectUrl .= "&fechaIngreso=" . urlencode($fechaIngreso);
            }
            header("Location: $redirectUrl");
            exit();
        } catch (PDOException $e) {
            echo "Error al actualizar el registro: " . $e->getMessage();
        }
    } else {
        // Redirigir en caso de error
        header("Location: " . BASE_PATH . "controllers/causas/editarCausas.php?id=" . urlencode($idCausa) . "&error=" . urlencode($error));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Causa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_PATH; ?>public/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .btn-update {
            background-color: #4e73df;
            color: white;
        }

        .btn-update:hover {
            background-color: #224abe;
        }
    </style>
</head>


<body id="page-top">
    <div id="wrapper">
        <?php include ROOT_PATH . 'includes/sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include ROOT_PATH . 'includes/topbar.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Editar Registro de Causa</h1>

                    <!-- Mostrar mensajes de éxito o error -->
                    <?php if (isset($_GET['mensaje'])): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($_GET['mensaje'] ?? ''); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($_GET['error'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($_GET['error'] ?? ''); ?>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form action="" method="POST">
                                <div class="mb-3">
                                    <label for="periodoZafra" class="form-label">Periodo Zafra:</label>
                                    <input type="text" id="periodoZafra" name="periodoZafra" class="form-control" value="<?= htmlspecialchars($periodoZafra); ?>" readonly required>
                                </div>
                                <div class="mb-3">
                                    <label for="fechaIngreso" class="form-label">Fecha de Ingreso:</label>
                                    <input type="date" id="fechaIngreso" name="fechaIngreso" class="form-control" value="<?= htmlspecialchars($fechaIngreso); ?>" readonly required>
                                </div>
                                <div class="mb-3">
                                    <label for="turno" class="form-label">Turno:</label>
                                    <input type="text" id="turno" name="turno" class="form-control" value="<?= htmlspecialchars($registro['turno'] ?? ''); ?>" readonly>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="paro" class="form-label">Hora de Paro:</label>
                                        <input type="time" id="paro" name="paro" class="form-control" value="<?= htmlspecialchars($registro['paro'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="arranque" class="form-label">Hora de Arranque:</label>
                                        <input type="time" id="arranque" name="arranque" class="form-control" value="<?= htmlspecialchars($registro['arranque'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label for="motivo" class="form-label">Causa:</label>
                                    <textarea id="motivo" name="motivo" class="form-control" rows="3"><?= htmlspecialchars($registro['motivo'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-update mt-3">Actualizar</button>
                                <a href="<?= BASE_PATH ?>controllers/causas/mostrarCausas.php?periodoZafra=<?= urlencode($periodoZafra) ?>&fechaIngreso=<?= urlencode($fechaIngreso) ?>" class="btn btn-secondary mt-3">Regresar</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/vendor/jquery/jquery.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/js/sb-admin-2.min.js"></script>
</body>

</html>