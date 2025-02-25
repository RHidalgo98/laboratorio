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
    $idFiltroCachaza = $_GET['id'];

    // Obtener los datos actuales del registro
    try {
        $stmt = $conexion->prepare("SELECT * FROM filtrocachaza WHERE idFiltroCachaza = :idFiltroCachaza");
        $stmt->bindParam(':idFiltroCachaza', $idFiltroCachaza, PDO::PARAM_INT);
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
    $hora = $_POST['hora'];
    $pol1 = $_POST['pol1'];
    $gFt1 = $_POST['gFt1'];
    $pol2 = $_POST['pol2'];
    $gFt2 = $_POST['gFt2'];
    $pol3 = $_POST['pol3'];
    $gFt3 = $_POST['gFt3'];
    $observacion = $_POST['observacion'];
    $periodoZafra = $_POST['periodoZafra'] ?? $periodoZafra;
    $fechaIngreso = $_POST['fechaIngreso'] ?? $fechaIngreso;

    // Validar que Tacho, VolFt3, Brix, y Pol no sean negativos
    if (empty($error)) {
        if ($pol1 < 0) {
            $error = "El valor de Pol1 no puede ser negativo.";
        } elseif ($gFt1 < 0) {
            $error = "El valor de gFt1 no puede ser negativo.";
        } elseif ($pol2 < 0) {
            $error = "El valor de Pol2 no puede ser negativo.";
        } elseif ($gFt2 < 0) {
            $error = "El valor de gFt2 no puede ser negativo.";
        } elseif ($pol3 < 0) {
            $error = "El valor de Pol3 no puede ser negativo.";
        } elseif ($gFt3 < 0) {
            $error = "El valor de gFt3 no puede ser negativo.";
        }
    }

    // Verificar si ya existe otro registro con la misma hora, periodoZafra y fechaIngreso
    if (empty($error)) {
        try {
            $check_sql = "SELECT COUNT(*) FROM filtrocachaza 
                          WHERE hora = :hora 
                          AND periodoZafra = :periodoZafra 
                          AND fechaIngreso = :fechaIngreso 
                          AND idFiltroCachaza != :idFiltroCachaza";
            $check_stmt = $conexion->prepare($check_sql);
            $check_stmt->bindParam(':hora', $hora);
            $check_stmt->bindParam(':periodoZafra', $periodoZafra);
            $check_stmt->bindParam(':fechaIngreso', $fechaIngreso);
            $check_stmt->bindParam(':idFiltroCachaza', $idFiltroCachaza);
            $check_stmt->execute();
            $count = $check_stmt->fetchColumn();

            if ($count > 0) {
                $error = "Ya existe un registro con la misma hora, periodo de zafra y fecha.";
            }
        } catch (PDOException $e) {
            echo "Error al comprobar duplicados: " . $e->getMessage();
            exit();
        }
    }

    // Si no hay errores, proceder con la actualización
    if (empty($error)) {
        try {
            $sql = "UPDATE filtrocachaza 
                    SET hora = :hora, 
                        pol1 = :pol1, 
                        gFt1 = :gFt1, 
                        pol2 = :pol2, 
                        gFt2 = :gFt2, 
                        pol3 = :pol3, 
                        gFt3 = :gFt3, 
                        observacion = :observacion,
                        periodoZafra = :periodoZafra, 
                        fechaIngreso = :fechaIngreso 
                    WHERE idFiltroCachaza = :idFiltroCachaza";

            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':hora', $hora);
            $stmt->bindParam(':pol1', $pol1);
            $stmt->bindParam(':gFt1', $gFt1);
            $stmt->bindParam(':pol2', $pol2);
            $stmt->bindParam(':gFt2', $gFt2);
            $stmt->bindParam(':pol3', $pol3);
            $stmt->bindParam(':gFt3', $gFt3);
            $stmt->bindParam(':observacion', $observacion);
            $stmt->bindParam(':periodoZafra', $periodoZafra);
            $stmt->bindParam(':fechaIngreso', $fechaIngreso);
            $stmt->bindParam(':idFiltroCachaza', $idFiltroCachaza);

            $stmt->execute();

            $detallesCambio .= "Pol1: " . ($registro['pol1'] ?? '') . " → $pol1, ";
            $detallesCambio .= "gFt1: " . ($registro['gFt1'] ?? '') . " → $gFt1, ";
            $detallesCambio .= "Pol2: " . ($registro['pol2'] ?? '') . " → $pol2, ";
            $detallesCambio .= "gFt2: " . ($registro['gFt2'] ?? '') . " → $gFt2, ";
            $detallesCambio .= "Pol3: " . ($registro['pol3'] ?? '') . " → $pol3, ";
            $detallesCambio .= "gFt3: " . ($registro['gFt3'] ?? '') . " → $gFt3, ";
            $detallesCambio .= "Observación: " . ($registro['observacion'] ?? '') . " → $observacion";

            registrarBitacora(
                $_SESSION['nombre'],
                "Edición de registro en Filtro Cachaza",
                $idFiltroCachaza,
                "filtrocachaza",
                $detallesCambio
            );

            // Redirigir después de la actualización
            $redirectUrl = BASE_PATH . "controllers/filtrosCachaza/mostrarFC.php?mensaje=Registro+actualizado+correctamente";
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
        header("Location: " . BASE_PATH . "controllers/filtrosCachaza/editarFC.php?id=" . urlencode($idFiltroCachaza) . "&error=" . urlencode($error));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Filtro Cachaza</title>
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
                    <h1 class="h3 mb-2 text-gray-800">Editar Filtro Cachaza</h1>

                    <!-- Mostrar mensajes de éxito o error -->
                    <?php if (isset($_GET['mensaje'])): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($_GET['mensaje']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($_GET['error'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($_GET['error']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form action="" method="POST">
                                <div class="row">
                                    <div class="mb-4">
                                        <label for="periodoZafra" class="form-label">Periodo Zafra:</label>
                                        <input type="text" id="periodoZafra" name="periodoZafra" class="form-control" value="<?= htmlspecialchars($periodoZafra) ?>" readonly>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="fechaIngreso" class="form-label">Fecha de Ingreso:</label>
                                    <input type="date" id="fechaIngreso" name="fechaIngreso" class="form-control" value="<?= htmlspecialchars($fechaIngreso) ?>" readonly>
                                </div>

                                <div class="mb-4">
                                    <label for="hora" class="form-label">Hora:</label>
                                    <select id="hora" name="hora" class="form-select" required>
                                        <?php
                                        $horas = [
                                            '06:00',
                                            '07:00',
                                            '08:00',
                                            '09:00',
                                            '10:00',
                                            '11:00',
                                            '12:00',
                                            '13:00',
                                            '14:00',
                                            '15:00',
                                            '16:00',
                                            '17:00',
                                            '18:00',
                                            '19:00',
                                            '20:00',
                                            '21:00',
                                            '22:00',
                                            '23:00',
                                            '00:00',
                                            '01:00',
                                            '02:00',
                                            '03:00',
                                            '04:00',
                                            '05:00'
                                        ];
                                        foreach ($horas as $h) {
                                            $selected = ($registro['hora'] == $h . ':00') ? 'selected' : '';
                                            echo "<option value='$h' $selected>$h</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <h5>Filtro Cachaza #1</h5>
                                        <div class="mb-3">
                                            <label for="pol1" class="form-label">Pol:</label>
                                            <input type="number" id="pol1" name="pol1" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['pol1'] ?? ''); ?>" min="0">
                                        </div>
                                        <div class="mb-3">
                                            <label for="gFt1" class="form-label">g/Ft²:</label>
                                            <input type="number" id="gFt1" name="gFt1" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['gFt1'] ?? ''); ?>" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <h5>Filtro Cachaza #2</h5>
                                        <div class="mb-3">
                                            <label for="pol2" class="form-label">Pol:</label>
                                            <input type="number" id="pol2" name="pol2" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['pol2'] ?? ''); ?>" min="0">
                                        </div>
                                        <div class="mb-3">
                                            <label for="gFt2" class="form-label">g/Ft²:</label>
                                            <input type="number" id="gFt2" name="gFt2" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['gFt2'] ?? ''); ?>" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <h5>Filtro Cachaza #3</h5>
                                        <div class="mb-3">
                                            <label for="pol3" class="form-label">Pol:</label>
                                            <input type="number" id="pol3" name="pol3" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['pol3'] ?? ''); ?>" min="0">
                                        </div>
                                        <div class="mb-3">
                                            <label for="gFt3" class="form-label">g/Ft²:</label>
                                            <input type="number" id="gFt3" name="gFt3" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['gFt3'] ?? ''); ?>" min="0">
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <label for="observacion" class="form-label">Observación:</label>
                                        <textarea id="observacion" name="observacion" class="form-control"><?= htmlspecialchars($registro['observacion'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="mb-3 mt-3">
                                        <button type="submit" class="btn btn-update">Actualizar</button>
                                        <a href="<?= BASE_PATH ?>controllers/filtrosCachaza/mostrarFC.php?periodoZafra=<?= urlencode($periodoZafra ?? '') ?>&fechaIngreso=<?= urlencode($fechaIngreso ?? '') ?>" class="btn btn-secondary">Regresar</a>
                                    </div>
                                </div>
                            </form>
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