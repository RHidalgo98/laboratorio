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
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Periodo de Zafra</title>
    <link href="<?= BASE_PATH ?>public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>public/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>public/css/sb-admin-2.min.css" rel="stylesheet">
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
                    <h1 class="h3 mb-4 text-gray-800">Agregar Nuevo Periodo de Zafra</h1>

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
                            <form action="<?= BASE_PATH ?>controllers/periodoZafra/insertarPeriodoZafra.php" method="POST">
                                <div class="mb-3">
                                    <label for="periodo" class="form-label">Periodo (Ej. 2025-2026):</label>
                                    <input type="text" id="periodo" name="periodo" class="form-control" required placeholder="Ejemplo: 2025-2026">
                                </div>
                                <div class="mb-3">
                                    <label for="inicio" class="form-label">Fecha de Inicio:</label>
                                    <input type="date" id="inicio" name="inicio" class="form-control" required onkeydown="return false;">
                                </div>
                                <div class="mb-3">
                                    <label for="fin" class="form-label">Fecha de Fin:</label>
                                    <input type="date" id="fin" name="fin" class="form-control" required onkeydown="return false;">
                                </div>
                                <div class="mb-3">
                                    <label for="activo" class="form-label">Activo:</label>
                                    <select id="activo" name="activo" class="form-select">
                                        <option value="1">Sí</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Registrar</button>
                                <a href="<?= BASE_PATH ?>controllers/periodoZafra/mostrarPeriodoZafra.php" class="btn btn-secondary">Regresar</a>
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

    
</body>

</html>