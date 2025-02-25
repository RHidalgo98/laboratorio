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
    $query = "SELECT DATE_FORMAT(hora, '%H:%i') AS hora, primario, mezclado, residual, sulfitado, filtrado, alcalizado, clarificado, meladura, observacion, idControlPH 
    FROM controlph 
    WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
    $stmt = $conexion->prepare($query);
    $stmt->bindParam(':periodoZafra', $periodoZafra);
    $stmt->bindParam(':fechaIngreso', $fechaIngreso);
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Formatear registros para que estén basados en las horas
$datosPorHora = [];
foreach ($horasFijas as $hora) {
    $datosPorHora[$hora] = [
        'hora' => $hora,
        'primario' => null,
        'mezclado' => null,
        'residual' => null,
        'sulfitado' => null,
        'filtrado' => null,
        'alcalizado' => null,
        'clarificado' => null,
        'meladura' => null,
        'observacion' => null,
        'idControlPH' => null // Para acciones de edición/eliminación
    ];
}

// Rellenar con datos existentes en la base de datos
foreach ($registros as $registro) {
    $hora = $registro['hora'];
    if (isset($datosPorHora[$hora])) { // Comprobar si la hora coincide con las horas predeterminadas
        $datosPorHora[$hora] = $registro;
    }
}
// Calcular sumas y promedios
$sumas = [
    'primario' => 0,
    'mezclado' => 0,
    'residual' => 0,
    'sulfitado' => 0,
    'filtrado' => 0,
    'alcalizado' => 0,
    'clarificado' => 0,
    'meladura' => 0
];
$contadores = array_fill_keys(array_keys($sumas), 0);

foreach ($datosPorHora as $registro) {
    foreach ($sumas as $key => $value) {
        // Asegurarse de que el valor sea numérico y mayor a cero
        if (isset($registro[$key]) && is_numeric($registro[$key]) && $registro[$key] > 0) {
            $sumas[$key] += $registro[$key]; // Sumar valores sin redondear
            $contadores[$key]++;
        }
    }
}

$promedios = [];
foreach ($sumas as $key => $value) {
    // Calcula solo si el contador es mayor a 0
    $promedios[$key] = ($contadores[$key] > 0)
        ? round($value / $contadores[$key], 2)  // Redondea el promedio
        : '-'; // Mostrar '-' si no hay valores válidos
}

// Función para formatear valores
function formatear_valor($valor, $decimales = 2)
{
    if ($valor === "" || $valor == 0 || $valor == 0.00 || !is_numeric($valor)) {
        return "-";
    }
    return number_format((float)$valor, $decimales);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mostrar Control de PH</title>

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

        .table th,
        .table td {
            text-align: center !important;
            vertical-align: middle;
            padding: 5px;
        }

        .table td.observacion {
            width: 30% !important;
            /* Aumentar aún más el tamaño de la columna de observación */
        }

        .table td:not(.observacion) {
            width: 6.66% !important;
            /* Ajustar el tamaño de las otras columnas */
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
                    <h1 class="h3 mb-2 text-gray-800">Registros del Control de PH</h1>

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

                    <!-- Mostrar botón para nuevo registro si se ha seleccionado periodo de zafra y fecha de ingreso -->
                    <?php if (!empty($periodoZafra) && !empty($fechaIngreso)): ?>
                        <div class="mb-4">
                            <a href="<?= BASE_PATH ?>forms/registrarControlPH.php?periodoZafra=<?= urlencode($periodoZafra); ?>&fechaIngreso=<?= urlencode($fechaIngreso); ?>" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Nuevo Registro
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Mostrar tabla o mensaje de no registros -->
                    <?php if (!empty($periodoZafra) && !empty($fechaIngreso)): ?>
                        <?php if (!empty($registros)): ?>
                            <div class="card shadow mb-4">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead class="table-success">
                                                <tr>
                                                    <th>Hora</th>
                                                    <th>Primario</th>
                                                    <th>Mezclado</th>
                                                    <th>Residual</th>
                                                    <th>Sulfitado</th>
                                                    <th>Filtrado</th>
                                                    <th>Alcalizado</th>
                                                    <th>Clarificado</th>
                                                    <th>Meladura</th>
                                                    <th>Observación</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($datosPorHora as $hora => $registro): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($hora) ?></td>
                                                        <td><?= formatear_valor($registro['primario'], 1) ?></td>
                                                        <td><?= formatear_valor($registro['mezclado'], 1) ?></td>
                                                        <td><?= formatear_valor($registro['residual'], 1) ?></td>
                                                        <td><?= formatear_valor($registro['sulfitado'], 1) ?></td>
                                                        <td><?= formatear_valor($registro['filtrado'], 1) ?></td>
                                                        <td><?= formatear_valor($registro['alcalizado'], 1) ?></td>
                                                        <td><?= formatear_valor($registro['clarificado'], 1) ?></td>
                                                        <td><?= formatear_valor($registro['meladura'], 1) ?></td>
                                                        <td class="observacion"><?= htmlspecialchars(!empty($registro['observacion']) ? $registro['observacion'] : '-') ?></td>
                                                        <td>
                                                            <?php if ($registro['idControlPH'] !== null): ?>
                                                                <a href="<?= BASE_PATH ?>controllers/controlPH/editarControlPH.php?id=<?= urlencode($registro['idControlPH']) ?>" class="text-warning me-2">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="<?= BASE_PATH ?>controllers/controlPH/eliminarControlPH.php?id=<?= urlencode($registro['idControlPH']) ?>&periodoZafra=<?= urlencode($periodoZafra) ?>&fechaIngreso=<?= urlencode($fechaIngreso) ?>"
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

                                                <!-- Fila de promedios -->
                                                <tr class="table-info">
                                                    <td><strong>Promedio</strong></td>
                                                    <td><?= formatear_valor($promedios['primario']) ?></td>
                                                    <td><?= formatear_valor($promedios['mezclado']) ?></td>
                                                    <td><?= formatear_valor($promedios['residual']) ?></td>
                                                    <td><?= formatear_valor($promedios['sulfitado']) ?></td>
                                                    <td><?= formatear_valor($promedios['filtrado']) ?></td>
                                                    <td><?= formatear_valor($promedios['alcalizado']) ?></td>
                                                    <td><?= formatear_valor($promedios['clarificado']) ?></td>
                                                    <td><?= formatear_valor($promedios['meladura']) ?></td>
                                                    <td></td>
                                                    <td></td>
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
            $('#tablaControlPH').DataTable({
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