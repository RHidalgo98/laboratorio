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
    $idSacoAzucar = $_GET['id'];

    // Obtener los datos actuales del registro
    try {
        $stmt = $conexion->prepare("SELECT * FROM sacoazucar WHERE idSacoAzucar = :idSacoAzucar");
        $stmt->bindParam(':idSacoAzucar', $idSacoAzucar, PDO::PARAM_INT);
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
    $turno = $_POST['turno'];
    $tnCanaACHSA = $_POST['tnCanaACHSA'];
    $moliendaTn = $_POST['moliendaTn'];
    $sacos50AzucarBlanco = $_POST['sacos50AzucarBlanco'];
    $sacosAzucarMorena = $_POST['sacosAzucarMorena'];
    $jumboAzucarBlanco = $_POST['jumboAzucarBlanco'];
    $observacion = $_POST['observacion'];
    $periodoZafra = $_POST['periodoZafra'] ?? $periodoZafra;
    $fechaIngreso = $_POST['fechaIngreso'] ?? $fechaIngreso;

    // Verificar si ya existe otro registro con el mismo turno, periodoZafra y fechaIngreso
    if (empty($error)) {
        try {
            $check_sql = "SELECT COUNT(*) FROM sacoazucar 
                          WHERE turno = :turno 
                          AND periodoZafra = :periodoZafra 
                          AND fechaIngreso = :fechaIngreso 
                          AND idSacoAzucar != :idSacoAzucar";
            $check_stmt = $conexion->prepare($check_sql);
            $check_stmt->bindParam(':turno', $turno);
            $check_stmt->bindParam(':periodoZafra', $periodoZafra);
            $check_stmt->bindParam(':fechaIngreso', $fechaIngreso);
            $check_stmt->bindParam(':idSacoAzucar', $idSacoAzucar);
            $check_stmt->execute();
            $count = $check_stmt->fetchColumn();

            if ($count > 0) {
                $error = "Ya existe un registro con el mismo turno, periodo de zafra y fecha.";
            }
        } catch (PDOException $e) {
            echo "Error al comprobar duplicados: " . $e->getMessage();
            exit();
        }
    }
    
    // Validar que los valores no sean negativos
    if (empty($error)) {
        if ($tnCanaACHSA < 0) {
            $error = "El valor de Toneladas de Caña ACHSA no puede ser negativo.";
        } elseif ($moliendaTn < 0) {
            $error = "El valor de Molienda no puede ser negativo.";
        } elseif ($sacos50AzucarBlanco < 0) {
            $error = "El valor de Sacos de Azúcar Blanco no puede ser negativo.";
        } elseif ($sacosAzucarMorena < 0) {
            $error = "El valor de Sacos de Azúcar Morena no puede ser negativo.";
        } elseif ($jumboAzucarBlanco < 0) {
            $error = "El valor de Sacos Jumbo de Azúcar Blanco no puede ser negativo.";
        }
    }

    // Si no hay errores, proceder con la actualización
    if (empty($error)) {
        try {
            $sql = "UPDATE sacoazucar 
                    SET turno = :turno, 
                        tnCanaACHSA = :tnCanaACHSA, 
                        moliendaTn = :moliendaTn,
                        sacos50AzucarBlanco = :sacos50AzucarBlanco, 
                        sacosAzucarMorena = :sacosAzucarMorena, 
                        jumboAzucarBlanco = :jumboAzucarBlanco, 
                        observacion = :observacion,
                        periodoZafra = :periodoZafra, 
                        fechaIngreso = :fechaIngreso 
                    WHERE idSacoAzucar = :idSacoAzucar";

            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':turno', $turno);
            $stmt->bindParam(':tnCanaACHSA', $tnCanaACHSA);
            $stmt->bindParam(':moliendaTn', $moliendaTn);
            $stmt->bindParam(':sacos50AzucarBlanco', $sacos50AzucarBlanco);
            $stmt->bindParam(':sacosAzucarMorena', $sacosAzucarMorena);
            $stmt->bindParam(':jumboAzucarBlanco', $jumboAzucarBlanco);
            $stmt->bindParam(':observacion', $observacion);
            $stmt->bindParam(':periodoZafra', $periodoZafra);
            $stmt->bindParam(':fechaIngreso', $fechaIngreso);
            $stmt->bindParam(':idSacoAzucar', $idSacoAzucar);

            $stmt->execute();

            $detallesCambio = "Turno: " . ($registro['turno'] ?? '') . " → $turno, ";
            $detallesCambio .= "Toneladas de Caña ACHSA: " . ($registro['tnCanaACHSA'] ?? '') . " → $tnCanaACHSA, ";
            $detallesCambio .= "Toneladas de Molienda: " . ($registro['moliendaTn'] ?? '') . " → $moliendaTn, ";
            $detallesCambio .= "Sacos de Azúcar Blanco: " . ($registro['sacos50AzucarBlanco'] ?? '') . " → $sacos50AzucarBlanco, ";
            $detallesCambio .= "Sacos de Azúcar Morena: " . ($registro['sacosAzucarMorena'] ?? '') . " → $sacosAzucarMorena, ";
            $detallesCambio .= "Jumbo de Azúcar Blanco: " . ($registro['jumboAzucarBlanco'] ?? '') . " → $jumboAzucarBlanco, ";
            $detallesCambio .= "Observación: " . ($registro['observacion'] ?? '') . " → $observacion";

            registrarBitacora(
                $_SESSION['nombre'],
                "Edición de registro en Saco de Azúcar",
                $idSacoAzucar,
                "sacoazucar",
                $detallesCambio
            );

            // Redirigir después de la actualización
            $redirectUrl = BASE_PATH . "controllers/sacoAzucar/mostrarSacoAzucar.php?mensaje=Registro+actualizado+correctamente";
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
        header("Location: " . BASE_PATH . "controllers/sacoAzucar/editarSacoAzucar.php?id=" . urlencode($idSacoAzucar) . "&error=" . urlencode($error));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Saco Azúcar</title>
    <link href="<?= BASE_PATH ?>public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>public/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
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
                    <h1 class="h3 mb-2 text-gray-800">Editar Registro de Saco Azúcar</h1>

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
                                    <input type="text" id="periodoZafra" name="periodoZafra" class="form-control" value="<?php echo $registro['periodoZafra']; ?>" readonly>
                                </div>

                                <div class="mb-3">
                                    <label for="fechaIngreso" class="form-label">Fecha de Ingreso:</label>
                                    <input type="date" id="fechaIngreso" name="fechaIngreso" class="form-control" value="<?php echo $registro['fechaIngreso']; ?>" readonly required>
                                </div>
                                <div class="mb-3">
                                    <label for="turno" class="form-label">Turno:</label>
                                    <select id="turno" name="turno" class="form-control" required>
                                        <option value="Turno 1" <?= $registro['turno'] == 'Turno 1' ? 'selected' : ''; ?>>Turno 1</option>
                                        <option value="Turno 2" <?= $registro['turno'] == 'Turno 2' ? 'selected' : ''; ?>>Turno 2</option>
                                        <option value="Turno 3" <?= $registro['turno'] == 'Turno 3' ? 'selected' : ''; ?>>Turno 3</option>
                                    </select>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="tnCanaACHSA" class="form-label">Toneladas de Caña ACHSA:</label>
                                        <input type="number" id="tnCanaACHSA" name="tnCanaACHSA" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['tnCanaACHSA']); ?>" min="0">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="moliendaTn" class="form-label">Toneladas de Molienda:</label>
                                        <input type="number" id="moliendaTn" name="moliendaTn" class="form-control" step="0.01" value="<?= htmlspecialchars($registro['moliendaTn']); ?>" min="0">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="sacos50AzucarBlanco" class="form-label">Sacos de Azúcar Blanco:</label>
                                        <input type="number" id="sacos50AzucarBlanco" name="sacos50AzucarBlanco" class="form-control" value="<?= htmlspecialchars($registro['sacos50AzucarBlanco']); ?>" min="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="sacosAzucarMorena" class="form-label">Sacos de Azúcar Morena:</label>
                                        <input type="number" id="sacosAzucarMorena" name="sacosAzucarMorena" class="form-control" value="<?= htmlspecialchars($registro['sacosAzucarMorena']); ?>" min="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="jumboAzucarBlanco" class="form-label">Jumbo de 1.25 Toneladas de Azúcar Blanco:</label>
                                        <input type="number" id="jumboAzucarBlanco" name="jumboAzucarBlanco" class="form-control" value="<?= htmlspecialchars($registro['jumboAzucarBlanco']); ?>" min="0">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="observacion" class="form-label">Observación:</label>
                                    <textarea id="observacion" name="observacion" class="form-control"><?= htmlspecialchars($registro['observacion'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-update">Actualizar</button>
                                <a href="<?= BASE_PATH ?>controllers/sacoAzucar/mostrarSacoAzucar.php?periodoZafra=<?= urlencode($periodoZafra); ?>&fechaIngreso=<?= urlencode($fechaIngreso); ?>" class="btn btn-secondary">Regresar</a>
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