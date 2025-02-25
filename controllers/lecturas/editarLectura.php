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

// Verificar si se ha enviado el ID por la URL
if (isset($_GET['id'])) {
    $idLectura = $_GET['id'];

    // Obtener los datos actuales del registro
    try {
        $stmt = $conexion->prepare("SELECT * FROM lecturas WHERE idLectura = :idLectura");
        $stmt->bindParam(':idLectura', $idLectura, PDO::PARAM_INT);
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
    $lecturaTurbo9_4MW = $_POST['lecturaTurbo9_4MW'];
    $lecturaTurbo2000KW = $_POST['lecturaTurbo2000KW'];
    $lecturaTurbo1600KW = $_POST['lecturaTurbo1600KW'];
    $lecturaTurbo1500KW = $_POST['lecturaTurbo1500KW'];
    $lecturaTurbo800KW = $_POST['lecturaTurbo800KW'];
    $lecturaZonaUrbana = $_POST['lecturaZonaUrbana'];
    $lecturaENEE = $_POST['lecturaENEE'];
    $observacion = $_POST['observacion'] ?? '';
    $periodoZafra = $_POST['periodoZafra'] ?? $periodoZafra;
    $fechaIngreso = $_POST['fechaIngreso'] ?? $fechaIngreso;

    // Verificar si ya existe otro registro con el mismo periodoZafra y fechaIngreso
    if (empty($error)) {
        try {
            $check_sql = "SELECT COUNT(*) FROM lecturas 
                          WHERE periodoZafra = :periodoZafra
                          AND fechaIngreso = :fechaIngreso
                          AND idLectura != :idLectura";
            $check_stmt = $conexion->prepare($check_sql);
            $check_stmt->bindParam(':periodoZafra', $periodoZafra);
            $check_stmt->bindParam(':fechaIngreso', $fechaIngreso);
            $check_stmt->bindParam(':idLectura', $idLectura);
            $check_stmt->execute();
            $count = $check_stmt->fetchColumn();

            if ($count > 0) {
                $error = "Ya existe un registro con el periodo de zafra y fecha de ingreso.";
            }
        } catch (PDOException $e) {
            echo "Error al comprobar duplicados: " . $e->getMessage();
            exit();
        }
    }

    // Validar que los valores no sean negativos
    if (empty($error)) {
        if ($lecturaTurbo9_4MW < 0) {
            $error = "El valor de Lectura Turbo 9.4 MW no puede ser negativo.";
        } elseif ($lecturaTurbo2000KW < 0) {
            $error = "El valor de Lectura Turbo 2000 KW no puede ser negativo.";
        } elseif ($lecturaTurbo1600KW < 0) {
            $error = "El valor de Lectura Turbo 1600 KW no puede ser negativo.";
        } elseif ($lecturaTurbo1500KW < 0) {
            $error = "El valor de Lectura Turbo 1500 KW no puede ser negativo.";
        } elseif ($lecturaTurbo800KW < 0) {
            $error = "El valor de Lectura Turbo 800 KW no puede ser negativo.";
        } elseif ($lecturaZonaUrbana < 0) {
            $error = "El valor de Lectura Zona Urbana no puede ser negativo.";
        } elseif ($lecturaENEE < 0) {
            $error = "El valor de Lectura ENEE no puede ser negativo.";
        }
    }

    // Si no hay errores, proceder con la actualización
    if (empty($error)) {
        try {
            $sql = "UPDATE lecturas
                    SET lecturaTurbo9_4MW = :lecturaTurbo9_4MW, 
                        lecturaTurbo2000KW = :lecturaTurbo2000KW, 
                        lecturaTurbo1600KW = :lecturaTurbo1600KW,
                        lecturaTurbo1500KW = :lecturaTurbo1500KW, 
                        lecturaTurbo800KW = :lecturaTurbo800KW, 
                        lecturaZonaUrbana = :lecturaZonaUrbana, 
                        lecturaENEE = :lecturaENEE, 
                        observacion = :observacion,
                        periodoZafra = :periodoZafra, 
                        fechaIngreso = :fechaIngreso
                    WHERE idLectura = :idLectura";

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
            $stmt->bindParam(':idLectura', $idLectura);

            $stmt->execute();

            // Redirigir después de la actualización
            $redirectUrl = BASE_PATH . "controllers/lecturas/mostrarLectura.php?mensaje=Registro+actualizado+correctamente";
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
        header("Location: " . BASE_PATH . "controllers/lecturas/editarLectura.php?id=" . urlencode($idLectura) . "&error=" . urlencode($error));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Lectura</title>
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
                    <h1 class="h3 mb-2 text-gray-800">Editar Registro de Lectura</h1>

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
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="lecturaTurbo9_4MW" class="form-label">Lectura Turbo 9.4 MW:</label>
                                        <input type="number" id="lecturaTurbo9_4MW" name="lecturaTurbo9_4MW" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['lecturaTurbo9_4MW']); ?>" min="0">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="lecturaTurbo2000KW" class="form-label">Lectura Turbo 2000 KW:</label>
                                        <input type="number" id="lecturaTurbo2000KW" name="lecturaTurbo2000KW" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['lecturaTurbo2000KW']); ?>" min="0">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="lecturaTurbo1600KW" class="form-label">Lectura Turbo 1600 KW:</label>
                                        <input type="number" id="lecturaTurbo1600KW" name="lecturaTurbo1600KW" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['lecturaTurbo1600KW']); ?>" min="0">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="lecturaTurbo1500KW" class="form-label">Lectura Turbo 1500 KW:</label>
                                        <input type="number" id="lecturaTurbo1500KW" name="lecturaTurbo1500KW" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['lecturaTurbo1500KW']); ?>" min="0">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="lecturaTurbo800KW" class="form-label">Lectura Turbo 800 KW:</label>
                                        <input type="number" id="lecturaTurbo800KW" name="lecturaTurbo800KW" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['lecturaTurbo800KW']); ?>" min="0">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="lecturaZonaUrbana" class="form-label">Lectura Zona Urbana:</label>
                                        <input type="number" id="lecturaZonaUrbana" name="lecturaZonaUrbana" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['lecturaZonaUrbana']); ?>" min="0">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="lecturaENEE" class="form-label">Lectura ENEE:</label>
                                        <input type="number" id="lecturaENEE" name="lecturaENEE" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['lecturaENEE']); ?>" min="0">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="observacion" class="form-label">Observación:</label>
                                    <textarea id="observacion" name="observacion" class="form-control"><?= htmlspecialchars($registro['observacion'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-update">Actualizar</button>
                                <a href="<?= BASE_PATH ?>controllers/lecturas/mostrarLectura.php?periodoZafra=<?= urlencode($periodoZafra); ?>&fechaIngreso=<?= urlencode($fechaIngreso); ?>" class="btn btn-secondary">Regresar</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="<?= BASE_PATH; ?>public/vendor/jquery/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/js/sb-admin-2.min.js"></script>
</body>

</html>