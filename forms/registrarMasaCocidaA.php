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

// Obtener el siguiente valor para num
$sqlNum = "SELECT COALESCE(MAX(num), 0) + 1 AS next_num FROM masacocidaa WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
$stmtNum = $conexion->prepare($sqlNum);
$stmtNum->bindParam(':periodoZafra', $periodoZafra);
$stmtNum->bindParam(':fechaIngreso', $fechaIngreso);
$stmtNum->execute();
$nextNum = $stmtNum->fetchColumn();

// Obtener los valores de los campos del formulario si existen
$hora = isset($_GET['hora']) ? $_GET['hora'] : '';
$tacho = isset($_GET['tacho']) ? $_GET['tacho'] : '';
$volFt3 = isset($_GET['volFt3']) ? $_GET['volFt3'] : '';
$brix = isset($_GET['brix']) ? $_GET['brix'] : '';
$pol = isset($_GET['pol']) ? $_GET['pol'] : '';
$observacion = isset($_GET['observacion']) ? $_GET['observacion'] : '';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Masa Cocida A</title>

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
                    <h1 class="h3 mb-2 text-gray-800">Registro de Masa Cocida A</h1>

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
                            <form action="<?= BASE_PATH ?>controllers/masaCocidaA/insertarMasaCocidaA.php" method="POST">
                                <div class="mb-3">
                                    <label for="periodoZafra" class="form-label">Periodo Zafra:</label>
                                    <input type="text" id="periodoZafra" name="periodoZafra" class="form-control" value="<?= htmlspecialchars($periodoZafra); ?>" readonly required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="fechaIngreso" class="form-label">Fecha de Ingreso:</label>
                                    <input type="date" id="fechaIngreso" name="fechaIngreso" class="form-control" value="<?= htmlspecialchars($fechaIngreso); ?>" readonly required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="num" class="form-label">Num:</label>
                                    <input type="text" id="num" name="num" class="form-control" value="<?= htmlspecialchars($nextNum); ?>" readonly>
                                </div>

                                <div class="mb-3">
                                    <label for="hora" class="form-label">Hora:</label>
                                    <input type="time" id="hora" name="hora" class="form-control" value="<?= htmlspecialchars($hora); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="tacho" class="form-label">Tacho:</label>
                                    <input type="number" id="tacho" name="tacho" class="form-control" step="1" min="0" value="<?= htmlspecialchars($tacho); ?>" required placeholder="Ejemplo: 8">
                                </div>

                                <div class="mb-3">
                                    <label for="volFt3" class="form-label">Volumen (ft³):</label>
                                    <input type="number" id="volFt3" name="volFt3" class="form-control" step="1" min="0" value="<?= htmlspecialchars($volFt3); ?>" required placeholder="Ejemplo: 2100">
                                </div>

                                <div class="mb-3">
                                    <label for="brix" class="form-label">Brix:</label>
                                    <input type="number" id="brix" name="brix" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($brix); ?>" required placeholder="Ejemplo: 15.75">
                                </div>

                                <div class="mb-3">
                                    <label for="pol" class="form-label">Pol:</label>
                                    <input type="number" id="pol" name="pol" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($pol); ?>" required placeholder="Ejemplo: 13.25">
                                </div>

                                <div class="mb-3">
                                    <label for="observacion" class="form-label">Observación:</label>
                                    <textarea id="observacion" name="observacion" class="form-control" maxlength="100" placeholder="Escribe una observación"><?= htmlspecialchars($observacion); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <button type="submit" class="btn btn-submit">Registrar</button>
                                    <a href="<?= BASE_PATH ?>controllers/masaCocidaA/mostrarMasaCocidaA.php?periodoZafra=<?= urlencode($periodoZafra); ?>&fechaIngreso=<?= urlencode($fechaIngreso); ?>" class="btn btn-secondary">Regresar</a>
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
