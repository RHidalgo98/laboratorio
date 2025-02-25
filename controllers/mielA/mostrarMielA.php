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

// Obtener los filtros de periodo y fecha
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
    <title>Mostrar Miel A</title>

    <!-- SB Admin 2 Stylesheets -->
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
            width: 5% !important;
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
                    <h1 class="h3 mb-2 text-gray-800">Registros de Miel A</h1>

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
                                    <option value="">Seleccione un periodo:</option>
                                    <?php
                                    try {
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

                    <!-- Mensaje informativo cuando no se seleccionan periodo ni fecha -->
                    <?php if (empty($periodoZafra) || empty($fechaIngreso)): ?>
                        <div class="alert alert-info">Por favor, seleccione un periodo de zafra y una fecha para ver los registros.</div>
                    <?php endif; ?>

                    <?php if (!empty($periodoZafra) && !empty($fechaIngreso)): ?>
                        <div class="mb-4">
                            <a href="<?= BASE_PATH ?>forms/registrarMielA.php?periodoZafra=<?php echo urlencode($periodoZafra); ?>&fechaIngreso=<?php echo urlencode($fechaIngreso); ?>" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Nuevo Registro
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Mostrar la tabla solo si se ha seleccionado el periodo de zafra y la fecha
                    if (!empty($periodoZafra) && !empty($fechaIngreso)) {
                        try {
                            // Obtener registros de Miel A que coincidan con Masa Cocida A en `periodoZafra`, `fechaIngreso`, y `num`
                            // Ordenando por el campo `num` de Masa Cocida A
                            $query = "SELECT mia.idMielA, mia.num, mia.hora, mia.brix AS brix_miel, mia.pol AS pol_miel, ma.brix AS brix_masa, ma.pol AS pol_masa, mia.observacion
                                      FROM miela mia
                                      LEFT JOIN masacocidaa ma ON mia.periodoZafra = ma.periodoZafra AND mia.fechaIngreso = ma.fechaIngreso AND mia.num = ma.num
                                      WHERE mia.periodoZafra = :periodoZafra AND mia.fechaIngreso = :fechaIngreso
                                      ORDER BY mia.num ASC";
                            $stmt = $conexion->prepare($query);
                            $stmt->bindParam(':periodoZafra', $periodoZafra);
                            $stmt->bindParam(':fechaIngreso', $fechaIngreso);
                            $stmt->execute();
                            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if ($result) {
                                $totalBrixMiel = $totalPolMiel = $totalPurMiel = $totalAgotam = $count = 0;
                                $totalPurMasa = 0; // Agregar esta línea para acumular el total de pureza de masa
                                $totalBrixMasa = $totalPolMasa = 0; // Agregar estas líneas para acumular los totales de brix y pol de masa

                                echo "<div class='card shadow mb-4'>";
                                echo "<div class='card-body'>";
                                echo "<div class='table-responsive'>";
                                echo "<table class='table table-bordered' id='tablaMielA' width='100%' cellspacing='0'>";
                                echo "<thead class='table-success'>
                                        <tr>
                                            <th>Num</th>
                                            <th>Hora</th>
                                            <th>Brix</th>
                                            <th>Pol</th>
                                            <th>Pureza Miel</th>
                                            <th>Agotam</th>
                                            <th>Observación</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>";
                                echo "<tbody>";

                                foreach ($result as $row) {
                                    $num = $row['num'];
                                    $hora = date('H:i', strtotime($row['hora']));
                                    $brixMiel = $row['brix_miel'];
                                    $polMiel = $row['pol_miel'];
                                    $brixMasa = $row['brix_masa'];
                                    $polMasa = $row['pol_masa'];
                                    $observacion = !empty($row['observacion']) ? $row['observacion'] : '-';


                                    // Cálculo de pureza de miel y agotamiento solo si hay datos de masa cocida
                                    $purMiel = ($brixMiel != 0) ? round(($polMiel * 100) / $brixMiel, 2) : 0;
                                    $purMasa = ($brixMasa != 0) ? round(($polMasa * 100) / $brixMasa, 2) : 0;
                                    $agotam = isset($purMasa) ? $purMasa - $purMiel : 0;

                                    // Acumular valores para promedios solo si hay datos de masa cocida
                                    if (isset($purMasa)) {
                                        $totalBrixMiel += $brixMiel;
                                        $totalPolMiel += $polMiel;
                                        $totalPurMiel += $purMiel;
                                        $totalAgotam += $agotam;
                                        $totalPurMasa += $purMasa; // Acumular el total de pureza de masa
                                        $totalBrixMasa += $brixMasa; // Acumular el total de brix de masa
                                        $totalPolMasa += $polMasa; // Acumular el total de pol de masa
                                        $count++;
                                    }

                                    echo "<tr>
                                            <td>" . htmlspecialchars($num) . "</td>
                                            <td>" . htmlspecialchars($hora) . "</td>
                                            <td>" . formatear_valor($brixMiel) . "</td>
                                            <td>" . formatear_valor($polMiel) . "</td>
                                            <td>" . formatear_valor($purMiel) . "</td>
                                            <td>" . formatear_valor($agotam) . "</td>
                                            <td class='observacion'>" . htmlspecialchars($observacion) . "</td>
                                            <td>
                                                <a href='" . BASE_PATH . "controllers/mielA/editarMielA.php?id=" . urlencode($row['idMielA']) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "' class='text-warning me-2 action-icons'>
                                                    <i class='fas fa-edit'></i>
                                                </a>
                                                <a href='" . BASE_PATH . "controllers/mielA/eliminarMielA.php?id=" . urlencode($row['idMielA']) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "' 
                                                    class='text-danger action-icons' 
                                                    onclick=\"return confirm('¿Estás seguro de eliminar este registro?')\">
                                                    <i class='fas fa-trash'></i>
                                                </a>
                                            </td>
                                        </tr>";
                                }

                                // Calcular promedios y aplicar redondeo
                                $promBrixMiel = $count ? round($totalBrixMiel / $count, 2) : 0;
                                $promPolMiel = $count ? round($totalPolMiel / $count, 2) : 0;
                                $promPurMiel = ($totalBrixMiel != 0) ? round(($totalPolMiel * 100) / $totalBrixMiel, 2) : 0; // Calcular el promedio de pureza de miel basado en los totales

                                $promBrixMasa = $count ? round($totalBrixMasa / $count, 2) : 0;
                                $promPolMasa = $count ? round($totalPolMasa / $count, 2) : 0;
                                $promPurMasa = ($promBrixMasa != 0) ? round(($promPolMasa * 100) / $promBrixMasa, 2) : 0; // Calcular el promedio de pureza de masa basado en los promedios

                                $totalAgotam = $promPurMasa - $promPurMiel;

                                // Mostrar fila de promedios
                                echo "<tr class='table-info'>
                                        <td>Promedio</td>
                                        <td></td>
                                        <td>" . formatear_valor($promBrixMiel) . "</td>
                                        <td>" . formatear_valor($promPolMiel) . "</td>
                                        <td>" . formatear_valor($promPurMiel) . "</td>
                                        <td>" . formatear_valor($totalAgotam) . "</td>
                                        <td class='observacion'></td>
                                        <td></td>
                                    </tr>";

                                echo "</tbody>";
                                echo "</table>";
                                echo "</div>";
                            } else {
                                echo "<div class='alert alert-info'>No se encontraron registros para el periodo de zafra y la fecha seleccionados.</div>";
                            }
                        } catch (PDOException $e) {
                            echo "<div class='alert alert-danger'>Error al cargar los registros: " . $e->getMessage() . "</div>";
                        }
                    }
                    ?>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <script src="<?= BASE_PATH; ?>public/vendor/jquery/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/js/sb-admin-2.min.js"></script>

    <!-- Inicialización de DataTables -->
    <script>
        $(document).ready(function() {
            $('#tablaMielA').DataTable({
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
        document.getElementById('periodoZafra').addEventListener('change', function() {
            var fechaIngreso = document.getElementById('fechaIngreso');
            fechaIngreso.value = '';
            if (this.value === '') {
                fechaIngreso.disabled = true;
            } else {
                fechaIngreso.disabled = false;
            }
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