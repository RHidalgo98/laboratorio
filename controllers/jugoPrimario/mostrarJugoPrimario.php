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

// Inicializa $result como un array vacío en caso de que la consulta no devuelva resultados
$result = [];

// Obtener las fechas de inicio y fin del periodo seleccionado
$fechaInicio = '';
$fechaFin = '';
if (isset($_GET['periodoZafra']) && !empty($_GET['periodoZafra'])) {
    $periodoZafra = $_GET['periodoZafra'];
    $queryFechas = "SELECT inicio, fin FROM periodoszafra WHERE periodo = :periodoZafra";
    $stmtFechas = $conexion->prepare($queryFechas);
    $stmtFechas->bindParam(':periodoZafra', $periodoZafra);
    $stmtFechas->execute();
    $fechas = $stmtFechas->fetch(PDO::FETCH_ASSOC);
    if ($fechas) {
        $fechaInicio = $fechas['inicio'];
        $fechaFin = $fechas['fin'];
    }
}

if (isset($_GET['periodoZafra']) && !empty($_GET['periodoZafra']) && isset($_GET['fechaIngreso']) && !empty($_GET['fechaIngreso'])) {
    try {
        $periodoZafra = $_GET['periodoZafra'];
        $fechaIngreso = $_GET['fechaIngreso'];

        // Consulta para obtener los datos del periodo y la fecha seleccionados
        $query = "SELECT idJugoPrimario, hora, observacion, brix, sac, mlGastado, fechaIngreso, periodoZafra 
                  FROM jugoprimario 
                  WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";

        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':periodoZafra', $periodoZafra);
        $stmt->bindParam(':fechaIngreso', $fechaIngreso);
        $stmt->execute();

        // Asigna los resultados a $result
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error al obtener los registros: " . $e->getMessage() . "</div>";
    }
}

// Función para formatear valores
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
    <title>Mostrar Jugo Primario</title>

    <!-- SB Admin 2 Stylesheets -->
    <link href="<?php echo BASE_PATH; ?>public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_PATH; ?>public/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_PATH; ?>public/css/sb-admin-2.min.css" rel="stylesheet">

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

        .table td.observacion {
            width: 25% !important;
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

                    <!-- Page Heading -->
                    <h1 class="h3 mb-2 text-gray-800">Registros de Jugo Primario</h1>

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

                    <!-- Filtros de selección de periodo y fecha -->
                    <form method="GET" action="" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="periodoZafra" class="form-label">Periodo Zafra:</label>
                                <select id="periodoZafra" name="periodoZafra" class="form-select" onchange="document.getElementById('fechaIngreso').value=''; this.form.submit()">
                                    <?php
                                    $query = "SELECT periodo FROM periodoszafra WHERE activo = 1 ORDER BY periodo DESC";
                                    $stmt = $conexion->prepare($query);
                                    $stmt->execute();
                                    $periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    echo '<option value="">Seleccione un periodo:</option>';
                                    foreach ($periodos as $periodo) {
                                        $selected = (isset($_GET['periodoZafra']) && $_GET['periodoZafra'] == $periodo['periodo']) ? 'selected' : '';
                                        echo "<option value='{$periodo['periodo']}' $selected>{$periodo['periodo']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="fechaIngreso" class="form-label">Fecha de Ingreso:</label>
                                <input type="date" id="fechaIngreso" name="fechaIngreso" class="form-control" value="<?php echo isset($_GET['fechaIngreso']) ? $_GET['fechaIngreso'] : ''; ?>" min="<?php echo $fechaInicio; ?>" max="<?php echo $fechaFin; ?>" <?php echo empty($fechaInicio) || empty($fechaFin) ? 'disabled' : ''; ?> onchange="this.form.submit()" onkeydown="return false">
                            </div>
                        </div>
                    </form>

                    <!-- Verificar si ambos valores existen antes de realizar la consulta -->
                    <?php if (!isset($_GET['periodoZafra']) || empty($_GET['periodoZafra']) || !isset($_GET['fechaIngreso']) || empty($_GET['fechaIngreso'])): ?>
                        <div class="alert alert-info">Por favor, seleccione un periodo de zafra y una fecha para ver los registros.</div>
                    <?php endif; ?>

                    <!-- Mostrar el botón solo si se han seleccionado el periodo y la fecha -->
                    <?php if (isset($_GET['periodoZafra']) && !empty($_GET['periodoZafra']) && isset($_GET['fechaIngreso']) && !empty($_GET['fechaIngreso'])): ?>
                        <div class="mb-4">
                            <a href="<?php echo BASE_PATH; ?>forms/registrarJugoPrimario.php?periodoZafra=<?php echo isset($_GET['periodoZafra']) ? urlencode($_GET['periodoZafra']) : ''; ?>&fechaIngreso=<?php echo isset($_GET['fechaIngreso']) ? urlencode($_GET['fechaIngreso']) : ''; ?>" class="btn btn-new">
                                <i class="fas fa-plus"></i> Nuevo Registro
                            </a>

                        </div>
                    <?php endif; ?>

                    <!-- Mostrar la tabla solo si se ha seleccionado el periodo de zafra y la fecha -->
                    <?php if (isset($_GET['periodoZafra']) && !empty($_GET['periodoZafra']) && isset($_GET['fechaIngreso']) && !empty($_GET['fechaIngreso'])): ?>
                        <?php if ($result): ?>
                            <div class="card shadow mb-4">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="tablaJugoPrimario" class="table table-bordered" width="100%" cellspacing="0">
                                            <thead class="table-success">
                                                <tr>
                                                    <th>Hora</th>
                                                    <th>Brix</th>
                                                    <th>SAC</th>
                                                    <th>Pureza</th>
                                                    <th>Azúcar Reducido</th>
                                                    <th>ML Gastado</th>
                                                    <th>Observación</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $horas = array(
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
                                                );

                                                $sumaBrix = $sumaSac = $sumaAzucRed = $sumaMLGastado = 0;
                                                $countAzucRed = $countMLGastado = 0;

                                                // Convertir los resultados en un array asociativo basado en las horas
                                                $dataHora = [];
                                                foreach ($result as $row) {
                                                    $hora = date('H:i', strtotime($row['hora']));
                                                    $dataHora[$hora] = $row;
                                                }

                                                // Iterar sobre el array de horas
                                                foreach ($horas as $hora) {
                                                    if (isset($dataHora[$hora])) {
                                                        $row = $dataHora[$hora];
                                                        $horaRow = date('H:i', strtotime($row['hora']));
                                                        $brix = formatear_valor($row['brix']);
                                                        $sac = formatear_valor($row['sac']);
                                                        $mlGastado = formatear_valor($row['mlGastado']);
                                                        $observacion = !empty($row['observacion']) ? $row['observacion'] : '-';
                                                        $idJugoPrimario = $row['idJugoPrimario'];

                                                        // Cálculo de pureza y azúcar reducido
                                                        $pureza = (is_numeric($brix) && $brix != 0) ? formatear_valor(($sac * 100) / $brix) : '-';
                                                        $azucRed = ($mlGastado != '-' && $mlGastado != 0) ? formatear_valor(12.5 / $mlGastado) : '-';

                                                        // Acumulación para el promedio
                                                        $sumaBrix += is_numeric($brix) ? $brix : 0;
                                                        $sumaSac += is_numeric($sac) ? $sac : 0;
                                                        if ($azucRed != '-') {
                                                            $sumaAzucRed += $azucRed;
                                                            $countAzucRed++;
                                                        }
                                                        if ($mlGastado != '-' && $mlGastado != 0) {
                                                            $sumaMLGastado += is_numeric($mlGastado) ? $mlGastado : 0;
                                                            $countMLGastado++;
                                                        }

                                                        echo "<tr>
                                                            <td>{$horaRow}</td>
                                                            <td>{$brix}</td>
                                                            <td>{$sac}</td>
                                                            <td>{$pureza}</td>
                                                            <td>{$azucRed}</td>
                                                            <td>{$mlGastado}</td>
                                                            <td class='observacion'>{$observacion}</td>
                                                            <td>
                                                                <a href='" . BASE_PATH . "controllers/jugoPrimario/editarJugoPrimario.php?id={$idJugoPrimario}' class='text-warning me-2 action-icons'>
                                                                    <i class='fas fa-edit'></i>
                                                                </a>
                                                                <a href='" . BASE_PATH . "controllers/jugoPrimario/eliminarJugoPrimario.php?id={$idJugoPrimario}&periodoZafra={$periodoZafra}&fechaIngreso={$fechaIngreso}' class='text-danger action-icons' onclick='return confirm(\"¿Estás seguro de eliminar este registro?\")'>
                                                                    <i class='fas fa-trash'></i>
                                                                </a>
                                                            </td>
                                                        </tr>";
                                                    } else {
                                                        echo "<tr>
                                                            <td>{$hora}</td>
                                                            <td>-</td>
                                                            <td>-</td>
                                                            <td>-</td>
                                                            <td>-</td>
                                                            <td>-</td>
                                                            <td class='observacion'>-</td>
                                                            <td>-</td>
                                                            </tr>";
                                                    }
                                                }

                                                // Fila de promedios
                                                if ($result) {
                                                    $promBrix = formatear_valor(round($sumaBrix / count($result), 2));
                                                    $promSac = formatear_valor(round($sumaSac / count($result), 2));
                                                    $promAzucRed = ($countAzucRed > 0) ? formatear_valor(round($sumaAzucRed / $countAzucRed, 2)) : '-';
                                                    $promMLGastado = ($countMLGastado > 0) ? formatear_valor(round($sumaMLGastado / $countMLGastado, 2)) : '-';
                                                    $promPureza = ($promBrix != '-') ? formatear_valor(round(($promSac * 100) / $promBrix, 2)) : '-';

                                                    echo "<tr class='table-info'>
                                                        <td>Promedio</td>
                                                        <td>{$promBrix}</td>
                                                        <td>{$promSac}</td>
                                                        <td>{$promPureza}</td>
                                                        <td>{$promAzucRed}</td>
                                                        <td>{$promMLGastado}</td>
                                                        <td class='observacion'></td>
                                                        <td></td>
                                                    </tr>";
                                                }
                                                ?>
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

    <!-- DataTables Initialization -->
    <script>
        $(document).ready(function() {
            $('#tablaJugoPrimario').DataTable({
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