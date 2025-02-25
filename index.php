<?php
// Iniciar sesión si no está iniciada
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['idUsuario'])) {
    // Si no hay sesión activa, redirigir al formulario de login
    header("Location: login.html");
    exit(); // Asegurar que el script se detenga después de redirigir
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratorio</title>

    <!-- Custom fonts for this template-->
    <link href="public/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="public/css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- sidebar.php -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

            <!-- Sidebar - Brand -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
                <div class="sidebar-brand-icon rotate-n-15">
                    <i class="fas fa-laugh-wink"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Laboratorio</div>
            </a>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Dashboard -->
            <li class="nav-item active">
                <a class="nav-link" href="views/dashboard.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Views -->
            <li class="nav-item">
                <a class="nav-link" href="views/reporteDiario.php">
                    <i class="fas fa-fw fa-table"></i>
                    <span>Reporte Diario</span></a>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Jugos & Meladura -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseJugosMeladura" aria-expanded="true" aria-controls="collapseJugosMeladura">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Jugos & Meladura</span>
                </a>
                <div id="collapseJugosMeladura" class="collapse" aria-labelledby="headingJugosMeladura" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Análisis:</h6>
                        <a class="collapse-item" href="controllers/jugoPrimario/mostrarJugoPrimario.php">Primario</a>
                        <a class="collapse-item" href="controllers/jugoMezclado/mostrarJugoMezclado.php">Mezclado</a>
                        <a class="collapse-item" href="controllers/jugoResidual/mostrarJugoResidual.php">Residual</a>
                        <a class="collapse-item" href="controllers/jugoClarificado/mostrarJugoClarificado.php">Clarificado</a>
                        <a class="collapse-item" href="controllers/jugoFiltrado/mostrarJugoFiltrado.php">Filtrado</a>
                        <a class="collapse-item" href="controllers/meladura/mostrarMeladura.php">Meladura</a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Masas & Mieles -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseMasasMieles" aria-expanded="true" aria-controls="collapseMasasMieles">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Masas & Mieles</span>
                </a>
                <div id="collapseMasasMieles" class="collapse" aria-labelledby="headingMasasMieles" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Análisis:</h6>
                        <a class="collapse-item" href="controllers/masaCocidaA/mostrarMasaCocidaA.php">Masa Cocida A</a>
                        <a class="collapse-item" href="controllers/masaCocidaB/mostrarMasaCocidaB.php">Masa Cocida B</a>
                        <a class="collapse-item" href="controllers/masaCocidaC/mostrarMasaCocidaC.php">Masa Cocida C</a>
                        <a class="collapse-item" href="controllers/mielA/mostrarMielA.php">Miel A</a>
                        <a class="collapse-item" href="controllers/mielB/mostrarMielB.php">Miel B</a>
                        <a class="collapse-item" href="controllers/mielFinal/mostrarMielFinal.php">Miel Final</a>
                        <a class="collapse-item" href="controllers/analisisAzucar/mostrarAnalsisAzucar.php">Análisis de Azúcar</a>
                        <a class="collapse-item" href="controllers/magmaB/mostrarMagmaB.php">Magma B</a>
                        <a class="collapse-item" href="controllers/magmaC/mostrarMagmac.php">Magma C</a>
                        <a class="collapse-item" href="controllers/efluentes/mostrarEfluentes.php">Efluentes</a>
                        <a class="collapse-item" href="controllers/sacoAzucar/mostrarSacoAzucar.php">Saco Azúcar</a>
                        <!-- <a class="collapse-item" href="controllers/lecturas/mostrarLectura.php">Lecturas</a> -->
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - ph, Molinos, Bagazo & Cachazo -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsephMolinosBagazoCachaza" aria-expanded="true" aria-controls="collapsephMolinosBagazoCachaza">
                    <i class="fas fa-clipboard-list"></i>
                    <span>pH, Molinos, Bagazo & Cachaza</span>
                </a>
                <div id="collapsephMolinosBagazoCachaza" class="collapse" aria-labelledby="headingphMolinosBagazoCachaza" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Análisis:</h6>
                        <a class="collapse-item" href="controllers/controlPH/mostrarControlPH.php">Control de PH</a>
                        <a class="collapse-item" href="controllers/aguaImbibicion/mostrarAguaImbibicion.php">Agua de Imbibición</a>
                        <a class="collapse-item" href="controllers/jugoMezcladoPHMBC/mostrarJugoMezcladoPHMBC.php">Jugo Mezclado</a>
                        <a class="collapse-item" href="controllers/bagazo/mostrarBagazo.php">Bagazo</a>
                        <a class="collapse-item" href="controllers/filtrosCachaza/mostrarFC.php">Filtros Cachaza</a>
                        <a class="collapse-item" href="controllers/cachaza/mostrarCachaza.php">Cachaza</a>
                        <a class="collapse-item" href="controllers/causas/mostrarCausas.php">Causas</a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider my-0">

            <!-- Nav Item - Gestion -->
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseGestion" aria-expanded="true" aria-controls="collapseGestion">
                    <i class="fas fa-fw fa-user"></i>
                    <span>Gestión</span>
                </a>
                <div id="collapseGestion" class="collapse" aria-labelledby="headingGestion" data-parent="#accordionSidebar">
                    <div class="bg-white py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Menú:</h6>
                        <a class="collapse-item" href="controllers/periodoZafra/mostrarPeriodoZafra.php">Periodos de Zafra</a>
                        <a class="collapse-item" href="controllers/usuario/mostrarUsuario.php">Usuarios</a>
                        <a class="collapse-item" href="views/mostrarBitacora.php">Bitácora</a>
                    </div>
                </div>
            </li>

            <!-- Divider -->
            <hr class="sidebar-divider d-none d-md-block">

            <!-- Sidebar Toggler (Sidebar) -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>

        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- topbar.php -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo $_SESSION['nombre']; ?></span>
                                <img class="img-profile rounded-circle"
                                    src="img/undraw_profile.svg">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                             <!-- <a class="dropdown-item" href="#">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Perfil
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Configuración
                                </a>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Actividad
                                </a> 
                                <div class="dropdown-divider"></div> -->
                                <a class="dropdown-item" href="logout.php" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Cerrar Sesión
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800"></h1>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">¿Listo para Salir?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Seleccione "Salir" a continuación si está listo para finalizar su sesión actual.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancelar</button>
                    <a class="btn btn-primary" href="logout.php">Salir</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="public/vendor/jquery/jquery.min.js"></script>
    <script src="public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="public/vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="public/js/sb-admin-2.min.js"></script>

</body>

</html>