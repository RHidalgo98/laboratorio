<?php
include $_SERVER['DOCUMENT_ROOT'] . '/AnalisisLaboratorio/config/config.php';
include ROOT_PATH . 'config/conexion.php';
include ROOT_PATH . 'includes/bitacora.php';

// Iniciar sesión si no está iniciada
session_start();

// Verificar autenticación
if (!isset($_SESSION['idUsuario'])) {
    header("Location: " . BASE_PATH . "controllers/login.php");
    exit();
}

try {
    // Obtener datos del usuario por su ID
    $stmt = $conexion->prepare("SELECT rol, nombre FROM usuarios WHERE idUsuario = ?");
    $stmt->execute([$_SESSION['idUsuario']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verificar si el usuario existe
    if (!$usuario) {
        header("Location: " . BASE_PATH . "controllers/login.php");
        exit();
    }

    // Validar rol (ajusta según cómo se almacene en tu BD)
    if (strtolower($usuario['rol']) !== 'administrador') {
        echo "<script>
                alert('Acceso denegado: No tienes permisos de administrador.');
                window.location.href = '/AnalisisLaboratorio/index.php';
              </script>";
        exit();
    }

    // Asignar nombre de usuario
    $user_name = $usuario['nombre'] ?? 'Usuario';

} catch (PDOException $e) {
    error_log("Error de base de datos: " . $e->getMessage());
    header("Location: " . BASE_PATH . "error.php");
    exit();
}

$error = ""; // Variable para capturar errores

if (isset($_GET['id'])) {
    $idPeriodo = $_GET['id'];

    try {
        $sql = "SELECT * FROM periodoszafra WHERE idPeriodo = :idPeriodo";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':idPeriodo', $idPeriodo);
        $stmt->execute();
        $periodo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$periodo) {
            echo "Periodo no encontrado.";
            exit();
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
} else {
    echo "ID no recibido.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPeriodo = $_POST['idPeriodo'];
    $periodoStr = $_POST['periodo'];
    $inicio = $_POST['inicio'];
    $fin = $_POST['fin'];
    $activo = $_POST['activo'];

    try {
        // Validación de formato AAAA-AAAA
        if (!preg_match('/^\d{4}-\d{4}$/', $periodoStr)) {
            $error = "El periodo debe estar en el formato AAAA-AAAA. Ejemplo: 2025-2026.";
            header("Location: " . BASE_PATH . "controllers/periodoZafra/editarPeriodoZafra.php?id=" . $idPeriodo . "&error=" . urlencode($error));
            exit();
        }

        $sql = "UPDATE periodoszafra SET periodo = :periodo, inicio = :inicio, fin = :fin, activo = :activo WHERE idPeriodo = :idPeriodo";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':periodo', $periodoStr);
        $stmt->bindParam(':inicio', $inicio);
        $stmt->bindParam(':fin', $fin);
        $stmt->bindParam(':activo', $activo);
        $stmt->bindParam(':idPeriodo', $idPeriodo);

        if ($stmt->execute()) {
            $detallesCambio = [];
            
            // Comparar cambios en cada campo
            if($periodo['periodo'] != $periodoStr) {
                $detallesCambio[] = "Periodo: {$periodo['periodo']} → $periodoStr";
            }
            if($periodo['inicio'] != $inicio) {
                $detallesCambio[] = "Inicio: {$periodo['inicio']} → $inicio";
            }
            if($periodo['fin'] != $fin) {
                $detallesCambio[] = "Fin: {$periodo['fin']} → $fin";
            }
            if($periodo['activo'] != $activo) {
                $estadoAnterior = $periodo['activo'] ? 'Activo' : 'Inactivo';
                $estadoNuevo = $activo ? 'Activo' : 'Inactivo';
                $detallesCambio[] = "Estado: $estadoAnterior → $estadoNuevo";
            }
            
            // Registrar solo si hubo cambios
            if(!empty($detallesCambio)) {
                registrarBitacora(
                    $_SESSION['nombre'],
                    "Actualización de periodo de zafra",
                    $idPeriodo,
                    "periodoszafra",
                    implode(", ", $detallesCambio)
                );
            }
            header("Location: " . BASE_PATH . "controllers/periodoZafra/mostrarPeriodoZafra.php?mensaje=Registro+actualizado+correctamente");
            exit();
        } else {
            echo "Error al actualizar el periodo de zafra.";
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
    <title>Editar Periodo de Zafra</title>
    <link href="<?= BASE_PATH ?>public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
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

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">

                <?php include ROOT_PATH . 'includes/topbar.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Editar Periodo de Zafra</h1>

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
                                <input type="hidden" name="idPeriodo" value="<?= htmlspecialchars($periodo['idPeriodo']); ?>">
                                <div class="mb-3">
                                    <label for="periodo" class="form-label">Periodo:</label>
                                    <input type="text" id="periodo" name="periodo" class="form-control" value="<?= htmlspecialchars($periodo['periodo']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="inicio" class="form-label">Fecha de Inicio:</label>
                                    <input type="date" id="inicio" name="inicio" class="form-control" value="<?= $periodo['inicio']; ?>" required onkeydown="return false">
                                </div>
                                <div class="mb-3">
                                    <label for="fin" class="form-label">Fecha de Fin:</label>
                                    <input type="date" id="fin" name="fin" class="form-control" value="<?= $periodo['fin']; ?>" required onkeydown="return false">
                                </div>
                                <div class="mb-3">
                                    <label for="activo" class="form-label">Activo:</label>
                                    <select id="activo" name="activo" class="form-select">
                                        <option value="1" <?= $periodo['activo'] ? 'selected' : ''; ?>>Sí</option>
                                        <option value="0" <?= !$periodo['activo'] ? 'selected' : ''; ?>>No</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <button type="submit" class="btn btn-update">Actualizar</button>
                                    <a href="<?= BASE_PATH ?>controllers/periodoZafra/mostrarPeriodoZafra.php" class="btn btn-secondary">Regresar</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.querySelector("form");
            const periodoInput = document.getElementById("periodo");

            form.addEventListener("submit", function(event) {
                const periodoValue = periodoInput.value;
                const regex = /^\d{4}-\d{4}$/; // Patrón para validar formato AAAA-AAAA

                if (!regex.test(periodoValue)) {
                    event.preventDefault(); // Evita el envío del formulario
                    alert("El periodo debe estar en el formato AAAA-AAAA. Ejemplo: 2025-2026.");
                    periodoInput.focus();
                }
            });
        });
    </script>

    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/vendor/jquery/jquery.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="<?= BASE_PATH; ?>public/js/sb-admin-2.min.js"></script>
</body>

</html>