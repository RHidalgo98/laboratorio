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

// Obtener valores de periodo de zafra y fecha de ingreso
$periodoZafra = isset($_GET['periodoZafra']) ? $_GET['periodoZafra'] : '';
$fechaIngreso = isset($_GET['fechaIngreso']) ? $_GET['fechaIngreso'] : '';
$fechaInicio = '';
$fechaFin = '';

// Inicializar variables de mensajes
$mensajeError = '';
$mensajeAdvertencia = '';

// Consultar registros de la base de datos
$registros = [];
if (!empty($periodoZafra) && !empty($fechaIngreso)) {
    $query = "SELECT turno, 
                     DATE_FORMAT(paro, '%h:%i %p') as paro, 
                     DATE_FORMAT(arranque, '%h:%i %p') as arranque, 
                     CASE 
                         WHEN arranque IS NULL OR arranque = '' THEN NULL
                         WHEN paro > arranque THEN SEC_TO_TIME(TIME_TO_SEC('24:00') - TIME_TO_SEC(paro) + TIME_TO_SEC(arranque))
                         ELSE SEC_TO_TIME(TIME_TO_SEC(arranque) - TIME_TO_SEC(paro)) 
                     END AS tiempoPerdido, 
                     motivo, idCausa 
              FROM causas 
              WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso 
              ORDER BY 
                  CASE 
                      WHEN turno = 'Turno C' AND TIME(paro) >= '21:00:00' THEN 0
                      WHEN turno = 'Turno C' AND TIME(paro) < '01:00:00' THEN 1
                      WHEN turno = 'Turno C' THEN 2
                      ELSE 3
                  END, paro";
    $stmt = $conexion->prepare($query);
    $stmt->bindParam(':periodoZafra', $periodoZafra);
    $stmt->bindParam(':fechaIngreso', $fechaIngreso);
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Separar registros por turnos
$turnos = ['Turno A', 'Turno B', 'Turno C'];
$datosPorTurno = array_fill_keys($turnos, []);
$totalTiempoPorTurno = array_fill_keys($turnos, '00:00');
$totalDia = '00:00';

// Función para sumar tiempos (solo horas y minutos)
function sumarTiempos($tiempo1, $tiempo2)
{
    list($horas1, $minutos1) = explode(':', $tiempo1);
    list($horas2, $minutos2) = explode(':', $tiempo2);
    $totalMinutos = ($horas1 * 60 + $minutos1) + ($horas2 * 60 + $minutos2);
    $horas = floor($totalMinutos / 60);
    $minutos = $totalMinutos % 60;
    return sprintf('%02d:%02d', $horas, $minutos);
}

// Función para validar tiempo
function validarTiempo($tiempo)
{
    list($horas, $minutos) = explode(':', $tiempo);
    $totalMinutos = $horas * 60 + $minutos;
    return $totalMinutos <= 1440; // 24 horas en minutos
}

// Inicializar variables
$totalTiempoPorTurno = array_fill_keys($turnos, '00:00');
$totalDia = '00:00';
$mensajeAdvertencia = '';

// Procesar registros
foreach ($registros as $registro) {
    if (isset($datosPorTurno[$registro['turno']])) {
        $registro['tiempoPerdido'] = !empty($registro['tiempoPerdido']) ? date('H:i', strtotime($registro['tiempoPerdido'])) : '-';
        $datosPorTurno[$registro['turno']][] = $registro;

        // Sumar tiempo perdido al total del turno
        if (!empty($registro['tiempoPerdido']) && $registro['tiempoPerdido'] !== '-') {
            $totalTiempoPorTurno[$registro['turno']] = sumarTiempos($totalTiempoPorTurno[$registro['turno']], $registro['tiempoPerdido']);
        }
    }
}

// Sumar el total de horas de todos los turnos para obtener el total del día
foreach ($totalTiempoPorTurno as $turno => $totalTiempo) {
    $totalDia = sumarTiempos($totalDia, $totalTiempo);
}

// Verificar si algún turno excede las 24 horas
foreach ($totalTiempoPorTurno as $turno => $totalTiempo) {
    if (!validarTiempo($totalTiempo)) {
        list($horas, $minutos) = explode(':', $totalTiempo);
        $totalMinutos = $horas * 60 + $minutos;
        $minutosExcedidos = $totalMinutos - 1440;
        $horasExcedidas = floor($minutosExcedidos / 60);
        $minutosExcedidos = $minutosExcedidos % 60;
        $mensajeAdvertencia .= "El tiempo total en $turno excede las 24 horas por ";
        if ($horasExcedidas > 0) {
            $mensajeAdvertencia .= "$horasExcedidas hora(s)";
            if ($minutosExcedidos > 0) {
                $mensajeAdvertencia .= " y $minutosExcedidos minuto(s)";
            }
        } else {
            $mensajeAdvertencia .= "$minutosExcedidos minuto(s)";
        }
        $mensajeAdvertencia .= ".\n";
    }
}

// Validar el tiempo total del día
if (!validarTiempo($totalDia)) {
    list($horas, $minutos) = explode(':', $totalDia);
    $totalMinutos = $horas * 60 + $minutos;
    $minutosExcedidos = $totalMinutos - 1440;
    $horasExcedidas = floor($minutosExcedidos / 60);
    $minutosExcedidos = $minutosExcedidos % 60;
    $mensajeAdvertencia .= 'El tiempo total del día excede las 24 horas por ';
    if ($horasExcedidas > 0) {
        $mensajeAdvertencia .= $horasExcedidas . ' hora(s)';
        if ($minutosExcedidos > 0) {
            $mensajeAdvertencia .= ' y ' . $minutosExcedidos . ' minuto(s)';
        }
    } else {
        $mensajeAdvertencia .= $minutosExcedidos . ' minuto(s)';
    }
    $mensajeAdvertencia .= '. Por favor, revisa los registros.';
    $permitirIngreso = false;
} else {
    $permitirIngreso = true;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mostrar Causas</title>

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
            width: 10% !important;
        }

        .table td.causa {
            width: 40% !important;
            /* Aumentar el tamaño de la columna de causa */
        }

        .total-dia {
            text-align: center;
            font-size: 1.2em;
            margin-top: 20px;
        }

        .turno-header {
            text-align: center;
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
                    <h1 class="h3 mb-2 text-gray-800">Registros de Causas</h1>

                    <!-- Mostrar mensajes de éxito o error -->
                    <?php if (!empty($mensajeError)): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($mensajeError); ?>
                        </div>
                    <?php endif; ?>
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
                    <?php if (!empty($mensajeAdvertencia)): ?>
                        <div class="alert alert-warning">
                            <?= nl2br(htmlspecialchars($mensajeAdvertencia)); ?>
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

                    <!-- Botones para cada turno -->
                    <?php if (!empty($periodoZafra) && !empty($fechaIngreso) && $permitirIngreso): ?>
                        <div class="mb-4">
                            <?php foreach ($turnos as $turno): ?>
                                <a href="<?= BASE_PATH ?>forms/registrarCausas.php?turno=<?= urlencode($turno) ?>&periodoZafra=<?= urlencode($periodoZafra); ?>&fechaIngreso=<?= urlencode($fechaIngreso); ?>" class="btn btn-primary btn-turno">
                                    <i class="fas fa-plus"></i> Nuevo <?= htmlspecialchars($turno ?? '') ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Tablas por turno -->
                    <?php if (!empty($periodoZafra) && !empty($fechaIngreso)): ?>
                        <?php if (empty($registros)): ?>
                            <div class="alert alert-info">No se encontraron registros para el periodo de zafra y la fecha seleccionados.</div>
                        <?php else: ?>
                            <?php foreach ($turnos as $turno): ?>
                                <div class="card shadow mb-4">
                                    <h4 class="turno-header"><?= htmlspecialchars($turno ?? '') ?></h4>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead class="table-success">
                                                <tr>
                                                    <th>Paro</th>
                                                    <th>Arranque</th>
                                                    <th>Tiempo Perdido</th>
                                                    <th class="causa">Causa</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($datosPorTurno[$turno])): ?>
                                                    <?php foreach ($datosPorTurno[$turno] as $registro): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($registro['paro'] ?? '') ?></td>
                                                            <td><?= !empty($registro['arranque']) ? htmlspecialchars($registro['arranque']) : '-' ?></td>
                                                            <td><?= !empty($registro['tiempoPerdido']) ? htmlspecialchars($registro['tiempoPerdido']) : '-' ?></td>
                                                            <td class="causa"><?= htmlspecialchars($registro['motivo'] ?? '') ?></td>
                                                            <td>
                                                                <a href="<?= BASE_PATH ?>controllers/causas/editarCausas.php?id=<?= urlencode($registro['idCausa']) ?>" class="text-warning me-2">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="<?= BASE_PATH ?>controllers/causas/eliminarCausas.php?id=<?= urlencode($registro['idCausa']) ?>&periodoZafra=<?= urlencode($periodoZafra) ?>&fechaIngreso=<?= urlencode($fechaIngreso) ?>"
                                                                    class="text-danger"
                                                                    onclick="return confirm('¿Estás seguro de eliminar este registro?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="5">No hay registros para este turno.</td>
                                                    </tr>
                                                <?php endif; ?>

                                                <tr class="table-info">
                                                    <td colspan="2">Total Turno</td>
                                                    <td><?= !empty($totalTiempoPorTurno[$turno]) ? $totalTiempoPorTurno[$turno] : '00:00' ?></td>
                                                    <td colspan="2"></td>
                                                </tr>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="total-dia">
                                <p><strong>Total del Día:</strong> <?= htmlspecialchars($totalDia) ?></p>
                            </div>
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
            $('#tablaCausas').DataTable({
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