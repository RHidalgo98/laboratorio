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
    $idAguaImbibicion = $_GET['id'];

    // Obtener los datos actuales del registro
    try {
        $stmt = $conexion->prepare("SELECT * FROM aguaimbibicion WHERE idAguaImbibicion = :idAguaImbibicion");
        $stmt->bindParam(':idAguaImbibicion', $idAguaImbibicion, PDO::PARAM_INT);
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

// Verificar si ya existe un registro
$registroExistente = false;
$query = "SELECT COUNT(*) as count FROM aguaimbibicion WHERE periodoZafra = :periodoZafra AND fechaIngreso = :fechaIngreso";
$stmt = $conexion->prepare($query);
if ($stmt === false) {
    die("Error: Fallo en la preparación de la consulta SQL. " . $conexion->errorInfo()[2]);
}
$stmt->execute([':periodoZafra' => $periodoZafra, ':fechaIngreso' => $fechaIngreso]);
$registroCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
if ($registroCount > 1) {
    $registroExistente = true;
}
$stmt->closeCursor();

// Actualizar los datos si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hora = $_POST['hora'];
    $marcador = $_POST['marcador'];
    $totalizador = $_POST['totalizador'];
    $valorInicial = $_POST['valorInicial'] ?? $registro['valorInicial'];
    $observacion = $_POST['observacion'] ?? '';
    $periodoZafra = $_POST['periodoZafra'] ?? $periodoZafra;
    $fechaIngreso = $_POST['fechaIngreso'] ?? $fechaIngreso;

    // Validar campos obligatorios
    if (empty($hora) || empty($periodoZafra) || empty($fechaIngreso)) {
        $error = "El campo hora es obligatorio.";
    }

    // Verificar si marcador está vacío y asignarle NULL
    if ($marcador === '' || $marcador === null) {
        $marcador = null;
    }

    // Validar que los valores numéricos no sean negativos
    if ($marcador < 0 || $totalizador < 0 || $valorInicial < 0) {
        $error = "Los valores no pueden ser negativos.";
    }

    // Verificar si ya existe otro registro con la misma hora, periodoZafra y fechaIngreso
    if (empty($error)) {
        try {
            $check_sql = "SELECT COUNT(*) FROM aguaimbibicion 
                          WHERE hora = :hora 
                          AND periodoZafra = :periodoZafra 
                          AND fechaIngreso = :fechaIngreso 
                          AND idAguaImbibicion != :idAguaImbibicion";
            $check_stmt = $conexion->prepare($check_sql);
            $check_stmt->bindParam(':hora', $hora);
            $check_stmt->bindParam(':periodoZafra', $periodoZafra);
            $check_stmt->bindParam(':fechaIngreso', $fechaIngreso);
            $check_stmt->bindParam(':idAguaImbibicion', $idAguaImbibicion);
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

    // Si no hay errores, proceder con la actualización
    if (empty($error)) {
        try {
            $sql = "UPDATE aguaimbibicion 
                    SET hora = :hora, 
                        marcador = :marcador, 
                        totalizador = :totalizador, 
                        valorInicial = :valorInicial, 
                        observacion = :observacion,
                        periodoZafra = :periodoZafra, 
                        fechaIngreso = :fechaIngreso 
                    WHERE idAguaImbibicion = :idAguaImbibicion";

            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':hora', $hora);
            $stmt->bindParam(':marcador', $marcador);
            $stmt->bindParam(':totalizador', $totalizador);
            $stmt->bindParam(':valorInicial', $valorInicial);
            $stmt->bindParam(':observacion', $observacion);
            $stmt->bindParam(':periodoZafra', $periodoZafra);
            $stmt->bindParam(':fechaIngreso', $fechaIngreso);
            $stmt->bindParam(':idAguaImbibicion', $idAguaImbibicion);

            $stmt->execute();

            $detallesCambio = "";
            if ($registro['hora'] != $hora) {
                $detallesCambio .= "Hora: " . ($registro['hora'] ?? '') . " -> $hora, ";
            }
            if ($registro['marcador'] != $marcador) {
                $detallesCambio .= "Marcador: " . ($registro['marcador'] ?? '') . " -> $marcador, ";
            }
            if ($registro['totalizador'] != $totalizador) {
                $detallesCambio .= "Totalizador: " . ($registro['totalizador'] ?? '') . " -> $totalizador, ";
            }
            if ($registro['valorInicial'] != $valorInicial) {
                $detallesCambio .= "Valor Inicial: " . ($registro['valorInicial'] ?? '') . " -> $valorInicial, ";
            }
            if ($registro['observacion'] != $observacion) {
                $detallesCambio .= "Observación: " . ($registro['observacion'] ?? '') . " -> $observacion";
            }

            if (!empty($detallesCambio)) {
                registrarBitacora(
                    $_SESSION['nombre'],
                    "Edición de registro en Agua de Imbibición",
                    $idAguaImbibicion,
                    "aguaimbibicion",
                    rtrim($detallesCambio, ', ')
                );
            }

            // Redirigir después de la actualización
            $redirectUrl = BASE_PATH . "controllers/aguaimbibicion/mostrarAguaImbibicion.php?mensaje=Registro+actualizado+correctamente";
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
        header("Location: " . BASE_PATH . "controllers/aguaimbibicion/editarAguaImbibicion.php?id=" . urlencode($idAguaImbibicion) . "&error=" . urlencode($error));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Agua de Imbibición</title>
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
                    <h1 class="h3 mb-2 text-gray-800">Editar Registro de Agua de Imbibición</h1>

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
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="marcador" class="form-label">Marcador:</label>
                                        <input type="number" id="marcador" name="marcador" class="form-control" step="1" value="<?= htmlspecialchars($registro['marcador'] ?? ''); ?>" min="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="totalizador" class="form-label">Totalizador:</label>
                                        <input type="number" id="totalizador" name="totalizador" class="form-control" step="1" required value="<?= htmlspecialchars($registro['totalizador'] ?? ''); ?>" min="0">
                                    </div>
                                    <?php if (!$registroExistente): ?>
                                        <div class="col-md-4">
                                            <label for="valorInicial" class="form-label">Valor Inicial:</label>
                                            <input type="number" id="valorInicial" name="valorInicial" class="form-control" step="1" min="0" value="<?= htmlspecialchars($registro['valorInicial'] ?? '0'); ?>" required>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <label for="observacion" class="form-label">Observación:</label>
                                        <textarea id="observacion" name="observacion" class="form-control"><?= htmlspecialchars($registro['observacion'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary mt-3">Actualizar</button>
                                <a href="<?= BASE_PATH ?>controllers/aguaimbibicion/mostrarAguaImbibicion.php?periodoZafra=<?= !empty($periodoZafra) ? urlencode($periodoZafra) : ''; ?>&fechaIngreso=<?= !empty($fechaIngreso) ? urlencode($fechaIngreso) : ''; ?>" class="btn btn-secondary mt-3">Regresar</a>
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