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

// Lista fija de horas
$horasFijas = [
    "06:00",
    "07:00",
    "08:00",
    "09:00",
    "10:00",
    "11:00",
    "12:00",
    "13:00",
    "14:00",
    "15:00",
    "16:00",
    "17:00",
    "18:00",
    "19:00",
    "20:00",
    "21:00",
    "22:00",
    "23:00",
    "00:00",
    "01:00",
    "02:00",
    "03:00",
    "04:00",
    "05:00"
];

// Obtener valores de periodo de zafra y fecha de ingreso
$periodoZafra = isset($_GET['periodoZafra']) ? $_GET['periodoZafra'] : '';
$fechaIngreso = isset($_GET['fechaIngreso']) ? $_GET['fechaIngreso'] : '';
$fechaInicio = '';
$fechaFin = '';

// Consultar registros de la base de datos
$registros = [];
if (!empty($periodoZafra) && !empty($fechaIngreso)) {
    $query = "SELECT DATE_FORMAT(hora, '%H:%i') AS hora, pol1, gFt1, pol2, gFt2, pol3, gFt3, observacion, idFiltroCachaza
              FROM filtrocachaza
              WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
    $stmt = $conexion->prepare($query);
    $stmt->bindParam(':periodoZafra', $periodoZafra);
    $stmt->bindParam(':fechaIngreso', $fechaIngreso);
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Organizar registros por hora y por filtro
$datosPorHora = [];
foreach ($horasFijas as $hora) {
    $datosPorHora[$hora] = [
        'pol1' => null,
        'gFt1' => null,
        'pol2' => null,
        'gFt2' => null,
        'pol3' => null,
        'gFt3' => null,
        'idFiltroCachaza' => null
    ];
}

foreach ($registros as $registro) {
    $hora = $registro['hora'];
    $datosPorHora[$hora] = [
        'pol1' => $registro['pol1'] ?? null,
        'gFt1' => $registro['gFt1'] ?? null,
        'pol2' => $registro['pol2'] ?? null,
        'gFt2' => $registro['gFt2'] ?? null,
        'pol3' => $registro['pol3'] ?? null,
        'gFt3' => $registro['gFt3'] ?? null,
        'idFiltroCachaza' => $registro['idFiltroCachaza'] ?? null
    ];
}

// Inicializar acumuladores y contadores
$promedios = [
    'Filtro 1' => ['pol' => 0, 'gFt_ft2' => 0, 'count_pol' => 0, 'count_gFt' => 0],
    'Filtro 2' => ['pol' => 0, 'gFt_ft2' => 0, 'count_pol' => 0, 'count_gFt' => 0],
    'Filtro 3' => ['pol' => 0, 'gFt_ft2' => 0, 'count_pol' => 0, 'count_gFt' => 0],
];

// Calcular acumuladores para cada filtro
foreach ($datosPorHora as $hora => $datos) {
    if (is_numeric($datos['pol1']) && $datos['pol1'] != 0) {
        $promedios['Filtro 1']['pol'] += $datos['pol1'];
        $promedios['Filtro 1']['count_pol']++;
    }
    if (is_numeric($datos['gFt1']) && $datos['gFt1'] != 0) {
        $promedios['Filtro 1']['gFt_ft2'] += $datos['gFt1'];
        $promedios['Filtro 1']['count_gFt']++;
    }

    if (is_numeric($datos['pol2']) && $datos['pol2'] != 0) {
        $promedios['Filtro 2']['pol'] += $datos['pol2'];
        $promedios['Filtro 2']['count_pol']++;
    }
    if (is_numeric($datos['gFt2']) && $datos['gFt2'] != 0) {
        $promedios['Filtro 2']['gFt_ft2'] += $datos['gFt2'];
        $promedios['Filtro 2']['count_gFt']++;
    }

    if (is_numeric($datos['pol3']) && $datos['pol3'] != 0) {
        $promedios['Filtro 3']['pol'] += $datos['pol3'];
        $promedios['Filtro 3']['count_pol']++;
    }
    if (is_numeric($datos['gFt3']) && $datos['gFt3'] != 0) {
        $promedios['Filtro 3']['gFt_ft2'] += $datos['gFt3'];
        $promedios['Filtro 3']['count_gFt']++;
    }
}

// Calcular promedios finales
foreach ($promedios as $filtro => &$valores) {
    $valores['pol'] = $valores['count_pol'] > 0 ? number_format(round($valores['pol'] / $valores['count_pol'], 2), 2) : '0.00';
    $valores['gFt_ft2'] = $valores['count_gFt'] > 0 ? number_format(round($valores['gFt_ft2'] / $valores['count_gFt'], 2), 2) : '0.00';
    $valores['pol'] = formatear_valor($valores['pol']);
    $valores['gFt_ft2'] = formatear_valor($valores['gFt_ft2']);
}
unset($valores);

// Cálculo del Promedio Pol Cachaza
$promedioPolCachaza = '-';
$totalPol = 0;
$totalCount = 0;

if ($promedios['Filtro 1']['count_pol'] > 0) {
    $totalPol += $promedios['Filtro 1']['pol'];
    $totalCount += 1;
}

if ($promedios['Filtro 2']['count_pol'] > 0) {
    $totalPol += 0.5 * $promedios['Filtro 2']['pol'];
    $totalCount += 0.5;
}

if ($promedios['Filtro 3']['count_pol'] > 0) {
    $totalPol += $promedios['Filtro 3']['pol'];
    $totalCount += 1;
}

if ($totalCount > 0) {
    $promedioPolCachaza = round($totalPol / 2.5, 2);
    $promedioPolCachaza = formatear_valor($promedioPolCachaza);
}

function formatear_valor($valor, $decimales = 2)
{
    return ($valor === "" || $valor == 0 || $valor == 0.00) ? "-" : number_format($valor, $decimales);
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mostrar Filtros Cachaza</title>

    <!-- Incluir hojas de estilo de SB Admin 2 y Bootstrap -->
    <link href="<?= BASE_PATH ?>public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>public/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>public/css/sb-admin-2.min.css" rel="stylesheet">
    <style>
        .btn-new {
            background-color: #4e73df;
            color: white;
        }

        .btn-new:hover {
            background-color: #224abe;
        }

        .action-icons {
            cursor: pointer;
            font-size: 1.2em;
            color: #ffc107;
        }

        .table-info {
            font-weight: bold;
            background-color: #e0f7fa;
        }

        .table-warning {
            font-weight: bold;
            background-color: #fff3e0;
        }

        .table th,
        .table td {
            text-align: center !important;
            vertical-align: middle;
            padding: 5px;
            width: 10% !important;
        }

        .table td.observacion {
            width: 20% !important;
            /* Aumentar el tamaño de la columna de observación */
        }

        .sidebar {
            width: 200px;
            height: 100vh;
            /* Altura completa de la ventana */
            position: sticky;
            /* Fija la posición del sidebar */
            top: 0;
            /* Mantiene el sidebar en la parte superior al hacer scroll */
            background-color: #f4f4f4;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            z-index: 1050; /* Asegura que el sidebar esté por encima de otros elementos */
        }

        .content {
            margin-left: 220px;
            /* Espacio para el sidebar */
            padding: 20px;
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
                    <h1 class="h3 mb-4 text-gray-800">Registros de Filtros Cachaza</h1>

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

                    <!-- Formulario para seleccionar periodo de zafra y fecha de ingreso -->
                    <form method="GET" action="" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="periodoZafra" class="form-label">Periodo Zafra:</label>
                                <select id="periodoZafra" name="periodoZafra" class="form-select" onchange="document.getElementById('fechaIngreso').value=''; this.form.submit()">
                                    <option value="">Seleccione un periodo:</option>
                                    <?php
                                    try {
                                        // Consulta para obtener los periodos de zafra desde la tabla periodozafra
                                        $periodoQuery = "SELECT periodo FROM periodoszafra WHERE activo = 1 ORDER BY periodo DESC";
                                        $periodoStmt = $conexion->prepare($periodoQuery);
                                        $periodoStmt->execute();
                                        $periodos = $periodoStmt->fetchAll(PDO::FETCH_ASSOC);

                                        foreach ($periodos as $periodo) {
                                            $selected = ($periodoZafra == $periodo['periodo']) ? 'selected' : '';
                                            echo "<option value='" . htmlspecialchars($periodo['periodo']) . "' $selected>" . htmlspecialchars($periodo['periodo']) . "</option>";
                                        }
                                    } catch (PDOException $e) {
                                        echo "<div class='alert alert-danger'>Error al cargar los periodos de zafra: " . $e->getMessage() . "</div>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="fechaIngreso" class="form-label">Fecha de Ingreso:</label>
                                <input type="date" id="fechaIngreso" name="fechaIngreso" class="form-control" value="<?php echo $fechaIngreso; ?>" onchange="this.form.submit()" onkeydown="return false" min="<?php echo $fechaInicio; ?>" max="<?php echo $fechaFin; ?>" <?php echo empty($periodoZafra) ? 'disabled' : ''; ?>>
                            </div>
                        </div>
                    </form>

                    <!-- Mostrar mensaje si no se ha seleccionado periodo de zafra o fecha de ingreso -->
                    <?php if (empty($periodoZafra) || empty($fechaIngreso)): ?>
                        <div class="alert alert-info">Por favor, seleccione un periodo de zafra y una fecha para ver los registros.</div>
                    <?php endif; ?>

                    <!-- Botón para nuevo registro -->
                    <?php if (!empty($periodoZafra) && !empty($fechaIngreso)): ?>
                        <div class="mb-4">
                            <a href="<?= BASE_PATH ?>forms/registrarFiltrosCachaza.php?periodoZafra=<?= urlencode($periodoZafra); ?>&fechaIngreso=<?= urlencode($fechaIngreso); ?>" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Nuevo Registro
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Tabla -->
                    <?php if (!empty($periodoZafra) && !empty($fechaIngreso)): ?>
                        <?php if (!empty($registros)): ?>
                            <div class="card shadow mb-4">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead class="table-success">
                                                <tr>
                                                    <th>Hora</th>
                                                    <th>Pol (F1)</th>
                                                    <th>g/Ft² (F1)</th>
                                                    <th>Pol (F2)</th>
                                                    <th>g/Ft² (F2)</th>
                                                    <th>Pol (F3)</th>
                                                    <th>g/Ft² (F3)</th>
                                                    <th>Observación</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($datosPorHora as $hora => $registro): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($hora) ?></td>
                                                        <td><?= formatear_valor($registro['pol1']) ?></td>
                                                        <td><?= formatear_valor($registro['gFt1'], 0) ?></td>
                                                        <td><?= formatear_valor($registro['pol2']) ?></td>
                                                        <td><?= formatear_valor($registro['gFt2'], 0) ?></td>
                                                        <td><?= formatear_valor($registro['pol3']) ?></td>
                                                        <td><?= formatear_valor($registro['gFt3'], 0) ?></td>
                                                        <td class="observacion"><?= htmlspecialchars(!empty($registro['observacion']) ? $registro['observacion'] : '-') ?></td>
                                                        <td>
                                                            <?php if ($registro['idFiltroCachaza'] !== null): ?>
                                                                <a href="<?= BASE_PATH ?>controllers/filtrosCachaza/editarFC.php?id=<?= urlencode($registro['idFiltroCachaza']) ?>" class="text-warning me-2">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="<?= BASE_PATH ?>controllers/filtrosCachaza/eliminarFC.php?id=<?= urlencode($registro['idFiltroCachaza']) ?>&periodoZafra=<?= urlencode($periodoZafra) ?>&fechaIngreso=<?= urlencode($fechaIngreso) ?>"
                                                                    class="text-danger"
                                                                    onclick="return confirm('¿Estás seguro de eliminar este registro?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            <?php else: ?>
                                                                -
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <tr class="table-info">
                                                    <td>Promedio</td>
                                                    <td><?= $promedios['Filtro 1']['pol'] ?></td>
                                                    <td><?= $promedios['Filtro 1']['gFt_ft2'] ?></td>
                                                    <td><?= $promedios['Filtro 2']['pol'] ?></td>
                                                    <td><?= $promedios['Filtro 2']['gFt_ft2'] ?></td>
                                                    <td><?= $promedios['Filtro 3']['pol'] ?></td>
                                                    <td><?= $promedios['Filtro 3']['gFt_ft2'] ?></td>
                                                    <td></td>
                                                    <td></td>
                                                </tr>
                                                <tr class="table-warning">
                                                    <td colspan="4">Promedio Pol Cachaza</td>
                                                    <td colspan="5"><?= $promedioPolCachaza ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No se encontraron registros para el periodo de zafra y la fecha seleccionados.</div>
                        <?php endif; ?>
                    <?php endif; ?>
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

    <!-- Inicialización de DataTables -->
    <script>
        $(document).ready(function() {
            $('#tablaFiltrosCachaza').DataTable({
                "paging": false,
                "searching": false,
                "info": false,
                "order": false,
                "sort": false,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.1/i18n/es-ES.json"
                }
            });
        });
    </script>
    <script>
        // Smooth scroll to top
        $(document).ready(function() {
            $('a.scroll-to-top').click(function(event) {
                event.preventDefault();
                $('html, body').animate({
                    scrollTop: 0
                }, 'fast');
                return false;
            });
        });
    </script>
    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
</body>

</html>