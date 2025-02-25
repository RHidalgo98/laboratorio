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

$error = ""; // Variable para capturar errores

// Obtener valores de periodo de zafra y fecha de ingreso
$periodoZafra = isset($_GET['periodoZafra']) ? $_GET['periodoZafra'] : '';
$fechaIngreso = isset($_GET['fechaIngreso']) ? $_GET['fechaIngreso'] : '';
$fechaInicio = '';
$fechaFin = '';

if (!empty($periodoZafra)) {
    try {
        // Consulta para obtener las fechas de inicio y fin del periodo de zafra seleccionado
        $fechaQuery = "SELECT inicio, fin FROM periodoszafra WHERE periodo = :periodoZafra";
        $fechaStmt = $conexion->prepare($fechaQuery);
        $fechaStmt->bindParam(':periodoZafra', $periodoZafra);
        $fechaStmt->execute();
        $fechaResult = $fechaStmt->fetch(PDO::FETCH_ASSOC);

        if ($fechaResult) {
            $fechaInicio = $fechaResult['inicio'];
            $fechaFin = $fechaResult['fin'];
        }
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error al cargar las fechas del periodo de zafra: " . $e->getMessage() . "</div>";
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
    <title>Mostrar Lectura</title>

    <!-- Incluir hojas de estilo de SB Admin 2 y Bootstrap -->
    <link href="<?= BASE_PATH ?>public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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
            background-color: #ffecb3;
        }

        .table th,
        .table td {
            text-align: center !important;
            vertical-align: middle;
            padding: 5px;
            width: 5% !important;
        }

        .table td.observacion {
            width: 15% !important;
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

    <!-- Contenedor principal -->
    <div id="wrapper">

        <?php include ROOT_PATH . 'includes/sidebar.php'; ?>

        <!-- Contenedor de contenido -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Contenido principal -->
            <div id="content">

                <?php include ROOT_PATH . 'includes/topbar.php'; ?>

                <!-- Inicio del contenido de la página -->
                <div class="container-fluid">

                    <!-- Encabezado de la página -->
                    <h1 class="h3 mb-2 text-gray-800">Registros de Lecturas</h1>

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
                            <a href="<?= BASE_PATH ?>forms/registrarLectura.php?periodoZafra=<?= urlencode($periodoZafra); ?>&fechaIngreso=<?= urlencode($fechaIngreso); ?>" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Nuevo Registro
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Mostrar registros si se ha seleccionado periodo de zafra y fecha de ingreso
                    if (!empty($periodoZafra) && !empty($fechaIngreso)) {
                        // Consulta para obtener los registros que ya existen en la base de datos para el periodo y fecha seleccionados
                        $query = "SELECT idLectura, lecturaTurbo9_4MW, lecturaTurbo2000KW, lecturaTurbo1600KW, lecturaTurbo1500KW, lecturaTurbo800KW, lecturaZonaUrbana, lecturaENEE, observacion
                                  FROM lecturas
                                  WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso
                                  ORDER BY idLectura ASC";
                        $stmt = $conexion->prepare($query);
                        $stmt->bindParam(':periodoZafra', $periodoZafra);
                        $stmt->bindParam(':fechaIngreso', $fechaIngreso);
                        $stmt->execute();
                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if ($result) {
                            $totalGeneracionEnergia = 0;
                            $totalEnergiaComprada = 0;
                            $totalZonaUrbana = 0;
                            $totalENEE = 0;
                            $totalTurbo9_4MW = 0;
                            $totalTurbo2000KW = 0;
                            $totalTurbo1600KW = 0;
                            $totalTurbo1500KW = 0;
                            $totalTurbo800KW = 0;

                            echo "<div class='card shadow mb-4'>";
                            echo "<div class='card-body'>";
                            echo "<div class='table-responsive'>";
                            echo "<table class='table table-bordered' id='tablaLecturas' width='100%' cellspacing='0'>";
                            echo "<thead class='table-success'>
                                    <tr>
                                        <th></th>
                                        <th></th>
                                        <th>Turbo 9.4 MW</th>
                                        <th>Turbo 2000 KW</th>
                                        <th>Turbo 1600 KW</th>
                                        <th>Turbo 1500 KW</th>
                                        <th>Turbo 800 KW</th>
                                        <th>Zona Urbana</th>
                                        <th>ENEE</th>
                                        <th>Observación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>";
                            echo "<tbody>";

                            // Recorrer los registros y mostrarlos en la tabla
                            foreach ($result as $row) {
                                $idLectura = $row['idLectura'];
                                $lecturaTurbo9_4MW = $row['lecturaTurbo9_4MW'];
                                $lecturaTurbo2000KW = $row['lecturaTurbo2000KW'];
                                $lecturaTurbo1600KW = $row['lecturaTurbo1600KW'];
                                $lecturaTurbo1500KW = $row['lecturaTurbo1500KW'];
                                $lecturaTurbo800KW = $row['lecturaTurbo800KW'];
                                $lecturaZonaUrbana = $row['lecturaZonaUrbana'];
                                $lecturaENEE = $row['lecturaENEE'];
                                $observacion = !empty($row['observacion']) ? $row['observacion'] : '-';

                                // Calcular generación total y energía total utilizada
                                $generacionEnergia = $lecturaTurbo9_4MW + $lecturaTurbo2000KW + $lecturaTurbo1600KW + $lecturaTurbo1500KW + $lecturaTurbo800KW;
                                $energiaComprada = $generacionEnergia - $lecturaZonaUrbana;
                                $energiaTotal = $energiaComprada - $lecturaZonaUrbana;

                                // Sumar valores para el cálculo de totales
                                $totalGeneracionEnergia += $generacionEnergia;
                                $totalEnergiaComprada += $energiaTotal;
                                $totalZonaUrbana += $lecturaZonaUrbana;
                                // $totalENEE += $lecturaENEE;
                                $totalENEE += $energiaComprada;
                                $totalTurbo9_4MW += $lecturaTurbo9_4MW;
                                $totalTurbo2000KW += $lecturaTurbo2000KW;
                                $totalTurbo1600KW += $lecturaTurbo1600KW;
                                $totalTurbo1500KW += $lecturaTurbo1500KW;
                                $totalTurbo800KW += $lecturaTurbo800KW;

                                echo "<tr>
                                        <td></td>
                                        <td></td>
                                        <td>" . formatear_valor($lecturaTurbo9_4MW) . "</td>
                                        <td>" . formatear_valor($lecturaTurbo2000KW) . "</td>
                                        <td>" . formatear_valor($lecturaTurbo1600KW) . "</td>
                                        <td>" . formatear_valor($lecturaTurbo1500KW) . "</td>
                                        <td>" . formatear_valor($lecturaTurbo800KW) . "</td>
                                        <td>" . formatear_valor($lecturaZonaUrbana) . "</td>
                                        <td>" . formatear_valor($lecturaENEE) . "</td>
                                        <td class='observacion'>" . htmlspecialchars($observacion) . "</td>
                                        <td>
                                            <a href='" . BASE_PATH . "controllers/lecturas/editarLectura.php?id=" . urlencode($idLectura) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "' class='text-warning me-2 action-icons'>
                                                <i class='fas fa-edit'></i>
                                            </a>
                                            <a href='" . BASE_PATH . "controllers/lecturas/eliminarLectura.php?id=" . urlencode($idLectura) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "' 
                                            class='text-danger action-icons' 
                                            onclick=\"return confirm('¿Estás seguro de eliminar este registro?')\">
                                                <i class='fas fa-trash'></i>
                                            </a>
                                        </td>
                                    </tr>";
                            }

                            // Mostrar fila de totales
                            echo "<tr class='table-info'>
                                    <td colspan='2'>Generación de Energía</td>
                                    <td  colspan='5'>" . formatear_valor($totalGeneracionEnergia) . "</td>
                                    <td>" . formatear_valor($totalZonaUrbana) . "</td>
                                    <td>" . formatear_valor($totalENEE) . "</td>
                                    <td></td>
                                    <td></td>
                                </tr>";
                            echo "<tr class='table-warning'>
                                    <td colspan='2'>Total Energía Comprada</td>
                                    <td colspan='9'>" . formatear_valor($totalEnergiaComprada) . "</td>
                                </tr>";

                            echo "</tbody>";
                            echo "</table>";
                            echo "</div>";
                        } else {
                            echo "<div class='alert alert-info'>No se encontraron registros para el periodo de zafra y la fecha seleccionados.</div>";
                        }
                    }
                    ?>
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
            $('#tablaLectura').DataTable({
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