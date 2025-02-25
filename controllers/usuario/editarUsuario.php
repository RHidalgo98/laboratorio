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
    $idUsuario = $_GET['id'];

    try {
        $sql = "SELECT * FROM usuarios WHERE idUsuario = :idUsuario";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':idUsuario', $idUsuario);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            echo "Usuario no encontrado.";
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
    $idUsuario = $_POST['idUsuario'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correoElectronico = $_POST['correoElectronico'];
    if (!empty($_POST['contrasena'])) {
        $contrasena = ($_POST['contrasena']);
        $sql = "UPDATE usuarios SET nombre = :nombre, apellido = :apellido, correoElectronico = :correoElectronico, contrasena = :contrasena, rol = :rol, estado = :estado WHERE idUsuario = :idUsuario";
    } else {
        $sql = "UPDATE usuarios SET nombre = :nombre, apellido = :apellido, correoElectronico = :correoElectronico, rol = :rol, estado = :estado WHERE idUsuario = :idUsuario";
    }
    $rol = $_POST['rol'];
    $estado = $_POST['estado'];


    try {
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido', $apellido);
        $stmt->bindParam(':correoElectronico', $correoElectronico);
        if (!empty($_POST['contrasena'])) {
            $stmt->bindParam(':contrasena', $contrasena);
        }
        $stmt->bindParam(':rol', $rol);
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':idUsuario', $idUsuario);

        if ($stmt->execute()) {
            $cambios = [];
            
            // Comparar cambios en cada campo
            if($usuario['nombre'] != $nombre) {
                $cambios[] = "Nombre: {$usuario['nombre']} → $nombre";
            }
            if($usuario['apellido'] != $apellido) {
                $cambios[] = "Apellido: {$usuario['apellido']} → $apellido";
            }
            if($usuario['correoElectronico'] != $correoElectronico) {
                $cambios[] = "Correo: {$usuario['correoElectronico']} → $correoElectronico";
            }
            if($usuario['rol'] != $rol) {
                $cambios[] = "Rol: {$usuario['rol']} → $rol";
            }
            if($usuario['estado'] != $estado) {
                $cambios[] = "Estado: " . ($usuario['estado'] ? 'Activo' : 'Inactivo') . " → " . ($estado ? 'Activo' : 'Inactivo');
            }
            if(!empty($_POST['contrasena'])) {
                $cambios[] = "Contraseña actualizada";
            }
            
            // Registrar solo si hubo cambios
            if(!empty($cambios)) {
                registrarBitacora(
                    $_SESSION['nombre'],
                    "Actualización de usuario",
                    $idUsuario,
                    "usuarios",
                    implode(", ", $cambios)
                );
            }
            header("Location: " . BASE_PATH . "controllers/usuario/mostrarUsuario.php?mensaje=Registro+actualizado+correctamente");
            exit();
        } else {
            echo "Error al actualizar el usuario.";
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
    <title>Editar Usuario</title>
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
                    <h1 class="h3 mb-2 text-gray-800">Editar Usuario</h1>

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
                            <form action="" method="POST" onsubmit="return validarFormulario()">
                                <input type="hidden" name="idUsuario" value="<?= htmlspecialchars($usuario['idUsuario']); ?>">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre:</label>
                                    <input type="text" id="nombre" name="nombre" class="form-control" value="<?= htmlspecialchars($usuario['nombre']); ?>" required pattern="[A-Za-z]+" title="Solo se permiten letras">
                                </div>
                                <div class="mb-3">
                                    <label for="apellido" class="form-label">Apellido:</label>
                                    <input type="text" id="apellido" name="apellido" class="form-control" value="<?= htmlspecialchars($usuario['apellido']); ?>" required pattern="[A-Za-z]+" title="Solo se permiten letras">
                                </div>
                                <div class="mb-3">
                                    <label for="correoElectronico" class="form-label">Correo Electrónico:</label>
                                    <input type="email" id="correoElectronico" name="correoElectronico" class="form-control" value="<?= htmlspecialchars($usuario['correoElectronico']); ?>" required pattern="[a-zA-Z0-9._%+-]+@cahsa\.com$" title="El correo debe tener el formato @cahsa.com">
                                </div>
                                <div class="mb-3">
                                    <label for="contrasena" class="form-label">Nueva Contraseña (dejar en blanco para no cambiar):</label>
                                    <div class="input-group">
                                        <input type="password" id="contrasena" name="contrasena" class="form-control">
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility()">
                                            <i class="fas fa-eye" id="togglePasswordIcon"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="rol" class="form-label">Rol:</label>
                                    <select id="rol" name="rol" class="form-control">
                                        <option value="Administrador" <?= $usuario['rol'] == 'Administrador' ? 'selected' : ''; ?>>Administrador</option>
                                        <option value="Usuario" <?= $usuario['rol'] == 'Usuario' ? 'selected' : ''; ?>>Usuario</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="estado" class="form-label">Estado:</label>
                                    <select id="estado" name="estado" class="form-control">
                                        <option value="1" <?= $usuario['estado'] ? 'selected' : ''; ?>>Activo</option>
                                        <option value="0" <?= !$usuario['estado'] ? 'selected' : ''; ?>>Inactivo</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <button type="submit" class="btn btn-update">Actualizar</button>
                                    <a href="<?= BASE_PATH ?>controllers/usuario/mostrarUsuario.php" class="btn btn-secondary">Regresar</a>
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
            var passwordInput = document.getElementById('contrasena');
            var togglePasswordIcon = document.getElementById('togglePasswordIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                togglePasswordIcon.classList.remove('fa-eye');
                togglePasswordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                togglePasswordIcon.classList.remove('fa-eye-slash');
                togglePasswordIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>

</html>