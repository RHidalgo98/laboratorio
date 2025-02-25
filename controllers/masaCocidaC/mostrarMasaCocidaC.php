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

// Función para calcular el promedio de un array de valores
function calcular_promedio($valores)
{
    // Filtrar valores nulos o vacíos
    $valores_filtrados = array_filter($valores, function ($valor) {
        return $valor !== null && $valor !== '';
    });

    // Contar el número de elementos válidos
    $conteo = count($valores_filtrados);

    // Si no hay elementos válidos, devolver 0
    if ($conteo === 0) {
        return 0;
    }

    // Calcular el promedio y redondear a 2 decimales
    $suma = array_sum($valores_filtrados);
    $promedio = $suma / $conteo;
    return round($promedio, 2);
}

// Cálculo de Tn Masa según la fórmula proporcionada
function calcular_tn_masa($vol_ft3, $tn_m3)
{
    if ($vol_ft3 == "" || $tn_m3 == "") {
        return ""; // Retorna vacío si vol_ft3 o tn_m3 está vacío
    }
    return round(0.3048 ** 3 * $vol_ft3 * $tn_m3, 2);
}

// Cálculo de Tn/m³ según la fórmula proporcionada
function calcular_tn_m3($brix)
{
    if ($brix == "") {
        return ""; // Retorna vacío si brix está vacío
    }
    return round(1 + 0.003865 * $brix + 0.000012912 * pow($brix, 2) + 0.0000000643323 * pow($brix, 3) - 0.00000000024661 * pow($brix, 4), 5);
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
    <title>Mostrar Masa Cocida C</title>

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
                    <h1 class="h3 mb-2 text-gray-800">Registros de Masa Cocida C</h1>

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

                    <?php
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

                    $promRendCristal = 0; // Inicializar variable
                    $promPurMiel = 0; // Inicializar variable

                    if (!empty($periodoZafra) && !empty($fechaIngreso)) {
                        try {
                            // Calcular suma de Pol y Brix para Masa
                            $queryMasa = "SELECT SUM(pol) AS sumPolMasa, SUM(brix) AS sumBrixMasa FROM masacocidac 
                                          WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso AND brix > 0";
                            $stmtMasa = $conexion->prepare($queryMasa);
                            $stmtMasa->bindParam(':periodoZafra', $periodoZafra);
                            $stmtMasa->bindParam(':fechaIngreso', $fechaIngreso);
                            $stmtMasa->execute();
                            $resultMasa = $stmtMasa->fetch(PDO::FETCH_ASSOC);
                            $sumPolMasa = $resultMasa['sumPolMasa'] ?: 0;
                            $sumBrixMasa = $resultMasa['sumBrixMasa'] ?: 0;
                            $promPurMasa = ($sumBrixMasa != 0) ? round(($sumPolMasa * 100) / $sumBrixMasa, 2) : 0;

                            // Calcular suma de Pol y Brix para Miel
                            $queryMiel = "SELECT SUM(pol) AS sumPolMiel, SUM(brix) AS sumBrixMiel, COUNT(*) AS countMiel FROM mielfinal 
                                          WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso AND brix > 0";
                            $stmtMiel = $conexion->prepare($queryMiel);
                            $stmtMiel->bindParam(':periodoZafra', $periodoZafra);
                            $stmtMiel->bindParam(':fechaIngreso', $fechaIngreso);
                            $stmtMiel->execute();
                            $resultMiel = $stmtMiel->fetch(PDO::FETCH_ASSOC);
                            $sumPolMiel = $resultMiel['sumPolMiel'] ?: 0;
                            $sumBrixMiel = $resultMiel['sumBrixMiel'] ?: 0;
                            $countMiel = $resultMiel['countMiel'] ?: 0;
                            $promBrixMiel = $countMiel ? round($sumBrixMiel / $countMiel, 2) : 0;
                            $promPolMiel = $countMiel ? round($sumPolMiel / $countMiel, 2) : 0;
                            $promPurMiel = ($promBrixMiel != 0) ? round(($promPolMiel * 100) / $promBrixMiel, 2) : 0;

                            // Calcular promedio de Rendimiento Cristal combinando valores de masa y miel
                            $promRendCristal = ($promPurMasa != "" && $promPurMiel != "") ? round((($promPurMasa - $promPurMiel) / (100 - $promPurMiel)) * 100, 2) : 0;
                        } catch (PDOException $e) {
                            echo "<div class='alert alert-danger'>Error al calcular promedios: " . $e->getMessage() . "</div>";
                        }
                    }
                    ?>

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
                            <a href="<?= BASE_PATH ?>forms/registrarMasaCocidaC.php?periodoZafra=<?= urlencode($periodoZafra); ?>&fechaIngreso=<?= urlencode($fechaIngreso); ?>" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Nuevo Registro
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Mostrar registros si se ha seleccionado periodo de zafra y fecha de ingreso
                    if (!empty($periodoZafra) && !empty($fechaIngreso)) {
                        // Consulta para obtener los registros que ya existen en la base de datos para el periodo y fecha seleccionados
                        $query = "SELECT mc.idMasaCocidaC, mc.num, mc.hora, mc.tacho, mc.volFt3, mc.brix, mc.pol, mc.periodoZafra, mc.fechaIngreso, 
                                         mic.brix AS brix_miel, mic.pol AS pol_miel, mc.observacion
                                  FROM masacocidac mc
                                  LEFT JOIN mielfinal mic ON mc.periodoZafra = mic.periodoZafra AND mc.fechaIngreso = mic.fechaIngreso AND mc.num = mic.num
                                  WHERE mc.periodoZafra = :periodoZafra AND mc.fechaIngreso = :fechaIngreso
                                  ORDER BY mc.num ASC";
                        $stmt = $conexion->prepare($query);
                        $stmt->bindParam(':periodoZafra', $periodoZafra);
                        $stmt->bindParam(':fechaIngreso', $fechaIngreso);
                        $stmt->execute();
                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if ($result) {
                            $contadorTacho = count($result); // Contar el número total de registros de tacho
                            $totalVolFt3 = 0; // Inicializar suma total de volFt3
                            $totalTnMasa = 0; // Inicializar suma total de Tn Masa
                            $purMasaValores = []; // Inicializar array de purMasaValores
                            $purMielValores = []; // Inicializar array de purMielValores

                            echo "<div class='card shadow mb-4'>";
                            echo "<div class='card-body'>";
                            echo "<div class='table-responsive'>";
                            echo "<table class='table table-bordered' id='tablaMasaCocidaC' width='100%' cellspacing='0'>";
                            echo "<thead class='table-success'>
                                    <tr>
                                        <th>Num</th>
                                        <th>Hora</th>
                                        <th>Tacho</th>
                                        <th>Cristaliz</th>
                                        <th>Vol ft³</th>
                                        <th>Brix</th>
                                        <th>Pol</th>
                                        <th>Pureza Masa</th>
                                        <th>Rend Cristal</th>
                                        <th>Tn Masa</th>
                                        <th>Tn/m³</th>
                                        <th>Observación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>";
                            echo "<tbody>";

                            // Recorrer los registros y mostrarlos en la tabla
                            foreach ($result as $row) {
                                $num = $row['num'];
                                $hora = date('H:i', strtotime($row['hora']));
                                $tacho = $row['tacho'];
                                $volFt3 = $row['volFt3'];
                                $brix = $row['brix'];
                                $pol = $row['pol'];
                                $brixMiel = $row['brix_miel'];
                                $polMiel = $row['pol_miel'];
                                $observacion = !empty($row['observacion']) ? $row['observacion'] : '-';
                                $idMasaCocidaC = $row['idMasaCocidaC'];

                                // Sumar volFt3 para el cálculo total
                                $totalVolFt3 += $volFt3;

                                // Calcular valores específicos
                                $purMasa = ($brix != 0) ? round(($pol * 100) / $brix, 2) : 0;
                                $purMiel = ($brixMiel != 0) ? round(($polMiel * 100) / $brixMiel, 2) : 0;
                                $rendCristal = ($purMasa != "" && $purMiel != "") ? round((($purMasa - $purMiel) / (100 - $purMiel)) * 100, 2) : 0;

                                // Calcular Tn/m³ usando la fórmula en base al valor de Brix
                                $tnM3 = calcular_tn_m3($brix);

                                // Calcular Tn Masa utilizando Vol Ft³ y Tn/m³
                                $tnMasa = calcular_tn_masa($volFt3, $tnM3);
                                $totalTnMasa += $tnMasa; // Sumar Tn Masa para el cálculo total

                                $cristaliz = ($tacho == 10) ? 7 : "-";

                                echo "<tr>
                                        <td>" . htmlspecialchars($num) . "</td>
                                        <td>" . htmlspecialchars($hora) . "</td>
                                        <td>" . htmlspecialchars($tacho) . "</td>
                                        <td>" . htmlspecialchars($cristaliz) . "</td>
                                        <td>" . formatear_valor($volFt3, 0) . "</td>
                                        <td>" . formatear_valor($brix, 2) . "</td>
                                        <td>" . formatear_valor($pol, 2) . "</td>
                                        <td>" . formatear_valor($purMasa, 2) . "</td>
                                        <td>" . formatear_valor($rendCristal, 2) . "</td>
                                        <td>" . formatear_valor($tnMasa, 3) . "</td>
                                        <td>" . formatear_valor($tnM3, 5) . "</td>
                                        <td class='observacion'>" . htmlspecialchars($observacion) . "</td>
                                        <td>
                                            <a href='" . BASE_PATH . "controllers/masaCocidaC/editarMasaCocidaC.php?id=" . urlencode($idMasaCocidaC) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "' class='text-warning me-2 action-icons'>
                                                <i class='fas fa-edit'></i>
                                            </a>
                                            <a href='" . BASE_PATH . "controllers/masaCocidaC/eliminarMasaCocidaC.php?id=" . urlencode($idMasaCocidaC) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso) . "' 
                                            class='text-danger action-icons' 
                                            onclick=\"return confirm('¿Estás seguro de eliminar este registro?')\">
                                                <i class='fas fa-trash'></i>
                                            </a>
                                        </td>
                                    </tr>";
                            }

                            // Calcular promedios
                            $promBrix = calcular_promedio(array_column($result, 'brix'));
                            $promPol = calcular_promedio(array_column($result, 'pol'));
                            $promTnM3 = ($totalVolFt3 > 0) ? round($totalTnMasa / ($totalVolFt3 * pow(0.3048, 3)), 5) : 0; // Calcular promedio de Tn/m³
                            $promBrixMiel = calcular_promedio(array_column($result, 'brix_miel'));
                            $promPolMiel = calcular_promedio(array_column($result, 'pol_miel'));
                            $promPurMiel = ($promBrixMiel != 0) ? round(($promPolMiel * 100) / $promBrixMiel, 2) : 0;

                            // Mostrar fila de promedios
                            echo "<tr class='table-info'>
                                    <td>Promedio</td>
                                    <td></td>
                                    <td>" . htmlspecialchars($contadorTacho) . "</td>
                                    <td></td>
                                    <td>" . formatear_valor($totalVolFt3, 0) . "</td>
                                    <td>" . formatear_valor($promBrix, 2) . "</td>
                                    <td>" . formatear_valor($promPol, 2) . "</td>
                                    <td>" . formatear_valor($promPurMasa, 2) . "</td>
                                    <td>" . formatear_valor($promRendCristal, 2) . "</td>
                                    <td>" . formatear_valor($totalTnMasa, 3) . "</td>
                                    <td>" . formatear_valor($promTnM3, 5) . "</td>
                                    <td class='observacion'></td>
                                    <td></td>
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
            $('#tablaMasaCocidaC').DataTable({
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

        // Validación del formulario antes de enviar
        document.querySelector('form').addEventListener('submit', function(event) {
            var periodoZafra = document.getElementById('periodoZafra').value;
            var fechaIngreso = document.getElementById('fechaIngreso').value;
            if (periodoZafra === '' || fechaIngreso === '') {
                event.preventDefault();
                alert('Todos los campos son obligatorios.');
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