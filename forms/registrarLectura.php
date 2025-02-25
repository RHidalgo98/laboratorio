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

// Obtener la fecha actual y calcular el periodo de zafra sugerido
$currentYear = date('Y');
$currentMonth = date('m');

// Determinar el periodo sugerido según la temporada de zafra
if ($currentMonth >= 11 || $currentMonth <= 4) { // Noviembre a Abril
    $suggestedPeriodo = $currentMonth >= 11 ? $currentYear . '-' . ($currentYear + 1) : ($currentYear - 1) . '-' . $currentYear;
} else {
    $suggestedPeriodo = ''; // Fuera de temporada de zafra
}

// Obtener los datos previamente ingresados o asignar valores predeterminados
$periodoZafra = isset($_GET['periodoZafra']) ? $_GET['periodoZafra'] : $suggestedPeriodo;
$fechaIngreso = isset($_GET['fechaIngreso']) ? $_GET['fechaIngreso'] : '';
$lecturaTurbo9_4MW = isset($_GET['lecturaTurbo9_4MW']) ? $_GET['lecturaTurbo9_4MW'] : '';
$lecturaTurbo2000KW = isset($_GET['lecturaTurbo2000KW']) ? $_GET['lecturaTurbo2000KW'] : '';
$lecturaTurbo1600KW = isset($_GET['lecturaTurbo1600KW']) ? $_GET['lecturaTurbo1600KW'] : '';
$lecturaTurbo1500KW = isset($_GET['lecturaTurbo1500KW']) ? $_GET['lecturaTurbo1500KW'] : '';
$lecturaTurbo800KW = isset($_GET['lecturaTurbo800KW']) ? $_GET['lecturaTurbo800KW'] : '';
$lecturaZonaUrbana = isset($_GET['lecturaZonaUrbana']) ? $_GET['lecturaZonaUrbana'] : '';
$lecturaENEE = isset($_GET['lecturaENEE']) ? $_GET['lecturaENEE'] : '';
$observacion = isset($_GET['observacion']) ? $_GET['observacion'] : '';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Lectura</title>

    <!-- Bootstrap and SB Admin 2 Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@1.0.0/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>public/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .btn-submit {
            background-color: #4e73df;
            color: white;
        }

        .btn-submit:hover {
            background-color: #224abe;
        }
    </style>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <?php include ROOT_PATH . 'includes/sidebar.php'; ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">

                <?php include ROOT_PATH . 'includes/topbar.php'; ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Registro de Lectura</h1>

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

                    <!-- Formulario -->
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form action="<?= BASE_PATH ?>controllers/lecturas/insertarLectura.php" method="POST">
                                <div class="mb-3">
                                    <label for="periodoZafra" class="form-label">Periodo Zafra:</label>
                                    <input type="text" id="periodoZafra" name="periodoZafra" class="form-control" value="<?= htmlspecialchars($periodoZafra); ?>" readonly required>
                                </div>

                                <div class="mb-3">
                                    <label for="fechaIngreso" class="form-label">Fecha de Ingreso:</label>
                                    <input type="date" id="fechaIngreso" name="fechaIngreso" class="form-control" value="<?= htmlspecialchars($fechaIngreso); ?>" readonly required>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="lecturaTurbo9_4MW" class="form-label">Lectura Turbo 9.4 MW:</label>
                                        <input type="number" id="lecturaTurbo9_4MW" name="lecturaTurbo9_4MW" class="form-control" step="0.01" min="0" placeholder="Ejemplo: 123.45" value="<?= htmlspecialchars($lecturaTurbo9_4MW); ?>">
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="lecturaTurbo2000KW" class="form-label">Lectura Turbo 2000 KW:</label>
                                        <input type="number" id="lecturaTurbo2000KW" name="lecturaTurbo2000KW" class="form-control" step="0.01" min="0" placeholder="Ejemplo: 123.45" value="<?= htmlspecialchars($lecturaTurbo2000KW); ?>">
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="lecturaTurbo1600KW" class="form-label">Lectura Turbo 1600 KW:</label>
                                        <input type="number" id="lecturaTurbo1600KW" name="lecturaTurbo1600KW" class="form-control" step="0.01" min="0" placeholder="Ejemplo: 123.45" value="<?= htmlspecialchars($lecturaTurbo1600KW); ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="lecturaTurbo1500KW" class="form-label">Lectura Turbo 1500 KW:</label>
                                        <input type="number" id="lecturaTurbo1500KW" name="lecturaTurbo1500KW" class="form-control" step="0.01" min="0" placeholder="Ejemplo: 123.45" value="<?= htmlspecialchars($lecturaTurbo1500KW); ?>">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="lecturaTurbo800KW" class="form-label">Lectura Turbo 800 KW:</label>
                                        <input type="number" id="lecturaTurbo800KW" name="lecturaTurbo800KW" class="form-control" step="0.01" min="0" placeholder="Ejemplo: 123.45" value="<?= htmlspecialchars($lecturaTurbo800KW); ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="lecturaZonaUrbana" class="form-label">Lectura Zona Urbana:</label>
                                        <input type="number" id="lecturaZonaUrbana" name="lecturaZonaUrbana" class="form-control" step="0.01" min="0" placeholder="Ejemplo: 123.45" value="<?= htmlspecialchars($lecturaZonaUrbana); ?>">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="lecturaENEE" class="form-label">Lectura ENEE:</label>
                                        <input type="number" id="lecturaENEE" name="lecturaENEE" class="form-control" step="0.01" min="0" placeholder="Ejemplo: 123.45" value="<?= htmlspecialchars($lecturaENEE); ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="observacion" class="form-label">Observación:</label>
                                    <textarea id="observacion" name="observacion" class="form-control" maxlength="100" placeholder="Escribe una observación"><?= htmlspecialchars($observacion); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <button type="submit" class="btn btn-submit">Registrar</button>
                                    <a href="<?= BASE_PATH ?>controllers/lecturas/mostrarLectura.php?periodoZafra=<?= urlencode($periodoZafra); ?>&fechaIngreso=<?= urlencode($fechaIngreso); ?>" class="btn btn-secondary">Regresar</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/vendor/jquery/jquery.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/js/sb-admin-2.min.js"></script>
</body>

</html>
