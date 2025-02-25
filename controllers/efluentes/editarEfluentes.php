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
    $idEfluentes = $_GET['id'];

    // Obtener los datos actuales del registro
    try {
        $stmt = $conexion->prepare("SELECT * FROM efluentes WHERE idEfluentes = :idEfluentes");
        $stmt->bindParam(':idEfluentes', $idEfluentes, PDO::PARAM_INT);
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
    $enfriamiento = $_POST['enfriamiento'];
    $retorno = $_POST['retorno'];
    $desechos = $_POST['desechos'];
    $observacion = $_POST['observacion'] ?? '';
    $periodoZafra = $_POST['periodoZafra'] ?? $periodoZafra;
    $fechaIngreso = $_POST['fechaIngreso'] ?? $fechaIngreso;

    // Verificar si ya existe otro registro con la misma hora, periodoZafra y fechaIngreso
    if (empty($error)) {
        try {
            $check_sql = "SELECT COUNT(*) FROM efluentes 
                          WHERE hora = :hora 
                          AND periodoZafra = :periodoZafra 
                          AND fechaIngreso = :fechaIngreso 
                          AND idEfluentes != :idEfluentes";
            $check_stmt = $conexion->prepare($check_sql);
            $check_stmt->bindParam(':hora', $hora);
            $check_stmt->bindParam(':periodoZafra', $periodoZafra);
            $check_stmt->bindParam(':fechaIngreso', $fechaIngreso);
            $check_stmt->bindParam(':idEfluentes', $idEfluentes);
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

    // Validar que todos los campos requeridos estén presentes
    if (empty($periodoZafra) || empty($fechaIngreso) || empty($hora)) {
        $error = "Todos los campos son obligatorios.";
    }

    // Validar que Enfriamiento, Retorno y Desechos no sean negativos
    if (empty($error)) {
        if ($enfriamiento <= 0) {
            $error = "El valor de Enfriamiento debe ser mayor que cero.";
        } elseif ($retorno <= 0) {
            $error = "El valor de Retorno debe ser mayor que cero.";
        } elseif ($desechos < 0) {
            $error = "El valor de Desechos no puede ser negativo.";
        }
    }

    // Si no hay errores, proceder con la actualización
    if (empty($error)) {
        try {
            $sql = "UPDATE efluentes 
                    SET hora = :hora, 
                        enfriamiento = :enfriamiento, 
                        retorno = :retorno,
                        desechos = :desechos, 
                        observacion = :observacion,
                        periodoZafra = :periodoZafra, 
                        fechaIngreso = :fechaIngreso 
                    WHERE idEfluentes = :idEfluentes";

            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':hora', $hora);
            $stmt->bindParam(':enfriamiento', $enfriamiento);
            $stmt->bindParam(':retorno', $retorno);
            $stmt->bindParam(':desechos', $desechos);
            $stmt->bindParam(':observacion', $observacion);
            $stmt->bindParam(':periodoZafra', $periodoZafra);
            $stmt->bindParam(':fechaIngreso', $fechaIngreso);
            $stmt->bindParam(':idEfluentes', $idEfluentes);

            $stmt->execute();

            $detallesCambio .= "Enfriamiento: " . ($registro['enfriamiento'] ?? '') . " → $enfriamiento, ";
            $detallesCambio .= "Retorno: " . ($registro['retorno'] ?? '') . " → $retorno, ";
            $detallesCambio .= "Desechos: " . ($registro['desechos'] ?? '') . " → $desechos, ";
            $detallesCambio .= "Observación: " . ($registro['observacion'] ?? '') . " → $observacion";
            
            registrarBitacora(
                $_SESSION['nombre'],
                "Edición de registro en Efluentes",
                $idEfluentes,
                "efluentes",
                $detallesCambio
            );

            // Redirigir después de la actualización
            $redirectUrl = BASE_PATH . "controllers/efluentes/mostrarEfluentes.php?mensaje=Registro+actualizado+correctamente";
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
        header("Location: " . BASE_PATH . "controllers/efluentes/editarEfluentes.php?id=" . urlencode($idEfluentes) . "&error=" . urlencode($error));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Efluentes</title>
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
                    <h1 class="h3 mb-2 text-gray-800">Editar Registro de Efluentes</h1>

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
                                <div class="mb-3">
                                    <label for="enfriamiento" class="form-label">Enfriamiento:</label>
                                    <input type="number" id="enfriamiento" name="enfriamiento" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['enfriamiento']); ?>" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label for="retorno" class="form-label">Retorno:</label>
                                    <input type="number" id="retorno" name="retorno" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['retorno']); ?>" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label for="desechos" class="form-label">Desechos:</label>
                                    <input type="number" id="desechos" name="desechos" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['desechos']); ?>" min="0">
                                </div>
                                <div class="mb-3">
                                    <label for="observacion" class="form-label">Observación:</label>
                                    <textarea id="observacion" name="observacion" class="form-control"><?= htmlspecialchars($registro['observacion'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-update">Actualizar</button>
                                <a href="<?= BASE_PATH ?>controllers/efluentes/mostrarEfluentes.php?periodoZafra=<?= urlencode($periodoZafra); ?>&fechaIngreso=<?= urlencode($fechaIngreso); ?>" class="btn btn-secondary">Regresar</a>
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
