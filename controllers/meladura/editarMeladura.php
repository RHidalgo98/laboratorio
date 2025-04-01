<?php
include $_SERVER['DOCUMENT_ROOT'] . '/AnalisisLaboratorio/config/config.php';
include ROOT_PATH . 'config/conexion.php';
include ROOT_PATH . 'includes/bitacora.php';

// Iniciar sesión si no está iniciada
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['idUsuario'])) {
    // Si no hay sesión activa, redirigir al formulario de login
    header("Location: " . BASE_PATH . "login.html");
    exit(); // Asegurar que el script se detenga después de redirigir
}

$error = ""; // Variable para capturar errores

// Verificar si se ha enviado el ID por la URL
if (isset($_GET['id'])) {
    $idMeladura = $_GET['id'];

    // Obtener los datos actuales del registro
    try {
        $stmt = $conexion->prepare("SELECT * FROM meladura WHERE idMeladura = :idMeladura");
        $stmt->bindParam(':idMeladura', $idMeladura, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            echo "Registro no encontrado.";
            exit();
        }

        // Asignar valores si existen, de lo contrario, vacíos
        $periodoZafra = $registro['periodoZafra'] ?? '';
        $fechaIngreso = $registro['fechaIngreso'] ?? '';
        $observacion = $registro['observacion'] ?? null;
        
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
} else {
    echo "ID no recibido.";
    exit();
}

// Actualizar los datos si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hora = $_POST['hora'];
    $observacion = $_POST['observacion'];
    $color = $_POST['color'];
    $brix = $_POST['brix'];
    $sac = $_POST['sac'];
    $mlGastado = $_POST['mlGastado'];
    $periodoZafra = $_POST['periodoZafra'];
    $fechaIngreso = $_POST['fechaIngreso'];

    // Verificar si ya existe otro registro con la misma hora, periodoZafra y fechaIngreso
    try {
        $check_sql = "SELECT COUNT(*) FROM meladura 
                      WHERE hora = :hora 
                      AND periodoZafra = :periodoZafra 
                      AND fechaIngreso = :fechaIngreso 
                      AND idMeladura != :idMeladura";
        $check_stmt = $conexion->prepare($check_sql);
        $check_stmt->bindParam(':hora', $hora);
        $check_stmt->bindParam(':periodoZafra', $periodoZafra);
        $check_stmt->bindParam(':fechaIngreso', $fechaIngreso);
        $check_stmt->bindParam(':idMeladura', $idMeladura);
        $check_stmt->execute();
        $count = $check_stmt->fetchColumn();

        if ($count > 0) {
            $error = "Ya existe un registro con la misma hora, periodo de zafra y fecha.";
            header("Location: " . BASE_PATH . "controllers/meladura/editarMeladura.php?id=" . urlencode($idMeladura) . "&error=" . urlencode($error));
            exit();
        }
    } catch (PDOException $e) {
        echo "Error al comprobar duplicados: " . $e->getMessage();
        exit();
    }

    // Validar que todos los campos requeridos estén presentes
    if (empty($periodoZafra) || empty($fechaIngreso) || empty($hora) || empty($brix) || empty($sac)) {
        $error = "Los valores de Brix y SAC no pueden estar vacios.";
        header("Location: " . BASE_PATH . "controllers/meladura/editarMeladura.php?id=" . urlencode($idMeladura) . "&error=" . urlencode($error) . "&periodoZafra=" . urlencode($periodoZafra) . "&fechaIngreso=" . urlencode($fechaIngreso));
        exit();
    }

    // Validar que Color, Brix, SAC y ML Gastado no sean negativos
    if ($color < 0) {
        $error = "El valor de Color no puede ser negativo.";
    } elseif ($brix <= 0) {
        $error = "El valor de Brix debe ser mayor que cero.";
    } elseif ($sac <= 0) {
        $error = "El valor de SAC debe ser mayor que cero.";
    } elseif ($mlGastado < 0) {
        $error = "El valor de ML Gastado no puede ser negativo.";
    } else {
        // Verificar que Brix y SAC son válidos
        if ($brix != 0) {
            $pureza = round(($sac * 100) / $brix, 2);
        } else {
            $pureza = 0;
        }
    }

    // Si hay un error, redirigir al formulario con el mensaje de error
    if (!empty($error)) {
        header("Location: " . BASE_PATH . "controllers/meladura/editarMeladura.php?id=" . urlencode($idMeladura) . "&error=" . urlencode($error));
        exit();
    }

    try {
        $sql = "UPDATE meladura 
                SET hora = :hora, 
                    observacion = :observacion, 
                    color = :color,
                    brix = :brix, 
                    sac = :sac, 
                    mlGastado = :mlGastado 
                WHERE idMeladura = :idMeladura";

        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':hora', $hora);
        $stmt->bindParam(':observacion', $observacion);
        $stmt->bindParam(':color', $color);
        $stmt->bindParam(':brix', $brix);
        $stmt->bindParam(':sac', $sac);
        $stmt->bindParam(':mlGastado', $mlGastado);
        $stmt->bindParam(':idMeladura', $idMeladura);

        if ($stmt->execute()) {
            $detallesCambio = [];
            
            // Comparar cambios en cada campo y guardar solo si hay cambios
            if($registro['hora'] !== $hora) {
                $detallesCambio[] = "Hora: {$registro['hora']} -> $hora";
            }
            if(($registro['observacion'] ?? '') !== ($observacion ?? '')) {
                $detallesCambio[] = "Observación: " . ($registro['observacion'] ?? '') . " -> " . ($observacion ?? '');
            }
            if($registro['color'] !== $color) {
                $detallesCambio[] = "Color: {$registro['color']} -> $color";
            }
            if($registro['brix'] !== $brix) {
                $detallesCambio[] = "Brix: {$registro['brix']} -> $brix";
            }
            if($registro['sac'] !== $sac) {
                $detallesCambio[] = "SAC: {$registro['sac']} -> $sac";
            }
            if($registro['mlGastado'] !== $mlGastado) {
                $detallesCambio[] = "ML Gastado: {$registro['mlGastado']} -> $mlGastado";
            }
            
            // Registrar solo si hubo cambios
            if(!empty($detallesCambio)) {
                registrarBitacora(
                    $_SESSION['nombre'],
                    "Edición de registro en Meladura",
                    $idMeladura,
                    "meladura",
                    implode(", ", $detallesCambio)
                );
            }
            $url = BASE_PATH . "controllers/meladura/mostrarMeladura.php?mensaje=Registro+actualizado+correctamente";
            $url .= "&periodoZafra=" . urlencode($periodoZafra);
            $url .= "&fechaIngreso=" . urlencode($fechaIngreso);
            header("Location: $url");
            exit();
        } else {
            echo "Error al actualizar el registro.";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Registro de Meladura</title>

    <!-- Bootstrap and SB Admin 2 Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="<?php echo BASE_PATH; ?>public/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .btn-update {
            background-color: #4e73df;
            color: white;
        }

        .btn-update:hover {
            background-color: #224abe;
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
                    <h1 class="h3 mb-2 text-gray-800">Editar Registro de Meladura</h1>

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

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form action="<?php echo BASE_PATH; ?>controllers/meladura/editarMeladura.php?id=<?php echo $idMeladura; ?>" method="POST">
                                <div class="mb-3">
                                    <label for="periodoZafra" class="form-label">Periodo Zafra:</label>
                                    <input type="text" id="periodoZafra" name="periodoZafra" class="form-control" value="<?php echo $registro['periodoZafra']; ?>" readonly>
                                </div>

                                <div class="mb-3">
                                    <label for="fechaIngreso" class="form-label">Fecha de Ingreso:</label>
                                    <input type="date" id="fechaIngreso" name="fechaIngreso" class="form-control" value="<?php echo $registro['fechaIngreso']; ?>" readonly required>
                                </div>

                                <div class="mb-3">
                                    <label for="hora" class="form-label">Hora:</label>
                                    <select id="hora" name="hora" class="form-select" required>
                                        <?php
                                        $horas = [
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
                                        ];
                                        foreach ($horas as $hora) {
                                            $selected = ($registro['hora'] == $hora . ':00') ? 'selected' : '';
                                            echo "<option value='$hora' $selected>$hora</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="color" class="form-label">Color:</label>
                                    <input type="number" id="color" name="color" class="form-control" step="0.01" min="0" value="<?php echo $registro['color']; ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="brix" class="form-label">Brix:</label>
                                    <input type="number" id="brix" name="brix" class="form-control" step="0.01" min="0" value="<?php echo $registro['brix']; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="sac" class="form-label">SAC:</label>
                                    <input type="number" id="sac" name="sac" class="form-control" step="0.01" min="0" value="<?php echo $registro['sac']; ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="mlGastado" class="form-label">ML Gastado:</label>
                                    <input type="number" id="mlGastado" name="mlGastado" class="form-control" step="0.01" min="0" value="<?php echo $registro['mlGastado']; ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="observacion" class="form-label">Observación:</label>
                                    <textarea id="observacion" name="observacion" class="form-control" rows="2" maxlength="100"><?php echo htmlspecialchars($observacion ?? ''); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <button type="submit" class="btn btn-update">Actualizar</button>
                                    <a href="<?= BASE_PATH ?>controllers/meladura/mostrarMeladura.php?periodoZafra=<?= urlencode($periodoZafra); ?>&fechaIngreso=<?= urlencode($fechaIngreso); ?>" class="btn btn-secondary">Regresar</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/vendor/jquery/jquery.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/js/sb-admin-2.min.js"></script>

</body>

</html>