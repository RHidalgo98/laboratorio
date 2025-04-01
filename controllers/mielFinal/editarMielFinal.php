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
    $idMielFinal = $_GET['id'];

    // Obtener los datos actuales del registro
    try {
        $stmt = $conexion->prepare("SELECT * FROM mielfinal WHERE idMielFinal = :idMielFinal");
        $stmt->bindParam(':idMielFinal', $idMielFinal, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            echo "Registro no encontrado.";
            exit();
        }

        // Asignar valores si existen, de lo contrario, vacíos
        $periodoZafra = $registro['periodoZafra'] ?? '';
        $fechaIngreso = $registro['fechaIngreso'] ?? '';
        $observacion = htmlspecialchars($registro['observacion'] ?? '', ENT_QUOTES, 'UTF-8');
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
    $num = $_POST['num'];
    $hora = $_POST['hora'];
    $brix = $_POST['brix'];
    $pol = $_POST['pol'];
    $azucRed = $_POST['azucRed'];
    $observacion = $_POST['observacion'] ?? '';
    $periodoZafra = $_POST['periodoZafra'] ?? $periodoZafra;
    $fechaIngreso = $_POST['fechaIngreso'] ?? $fechaIngreso;

    // Verificar si ya existe otro registro con el mismo número, periodoZafra, fechaIngreso o hora
    if (empty($error)) {
        try {
            $check_sql = "SELECT COUNT(*) FROM mielfinal
                          WHERE (num = :num OR hora = :hora)
                          AND periodoZafra = :periodoZafra 
                          AND fechaIngreso = :fechaIngreso 
                          AND idMielFinal != :idMielFinal";
            $check_stmt = $conexion->prepare($check_sql);
            $check_stmt->bindParam(':num', $num);
            $check_stmt->bindParam(':hora', $hora);
            $check_stmt->bindParam(':periodoZafra', $periodoZafra);
            $check_stmt->bindParam(':fechaIngreso', $fechaIngreso);
            $check_stmt->bindParam(':idMielFinal', $idMielFinal);
            $check_stmt->execute();
            $count = $check_stmt->fetchColumn();

            if ($count > 0) {
                $error = "Ya existe un registro con el mismo número, hora, periodo de zafra y fecha.";
            }
        } catch (PDOException $e) {
            echo "Error al comprobar duplicados: " . $e->getMessage();
            exit();
        }
    }

    // Validar que todos los campos requeridos estén presentes
    if (empty($periodoZafra) || empty($fechaIngreso) || empty($hora) || empty($num) || empty($brix) || empty($pol)) {
        $error = "Los valores de Brix y Pol no pueden estar vacios.";
        header("Location: " . BASE_PATH . "controllers/mielFinal/editarMielFinal.php?id=" . urlencode($idMielFinal) . "&error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
        exit();
    }

    // Validar que Brix, Pol y Azuc Red no sean negativos
    if (empty($error)) {
        if ($brix <= 0) {
            $error = "El valor de Brix debe ser mayor que cero.";
        } elseif ($pol <= 0) {
            $error = "El valor de Pol debe ser mayor que cero.";
        } elseif ($azucRed < 0) {
            $error = "El valor de Azuc Red no puede ser negativo.";
        }
    }

    // Si no hay errores, proceder con la actualización
    if (empty($error)) {
        try {
            $sql = "UPDATE mielfinal 
                    SET num = :num,
                        hora = :hora, 
                        brix = :brix, 
                        pol = :pol,
                        azucRed = :azucRed,
                        observacion = :observacion,
                        periodoZafra = :periodoZafra, 
                        fechaIngreso = :fechaIngreso 
                    WHERE idMielFinal = :idMielFinal";

            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':num', $num);
            $stmt->bindParam(':hora', $hora);
            $stmt->bindParam(':brix', $brix);
            $stmt->bindParam(':pol', $pol);
            $stmt->bindParam(':azucRed', $azucRed);
            $stmt->bindParam(':observacion', $observacion);
            $stmt->bindParam(':periodoZafra', $periodoZafra);
            $stmt->bindParam(':fechaIngreso', $fechaIngreso);
            $stmt->bindParam(':idMielFinal', $idMielFinal);

            $stmt->execute();

            $detallesCambio = "";
            if ($registro['num'] != $num) {
                $detallesCambio .= "Num: " . ($registro['num'] ?? '') . " -> $num, ";
            }
            if ($registro['hora'] != $hora) {
                $detallesCambio .= "Hora: " . ($registro['hora'] ?? '') . " -> $hora, ";
            }
            if ($registro['brix'] != $brix) {
                $detallesCambio .= "Brix: " . ($registro['brix'] ?? '') . " -> $brix, ";
            }
            if ($registro['pol'] != $pol) {
                $detallesCambio .= "Pol: " . ($registro['pol'] ?? '') . " -> $pol, ";
            }
            if ($registro['azucRed'] != $azucRed) {
                $detallesCambio .= "Azuc Red: " . ($registro['azucRed'] ?? '') . " -> $azucRed, ";
            }
            if ($registro['observacion'] != $observacion) {
                $detallesCambio .= "Observación: " . ($registro['observacion'] ?? '') . " -> $observacion";
            }

            if (!empty($detallesCambio)) {
                registrarBitacora(
                    $_SESSION['nombre'],
                    "Edición de registro en Miel Final",
                    $idMielFinal,
                    "mielfinal",
                    rtrim($detallesCambio, ', ')
                );
            }

            // Redirigir después de la actualización
            $redirectUrl = BASE_PATH . "controllers/mielFinal/mostrarMielFinal.php?mensaje=Registro+actualizado+correctamente";
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
        header("Location: " . BASE_PATH . "controllers/mielFinal/editarMielFinal.php?id=" . urlencode($idMielFinal) . "&error=" . urlencode($error));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Miel Final</title>
    <link href="<?= BASE_PATH ?>public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>public/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>public/css/sb-admin-2.min.css" rel="stylesheet">

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
                    <h1 class="h3 mb-2 text-gray-800">Editar Registro de Miel Final</h1>

                    <!-- Mostrar mensajes de éxito o error -->
                    <?php if (isset($_GET['mensaje'])): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($_GET['mensaje']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($_GET['error']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form action="" method="POST">
                                <div class="mb-3">
                                    <label for="periodoZafra" class="form-label">Periodo Zafra:</label>
                                    <input type="text" id="periodoZafra" name="periodoZafra" class="form-control" value="<?php echo $registro['periodoZafra']; ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="fechaIngreso" class="form-label">Fecha de Ingreso:</label>
                                    <input type="date" id="fechaIngreso" name="fechaIngreso" class="form-control" value="<?php echo $registro['fechaIngreso']; ?>" readonly required>
                                </div>
                                <div class="mb-3">
                                    <label for="num" class="form-label">Num:</label>
                                    <input type="number" id="num" name="num" class="form-control" value="<?= htmlspecialchars($registro['num']); ?>" min="1" step="1" required>
                                </div>
                                <div class="mb-3">
                                    <label for="hora" class="form-label">Hora:</label>
                                    <input type="time" id="hora" name="hora" class="form-control" value="<?= htmlspecialchars($registro['hora']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="brix" class="form-label">Brix:</label>
                                    <input type="number" id="brix" name="brix" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['brix']); ?>" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label for="pol" class="form-label">Pol:</label>
                                    <input type="number" id="pol" name="pol" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['pol']); ?>" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label for="azucRed" class="form-label">Azuc Red:</label>
                                    <input type="number" id="azucRed" name="azucRed" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['azucRed']); ?>" min="0">
                                </div>
                                <div class="mb-3">
                                    <label for="observacion" class="form-label">Observación:</label>
                                    <textarea id="observacion" name="observacion" class="form-control"><?= htmlspecialchars($registro['observacion'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-update">Actualizar</button>
                                <a href="<?= BASE_PATH ?>controllers/mielFinal/mostrarMielFinal.php?periodoZafra=<?= urlencode($periodoZafra); ?>&fechaIngreso=<?= urlencode($fechaIngreso); ?>" class="btn btn-secondary">Regresar</a>
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