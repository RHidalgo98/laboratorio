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
$turno = isset($_GET['turno']) ? $_GET['turno'] : '';
$tnCanaACHSA = isset($_GET['tnCanaACHSA']) ? $_GET['tnCanaACHSA'] : '';
$moliendaTn = isset($_GET['moliendaTn']) ? $_GET['moliendaTn'] : '';
$sacos50AzucarBlanco = isset($_GET['sacos50AzucarBlanco']) ? $_GET['sacos50AzucarBlanco'] : '';
$sacosAzucarMorena = isset($_GET['sacosAzucarMorena']) ? $_GET['sacosAzucarMorena'] : '';
$jumboAzucarBlanco = isset($_GET['jumboAzucarBlanco']) ? $_GET['jumboAzucarBlanco'] : '';
$observacion = isset($_GET['observacion']) ? $_GET['observacion'] : '';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Saco de Azúcar</title>

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
                    <h1 class="h3 mb-2 text-gray-800">Registro de Saco de Azúcar</h1>

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
                            <form action="<?= BASE_PATH ?>controllers/sacoAzucar/insertarSacoAzucar.php" method="POST">
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
                                    <select id="turno" name="turno" class="form-control" required>
                                        <option value="" disabled selected>Seleccione un turno:</option>
                                        <option value="1" <?= $turno == '1' ? 'selected' : ''; ?>>Turno 1</option>
                                        <option value="2" <?= $turno == '2' ? 'selected' : ''; ?>>Turno 2</option>
                                        <option value="3" <?= $turno == '3' ? 'selected' : ''; ?>>Turno 3</option>
                                    </select>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="tnCanaACHSA" class="form-label">Toneladas de Caña ACHSA:</label>
                                        <input type="number" id="tnCanaACHSA" name="tnCanaACHSA" class="form-control" step="0.01" min="0" placeholder="Ejemplo: 123.45" value="<?= htmlspecialchars($tnCanaACHSA); ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label for="moliendaTn" class="form-label">Toneladas de Molienda:</label>
                                        <input type="number" id="moliendaTn" name="moliendaTn" class="form-control" step="0.01" min="0" placeholder="Ejemplo: 123.45" value="<?= htmlspecialchars($moliendaTn); ?>">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="sacos50AzucarBlanco" class="form-label">Sacos de Azúcar Blanco:</label>
                                        <input type="number" id="sacos50AzucarBlanco" name="sacos50AzucarBlanco" class="form-control" min="0" placeholder="Ejemplo: 100" value="<?= htmlspecialchars($sacos50AzucarBlanco); ?>">
                                    </div>

                                    <div class="col-md-4">
                                        <label for="sacosAzucarMorena" class="form-label">Sacos de Azúcar Morena:</label>
                                        <input type="number" id="sacosAzucarMorena" name="sacosAzucarMorena" class="form-control" min="0" placeholder="Ejemplo: 50" value="<?= htmlspecialchars($sacosAzucarMorena); ?>">
                                    </div>

                                    <div class="col-md-4">
                                        <label for="jumboAzucarBlanco" class="form-label">Jumbo de 1.25 Toneladas de Azúcar Blanco:</label>
                                        <input type="number" id="jumboAzucarBlanco" name="jumboAzucarBlanco" class="form-control" min="0" placeholder="Ejemplo: 10" value="<?= htmlspecialchars($jumboAzucarBlanco); ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="observacion" class="form-label">Observación:</label>
                                    <textarea id="observacion" name="observacion" class="form-control" maxlength="100" placeholder="Escribe una observación"><?= htmlspecialchars($observacion); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <button type="submit" class="btn btn-submit">Registrar</button>
                                    <a href="<?= BASE_PATH ?>controllers/sacoAzucar/mostrarSacoAzucar.php?periodoZafra=<?= urlencode($periodoZafra); ?>&fechaIngreso=<?= urlencode($fechaIngreso); ?>" class="btn btn-secondary">Regresar</a>
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