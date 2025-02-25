<?php
include $_SERVER['DOCUMENT_ROOT'] . '/AnalisisLaboratorio/config/config.php';
include ROOT_PATH . 'config/conexion.php';

session_start();

// Verificar autenticación
if (!isset($_SESSION['idUsuario'])) {
    header("Location: " . BASE_PATH . "controllers/login.php");
    exit();
}

// Activa el reporte de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

$resultadosPrimario = []; // Resultados para Jugo Primario
$resultadosMezclado = []; // Resultados para Jugo Mezclado
$resultadosResidual = []; // Resultados para Jugo Residual
$resultadosFiltrado = []; // Resultados para Jugo Filtrado
$resultadosClarificado = []; // Resultados para Jugo Clarificado
$resultadosMeladura = []; // Resultados para Meladura
$resultadosMasaCocidaA = []; // Resultados para Masa Cocida A
$resultadosMasaCocidaB = []; // Resultados para Masa Cocida B
$resultadosMasaCocidaC = []; // Resultados para Masa Cocida C
$promediosMielA = []; // Promedios para Miel A
$promPurMielA = 0; // Initialize $promPurMiel
$promPurMielB = 0; // Initialize $promPurMiel
$promPurMielFinal = 0; // Initialize $promPurMiel
$promPurMasaA = 0; // Initialize $promPurMasa
$promPurMasaB = 0; // Initialize $promPurMasa
$promPurMasaC = 0; // Initialize $promPurMasa
$totalVolFt3A = 0; // Inicializar suma total de volFt3
$totalVolFt3B = 0; // Inicializar suma total de volFt3
$totalVolFt3C = 0; // Inicializar suma total de volFt3
$totalTnMasaA = 0; // Inicializar suma total de Tn Masa
$totalTnMasaB = 0; // Inicializar suma total de Tn Masa
$totalTnMasaC = 0; // Inicializar suma total de Tn Masa
$promediosMielA = []; // Promedios para Miel A
$promediosMielB = []; // Promedios para Miel B
$promediosMielFinal = []; // Promedios para Miel Final
$promediosAguaImbibicion = []; // Promedios para Agua de Imbibición
$promediosJugoMezcladoPHMBC = []; // Promedios para Jugo Mezclado PH MBC
$promediosBagazo = []; // Promedios para Bagazo
$promediosFiltros = []; // Promedios para Filtros
$promediosCachaza = []; // Promedios para Cachaza
$promediosCausas = []; // Promedios para Causas

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

if (!empty($periodoZafra) && !empty($fechaIngreso)) {
    try {
        // Consulta para Jugo Primario
        $queryJugoPrimario = "SELECT 
                    SUM(brix) AS sumaBrix, 
                    SUM(sac) AS sumaSac, 
                    SUM(mlGastado) AS sumaMlGastado, 
                    COUNT(NULLIF(brix, 0)) AS countBrix,
                    COUNT(NULLIF(sac, 0)) AS countSac,
                    COUNT(NULLIF(mlGastado, 0)) AS countMlGastado,
                    SUM(CASE WHEN mlGastado > 0 THEN 12.5 / mlGastado ELSE 0 END) AS sumaAzucarRed,
                    COUNT(CASE WHEN mlGastado > 0 THEN 1 END) AS countAzucarRed
                  FROM jugoprimario
                  WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
        $stmtJugoPrimario = $conexion->prepare($queryJugoPrimario);
        $stmtJugoPrimario->bindParam(':periodoZafra', $periodoZafra);
        $stmtJugoPrimario->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtJugoPrimario->execute();
        $resultadosPrimario = $stmtJugoPrimario->fetch(PDO::FETCH_ASSOC);

        // Asignación de resultados
        $sumaBrix = !empty($resultadosPrimario['sumaBrix']) ? $resultadosPrimario['sumaBrix'] : 0;
        $sumaSac = !empty($resultadosPrimario['sumaSac']) ? $resultadosPrimario['sumaSac'] : 0;
        $sumaMlGastado = !empty($resultadosPrimario['sumaMlGastado']) ? $resultadosPrimario['sumaMlGastado'] : 0;
        $countBrix = !empty($resultadosPrimario['countBrix']) ? $resultadosPrimario['countBrix'] : 1;
        $countSac = !empty($resultadosPrimario['countSac']) ? $resultadosPrimario['countSac'] : 1;
        $countMlGastado = !empty($resultadosPrimario['countMlGastado']) ? $resultadosPrimario['countMlGastado'] : 1;
        $sumaAzucarRed = !empty($resultadosPrimario['sumaAzucarRed']) ? $resultadosPrimario['sumaAzucarRed'] : 0;
        $countAzucarRed = !empty($resultadosPrimario['countAzucarRed']) ? $resultadosPrimario['countAzucarRed'] : 0;

        // Cálculo de promedios
        $avgBrixJP = ($countBrix > 0) ? round($sumaBrix / $countBrix, 2) : 0;
        $avgSacJP = ($countSac > 0) ? round($sumaSac / $countSac, 2) : 0;
        $avgMlGastadoJP = ($countMlGastado > 0) ? round($sumaMlGastado / $countMlGastado, 2) : 0;
        $promPurJP = ($avgBrixJP != 0) ? round(($avgSacJP * 100) / $avgBrixJP, 2) : 0;
        $promAzucarRedJP = ($countAzucarRed > 0) ? round($sumaAzucarRed / $countAzucarRed, 2) : 0;

        // Consulta para Jugo Mezclado
        $queryJugoMezclado = "SELECT 
                SUM(brix) AS sumaBrix, 
                SUM(sac) AS sumaSac, 
                SUM(mlGastado) AS sumaMlGastado, 
                COUNT(NULLIF(brix, 0)) AS countBrix,
                COUNT(NULLIF(sac, 0)) AS countSac,
                COUNT(NULLIF(mlGastado, 0)) AS countMlGastado,
                SUM(CASE WHEN mlGastado > 0 THEN 12.5 / mlGastado ELSE 0 END) AS sumaAzucarRed,
                COUNT(CASE WHEN mlGastado > 0 THEN 1 END) AS countAzucarRed
              FROM jugomezclado
              WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
        $stmtJugoMezclado = $conexion->prepare($queryJugoMezclado);
        $stmtJugoMezclado->bindParam(':periodoZafra', $periodoZafra);
        $stmtJugoMezclado->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtJugoMezclado->execute();
        $resultadosMezclado = $stmtJugoMezclado->fetch(PDO::FETCH_ASSOC);

        // Asignación de resultados
        $sumaBrixJM = !empty($resultadosMezclado['sumaBrix']) ? $resultadosMezclado['sumaBrix'] : 0;
        $sumaSacJM = !empty($resultadosMezclado['sumaSac']) ? $resultadosMezclado['sumaSac'] : 0;
        $sumaMlGastadoJM = !empty($resultadosMezclado['sumaMlGastado']) ? $resultadosMezclado['sumaMlGastado'] : 0;
        $countBrixJM = !empty($resultadosMezclado['countBrix']) ? $resultadosMezclado['countBrix'] : 1;
        $countSacJM = !empty($resultadosMezclado['countSac']) ? $resultadosMezclado['countSac'] : 1;
        $countMlGastadoJM = !empty($resultadosMezclado['countMlGastado']) ? $resultadosMezclado['countMlGastado'] : 1;
        $sumaAzucarRedJM = !empty($resultadosMezclado['sumaAzucarRed']) ? $resultadosMezclado['sumaAzucarRed'] : 0;
        $countAzucarRedJM = !empty($resultadosMezclado['countAzucarRed']) ? $resultadosMezclado['countAzucarRed'] : 0;

        // Cálculo de promedios
        $avgBrixJM = ($countBrixJM > 0) ? round($sumaBrixJM / $countBrixJM, 2) : 0;
        $avgSacJM = ($countSacJM > 0) ? round($sumaSacJM / $countSacJM, 2) : 0;
        $avgMlGastadoJM = ($countMlGastadoJM > 0) ? round($sumaMlGastadoJM / $countMlGastadoJM, 2) : 0;
        $promPurJM = ($avgBrixJM != 0) ? round(($avgSacJM * 100) / $avgBrixJM, 2) : 0;
        $promAzucarRedJM = ($countAzucarRedJM > 0) ? round($sumaAzucarRedJM / $countAzucarRedJM, 2) : 0;

        // Consulta para Jugo Residual
        $queryJugoResidual = "SELECT 
                SUM(brix) AS sumaBrix, 
                SUM(sac) AS sumaSac, 
                SUM(mlGastado) AS sumaMlGastado, 
                COUNT(NULLIF(brix, 0)) AS countBrix,
                COUNT(NULLIF(sac, 0)) AS countSac,
                COUNT(NULLIF(mlGastado, 0)) AS countMlGastado,
                SUM(CASE WHEN mlGastado > 0 THEN 12.5 / mlGastado ELSE 0 END) AS sumaAzucarRed,
                COUNT(CASE WHEN mlGastado > 0 THEN 1 END) AS countAzucarRed
              FROM jugoresidual
              WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
        $stmtJugoResidual = $conexion->prepare($queryJugoResidual);
        $stmtJugoResidual->bindParam(':periodoZafra', $periodoZafra);
        $stmtJugoResidual->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtJugoResidual->execute();
        $resultadosResidual = $stmtJugoResidual->fetch(PDO::FETCH_ASSOC);

        // Asignación de resultados
        $sumaBrixJR = !empty($resultadosResidual['sumaBrix']) ? $resultadosResidual['sumaBrix'] : 0;
        $sumaSacJR = !empty($resultadosResidual['sumaSac']) ? $resultadosResidual['sumaSac'] : 0;
        $sumaMlGastadoJR = !empty($resultadosResidual['sumaMlGastado']) ? $resultadosResidual['sumaMlGastado'] : 0;
        $countBrixJR = !empty($resultadosResidual['countBrix']) ? $resultadosResidual['countBrix'] : 1;
        $countSacJR = !empty($resultadosResidual['countSac']) ? $resultadosResidual['countSac'] : 1;
        $countMlGastadoJR = !empty($resultadosResidual['countMlGastado']) ? $resultadosResidual['countMlGastado'] : 1;
        $sumaAzucarRedJR = !empty($resultadosResidual['sumaAzucarRed']) ? $resultadosResidual['sumaAzucarRed'] : 0;
        $countAzucarRedJR = !empty($resultadosResidual['countAzucarRed']) ? $resultadosResidual['countAzucarRed'] : 0;

        // Cálculo de promedios
        $avgBrixJR = ($countBrixJR > 0) ? round($sumaBrixJR / $countBrixJR, 2) : 0;
        $avgSacJR = ($countSacJR > 0) ? round($sumaSacJR / $countSacJR, 2) : 0;
        $avgMlGastadoJR = ($countMlGastadoJR > 0) ? round($sumaMlGastadoJR / $countMlGastadoJR, 2) : 0;
        $promPurJR = ($avgBrixJR != 0) ? round(($avgSacJR * 100) / $avgBrixJR, 2) : 0;
        $promAzucarRedJR = ($countAzucarRedJR > 0) ? round($sumaAzucarRedJR / $countAzucarRedJR, 2) : 0;

        // Consulta para Jugo Clarificado
        $queryJugoClarificado = "SELECT 
                SUM(brix) AS sumaBrix, 
                SUM(sac) AS sumaSac, 
                SUM(mlGastado) AS sumaMlGastado, 
                COUNT(NULLIF(brix, 0)) AS countBrix,
                COUNT(NULLIF(sac, 0)) AS countSac,
                COUNT(NULLIF(mlGastado, 0)) AS countMlGastado,
                SUM(CASE WHEN mlGastado > 0 THEN 12.5 / mlGastado ELSE 0 END) AS sumaAzucarRed,
                COUNT(CASE WHEN mlGastado > 0 THEN 1 END) AS countAzucarRed
              FROM jugoclarificado
              WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
        $stmtJugoClarificado = $conexion->prepare($queryJugoClarificado);
        $stmtJugoClarificado->bindParam(':periodoZafra', $periodoZafra);
        $stmtJugoClarificado->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtJugoClarificado->execute();
        $resultadosClarificado = $stmtJugoClarificado->fetch(PDO::FETCH_ASSOC);

        // Asignación de resultados
        $sumaBrixJC = !empty($resultadosClarificado['sumaBrix']) ? $resultadosClarificado['sumaBrix'] : 0;
        $sumaSacJC = !empty($resultadosClarificado['sumaSac']) ? $resultadosClarificado['sumaSac'] : 0;
        $sumaMlGastadoJC = !empty($resultadosClarificado['sumaMlGastado']) ? $resultadosClarificado['sumaMlGastado'] : 0;
        $countBrixJC = !empty($resultadosClarificado['countBrix']) ? $resultadosClarificado['countBrix'] : 1;
        $countSacJC = !empty($resultadosClarificado['countSac']) ? $resultadosClarificado['countSac'] : 1;
        $countMlGastadoJC = !empty($resultadosClarificado['countMlGastado']) ? $resultadosClarificado['countMlGastado'] : 1;
        $sumaAzucarRedJC = !empty($resultadosClarificado['sumaAzucarRed']) ? $resultadosClarificado['sumaAzucarRed'] : 0;
        $countAzucarRedJC = !empty($resultadosClarificado['countAzucarRed']) ? $resultadosClarificado['countAzucarRed'] : 0;

        // Cálculo de promedios
        $avgBrixJC = ($countBrixJC > 0) ? round($sumaBrixJC / $countBrixJC, 2) : 0;
        $avgSacJC = ($countSacJC > 0) ? round($sumaSacJC / $countSacJC, 2) : 0;
        $avgMlGastadoJC = ($countMlGastadoJC > 0) ? round($sumaMlGastadoJC / $countMlGastadoJC, 2) : 0;
        $promPurJC = ($avgBrixJC != 0) ? round(($avgSacJC * 100) / $avgBrixJC, 2) : 0;
        $promAzucarRedJC = ($countAzucarRedJC > 0) ? round($sumaAzucarRedJC / $countAzucarRedJC, 2) : 0;

        // Consulta para Jugo Filtrado
        $queryJugoFiltrado = "SELECT 
                SUM(brix) AS sumaBrix, 
                SUM(sac) AS sumaSac, 
                SUM(mlGastado) AS sumaMlGastado, 
                COUNT(NULLIF(brix, 0)) AS countBrix,
                COUNT(NULLIF(sac, 0)) AS countSac,
                COUNT(NULLIF(mlGastado, 0)) AS countMlGastado,
                SUM(CASE WHEN mlGastado > 0 THEN 12.5 / mlGastado ELSE 0 END) AS sumaAzucarRed,
                COUNT(CASE WHEN mlGastado > 0 THEN 1 END) AS countAzucarRed
              FROM jugofiltrado
              WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
        $stmtJugoFiltrado = $conexion->prepare($queryJugoFiltrado);
        $stmtJugoFiltrado->bindParam(':periodoZafra', $periodoZafra);
        $stmtJugoFiltrado->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtJugoFiltrado->execute();
        $resultadosFiltrado = $stmtJugoFiltrado->fetch(PDO::FETCH_ASSOC);

        // Asignación de resultados
        $sumaBrixJF = !empty($resultadosFiltrado['sumaBrix']) ? $resultadosFiltrado['sumaBrix'] : 0;
        $sumaSacJF = !empty($resultadosFiltrado['sumaSac']) ? $resultadosFiltrado['sumaSac'] : 0;
        $sumaMlGastadoJF = !empty($resultadosFiltrado['sumaMlGastado']) ? $resultadosFiltrado['sumaMlGastado'] : 0;
        $countBrixJF = !empty($resultadosFiltrado['countBrix']) ? $resultadosFiltrado['countBrix'] : 1;
        $countSacJF = !empty($resultadosFiltrado['countSac']) ? $resultadosFiltrado['countSac'] : 1;
        $countMlGastadoJF = !empty($resultadosFiltrado['countMlGastado']) ? $resultadosFiltrado['countMlGastado'] : 1;
        $sumaAzucarRedJF = !empty($resultadosFiltrado['sumaAzucarRed']) ? $resultadosFiltrado['sumaAzucarRed'] : 0;
        $countAzucarRedJF = !empty($resultadosFiltrado['countAzucarRed']) ? $resultadosFiltrado['countAzucarRed'] : 0;

        // Cálculo de promedios
        $avgBrixJF = ($countBrixJF > 0) ? round($sumaBrixJF / $countBrixJF, 2) : 0;
        $avgSacJF = ($countSacJF > 0) ? round($sumaSacJF / $countSacJF, 2) : 0;
        $avgMlGastadoJF = ($countMlGastadoJF > 0) ? round($sumaMlGastadoJF / $countMlGastadoJF, 2) : 0;
        $promPurJF = ($avgBrixJF != 0) ? round(($avgSacJF * 100) / $avgBrixJF, 2) : 0;
        $promAzucarRedJF = ($countAzucarRedJF > 0) ? round($sumaAzucarRedJF / $countAzucarRedJF, 2) : 0;

        // Consulta para Meladura
        $queryMeladura = "SELECT 
            SUM(brix) AS sumaBrix, 
            SUM(sac) AS sumaSac, 
            SUM(mlGastado) AS sumaMlGastado, 
            COUNT(NULLIF(brix, 0)) AS countBrix,
            COUNT(NULLIF(sac, 0)) AS countSac,
            COUNT(NULLIF(mlGastado, 0)) AS countMlGastado,
            SUM(CASE WHEN mlGastado > 0 THEN 100 / mlGastado ELSE 0 END) AS sumaAzucarRed,
            COUNT(CASE WHEN mlGastado > 0 THEN 1 END) AS countAzucarRed
              FROM meladura
              WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
        $stmtMeladura = $conexion->prepare($queryMeladura);
        $stmtMeladura->bindParam(':periodoZafra', $periodoZafra);
        $stmtMeladura->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtMeladura->execute();
        $resultadosMeladura = $stmtMeladura->fetch(PDO::FETCH_ASSOC);

        // Asignación de resultados
        $sumaBrixMel = !empty($resultadosMeladura['sumaBrix']) ? $resultadosMeladura['sumaBrix'] : 0;
        $sumaSacMel = !empty($resultadosMeladura['sumaSac']) ? $resultadosMeladura['sumaSac'] : 0;
        $sumaMlGastadoMel = !empty($resultadosMeladura['sumaMlGastado']) ? $resultadosMeladura['sumaMlGastado'] : 0;
        $countBrixMel = !empty($resultadosMeladura['countBrix']) ? $resultadosMeladura['countBrix'] : 1;
        $countSacMel = !empty($resultadosMeladura['countSac']) ? $resultadosMeladura['countSac'] : 1;
        $countMlGastadoMel = !empty($resultadosMeladura['countMlGastado']) ? $resultadosMeladura['countMlGastado'] : 1;
        $sumaAzucarRedMel = !empty($resultadosMeladura['sumaAzucarRed']) ? $resultadosMeladura['sumaAzucarRed'] : 0;
        $countAzucarRedMel = !empty($resultadosMeladura['countAzucarRed']) ? $resultadosMeladura['countAzucarRed'] : 0;

        // Cálculo de promedios
        $avgBrixMel = ($countBrixMel > 0) ? round($sumaBrixMel / $countBrixMel, 2) : 0;
        $avgSacMel = ($countSacMel > 0) ? round($sumaSacMel / $countSacMel, 2) : 0;
        $avgMlGastadoMel = ($countMlGastadoMel > 0) ? round($sumaMlGastadoMel / $countMlGastadoMel, 2) : 0;
        $promPurMel = ($avgBrixMel != 0) ? round(($avgSacMel * 100) / $avgBrixMel, 2) : 0;
        $promAzucarRedMel = ($countAzucarRedMel > 0) ? round($sumaAzucarRedMel / $countAzucarRedMel, 2) : 0;

        // Función para calcular Tn/m³
        function calcular_tn_m3($brix)
        {
            if ($brix == "") {
                return 0; // Retorna 0 si Brix está vacío
            }
            return round(1 + 0.003865 * $brix + 0.000012912 * pow($brix, 2) + 0.0000000643323 * pow($brix, 3) - 0.00000000024661 * pow($brix, 4), 5);
        }

        // Función para calcular Tn Masa
        function calcular_tn_masa($vol_ft3, $tn_m3)
        {
            if ($vol_ft3 == "" || $tn_m3 == "") {
                return 0; // Retorna 0 si vol_ft3 o tn_m3 está vacío
            }
            return round(pow(0.3048, 3) * $vol_ft3 * $tn_m3, 2);
        }

        // Variables para acumular totales
        $totalTnMasaA = 0;
        $totalVolFt3A = 0;

        // Procesar Masa Cocida A
        $queryMasaCocidaA = "SELECT 
    ROUND(AVG(brix), 2) AS promedio_brix, 
    ROUND(AVG(pol), 2) AS promedio_pol, 
    ROUND(AVG(CASE WHEN brix > 0 THEN (pol * 100) / brix ELSE NULL END), 2) AS promedio_pureza, 
    ROUND(SUM(volFt3), 2) AS total_vol_ft3 
    FROM masacocidaa
    WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
        $stmtMasaCocidaA = $conexion->prepare($queryMasaCocidaA);
        $stmtMasaCocidaA->bindParam(':periodoZafra', $periodoZafra);
        $stmtMasaCocidaA->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtMasaCocidaA->execute();

        $resultadosMasaCocidaA = $stmtMasaCocidaA->fetch(PDO::FETCH_ASSOC);

        $brixA = $resultadosMasaCocidaA['promedio_brix'] ?? 0;
        $volFt3A = $resultadosMasaCocidaA['total_vol_ft3'] ?? 0;

        // Calcular Tn/m³ para Masa A
        $tnM3A = calcular_tn_m3($brixA);

        // Calcular Tn Masa para Masa A
        $tnMasaA = calcular_tn_masa($volFt3A, $tnM3A);

        // Acumular totales
        $totalTnMasaA += $tnMasaA;
        $totalVolFt3A += $volFt3A;

        // Calcular promedio de Tn/m³ para Masa A
        $promTnM3A = ($totalVolFt3A > 0) ? round($totalTnMasaA / ($totalVolFt3A * pow(0.3048, 3)), 5) : 0;

        // Calcular pureza
        $purezaA = ($brixA > 0) ? round(($resultadosMasaCocidaA['promedio_pol'] * 100) / $brixA, 2) : 0;

        $queryMasaA = "SELECT SUM(pol) AS sumPolMasaA, SUM(brix) AS sumBrixMasaA FROM masacocidaa 
            WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso AND brix > 0";
        $stmtMasaA = $conexion->prepare($queryMasaA);
        $stmtMasaA->bindParam(':periodoZafra', $periodoZafra);
        $stmtMasaA->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtMasaA->execute();
        $resultMasaA = $stmtMasaA->fetch(PDO::FETCH_ASSOC);
        $sumPolMasaA = $resultMasaA['sumPolMasaA'] ?: 0;
        $sumBrixMasaA = $resultMasaA['sumBrixMasaA'] ?: 0;
        $promPurMasaA = ($sumBrixMasaA != 0) ? round(($sumPolMasaA * 100) / $sumBrixMasaA, 2) : 0;

        // Calcular suma de Pol y Brix para Miel A
        $queryMielA = "SELECT SUM(pol) AS sumPolMielA, SUM(brix) AS sumBrixMielA, COUNT(*) AS countMielA FROM miela
        WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso AND brix > 0";
        $stmtMielA = $conexion->prepare($queryMielA);
        $stmtMielA->bindParam(':periodoZafra', $periodoZafra);
        $stmtMielA->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtMielA->execute();
        $resultMielA = $stmtMielA->fetch(PDO::FETCH_ASSOC);
        $sumPolMielA = $resultMielA['sumPolMielA'] ?: 0;
        $sumBrixMielA = $resultMielA['sumBrixMielA'] ?: 0;
        $countMielA = $resultMielA['countMielA'] ?: 0;
        $promBrixMielA = $countMielA ? round($sumBrixMielA / $countMielA, 2) : 0;
        $promPolMielA = $countMielA ? round($sumPolMielA / $countMielA, 2) : 0;
        $promPurMielA = ($promBrixMielA != 0) ? round(($promPolMielA * 100) / $promBrixMielA, 2) : 0;
        $promAgotamA = ($promPurMasaA != 0 && $promPurMielA != 0) ? round($promPurMasaA - $promPurMielA, 2) : 0;

        // Calcular promedio de Rendimiento Cristal combinando valores de masa y miel
        $promRendCristalA = ($promPurMasaA != 0 && $promPurMielA != 0 && (100 - $promPurMielA) != 0) ?
            round((($promPurMasaA - $promPurMielA) / (100 - $promPurMielA)) * 100, 2) : 0;

        // Procesar Masa Cocida B
        $queryMasaCocidaB = "SELECT 
        ROUND(AVG(brix), 2) AS promedio_brix, 
        ROUND(AVG(pol), 2) AS promedio_pol, 
        ROUND(AVG(CASE WHEN brix > 0 THEN (pol * 100) / brix ELSE NULL END), 2) AS promedio_pureza, 
        ROUND(SUM(volFt3), 2) AS total_vol_ft3 
        FROM masacocidab
        WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
        $stmtMasaCocidaB = $conexion->prepare($queryMasaCocidaB);
        $stmtMasaCocidaB->bindParam(':periodoZafra', $periodoZafra);
        $stmtMasaCocidaB->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtMasaCocidaB->execute();

        $resultadosMasaCocidaB = $stmtMasaCocidaB->fetch(PDO::FETCH_ASSOC);

        $brixB = $resultadosMasaCocidaB['promedio_brix'] ?? 0;
        $volFt3B = $resultadosMasaCocidaB['total_vol_ft3'] ?? 0;

        // Calcular Tn/m³ para Masa B
        $tnM3B = calcular_tn_m3($brixB);

        // Calcular Tn Masa para Masa B
        $tnMasaB = calcular_tn_masa($volFt3B, $tnM3B);

        // Acumular totales
        $totalTnMasaB += $tnMasaB;
        $totalVolFt3B += $volFt3B;

        // Calcular promedio de Tn/m³ para Masa B
        $promTnM3B = ($totalVolFt3B > 0) ? round($totalTnMasaB / ($totalVolFt3B * pow(0.3048, 3)), 5) : 0;

        // Calcular pureza
        $purezaB = ($brixB > 0) ? round(($resultadosMasaCocidaB['promedio_pol'] * 100) / $brixB, 2) : 0;

        $queryMasaB = "SELECT SUM(pol) AS sumPolMasaB, SUM(brix) AS sumBrixMasaB FROM masacocidab 
            WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso AND brix > 0";
        $stmtMasaB = $conexion->prepare($queryMasaB);
        $stmtMasaB->bindParam(':periodoZafra', $periodoZafra);
        $stmtMasaB->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtMasaB->execute();
        $resultMasaB = $stmtMasaB->fetch(PDO::FETCH_ASSOC);
        $sumPolMasaB = $resultMasaB['sumPolMasaB'] ?: 0;
        $sumBrixMasaB = $resultMasaB['sumBrixMasaB'] ?: 0;
        $promPurMasaB = ($sumBrixMasaB != 0) ? round(($sumPolMasaB * 100) / $sumBrixMasaB, 2) : 0;

        // Calcular suma de Pol y Brix para Miel B
        $queryMielB = "SELECT SUM(pol) AS sumPolMielB, SUM(brix) AS sumBrixMielB, COUNT(*) AS countMielB FROM mielb
        WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso AND brix > 0";
        $stmtMielB = $conexion->prepare($queryMielB);
        $stmtMielB->bindParam(':periodoZafra', $periodoZafra);
        $stmtMielB->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtMielB->execute();
        $resultMielB = $stmtMielB->fetch(PDO::FETCH_ASSOC);
        $sumPolMielB = $resultMielB['sumPolMielB'] ?: 0;
        $sumBrixMielB = $resultMielB['sumBrixMielB'] ?: 0;
        $countMielB = $resultMielB['countMielB'] ?: 0;
        $promBrixMielB = $countMielB ? round($sumBrixMielB / $countMielB, 2) : 0;
        $promPolMielB = $countMielB ? round($sumPolMielB / $countMielB, 2) : 0;
        $promPurMielB = ($promBrixMielB != 0) ? round(($promPolMielB * 100) / $promBrixMielB, 2) : 0;
        $promAgotamB = ($promPurMasaB != 0 && $promPurMielB != 0) ? round($promPurMasaB - $promPurMielB, 2) : 0;

        // Calcular promedio de Rendimiento Cristal combinando valores de masa y miel
        $promRendCristalB = ($promPurMasaB != 0 && $promPurMielB != 0 && (100 - $promPurMielB) != 0) ?
            round((($promPurMasaB - $promPurMielB) / (100 - $promPurMielB)) * 100, 2) : 0;

        // Procesar Masa Cocida C
        $queryMasaCocidaC = "SELECT 
        ROUND(AVG(brix), 2) AS promedio_brix, 
        ROUND(AVG(pol), 2) AS promedio_pol, 
        ROUND(AVG(CASE WHEN brix > 0 THEN (pol * 100) / brix ELSE NULL END), 2) AS promedio_pureza, 
        ROUND(SUM(volFt3), 2) AS total_vol_ft3 
        FROM masacocidac
        WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
        $stmtMasaCocidaC = $conexion->prepare($queryMasaCocidaC);
        $stmtMasaCocidaC->bindParam(':periodoZafra', $periodoZafra);
        $stmtMasaCocidaC->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtMasaCocidaC->execute();

        $resultadosMasaCocidaC = $stmtMasaCocidaC->fetch(PDO::FETCH_ASSOC);

        $brixC = $resultadosMasaCocidaC['promedio_brix'] ?? 0;
        $volFt3C = $resultadosMasaCocidaC['total_vol_ft3'] ?? 0;

        // Calcular Tn/m³ para Masa C
        $tnM3C = calcular_tn_m3($brixC);

        // Calcular Tn Masa para Masa C
        $tnMasaC = calcular_tn_masa($volFt3C, $tnM3C);

        // Acumular totales
        $totalTnMasaC += $tnMasaC;
        $totalVolFt3C += $volFt3C;

        // Calcular promedio de Tn/m³ para Masa C
        $promTnM3C = ($totalVolFt3C > 0) ? round($totalTnMasaC / ($totalVolFt3C * pow(0.3048, 3)), 5) : 0;

        // Calcular pureza
        $purezaC = ($brixC > 0) ? round(($resultadosMasaCocidaC['promedio_pol'] * 100) / $brixC, 2) : 0;

        $queryMasaC = "SELECT SUM(pol) AS sumPolMasaC, SUM(brix) AS sumBrixMasaC FROM masacocidac 
            WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso AND brix > 0";
        $stmtMasaC = $conexion->prepare($queryMasaC);
        $stmtMasaC->bindParam(':periodoZafra', $periodoZafra);
        $stmtMasaC->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtMasaC->execute();
        $resultMasaC = $stmtMasaC->fetch(PDO::FETCH_ASSOC);
        $sumPolMasaC = $resultMasaC['sumPolMasaC'] ?: 0;
        $sumBrixMasaC = $resultMasaC['sumBrixMasaC'] ?: 0;
        $promPurMasaC = ($sumBrixMasaC != 0) ? round(($sumPolMasaC * 100) / $sumBrixMasaC, 2) : 0;

        // Calcular suma de Pol y Brix para Miel Final
        $queryMielFinal = "SELECT SUM(pol) AS sumPolMielFinal, SUM(brix) AS sumBrixMielFinal, SUM(azucRed) AS sumAzucRedMielFinal, COUNT(*) AS countMielFinal, COUNT(NULLIF(azucRed, 0)) AS countAzucRedMielFinal FROM mielfinal
        WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso AND brix > 0";
        $stmtMielFinal = $conexion->prepare($queryMielFinal);
        $stmtMielFinal->bindParam(':periodoZafra', $periodoZafra);
        $stmtMielFinal->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtMielFinal->execute();
        $resultMielFinal = $stmtMielFinal->fetch(PDO::FETCH_ASSOC);
        $sumPolMielFinal = $resultMielFinal['sumPolMielFinal'] ?: 0;
        $sumBrixMielFinal = $resultMielFinal['sumBrixMielFinal'] ?: 0;
        $sumAzucRedMielFinal = $resultMielFinal['sumAzucRedMielFinal'] ?: 0;
        $countMielFinal = $resultMielFinal['countMielFinal'] ?: 0;
        $countAzucRedMielFinal = $resultMielFinal['countAzucRedMielFinal'] ?: 0;
        $promBrixMielFinal = $countMielFinal ? round($sumBrixMielFinal / $countMielFinal, 2) : 0;
        $promPolMielFinal = $countMielFinal ? round($sumPolMielFinal / $countMielFinal, 2) : 0;
        $promPurMielFinal = ($promBrixMielFinal != 0) ? round(($promPolMielFinal * 100) / $promBrixMielFinal, 2) : 0;
        $promAzucRedMielFinal = ($countAzucRedMielFinal > 0) ? round($sumAzucRedMielFinal / $countAzucRedMielFinal, 2) : 0;

        // Calcular promedio de Rendimiento Cristal combinando valores de masa y miel
        $promRendCristalFinal = ($promPurMasaC != 0 && $promPurMielFinal != 0 && (100 - $promPurMielFinal) != 0) ?
            round((($promPurMasaC - $promPurMielFinal) / (100 - $promPurMielFinal)) * 100, 2) : 0;

        // Consulta para Análisis Azúcar
        $queryAnalisisAzucar = "SELECT 
            ROUND(AVG(color), 0) AS promedio_color, 
            ROUND(AVG(turbidez), 0) AS promedio_turbidez, 
            ROUND(AVG(vitaminaA), 2) AS promedio_vitaminaA, 
            ROUND(AVG(pol), 2) AS promedio_pol, 
            ROUND(AVG(humedad), 2) AS promedio_humedad, 
            ROUND(AVG(cenizas), 2) AS promedio_cenizas
        FROM analisisazucar
        WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";

        $stmtAnalisisAzucar = $conexion->prepare($queryAnalisisAzucar);
        $stmtAnalisisAzucar->bindParam(':periodoZafra', $periodoZafra);
        $stmtAnalisisAzucar->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtAnalisisAzucar->execute();
        $promediosAzucar = $stmtAnalisisAzucar->fetch(PDO::FETCH_ASSOC);

        $promColor = $promediosAzucar['promedio_color'] ?? 0;
        $promTurbidez = $promediosAzucar['promedio_turbidez'] ?? 0;
        $promVitaminaA = $promediosAzucar['promedio_vitaminaA'] ?? 0;
        $promPolAzucar = $promediosAzucar['promedio_pol'] ?? 0;
        $promHumedad = $promediosAzucar['promedio_humedad'] ?? 0;
        $promCenizas = $promediosAzucar['promedio_cenizas'] ?? 0;

        // Consulta para Magma B
        $queryMagmaB = "SELECT 
            SUM(brix) AS sumaBrix, 
            SUM(pol) AS sumaPol, 
            COUNT(NULLIF(brix, 0)) AS countBrix,
            COUNT(NULLIF(pol, 0)) AS countPol
              FROM magmab
              WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
        $stmtMagmaB = $conexion->prepare($queryMagmaB);
        $stmtMagmaB->bindParam(':periodoZafra', $periodoZafra);
        $stmtMagmaB->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtMagmaB->execute();
        $resultadosMagmaB = $stmtMagmaB->fetch(PDO::FETCH_ASSOC);

        // Asignación de resultados
        $sumaBrixMB = !empty($resultadosMagmaB['sumaBrix']) ? $resultadosMagmaB['sumaBrix'] : 0;
        $sumaPolMB = !empty($resultadosMagmaB['sumaPol']) ? $resultadosMagmaB['sumaPol'] : 0;
        $countBrixMB = !empty($resultadosMagmaB['countBrix']) ? $resultadosMagmaB['countBrix'] : 1;
        $countPolMB = !empty($resultadosMagmaB['countPol']) ? $resultadosMagmaB['countPol'] : 1;

        // Cálculo de promedios
        $avgBrixMB = ($countBrixMB > 0) ? round($sumaBrixMB / $countBrixMB, 2) : 0;
        $avgPolMB = ($countPolMB > 0) ? round($sumaPolMB / $countPolMB, 2) : 0;
        $promPurezaMB = ($avgBrixMB != 0) ? round(($avgPolMB * 100) / $avgBrixMB, 2) : 0;

        // Consulta para Magma C
        $queryMagmaC = "SELECT 
            SUM(brix) AS sumaBrix, 
            SUM(pol) AS sumaPol, 
            COUNT(NULLIF(brix, 0)) AS countBrix,
            COUNT(NULLIF(pol, 0)) AS countPol
              FROM magmac
              WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
        $stmtMagmaC = $conexion->prepare($queryMagmaC);
        $stmtMagmaC->bindParam(':periodoZafra', $periodoZafra);
        $stmtMagmaC->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtMagmaC->execute();
        $resultadosMagmaC = $stmtMagmaC->fetch(PDO::FETCH_ASSOC);

        // Asignación de resultados
        $sumaBrixMC = !empty($resultadosMagmaC['sumaBrix']) ? $resultadosMagmaC['sumaBrix'] : 0;
        $sumaPolMC = !empty($resultadosMagmaC['sumaPol']) ? $resultadosMagmaC['sumaPol'] : 0;
        $countBrixMC = !empty($resultadosMagmaC['countBrix']) ? $resultadosMagmaC['countBrix'] : 1;
        $countPolMC = !empty($resultadosMagmaC['countPol']) ? $resultadosMagmaC['countPol'] : 1;

        // Cálculo de promedios
        $avgBrixMC = ($countBrixMC > 0) ? round($sumaBrixMC / $countBrixMC, 2) : 0;
        $avgPolMC = ($countPolMC > 0) ? round($sumaPolMC / $countPolMC, 2) : 0;
        $promPurezaMC = ($avgBrixMC != 0) ? round(($avgPolMC * 100) / $avgBrixMC, 2) : 0;

        // Consulta para Efluentes
        $queryEfluentes = "SELECT 
            ROUND(AVG(enfriamiento), 2) AS promedio_enfriamiento, 
            ROUND(AVG(retorno), 2) AS promedio_retorno, 
            ROUND(AVG(desechos), 2) AS promedio_desechos
        FROM efluentes
        WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";

        $stmtEfluentes = $conexion->prepare($queryEfluentes);
        $stmtEfluentes->bindParam(':periodoZafra', $periodoZafra);
        $stmtEfluentes->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtEfluentes->execute();
        $promediosEfluentes = $stmtEfluentes->fetch(PDO::FETCH_ASSOC);

        $promEnfriamiento = $promediosEfluentes['promedio_enfriamiento'] ?? 0;
        $promRetorno = $promediosEfluentes['promedio_retorno'] ?? 0;
        $promDesechos = $promediosEfluentes['promedio_desechos'] ?? 0;

        // Consulta para Saco Azúcar
        $querySacoAzucar = "SELECT 
            ROUND(SUM(tnCanaACHSA), 2) AS total_tn_cana_achsa, 
            ROUND(SUM(moliendaTn), 2) AS total_molienda_tn, 
            ROUND(SUM(sacos50AzucarBlanco), 2) AS total_sacos_50_azucar_blanco, 
            ROUND(SUM(sacosAzucarMorena), 2) AS total_sacos_azucar_morena, 
            ROUND(SUM(jumboAzucarBlanco), 2) AS total_jumbo_azucar_blanco
        FROM sacoazucar
        WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";

        $stmtSacoAzucar = $conexion->prepare($querySacoAzucar);
        $stmtSacoAzucar->bindParam(':periodoZafra', $periodoZafra);
        $stmtSacoAzucar->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtSacoAzucar->execute();
        $resultadosSacoAzucar = $stmtSacoAzucar->fetch(PDO::FETCH_ASSOC);

        $totalTnCanaACHSA = $resultadosSacoAzucar['total_tn_cana_achsa'] ?? 0;
        $totalMoliendaTn = $resultadosSacoAzucar['total_molienda_tn'] ?? 0;
        $totalSacos50AzucarBlanco = $resultadosSacoAzucar['total_sacos_50_azucar_blanco'] ?? 0;
        $totalSacosAzucarMorena = $resultadosSacoAzucar['total_sacos_azucar_morena'] ?? 0;
        $totalJumboAzucarBlanco = $resultadosSacoAzucar['total_jumbo_azucar_blanco'] ?? 0;

        // Calcular toneladas de azúcar
        $tnAzucarBlanco50 = round($totalSacos50AzucarBlanco / 20, 3);
        $tnAzucarMorena = round($totalSacosAzucarMorena / 20, 3);
        $tnAzucarBlancoJumbo = round($totalJumboAzucarBlanco * 1.25, 3);
        $tnAzucarTotal = round($tnAzucarBlanco50 + $tnAzucarMorena + $tnAzucarBlancoJumbo, 3);

        // Consulta para Control PH
        $queryPromedioControlPH = "SELECT 
            ROUND(SUM(primario) / NULLIF(COUNT(NULLIF(primario, 0)), 0), 2) AS promedio_primario, 
            ROUND(SUM(mezclado) / NULLIF(COUNT(NULLIF(mezclado, 0)), 0), 2) AS promedio_mezclado, 
            ROUND(SUM(residual) / NULLIF(COUNT(NULLIF(residual, 0)), 0), 2) AS promedio_residual, 
            ROUND(SUM(sulfitado) / NULLIF(COUNT(NULLIF(sulfitado, 0)), 0), 2) AS promedio_sulfitado, 
            ROUND(SUM(filtrado) / NULLIF(COUNT(NULLIF(filtrado, 0)), 0), 2) AS promedio_filtrado, 
            ROUND(SUM(alcalizado) / NULLIF(COUNT(NULLIF(alcalizado, 0)), 0), 2) AS promedio_alcalizado, 
            ROUND(SUM(clarificado) / NULLIF(COUNT(NULLIF(clarificado, 0)), 0), 2) AS promedio_clarificado, 
            ROUND(SUM(meladura) / NULLIF(COUNT(NULLIF(meladura, 0)), 0), 2) AS promedio_meladura
        FROM controlph 
        WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";

        $stmtPromedioControlPH = $conexion->prepare($queryPromedioControlPH);
        $stmtPromedioControlPH->bindParam(':periodoZafra', $periodoZafra);
        $stmtPromedioControlPH->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtPromedioControlPH->execute();

        $resultadosPromedioControlPH = $stmtPromedioControlPH->fetch(PDO::FETCH_ASSOC);

        // Variables para los promedios individuales
        $promedioPrimario = $resultadosPromedioControlPH['promedio_primario'] ?? '-';
        $promedioMezclado = $resultadosPromedioControlPH['promedio_mezclado'] ?? '-';
        $promedioResidual = $resultadosPromedioControlPH['promedio_residual'] ?? '-';
        $promedioSulfitado = $resultadosPromedioControlPH['promedio_sulfitado'] ?? '-';
        $promedioFiltrado = $resultadosPromedioControlPH['promedio_filtrado'] ?? '-';
        $promedioAlcalizado = $resultadosPromedioControlPH['promedio_alcalizado'] ?? '-';
        $promedioClarificado = $resultadosPromedioControlPH['promedio_clarificado'] ?? '-';
        $promedioMeladura = $resultadosPromedioControlPH['promedio_meladura'] ?? '-';

        // Consulta para Agua de Imbibición
        $queryAguaImbibicion = "SELECT
            totalizador, 
            valorInicial 
        FROM aguaimbibicion 
        WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";

        $stmtAguaImbibicion = $conexion->prepare($queryAguaImbibicion);
        $stmtAguaImbibicion->bindParam(':periodoZafra', $periodoZafra);
        $stmtAguaImbibicion->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtAguaImbibicion->execute();
        $resultadosAguaImbibicion = $stmtAguaImbibicion->fetchAll(PDO::FETCH_ASSOC);

        // Inicializar variables
        $ultimoTotalizador = null; // Para identificar si es el primer cálculo
        $factorConversion = 0.003511943101; // Factor constante para conversión

        // Calcular Tn/hora
        foreach ($resultadosAguaImbibicion as &$registro) {
            if ($registro['totalizador'] !== null && $registro['totalizador'] > 0) {
            if ($ultimoTotalizador === null) {
            if ($registro['valorInicial'] !== null) {
            // Calcular diferencia con el valor inicial para el primer registro
            $diferencia = $registro['totalizador'] - $registro['valorInicial'];
            } else {
            // Si no hay valor inicial, usar el totalizador directamente
            $diferencia = $registro['totalizador'];
            }
            } else {
            // Calcular diferencia con el último totalizador para los registros posteriores
            $diferencia = $registro['totalizador'] - $ultimoTotalizador;
            }
            $registro['tnHora'] = ($diferencia >= 0) ? round($diferencia * $factorConversion, 3) : '';
            // Actualizar el último totalizador
            $ultimoTotalizador = $registro['totalizador'];
            } else {
            $registro['tnHora'] = 0; // Si no hay totalizador, dejar vacío
            }
        }
        unset($registro); // Liberar referencia

        // Calcular sumas y promedios
        $sumas = ['totalizador' => 0, 'tnHora' => 0];
        $contadores = array_fill_keys(array_keys($sumas), 0);

        foreach ($resultadosAguaImbibicion as $registro) {
            foreach ($sumas as $key => $value) {
            if (is_numeric($registro[$key]) && $registro[$key] != 0 && $registro[$key] != '-') {
            $sumas[$key] += $registro[$key];
            $contadores[$key]++;
            }
            }
        }

        $promediosAguaImbibicion = [];
        foreach ($sumas as $key => $value) {
            if ($key === 'tnHora') {
            $promediosAguaImbibicion[$key] = round($value, 2);
            } else {
            $promediosAguaImbibicion[$key] = ($contadores[$key] > 0) ? round($value / $contadores[$key]) : 0;
            }
        }

        // Consulta para Jugo Mezclado PH
        $queryMezcladoPHMBC = "SELECT 
            totalizador, 
            valorInicial 
        FROM jugomezcladophmbc 
        WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";

        $stmtMezcladoPHMBC = $conexion->prepare($queryMezcladoPHMBC);
        $stmtMezcladoPHMBC->bindParam(':periodoZafra', $periodoZafra);
        $stmtMezcladoPHMBC->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtMezcladoPHMBC->execute();
        $resultadosJugoMezcladoPHMBC = $stmtMezcladoPHMBC->fetchAll(PDO::FETCH_ASSOC);

        // Inicializar variables
        $ultimoTotalizador = null;
        $factorConversionGeneral = 0.95 / 2204.62;

        foreach ($resultadosJugoMezcladoPHMBC as &$registro) {
            if ($registro['totalizador'] !== null) {
            if ($ultimoTotalizador === null) {
                $diferencia = $registro['totalizador'] - $registro['valorInicial'];
                $registro['tnHora'] = ($diferencia < 0) ? "" : round($diferencia * $factorConversionGeneral, 3);
            } else {
                $diferencia = $registro['totalizador'] - $ultimoTotalizador;
                $registro['tnHora'] = round($diferencia * $factorConversionGeneral, 3);
            }
            $ultimoTotalizador = $registro['totalizador'];
            } else {
            $registro['tnHora'] = round(0 * $factorConversionGeneral, 3);
            }
        }
        unset($registro); // Liberar referencia

        // Calcular sumas y promedios
        $sumas = ['totalizador' => 0, 'tnHora' => 0];
        $contadores = array_fill_keys(array_keys($sumas), 0);

        foreach ($resultadosJugoMezcladoPHMBC as $registro) {
            foreach ($sumas as $key => $value) {
            if ($registro[$key] !== null && $registro[$key] != 0 && $registro[$key] != '-') {
                $sumas[$key] += is_numeric($registro[$key]) ? $registro[$key] : 0;
                $contadores[$key]++;
            }
            }
        }

        $promediosJugoMezcladoPHMBC = [];
        foreach ($sumas as $key => $value) {
            if ($key === 'tnHora') {
            $promediosJugoMezcladoPHMBC[$key] = round($value, 2);
            } else {
            $promediosJugoMezcladoPHMBC[$key] = ($contadores[$key] > 0) ? round($value / $contadores[$key]) : 0;
            }
        }

        // Consulta para Filtro Cachaza
        $queryFiltroCachaza = "SELECT 
            DATE_FORMAT(hora, '%H:%i') AS hora, 
            pol1, gFt1, pol2, gFt2, pol3, gFt3, idFiltroCachaza
        FROM filtrocachaza
        WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";

        $stmtFiltroCachaza = $conexion->prepare($queryFiltroCachaza);
        $stmtFiltroCachaza->bindParam(':periodoZafra', $periodoZafra);
        $stmtFiltroCachaza->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtFiltroCachaza->execute();
        $registrosFiltroCachaza = $stmtFiltroCachaza->fetchAll(PDO::FETCH_ASSOC);

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

        // Organizar registros por hora y por filtro
        $datosPorHoraFiltroCachaza = [];
        foreach ($horasFijas as $hora) {
            $datosPorHoraFiltroCachaza[$hora] = [
                'pol1' => null,
                'gFt1' => null,
                'pol2' => null,
                'gFt2' => null,
                'pol3' => null,
                'gFt3' => null,
                'idFiltroCachaza' => null
            ];
        }

        foreach ($registrosFiltroCachaza as $registro) {
            $hora = $registro['hora'];
            $datosPorHoraFiltroCachaza[$hora] = [
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
        $promediosFiltroCachaza = [
            'Filtro 1' => ['pol' => 0, 'gFt_ft2' => 0, 'count_pol' => 0, 'count_gFt' => 0],
            'Filtro 2' => ['pol' => 0, 'gFt_ft2' => 0, 'count_pol' => 0, 'count_gFt' => 0],
            'Filtro 3' => ['pol' => 0, 'gFt_ft2' => 0, 'count_pol' => 0, 'count_gFt' => 0],
        ];

        // Calcular acumuladores para cada filtro
        foreach ($datosPorHoraFiltroCachaza as $hora => $datos) {
            if (is_numeric($datos['pol1']) && $datos['pol1'] > 0) {
                $promediosFiltroCachaza['Filtro 1']['pol'] += $datos['pol1'];
                $promediosFiltroCachaza['Filtro 1']['count_pol']++;
            }
            if (is_numeric($datos['gFt1']) && $datos['gFt1'] > 0) {
                $promediosFiltroCachaza['Filtro 1']['gFt_ft2'] += $datos['gFt1'];
                $promediosFiltroCachaza['Filtro 1']['count_gFt']++;
            }

            if (is_numeric($datos['pol2']) && $datos['pol2'] > 0) {
                $promediosFiltroCachaza['Filtro 2']['pol'] += $datos['pol2'];
                $promediosFiltroCachaza['Filtro 2']['count_pol']++;
            }

            if (is_numeric($datos['gFt2']) && $datos['gFt2'] > 0) {
                $promediosFiltroCachaza['Filtro 2']['gFt_ft2'] += $datos['gFt2'];
                $promediosFiltroCachaza['Filtro 2']['count_gFt']++;
            }

            if (is_numeric($datos['pol3']) && $datos['pol3'] > 0) {
                $promediosFiltroCachaza['Filtro 3']['pol'] += $datos['pol3'];
                $promediosFiltroCachaza['Filtro 3']['count_pol']++;
            }
            if (is_numeric($datos['gFt3']) && $datos['gFt3'] > 0) {
                $promediosFiltroCachaza['Filtro 3']['gFt_ft2'] += $datos['gFt3'];
                $promediosFiltroCachaza['Filtro 3']['count_gFt']++;
            }
        }

        // Calcular promedios finales
        foreach ($promediosFiltroCachaza as $filtro => &$valores) {
            $valores['pol'] = $valores['count_pol'] > 0 ? number_format(round($valores['pol'] / $valores['count_pol'], 2), 2) : '0.00';
            $valores['gFt_ft2'] = $valores['count_gFt'] > 0 ? number_format(round($valores['gFt_ft2'] / $valores['count_gFt'], 2), 2) : '0.00';
        }
        unset($valores);

        // Cálculo del Promedio Pol Cachaza
        $promedioPolCachaza = '-';
        if (
            is_numeric($promediosFiltroCachaza['Filtro 1']['pol']) &&
            is_numeric($promediosFiltroCachaza['Filtro 2']['pol']) &&
            is_numeric($promediosFiltroCachaza['Filtro 3']['pol'])
        ) {
            $promedioPolCachaza = round(
                ($promediosFiltroCachaza['Filtro 1']['pol'] + 0.5 * $promediosFiltroCachaza['Filtro 2']['pol'] + $promediosFiltroCachaza['Filtro 3']['pol']) / 2.5,
                2
            );
        }

        // Consulta para Cachaza
        $queryCachaza = "SELECT 
            ROUND(AVG(humedad), 2) AS promedio_humedad, 
            ROUND(AVG(fibra), 2) AS promedio_fibra
        FROM cachaza 
        WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";

        $stmtCachaza = $conexion->prepare($queryCachaza);
        $stmtCachaza->bindParam(':periodoZafra', $periodoZafra);
        $stmtCachaza->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtCachaza->execute();
        $promediosCachaza = $stmtCachaza->fetch(PDO::FETCH_ASSOC);

        // Consulta para Bagazo
        $queryBagazo = "SELECT 
            ROUND(AVG(pol), 2) AS promedio_pol, 
            ROUND(AVG(humedad), 2) AS promedio_humedad 
        FROM bagazo 
        WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";

        $stmtBagazo = $conexion->prepare($queryBagazo);
        $stmtBagazo->bindParam(':periodoZafra', $periodoZafra);
        $stmtBagazo->bindParam(':fechaIngreso', $fechaIngreso);
        $stmtBagazo->execute();
        $promediosBagazo = $stmtBagazo->fetch(PDO::FETCH_ASSOC);

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
                'meladura' => null
            ];
        }

        // Asegurarse de que $resultadosControlPH sea un array
        $resultadosControlPH = $resultadosControlPH ?? [];

        // Rellenar con datos existentes en la base de datos
        foreach ($resultadosControlPH as $registro) {
            $hora = $registro['hora'];
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

        $promediosPH = [];
        foreach ($sumas as $key => $value) {
            // Calcula solo si el contador es mayor a 0
            $promediosPH[$key] = ($contadores[$key] > 0)
                ? round($value / $contadores[$key], 2)  // Redondea el promedio
                : '-'; // Mostrar '-' si no hay valores válidos
        }

        // Consulta para Causas
        $queryCausas = "SELECT turno, 
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
        $stmt = $conexion->prepare($queryCausas);
        $stmt->bindParam(':periodoZafra', $periodoZafra);
        $stmt->bindParam(':fechaIngreso', $fechaIngreso);
        $stmt->execute();
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Separar registros por turnos
        $turnos = ['Turno A', 'Turno B', 'Turno C'];
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

        // Inicializar variables
        $totalTiempoPorTurno = array_fill_keys($turnos, '00:00');
        $totalDia = '00:00';

        // Procesar registros
        foreach ($registros as $registro) {
            if (isset($totalTiempoPorTurno[$registro['turno']])) {
                $registro['tiempoPerdido'] = !empty($registro['tiempoPerdido']) ? date('H:i', strtotime($registro['tiempoPerdido'])) : '00:00';
                $totalTiempoPorTurno[$registro['turno']] = sumarTiempos($totalTiempoPorTurno[$registro['turno']], $registro['tiempoPerdido']);
            }
        }

        // Sumar el total de horas de todos los turnos para obtener el total del día
        foreach ($totalTiempoPorTurno as $totalTiempo) {
            $totalDia = sumarTiempos($totalDia, $totalTiempo);
        }
    } catch (PDOException $e) {
        die("<div class='alert alert-danger'>Error al obtener el resumen diario: " . $e->getMessage() . "</div>");
    }
}


// Consulta para obtener los períodos de zafra
$queryPeriodos = "SELECT periodo FROM periodoszafra WHERE activo = 1 ORDER BY periodo DESC";
$stmtPeriodos = $conexion->prepare($queryPeriodos);
$stmtPeriodos->execute();
$periodos = $stmtPeriodos->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen Diario</title>
    <link href="<?= BASE_PATH ?>public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">
    <link href="<?= BASE_PATH ?>public/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>public/css/sb-admin-2.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
    <script>
        function exportToExcel() {
            var tables = document.querySelectorAll("table");
            var wb = XLSX.utils.book_new();
            var ws = XLSX.utils.aoa_to_sheet([]);
            var rowOffset = 0;

            tables.forEach(function(table) {
                var tableName = table.previousElementSibling.textContent.trim();
                var wsData = XLSX.utils.table_to_sheet(table);
                var jsonData = XLSX.utils.sheet_to_json(wsData, {
                    header: 1
                });

                // Add table name as a header
                jsonData.unshift([tableName]);
                jsonData.unshift([]);

                XLSX.utils.sheet_add_aoa(ws, jsonData, {
                    origin: -1
                });
                rowOffset += jsonData.length + 1;
            });

            // agregar formato a la hoja de excel
            var range = XLSX.utils.decode_range(ws['!ref']);
            for (var R = range.s.r; R <= range.e.r; ++R) {
                for (var C = range.s.c; C <= range.e.c; ++C) {
                    var cell_address = {
                        c: C,
                        r: R
                    };
                    var cell_ref = XLSX.utils.encode_cell(cell_address);
                    if (!ws[cell_ref]) continue;
                    ws[cell_ref].s = {
                        border: {
                            top: {
                                style: "thin",
                                color: {
                                    rgb: "000000"
                                }
                            },
                            bottom: {
                                style: "thin",
                                color: {
                                    rgb: "000000"
                                }
                            },
                            left: {
                                style: "thin",
                                color: {
                                    rgb: "000000"
                                }
                            },
                            right: {
                                style: "thin",
                                color: {
                                    rgb: "000000"
                                }
                            }
                        },
                        alignment: {
                            vertical: "center",
                            horizontal: "center"
                        },
                        font: {
                            name: "Arial",
                            sz: 10
                        },
                        fill: {
                            fgColor: {
                                rgb: (R === 0 ? "FFFF00" : "FFFFFF")
                            }
                        }
                    };
                }
            }

            XLSX.utils.book_append_sheet(wb, ws, "Resumen Diario");
            XLSX.writeFile(wb, "resumen_diario.xlsx");
        }
    </script>

    <style>
        .btn-new {
            background-color: rgb(47, 165, 72);
            color: white;
        }

        .btn-new:hover {
            background-color: rgb(60, 236, 44);
            color: white
        }

        .table th,
        .table td {
            text-align: center !important;
            vertical-align: middle;
            padding: 5px;
            width: 10% !important;
        }

        .table tbody tr:last-child td {
            padding-bottom: 0.25rem;
            /* Reduce el tamaño de la parte inferior de las tablas */
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
            z-index: 1050;
            /* Asegura que el sidebar esté por encima de otros elementos */
        }


        .card {
            margin-bottom: 10 !important;
            /* Quita el margen inferior de las tablas */
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
                    <div class="container-fluid">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h1 class="h3 mb-2 text-gray-800">Resumen Diario</h1>
                            <!-- <button class="btn btn-new" onclick="exportToExcel()">Exportar a Excel</button> -->
                        </div>
                        <!-- Filtros de selección de periodo y fecha -->
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

                        <?php if (empty($periodoZafra) || empty($fechaIngreso)): ?>
                            <!-- Mostrar mensaje si no se selecciona un período de zafra o fecha -->
                            <div class="alert alert-info">Por favor, seleccione un periodo de zafra y una fecha para ver los registros.</div>
                        <?php else: ?>
                            <?php

                            // Validar si hay datos en al menos una tabla
                            $hayDatos = (
                                ($resultadosPrimario && array_filter($resultadosPrimario)) ||
                                ($resultadosMezclado && array_filter($resultadosMezclado)) ||
                                ($resultadosResidual && array_filter($resultadosResidual)) ||
                                ($resultadosClarificado && array_filter($resultadosClarificado)) ||
                                ($resultadosFiltrado && array_filter($resultadosFiltrado)) ||
                                ($resultadosMeladura && array_filter($resultadosMeladura)) ||
                                ($resultadosMasaCocidaA && array_filter($resultadosMasaCocidaA)) ||
                                ($resultadosMasaCocidaB && array_filter($resultadosMasaCocidaB)) ||
                                ($resultadosMasaCocidaC && array_filter($resultadosMasaCocidaC)) ||
                                ($promediosMielA && array_filter($promediosMielA)) ||
                                ($promediosMielB && array_filter($promediosMielB)) ||
                                ($promediosMielFinal && array_filter($promediosMielFinal)) ||
                                ($promediosAzucar && array_filter($promediosAzucar)) ||
                                ($promediosMagmaB && array_filter($promediosMagmaB)) ||
                                ($promediosMagmaC && array_filter($promediosMagmaC)) ||
                                ($promediosEfluentes && array_filter($promediosEfluentes)) ||
                                ($resultadosSacoAzucar && array_filter($resultadosSacoAzucar)) ||
                                ($resultadosPromedioControlPH && array_filter($resultadosPromedioControlPH)) ||
                                ($promediosAguaImbibicion && array_filter($promediosAguaImbibicion)) ||
                                ($promediosJugoMezcladoPHMBC && array_filter($promediosJugoMezcladoPHMBC)) ||
                                ($promediosBagazo && array_filter($promediosBagazo)) ||
                                ($registrosFiltroCachaza && array_filter($registrosFiltroCachaza)) ||
                                ($promediosCachaza && array_filter($promediosCachaza)) ||
                                ($promediosCausas && array_filter($promediosCausas)) ||
                                ($registros && array_filter($registros))
                            );

                            if (!$hayDatos) {
                                echo "<div class='alert alert-info'>No se encontraron registros para el periodo de zafra y la fecha seleccionados.</div>";
                            }
                            ?>

                            <!-- Mostrar divisiones de reportes solo si hay datos -->
                            <?php if (
                                ($resultadosPrimario && array_filter($resultadosPrimario)) ||
                                ($resultadosMezclado && array_filter($resultadosMezclado)) ||
                                ($resultadosResidual && array_filter($resultadosResidual)) ||
                                ($resultadosClarificado && array_filter($resultadosClarificado)) ||
                                ($resultadosFiltrado && array_filter($resultadosFiltrado)) ||
                                ($resultadosMeladura && array_filter($resultadosMeladura))
                            ): ?>
                                <div class="my-4">
                                    <h3 class="text-center text-secondary"><i class="fas fa-layer-group"></i> Jugos & Meladura</h3>
                                    <hr class="my-4">
                                </div>
                            <?php endif; ?>

                            <!-- Mostrar tablas solo si hay datos -->
                            <?php if ($resultadosPrimario && array_filter($resultadosPrimario)): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <!-- Encabezado para Jugo Primario -->
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-flask"></i> Jugo Primario</h4>
                                                    <tr>
                                                        <th>Brix</th>
                                                        <th>SAC</th>
                                                        <th>Pureza</th>
                                                        <th>Azúcar Reducido</th>
                                                        <th>ML Gastado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= number_format($avgBrixJP, 2); ?></td>
                                                        <td><?= number_format($avgSacJP, 2); ?></td>
                                                        <td><?= number_format($promPurJP, 2); ?></td>
                                                        <td><?= number_format($promAzucarRedJP, 2); ?></td>
                                                        <td><?= number_format($avgMlGastadoJP, 2); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($resultadosMezclado && array_filter($resultadosMezclado)): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <!-- Encabezado para Jugo Mezclado -->
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-flask"></i> Jugo Mezclado</h4>
                                                    <tr>
                                                        <th>Brix</th>
                                                        <th>SAC</th>
                                                        <th>Pureza</th>
                                                        <th>Azúcar Reducido</th>
                                                        <th>ML Gastado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= number_format($avgBrixJM, 2); ?></td>
                                                        <td><?= number_format($avgSacJM, 2); ?></td>
                                                        <td><?= number_format($promPurJM, 2); ?></td>
                                                        <td><?= number_format($promAzucarRedJM, 2); ?></td>
                                                        <td><?= number_format($avgMlGastadoJM, 2); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($resultadosResidual && array_filter($resultadosResidual)): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <!-- Encabezado para Jugo Residual -->
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-flask"></i> Jugo Residual</h4>
                                                    <tr>
                                                        <th>Brix</th>
                                                        <th>SAC</th>
                                                        <th>Pureza</th>
                                                        <th>Azúcar Reducido</th>
                                                        <th>ML Gastado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= number_format($avgBrixJR, 2); ?></td>
                                                        <td><?= number_format($avgSacJR, 2); ?></td>
                                                        <td><?= number_format($promPurJR, 2); ?></td>
                                                        <td><?= number_format($promAzucarRedJR, 2); ?></td>
                                                        <td><?= number_format($avgMlGastadoJR, 2); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($resultadosClarificado && array_filter($resultadosClarificado)): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <!-- Encabezado para Jugo Clarificado -->
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-flask"></i> Jugo Clarificado</h4>
                                                    <tr>
                                                        <th>Brix</th>
                                                        <th>SAC</th>
                                                        <th>Pureza</th>
                                                        <th>Azúcar Reducido</th>
                                                        <th>ML Gastado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= number_format($avgBrixJC, 2); ?></td>
                                                        <td><?= number_format($avgSacJC, 2); ?></td>
                                                        <td><?= number_format($promPurJC, 2); ?></td>
                                                        <td><?= number_format($promAzucarRedJC, 2); ?></td>
                                                        <td><?= number_format($avgMlGastadoJC, 2); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($resultadosFiltrado && array_filter($resultadosFiltrado)): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <!-- Encabezado para Jugo Filtrado -->
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-flask"></i> Jugo Filtrado</h4>
                                                    <tr>
                                                        <th>Brix</th>
                                                        <th>SAC</th>
                                                        <th>Pureza</th>
                                                        <th>Azúcar Reducido</th>
                                                        <th>ML Gastado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= number_format($avgBrixJF, 2); ?></td>
                                                        <td><?= number_format($avgSacJF, 2); ?></td>
                                                        <td><?= number_format($promPurJF, 2); ?></td>
                                                        <td><?= number_format($promAzucarRedJF, 2); ?></td>
                                                        <td><?= number_format($avgMlGastadoJF, 2); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($resultadosMeladura && array_filter($resultadosMeladura)): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <!-- Encabezado para Meladura -->
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-flask"></i> Meladura</h4>
                                                    <tr>
                                                        <th>Brix</th>
                                                        <th>SAC</th>
                                                        <th>Pureza</th>
                                                        <th>Azúcar Reducido</th>
                                                        <th>ML Gastado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= number_format($avgBrixMel, 2); ?></td>
                                                        <td><?= number_format($avgSacMel, 2); ?></td>
                                                        <td><?= number_format($promPurMel, 2); ?></td>
                                                        <td><?= number_format($promAzucarRedMel, 2); ?></td>
                                                        <td><?= number_format($avgMlGastadoMel, 2); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Separador para Masas & Mieles solo si hay datos -->
                            <?php if (
                                ($resultadosMasaCocidaA && array_filter($resultadosMasaCocidaA)) ||
                                ($resultadosMasaCocidaB && array_filter($resultadosMasaCocidaB)) ||
                                ($resultadosMasaCocidaC && array_filter($resultadosMasaCocidaC)) ||
                                ($promediosMielA && array_filter($promediosMielA)) ||
                                ($promediosMielB && array_filter($promediosMielB)) ||
                                ($promediosMielFinal && array_filter($promediosMielFinal)) ||
                                ($promediosAzucar && array_filter($promediosAzucar)) ||
                                ($promediosMagmaB && array_filter($promediosMagmaB)) ||
                                ($promediosMagmaC && array_filter($promediosMagmaC)) ||
                                ($promediosEfluentes && array_filter($promediosEfluentes)) ||
                                ($resultadosSacoAzucar && array_filter($resultadosSacoAzucar))

                            ): ?>
                                <div class="my-4">
                                    <h3 class="text-center text-secondary"><i class="fas fa-layer-group"></i> Masas & Mieles</h3>
                                    <hr class="my-4">
                                </div>
                            <?php endif; ?>

                            <?php if ($resultadosMasaCocidaA && array_filter($resultadosMasaCocidaA)): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-layer-group"></i> Masa Cocida A</h4>
                                                    <tr>
                                                        <th>Volumen (ft³)</th>
                                                        <th>Brix</th>
                                                        <th>Pol</th>
                                                        <th>Pureza</th>
                                                        <th>Rendimiento Cristal</th>
                                                        <th>Tn Masa</th>
                                                        <th>Tn/m³</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= number_format($volFt3A, 0); ?></td>
                                                        <td><?= number_format($brixA, 2); ?></td>
                                                        <td><?= number_format($resultadosMasaCocidaA['promedio_pol'], 2); ?></td>
                                                        <td><?= number_format($purezaA, 2); ?></td>
                                                        <td><?= number_format($promRendCristalA, 2); ?></td>
                                                        <td><?= number_format($totalTnMasaA, 3); ?></td>
                                                        <td><?= number_format($promTnM3A, 5); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($resultadosMasaCocidaB && array_filter($resultadosMasaCocidaB)): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-layer-group"></i> Masa Cocida B</h4>
                                                    <tr>
                                                        <th>Volumen (ft³)</th>
                                                        <th>Brix</th>
                                                        <th>Pol</th>
                                                        <th>Pureza</th>
                                                        <th>Rendimiento Cristal</th>
                                                        <th>Tn Masa</th>
                                                        <th>Tn/m³</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= number_format($volFt3B, 0); ?></td>
                                                        <td><?= number_format($brixB, 2); ?></td>
                                                        <td><?= number_format($resultadosMasaCocidaB['promedio_pol'], 2); ?></td>
                                                        <td><?= number_format($purezaB, 2); ?></td>
                                                        <td><?= number_format($promRendCristalB, 2); ?></td>
                                                        <td><?= number_format($tnMasaB, 3); ?></td>
                                                        <td><?= number_format($tnM3B, 5); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($resultadosMasaCocidaC && array_filter($resultadosMasaCocidaC)): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-layer-group"></i> Masa Cocida C</h4>
                                                    <tr>
                                                        <th>Volumen (ft³)</th>
                                                        <th>Brix</th>
                                                        <th>Pol</th>
                                                        <th>Pureza</th>
                                                        <th>Rendimiento Cristal</th>
                                                        <th>Tn Masa</th>
                                                        <th>Tn/m³</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= number_format($volFt3C, 0); ?></td>
                                                        <td><?= number_format($brixC, 2); ?></td>
                                                        <td><?= number_format($resultadosMasaCocidaC['promedio_pol'], 2); ?></td>
                                                        <td><?= number_format($purezaC, 2); ?></td>
                                                        <td><?= number_format($promRendCristalFinal, 2); ?></td>
                                                        <td><?= number_format($tnMasaC, 3); ?></td>
                                                        <td><?= number_format($tnM3C, 5); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($promBrixMielA || $promPolMielA || $promPurMielA): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-layer-group"></i> Miel A</h4>
                                                    <tr>
                                                        <th>Brix</th>
                                                        <th>Pol</th>
                                                        <th>Pureza</th>
                                                        <th>Agotamiento</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= number_format($promBrixMielA, 2); ?></td>
                                                        <td><?= number_format($promPolMielA, 2); ?></td>
                                                        <td><?= number_format($promPurMielA, 2); ?></td>
                                                        <td><?= number_format($promAgotamA, 2); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($promBrixMielB || $promPolMielB || $promPurMielB): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-layer-group"></i> Miel B</h4>
                                                    <tr>
                                                        <th>Brix</th>
                                                        <th>Pol</th>
                                                        <th>Pureza</th>
                                                        <th>Agotamiento</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= number_format($promBrixMielB, 2); ?></td>
                                                        <td><?= number_format($promPolMielB, 2); ?></td>
                                                        <td><?= number_format($promPurMielB, 2); ?></td>
                                                        <td><?= number_format($promAgotamB, 2); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($promBrixMielFinal || $promPolMielFinal || $promPurMielFinal || $promAzucRedMielFinal): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-layer-group"></i> Miel Final</h4>
                                                    <tr>
                                                        <th>Brix</th>
                                                        <th>Pol</th>
                                                        <th>Pureza</th>
                                                        <th>Azúcar Reducido</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= number_format($promBrixMielFinal, 2); ?></td>
                                                        <td><?= number_format($promPolMielFinal, 2); ?></td>
                                                        <td><?= number_format($promPurMielFinal, 2); ?></td>
                                                        <td><?= number_format($promAzucRedMielFinal, 2); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($promColor || $promTurbidez || $promVitaminaA || $promPolAzucar || $promHumedad || $promCenizas): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-flask"></i> Análisis Azúcar</h4>
                                                    <tr>
                                                        <th>Color</th>
                                                        <th>Turbidéz</th>
                                                        <th>Vitamina 'A'</th>
                                                        <th>Pol</th>
                                                        <th>Humedad</th>
                                                        <th>Cenizas</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= number_format($promColor, 0); ?></td>
                                                        <td><?= number_format($promTurbidez, 0); ?></td>
                                                        <td><?= number_format($promVitaminaA, 2); ?></td>
                                                        <td><?= number_format($promPolAzucar, 2); ?></td>
                                                        <td><?= number_format($promHumedad, 2); ?></td>
                                                        <td><?= number_format($promCenizas, 2); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($avgBrixMB || $avgPolMB || $promPurezaMB): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-flask"></i> Magma B</h4>
                                                    <tr>
                                                        <th>Brix</th>
                                                        <th>Pol</th>
                                                        <th>Pureza</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= number_format($avgBrixMB, 2); ?></td>
                                                        <td><?= number_format($avgPolMB, 2); ?></td>
                                                        <td><?= number_format($promPurezaMB, 2); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($avgBrixMC || $avgPolMC || $promPurezaMC): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-flask"></i> Magma C</h4>
                                                    <tr>
                                                        <th>Brix</th>
                                                        <th>Pol</th>
                                                        <th>Pureza</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= number_format($avgBrixMC, 2); ?></td>
                                                        <td><?= number_format($avgPolMC, 2); ?></td>
                                                        <td><?= number_format($promPurezaMC, 2); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($promEnfriamiento || $promRetorno || $promDesechos): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-water"></i> Efluentes</h4>
                                                    <tr>
                                                        <th>Enfriamiento</th>
                                                        <th>Retorno</th>
                                                        <th>Desechos</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= number_format($promEnfriamiento, 2); ?></td>
                                                        <td><?= number_format($promRetorno, 2); ?></td>
                                                        <td><?= number_format($promDesechos, 2); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>


                            <?php if ($totalTnCanaACHSA || $totalMoliendaTn || $totalSacos50AzucarBlanco || $totalSacosAzucarMorena || $totalJumboAzucarBlanco): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <h4 class="mb-1 text-primary"><i i class="fas fa-box"></i> Saco Azúcar</h4>
                                                    <tr>
                                                        <th>Toneladas Caña ACHSA</th>
                                                        <th>Toneladas Molienda</th>
                                                        <th>Sacos Azúcar Blanco</th>
                                                        <th>Toneladas Azúcar Blanco</th>
                                                        <th>Sacos Azúcar Morena</th>
                                                        <th>Toneladas Azúcar Morena</th>
                                                        <th>Jumbo Azúcar Blanco</th>
                                                        <th>Toneladas Azúcar Blanco (Jumbo)</th>
                                                        <th>Total Sacos 50kg</th>
                                                        <th>Toneladas de Azúcar Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= number_format($totalTnCanaACHSA, 2); ?></td>
                                                        <td><?= number_format($totalMoliendaTn, 2); ?></td>
                                                        <td><?= number_format($totalSacos50AzucarBlanco, 0); ?></td>
                                                        <td><?= number_format($tnAzucarBlanco50, 3); ?></td>
                                                        <td><?= number_format($totalSacosAzucarMorena, 0); ?></td>
                                                        <td><?= number_format($tnAzucarMorena, 3); ?></td>
                                                        <td><?= number_format($totalJumboAzucarBlanco, 0); ?></td>
                                                        <td><?= number_format($tnAzucarBlancoJumbo, 3); ?></td>
                                                        <td><?= number_format($totalSacos50AzucarBlanco + $totalSacosAzucarMorena, 0); ?></td>
                                                        <td><?= number_format($tnAzucarTotal, 3); ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (
                                ($resultadosPromedioControlPH && array_filter($resultadosPromedioControlPH)) ||
                                ($promediosAguaImbibicion && array_filter($promediosAguaImbibicion)) ||
                                ($promediosJugoMezcladoPHMBC && array_filter($promediosJugoMezcladoPHMBC)) ||
                                ($promediosBagazo && array_filter($promediosBagazo)) ||
                                ($registrosFiltroCachaza && array_filter($registrosFiltroCachaza)) ||
                                ($promediosCachaza && array_filter($promediosCachaza)) ||
                                ($registros && array_filter($registros))
                            ): ?>
                                <div class="my-4">
                                    <h3 class="text-center text-secondary"><i class="fas fa-layer-group"></i> pH, Molinos, Bagazo & Cachaza</h3>
                                    <hr class="my-4">
                                </div>
                            <?php endif; ?>

                            <?php if ($resultadosPromedioControlPH && array_filter($resultadosPromedioControlPH)): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-flask"></i> Control de PH</h4>
                                                    <tr>
                                                        <th>Primario</th>
                                                        <th>Mezclado</th>
                                                        <th>Residual</th>
                                                        <th>Sulfitado</th>
                                                        <th>Filtrado</th>
                                                        <th>Alcalizado</th>
                                                        <th>Clarificado</th>
                                                        <th>Meladura</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= is_numeric($promedioPrimario) ? number_format($promedioPrimario, 2) : '-'; ?></td>
                                                        <td><?= is_numeric($promedioMezclado) ? number_format($promedioMezclado, 2) : '-'; ?></td>
                                                        <td><?= is_numeric($promedioResidual) ? number_format($promedioResidual, 2) : '-'; ?></td>
                                                        <td><?= is_numeric($promedioSulfitado) ? number_format($promedioSulfitado, 2) : '-'; ?></td>
                                                        <td><?= is_numeric($promedioFiltrado) ? number_format($promedioFiltrado, 2) : '-'; ?></td>
                                                        <td><?= is_numeric($promedioAlcalizado) ? number_format($promedioAlcalizado, 2) : '-'; ?></td>
                                                        <td><?= is_numeric($promedioClarificado) ? number_format($promedioClarificado, 2) : '-'; ?></td>
                                                        <td><?= is_numeric($promedioMeladura) ? number_format($promedioMeladura, 2) : '-'; ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>


                            <?php if ($promediosAguaImbibicion && array_filter($promediosAguaImbibicion)): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-water"></i> Agua de Imbibición</h4>
                                                    <tr>
                                                        <th>Tn/hora</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= number_format($promediosAguaImbibicion['tnHora'], 3) ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($promediosJugoMezcladoPHMBC && array_filter($promediosJugoMezcladoPHMBC)): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-water"></i> Jugo Mezclado PH</h4>
                                                    <tr>
                                                        <th>Tn/hora</th>
                                                    </tr>
                                                </thead>
                                                <tbody>

                                                    <tr>
                                                        <td><?= number_format($promediosJugoMezcladoPHMBC['tnHora'], 3) ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($promediosBagazo && array_filter($promediosBagazo)): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-filter"></i> Bagazo</h4>
                                                    <tr>
                                                        <th>Pol</th>
                                                        <th>Humedad</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= number_format($promediosBagazo['promedio_pol'], 2) ?></td>
                                                        <td><?= number_format($promediosBagazo['promedio_humedad'], 2) ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($registrosFiltroCachaza && array_filter($registrosFiltroCachaza)): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-filter"></i> Filtro Cachaza</h4>
                                                    <tr>
                                                        <th>Pol (F1)</th>
                                                        <th>g/Ft² (F1)</th>
                                                        <th>Pol (F2)</th>
                                                        <th>g/Ft² (F2)</th>
                                                        <th>Pol (F3)</th>
                                                        <th>g/Ft² (F3)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($datosPorHoraFiltroCachaza as $hora => $registro): ?>

                                                    <?php endforeach; ?>
                                                    <tr class=>
                                                        <td><?= $promediosFiltroCachaza['Filtro 1']['pol'] ?></td>
                                                        <td><?= $promediosFiltroCachaza['Filtro 1']['gFt_ft2'] ?></td>
                                                        <td><?= $promediosFiltroCachaza['Filtro 2']['pol'] ?></td>
                                                        <td><?= $promediosFiltroCachaza['Filtro 2']['gFt_ft2'] ?></td>
                                                        <td><?= $promediosFiltroCachaza['Filtro 3']['pol'] ?></td>
                                                        <td><?= $promediosFiltroCachaza['Filtro 3']['gFt_ft2'] ?></td>

                                                    </tr>
                                                    <tr class="table-warning">
                                                        <td colspan="4"><strong>Promedio Pol Cachaza</strong></td>
                                                        <td colspan="4"><?= $promedioPolCachaza ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($promediosCachaza && array_filter($promediosCachaza)): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-filter"></i> Cachaza</h4>
                                                    <tr>
                                                        <th>Humedad</th>
                                                        <th>Fibra</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><?= number_format($promediosCachaza['promedio_humedad'], 2) ?></td>
                                                        <td><?= number_format($promediosCachaza['promedio_fibra'], 2) ?></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($registros && array_filter($registros)): ?>
                                <div class="card shadow mb-1">
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-success text-center">
                                                    <h4 class="mb-1 text-primary"><i class="fas fa-exclamation-triangle"></i> Causas</h4>
                                                    <tr>
                                                        <th>Turno</th>
                                                        <th>Tiempo Perdido</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($totalTiempoPorTurno as $turno => $tiempo): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($turno) ?></td>
                                                            <td><?= htmlspecialchars($tiempo) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    <tr class="table-warning">
                                                        <td><strong>Total del Día</strong></td>
                                                        <td><strong><?= htmlspecialchars($totalDia) ?></strong></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                        <?php endif; ?>

                        <script src="<?= BASE_PATH; ?>public/vendor/jquery/jquery.min.js"></script>
                        <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
                        <script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
                        <script src="<?= BASE_PATH; ?>public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
                        <script src="<?= BASE_PATH; ?>public/vendor/jquery-easing/jquery.easing.min.js"></script>
                        <script src="<?= BASE_PATH; ?>public/js/sb-admin-2.min.js"></script>

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