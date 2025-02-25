<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// Obtener la fecha de ingreso como objeto DateTime
$fechaIngresoObj = new DateTime($fechaIngreso);

// Día de la semana (0 = domingo, 6 = sábado)
$diaSemana = $fechaIngresoObj->format('w');

// Domingo de la semana de la fecha de ingreso
$semanaActualInicio = (clone $fechaIngresoObj)->modify('-' . $diaSemana . ' days')->setTime(0, 0, 0);

// Sábado de la semana de la fecha de ingreso
$semanaActualTermina = (clone $semanaActualInicio)->modify('+6 days')->setTime(23, 59, 59);

// Domingo de la semana anterior a la fecha de ingreso
$semanaAnteriorInicio = (clone $semanaActualInicio)->modify('-7 days');

// Sábado de la semana anterior a la fecha de ingreso
$semanaAnteriorTermina = (clone $semanaAnteriorInicio)->modify('+6 days');

// Convertir a formato de fecha para consultas (opcional)
$semanaActualInicio = $semanaActualInicio->format('Y-m-d');
$semanaActualTermina = $semanaActualTermina->format('Y-m-d');
$semanaAnteriorInicio = $semanaAnteriorInicio->format('Y-m-d');
$semanaAnteriorTermina = $semanaAnteriorTermina->format('Y-m-d');

// Mostrar resultados para depuración
//echo "Semana actual: $semanaActualInicio a $semanaActualTermina<br>";
//echo "Semana anterior: $semanaAnteriorInicio a $semanaAnteriorTermina<br>";

if (!empty($periodoZafra) && !empty($fechaIngreso)) {
    // Consultar datos de jugo primario para la semana actual (domingo a sábado)
    $queryJugoPrimario = "SELECT AVG(brix) AS avgBrixJP, AVG(sac) AS avgSacJP, COUNT(*) AS countJP FROM jugoprimario
                          WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtJugoPrimario = $conexion->prepare($queryJugoPrimario);
    $stmtJugoPrimario->bindParam(':periodoZafra', $periodoZafra);
    $stmtJugoPrimario->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtJugoPrimario->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtJugoPrimario->execute();
    $resultJugoPrimario = $stmtJugoPrimario->fetch(PDO::FETCH_ASSOC);
    $countJP = !empty($resultJugoPrimario['countJP']) ? $resultJugoPrimario['countJP'] : 1;
    $avgBrixJP = !empty($resultJugoPrimario['avgBrixJP']) ? round($resultJugoPrimario['avgBrixJP'], 2) : 0;
    $avgSacJP = !empty($resultJugoPrimario['avgSacJP']) ? round($resultJugoPrimario['avgSacJP'], 2) : 0;
    $promPurJP = ($avgBrixJP != 0) ? round(($avgSacJP * 100) / $avgBrixJP, 2) : 0;

    // Consultar datos de jugo primario para la semana anterior (domingo a sábado)
    $queryJugoPrimarioAnterior = "SELECT AVG(brix) AS avgBrixJPAnterior, AVG(sac) AS avgSacJPAnterior, COUNT(*) AS countJPAnterior FROM jugoprimario
                                  WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtJugoPrimarioAnterior = $conexion->prepare($queryJugoPrimarioAnterior);
    $stmtJugoPrimarioAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtJugoPrimarioAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtJugoPrimarioAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtJugoPrimarioAnterior->execute();
    $resultJugoPrimarioAnterior = $stmtJugoPrimarioAnterior->fetch(PDO::FETCH_ASSOC);

    $countJPAnterior = !empty($resultJugoPrimarioAnterior['countJPAnterior']) ? $resultJugoPrimarioAnterior['countJPAnterior'] : 1;
    $avgBrixJPAnterior = !empty($resultJugoPrimarioAnterior['avgBrixJPAnterior']) ? round($resultJugoPrimarioAnterior['avgBrixJPAnterior'], 2) : 0;
    $avgSacJPAnterior = !empty($resultJugoPrimarioAnterior['avgSacJPAnterior']) ? round($resultJugoPrimarioAnterior['avgSacJPAnterior'], 2) : 0;
    $promPurJPAnterior = ($avgBrixJPAnterior != 0) ? round(($avgSacJPAnterior * 100) / $avgBrixJPAnterior, 2) : 0;

    // Consultar datos de jugo mezclado para la semana actual (domingo a sábado)
    $queryJugoMezclado = "SELECT AVG(brix) AS avgBrixJM, AVG(sac) AS avgSacJM, COUNT(*) AS countJM FROM jugomezclado
                          WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtJugoMezclado = $conexion->prepare($queryJugoMezclado);
    $stmtJugoMezclado->bindParam(':periodoZafra', $periodoZafra);
    $stmtJugoMezclado->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtJugoMezclado->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtJugoMezclado->execute();
    $resultJugoMezclado = $stmtJugoMezclado->fetch(PDO::FETCH_ASSOC);
    $countJM = !empty($resultJugoMezclado['countJM']) ? $resultJugoMezclado['countJM'] : 1;
    $avgBrixJM = !empty($resultJugoMezclado['avgBrixJM']) ? round($resultJugoMezclado['avgBrixJM'], 2) : 0;
    $avgSacJM = !empty($resultJugoMezclado['avgSacJM']) ? round($resultJugoMezclado['avgSacJM'], 2) : 0;
    $promPurJM = ($avgBrixJM != 0) ? round(($avgSacJM * 100) / $avgBrixJM, 2) : 0;

    // Consultar datos de jugo mezclado para la semana anterior (domingo a sábado)
    $queryJugoMezcladoAnterior = "SELECT AVG(brix) AS avgBrixJMAnterior, AVG(sac) AS avgSacJMAnterior, COUNT(*) AS countJMAnterior FROM jugomezclado
                                  WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtJugoMezcladoAnterior = $conexion->prepare($queryJugoMezcladoAnterior);
    $stmtJugoMezcladoAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtJugoMezcladoAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtJugoMezcladoAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtJugoMezcladoAnterior->execute();
    $resultJugoMezcladoAnterior = $stmtJugoMezcladoAnterior->fetch(PDO::FETCH_ASSOC);

    $countJMAnterior = !empty($resultJugoMezcladoAnterior['countJMAnterior']) ? $resultJugoMezcladoAnterior['countJMAnterior'] : 1;
    $avgBrixJMAnterior = !empty($resultJugoMezcladoAnterior['avgBrixJMAnterior']) ? round($resultJugoMezcladoAnterior['avgBrixJMAnterior'], 2) : 0;
    $avgSacJMAnterior = !empty($resultJugoMezcladoAnterior['avgSacJMAnterior']) ? round($resultJugoMezcladoAnterior['avgSacJMAnterior'], 2) : 0;
    $promPurJMAnterior = ($avgBrixJMAnterior != 0) ? round(($avgSacJMAnterior * 100) / $avgBrixJMAnterior, 2) : 0;

    // Consultar datos de jugo residual para la semana actual (domingo a sábado)
    $queryJugoResidual = "SELECT AVG(brix) AS avgBrixJR, AVG(sac) AS avgSacJR, COUNT(*) AS countJR FROM jugoresidual
                          WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtJugoResidual = $conexion->prepare($queryJugoResidual);
    $stmtJugoResidual->bindParam(':periodoZafra', $periodoZafra);
    $stmtJugoResidual->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtJugoResidual->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtJugoResidual->execute();
    $resultJugoResidual = $stmtJugoResidual->fetch(PDO::FETCH_ASSOC);
    $countJR = !empty($resultJugoResidual['countJR']) ? $resultJugoResidual['countJR'] : 1;
    $avgBrixJR = !empty($resultJugoResidual['avgBrixJR']) ? round($resultJugoResidual['avgBrixJR'], 2) : 0;
    $avgSacJR = !empty($resultJugoResidual['avgSacJR']) ? round($resultJugoResidual['avgSacJR'], 2) : 0;
    $promPurJR = ($avgBrixJR != 0) ? round(($avgSacJR * 100) / $avgBrixJR, 2) : 0;

    // Consultar datos de jugo residual para la semana anterior (domingo a sábado)
    $queryJugoResidualAnterior = "SELECT AVG(brix) AS avgBrixJRAnterior, AVG(sac) AS avgSacJRAnterior, COUNT(*) AS countJRAnterior FROM jugoresidual
                                  WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtJugoResidualAnterior = $conexion->prepare($queryJugoResidualAnterior);
    $stmtJugoResidualAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtJugoResidualAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtJugoResidualAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtJugoResidualAnterior->execute();
    $resultJugoResidualAnterior = $stmtJugoResidualAnterior->fetch(PDO::FETCH_ASSOC);
    $countJRAnterior = !empty($resultJugoResidualAnterior['countJRAnterior']) ? $resultJugoResidualAnterior['countJRAnterior'] : 1;
    $avgBrixJRAnterior = !empty($resultJugoResidualAnterior['avgBrixJRAnterior']) ? round($resultJugoResidualAnterior['avgBrixJRAnterior'], 2) : 0;
    $avgSacJRAnterior = !empty($resultJugoResidualAnterior['avgSacJRAnterior']) ? round($resultJugoResidualAnterior['avgSacJRAnterior'], 2) : 0;
    $promPurJRAnterior = ($avgBrixJRAnterior != 0) ? round(($avgSacJRAnterior * 100) / $avgBrixJRAnterior, 2) : 0;

    // Consultar datos de jugo clarificado para la semana actual (domingo a sábado)
    $queryJugoClarificado = "SELECT AVG(brix) AS avgBrixJC, AVG(sac) AS avgSacJC, COUNT(*) AS countJC FROM jugoclarificado
                             WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtJugoClarificado = $conexion->prepare($queryJugoClarificado);
    $stmtJugoClarificado->bindParam(':periodoZafra', $periodoZafra);
    $stmtJugoClarificado->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtJugoClarificado->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtJugoClarificado->execute();
    $resultJugoClarificado = $stmtJugoClarificado->fetch(PDO::FETCH_ASSOC);
    $countJC = !empty($resultJugoClarificado['countJC']) ? $resultJugoClarificado['countJC'] : 1;
    $avgBrixJC = !empty($resultJugoClarificado['avgBrixJC']) ? round($resultJugoClarificado['avgBrixJC'], 2) : 0;
    $avgSacJC = !empty($resultJugoClarificado['avgSacJC']) ? round($resultJugoClarificado['avgSacJC'], 2) : 0;
    $promPurJC = ($avgBrixJC != 0) ? round(($avgSacJC * 100) / $avgBrixJC, 2) : 0;

    // Consultar datos de jugo clarificado para la semana anterior (domingo a sábado)
    $queryJugoClarificadoAnterior = "SELECT AVG(brix) AS avgBrixJCAnterior, AVG(sac) AS avgSacJCAnterior, COUNT(*) AS countJCAnterior FROM jugoclarificado
                                     WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtJugoClarificadoAnterior = $conexion->prepare($queryJugoClarificadoAnterior);
    $stmtJugoClarificadoAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtJugoClarificadoAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtJugoClarificadoAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtJugoClarificadoAnterior->execute();
    $resultJugoClarificadoAnterior = $stmtJugoClarificadoAnterior->fetch(PDO::FETCH_ASSOC);
    $countJCAnterior = !empty($resultJugoClarificadoAnterior['countJCAnterior']) ? $resultJugoClarificadoAnterior['countJCAnterior'] : 1;
    $avgBrixJCAnterior = !empty($resultJugoClarificadoAnterior['avgBrixJCAnterior']) ? round($resultJugoClarificadoAnterior['avgBrixJCAnterior'], 2) : 0;
    $avgSacJCAnterior = !empty($resultJugoClarificadoAnterior['avgSacJCAnterior']) ? round($resultJugoClarificadoAnterior['avgSacJCAnterior'], 2) : 0;
    $promPurJCAnterior = ($avgBrixJCAnterior != 0) ? round(($avgSacJCAnterior * 100) / $avgBrixJCAnterior, 2) : 0;

    // Consultar datos de jugo filtrado para la semana actual (domingo a sábado)
    $queryJugoFiltrado = "SELECT AVG(brix) AS avgBrixJF, AVG(sac) AS avgSacJF, COUNT(*) AS countJF FROM jugofiltrado
                          WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtJugoFiltrado = $conexion->prepare($queryJugoFiltrado);
    $stmtJugoFiltrado->bindParam(':periodoZafra', $periodoZafra);
    $stmtJugoFiltrado->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtJugoFiltrado->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtJugoFiltrado->execute();
    $resultJugoFiltrado = $stmtJugoFiltrado->fetch(PDO::FETCH_ASSOC);
    $countJF = !empty($resultJugoFiltrado['countJF']) ? $resultJugoFiltrado['countJF'] : 1;
    $avgBrixJF = !empty($resultJugoFiltrado['avgBrixJF']) ? round($resultJugoFiltrado['avgBrixJF'], 2) : 0;
    $avgSacJF = !empty($resultJugoFiltrado['avgSacJF']) ? round($resultJugoFiltrado['avgSacJF'], 2) : 0;
    $promPurJF = ($avgBrixJF != 0) ? round(($avgSacJF * 100) / $avgBrixJF, 2) : 0;

    // Consultar datos de jugo filtrado para la semana anterior (domingo a sábado)
    $queryJugoFiltradoAnterior = "SELECT AVG(brix) AS avgBrixJFAnterior, AVG(sac) AS avgSacJFAnterior, COUNT(*) AS countJFAnterior FROM jugofiltrado
                                  WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtJugoFiltradoAnterior = $conexion->prepare($queryJugoFiltradoAnterior);
    $stmtJugoFiltradoAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtJugoFiltradoAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtJugoFiltradoAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtJugoFiltradoAnterior->execute();
    $resultJugoFiltradoAnterior = $stmtJugoFiltradoAnterior->fetch(PDO::FETCH_ASSOC);
    $countJFAnterior = !empty($resultJugoFiltradoAnterior['countJFAnterior']) ? $resultJugoFiltradoAnterior['countJFAnterior'] : 1;
    $avgBrixJFAnterior = !empty($resultJugoFiltradoAnterior['avgBrixJFAnterior']) ? round($resultJugoFiltradoAnterior['avgBrixJFAnterior'], 2) : 0;
    $avgSacJFAnterior = !empty($resultJugoFiltradoAnterior['avgSacJFAnterior']) ? round($resultJugoFiltradoAnterior['avgSacJFAnterior'], 2) : 0;
    $promPurJFAnterior = ($avgBrixJFAnterior != 0) ? round(($avgSacJFAnterior * 100) / $avgBrixJFAnterior, 2) : 0;

    // Consultar datos de meladura para la semana actual (domingo a sábado)
    $queryMeladura = "SELECT AVG(brix) AS avgBrixMeladura, AVG(sac) AS avgSacMeladura, COUNT(*) AS countMeladura FROM meladura
                      WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtMeladura = $conexion->prepare($queryMeladura);
    $stmtMeladura->bindParam(':periodoZafra', $periodoZafra);
    $stmtMeladura->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtMeladura->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtMeladura->execute();
    $resultMeladura = $stmtMeladura->fetch(PDO::FETCH_ASSOC);
    $countMeladura = !empty($resultMeladura['countMeladura']) ? $resultMeladura['countMeladura'] : 1;
    $avgBrixMeladura = !empty($resultMeladura['avgBrixMeladura']) ? round($resultMeladura['avgBrixMeladura'], 2) : 0;
    $avgSacMeladura = !empty($resultMeladura['avgSacMeladura']) ? round($resultMeladura['avgSacMeladura'], 2) : 0;
    $promPurMeladura = ($avgBrixMeladura != 0) ? round(($avgSacMeladura * 100) / $avgBrixMeladura, 2) : 0;

    // Consultar datos de meladura para la semana anterior (domingo a sábado)
    $queryMeladuraAnterior = "SELECT AVG(brix) AS avgBrixMeladuraAnterior, AVG(sac) AS avgSacMeladuraAnterior, COUNT(*) AS countMeladuraAnterior FROM meladura
                              WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtMeladuraAnterior = $conexion->prepare($queryMeladuraAnterior);
    $stmtMeladuraAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtMeladuraAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtMeladuraAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtMeladuraAnterior->execute();
    $resultMeladuraAnterior = $stmtMeladuraAnterior->fetch(PDO::FETCH_ASSOC);
    $countMeladuraAnterior = !empty($resultMeladuraAnterior['countMeladuraAnterior']) ? $resultMeladuraAnterior['countMeladuraAnterior'] : 1;
    $avgBrixMeladuraAnterior = !empty($resultMeladuraAnterior['avgBrixMeladuraAnterior']) ? round($resultMeladuraAnterior['avgBrixMeladuraAnterior'], 2) : 0;
    $avgSacMeladuraAnterior = !empty($resultMeladuraAnterior['avgSacMeladuraAnterior']) ? round($resultMeladuraAnterior['avgSacMeladuraAnterior'], 2) : 0;
    $promPurMeladuraAnterior = ($avgBrixMeladuraAnterior != 0) ? round(($avgSacMeladuraAnterior * 100) / $avgBrixMeladuraAnterior, 2) : 0;

    // Consultar datos de masa cocida A para la semana actual (domingo a sábado)
    $queryMasaCocidaA = "SELECT AVG(brix) AS avgBrixMCA, AVG(pol) AS avgPolMCA, COUNT(*) AS countMCA FROM masacocidaA
                         WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtMasaCocidaA = $conexion->prepare($queryMasaCocidaA);
    $stmtMasaCocidaA->bindParam(':periodoZafra', $periodoZafra);
    $stmtMasaCocidaA->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtMasaCocidaA->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtMasaCocidaA->execute();
    $resultMasaCocidaA = $stmtMasaCocidaA->fetch(PDO::FETCH_ASSOC);
    $countMCA = !empty($resultMasaCocidaA['countMCA']) ? $resultMasaCocidaA['countMCA'] : 1;
    $avgBrixMCA = !empty($resultMasaCocidaA['avgBrixMCA']) ? round($resultMasaCocidaA['avgBrixMCA'], 2) : 0;
    $avgPolMCA = !empty($resultMasaCocidaA['avgPolMCA']) ? round($resultMasaCocidaA['avgPolMCA'], 2) : 0;
    $promPurMCA = ($avgBrixMCA != 0) ? round(($avgPolMCA * 100) / $avgBrixMCA, 2) : 0;

    // Consultar datos de masa cocida A para la semana anterior (domingo a sábado)
    $queryMasaCocidaAAnterior = "SELECT AVG(brix) AS avgBrixMCAAnterior, AVG(pol) AS avgPolMCAAnterior, COUNT(*) AS countMCAAnterior FROM masacocidaA
                                 WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtMasaCocidaAAnterior = $conexion->prepare($queryMasaCocidaAAnterior);
    $stmtMasaCocidaAAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtMasaCocidaAAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtMasaCocidaAAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtMasaCocidaAAnterior->execute();
    $resultMasaCocidaAAnterior = $stmtMasaCocidaAAnterior->fetch(PDO::FETCH_ASSOC);
    $countMCAAnterior = !empty($resultMasaCocidaAAnterior['countMCAAnterior']) ? $resultMasaCocidaAAnterior['countMCAAnterior'] : 1;
    $avgBrixMCAAnterior = !empty($resultMasaCocidaAAnterior['avgBrixMCAAnterior']) ? round($resultMasaCocidaAAnterior['avgBrixMCAAnterior'], 2) : 0;
    $avgPolMCAAnterior = !empty($resultMasaCocidaAAnterior['avgPolMCAAnterior']) ? round($resultMasaCocidaAAnterior['avgPolMCAAnterior'], 2) : 0;
    $promPurMCAAnterior = ($avgBrixMCAAnterior != 0) ? round(($avgPolMCAAnterior * 100) / $avgBrixMCAAnterior, 2) : 0;

    // Consultar datos de masa cocida B para la semana actual (domingo a sábado)
    $queryMasaCocidaB = "SELECT AVG(brix) AS avgBrixMCB, AVG(pol) AS avgPolMCB, COUNT(*) AS countMCB FROM masacocidaB
                         WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtMasaCocidaB = $conexion->prepare($queryMasaCocidaB);
    $stmtMasaCocidaB->bindParam(':periodoZafra', $periodoZafra);
    $stmtMasaCocidaB->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtMasaCocidaB->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtMasaCocidaB->execute();
    $resultMasaCocidaB = $stmtMasaCocidaB->fetch(PDO::FETCH_ASSOC);
    $countMCB = !empty($resultMasaCocidaB['countMCB']) ? $resultMasaCocidaB['countMCB'] : 1;
    $avgBrixMCB = !empty($resultMasaCocidaB['avgBrixMCB']) ? round($resultMasaCocidaB['avgBrixMCB'], 2) : 0;
    $avgPolMCB = !empty($resultMasaCocidaB['avgPolMCB']) ? round($resultMasaCocidaB['avgPolMCB'], 2) : 0;
    $promPurMCB = ($avgBrixMCB != 0) ? round(($avgPolMCB * 100) / $avgBrixMCB, 2) : 0;

    // Consultar datos de masa cocida B para la semana anterior (domingo a sábado)
    $queryMasaCocidaBAnterior = "SELECT AVG(brix) AS avgBrixMCBAnterior, AVG(pol) AS avgPolMCBAnterior, COUNT(*) AS countMCBAnterior FROM masacocidaB
                                 WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtMasaCocidaBAnterior = $conexion->prepare($queryMasaCocidaBAnterior);
    $stmtMasaCocidaBAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtMasaCocidaBAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtMasaCocidaBAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtMasaCocidaBAnterior->execute();
    $resultMasaCocidaBAnterior = $stmtMasaCocidaBAnterior->fetch(PDO::FETCH_ASSOC);
    $countMCBAnterior = !empty($resultMasaCocidaBAnterior['countMCBAnterior']) ? $resultMasaCocidaBAnterior['countMCBAnterior'] : 1;
    $avgBrixMCBAnterior = !empty($resultMasaCocidaBAnterior['avgBrixMCBAnterior']) ? round($resultMasaCocidaBAnterior['avgBrixMCBAnterior'], 2) : 0;
    $avgPolMCBAnterior = !empty($resultMasaCocidaBAnterior['avgPolMCBAnterior']) ? round($resultMasaCocidaBAnterior['avgPolMCBAnterior'], 2) : 0;
    $promPurMCBAnterior = ($avgBrixMCBAnterior != 0) ? round(($avgPolMCBAnterior * 100) / $avgBrixMCBAnterior, 2) : 0;

    // Consultar datos de masa cocida C para la semana actual (domingo a sábado)
    $queryMasaCocidaC = "SELECT AVG(brix) AS avgBrixMCC, AVG(pol) AS avgPolMCC, COUNT(*) AS countMCC FROM masacocidaC
                         WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtMasaCocidaC = $conexion->prepare($queryMasaCocidaC);
    $stmtMasaCocidaC->bindParam(':periodoZafra', $periodoZafra);
    $stmtMasaCocidaC->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtMasaCocidaC->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtMasaCocidaC->execute();
    $resultMasaCocidaC = $stmtMasaCocidaC->fetch(PDO::FETCH_ASSOC);
    $countMCC = !empty($resultMasaCocidaC['countMCC']) ? $resultMasaCocidaC['countMCC'] : 1;
    $avgBrixMCC = !empty($resultMasaCocidaC['avgBrixMCC']) ? round($resultMasaCocidaC['avgBrixMCC'], 2) : 0;
    $avgPolMCC = !empty($resultMasaCocidaC['avgPolMCC']) ? round($resultMasaCocidaC['avgPolMCC'], 2) : 0;
    $promPurMCC = ($avgBrixMCC != 0) ? round(($avgPolMCC * 100) / $avgBrixMCC, 2) : 0;

    // Consultar datos de masa cocida C para la semana anterior (domingo a sábado)
    $queryMasaCocidaCAnterior = "SELECT AVG(brix) AS avgBrixMCCAnterior, AVG(pol) AS avgPolMCCAnterior, COUNT(*) AS countMCCAnterior FROM masacocidaC
                                 WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtMasaCocidaCAnterior = $conexion->prepare($queryMasaCocidaCAnterior);
    $stmtMasaCocidaCAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtMasaCocidaCAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtMasaCocidaCAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtMasaCocidaCAnterior->execute();
    $resultMasaCocidaCAnterior = $stmtMasaCocidaCAnterior->fetch(PDO::FETCH_ASSOC);
    $countMCCAnterior = !empty($resultMasaCocidaCAnterior['countMCCAnterior']) ? $resultMasaCocidaCAnterior['countMCCAnterior'] : 1;
    $avgBrixMCCAnterior = !empty($resultMasaCocidaCAnterior['avgBrixMCCAnterior']) ? round($resultMasaCocidaCAnterior['avgBrixMCCAnterior'], 2) : 0;
    $avgPolMCCAnterior = !empty($resultMasaCocidaCAnterior['avgPolMCCAnterior']) ? round($resultMasaCocidaCAnterior['avgPolMCCAnterior'], 2) : 0;
    $promPurMCCAnterior = ($avgBrixMCCAnterior != 0) ? round(($avgPolMCCAnterior * 100) / $avgBrixMCCAnterior, 2) : 0;

    // Consultar datos de miel A para la semana actual (domingo a sábado)
    $queryMielA = "SELECT AVG(brix) AS avgBrixMielA, AVG(pol) AS avgPolMielA, COUNT(*) AS countMielA FROM mielA
                   WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtMielA = $conexion->prepare($queryMielA);
    $stmtMielA->bindParam(':periodoZafra', $periodoZafra);
    $stmtMielA->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtMielA->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtMielA->execute();
    $resultMielA = $stmtMielA->fetch(PDO::FETCH_ASSOC);
    $countMielA = !empty($resultMielA['countMielA']) ? $resultMielA['countMielA'] : 1;
    $avgBrixMielA = !empty($resultMielA['avgBrixMielA']) ? round($resultMielA['avgBrixMielA'], 2) : 0;
    $avgPolMielA = !empty($resultMielA['avgPolMielA']) ? round($resultMielA['avgPolMielA'], 2) : 0;
    $promPurMielA = ($avgBrixMielA != 0) ? round(($avgPolMielA * 100) / $avgBrixMielA, 2) : 0;

    // Consultar datos de miel A para la semana anterior (domingo a sábado)
    $queryMielAAnterior = "SELECT AVG(brix) AS avgBrixMielAAnterior, AVG(pol) AS avgPolMielAAnterior, COUNT(*) AS countMielAAnterior FROM mielA
                           WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtMielAAnterior = $conexion->prepare($queryMielAAnterior);
    $stmtMielAAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtMielAAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtMielAAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtMielAAnterior->execute();
    $resultMielAAnterior = $stmtMielAAnterior->fetch(PDO::FETCH_ASSOC);
    $countMielAAnterior = !empty($resultMielAAnterior['countMielAAnterior']) ? $resultMielAAnterior['countMielAAnterior'] : 1;
    $avgBrixMielAAnterior = !empty($resultMielAAnterior['avgBrixMielAAnterior']) ? round($resultMielAAnterior['avgBrixMielAAnterior'], 2) : 0;
    $avgPolMielAAnterior = !empty($resultMielAAnterior['avgPolMielAAnterior']) ? round($resultMielAAnterior['avgPolMielAAnterior'], 2) : 0;
    $promPurMielAAnterior = ($avgBrixMielAAnterior != 0) ? round(($avgPolMielAAnterior * 100) / $avgBrixMielAAnterior, 2) : 0;

    // Consultar datos de miel B para la semana actual (domingo a sábado)
    $queryMielB = "SELECT AVG(brix) AS avgBrixMielB, AVG(pol) AS avgPolMielB, COUNT(*) AS countMielB FROM mielB
                   WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtMielB = $conexion->prepare($queryMielB);
    $stmtMielB->bindParam(':periodoZafra', $periodoZafra);
    $stmtMielB->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtMielB->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtMielB->execute();
    $resultMielB = $stmtMielB->fetch(PDO::FETCH_ASSOC);
    $countMielB = !empty($resultMielB['countMielB']) ? $resultMielB['countMielB'] : 1;
    $avgBrixMielB = !empty($resultMielB['avgBrixMielB']) ? round($resultMielB['avgBrixMielB'], 2) : 0;
    $avgPolMielB = !empty($resultMielB['avgPolMielB']) ? round($resultMielB['avgPolMielB'], 2) : 0;
    $promPurMielB = ($avgBrixMielB != 0) ? round(($avgPolMielB * 100) / $avgBrixMielB, 2) : 0;

    // Consultar datos de miel B para la semana anterior (domingo a sábado)
    $queryMielBAnterior = "SELECT AVG(brix) AS avgBrixMielBAnterior, AVG(pol) AS avgPolMielBAnterior, COUNT(*) AS countMielBAnterior FROM mielB
                           WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtMielBAnterior = $conexion->prepare($queryMielBAnterior);
    $stmtMielBAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtMielBAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtMielBAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtMielBAnterior->execute();
    $resultMielBAnterior = $stmtMielBAnterior->fetch(PDO::FETCH_ASSOC);
    $countMielBAnterior = !empty($resultMielBAnterior['countMielBAnterior']) ? $resultMielBAnterior['countMielBAnterior'] : 1;
    $avgBrixMielBAnterior = !empty($resultMielBAnterior['avgBrixMielBAnterior']) ? round($resultMielBAnterior['avgBrixMielBAnterior'], 2) : 0;
    $avgPolMielBAnterior = !empty($resultMielBAnterior['avgPolMielBAnterior']) ? round($resultMielBAnterior['avgPolMielBAnterior'], 2) : 0;
    $promPurMielBAnterior = ($avgBrixMielBAnterior != 0) ? round(($avgPolMielBAnterior * 100) / $avgBrixMielBAnterior, 2) : 0;

    // Consultar datos de miel final para la semana actual (domingo a sábado)
    $queryMielFinal = "SELECT AVG(brix) AS avgBrixMielFinal, AVG(pol) AS avgPolMielFinal, COUNT(*) AS countMielFinal FROM mielFinal
                       WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtMielFinal = $conexion->prepare($queryMielFinal);
    $stmtMielFinal->bindParam(':periodoZafra', $periodoZafra);
    $stmtMielFinal->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtMielFinal->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtMielFinal->execute();
    $resultMielFinal = $stmtMielFinal->fetch(PDO::FETCH_ASSOC);
    $countMielFinal = !empty($resultMielFinal['countMielFinal']) ? $resultMielFinal['countMielFinal'] : 1;
    $avgBrixMielFinal = !empty($resultMielFinal['avgBrixMielFinal']) ? round($resultMielFinal['avgBrixMielFinal'], 2) : 0;
    $avgPolMielFinal = !empty($resultMielFinal['avgPolMielFinal']) ? round($resultMielFinal['avgPolMielFinal'], 2) : 0;
    $promPurMielFinal = ($avgBrixMielFinal != 0) ? round(($avgPolMielFinal * 100) / $avgBrixMielFinal, 2) : 0;

    // Consultar datos de miel final para la semana anterior (domingo a sábado)
    $queryMielFinalAnterior = "SELECT AVG(brix) AS avgBrixMielFinalAnterior, AVG(pol) AS avgPolMielFinalAnterior, COUNT(*) AS countMielFinalAnterior FROM mielFinal
                               WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtMielFinalAnterior = $conexion->prepare($queryMielFinalAnterior);
    $stmtMielFinalAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtMielFinalAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtMielFinalAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtMielFinalAnterior->execute();
    $resultMielFinalAnterior = $stmtMielFinalAnterior->fetch(PDO::FETCH_ASSOC);
    $countMielFinalAnterior = !empty($resultMielFinalAnterior['countMielFinalAnterior']) ? $resultMielFinalAnterior['countMielFinalAnterior'] : 1;
    $avgBrixMielFinalAnterior = !empty($resultMielFinalAnterior['avgBrixMielFinalAnterior']) ? round($resultMielFinalAnterior['avgBrixMielFinalAnterior'], 2) : 0;
    $avgPolMielFinalAnterior = !empty($resultMielFinalAnterior['avgPolMielFinalAnterior']) ? round($resultMielFinalAnterior['avgPolMielFinalAnterior'], 2) : 0;
    $promPurMielFinalAnterior = ($avgBrixMielFinalAnterior != 0) ? round(($avgPolMielFinalAnterior * 100) / $avgBrixMielFinalAnterior, 2) : 0;

    // Consultar datos de magma B para la semana actual (domingo a sábado)
    $queryMagmaB = "SELECT AVG(brix) AS avgBrixMagmaB, AVG(pol) AS avgPolMagmaB, COUNT(*) AS countMagmaB FROM magmaB
                    WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtMagmaB = $conexion->prepare($queryMagmaB);
    $stmtMagmaB->bindParam(':periodoZafra', $periodoZafra);
    $stmtMagmaB->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtMagmaB->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtMagmaB->execute();
    $resultMagmaB = $stmtMagmaB->fetch(PDO::FETCH_ASSOC);
    $countMagmaB = !empty($resultMagmaB['countMagmaB']) ? $resultMagmaB['countMagmaB'] : 1;
    $avgBrixMagmaB = !empty($resultMagmaB['avgBrixMagmaB']) ? round($resultMagmaB['avgBrixMagmaB'], 2) : 0;
    $avgPolMagmaB = !empty($resultMagmaB['avgPolMagmaB']) ? round($resultMagmaB['avgPolMagmaB'], 2) : 0;
    $promPurMagmaB = ($avgBrixMagmaB != 0) ? round(($avgPolMagmaB * 100) / $avgBrixMagmaB, 2) : 0;

    // Consultar datos de magma B para la semana anterior (domingo a sábado)
    $queryMagmaBAnterior = "SELECT AVG(brix) AS avgBrixMagmaBAnterior, AVG(pol) AS avgPolMagmaBAnterior, COUNT(*) AS countMagmaBAnterior FROM magmaB
                            WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtMagmaBAnterior = $conexion->prepare($queryMagmaBAnterior);
    $stmtMagmaBAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtMagmaBAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtMagmaBAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtMagmaBAnterior->execute();
    $resultMagmaBAnterior = $stmtMagmaBAnterior->fetch(PDO::FETCH_ASSOC);
    $countMagmaBAnterior = !empty($resultMagmaBAnterior['countMagmaBAnterior']) ? $resultMagmaBAnterior['countMagmaBAnterior'] : 1;
    $avgBrixMagmaBAnterior = !empty($resultMagmaBAnterior['avgBrixMagmaBAnterior']) ? round($resultMagmaBAnterior['avgBrixMagmaBAnterior'], 2) : 0;
    $avgPolMagmaBAnterior = !empty($resultMagmaBAnterior['avgPolMagmaBAnterior']) ? round($resultMagmaBAnterior['avgPolMagmaBAnterior'], 2) : 0;
    $promPurMagmaBAnterior = ($avgBrixMagmaBAnterior != 0) ? round(($avgPolMagmaBAnterior * 100) / $avgBrixMagmaBAnterior, 2) : 0;

    // Consultar datos de magma C para la semana actual (domingo a sábado)
    $queryMagmaC = "SELECT AVG(brix) AS avgBrixMagmaC, AVG(pol) AS avgPolMagmaC, COUNT(*) AS countMagmaC FROM magmaC
                    WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtMagmaC = $conexion->prepare($queryMagmaC);
    $stmtMagmaC->bindParam(':periodoZafra', $periodoZafra);
    $stmtMagmaC->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtMagmaC->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtMagmaC->execute();
    $resultMagmaC = $stmtMagmaC->fetch(PDO::FETCH_ASSOC);
    $countMagmaC = !empty($resultMagmaC['countMagmaC']) ? $resultMagmaC['countMagmaC'] : 1;
    $avgBrixMagmaC = !empty($resultMagmaC['avgBrixMagmaC']) ? round($resultMagmaC['avgBrixMagmaC'], 2) : 0;
    $avgPolMagmaC = !empty($resultMagmaC['avgPolMagmaC']) ? round($resultMagmaC['avgPolMagmaC'], 2) : 0;
    $promPurMagmaC = ($avgBrixMagmaC != 0) ? round(($avgPolMagmaC * 100) / $avgBrixMagmaC, 2) : 0;

    // Consultar datos de magma C para la semana anterior (domingo a sábado)
    $queryMagmaCAnterior = "SELECT AVG(brix) AS avgBrixMagmaCAnterior, AVG(pol) AS avgPolMagmaCAnterior, COUNT(*) AS countMagmaCAnterior FROM magmaC
                            WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtMagmaCAnterior = $conexion->prepare($queryMagmaCAnterior);
    $stmtMagmaCAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtMagmaCAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtMagmaCAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtMagmaCAnterior->execute();
    $resultMagmaCAnterior = $stmtMagmaCAnterior->fetch(PDO::FETCH_ASSOC);
    $countMagmaCAnterior = !empty($resultMagmaCAnterior['countMagmaCAnterior']) ? $resultMagmaCAnterior['countMagmaCAnterior'] : 1;
    $avgBrixMagmaCAnterior = !empty($resultMagmaCAnterior['avgBrixMagmaCAnterior']) ? round($resultMagmaCAnterior['avgBrixMagmaCAnterior'], 2) : 0;
    $avgPolMagmaCAnterior = !empty($resultMagmaCAnterior['avgPolMagmaCAnterior']) ? round($resultMagmaCAnterior['avgPolMagmaCAnterior'], 2) : 0;
    $promPurMagmaCAnterior = ($avgBrixMagmaCAnterior != 0) ? round(($avgPolMagmaCAnterior * 100) / $avgBrixMagmaCAnterior, 2) : 0;

    // Consultar datos de análisis de azúcar para la semana actual (domingo a sábado)
    $queryAnalisisAzucar = "SELECT 
                            AVG(CASE WHEN vitaminaA > 0 THEN vitaminaA ELSE NULL END) as avgVitaminaA, 
                            AVG(CASE WHEN pol > 0 THEN pol ELSE NULL END) as avgPol, 
                            AVG(CASE WHEN humedad > 0 THEN humedad ELSE NULL END) as avgHumedad, 
                            AVG(CASE WHEN cenizas > 0 THEN cenizas ELSE NULL END) as avgCenizas
                            FROM analisisazucar 
                            WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtAnalisisAzucar = $conexion->prepare($queryAnalisisAzucar);
    $stmtAnalisisAzucar->bindParam(':periodoZafra', $periodoZafra);
    $stmtAnalisisAzucar->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtAnalisisAzucar->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtAnalisisAzucar->execute();
    $resultAnalisisAzucar = $stmtAnalisisAzucar->fetch(PDO::FETCH_ASSOC);
    $avgVitaminaA = !empty($resultAnalisisAzucar['avgVitaminaA']) ? round($resultAnalisisAzucar['avgVitaminaA'], 2) : 0;
    $avgPol = !empty($resultAnalisisAzucar['avgPol']) ? round($resultAnalisisAzucar['avgPol'], 2) : 0;
    $avgHumedad = !empty($resultAnalisisAzucar['avgHumedad']) ? round($resultAnalisisAzucar['avgHumedad'], 2) : 0;
    $avgCenizas = !empty($resultAnalisisAzucar['avgCenizas']) ? round($resultAnalisisAzucar['avgCenizas'], 2) : 0;

    // Consultar datos de análisis de azúcar para la semana anterior (domingo a sábado)
    $queryAnalisisAzucarAnterior = "SELECT 
                                    AVG(CASE WHEN vitaminaA > 0 THEN vitaminaA ELSE NULL END) as avgVitaminaAAnterior, 
                                    AVG(CASE WHEN pol > 0 THEN pol ELSE NULL END) as avgPolAnterior, 
                                    AVG(CASE WHEN humedad > 0 THEN humedad ELSE NULL END) as avgHumedadAnterior, 
                                    AVG(CASE WHEN cenizas > 0 THEN cenizas ELSE NULL END) as avgCenizasAnterior
                                    FROM analisisazucar 
                                    WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtAnalisisAzucarAnterior = $conexion->prepare($queryAnalisisAzucarAnterior);
    $stmtAnalisisAzucarAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtAnalisisAzucarAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtAnalisisAzucarAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtAnalisisAzucarAnterior->execute();
    $resultAnalisisAzucarAnterior = $stmtAnalisisAzucarAnterior->fetch(PDO::FETCH_ASSOC);
    $avgVitaminaAAnterior = !empty($resultAnalisisAzucarAnterior['avgVitaminaAAnterior']) ? round($resultAnalisisAzucarAnterior['avgVitaminaAAnterior'], 2) : 0;
    $avgPolAnterior = !empty($resultAnalisisAzucarAnterior['avgPolAnterior']) ? round($resultAnalisisAzucarAnterior['avgPolAnterior'], 2) : 0;
    $avgHumedadAnterior = !empty($resultAnalisisAzucarAnterior['avgHumedadAnterior']) ? round($resultAnalisisAzucarAnterior['avgHumedadAnterior'], 2) : 0;
    $avgCenizasAnterior = !empty($resultAnalisisAzucarAnterior['avgCenizasAnterior']) ? round($resultAnalisisAzucarAnterior['avgCenizasAnterior'], 2) : 0;

    // Consultar datos de efluentes para la semana actual (domingo a sábado)
    $queryEfluentes = "SELECT 
                       AVG(CASE WHEN enfriamiento > 0 THEN enfriamiento ELSE NULL END) as avgEnfriamiento, 
                       AVG(CASE WHEN retorno > 0 THEN retorno ELSE NULL END) as avgRetorno, 
                       AVG(CASE WHEN desechos > 0 THEN desechos ELSE NULL END) as avgDesechos
                       FROM efluentes 
                       WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtEfluentes = $conexion->prepare($queryEfluentes);
    $stmtEfluentes->bindParam(':periodoZafra', $periodoZafra);
    $stmtEfluentes->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtEfluentes->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtEfluentes->execute();
    $resultEfluentes = $stmtEfluentes->fetch(PDO::FETCH_ASSOC);
    $avgEnfriamiento = !empty($resultEfluentes['avgEnfriamiento']) ? round($resultEfluentes['avgEnfriamiento'], 2) : 0;
    $avgRetorno = !empty($resultEfluentes['avgRetorno']) ? round($resultEfluentes['avgRetorno'], 2) : 0;
    $avgDesechos = !empty($resultEfluentes['avgDesechos']) ? round($resultEfluentes['avgDesechos'], 2) : 0;

    // Consultar datos de efluentes para la semana anterior (domingo a sábado)
    $queryEfluentesAnterior = "SELECT 
                               AVG(CASE WHEN enfriamiento > 0 THEN enfriamiento ELSE NULL END) as avgEnfriamientoAnterior, 
                               AVG(CASE WHEN retorno > 0 THEN retorno ELSE NULL END) as avgRetornoAnterior, 
                               AVG(CASE WHEN desechos > 0 THEN desechos ELSE NULL END) as avgDesechosAnterior
                               FROM efluentes 
                               WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtEfluentesAnterior = $conexion->prepare($queryEfluentesAnterior);
    $stmtEfluentesAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtEfluentesAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtEfluentesAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtEfluentesAnterior->execute();
    $resultEfluentesAnterior = $stmtEfluentesAnterior->fetch(PDO::FETCH_ASSOC);
    $avgEnfriamientoAnterior = !empty($resultEfluentesAnterior['avgEnfriamientoAnterior']) ? round($resultEfluentesAnterior['avgEnfriamientoAnterior'], 2) : 0;
    $avgRetornoAnterior = !empty($resultEfluentesAnterior['avgRetornoAnterior']) ? round($resultEfluentesAnterior['avgRetornoAnterior'], 2) : 0;
    $avgDesechosAnterior = !empty($resultEfluentesAnterior['avgDesechosAnterior']) ? round($resultEfluentesAnterior['avgDesechosAnterior'], 2) : 0;

    // Consultar datos de saco azúcar para la semana actual (domingo a sábado)
    $querySacoAzucar = "SELECT 
        ROUND(SUM(tnCanaACHSA), 2) AS total_tn_cana_achsa, 
        ROUND(SUM(moliendaTn), 2) AS total_molienda_tn, 
        ROUND(SUM(sacos50AzucarBlanco), 2) AS total_sacos_50_azucar_blanco, 
        ROUND(SUM(sacosAzucarMorena), 2) AS total_sacos_azucar_morena, 
        ROUND(SUM(jumboAzucarBlanco), 2) AS total_jumbo_azucar_blanco
    FROM sacoazucar
    WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";

    $stmtSacoAzucar = $conexion->prepare($querySacoAzucar);
    $stmtSacoAzucar->bindParam(':periodoZafra', $periodoZafra);
    $stmtSacoAzucar->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtSacoAzucar->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtSacoAzucar->execute();
    $resultadosSacoAzucar = $stmtSacoAzucar->fetch(PDO::FETCH_ASSOC);

    $totalTnCanaACHSA = $resultadosSacoAzucar['total_tn_cana_achsa'] ?? 0;
    $totalMoliendaTn = $resultadosSacoAzucar['total_molienda_tn'] ?? 0;
    $totalSacos50AzucarBlanco = $resultadosSacoAzucar['total_sacos_50_azucar_blanco'] ?? 0;
    $totalSacosAzucarMorena = $resultadosSacoAzucar['total_sacos_azucar_morena'] ?? 0;
    $totalJumboAzucarBlanco = $resultadosSacoAzucar['total_jumbo_azucar_blanco'] ?? 0;

    $totalToneladasAzucar = number_format(($totalSacos50AzucarBlanco / 20) + ($totalSacosAzucarMorena / 20) + ($totalJumboAzucarBlanco * 1.25), 3);
    $sacos50kg = ($totalSacos50AzucarBlanco + $totalSacosAzucarMorena + $totalJumboAzucarBlanco * 25);
    $rendimiento = ($totalMoliendaTn > 0) ? number_format(($sacos50kg * 50) / $totalMoliendaTn, 2) : 0;

    $sacoAzucarData = [
        'tnCanaACHSA' => $totalTnCanaACHSA,
        'moliendaTn' => $totalMoliendaTn,
        'sacos50AzucarBlanco' => $totalSacos50AzucarBlanco,
        'sacosAzucarMorena' => $totalSacosAzucarMorena,
        'jumboAzucarBlanco' => $totalJumboAzucarBlanco,
        'sacos50kg' => $sacos50kg,
        'totalToneladasAzucar' => $totalToneladasAzucar,
        'rendimiento' => $rendimiento
    ];

    // Consultar datos de saco azúcar para la semana anterior (domingo a sábado)
    $querySacoAzucarAnterior = "SELECT 
        ROUND(SUM(tnCanaACHSA), 2) AS total_tn_cana_achsa, 
        ROUND(SUM(moliendaTn), 2) AS total_molienda_tn, 
        ROUND(SUM(sacos50AzucarBlanco), 2) AS total_sacos_50_azucar_blanco, 
        ROUND(SUM(sacosAzucarMorena), 2) AS total_sacos_azucar_morena, 
        ROUND(SUM(jumboAzucarBlanco), 2) AS total_jumbo_azucar_blanco
    FROM sacoazucar
    WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";

    $stmtSacoAzucarAnterior = $conexion->prepare($querySacoAzucarAnterior);
    $stmtSacoAzucarAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtSacoAzucarAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtSacoAzucarAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtSacoAzucarAnterior->execute();
    $resultadosSacoAzucarAnterior = $stmtSacoAzucarAnterior->fetch(PDO::FETCH_ASSOC);

    $totalTnCanaACHSAAnterior = $resultadosSacoAzucarAnterior['total_tn_cana_achsa'] ?? 0;
    $totalMoliendaTnAnterior = $resultadosSacoAzucarAnterior['total_molienda_tn'] ?? 0;
    $totalSacos50AzucarBlancoAnterior = $resultadosSacoAzucarAnterior['total_sacos_50_azucar_blanco'] ?? 0;
    $totalSacosAzucarMorenaAnterior = $resultadosSacoAzucarAnterior['total_sacos_azucar_morena'] ?? 0;
    $totalJumboAzucarBlancoAnterior = $resultadosSacoAzucarAnterior['total_jumbo_azucar_blanco'] ?? 0;

    $totalToneladasAzucarAnterior = number_format(($totalSacos50AzucarBlancoAnterior / 20) + ($totalSacosAzucarMorenaAnterior / 20) + ($totalJumboAzucarBlancoAnterior * 1.25), 3);
    $sacos50kgAnterior = ($totalSacos50AzucarBlancoAnterior + $totalSacosAzucarMorenaAnterior + $totalJumboAzucarBlancoAnterior * 25);
    $rendimientoAnterior = ($totalMoliendaTnAnterior > 0) ? number_format(($sacos50kgAnterior * 50) / $totalMoliendaTnAnterior, 2) : 0;

    $sacoAzucarDataAnterior = [
        'tnCanaACHSA' => $totalTnCanaACHSAAnterior,
        'moliendaTn' => $totalMoliendaTnAnterior,
        'sacos50AzucarBlanco' => $totalSacos50AzucarBlancoAnterior,
        'sacosAzucarMorena' => $totalSacosAzucarMorenaAnterior,
        'jumboAzucarBlanco' => $totalJumboAzucarBlancoAnterior,
        'sacos50kg' => $sacos50kgAnterior,
        'totalToneladasAzucar' => $totalToneladasAzucarAnterior,
        'rendimiento' => $rendimientoAnterior
    ];

    // Consultar datos de control pH para la semana actual (domingo a sábado)
    $queryPHActual = "SELECT 
        SUM(CASE WHEN primario > 0 THEN primario ELSE 0 END) as sumPrimario, 
        SUM(CASE WHEN mezclado > 0 THEN mezclado ELSE 0 END) as sumMezclado, 
        SUM(CASE WHEN residual > 0 THEN residual ELSE 0 END) as sumResidual, 
        SUM(CASE WHEN sulfitado > 0 THEN sulfitado ELSE 0 END) as sumSulfitado, 
        SUM(CASE WHEN filtrado > 0 THEN filtrado ELSE 0 END) as sumFiltrado, 
        SUM(CASE WHEN alcalizado > 0 THEN alcalizado ELSE 0 END) as sumAlcalizado, 
        SUM(CASE WHEN clarificado > 0 THEN clarificado ELSE 0 END) as sumClarificado, 
        SUM(CASE WHEN meladura > 0 THEN meladura ELSE 0 END) as sumMeladura, 
        COUNT(CASE WHEN primario > 0 THEN 1 ELSE NULL END) as countPrimario, 
        COUNT(CASE WHEN mezclado > 0 THEN 1 ELSE NULL END) as countMezclado, 
        COUNT(CASE WHEN residual > 0 THEN 1 ELSE NULL END) as countResidual, 
        COUNT(CASE WHEN sulfitado > 0 THEN 1 ELSE NULL END) as countSulfitado, 
        COUNT(CASE WHEN filtrado > 0 THEN 1 ELSE NULL END) as countFiltrado, 
        COUNT(CASE WHEN alcalizado > 0 THEN 1 ELSE NULL END) as countAlcalizado, 
        COUNT(CASE WHEN clarificado > 0 THEN 1 ELSE NULL END) as countClarificado, 
        COUNT(CASE WHEN meladura > 0 THEN 1 ELSE NULL END) as countMeladura
        FROM controlph 
        WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtPHActual = $conexion->prepare($queryPHActual);
    $stmtPHActual->bindParam(':periodoZafra', $periodoZafra);
    $stmtPHActual->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtPHActual->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtPHActual->execute();
    $resultPHActual = $stmtPHActual->fetch(PDO::FETCH_ASSOC);

    $phDataActual = [];
    foreach (['Primario', 'Mezclado', 'Residual', 'Sulfitado', 'Filtrado', 'Alcalizado', 'Clarificado', 'Meladura'] as $key) {
        $sumKey = 'sum' . $key;
        $countKey = 'count' . $key;
        $phDataActual[strtolower($key)] = $resultPHActual[$countKey] ? round($resultPHActual[$sumKey] / $resultPHActual[$countKey], 2) : 0;
    }

    // Consultar datos de control pH para la semana anterior (domingo a sábado)
    $queryPHAnterior = "SELECT 
        SUM(CASE WHEN primario > 0 THEN primario ELSE 0 END) as sumPrimario, 
        SUM(CASE WHEN mezclado > 0 THEN mezclado ELSE 0 END) as sumMezclado, 
        SUM(CASE WHEN residual > 0 THEN residual ELSE 0 END) as sumResidual, 
        SUM(CASE WHEN sulfitado > 0 THEN sulfitado ELSE 0 END) as sumSulfitado, 
        SUM(CASE WHEN filtrado > 0 THEN filtrado ELSE 0 END) as sumFiltrado, 
        SUM(CASE WHEN alcalizado > 0 THEN alcalizado ELSE 0 END) as sumAlcalizado, 
        SUM(CASE WHEN clarificado > 0 THEN clarificado ELSE 0 END) as sumClarificado, 
        SUM(CASE WHEN meladura > 0 THEN meladura ELSE 0 END) as sumMeladura, 
        COUNT(CASE WHEN primario > 0 THEN 1 ELSE NULL END) as countPrimario, 
        COUNT(CASE WHEN mezclado > 0 THEN 1 ELSE NULL END) as countMezclado, 
        COUNT(CASE WHEN residual > 0 THEN 1 ELSE NULL END) as countResidual, 
        COUNT(CASE WHEN sulfitado > 0 THEN 1 ELSE NULL END) as countSulfitado, 
        COUNT(CASE WHEN filtrado > 0 THEN 1 ELSE NULL END) as countFiltrado, 
        COUNT(CASE WHEN alcalizado > 0 THEN 1 ELSE NULL END) as countAlcalizado, 
        COUNT(CASE WHEN clarificado > 0 THEN 1 ELSE NULL END) as countClarificado, 
        COUNT(CASE WHEN meladura > 0 THEN 1 ELSE NULL END) as countMeladura
        FROM controlph 
        WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtPHAnterior = $conexion->prepare($queryPHAnterior);
    $stmtPHAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtPHAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtPHAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtPHAnterior->execute();
    $resultPHAnterior = $stmtPHAnterior->fetch(PDO::FETCH_ASSOC);

    $phDataAnterior = [];
    foreach (['Primario', 'Mezclado', 'Residual', 'Sulfitado', 'Filtrado', 'Alcalizado', 'Clarificado', 'Meladura'] as $key) {
        $sumKey = 'sum' . $key;
        $countKey = 'count' . $key;
        $phDataAnterior[strtolower($key)] = $resultPHAnterior[$countKey] ? round($resultPHAnterior[$sumKey] / $resultPHAnterior[$countKey], 2) : 0;
    }

    $avgPrimario = $phDataActual['primario'];
    $avgMezclado = $phDataActual['mezclado'];
    $avgResidual = $phDataActual['residual'];
    $avgSulfitado = $phDataActual['sulfitado'];
    $avgFiltrado = $phDataActual['filtrado'];
    $avgAlcalizado = $phDataActual['alcalizado'];
    $avgClarificado = $phDataActual['clarificado'];
    $avgMeladura = $phDataActual['meladura'];

    $avgPrimarioAnterior = $phDataAnterior['primario'];
    $avgMezcladoAnterior = $phDataAnterior['mezclado'];
    $avgResidualAnterior = $phDataAnterior['residual'];
    $avgSulfitadoAnterior = $phDataAnterior['sulfitado'];
    $avgFiltradoAnterior = $phDataAnterior['filtrado'];
    $avgAlcalizadoAnterior = $phDataAnterior['alcalizado'];
    $avgClarificadoAnterior = $phDataAnterior['clarificado'];
    $avgMeladuraAnterior = $phDataAnterior['meladura'];
    
    // Consultar datos de agua imbibición para la semana actual (domingo a sábado)
    $queryAguaImbibicion = "SELECT totalizador, valorInicial
                            FROM aguaimbibicion
                            WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtAguaImbibicion = $conexion->prepare($queryAguaImbibicion);
    $stmtAguaImbibicion->bindParam(':periodoZafra', $periodoZafra);
    $stmtAguaImbibicion->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtAguaImbibicion->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtAguaImbibicion->execute();
    $registrosAguaImbibicion = $stmtAguaImbibicion->fetchAll(PDO::FETCH_ASSOC);

    // Inicializar variables de agua imbibición
    $ultimoTotalizador = null;
    $factorConversion = 0.003511943101; // Factor constante

    foreach ($registrosAguaImbibicion as &$registro) {
        if ($registro['totalizador'] !== null) {
            if ($ultimoTotalizador === null) {
                // Primer registro: diferencia con valor inicial (acepta negativos)
                $diferencia = $registro['totalizador'] - $registro['valorInicial'];
                $registro['tnHora'] = ($diferencia < 0) ? "" : round($diferencia * $factorConversion, 3);
            } else {
                // Registros posteriores: diferencia con último totalizador
                $diferencia = $registro['totalizador'] - $ultimoTotalizador;
                $registro['tnHora'] = round($diferencia * $factorConversion, 3);
            }
            
            $ultimoTotalizador = $registro['totalizador'];
        } else {
            $registro['tnHora'] = 0; // Valor por defecto
        }
    }
    unset($registro); // Liberar referencia

    // Calcular sumas y promedios
    $sumas = ['totalizador' => 0, 'tnHora' => 0];
    $contadores = array_fill_keys(array_keys($sumas), 0);

    foreach ($registrosAguaImbibicion as $registro) {
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
            // El promedio de tnHora es la suma de todos los valores de tnHora
            $promediosAguaImbibicion[$key] = round($value, 2);
        } else {
            $promediosAguaImbibicion[$key] = ($contadores[$key] > 0) ? round($value / $contadores[$key]) : 0;
        }
    }

    // Consultar datos de agua imbibición para la semana anterior (domingo a sábado)
    $queryAguaImbibicionAnterior = "SELECT totalizador, valorInicial
                                    FROM aguaimbibicion
                                    WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtAguaImbibicionAnterior = $conexion->prepare($queryAguaImbibicionAnterior);
    $stmtAguaImbibicionAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtAguaImbibicionAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtAguaImbibicionAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtAguaImbibicionAnterior->execute();
    $registrosAguaImbibicionAnterior = $stmtAguaImbibicionAnterior->fetchAll(PDO::FETCH_ASSOC);

    // Inicializar variables de agua imbibición para la semana anterior
    $ultimoTotalizadorAnterior = null;

    foreach ($registrosAguaImbibicionAnterior as &$registro) {
        if ($registro['totalizador'] !== null) {
            if ($ultimoTotalizadorAnterior === null) {
                // Primer registro: diferencia con valor inicial (acepta negativos)
                $diferencia = $registro['totalizador'] - $registro['valorInicial'];
                $registro['tnHora'] = ($diferencia < 0) ? "" : round($diferencia * $factorConversion, 3);
            } else {
                // Registros posteriores: diferencia con último totalizador
                $diferencia = $registro['totalizador'] - $ultimoTotalizadorAnterior;
                $registro['tnHora'] = round($diferencia * $factorConversion, 3);
            }
            
            $ultimoTotalizadorAnterior = $registro['totalizador'];
        } else {
            $registro['tnHora'] = 0; // Valor por defecto
        }
    }
    unset($registro); // Liberar referencia

    // Calcular sumas y promedios para la semana anterior
    $sumasAnterior = ['totalizador' => 0, 'tnHora' => 0];
    $contadoresAnterior = array_fill_keys(array_keys($sumasAnterior), 0);

    foreach ($registrosAguaImbibicionAnterior as $registro) {
        foreach ($sumasAnterior as $key => $value) {
            if (is_numeric($registro[$key]) && $registro[$key] != 0 && $registro[$key] != '-') {
                $sumasAnterior[$key] += $registro[$key];
                $contadoresAnterior[$key]++;
            }
        }
    }

    $promediosAguaImbibicionAnterior = [];
    foreach ($sumasAnterior as $key => $value) {
        if ($key === 'tnHora') {
            // El promedio de tnHora es la suma de todos los valores de tnHora
            $promediosAguaImbibicionAnterior[$key] = round($value, 2);
        } else {
            $promediosAguaImbibicionAnterior[$key] = ($contadoresAnterior[$key] > 0) ? round($value / $contadoresAnterior[$key]) : 0;
        }
    }

    // Consultar datos de jugo mezclado phmbc para la semana actual (domingo a sábado)
    $queryJugoMezcladoPHMBC = "SELECT totalizador, valorInicial
                               FROM jugomezcladophmbc
                               WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtJugoMezcladoPHMBC = $conexion->prepare($queryJugoMezcladoPHMBC);
    $stmtJugoMezcladoPHMBC->bindParam(':periodoZafra', $periodoZafra);
    $stmtJugoMezcladoPHMBC->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtJugoMezcladoPHMBC->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtJugoMezcladoPHMBC->execute();
    $registrosJugoMezcladoPHMBC = $stmtJugoMezcladoPHMBC->fetchAll(PDO::FETCH_ASSOC);

    // Inicializar variables
    $ultimoTotalizador = null;
    $factorConversionGeneral = 0.95 / 2204.62;

    foreach ($registrosJugoMezcladoPHMBC as &$registro) {
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

    foreach ($registrosJugoMezcladoPHMBC as $registro) {
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

    // Consultar datos de jugo mezclado phmbc para la semana anterior (domingo a sábado)
    $queryJugoMezcladoPHMBCAnterior = "SELECT totalizador, valorInicial
                                      FROM jugomezcladophmbc
                                      WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtJugoMezcladoPHMBCAnterior = $conexion->prepare($queryJugoMezcladoPHMBCAnterior);
    $stmtJugoMezcladoPHMBCAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtJugoMezcladoPHMBCAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtJugoMezcladoPHMBCAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtJugoMezcladoPHMBCAnterior->execute();
    $registrosJugoMezcladoPHMBCAnterior = $stmtJugoMezcladoPHMBCAnterior->fetchAll(PDO::FETCH_ASSOC);

    // Inicializar variables
    $ultimoTotalizadorAnterior = null;

    foreach ($registrosJugoMezcladoPHMBCAnterior as &$registro) {
        if ($registro['totalizador'] !== null) {
            if ($ultimoTotalizadorAnterior === null) {
                $diferencia = $registro['totalizador'] - $registro['valorInicial'];
                $registro['tnHora'] = ($diferencia < 0) ? "" : round($diferencia * $factorConversionGeneral, 3);
            } else {
                $diferencia = $registro['totalizador'] - $ultimoTotalizadorAnterior;
                $registro['tnHora'] = round($diferencia * $factorConversionGeneral, 3);
            }
            $ultimoTotalizadorAnterior = $registro['totalizador'];
        } else {
            $registro['tnHora'] = round(0 * $factorConversionGeneral, 3);
        }
    }
    unset($registro); // Liberar referencia

    // Calcular sumas y promedios
    $sumasAnterior = ['totalizador' => 0, 'tnHora' => 0];
    $contadoresAnterior = array_fill_keys(array_keys($sumasAnterior), 0);

    foreach ($registrosJugoMezcladoPHMBCAnterior as $registro) {
        foreach ($sumasAnterior as $key => $value) {
            if ($registro[$key] !== null && $registro[$key] != 0 && $registro[$key] != '-') {
                $sumasAnterior[$key] += is_numeric($registro[$key]) ? $registro[$key] : 0;
                $contadoresAnterior[$key]++;
            }
        }
    }

    $promediosJugoMezcladoPHMBCAnterior = [];
    foreach ($sumasAnterior as $key => $value) {
        if ($key === 'tnHora') {
            $promediosJugoMezcladoPHMBCAnterior[$key] = round($value, 2);
        } else {
            $promediosJugoMezcladoPHMBCAnterior[$key] = ($contadoresAnterior[$key] > 0) ? round($value / $contadoresAnterior[$key]) : 0;
        }
    }

    // Consultar datos de bagazo para la semana actual (domingo a sábado)
    $queryBagazoActual = "SELECT 
                          AVG(CASE WHEN pol > 0 THEN pol ELSE NULL END) as avgPol, 
                          AVG(CASE WHEN humedad > 0 THEN humedad ELSE NULL END) as avgHumedad
                          FROM bagazo 
                          WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtBagazoActual = $conexion->prepare($queryBagazoActual);
    $stmtBagazoActual->bindParam(':periodoZafra', $periodoZafra);
    $stmtBagazoActual->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtBagazoActual->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtBagazoActual->execute();
    $resultBagazoActual = $stmtBagazoActual->fetch(PDO::FETCH_ASSOC);
    $avgPolBagazoActual = !empty($resultBagazoActual['avgPol']) ? round($resultBagazoActual['avgPol'], 2) : 0;
    $avgHumedadBagazoActual = !empty($resultBagazoActual['avgHumedad']) ? round($resultBagazoActual['avgHumedad'], 2) : 0;

    // Consultar datos de bagazo para la semana anterior (domingo a sábado)
    $queryBagazoAnterior = "SELECT 
                            AVG(CASE WHEN pol > 0 THEN pol ELSE NULL END) as avgPolAnterior, 
                            AVG(CASE WHEN humedad > 0 THEN humedad ELSE NULL END) as avgHumedadAnterior
                            FROM bagazo 
                            WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtBagazoAnterior = $conexion->prepare($queryBagazoAnterior);
    $stmtBagazoAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtBagazoAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtBagazoAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtBagazoAnterior->execute();
    $resultBagazoAnterior = $stmtBagazoAnterior->fetch(PDO::FETCH_ASSOC);
    $avgPolBagazoAnterior = !empty($resultBagazoAnterior['avgPolAnterior']) ? round($resultBagazoAnterior['avgPolAnterior'], 2) : 0;
    $avgHumedadBagazoAnterior = !empty($resultBagazoAnterior['avgHumedadAnterior']) ? round($resultBagazoAnterior['avgHumedadAnterior'], 2) : 0;

    // Consulta para Filtro Cachaza para la semana actual
    $queryFiltroCachazaActual = "SELECT pol1, gFt1, pol2, gFt2, pol3, gFt3
                                FROM filtrocachaza
                                WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtFiltroCachazaActual = $conexion->prepare($queryFiltroCachazaActual);
    $stmtFiltroCachazaActual->bindParam(':periodoZafra', $periodoZafra);
    $stmtFiltroCachazaActual->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtFiltroCachazaActual->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtFiltroCachazaActual->execute();
    $registrosFiltroCachazaActual = $stmtFiltroCachazaActual->fetchAll(PDO::FETCH_ASSOC);

    // Inicializar acumuladores y contadores para la semana actual
    $promediosFiltroCachazaActual = [
        'Filtro 1' => ['pol' => 0, 'gFt_ft2' => 0, 'count_pol' => 0, 'count_gFt' => 0],
        'Filtro 2' => ['pol' => 0, 'gFt_ft2' => 0, 'count_pol' => 0, 'count_gFt' => 0],
        'Filtro 3' => ['pol' => 0, 'gFt_ft2' => 0, 'count_pol' => 0, 'count_gFt' => 0],
    ];

    // Calcular acumuladores para cada filtro para la semana actual
    foreach ($registrosFiltroCachazaActual as $registro) {
        if (is_numeric($registro['pol1']) && $registro['pol1'] > 0) {
            $promediosFiltroCachazaActual['Filtro 1']['pol'] += $registro['pol1'];
            $promediosFiltroCachazaActual['Filtro 1']['count_pol']++;
        }
        if (is_numeric($registro['gFt1']) && $registro['gFt1'] > 0) {
            $promediosFiltroCachazaActual['Filtro 1']['gFt_ft2'] += $registro['gFt1'];
            $promediosFiltroCachazaActual['Filtro 1']['count_gFt']++;
        }

        if (is_numeric($registro['pol2']) && $registro['pol2'] > 0) {
            $promediosFiltroCachazaActual['Filtro 2']['pol'] += $registro['pol2'];
            $promediosFiltroCachazaActual['Filtro 2']['count_pol']++;
        }
        if (is_numeric($registro['gFt2']) && $registro['gFt2'] > 0) {
            $promediosFiltroCachazaActual['Filtro 2']['gFt_ft2'] += $registro['gFt2'];
            $promediosFiltroCachazaActual['Filtro 2']['count_gFt']++;
        }

        if (is_numeric($registro['pol3']) && $registro['pol3'] > 0) {
            $promediosFiltroCachazaActual['Filtro 3']['pol'] += $registro['pol3'];
            $promediosFiltroCachazaActual['Filtro 3']['count_pol']++;
        }
        if (is_numeric($registro['gFt3']) && $registro['gFt3'] > 0) {
            $promediosFiltroCachazaActual['Filtro 3']['gFt_ft2'] += $registro['gFt3'];
            $promediosFiltroCachazaActual['Filtro 3']['count_gFt']++;
        }
    }

    // Calcular promedios finales para la semana actual
    foreach ($promediosFiltroCachazaActual as $filtro => &$valores) {
        $valores['pol'] = $valores['count_pol'] > 0 ? number_format(round($valores['pol'] / $valores['count_pol'], 2), 2) : '0.00';
        $valores['gFt_ft2'] = $valores['count_gFt'] > 0 ? number_format(round($valores['gFt_ft2'] / $valores['count_gFt'], 2), 2) : '0.00';
    }
    unset($valores);

    // Cálculo del Promedio Pol Cachaza para la semana actual
    $promedioPolCachazaActual = '-';
    if (
        is_numeric($promediosFiltroCachazaActual['Filtro 1']['pol']) &&
        is_numeric($promediosFiltroCachazaActual['Filtro 2']['pol']) &&
        is_numeric($promediosFiltroCachazaActual['Filtro 3']['pol'])
    ) {
        $promedioPolCachazaActual = round(
            ($promediosFiltroCachazaActual['Filtro 1']['pol'] + 0.5 * $promediosFiltroCachazaActual['Filtro 2']['pol'] + $promediosFiltroCachazaActual['Filtro 3']['pol']) / 2.5,
            2
        );
    }

    // Consulta para Filtro Cachaza para la semana anterior
    $queryFiltroCachazaAnterior = "SELECT pol1, gFt1, pol2, gFt2, pol3, gFt3
                                FROM filtrocachaza
                                WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtFiltroCachazaAnterior = $conexion->prepare($queryFiltroCachazaAnterior);
    $stmtFiltroCachazaAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtFiltroCachazaAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtFiltroCachazaAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtFiltroCachazaAnterior->execute();
    $registrosFiltroCachazaAnterior = $stmtFiltroCachazaAnterior->fetchAll(PDO::FETCH_ASSOC);

    // Inicializar acumuladores y contadores para la semana anterior
    $promediosFiltroCachazaAnterior = [
        'Filtro 1' => ['pol' => 0, 'gFt_ft2' => 0, 'count_pol' => 0, 'count_gFt' => 0],
        'Filtro 2' => ['pol' => 0, 'gFt_ft2' => 0, 'count_pol' => 0, 'count_gFt' => 0],
        'Filtro 3' => ['pol' => 0, 'gFt_ft2' => 0, 'count_pol' => 0, 'count_gFt' => 0],
    ];

    // Calcular acumuladores para cada filtro para la semana anterior
    foreach ($registrosFiltroCachazaAnterior as $registro) {
        if (is_numeric($registro['pol1']) && $registro['pol1'] > 0) {
            $promediosFiltroCachazaAnterior['Filtro 1']['pol'] += $registro['pol1'];
            $promediosFiltroCachazaAnterior['Filtro 1']['count_pol']++;
        }
        if (is_numeric($registro['gFt1']) && $registro['gFt1'] > 0) {
            $promediosFiltroCachazaAnterior['Filtro 1']['gFt_ft2'] += $registro['gFt1'];
            $promediosFiltroCachazaAnterior['Filtro 1']['count_gFt']++;
        }

        if (is_numeric($registro['pol2']) && $registro['pol2'] > 0) {
            $promediosFiltroCachazaAnterior['Filtro 2']['pol'] += $registro['pol2'];
            $promediosFiltroCachazaAnterior['Filtro 2']['count_pol']++;
        }
        if (is_numeric($registro['gFt2']) && $registro['gFt2'] > 0) {
            $promediosFiltroCachazaAnterior['Filtro 2']['gFt_ft2'] += $registro['gFt2'];
            $promediosFiltroCachazaAnterior['Filtro 2']['count_gFt']++;
        }

        if (is_numeric($registro['pol3']) && $registro['pol3'] > 0) {
            $promediosFiltroCachazaAnterior['Filtro 3']['pol'] += $registro['pol3'];
            $promediosFiltroCachazaAnterior['Filtro 3']['count_pol']++;
        }
        if (is_numeric($registro['gFt3']) && $registro['gFt3'] > 0) {
            $promediosFiltroCachazaAnterior['Filtro 3']['gFt_ft2'] += $registro['gFt3'];
            $promediosFiltroCachazaAnterior['Filtro 3']['count_gFt']++;
        }
    }

    // Calcular promedios finales para la semana anterior
    foreach ($promediosFiltroCachazaAnterior as $filtro => &$valores) {
        $valores['pol'] = $valores['count_pol'] > 0 ? number_format(round($valores['pol'] / $valores['count_pol'], 2), 2) : '0.00';
        $valores['gFt_ft2'] = $valores['count_gFt'] > 0 ? number_format(round($valores['gFt_ft2'] / $valores['count_gFt'], 2), 2) : '0.00';
    }
    unset($valores);

    // Cálculo del Promedio Pol Cachaza para la semana anterior
    $promedioPolCachazaAnterior = '-';
    if (
        is_numeric($promediosFiltroCachazaAnterior['Filtro 1']['pol']) &&
        is_numeric($promediosFiltroCachazaAnterior['Filtro 2']['pol']) &&
        is_numeric($promediosFiltroCachazaAnterior['Filtro 3']['pol'])
    ) {
        $promedioPolCachazaAnterior = round(
            ($promediosFiltroCachazaAnterior['Filtro 1']['pol'] + 0.5 * $promediosFiltroCachazaAnterior['Filtro 2']['pol'] + $promediosFiltroCachazaAnterior['Filtro 3']['pol']) / 2.5,
            2
        );
    }

    // Consultar datos de cachaza para la semana actual (domingo a sábado)
    $queryCachazaActual = "SELECT 
                           AVG(CASE WHEN humedad > 0 THEN humedad ELSE NULL END) as avgHumedad, 
                           AVG(CASE WHEN fibra > 0 THEN fibra ELSE NULL END) as avgFibra
                           FROM cachaza 
                           WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina";
    $stmtCachazaActual = $conexion->prepare($queryCachazaActual);
    $stmtCachazaActual->bindParam(':periodoZafra', $periodoZafra);
    $stmtCachazaActual->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtCachazaActual->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtCachazaActual->execute();
    $resultCachazaActual = $stmtCachazaActual->fetch(PDO::FETCH_ASSOC);
    $avgHumedadCachazaActual = !empty($resultCachazaActual['avgHumedad']) ? round($resultCachazaActual['avgHumedad'], 2) : 0;
    $avgFibraCachazaActual = !empty($resultCachazaActual['avgFibra']) ? round($resultCachazaActual['avgFibra'], 2) : 0;

    // Consultar datos de cachaza para la semana anterior (domingo a sábado)
    $queryCachazaAnterior = "SELECT 
                             AVG(CASE WHEN humedad > 0 THEN humedad ELSE NULL END) as avgHumedadAnterior, 
                             AVG(CASE WHEN fibra > 0 THEN fibra ELSE NULL END) as avgFibraAnterior
                             FROM cachaza 
                             WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina";
    $stmtCachazaAnterior = $conexion->prepare($queryCachazaAnterior);
    $stmtCachazaAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtCachazaAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtCachazaAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtCachazaAnterior->execute();
    $resultCachazaAnterior = $stmtCachazaAnterior->fetch(PDO::FETCH_ASSOC);
    $avgHumedadCachazaAnterior = !empty($resultCachazaAnterior['avgHumedadAnterior']) ? round($resultCachazaAnterior['avgHumedadAnterior'], 2) : 0;
    $avgFibraCachazaAnterior = !empty($resultCachazaAnterior['avgFibraAnterior']) ? round($resultCachazaAnterior['avgFibraAnterior'], 2) : 0;



    // Consultar datos de causas para la semana actual (domingo a sábado)
    $queryCausasActual = "SELECT turno, 
                             DATE_FORMAT(paro, '%h:%i %p') as paro, 
                             DATE_FORMAT(arranque, '%h:%i %p') as arranque, 
                             CASE 
                                 WHEN arranque IS NULL OR arranque = '' THEN NULL
                                 WHEN paro > arranque THEN SEC_TO_TIME(TIME_TO_SEC('24:00') - TIME_TO_SEC(paro) + TIME_TO_SEC(arranque))
                                 ELSE SEC_TO_TIME(TIME_TO_SEC(arranque) - TIME_TO_SEC(paro)) 
                             END AS tiempoPerdido, 
                             motivo, idCausa 
                      FROM causas 
                      WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaActualInicio AND :semanaActualTermina 
                      ORDER BY 
                          CASE 
                              WHEN turno = 'Turno C' AND TIME(paro) >= '21:00:00' THEN 0
                              WHEN turno = 'Turno C' AND TIME(paro) < '01:00:00' THEN 1
                              WHEN turno = 'Turno C' THEN 2
                              ELSE 3
                          END, paro";
    $stmtCausasActual = $conexion->prepare($queryCausasActual);
    $stmtCausasActual->bindParam(':periodoZafra', $periodoZafra);
    $stmtCausasActual->bindParam(':semanaActualInicio', $semanaActualInicio);
    $stmtCausasActual->bindParam(':semanaActualTermina', $semanaActualTermina);
    $stmtCausasActual->execute();
    $registrosCausasActual = $stmtCausasActual->fetchAll(PDO::FETCH_ASSOC);

    // Inicializar acumuladores de tiempo perdido por turno
    $tiempoPerdidoPorTurnoActual = ['Turno A' => 0, 'Turno B' => 0, 'Turno C' => 0];
    $totalDiaActual = 0;

    // Sumar tiempos perdidos por turno
    foreach ($registrosCausasActual as $causa) {
        if (!empty($causa['tiempoPerdido'])) {
            list($horas, $minutos, $segundos) = explode(':', $causa['tiempoPerdido']);
            $tiempoEnMinutos = ($horas * 60) + $minutos + ($segundos / 60);
            $tiempoPerdidoPorTurnoActual[$causa['turno']] += $tiempoEnMinutos;
            $totalDiaActual += $tiempoEnMinutos;
        }
    }

    // Convertir tiempos a horas y minutos para la semana actual
    foreach ($tiempoPerdidoPorTurnoActual as $turno => $tiempo) {
        $horas = floor($tiempo / 60);
        $minutos = $tiempo % 60;
        $tiempoEnHoras = $horas + ($minutos / 60);
        $tiempoPerdidoPorTurnoActual[$turno] = (float)$tiempoEnHoras;
    }
    $totalDiaHorasActual = (float)(floor($totalDiaActual / 60) + (($totalDiaActual % 60) / 60));

    // Consultar datos de causas para la semana anterior (domingo a sábado)
    $queryCausasAnterior = "SELECT turno, 
                         DATE_FORMAT(paro, '%h:%i %p') as paro, 
                         DATE_FORMAT(arranque, '%h:%i %p') as arranque, 
                         CASE 
                             WHEN arranque IS NULL OR arranque = '' THEN NULL
                             WHEN paro > arranque THEN SEC_TO_TIME(TIME_TO_SEC('24:00') - TIME_TO_SEC(paro) + TIME_TO_SEC(arranque))
                             ELSE SEC_TO_TIME(TIME_TO_SEC(arranque) - TIME_TO_SEC(paro)) 
                         END AS tiempoPerdido, 
                         motivo, idCausa 
                  FROM causas 
                  WHERE periodoZafra = :periodoZafra AND fechaIngreso BETWEEN :semanaAnteriorInicio AND :semanaAnteriorTermina 
                  ORDER BY 
                      CASE 
                          WHEN turno = 'Turno C' AND TIME(paro) >= '21:00:00' THEN 0
                          WHEN turno = 'Turno C' AND TIME(paro) < '01:00:00' THEN 1
                          WHEN turno = 'Turno C' THEN 2
                          ELSE 3
                      END, paro";
    $stmtCausasAnterior = $conexion->prepare($queryCausasAnterior);
    $stmtCausasAnterior->bindParam(':periodoZafra', $periodoZafra);
    $stmtCausasAnterior->bindParam(':semanaAnteriorInicio', $semanaAnteriorInicio);
    $stmtCausasAnterior->bindParam(':semanaAnteriorTermina', $semanaAnteriorTermina);
    $stmtCausasAnterior->execute();
    $registrosCausasAnterior = $stmtCausasAnterior->fetchAll(PDO::FETCH_ASSOC);

    // Inicializar acumuladores de tiempo perdido por turno para la semana anterior
    $tiempoPerdidoPorTurnoAnterior = ['Turno A' => 0, 'Turno B' => 0, 'Turno C' => 0];
    $totalDiaAnterior = 0;

    // Sumar tiempos perdidos por turno para la semana anterior
    foreach ($registrosCausasAnterior as $causa) {
        if (!empty($causa['tiempoPerdido'])) {
            list($horas, $minutos, $segundos) = explode(':', $causa['tiempoPerdido']);
            $tiempoEnMinutos = ($horas * 60) + $minutos + ($segundos / 60);
            $tiempoPerdidoPorTurnoAnterior[$causa['turno']] += $tiempoEnMinutos;
            $totalDiaAnterior += $tiempoEnMinutos;
        }
    }

    // Convertir tiempos a horas y minutos para la semana anterior
    foreach ($tiempoPerdidoPorTurnoAnterior as $turno => $tiempo) {
        $horas = floor($tiempo / 60);
        $minutos = $tiempo % 60;
        $tiempoEnHoras = $horas + ($minutos / 60);
        $tiempoPerdidoPorTurnoAnterior[$turno] = (float)$tiempoEnHoras;
    }
    $totalDiaHorasAnterior = (float)(floor($totalDiaAnterior / 60) + (($totalDiaAnterior % 60) / 60));
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">
    <link href="<?= BASE_PATH ?>public/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>public/css/sb-admin-2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
    <script>
        function decimalToHoursMinutes(decimal) {
            const horas = Math.floor(decimal);
            const minutos = Math.round((decimal - horas) * 60);

            // Caso 1: Sin horas, solo minutos (ej: 0.5 → 30m)
            if (horas === 0 && minutos > 0) {
                return `${minutos}m`;
            }

            // Caso 2: Sin minutos, solo horas (ej:5.0 → 5h)
            if (minutos === 0) {
                return `${horas}h`;
            }

            // Caso 3: Ambos (ej: 2.5 → 2h 30m)
            return `${horas}h ${minutos}m`;
        }
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var ctxJugoPrimario = document.getElementById('myPieChartJugoPrimario').getContext('2d');
            var myPieChartJugoPrimario = new Chart(ctxJugoPrimario, {
                type: 'line',
                data: {
                    labels: ['Brix', 'Sac', 'Pureza'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $avgBrixJP; ?>, <?php echo $avgSacJP; ?>, <?php echo $promPurJP; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $avgBrixJPAnterior; ?>, <?php echo $avgSacJPAnterior; ?>, <?php echo $promPurJPAnterior; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            var ctxJugoMezclado = document.getElementById('myPieChartJugoMezclado').getContext('2d');
            var myPieChartJugoMezclado = new Chart(ctxJugoMezclado, {
                type: 'line',
                data: {
                    labels: ['Brix', 'Sac', 'Pureza'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $avgBrixJM; ?>, <?php echo $avgSacJM; ?>, <?php echo $promPurJM; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $avgBrixJMAnterior; ?>, <?php echo $avgSacJMAnterior; ?>, <?php echo $promPurJMAnterior; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value;
                                }
                            }
                        }
                    }
                }
            });

            var ctxJugoResidual = document.getElementById('myPieChartJugoResidual').getContext('2d');
            var myPieChartJugoResidual = new Chart(ctxJugoResidual, {
                type: 'line',
                data: {
                    labels: ['Brix', 'Sac', 'Pureza'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $avgBrixJR; ?>, <?php echo $avgSacJR; ?>, <?php echo $promPurJR; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $avgBrixJRAnterior; ?>, <?php echo $avgSacJRAnterior; ?>, <?php echo $promPurJRAnterior; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            var ctxJugoClarificado = document.getElementById('myPieChartJugoClarificado').getContext('2d');
            var myPieChartJugoClarificado = new Chart(ctxJugoClarificado, {
                type: 'line',
                data: {
                    labels: ['Brix', 'Sac', 'Pureza'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $avgBrixJC; ?>, <?php echo $avgSacJC; ?>, <?php echo $promPurJC; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $avgBrixJCAnterior; ?>, <?php echo $avgSacJCAnterior; ?>, <?php echo $promPurJCAnterior; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            var ctxJugoFiltrado = document.getElementById('myPieChartJugoFiltrado').getContext('2d');
            var myPieChartJugoFiltrado = new Chart(ctxJugoFiltrado, {
                type: 'line',
                data: {
                    labels: ['Brix', 'Sac', 'Pureza'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $avgBrixJF; ?>, <?php echo $avgSacJF; ?>, <?php echo $promPurJF; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $avgBrixJFAnterior; ?>, <?php echo $avgSacJFAnterior; ?>, <?php echo $promPurJFAnterior; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            var ctxMeladura = document.getElementById('myPieChartMeladura').getContext('2d');
            var myPieChartMeladura = new Chart(ctxMeladura, {
                type: 'line',
                data: {
                    labels: ['Brix', 'Sac', 'Pureza'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $avgBrixMeladura; ?>, <?php echo $avgSacMeladura; ?>, <?php echo $promPurMeladura; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $avgBrixMeladuraAnterior; ?>, <?php echo $avgSacMeladuraAnterior; ?>, <?php echo $promPurMeladuraAnterior; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            var ctxMasaCocidaA = document.getElementById('myPieChartMasaCocidaA').getContext('2d');
            var myPieChartMasaCocidaA = new Chart(ctxMasaCocidaA, {
                type: 'line',
                data: {
                    labels: ['Brix', 'Pol', 'Pureza'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $avgBrixMCA; ?>, <?php echo $avgPolMCA; ?>, <?php echo $promPurMCA; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $avgBrixMCAAnterior; ?>, <?php echo $avgPolMCAAnterior; ?>, <?php echo $promPurMCAAnterior; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            var ctxMasaCocidaB = document.getElementById('myPieChartMasaCocidaB').getContext('2d');
            var myPieChartMasaCocidaB = new Chart(ctxMasaCocidaB, {
                type: 'line',
                data: {
                    labels: ['Brix', 'Pol', 'Pureza'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $avgBrixMCB; ?>, <?php echo $avgPolMCB; ?>, <?php echo $promPurMCB; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $avgBrixMCBAnterior; ?>, <?php echo $avgPolMCBAnterior; ?>, <?php echo $promPurMCBAnterior; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            var ctxMasaCocidaC = document.getElementById('myPieChartMasaCocidaC').getContext('2d');
            var myPieChartMasaCocidaC = new Chart(ctxMasaCocidaC, {
                type: 'line',
                data: {
                    labels: ['Brix', 'Pol', 'Pureza'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $avgBrixMCC; ?>, <?php echo $avgPolMCC; ?>, <?php echo $promPurMCC; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $avgBrixMCCAnterior; ?>, <?php echo $avgPolMCCAnterior; ?>, <?php echo $promPurMCCAnterior; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            var ctxMielA = document.getElementById('myPieChartMielA').getContext('2d');
            var myPieChartMielA = new Chart(ctxMielA, {
                type: 'line',
                data: {
                    labels: ['Brix', 'Pol', 'Pureza'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $avgBrixMielA; ?>, <?php echo $avgPolMielA; ?>, <?php echo $promPurMielA; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $avgBrixMielAAnterior; ?>, <?php echo $avgPolMielAAnterior; ?>, <?php echo $promPurMielAAnterior; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            var ctxMielB = document.getElementById('myPieChartMielB').getContext('2d');
            var myPieChartMielB = new Chart(ctxMielB, {
                type: 'line',
                data: {
                    labels: ['Brix', 'Pol', 'Pureza'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $avgBrixMielB; ?>, <?php echo $avgPolMielB; ?>, <?php echo $promPurMielB; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $avgBrixMielBAnterior; ?>, <?php echo $avgPolMielBAnterior; ?>, <?php echo $promPurMielBAnterior; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            var ctxMielFinal = document.getElementById('myPieChartMielFinal').getContext('2d');
            var myPieChartMielFinal = new Chart(ctxMielFinal, {
                type: 'line',
                data: {
                    labels: ['Brix', 'Pol', 'Pureza'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $avgBrixMielFinal; ?>, <?php echo $avgPolMielFinal; ?>, <?php echo $promPurMielFinal; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $avgBrixMielFinalAnterior; ?>, <?php echo $avgPolMielFinalAnterior; ?>, <?php echo $promPurMielFinalAnterior; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            var ctxMagmaB = document.getElementById('myPieChartMagmaB').getContext('2d');
            var myPieChartMagmaB = new Chart(ctxMagmaB, {
                type: 'line',
                data: {
                    labels: ['Brix', 'Pol', 'Pureza'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $avgBrixMagmaB; ?>, <?php echo $avgPolMagmaB; ?>, <?php echo $promPurMagmaB; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $avgBrixMagmaBAnterior; ?>, <?php echo $avgPolMagmaBAnterior; ?>, <?php echo $promPurMagmaBAnterior; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            var ctxMagmaC = document.getElementById('myPieChartMagmaC').getContext('2d');
            var myPieChartMagmaC = new Chart(ctxMagmaC, {
                type: 'line',
                data: {
                    labels: ['Brix', 'Pol', 'Pureza'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $avgBrixMagmaC; ?>, <?php echo $avgPolMagmaC; ?>, <?php echo $promPurMagmaC; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $avgBrixMagmaCAnterior; ?>, <?php echo $avgPolMagmaCAnterior; ?>, <?php echo $promPurMagmaCAnterior; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            var ctxAnalisisAzucar = document.getElementById('myPieChartAnalisisAzucar').getContext('2d');
            var myPieChartAnalisisAzucar = new Chart(ctxAnalisisAzucar, {
                type: 'line',
                data: {
                    labels: ['Vitamina A', 'Pol', 'Humedad', 'Cenizas'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $avgVitaminaA; ?>, <?php echo $avgPol; ?>, <?php echo $avgHumedad; ?>, <?php echo $avgCenizas; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $avgVitaminaAAnterior; ?>, <?php echo $avgPolAnterior; ?>, <?php echo $avgHumedadAnterior; ?>, <?php echo $avgCenizasAnterior; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            var ctxEfluentes = document.getElementById('myPieChartEfluentes').getContext('2d');
            var myPieChartEfluentes = new Chart(ctxEfluentes, {
                type: 'line',
                data: {
                    labels: ['Enfriamiento', 'Retorno', 'Desechos'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $avgEnfriamiento; ?>, <?php echo $avgRetorno; ?>, <?php echo $avgDesechos; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $avgEnfriamientoAnterior; ?>, <?php echo $avgRetornoAnterior; ?>, <?php echo $avgDesechosAnterior; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            var ctxSacoAzucar = document.getElementById('myPieChartSacoAzucar').getContext('2d');
            var myPieChartSacoAzucar = new Chart(ctxSacoAzucar, {
                type: 'line',
                data: {
                    labels: ['Toneladas Caña ACHSA', 'Toneladas Molienda', 'Sacos Azúcar Blanco', 'Sacos Azúcar Moreno', 'Jumbo Azúcar Blanco', 'Sacos 50kg', 'Rendimiento', 'Total Toneladas Azúcar'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $sacoAzucarData['tnCanaACHSA']; ?>, <?php echo $sacoAzucarData['moliendaTn']; ?>, <?php echo $sacoAzucarData['sacos50AzucarBlanco']; ?>, <?php echo $sacoAzucarData['sacosAzucarMorena']; ?>, <?php echo $sacoAzucarData['jumboAzucarBlanco']; ?>, <?php echo $sacoAzucarData['sacos50kg']; ?>, <?php echo $sacoAzucarData['rendimiento']; ?>, <?php echo $sacoAzucarData['totalToneladasAzucar']; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $sacoAzucarDataAnterior['tnCanaACHSA']; ?>, <?php echo $sacoAzucarDataAnterior['moliendaTn']; ?>, <?php echo $sacoAzucarDataAnterior['sacos50AzucarBlanco']; ?>, <?php echo $sacoAzucarDataAnterior['sacosAzucarMorena']; ?>, <?php echo $sacoAzucarDataAnterior['jumboAzucarBlanco']; ?>, <?php echo $sacoAzucarDataAnterior['sacos50kg']; ?>, <?php echo $sacoAzucarDataAnterior['rendimiento']; ?>, <?php echo $sacoAzucarDataAnterior['totalToneladasAzucar']; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            var ctxPH = document.getElementById('myPieChartPH').getContext('2d');
            var myPieChartPH = new Chart(ctxPH, {
                type: 'line',
                data: {
                    labels: ['Primario', 'Mezclado', 'Residual', 'Sulfitado', 'Filtrado', 'Alcalizado', 'Clarificado', 'Meladura'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $phDataActual['primario']; ?>, <?php echo $phDataActual['mezclado']; ?>, <?php echo $phDataActual['residual']; ?>, <?php echo $phDataActual['sulfitado']; ?>, <?php echo $phDataActual['filtrado']; ?>, <?php echo $phDataActual['alcalizado']; ?>, <?php echo $phDataActual['clarificado']; ?>, <?php echo $phDataActual['meladura']; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $phDataAnterior['primario']; ?>, <?php echo $phDataAnterior['mezclado']; ?>, <?php echo $phDataAnterior['residual']; ?>, <?php echo $phDataAnterior['sulfitado']; ?>, <?php echo $phDataAnterior['filtrado']; ?>, <?php echo $phDataAnterior['alcalizado']; ?>, <?php echo $phDataAnterior['clarificado']; ?>, <?php echo $phDataAnterior['meladura']; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            var ctxAguaImbibicion = document.getElementById('myPieChartAguaImbibicion').getContext('2d');
            var myBarChartAguaImbibicion = new Chart(ctxAguaImbibicion, {
                type: 'bar',
                data: {
                    labels: ['Tn/hora'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $promediosAguaImbibicion['tnHora']; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $promediosAguaImbibicionAnterior['tnHora']; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            var ctxJugoMezcladoPHMBC = document.getElementById('myPieChartJugoMezcladoPHMBC').getContext('2d');
            var myBarChartJugoMezcladoPHMBC = new Chart(ctxJugoMezcladoPHMBC, {
                type: 'bar',
                data: {
                    labels: ['Tn/hora'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $promediosJugoMezcladoPHMBC['tnHora']; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $promediosJugoMezcladoPHMBCAnterior['tnHora']; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            var ctxBagazo = document.getElementById('myPieChartBagazo').getContext('2d');
            var myPieChartBagazo = new Chart(ctxBagazo, {
                type: 'line',
                data: {
                    labels: ['Pol', 'Humedad'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $avgPolBagazoActual; ?>, <?php echo $avgHumedadBagazoActual; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $avgPolBagazoAnterior; ?>, <?php echo $avgHumedadBagazoAnterior; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            var ctxFiltrosCachaza = document.getElementById('myPieChartFiltrosCachaza').getContext('2d');
            var myPieChartFiltrosCachaza = new Chart(ctxFiltrosCachaza, {
                type: 'line',
                data: {
                    labels: ['Pol 1', 'g/Ft 1', 'Pol 2', 'g/Ft 2', 'Pol 3', 'g/Ft 3', 'Prom Pol Cachaza'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $promediosFiltroCachazaActual['Filtro 1']['pol']; ?>, <?php echo $promediosFiltroCachazaActual['Filtro 1']['gFt_ft2']; ?>, <?php echo $promediosFiltroCachazaActual['Filtro 2']['pol']; ?>, <?php echo $promediosFiltroCachazaActual['Filtro 2']['gFt_ft2']; ?>, <?php echo $promediosFiltroCachazaActual['Filtro 3']['pol']; ?>, <?php echo $promediosFiltroCachazaActual['Filtro 3']['gFt_ft2']; ?>, <?php echo $promedioPolCachazaActual; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $promediosFiltroCachazaAnterior['Filtro 1']['pol']; ?>, <?php echo $promediosFiltroCachazaAnterior['Filtro 1']['gFt_ft2']; ?>, <?php echo $promediosFiltroCachazaAnterior['Filtro 2']['pol']; ?>, <?php echo $promediosFiltroCachazaAnterior['Filtro 2']['gFt_ft2']; ?>, <?php echo $promediosFiltroCachazaAnterior['Filtro 3']['pol']; ?>, <?php echo $promediosFiltroCachazaAnterior['Filtro 3']['gFt_ft2']; ?>, <?php echo $promedioPolCachazaAnterior; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            var ctxCachaza = document.getElementById('myPieChartCachaza').getContext('2d');
            var myPieChartCachaza = new Chart(ctxCachaza, {
                type: 'line',
                data: {
                    labels: ['Humedad', 'Fibra'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $avgHumedadCachazaActual; ?>, <?php echo $avgFibraCachazaActual; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Anterior',
                        data: [<?php echo $avgHumedadCachazaAnterior; ?>, <?php echo $avgFibraCachazaAnterior; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    var label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.raw !== null) {
                                        label += context.raw;
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            var ctxCausas = document.getElementById('myPieChartCausas').getContext('2d');
            var myPieChartCausas = new Chart(ctxCausas, {
                type: 'line',
                data: {
                    labels: ['Turno A', 'Turno B', 'Turno C', 'Total del Día'],
                    datasets: [{
                        label: 'Semana Actual',
                        data: [<?php echo $tiempoPerdidoPorTurnoActual['Turno A']; ?>, <?php echo $tiempoPerdidoPorTurnoActual['Turno B']; ?>, <?php echo $tiempoPerdidoPorTurnoActual['Turno C']; ?>, <?php echo $totalDiaHorasActual; ?>],
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        fill: true
                    }, {
                        label: 'Semana Pasada',
                        data: [<?php echo $tiempoPerdidoPorTurnoAnterior['Turno A']; ?>, <?php echo $tiempoPerdidoPorTurnoAnterior['Turno B']; ?>, <?php echo $tiempoPerdidoPorTurnoAnterior['Turno C']; ?>, <?php echo $totalDiaHorasAnterior; ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        fill: true
                    }]
                },
                options: {
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                const value = tooltipItem.yLabel;
                                return decimalToHoursMinutes(value); // Aplica la función
                            }
                        }
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                callback: function(value) {
                                    return decimalToHoursMinutes(value); // Formato limpio
                                }
                            }
                        }]
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        });
    </script>

    <style>
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

        .content {
            margin-left: 220px;
            /* Espacio para el sidebar */
            padding: 20px;
        }

        .chart-area {
            position: relative;
            height: 100%;
            width: 100%;
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
                    <h1 class="h3 mb-2 text-gray-800">Dashboard</h1>
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

                    <?php if (!empty($periodoZafra) && !empty($fechaIngreso)): ?>
                        <div class="mb-4">
                            <div class="card-header py-3">
                                <h5 class="m-0 font-weight-bold text-primary text-center">Jugos & Meladura</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Jugo Primario</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartJugoPrimario" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Jugo Mezclado</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartJugoMezclado" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Jugo Residual</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartJugoResidual" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Jugo Clarificado</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartJugoClarificado" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Jugo Filtrado</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartJugoFiltrado" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Meladura</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartMeladura" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-header py-3">
                                <h5 class="m-0 font-weight-bold text-primary text-center">Masas & Mieles</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Masa Cocida A</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartMasaCocidaA" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Masa Cocida B</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartMasaCocidaB" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Masa Cocida C</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartMasaCocidaC" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Miel A</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartMielA" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Miel B</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartMielB" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Miel Final</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartMielFinal" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Magma B</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartMagmaB" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Magma C</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartMagmaC" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Análisis de Azúcar</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartAnalisisAzucar" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Efluentes</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartEfluentes" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Saco Azúcar</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartSacoAzucar" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-header py-3">
                                <h5 class="m-0 font-weight-bold text-primary text-center">pH, Molinos, Bagazo & Cachaza</h5>
                            </div>
                            <div class="card-body">
                                <div class="row justify-content">
                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Control pH</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartPH" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Agua Imbibición</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartAguaImbibicion" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Jugo Mezclado</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartJugoMezcladoPHMBC" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Bagazo</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartBagazo" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Filtros Cachaza</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartFiltrosCachaza" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Cachaza</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartCachaza" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-center">
                                                <h6 class="m-0 font-weight-bold text-primary">Causas</h6>
                                            </div>
                                            <div class="card-body text-center" style="height: 400px; width: 100%;">
                                                <div class="chart-area pt-4 pb-2" style="height: 100%; width: 100%;">
                                                    <canvas id="myPieChartCausas" class="chartjs-render-monitor mx-auto" style="height: 100%; width: 100%;"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" role="alert">
                            Por favor, seleccione un periodo de zafra y una fecha de ingreso para ver los gráficos.
                        </div>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

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
    </script>
</body>

</html>