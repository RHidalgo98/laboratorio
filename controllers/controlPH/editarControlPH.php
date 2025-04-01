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
    $idControlPH = $_GET['id'];

    // Obtener los datos actuales del registro
    try {
        $stmt = $conexion->prepare("SELECT * FROM controlph WHERE idControlPH = :idControlPH");
        $stmt->bindParam(':idControlPH', $idControlPH, PDO::PARAM_INT);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$registro) {
            echo "Registro no encontrado.";
            exit();
        }

        // Asignar valores si existen, de lo contrario, vacíos
        $periodoZafra = $registro['periodoZafra'] ?? '';
        $fechaIngreso = $registro['fechaIngreso'] ?? '';
        $observacion = htmlspecialchars($registro['observacion'] ?? '', ENT_QUOTES, 'UTF-8');
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
    $primario = $_POST['primario'];
    $mezclado = $_POST['mezclado'];
    $residual = $_POST['residual'];
    $sulfitado = $_POST['sulfitado'];
    $filtrado = $_POST['filtrado'];
    $alcalizado = $_POST['alcalizado'];
    $clarificado = $_POST['clarificado'];
    $meladura = $_POST['meladura'];
    $observacion = $_POST['observacion'];
    $periodoZafra = $_POST['periodoZafra'] ?? $periodoZafra;
    $fechaIngreso = $_POST['fechaIngreso'] ?? $fechaIngreso;

    // Verificar si ya existe otro registro con la misma hora, periodoZafra y fechaIngreso
    if (empty($error)) {
        try {
            $check_sql = "SELECT COUNT(*) FROM controlph 
                          WHERE hora = :hora 
                          AND periodoZafra = :periodoZafra 
                          AND fechaIngreso = :fechaIngreso 
                          AND idControlPH != :idControlPH";
            $check_stmt = $conexion->prepare($check_sql);
            $check_stmt->bindParam(':hora', $hora);
            $check_stmt->bindParam(':periodoZafra', $periodoZafra);
            $check_stmt->bindParam(':fechaIngreso', $fechaIngreso);
            $check_stmt->bindParam(':idControlPH', $idControlPH);
            $check_stmt->execute();
            $count = $check_stmt->fetchColumn();

            if ($count > 0) {
                $error = "Ya existe un registro con la misma hora, periodo de zafra y fecha.";
            }
        } catch (PDOException $e) {
            echo "Error al comprobar duplicados: " . $e->getMessage();
            exit();
        }
    }

    //Validar campos numéricos negativos
    if (empty($error)) {
        if ($primario < 0) {
            $error = "El valor de Primario no puede ser negativo.";
        } elseif ($mezclado < 0) {
            $error = "El valor de Mezclado no puede ser negativo.";
        } elseif ($residual < 0) {
            $error = "El valor de Residual no puede ser negativo.";
        } elseif ($sulfitado < 0) {
            $error = "El valor de Sulfitado no puede ser negativo.";
        } elseif ($filtrado < 0) {
            $error = "El valor de Filtrado no puede ser negativo.";
        } elseif ($alcalizado < 0) {
            $error = "El valor de Alcalizado no puede ser negativo.";
        } elseif ($clarificado < 0) {
            $error = "El valor de Clarificado no puede ser negativo.";
        } elseif ($meladura < 0) {
            $error = "El valor de Meladura no puede ser negativo.";
        }
    }

    // Si no hay errores, proceder con la actualización
    if (empty($error)) {
        try {
            $sql = "UPDATE controlph 
                    SET hora = :hora, 
                        primario = :primario, 
                        mezclado = :mezclado, 
                        residual = :residual, 
                        sulfitado = :sulfitado, 
                        filtrado = :filtrado, 
                        alcalizado = :alcalizado, 
                        clarificado = :clarificado, 
                        meladura = :meladura, 
                        observacion = :observacion,
                        periodoZafra = :periodoZafra, 
                        fechaIngreso = :fechaIngreso 
                    WHERE idControlPH = :idControlPH";

            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':hora', $hora);
            $stmt->bindParam(':primario', $primario);
            $stmt->bindParam(':mezclado', $mezclado);
            $stmt->bindParam(':residual', $residual);
            $stmt->bindParam(':sulfitado', $sulfitado);
            $stmt->bindParam(':filtrado', $filtrado);
            $stmt->bindParam(':alcalizado', $alcalizado);
            $stmt->bindParam(':clarificado', $clarificado);
            $stmt->bindParam(':meladura', $meladura);
            $stmt->bindParam(':observacion', $observacion);
            $stmt->bindParam(':periodoZafra', $periodoZafra);
            $stmt->bindParam(':fechaIngreso', $fechaIngreso);
            $stmt->bindParam(':idControlPH', $idControlPH);

            $stmt->execute();

            $detallesCambio = "";
            if ($registro['hora'] != $hora) {
                $detallesCambio .= "Hora: " . ($registro['hora'] ?? '') . " -> $hora, ";
            }
            if ($registro['primario'] != $primario) {
                $detallesCambio .= "Primario: " . ($registro['primario'] ?? '') . " -> $primario, ";
            }
            if ($registro['mezclado'] != $mezclado) {
                $detallesCambio .= "Mezclado: " . ($registro['mezclado'] ?? '') . " -> $mezclado, ";
            }
            if ($registro['residual'] != $residual) {
                $detallesCambio .= "Residual: " . ($registro['residual'] ?? '') . " -> $residual, ";
            }
            if ($registro['sulfitado'] != $sulfitado) {
                $detallesCambio .= "Sulfitado: " . ($registro['sulfitado'] ?? '') . " -> $sulfitado, ";
            }
            if ($registro['filtrado'] != $filtrado) {
                $detallesCambio .= "Filtrado: " . ($registro['filtrado'] ?? '') . " -> $filtrado, ";
            }
            if ($registro['alcalizado'] != $alcalizado) {
                $detallesCambio .= "Alcalizado: " . ($registro['alcalizado'] ?? '') . " -> $alcalizado, ";
            }
            if ($registro['clarificado'] != $clarificado) {
                $detallesCambio .= "Clarificado: " . ($registro['clarificado'] ?? '') . " -> $clarificado, ";
            }
            if ($registro['meladura'] != $meladura) {
                $detallesCambio .= "Meladura: " . ($registro['meladura'] ?? '') . " -> $meladura, ";
            }
            if ($registro['observacion'] != $observacion) {
                $detallesCambio .= "Observación: " . ($registro['observacion'] ?? '') . " -> $observacion";
            }

            if (!empty($detallesCambio)) {
                registrarBitacora(
                    $_SESSION['nombre'],
                    "Edición de registro en Control de PH",
                    $idControlPH,
                    "controlph",
                    rtrim($detallesCambio, ', ')
                );
            }

            // Redirigir después de la actualización
            $redirectUrl = BASE_PATH . "controllers/controlPH/mostrarControlPH.php?mensaje=Registro+actualizado+correctamente";
            if (!empty($periodoZafra)) {
                $redirectUrl .= "&periodoZafra=" . urlencode($periodoZafra);
            }
            if (!empty($fechaIngreso)) {
                $redirectUrl .= "&fechaIngreso=" . urlencode($fechaIngreso);
            }
            header("Location: $redirectUrl");
            exit();
        } catch (PDOException $e) {
            echo "Error al actualizar el registro: " . $e->getMessage();
        }
    } else {
        // Redirigir en caso de error
        header("Location: " . BASE_PATH . "controllers/controlPH/editarControlPH.php?id=" . urlencode($idControlPH) . "&error=" . urlencode($error));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Control de PH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>public/css/sb-admin-2.min.css" rel="stylesheet">

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
                    <h1 class="h3 mb-2 text-gray-800">Editar Registro de Control de PH</h1>

                    <!-- Mostrar mensajes de éxito o error -->
                    <?php if (isset($_GET['mensaje'])): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($_GET['mensaje']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($_GET['error'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($_GET['error']); ?>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form action="" method="POST">
                                <div class="mb-3">
                                    <label for="periodoZafra" class="form-label">Periodo Zafra:</label>
                                    <input type="text" id="periodoZafra" name="periodoZafra" class="form-control" value="<?= htmlspecialchars($registro['periodoZafra']); ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="fechaIngreso" class="form-label">Fecha de Ingreso:</label>
                                    <input type="date" id="fechaIngreso" name="fechaIngreso" class="form-control" value="<?= htmlspecialchars($registro['fechaIngreso']); ?>" readonly required>
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
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="primario" class="form-label">Primario:</label>
                                        <input type="number" id="primario" name="primario" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['primario'] ?? ''); ?>" min="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="mezclado" class="form-label">Mezclado:</label>
                                        <input type="number" id="mezclado" name="mezclado" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['mezclado'] ?? ''); ?>" min="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="residual" class="form-label">Residual:</label>
                                        <input type="number" id="residual" name="residual" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['residual'] ?? ''); ?>" min="0">
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <label for="sulfitado" class="form-label">Sulfitado:</label>
                                        <input type="number" id="sulfitado" name="sulfitado" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['sulfitado'] ?? ''); ?>" min="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="filtrado" class="form-label">Filtrado:</label>
                                        <input type="number" id="filtrado" name="filtrado" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['filtrado'] ?? ''); ?>" min="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="alcalizado" class="form-label">Alcalizado:</label>
                                        <input type="number" id="alcalizado" name="alcalizado" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['alcalizado'] ?? ''); ?>" min="0">
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <label for="clarificado" class="form-label">Clarificado:</label>
                                        <input type="number" id="clarificado" name="clarificado" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['clarificado'] ?? ''); ?>" min="0">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="meladura" class="form-label">Meladura:</label>
                                        <input type="number" id="meladura" name="meladura" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['meladura'] ?? ''); ?>" min="0">
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <label for="observacion" class="form-label">Observación:</label>
                                        <textarea id="observacion" name="observacion" class="form-control"><?= htmlspecialchars($registro['observacion'] ?? ''); ?></textarea>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <button type="submit" class="btn btn-update">Actualizar</button>
                                        <a href="<?= BASE_PATH ?>controllers/controlPH/mostrarControlPH.php?periodoZafra=<?= urlencode($periodoZafra); ?>&fechaIngreso=<?= urlencode($fechaIngreso); ?>" class="btn btn-secondary">Regresar</a>
                                    </div>
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