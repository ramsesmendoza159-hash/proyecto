<?php
// views/layouts/sidebar.php
// VERSIÓN COMPLETA - CON ROLES DE FIRMAS SECUENCIALES, MONITOREO Y CHECKLISTS
// ✅ Añadidos: ingeniero, calidad, seguridad
// ✅ NUEVO: entrada "Monitoreo" para admin, supervisor, operador, tecnico
// ✅ NUEVO: contador de pendientes+vencidos vía MonitoreoModel::contarPendientesDelDia()
// ✅ NUEVO: entrada "Checklists" para admin, supervisor, tecnico, operador
// ✅ FIX: Se eliminó el link "Calendario" del menú del técnico

$rol = $_SESSION['rol'] ?? 'usuario';
$nombre = $_SESSION['nombre'] ?? 'Usuario';
$iniciales = strtoupper(substr($nombre, 0, 1));

// ==========================================
// DETECCIÓN SIMPLIFICADA DE SECCIÓN
// ==========================================
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
$path = str_replace('/proyecto', '', $currentPath);
$path = trim(parse_url($path, PHP_URL_PATH) ?? '', '/');
$segments = explode('/', $path);
$controller = $segments[0] ?? 'dashboard';
$action = $segments[1] ?? 'index';

$sectionMap = [
    'dashboard'             => 'dashboard',
    'ordenes'               => 'ordenes',
    'tecnicos'              => 'tecnicos',
    'supervisores'          => 'supervisores',
    'inventario'            => 'inventario',
    'equipos'               => 'equipos',
    'reportes'              => 'reportes',
    'supervision'           => 'supervision',
    'auditoria'             => 'auditoria',
    'perfil'                => 'perfil',
    'almacen'               => 'almacen',
    'mantenimiento'         => 'mantenimiento',
    'mantenimientoEquipos'  => 'mantenimiento',
    'monitoreo'             => 'monitoreo',
    'checklists'            => 'checklists',
    'checklist'             => 'checklists',
    'checklistEjecucion'    => 'checklists',
    'tecnico'               => 'tecnico',
    'supervisor'            => 'supervisor',
    'operador'              => 'operador',
    'consultor'             => 'consultor',
    'ingeniero'             => 'ingeniero',
    'calidad'               => 'calidad',
    'seguridad'             => 'seguridad',
    'firmas'                => 'firmas',
];

$seccion = $sectionMap[$controller] ?? 'dashboard';

switch ($controller) {
    case 'almacen':
        if (in_array($action, ['entrada', 'entrada_seleccion'])) {
            $seccion = 'entrada_stock';
        } elseif (in_array($action, ['salida', 'salida_seleccion'])) {
            $seccion = 'salida_stock';
        } elseif ($action === 'movimientos') {
            $seccion = 'movimientos';
        } elseif ($action === 'permanencia') {
            $seccion = 'permanencia';
        } else {
            $seccion = 'almacen';
        }
        break;

    case 'reportes':
        $seccion = ($action === 'financieros' || $action === 'financiero') ? 'financieros' : 'reportes';
        break;

    case 'mantenimientoEquipos':
        $seccion = in_array($action, ['mis_tareas', 'ejecutar', 'completarEjecucion']) ? 'mis_tareas' : 'mantenimiento';
        break;

    case 'ordenes':
        $seccion = ($action === 'crear') ? 'crear_orden' : 'ordenes';
        break;

    case 'tecnico':
        if (in_array($action, ['mis_ordenes', 'detalle_orden', 'cerrar_orden'])) {
            $seccion = 'mis_ordenes';
        } elseif (in_array($action, ['mis_equipos', 'equipo_detalle'])) {
            $seccion = 'mis_equipos';
        } elseif ($action === 'herramientas') {
            $seccion = 'herramientas';
        } else {
            $seccion = 'tecnico';
        }
        break;

    case 'supervisor':
        if (in_array($action, ['ordenes', 'revisar', 'ver_orden', 'ordenesList'])) {
            $seccion = 'ordenes';
        } elseif (in_array($action, ['supervisiones', 'ver_supervision', 'supervisionesList'])) {
            $seccion = 'supervision';
        } else {
            $seccion = 'supervisor';
        }
        break;

    case 'operador':
        $seccion = ($action === 'ordenes') ? 'ordenes' : (($action === 'crear_orden') ? 'crear_orden' : 'operador');
        break;

    case 'consultor':
        $seccion = in_array($action, ['ordenes', 'ver_orden']) ? 'ordenes' : 'consultor';
        break;

    case 'monitoreo':
        $seccion = 'monitoreo';
        break;

    case 'checklists':
    case 'checklist':
    case 'checklistEjecucion':
        $seccion = 'checklists';
        break;

    case 'firmas':
        $seccion = 'firmas';
        break;

    case 'ingeniero':
        $seccion = 'ingeniero';
        break;

    case 'calidad':
        $seccion = 'calidad';
        break;

    case 'seguridad':
        $seccion = 'seguridad';
        break;
}

// ==========================================
// CARGAR ALERTAS DE MANTENIMIENTO
// ==========================================
$resumen_alertas = ['total_alertas' => 0];
$tareas_tecnico = ['total' => 0, 'vencidas' => 0];

if (in_array($rol, ['admin', 'supervisor', 'tecnico'])) {
    $mantModelPath = __DIR__ . '/../../model/MantenimientoEquiposModel.php';
    if (file_exists($mantModelPath)) {
        try {
            require_once $mantModelPath;
            if (class_exists('MantenimientoEquiposModel')) {
                $mantModel = new MantenimientoEquiposModel();

                if (in_array($rol, ['admin', 'supervisor'])) {
                    $resumen_alertas = $mantModel->obtenerResumenAlertas();
                }

                if ($rol === 'tecnico') {
                    $tecnico_id = $mantModel->obtenerTecnicoIdByEmail($_SESSION['email'] ?? '');
                    if ($tecnico_id) {
                        $tareas_tecnico = $mantModel->contarTareasAsignadas($tecnico_id);
                    }
                }
            }
        } catch (Throwable $e) {
            error_log("Error cargando alertas sidebar: " . $e->getMessage());
        }
    }
}

// ==========================================
// CARGAR FIRMAS PENDIENTES
// ==========================================
$firmas_pendientes = 0;
if (in_array($rol, ['ingeniero', 'calidad', 'seguridad', 'tecnico', 'supervisor'])) {
    $firmaModelPath = __DIR__ . '/../../model/FirmaModel.php';
    if (file_exists($firmaModelPath)) {
        try {
            require_once $firmaModelPath;
            if (class_exists('FirmaModel')) {
                $firmaModel = new FirmaModel();
                $firmas_pendientes = $firmaModel->contarPendientesPorRol($rol);
            }
        } catch (Throwable $e) {
            error_log("Error cargando firmas pendientes: " . $e->getMessage());
        }
    }
}

// ==========================================
// CARGAR REGISTROS DE MONITOREO DEL DÍA
// ==========================================
$monitoreo_pendientes = 0;
if (in_array($rol, ['admin', 'supervisor', 'operador', 'tecnico'])) {
    $monModelPath = __DIR__ . '/../../model/MonitoreoModel.php';
    if (file_exists($monModelPath)) {
        try {
            require_once $monModelPath;
            if (class_exists('MonitoreoModel')) {
                $monModel = new MonitoreoModel();
                $monitoreo_pendientes = $monModel->contarPendientesDelDia();
            }
        } catch (Throwable $e) {
            error_log("Error cargando monitoreo sidebar: " . $e->getMessage());
        }
    }
}

// ==========================================
// CARGAR CHECKLISTS PENDIENTES DEL DÍA
// ==========================================
$checklists_pendientes = 0;
if (in_array($rol, ['admin', 'supervisor', 'operador', 'tecnico'])) {
    $checkModelPath = __DIR__ . '/../../model/ChecklistModel.php';
    if (file_exists($checkModelPath)) {
        try {
            require_once $checkModelPath;
            if (class_exists('ChecklistModel')) {
                $checkModel = new ChecklistModel();
                $tipos_rol = $checkModel->obtenerTiposPorRol($rol);
                $fecha_hoy = date('Y-m-d');
                foreach ($tipos_rol as $tipo_item) {
                    $turno_actual = !empty($tipo_item['requiere_turno'])
                        ? $checkModel->detectarTurnoActual()
                        : null;
                    $reg = $checkModel->obtenerRegistroPorTipoFechaTurno(
                        (int)$tipo_item['id'],
                        $fecha_hoy,
                        $turno_actual
                    );
                    if (!$reg || $reg['estado'] === 'BORRADOR') {
                        $checklists_pendientes++;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log("Error cargando checklists sidebar: " . $e->getMessage());
        }
    }
}
?>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<nav class="sidebar" id="sidebar">
    <div class="brand">
        <div class="brand-icon"><i class="fas fa-tools"></i></div>
        <h4>PROYECTO</h4>
        <small>Sistema de Mantenimiento</small>
    </div>

    <ul class="nav flex-column">

        <?php if ($rol === 'admin'): ?>

            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'dashboard' ? 'active' : ''; ?>" href="/proyecto/dashboard">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
            </li>

            <div class="nav-section-title">GESTIÓN</div>

            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'ordenes' ? 'active' : ''; ?>" href="/proyecto/ordenes">
                    <i class="fas fa-clipboard-list"></i> Órdenes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'tecnicos' ? 'active' : ''; ?>" href="/proyecto/tecnicos">
                    <i class="fas fa-users-cog"></i> Técnicos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'supervisores' ? 'active' : ''; ?>" href="/proyecto/supervisores">
                    <i class="fas fa-user-tie"></i> Supervisores
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'inventario' ? 'active' : ''; ?>" href="/proyecto/inventario">
                    <i class="fas fa-boxes"></i> Inventario
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'equipos' ? 'active' : ''; ?>" href="/proyecto/equipos">
                    <i class="fas fa-industry"></i> Equipos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'mantenimiento' ? 'active' : ''; ?>" href="/proyecto/mantenimiento/panel">
                    <i class="fas fa-tools"></i> Mantenimiento
                    <?php if (!empty($resumen_alertas['total_alertas']) && $resumen_alertas['total_alertas'] > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $resumen_alertas['total_alertas'] ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'monitoreo' ? 'active' : ''; ?>" href="/proyecto/monitoreo">
                    <i class="fas fa-clipboard-check"></i> Monitoreo
                    <?php if ($monitoreo_pendientes > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $monitoreo_pendientes ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'checklists' ? 'active' : ''; ?>" href="/proyecto/checklists">
                    <i class="fas fa-list-check"></i> Checklists
                    <?php if ($checklists_pendientes > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $checklists_pendientes ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <div class="nav-section-title">FIRMAS</div>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'firmas' ? 'active' : ''; ?>" href="/proyecto/firmas/configuracion">
                    <i class="fas fa-cog"></i> Configurar Secuencia
                </a>
            </li>

            <div class="nav-section-title">REPORTES</div>

            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'reportes' ? 'active' : ''; ?>" href="/proyecto/reportes">
                    <i class="fas fa-file-alt"></i> Reportes Generales
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'financieros' ? 'active' : ''; ?>" href="/proyecto/reportes/financieros">
                    <i class="fas fa-money-bill-wave"></i> Reportes Financieros
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'supervision' ? 'active' : ''; ?>" href="/proyecto/supervision">
                    <i class="fas fa-clipboard-check"></i> Supervisión
                </a>
            </li>

            <div class="nav-section-title">SEGURIDAD</div>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'auditoria' ? 'active' : ''; ?>" href="/proyecto/auditoria">
                    <i class="fas fa-clipboard-list"></i> Auditoría
                </a>
            </li>

            <div class="nav-section-title">CUENTA</div>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'perfil' ? 'active' : ''; ?>" href="/proyecto/perfil">
                    <i class="fas fa-user-circle"></i> Mi Perfil
                </a>
            </li>

        <?php elseif ($rol === 'supervisor'): ?>

            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'supervisor' ? 'active' : ''; ?>" href="/proyecto/supervisor">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'ordenes' ? 'active' : ''; ?>" href="/proyecto/supervisor/ordenes">
                    <i class="fas fa-list"></i> Órdenes para Revisar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'supervision' ? 'active' : ''; ?>" href="/proyecto/supervision">
                    <i class="fas fa-clipboard-check"></i> Supervisiones
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'equipos' ? 'active' : ''; ?>" href="/proyecto/equipos">
                    <i class="fas fa-industry"></i> Equipos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'mantenimiento' ? 'active' : ''; ?>" href="/proyecto/mantenimiento/panel">
                    <i class="fas fa-tools"></i> Mantenimiento
                    <?php if (!empty($resumen_alertas['total_alertas']) && $resumen_alertas['total_alertas'] > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $resumen_alertas['total_alertas'] ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'monitoreo' ? 'active' : ''; ?>" href="/proyecto/monitoreo">
                    <i class="fas fa-clipboard-check"></i> Monitoreo
                    <?php if ($monitoreo_pendientes > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $monitoreo_pendientes ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'checklists' ? 'active' : ''; ?>" href="/proyecto/checklists">
                    <i class="fas fa-list-check"></i> Checklists
                    <?php if ($checklists_pendientes > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $checklists_pendientes ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'firmas' ? 'active' : ''; ?>" href="/proyecto/firmas/pendientes">
                    <i class="fas fa-signature"></i> Firmas Pendientes
                    <?php if ($firmas_pendientes > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $firmas_pendientes ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <div class="nav-section-title">REPORTES</div>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'financieros' ? 'active' : ''; ?>" href="/proyecto/reportes/financieros">
                    <i class="fas fa-money-bill-wave"></i> Financieros
                </a>
            </li>

            <div class="nav-section-title">CUENTA</div>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'perfil' ? 'active' : ''; ?>" href="/proyecto/perfil">
                    <i class="fas fa-user-circle"></i> Mi Perfil
                </a>
            </li>

        <?php elseif ($rol === 'tecnico'): ?>

            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'tecnico' ? 'active' : ''; ?>" href="/proyecto/tecnico">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'mis_ordenes' ? 'active' : ''; ?>" href="/proyecto/tecnico/mis_ordenes">
                    <i class="fas fa-clipboard-list"></i> Mis Órdenes
                </a>
            </li>

            <div class="nav-section-title">MANTENIMIENTO</div>

            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'mis_tareas' ? 'active' : ''; ?>" href="/proyecto/mantenimiento/mis-tareas">
                    <i class="fas fa-tasks"></i> Mis Mantenimientos
                    <?php if (!empty($tareas_tecnico['total']) && $tareas_tecnico['total'] > 0): ?>
                        <span class="badge bg-<?= ($tareas_tecnico['vencidas'] ?? 0) > 0 ? 'danger' : 'warning' ?> ms-1">
                            <?= $tareas_tecnico['total'] ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'monitoreo' ? 'active' : ''; ?>" href="/proyecto/monitoreo/mis-rutas">
                    <i class="fas fa-clipboard-check"></i> Monitoreo
                    <?php if ($monitoreo_pendientes > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $monitoreo_pendientes ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'checklists' ? 'active' : ''; ?>" href="/proyecto/checklists/mis-checklists">
                    <i class="fas fa-list-check"></i> Mis Checklists
                    <?php if ($checklists_pendientes > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $checklists_pendientes ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'firmas' ? 'active' : ''; ?>" href="/proyecto/firmas/pendientes">
                    <i class="fas fa-signature"></i> Firmas Pendientes
                    <?php if ($firmas_pendientes > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $firmas_pendientes ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <div class="nav-section-title">RECURSOS</div>

            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'mis_equipos' ? 'active' : ''; ?>" href="/proyecto/tecnico/mis_equipos">
                    <i class="fas fa-microchip"></i> Mis Equipos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'herramientas' ? 'active' : ''; ?>" href="/proyecto/tecnico/herramientas">
                    <i class="fas fa-tools"></i> Herramientas
                </a>
            </li>

            <div class="nav-section-title">CUENTA</div>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'perfil' ? 'active' : ''; ?>" href="/proyecto/perfil">
                    <i class="fas fa-user-circle"></i> Mi Perfil
                </a>
            </li>

        <?php elseif ($rol === 'almacen'): ?>

            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'almacen' ? 'active' : ''; ?>" href="/proyecto/almacen">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
            </li>

            <div class="nav-section-title">MOVIMIENTOS DE STOCK</div>

            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'entrada_stock' ? 'active' : ''; ?>" href="/proyecto/almacen/entrada_seleccion">
                    <i class="fas fa-arrow-down text-success"></i> Registrar Entrada
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'salida_stock' ? 'active' : ''; ?>" href="/proyecto/almacen/salida_seleccion">
                    <i class="fas fa-arrow-up text-danger"></i> Registrar Salida
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'movimientos' ? 'active' : ''; ?>" href="/proyecto/almacen/movimientos">
                    <i class="fas fa-history text-info"></i> Historial de Movimientos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'permanencia' ? 'active' : ''; ?>" href="/proyecto/almacen/permanencia">
                    <i class="fas fa-clock text-warning"></i> Control de Permanencia
                </a>
            </li>

            <div class="nav-section-title">GESTIÓN</div>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'inventario' ? 'active' : ''; ?>" href="/proyecto/inventario">
                    <i class="fas fa-boxes"></i> Inventario
                </a>
            </li>

            <div class="nav-section-title">CUENTA</div>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'perfil' ? 'active' : ''; ?>" href="/proyecto/perfil">
                    <i class="fas fa-user-circle"></i> Mi Perfil
                </a>
            </li>

        <?php elseif ($rol === 'operador'): ?>

            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'operador' ? 'active' : ''; ?>" href="/proyecto/operador">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'ordenes' ? 'active' : ''; ?>" href="/proyecto/operador/ordenes">
                    <i class="fas fa-clipboard-list"></i> Mis Órdenes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'crear_orden' ? 'active' : ''; ?>" href="/proyecto/ordenes/crear">
                    <i class="fas fa-plus-circle"></i> Nueva Orden
                </a>
            </li>

            <div class="nav-section-title">MONITOREO</div>

            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'monitoreo' ? 'active' : ''; ?>" href="/proyecto/monitoreo/mis-rutas">
                    <i class="fas fa-clipboard-check"></i> Mis Rutas
                    <?php if ($monitoreo_pendientes > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $monitoreo_pendientes ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'checklists' ? 'active' : ''; ?>" href="/proyecto/checklists/mis-checklists">
                    <i class="fas fa-list-check"></i> Mis Checklists
                    <?php if ($checklists_pendientes > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $checklists_pendientes ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <div class="nav-section-title">CUENTA</div>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'perfil' ? 'active' : ''; ?>" href="/proyecto/perfil">
                    <i class="fas fa-user-circle"></i> Mi Perfil
                </a>
            </li>

        <?php elseif ($rol === 'consultor'): ?>

            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'consultor' ? 'active' : ''; ?>" href="/proyecto/consultor">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'ordenes' ? 'active' : ''; ?>" href="/proyecto/consultor/ordenes">
                    <i class="fas fa-clipboard-list"></i> Ver Órdenes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'equipos' ? 'active' : ''; ?>" href="/proyecto/equipos">
                    <i class="fas fa-industry"></i> Equipos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'mantenimiento' ? 'active' : ''; ?>" href="/proyecto/mantenimiento/panel">
                    <i class="fas fa-tools"></i> Mantenimiento
                </a>
            </li>

            <div class="nav-section-title">CUENTA</div>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'perfil' ? 'active' : ''; ?>" href="/proyecto/perfil">
                    <i class="fas fa-user-circle"></i> Mi Perfil
                </a>
            </li>

        <?php elseif ($rol === 'ingeniero'): ?>

            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'ingeniero' ? 'active' : ''; ?>" href="/proyecto/ingeniero">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
            </li>

            <div class="nav-section-title">FIRMAS</div>

            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'firmas' ? 'active' : ''; ?>" href="/proyecto/firmas/pendientes">
                    <i class="fas fa-signature"></i> Firmas Pendientes
                    <?php if ($firmas_pendientes > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $firmas_pendientes ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <div class="nav-section-title">GESTIÓN</div>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'ordenes' ? 'active' : ''; ?>" href="/proyecto/ordenes">
                    <i class="fas fa-clipboard-list"></i> Ver Órdenes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'equipos' ? 'active' : ''; ?>" href="/proyecto/equipos">
                    <i class="fas fa-industry"></i> Equipos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'mantenimiento' ? 'active' : ''; ?>" href="/proyecto/mantenimiento/panel">
                    <i class="fas fa-tools"></i> Mantenimiento
                </a>
            </li>

            <div class="nav-section-title">CUENTA</div>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'perfil' ? 'active' : ''; ?>" href="/proyecto/perfil">
                    <i class="fas fa-user-circle"></i> Mi Perfil
                </a>
            </li>

        <?php elseif ($rol === 'calidad'): ?>

            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'calidad' ? 'active' : ''; ?>" href="/proyecto/calidad">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
            </li>

            <div class="nav-section-title">FIRMAS</div>

            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'firmas' ? 'active' : ''; ?>" href="/proyecto/firmas/pendientes">
                    <i class="fas fa-signature"></i> Firmas Pendientes
                    <?php if ($firmas_pendientes > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $firmas_pendientes ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <div class="nav-section-title">GESTIÓN</div>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'ordenes' ? 'active' : ''; ?>" href="/proyecto/ordenes">
                    <i class="fas fa-clipboard-list"></i> Ver Órdenes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'equipos' ? 'active' : ''; ?>" href="/proyecto/equipos">
                    <i class="fas fa-industry"></i> Equipos
                </a>
            </li>

            <div class="nav-section-title">CUENTA</div>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'perfil' ? 'active' : ''; ?>" href="/proyecto/perfil">
                    <i class="fas fa-user-circle"></i> Mi Perfil
                </a>
            </li>

        <?php elseif ($rol === 'seguridad'): ?>

            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'seguridad' ? 'active' : ''; ?>" href="/proyecto/seguridad">
                    <i class="fas fa-chart-pie"></i> Dashboard
                </a>
            </li>

            <div class="nav-section-title">FIRMAS</div>

            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'firmas' ? 'active' : ''; ?>" href="/proyecto/firmas/pendientes">
                    <i class="fas fa-signature"></i> Firmas Pendientes
                    <?php if ($firmas_pendientes > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $firmas_pendientes ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <div class="nav-section-title">GESTIÓN</div>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'ordenes' ? 'active' : ''; ?>" href="/proyecto/ordenes">
                    <i class="fas fa-clipboard-list"></i> Ver Órdenes
                </a>
            </li>

            <div class="nav-section-title">CUENTA</div>
            <li class="nav-item">
                <a class="nav-link <?= $seccion === 'perfil' ? 'active' : ''; ?>" href="/proyecto/perfil">
                    <i class="fas fa-user-circle"></i> Mi Perfil
                </a>
            </li>

        <?php endif; ?>

    </ul>

    <div class="user-info">
        <div class="user-details">
            <span class="user-avatar"><?= $iniciales ?></span>
            <div>
                <span class="user-name"><?= htmlspecialchars($nombre) ?></span>
                <div class="user-role"><?= ucfirst($rol) ?></div>
            </div>
        </div>
        <a href="/proyecto/auth/logout" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
        </a>
    </div>
</nav>