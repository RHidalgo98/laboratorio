<?php
include $_SERVER['DOCUMENT_ROOT'] . '/AnalisisLaboratorio/config/config.php';
include ROOT_PATH . 'config/conexion.php';

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
    <title>Bitácora del Sistema</title>
    <link href="<?= BASE_PATH ?>public/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css">
    <link href="<?= BASE_PATH ?>public/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_PATH ?>public/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .table-info {
            font-weight: bold;
            background-color: #e0f7fa;
        }

        .table th {
            border-color: #89e2c2;
            background-color: #d1e7dd;
            vertical-align: middle;
            padding: 12px;
        }

        .table td {
            text-align: center;
            vertical-align: middle;
            padding: 5px;
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
                    <h1 class="h3 mb-4 text-gray-800">Registros de Bitácora</h1>

                    <!-- Mensajes de estado -->
                    <?php if (isset($_GET['mensaje'])): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($_GET['mensaje']) ?></div>
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-body table-responsive">
                            <?php
                            $sql = "SELECT idBitacora, nombreUsuario, fechaHora, descripcion, 
                                   idRegistro, nombreTabla, detallesCambio 
                            FROM bitacora 
                            ORDER BY fechaHora DESC";

                            $stmt = $conexion->prepare($sql);
                            $stmt->execute();
                            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>

                            <button id="exportarPDF" class="btn btn-danger mb-4">Exportar a PDF</button>

                            <div class="table-responsive">
                                <table class="table table-bordered" id="tablaBitacora" width="100%" cellspacing="0">
                                    <thead class="table-success">
                                        <tr>
                                            <th class="text-center">Usuario</th>
                                            <th class="text-center">Fecha/Hora</th>
                                            <th class="text-center">Descripción</th>
                                            <th class="text-center">ID Registro</th>
                                            <th class="text-center">Tabla</th>
                                            <th class="text-center">Detalles del Cambio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($registros as $registro): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($registro['nombreUsuario']) ?></td>
                                                <td><?= htmlspecialchars($registro['fechaHora']) ?></td>
                                                <td><?= htmlspecialchars($registro['descripcion']) ?></td>
                                                <td class="text-center"><?= $registro['idRegistro'] ?></td>
                                                <td class="text-center"><?= htmlspecialchars($registro['nombreTabla']) ?></td>
                                                <td><?= nl2br(htmlspecialchars($registro['detallesCambio'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
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
            <script src="<?= BASE_PATH; ?>public/js/sb-admin-2.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.13/jspdf.plugin.autotable.min.js"></script>

            <!-- DataTables Initialization -->
            <script>
                $(document).ready(function() {
                    $('#tablaBitacora').DataTable({
                        "order": [
                            [1, "desc"]
                        ], // Ordenar por fecha más reciente primero
                        "language": {
                            "url": "//cdn.datatables.net/plug-ins/1.13.1/i18n/es-ES.json"
                        },
                        "columnDefs": [{
                                "width": "15%",
                                "targets": 1
                            }, // Ancho columna fecha/hora
                            {
                                "width": "25%",
                                "targets": 5
                            } // Ancho columna detalles
                        ]
                    });

                    $('#exportarPDF').on('click', function() {
                        const { jsPDF } = window.jspdf;
                        const doc = new jsPDF('landscape'); // Cambiar la orientación a horizontal
                        doc.autoTable({ html: '#tablaBitacora' });
                        doc.save('bitacora.pdf');
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