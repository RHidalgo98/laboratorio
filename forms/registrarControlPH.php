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
$primario = isset($_GET['primario']) ? $_GET['primario'] : '';
$mezclado = isset($_GET['mezclado']) ? $_GET['mezclado'] : '';
$residual = isset($_GET['residual']) ? $_GET['residual'] : '';
$sulfitado = isset($_GET['sulfitado']) ? $_GET['sulfitado'] : '';
$filtrado = isset($_GET['filtrado']) ? $_GET['filtrado'] : '';
$alcalizado = isset($_GET['alcalizado']) ? $_GET['alcalizado'] : '';
$clarificado = isset($_GET['clarificado']) ? $_GET['clarificado'] : '';
$meladura = isset($_GET['meladura']) ? $_GET['meladura'] : '';
$observacion = isset($_GET['observacion']) ? $_GET['observacion'] : '';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Control de PH</title>

    <!-- Bootstrap and SB Admin 2 Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@1.0.0/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="<?php echo BASE_PATH; ?>public/css/sb-admin-2.min.css" rel="stylesheet">

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
                    <h1 class="h3 mb-2 text-gray-800">Registro de Control de PH</h1>

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
                            <form action="<?= BASE_PATH ?>controllers/controlPH/insertarControlPH.php" method="POST">
                                <div class="mb-3">
                                    <label for="periodoZafra" class="form-label">Periodo Zafra:</label>
                                    <input type="text" id="periodoZafra" name="periodoZafra" class="form-control" value="<?= htmlspecialchars($periodoZafra); ?>" readonly required>
                                </div>

                                <div class="mb-3">
                                    <label for="fechaIngreso" class="form-label">Fecha de Ingreso:</label>
                                    <input type="date" id="fechaIngreso" name="fechaIngreso" class="form-control" value="<?= htmlspecialchars($fechaIngreso); ?>" readonly required>
                                </div>

                                <div class="mb-3">
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

                                <!-- Campos de Control de pH -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="primario" class="form-label">Primario:</label>
                                        <input type="number" id="primario" name="primario" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($primario); ?>" placeholder="Ejemplo: 5.2">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="mezclado" class="form-label">Mezclado:</label>
                                        <input type="number" id="mezclado" name="mezclado" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($mezclado); ?>" placeholder="Ejemplo: 5.3">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="residual" class="form-label">Residual:</label>
                                        <input type="number" id="residual" name="residual" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($residual); ?>" placeholder="Ejemplo: 6.0">
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <label for="sulfitado" class="form-label">Sulfitado:</label>
                                        <input type="number" id="sulfitado" name="sulfitado" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($sulfitado); ?>" placeholder="Ejemplo: 4.9">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="filtrado" class="form-label">Filtrado:</label>
                                        <input type="number" id="filtrado" name="filtrado" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($filtrado); ?>" placeholder="Ejemplo: 8.6">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="alcalizado" class="form-label">Alcalizado:</label>
                                        <input type="number" id="alcalizado" name="alcalizado" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($alcalizado); ?>" placeholder="Ejemplo: 7.0">
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label for="clarificado" class="form-label">Clarificado:</label>
                                        <input type="number" id="clarificado" name="clarificado" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($clarificado); ?>" placeholder="Ejemplo: 6.6">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="meladura" class="form-label">Meladura:</label>
                                        <input type="number" id="meladura" name="meladura" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($meladura); ?>" placeholder="Ejemplo: 5.0">
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <label for="observacion" class="form-label">Observación:</label>
                                        <textarea id="observacion" name="observacion" class="form-control" maxlength="100" placeholder="Escribe una observación"><?= htmlspecialchars($observacion); ?></textarea>
                                    </div>
                                </div>

                                <div class="mb-3 mt-3">
                                    <button type="submit" class="btn btn-submit">Registrar</button>
                                    <a href="<?= BASE_PATH ?>controllers/controlPH/mostrarControlPH.php?periodoZafra=<?= urlencode($periodoZafra); ?>&fechaIngreso=<?= urlencode($fechaIngreso); ?>" class="btn btn-secondary">Regresar</a>
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