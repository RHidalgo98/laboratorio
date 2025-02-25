<?php
// Incluir archivos de configuración y conexión a la base de datos
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

// Obtener valores de GET
$periodoZafra = isset($_GET['periodoZafra']) ? $_GET['periodoZafra'] : $suggestedPeriodo;
$fechaIngreso = isset($_GET['fechaIngreso']) ? $_GET['fechaIngreso'] : '';
$hora = isset($_GET['hora']) ? $_GET['hora'] : '';
$pol1 = isset($_GET['pol1']) ? $_GET['pol1'] : '';
$gFt1 = isset($_GET['gFt1']) ? $_GET['gFt1'] : '';
$pol2 = isset($_GET['pol2']) ? $_GET['pol2'] : '';
$gFt2 = isset($_GET['gFt2']) ? $_GET['gFt2'] : '';
$pol3 = isset($_GET['pol3']) ? $_GET['pol3'] : '';
$gFt3 = isset($_GET['gFt3']) ? $_GET['gFt3'] : '';
$observacion = isset($_GET['observacion']) ? $_GET['observacion'] : '';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Filtros Cachaza</title>

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
                    <h1 class="h3 mb-2 text-gray-800">Registro de Filtro Cachaza</h1>

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
                            <form action="<?= BASE_PATH ?>controllers/filtrosCachaza/insertarFC.php" method="POST">
                                <div class="mb-4">
                                    <label for="periodoZafra" class="form-label">Periodo Zafra:</label>
                                    <input type="text" id="periodoZafra" name="periodoZafra" class="form-control" value="<?= htmlspecialchars($periodoZafra); ?>" readonly>
                                </div>

                                <div class="mb-4">
                                    <label for="fechaIngreso" class="form-label">Fecha de Ingreso:</label>
                                    <input type="date" id="fechaIngreso" name="fechaIngreso" class="form-control" value="<?= htmlspecialchars($fechaIngreso); ?>" readonly>
                                </div>

                                <div class="mb-4">
                                    <label for="hora" class="form-label">Hora:</label>
                                    <select id="hora" name="hora" class="form-select" required>
                                        <option value="">Seleccione la hora:</option>
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
                                        foreach ($horas as $hora) {
                                            $selected = (isset($_GET['hora']) && $_GET['hora'] == $hora) ? 'selected' : '';
                                            echo "<option value='$hora' $selected>$hora</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Campos para los filtros -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <h5>Filtro Cachaza #1</h5>
                                        <div class="mb-3">
                                            <label for="pol1" class="form-label">Pol:</label>
                                            <input type="number" id="pol1" name="pol1" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($pol1); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="gFt1" class="form-label">g/Ft²:</label>
                                            <input type="number" id="gFt1" name="gFt1" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($gFt1); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <h5>Filtro Cachaza #2</h5>
                                        <div class="mb-3">
                                            <label for="pol2" class="form-label">Pol:</label>
                                            <input type="number" id="pol2" name="pol2" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($pol2); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="gFt2" class="form-label">g/Ft²:</label>
                                            <input type="number" id="gFt2" name="gFt2" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($gFt2); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <h5>Filtro Cachaza #3</h5>
                                        <div class="mb-3">
                                            <label for="pol3" class="form-label">Pol:</label>
                                            <input type="number" id="pol3" name="pol3" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($pol3); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="gFt3" class="form-label">g/Ft²:</label>
                                            <input type="number" id="gFt3" name="gFt3" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($gFt3); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <label for="observacion" class="form-label">Observación:</label>
                                        <textarea id="observacion" name="observacion" class="form-control" maxlength="100" placeholder="Escribe una observación"><?= htmlspecialchars($observacion); ?></textarea>
                                    </div>
                                </div>

                                <div class="mb-3 mt-3">
                                    <button type="submit" class="btn btn-primary">Registrar</button>
                                    <a href="<?= BASE_PATH ?>controllers/filtrosCachaza/mostrarFC.php?periodoZafra=<?= urlencode($periodoZafra); ?>&fechaIngreso=<?= urlencode($fechaIngreso); ?>" class="btn btn-secondary">Regresar</a>
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