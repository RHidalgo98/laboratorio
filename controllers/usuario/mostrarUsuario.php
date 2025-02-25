<?php
include $_SERVER['DOCUMENT_ROOT'] . '/AnalisisLaboratorio/config/config.php';
include ROOT_PATH . 'config/conexion.php';

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
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mostrar Usuarios</title>
    <!-- SB Admin 2 Stylesheets -->
    <link href="<?= BASE_PATH ?>public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>public/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>public/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .btn-new {
            background-color: #4e73df;
            color: white;
        }

        .btn-new:hover {
            background-color: #224abe;
        }

        .action-icons {
            cursor: pointer;
            font-size: 1.2em;
            color: #ffc107;
        }

        .table-info {
            font-weight: bold;
            background-color: #e0f7fa;
        }

        .table th {
            width: 10%;
            border-color: #89e2c2;
            background-color: #d1e7dd;
            color: #000000;
            vertical-align: middle;
            padding: 5px;
        }

        .table td {
            text-align: center;
            vertical-align: middle;
            padding: 5px;
        }

        .toggle-password {
            cursor: pointer;
            font-size: 1.2em;
            color: #6c757d;
        }

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
    </style>
</head>

<body id="page-top">

    <div id="wrapper">
        <?php include ROOT_PATH . 'includes/sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include ROOT_PATH . 'includes/topbar.php'; ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Gestión de Usuarios</h1>

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

                    <a href="<?= BASE_PATH ?>forms/registrarUsuario.php" class="btn btn-new mb-4">Agregar Nuevo Usuario</a>
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <?php
                            $sql = "SELECT idUsuario, nombre, apellido, correoElectronico, contrasena, rol, estado, fechaCreacion, ultimaActualizacion FROM usuarios ORDER BY rol ASC";
                            $stmt = $conexion->prepare($sql);
                            $stmt->execute();
                            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="tablaUsuarios">
                                    <thead>
                                        <tr>
                                            <th class="text-center">Nombre</th>
                                            <th class="text-center">Apellido</th>
                                            <th class="text-center">Correo Electrónico</th>
                                            <th class="text-center">Contraseña</th>
                                            <th class="text-center">Rol</th>
                                            <th class="text-center">Estado</th>
                                            <th class="text-center">Fecha de Creación</th>
                                            <th class="text-center">Última Actualización</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <tr>
                                                <td class="text-center"><?= htmlspecialchars($usuario['nombre']); ?></td>
                                                <td><?= htmlspecialchars($usuario['apellido']); ?></td>
                                                <td><?= htmlspecialchars($usuario['correoElectronico']); ?></td>
                                                <td>
                                                    <span class="password" data-password="<?= htmlspecialchars($usuario['contrasena']); ?>">********</span>
                                                    <i class="fas fa-eye toggle-password"></i>
                                                </td>
                                                <td><?= htmlspecialchars($usuario['rol']); ?></td>
                                                <td><?= $usuario['estado'] ? 'Activo' : 'Inactivo'; ?></td>
                                                <td><?= htmlspecialchars($usuario['fechaCreacion']); ?></td>
                                                <td><?= htmlspecialchars($usuario['ultimaActualizacion']); ?></td>
                                                <td>
                                                    <a href="<?= BASE_PATH ?>controllers/usuario/editarUsuario.php?id=<?= $usuario['idUsuario']; ?>" class="action-icons" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?= BASE_PATH ?>controllers/usuario/eliminarUsuario.php?id=<?= $usuario['idUsuario']; ?>"
                                                        class="text-danger action-icons"
                                                        onclick="return confirm('¿Estás seguro de eliminar este registro?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                </table>
                            </div>
                            </table>
                        </div>
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

        <!-- DataTables Initialization -->
        <script>
            $(document).ready(function() {
                $('#tablaUsuarios').DataTable({
                    "paging": true,
                    "searching": true,
                    "order": [
                        [4, "asc"]
                    ],
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.13.1/i18n/es-ES.json"
                    }
                });

                $('.toggle-password').on('click', function() {
                    var $password = $(this).siblings('.password');
                    var password = $password.data('password');
                    if ($password.text() === '********') {
                        $password.text(password);
                        $(this).removeClass('fa-eye').addClass('fa-eye-slash');
                    } else {
                        $password.text('********');
                        $(this).removeClass('fa-eye-slash').addClass('fa-eye');
                    }
                });
            });
        </script>
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
    </div>
</body>

</html>