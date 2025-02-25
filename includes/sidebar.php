<?php include ROOT_PATH . 'config/config.php'; ?>

<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
    <!-- Sidebar Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo BASE_PATH; ?>index.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-laugh-wink"></i>
        </div>
        <div class="sidebar-brand-text mx-3">Laboratorio</div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item">
        <a class="nav-link" href="<?php echo BASE_PATH; ?>views/dashboard.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Views -->
    <li class="nav-item">
        <a class="nav-link" href="<?php echo BASE_PATH; ?>views/reporteDiario.php">
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
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/jugoPrimario/mostrarJugoPrimario.php">Primario</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/jugoMezclado/mostrarJugoMezclado.php">Mezclado</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/jugoResidual/mostrarJugoResidual.php">Residual</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/jugoClarificado/mostrarJugoClarificado.php">Clarificado</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/jugoFiltrado/mostrarJugoFiltrado.php">Filtrado</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/meladura/mostrarMeladura.php">Meladura</a>
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
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/masaCocidaA/mostrarMasaCocidaA.php">Masa Cocida A</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/masaCocidaB/mostrarMasaCocidaB.php">Masa Cocida B</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/masaCocidaC/mostrarMasaCocidaC.php">Masa Cocida C</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/mielA/mostrarMielA.php">Miel A</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/mielB/mostrarMielB.php">Miel B</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/mielFinal/mostrarMielFinal.php">Miel Final</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/analisisAzucar/mostrarAnalisisAzucar.php">Análisis de Azúcar</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/magmaB/mostrarMagmaB.php">Magma B</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/magmaC/mostrarMagmac.php">Magma C</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/efluentes/mostrarEfluentes.php">Efluentes</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/sacoAzucar/mostrarSacoAzucar.php">Saco Azúcar</a>
                <!-- <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/lecturas/mostrarLectura.php">Lecturas</a> -->
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
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/controlPH/mostrarControlPH.php">Control de PH</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/aguaImbibicion/mostrarAguaImbibicion.php">Agua de Imbibición</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/jugoMezcladoPHMBC/mostrarJugoMezcladoPHMBC.php">Jugo Mezclado</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/bagazo/mostrarBagazo.php">Bagazo</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/filtrosCachaza/mostrarFC.php">Filtros Cachaza</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/cachaza/mostrarCachaza.php">Cachaza</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/causas/mostrarCausas.php">Causas</a>
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
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/periodoZafra/mostrarPeriodoZafra.php">Periodos de Zafra</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>controllers/usuario/mostrarUsuario.php">Usuarios</a>
                <a class="collapse-item" href="<?php echo BASE_PATH; ?>views/mostrarBitacora.php">Bitácora</a>
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