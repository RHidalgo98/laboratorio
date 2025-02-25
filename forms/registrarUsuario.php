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
    <title>Agregar Usuario</title>
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
                    <h1 class="h3 mb-4 text-gray-800">Agregar Nuevo Usuario</h1>

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
                            <form action="<?= BASE_PATH ?>controllers/usuario/insertarUsuario.php" method="POST" onsubmit="return validarFormulario()">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre:</label>
                                    <input type="text" id="nombre" name="nombre" class="form-select" required pattern="[A-Za-z]+" title="Solo se permiten letras">
                                </div>
                                <div class="mb-3">
                                    <label for="apellido" class="form-label">Apellido:</label>
                                    <input type="text" id="apellido" name="apellido" class="form-select" required pattern="[A-Za-z]+" title="Solo se permiten letras">
                                </div>
                                <div class="mb-3">
                                    <label for="correoElectronico" class="form-label">Correo Electrónico:</label>
                                    <input type="email" id="correoElectronico" name="correoElectronico" class="form-control" required pattern="[a-zA-Z0-9._%+-]+@cahsa\.com$" title="El correo debe tener el formato @cahsa.com">
                                </div>
                                <div class="mb-3">
                                    <label for="contrasena" class="form-label">Contraseña:</label>
                                    <div class="input-group">
                                        <input type="password" id="contrasena" name="contrasena" class="form-control" required>
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility()">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="rol" class="form-label">Rol:</label>
                                    <select id="rol" name="rol" class="form-control">
                                        <option value="Administrador">Administrador</option>
                                        <option value="Usuario">Usuario</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="estado" class="form-label">Estado:</label>
                                    <select id="estado" name="estado" class="form-control">
                                        <option value="1">Activo</option>
                                        <option value="0">Inactivo</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Registrar</button>
                                <a href="<?= BASE_PATH ?>controllers/usuario/mostrarUsuario.php" class="btn btn-secondary">Regresar</a>
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
        function validarFormulario() {
            var correo = document.getElementById('correoElectronico').value;
            var correoRegex = /^[a-zA-Z0-9._%+-]+@cahsa\.com$/;
            if (!correoRegex.test(correo)) {
                alert('El correo debe tener el formato @cahsa.com');
                return false;
            }
            return true;
        }

        function togglePasswordVisibility() {
            var passwordField = document.getElementById('contrasena');
            var passwordFieldType = passwordField.getAttribute('type');
            if (passwordFieldType === 'password') {
                passwordField.setAttribute('type', 'text');
            } else {
                passwordField.setAttribute('type', 'password');
            }
        }
    </script>
</body>

</html>