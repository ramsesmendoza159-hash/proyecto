<?php
// index.php - Punto de entrada principal
// Ubicación: C:\xampp\htdocs\proyecto\index.php
// ✅ FIX: autoloader completo (todos los controladores conocidos)
// ✅ FIX: route map 100% consistente con .htaccess
// ✅ FIX: eliminadas rutas a métodos inexistentes (checklist/crear, checklist/guardar, etc.)
// ✅ FIX: agregadas rutas faltantes (supervisor/revisar, inventario/list, etc.)
// ✅ FIX: catch (Throwable) en vez de catch (Exception)
// ✅ FIX: validación defensiva de controladores y métodos
// ✅ NUEVO: rutas /checklists/asignar, /checklists/asignar/guardar y /checklists/omitir

// ==========================================
// CONFIGURACIÓN DE ERRORES
// ==========================================
define('APP_ENV', getenv('APP_ENV') ?: 'development');

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

ini_set('log_errors', 1);

$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
}
ini_set('error_log', $logDir . '/error.log');

// ==========================================
// HEADERS DE SEGURIDAD
// ==========================================
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header("Content-Security-Policy: default-src 'self' https:; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https:; script-src 'self' 'unsafe-inline' https:; font-src 'self' data: https:; connect-src 'self' https:; media-src 'self' https:;");
}

// ==========================================
// INICIAR SESIÓN (con cookies seguras)
// ==========================================
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/proyecto/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// ==========================================
// AUTOCARGA DE CLASES
// ==========================================
spl_autoload_register(function ($class) {
    $class = ltrim($class, '\\');

    $classMap = [
        // ========== CONFIG ==========
        'Database'                      => ['config',  'database.php'],

        // ========== HELPERS ==========
        'AuthHelper'                    => ['helpers', 'AuthHelper.php'],
        'BackdatingHelper'              => ['helpers', 'BackdatingHelper.php'],
        'Controller'                    => ['helpers', 'Controller.php'],
        'FileHelper'                    => ['helpers', 'FileHelper.php'],
        'FirmaHelper'                   => ['helpers', 'FirmaHelper.php'],
        'FirmaSecuenciaHelper'          => ['helpers', 'FirmaSecuenciaHelper.php'],
        'HashHelper'                    => ['helpers', 'HashHelper.php'],
        'Router'                        => ['helpers', 'Router.php'],
        'SecurityHelper'                => ['helpers', 'SecurityHelper.php'],
        'SVGSanitizer'                  => ['helpers', 'SVGSanitizer.php'],
        'ToastHelper'                   => ['helpers', 'ToastHelper.php'],
        'ValidationHelper'              => ['helpers', 'ValidationHelper.php'],

        // ========== MODELOS ==========
        'AreasModel'                    => ['model', 'AreasModel.php'],
        'AuditoriaModel'                => ['model', 'AuditoriaModel.php'],
        'BackdatingModel'               => ['model', 'BackdatingModel.php'],
        'ChecklistModel'                => ['model', 'ChecklistModel.php'],
        'ComponentesModel'              => ['model', 'ComponentesModel.php'],
        'EquiposModel'                  => ['model', 'EquiposModel.php'],
        'FirmaModel'                    => ['model', 'FirmaModel.php'],
        'InventarioModel'               => ['model', 'InventarioModel.php'],
        'MantenimientoEquiposModel'     => ['model', 'MantenimientoEquiposModel.php'],
        'MonitoreoModel'                => ['model', 'MonitoreoModel.php'],
        'OrdenTrabajo'                  => ['model', 'OrdenTrabajo.php'],
        'PlantasModel'                  => ['model', 'PlantasModel.php'],
        'ProveedoresModel'              => ['model', 'ProveedoresModel.php'],
        'Supervisor'                    => ['model', 'Supervisor.php'],
        'Tecnico'                       => ['model', 'Tecnico.php'],
        'UsuariosModel'                 => ['model', 'UsuariosModel.php'],

        // ========== CONTROLADORES ==========
        'AdminController'               => ['controller', 'AdminController.php'],
        'AlmacenController'             => ['controller', 'AlmacenController.php'],
        'AuditoriaController'           => ['controller', 'AuditoriaController.php'],
        'AuthController'                => ['controller', 'AuthController.php'],
        'BackdatingController'          => ['controller', 'BackdatingController.php'],
        'CalidadController'             => ['controller', 'CalidadController.php'],
        'ChecklistController'           => ['controller', 'ChecklistController.php'],
        'ChecklistEjecucionController'  => ['controller', 'ChecklistEjecucionController.php'],
        'ConsultorController'           => ['controller', 'ConsultorController.php'],
        'DashboardController'           => ['controller', 'DashboardController.php'],
        'EquiposController'             => ['controller', 'EquiposController.php'],
        'ErrorController'               => ['controller', 'ErrorController.php'],
        'FirmaController'               => ['controller', 'FirmaController.php'],
        'IngenieroController'           => ['controller', 'IngenieroController.php'],
        'InventarioController'          => ['controller', 'InventarioController.php'],
        'MantenimientoEquiposController'=> ['controller', 'MantenimientoEquiposController.php'],
        'MonitoreoController'           => ['controller', 'MonitoreoController.php'],
        'OperadorController'            => ['controller', 'OperadorController.php'],
        'OrdenController'               => ['controller', 'OrdenController.php'],
        'PerfilController'              => ['controller', 'PerfilController.php'],
        'ReporteController'             => ['controller', 'ReporteController.php'],
        'ReporteFinancieroController'   => ['controller', 'ReporteFinancieroController.php'],
        'SeguridadController'           => ['controller', 'SeguridadController.php'],
        'SupervisionController'         => ['controller', 'SupervisionController.php'],
        'SupervisorController'          => ['controller', 'SupervisorController.php'],
        'SupervisoresController'        => ['controller', 'SupervisoresController.php'],
        'TecnicoController'             => ['controller', 'TecnicoController.php'],
        'TecnicosController'            => ['controller', 'TecnicosController.php'],
    ];

    if (isset($classMap[$class])) {
        [$dir, $file] = $classMap[$class];
        $path = __DIR__ . '/' . $dir . '/' . $file;
        if (file_exists($path)) {
            require_once $path;
            return true;
        }
    }

    $paths = [
        __DIR__ . '/controller/',
        __DIR__ . '/model/',
        __DIR__ . '/helpers/',
        __DIR__ . '/config/',
    ];

    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }

    return false;
});

// ==========================================
// VERIFICAR CONFIGURACIÓN DE BASE DE DATOS
// ==========================================
if (!file_exists(__DIR__ . '/config/database.php')) {
    error_log('FATAL: config/database.php no encontrado');
    die(APP_ENV === 'development'
        ? 'Error: config/database.php no encontrado.'
        : 'Error interno del servidor.');
}

require_once __DIR__ . '/config/database.php';

// ==========================================
// PARSEO DE LA URL
// ==========================================
$controller = $_GET['controller'] ?? '';
$action     = $_GET['action'] ?? '';
$id         = $_GET['id'] ?? null;
$paso_id    = $_GET['paso_id'] ?? null;

if ($controller === '' || $controller === 'proyecto') {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $base = '/proyecto/';

    if (strpos($requestUri, $base) === 0) {
        $path = substr($requestUri, strlen($base));
    } else {
        $path = ltrim($requestUri, '/');
    }

    if (($pos = strpos($path, '?')) !== false) {
        $path = substr($path, 0, $pos);
    }

    $path = trim($path, '/');

    if ($path === '' || $path === 'proyecto') {
        $controller = 'dashboard';
        $action = 'index';
    } else {
        // ==========================================
        // ROUTE MAP — CONSISTENTE CON .htaccess
        // Formato: [regex, controller_key, action, [param_names]]
        // ==========================================
        $routeMap = [
            // ========== AUTENTICACIÓN ==========
            ['#^login$#',                       'auth', 'login', []],
            ['#^auth/login$#',                  'auth', 'login', []],
            ['#^auth/authenticate$#',           'auth', 'authenticate', []],
            ['#^logout$#',                      'auth', 'logout', []],
            ['#^auth/logout$#',                 'auth', 'logout', []],

            // ========== DASHBOARD ==========
            ['#^dashboard$#',                   'dashboard', 'index', []],
            ['#^admin/dashboard$#',             'admin', 'dashboard', []],

            // ========== API ==========
            ['#^api/ordenes/dashboard$#',       'orden', 'apiDashboard', []],
            ['#^api/dashboard/stats$#',         'dashboard', 'getStats', []],

            // ========== REPORTES ==========
            ['#^reportes$#',                    'reporte', 'index', []],
            ['#^reportes/ordenes$#',            'reporte', 'ordenes', []],
            ['#^reportes/tecnicos$#',           'reporte', 'tecnicos', []],
            ['#^reportes/tecnicosData$#',       'reporte', 'tecnicosData', []],
            ['#^reportes/supervision$#',        'reporte', 'supervision', []],
            ['#^reportes/exportar$#',           'reporte', 'exportar', []],
            ['#^reportes/imprimir$#',           'reporte', 'imprimir', []],
            ['#^reportes/inventario$#',         'reporte', 'inventario', []],
            ['#^reportes/financieros$#',        'reporteFinanciero', 'index', []],
            ['#^reportes/financiero$#',         'reporteFinanciero', 'index', []],
            ['#^reportes/financieros/exportar$#','reporteFinanciero', 'exportar', []],

            // ========== ÓRDENES ==========
            ['#^ordenes$#',                             'orden', 'index', []],
            ['#^ordenes/crear$#',                       'orden', 'crear', []],
            ['#^ordenes/guardar$#',                     'orden', 'guardar', []],
            ['#^ordenes/ver/([0-9]+)$#',                'orden', 'ver', ['id']],
            ['#^ordenes/editar/([0-9]+)$#',             'orden', 'editar', ['id']],
            ['#^ordenes/actualizar/([0-9]+)$#',         'orden', 'actualizar', ['id']],
            ['#^ordenes/cerrar/([0-9]+)$#',             'orden', 'cerrar', ['id']],
            ['#^ordenes/procesarCierre/([0-9]+)$#',     'orden', 'procesarCierre', ['id']],
            ['#^ordenes/eliminar/([0-9]+)$#',           'orden', 'eliminar', ['id']],
            ['#^ordenes/estadisticas$#',                'orden', 'estadisticas', []],
            ['#^ordenes/detalle/([0-9]+)$#',            'orden', 'detalle', ['id']],
            ['#^ordenes/cambiarEstado$#',               'orden', 'cambiarEstado', []],
            ['#^ordenes/devolver$#',                    'orden', 'devolver', []],
            ['#^ordenes/aprobarDevolucion$#',           'orden', 'aprobarDevolucion', []],
            ['#^ordenes/devoluciones$#',                'orden', 'devoluciones', []],
            ['#^ordenes/verificar-firma/([0-9]+)$#',    'orden', 'verificarFirma', ['id']],

            // ========== TÉCNICOS ==========
            ['#^tecnicos$#',                            'tecnicos', 'index', []],
            ['#^tecnicos/crear$#',                      'tecnicos', 'crear', []],
            ['#^tecnicos/guardar$#',                    'tecnicos', 'guardar', []],
            ['#^tecnicos/editar/([0-9]+)$#',            'tecnicos', 'editar', ['id']],
            ['#^tecnicos/actualizar/([0-9]+)$#',        'tecnicos', 'actualizar', ['id']],
            ['#^tecnicos/eliminar/([0-9]+)$#',          'tecnicos', 'eliminar', ['id']],
            ['#^tecnicos/cambiarEstado/([0-9]+)$#',     'tecnicos', 'cambiarEstado', ['id']],

            // ========== SUPERVISORES ==========
            ['#^supervisores$#',                        'supervisores', 'index', []],
            ['#^supervisores/crear$#',                  'supervisores', 'crear', []],
            ['#^supervisores/guardar$#',                'supervisores', 'guardar', []],
            ['#^supervisores/editar/([0-9]+)$#',        'supervisores', 'editar', ['id']],
            ['#^supervisores/actualizar/([0-9]+)$#',    'supervisores', 'actualizar', ['id']],
            ['#^supervisores/eliminar/([0-9]+)$#',      'supervisores', 'eliminar', ['id']],
            ['#^supervisores/cambiarEstado/([0-9]+)$#', 'supervisores', 'cambiarEstado', ['id']],
            ['#^supervisores/cambiarPassword/([0-9]+)$#','supervisores', 'cambiarPassword', ['id']],

            // ========== SUPERVISOR (PANEL) ==========
            ['#^supervisor$#',                          'supervisor', 'index', []],
            ['#^supervisor/dashboardData$#',            'supervisor', 'dashboardData', []],
            ['#^supervisor/ordenes$#',                  'supervisor', 'ordenes', []],
            ['#^supervisor/ordenesList$#',              'supervisor', 'ordenesList', []],
            ['#^supervisor/revisar/([0-9]+)$#',         'supervisor', 'revisar', ['id']],
            ['#^supervisor/guardar_revision$#',         'supervisor', 'guardar_revision', []],
            ['#^supervisor/supervisiones$#',            'supervisor', 'supervisiones', []],
            ['#^supervisor/supervisionesList$#',        'supervisor', 'supervisionesList', []],
            ['#^supervisor/ver_orden/([0-9]+)$#',       'supervisor', 'ver_orden', ['id']],
            ['#^supervisor/ver_supervision/([0-9]+)$#', 'supervisor', 'ver_supervision', ['id']],
            ['#^supervisor/tecnicosList$#',             'supervisor', 'tecnicosList', []],

            // ========== TÉCNICO (PANEL) ==========
            ['#^tecnico$#',                             'tecnico', 'index', []],
            ['#^tecnico/dashboardData$#',               'tecnico', 'dashboardData', []],
            ['#^tecnico/mis_ordenes$#',                 'tecnico', 'mis_ordenes', []],
            ['#^tecnico/detalle_orden/([0-9]+)$#',      'tecnico', 'detalle_orden', ['id']],
            ['#^tecnico/cerrar_orden/([0-9]+)$#',       'tecnico', 'cerrar_orden', ['id']],
            ['#^tecnico/cerrar/([0-9]+)$#',             'tecnico', 'procesarCierre', ['id']],
            ['#^tecnico/mis_equipos$#',                 'tecnico', 'mis_equipos', []],
            ['#^tecnico/equipo_detalle/([0-9]+)$#',     'tecnico', 'equipo_detalle', ['id']],
            ['#^tecnico/herramientas$#',                'tecnico', 'herramientas', []],

            // ========== SUPERVISIÓN ==========
            ['#^supervision$#',                         'supervision', 'index', []],
            ['#^supervision/ver/([0-9]+)$#',            'supervision', 'ver', ['id']],
            ['#^supervision/editar/([0-9]+)$#',         'supervision', 'editar', ['id']],
            ['#^supervision/actualizar/([0-9]+)$#',     'supervision', 'actualizar', ['id']],
            ['#^supervision/aprobar/([0-9]+)$#',        'supervision', 'aprobar', ['id']],
            ['#^supervision/rechazar/([0-9]+)$#',       'supervision', 'rechazar', ['id']],
            ['#^supervision/eliminar/([0-9]+)$#',       'supervision', 'eliminar', ['id']],
            ['#^supervision/orden/([0-9]+)$#',          'supervision', 'orden', ['id']],
            ['#^supervision/ordenes$#',                 'supervision', 'ordenes', []],
            ['#^supervision/supervisores$#',            'supervision', 'supervisores', []],
            ['#^supervision/reporte$#',                 'supervision', 'reporte', []],

            // ========== INVENTARIO ==========
            ['#^inventario$#',                          'inventario', 'index', []],
            ['#^inventario/index$#',                    'inventario', 'index', []],
            ['#^inventario/list$#',                     'inventario', 'list', []],
            ['#^inventario/crear$#',                    'inventario', 'crear', []],
            ['#^inventario/guardar$#',                  'inventario', 'guardar', []],
            ['#^inventario/editar/([0-9]+)$#',          'inventario', 'editar', ['id']],
            ['#^inventario/actualizar/([0-9]+)$#',      'inventario', 'editar', ['id']],
            ['#^inventario/eliminar/([0-9]+)$#',        'inventario', 'eliminar', ['id']],
            ['#^inventario/detalle/([0-9]+)$#',         'inventario', 'detalle', ['id']],
            ['#^inventario/buscar$#',                   'inventario', 'buscar', []],
            ['#^inventario/api/detalle/([0-9]+)$#',     'inventario', 'apiDetalle', ['id']],

            // ========== ALMACÉN ==========
            ['#^almacen$#',                             'almacen', 'index', []],
            ['#^almacen/index$#',                       'almacen', 'index', []],
            ['#^almacen/dashboardData$#',               'almacen', 'dashboardData', []],
            ['#^almacen/detalle/([0-9]+)$#',            'almacen', 'detalle', ['id']],
            ['#^almacen/entrada$#',                     'almacen', 'entrada', []],
            ['#^almacen/entrada/([0-9]+)$#',            'almacen', 'entrada', ['id']],
            ['#^almacen/entrada_seleccion$#',           'almacen', 'entrada_seleccion', []],
            ['#^almacen/salida$#',                      'almacen', 'salida', []],
            ['#^almacen/salida/([0-9]+)$#',             'almacen', 'salida', ['id']],
            ['#^almacen/salida_seleccion$#',            'almacen', 'salida_seleccion', []],
            ['#^almacen/movimientos$#',                 'almacen', 'movimientos', []],
            ['#^almacen/buscar$#',                      'almacen', 'buscar', []],
            ['#^almacen/estadisticas$#',                'almacen', 'estadisticas', []],
            ['#^almacen/inventario$#',                  'almacen', 'inventario', []],
            ['#^almacen/notificaciones$#',              'almacen', 'notificaciones', []],
            ['#^almacen/permanencia$#',                 'almacen', 'permanencia', []],
            ['#^almacen/top_productos$#',               'inventario', 'topProductos', []],

            // ========== EQUIPOS ==========
            ['#^equipos$#',                             'equipos', 'index', []],
            ['#^equipos/crear$#',                       'equipos', 'crear', []],
            ['#^equipos/guardar$#',                     'equipos', 'guardar', []],
            ['#^equipos/ver/([0-9]+)$#',                'equipos', 'ver', ['id']],
            ['#^equipos/editar/([0-9]+)$#',             'equipos', 'editar', ['id']],
            ['#^equipos/actualizar/([0-9]+)$#',         'equipos', 'actualizar', ['id']],
            ['#^equipos/eliminar/([0-9]+)$#',           'equipos', 'eliminar', ['id']],
            ['#^equipos/buscar$#',                      'equipos', 'buscar', []],
            ['#^equipos/cambiarEstadoOperativo/([0-9]+)$#', 'equipos', 'cambiarEstadoOperativo', ['id']],

            // ========== MANTENIMIENTO ==========
            ['#^mantenimiento/panel$#',                 'mantenimientoEquipos', 'panel', []],
            ['#^mantenimiento/mis-tareas$#',            'mantenimientoEquipos', 'mis_tareas', []],
            ['#^mantenimiento/equipo/([0-9]+)$#',       'mantenimientoEquipos', 'equipo', ['id']],
            ['#^mantenimiento/crear-tarea$#',           'mantenimientoEquipos', 'crearTarea', []],
            ['#^mantenimiento/actualizar-tarea/([0-9]+)$#', 'mantenimientoEquipos', 'actualizarTarea', ['id']],
            ['#^mantenimiento/eliminar-tarea/([0-9]+)$#',   'mantenimientoEquipos', 'eliminarTarea', ['id']],
            ['#^mantenimiento/horometro/([0-9]+)$#',        'mantenimientoEquipos', 'horometro', ['id']],
            ['#^mantenimiento/editar-horometro/([0-9]+)$#', 'mantenimientoEquipos', 'editarHorometro', ['id']],
            ['#^mantenimiento/eliminar-horometro/([0-9]+)$#','mantenimientoEquipos', 'eliminarHorometro', ['id']],
            ['#^mantenimiento/asignar/([0-9]+)$#',          'mantenimientoEquipos', 'asignar', ['id']],
            ['#^mantenimiento/completar/([0-9]+)$#',        'mantenimientoEquipos', 'completar', ['id']],
            ['#^mantenimiento/ejecutar/([0-9]+)$#',         'mantenimientoEquipos', 'ejecutar', ['id']],
            ['#^mantenimiento/completar-ejecucion/([0-9]+)$#','mantenimientoEquipos', 'completarEjecucion', ['id']],
            ['#^mantenimiento/api/alertas$#',               'mantenimientoEquipos', 'apiAlertas', []],
            ['#^mantenimiento/detalle-mantenimiento/([0-9]+)$#','mantenimientoEquipos', 'detalleMantenimiento', ['id']],
            ['#^mantenimiento/editar-mantenimiento/([0-9]+)$#', 'mantenimientoEquipos', 'editarMantenimiento', ['id']],
            ['#^mantenimiento/actualizar-mantenimiento/([0-9]+)$#','mantenimientoEquipos', 'actualizarMantenimiento', ['id']],
            ['#^mantenimiento/eliminar-mantenimiento/([0-9]+)$#','mantenimientoEquipos', 'eliminarMantenimiento', ['id']],
            ['#^mantenimiento/editar-checklist/([0-9]+)$#', 'mantenimientoEquipos', 'editarChecklist', ['id']],
            ['#^mantenimiento/actualizar-checklist/([0-9]+)$#','mantenimientoEquipos', 'actualizarChecklist', ['id']],

            // ========== MONITOREO ==========
            ['#^monitoreo$#',                           'monitoreo', 'index', []],
            ['#^monitoreo/index$#',                     'monitoreo', 'index', []],
            ['#^monitoreo/mis-rutas$#',                 'monitoreo', 'misRutas', []],
            ['#^monitoreo/iniciar/([0-9]+)$#',          'monitoreo', 'iniciar', ['id']],
            ['#^monitoreo/llenar/([0-9]+)$#',           'monitoreo', 'llenar', ['id']],
            ['#^monitoreo/guardar-lectura$#',           'monitoreo', 'guardarLectura', []],
            ['#^monitoreo/cerrar/([0-9]+)$#',           'monitoreo', 'cerrar', ['id']],
            ['#^monitoreo/firmar/([0-9]+)$#',           'monitoreo', 'firmar', ['id']],
            ['#^monitoreo/procesar-firma$#',            'monitoreo', 'procesarFirma', []],
            ['#^monitoreo/rechazar$#',                  'monitoreo', 'rechazar', []],
            ['#^monitoreo/ver/([0-9]+)$#',              'monitoreo', 'ver', ['id']],
            ['#^monitoreo/historial$#',                 'monitoreo', 'historial', []],
            ['#^monitoreo/crear$#',                     'monitoreo', 'crear', []],
            ['#^monitoreo/editar/([0-9]+)$#',           'monitoreo', 'editar', ['id']],
            ['#^monitoreo/guardar-ruta$#',              'monitoreo', 'guardarRuta', []],
            ['#^monitoreo/actualizar-ruta/([0-9]+)$#',  'monitoreo', 'actualizarRuta', ['id']],
            ['#^monitoreo/eliminar-ruta/([0-9]+)$#',    'monitoreo', 'eliminarRuta', ['id']],
            ['#^monitoreo/reporte/([0-9]+)$#',          'monitoreo', 'reporte', ['id']],
            ['#^monitoreo/exportar$#',                  'monitoreo', 'exportar', []],
            ['#^monitoreo/api/alertas$#',               'monitoreo', 'apiAlertas', []],
            ['#^monitoreo/api/lecturas-equipo/([0-9]+)$#','monitoreo', 'apiLecturasEquipo', ['id']],
            ['#^monitoreo/api/registros-dia$#',         'monitoreo', 'apiRegistrosDia', []],

            // ========== CHECKLISTS ==========
            ['#^checklists$#',                          'checklist', 'index', []],
            ['#^checklists/asignar$#',                  'checklistEjecucion', 'asignar', []],
            ['#^checklists/asignar/guardar$#',          'checklistEjecucion', 'guardarAsignacion', []],
            ['#^checklists/mis-checklists$#',           'checklistEjecucion', 'misChecklists', []],
            ['#^checklists/iniciar/([0-9]+)$#',         'checklistEjecucion', 'iniciar', ['id']],
            ['#^checklists/llenar/([0-9]+)$#',          'checklistEjecucion', 'llenar', ['id']],
            ['#^checklists/guardar-lectura$#',          'checklistEjecucion', 'guardarLectura', []],
            ['#^checklists/cerrar/([0-9]+)$#',          'checklistEjecucion', 'cerrar', ['id']],
            ['#^checklists/omitir$#',                   'checklistEjecucion', 'omitir', []],
            ['#^checklists/firmar/([0-9]+)$#',          'checklistEjecucion', 'firmar', ['id']],
            ['#^checklists/procesar-firma$#',           'checklistEjecucion', 'procesarFirma', []],
            ['#^checklists/rechazar$#',                 'checklistEjecucion', 'rechazar', []],
            ['#^checklists/historial$#',                'checklistEjecucion', 'historial', []],
            ['#^checklists/reporte/([0-9]+)$#',         'checklistEjecucion', 'reporte', ['id']],
            ['#^checklists/exportar$#',                 'checklistEjecucion', 'exportar', []],
            ['#^checklists/ver/([0-9]+)$#',             'checklistEjecucion', 'ver', ['id']],

            // ========== FIRMAS ==========
            ['#^firmas/orden/([0-9]+)$#',               'firma', 'orden', ['id']],
            ['#^firmas/firmar/([0-9]+)/([0-9]+)$#',     'firma', 'firmar', ['id', 'paso_id']],
            ['#^firmas/procesar$#',                     'firma', 'procesar', []],
            ['#^firmas/rechazar$#',                     'firma', 'rechazar', []],
            ['#^firmas/pendientes$#',                   'firma', 'pendientes', []],
            ['#^firmas/configuracion$#',                'firma', 'configuracion', []],
            ['#^firmas/guardar-configuracion$#',        'firma', 'guardarConfiguracion', []],
            ['#^firmas/todas$#',                        'firma', 'todas', []],

            // ========== NUEVOS ROLES ==========
            ['#^ingeniero$#',                           'ingeniero', 'index', []],
            ['#^calidad$#',                             'calidad', 'index', []],
            ['#^seguridad$#',                           'seguridad', 'index', []],

            // ========== BACKDATING ==========
            ['#^backdating$#',                          'backdating', 'index', []],
            ['#^backdating/ver/([0-9]+)$#',             'backdating', 'ver', ['id']],
            ['#^backdating/aprobar/([0-9]+)$#',         'backdating', 'aprobar', ['id']],
            ['#^backdating/rechazar/([0-9]+)$#',        'backdating', 'rechazar', ['id']],
            ['#^backdating/validar-fecha$#',            'backdating', 'validarFecha', []],

            // ========== PERFIL ==========
            ['#^perfil$#',                              'perfil', 'index', []],
            ['#^perfil/editar$#',                       'perfil', 'editar', []],
            ['#^perfil/actualizar$#',                   'perfil', 'actualizar', []],
            ['#^perfil/cambiar_password$#',             'perfil', 'cambiarPassword', []],
            ['#^perfil/guardar-firma$#',                'perfil', 'guardarFirma', []],
            ['#^perfil/guardar_password$#',             'perfil', 'cambiarPassword', []],

            // ========== OPERADOR ==========
            ['#^operador$#',                            'operador', 'index', []],
            ['#^operador/ordenes$#',                    'operador', 'ordenes', []],
            ['#^operador/crear_orden$#',                'operador', 'crear_orden', []],

            // ========== CONSULTOR ==========
            ['#^consultor$#',                           'consultor', 'index', []],
            ['#^consultor/ordenes$#',                   'consultor', 'ordenes', []],
            ['#^consultor/ver_orden/([0-9]+)$#',        'consultor', 'ver_orden', ['id']],

            // ========== AUDITORÍA ==========
            ['#^auditoria$#',                           'auditoria', 'index', []],
            ['#^auditoria/ver/([0-9]+)$#',              'auditoria', 'ver', ['id']],
            ['#^auditoria/limpiar$#',                   'auditoria', 'limpiar', []],
        ];

        $matched = false;
        foreach ($routeMap as [$pattern, $ctrl, $act, $paramNames]) {
            if (preg_match($pattern, $path, $matches)) {
                $controller = $ctrl;
                $action = $act;

                $params = [];
                foreach ($paramNames as $index => $name) {
                    $params[$name] = $matches[$index + 1] ?? null;
                }

                $id = $params['id'] ?? null;
                $paso_id = $params['paso_id'] ?? null;

                $matched = true;
                break;
            }
        }

        if (!$matched) {
            $segments = explode('/', $path);
            if (count($segments) >= 2) {
                $controller = $segments[0];
                $action = $segments[1] ?? 'index';
                $id = $segments[2] ?? null;
                if (count($segments) >= 4) {
                    $paso_id = $segments[3] ?? null;
                }
            } else {
                $controller = $segments[0] ?? 'dashboard';
                $action = 'index';
            }
        }
    }
}

$controller = !empty($controller) ? $controller : 'dashboard';
$action = !empty($action) ? $action : 'index';

// ==========================================
// VALIDACIÓN DE SEGURIDAD
// ==========================================
$allowedControllers = [
    'auth', 'dashboard', 'admin', 'orden',
    'tecnicos', 'supervisores', 'supervisor', 'tecnico',
    'supervision', 'inventario', 'almacen', 'equipos',
    'mantenimientoEquipos', 'monitoreo',
    'checklist', 'checklistEjecucion',
    'firma',
    'ingeniero', 'calidad', 'seguridad',
    'backdating', 'perfil', 'operador', 'consultor',
    'auditoria', 'reporte', 'reporteFinanciero', 'error',
];

if (!preg_match('/^[a-zA-Z0-9_]+$/', $controller)) {
    error_log("Intento de acceso con controlador inválido: {$controller}");
    $controller = 'error';
    $action = 'error404';
} elseif (!in_array($controller, $allowedControllers, true)) {
    error_log("Controlador no permitido: {$controller}");
    $controller = 'error';
    $action = 'error404';
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $action)) {
    error_log("Intento de acceso con acción inválida: {$action}");
    $controller = 'error';
    $action = 'error404';
}

// ==========================================
// RESOLUCIÓN DEL CONTROLADOR
// ==========================================
$controllerName = ucfirst($controller) . 'Controller';

$altNames = [
    'reporteFinanciero'      => 'ReporteFinancieroController',
    'reporte'                => 'ReporteController',
    'inventario'             => 'InventarioController',
    'almacen'                => 'AlmacenController',
    'orden'                  => 'OrdenController',
    'perfil'                 => 'PerfilController',
    'auth'                   => 'AuthController',
    'dashboard'              => 'DashboardController',
    'error'                  => 'ErrorController',
    'mantenimientoEquipos'   => 'MantenimientoEquiposController',
    'equipos'                => 'EquiposController',
    'backdating'             => 'BackdatingController',
    'firma'                  => 'FirmaController',
    'ingeniero'              => 'IngenieroController',
    'calidad'                => 'CalidadController',
    'seguridad'              => 'SeguridadController',
    'monitoreo'              => 'MonitoreoController',
    'checklist'              => 'ChecklistController',
    'checklistEjecucion'     => 'ChecklistEjecucionController',
    'auditoria'              => 'AuditoriaController',
    'supervision'            => 'SupervisionController',
    'tecnico'                => 'TecnicoController',
    'supervisor'             => 'SupervisorController',
    'tecnicos'               => 'TecnicosController',
    'supervisores'           => 'SupervisoresController',
    'operador'               => 'OperadorController',
    'consultor'              => 'ConsultorController',
    'admin'                  => 'AdminController',
];

if (isset($altNames[$controller])) {
    $controllerName = $altNames[$controller];
}

$controllerFile = __DIR__ . '/controller/' . $controllerName . '.php';

if (!file_exists($controllerFile)) {
    error_log("Controlador no encontrado: {$controllerName}");
    $controllerName = 'ErrorController';
    $action = 'error404';
    $controllerFile = __DIR__ . '/controller/ErrorController.php';

    if (!file_exists($controllerFile)) {
        die('Error crítico: Controlador de errores no encontrado.');
    }
}

require_once $controllerFile;

if (!class_exists($controllerName)) {
    error_log("Clase no encontrada: {$controllerName}");
    http_response_code(500);
    die('Error crítico: Clase del controlador no encontrada.');
}

// ==========================================
// EJECUCIÓN DEL CONTROLADOR
// ==========================================
try {
    $controllerObj = new $controllerName();
} catch (Throwable $e) {
    error_log('Error al instanciar controlador: ' . $e->getMessage());
    http_response_code(500);
    die(APP_ENV === 'development'
        ? 'Error al instanciar controlador: ' . htmlspecialchars($e->getMessage())
        : 'Error interno del servidor.');
}

if (!method_exists($controllerObj, $action)) {
    error_log("Método no encontrado: {$action} en {$controllerName}");

    if (method_exists($controllerObj, 'error404')) {
        $controllerObj->error404();
        exit;
    }

    if (class_exists('ErrorController')) {
        $errorController = new ErrorController();
        if (method_exists($errorController, 'error404')) {
            $errorController->error404();
            exit;
        }
    }

    http_response_code(404);
    die('Método no encontrado: ' . htmlspecialchars($action));
}

// ==========================================
// INVOCAR MÉTODO CON PARÁMETROS DINÁMICOS
// ==========================================
try {
    $reflection = new ReflectionMethod($controllerObj, $action);
    $numParams = $reflection->getNumberOfParameters();

    $args = [];
    if ($numParams >= 1 && $id !== null && $id !== '') {
        $args[] = $id;
    }
    if ($numParams >= 2 && $paso_id !== null && $paso_id !== '') {
        $args[] = $paso_id;
    }

    $controllerObj->$action(...$args);

} catch (Throwable $e) {
    error_log('Error en ejecución: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());

    http_response_code(500);

    if (class_exists('ErrorController')) {
        $errorController = new ErrorController();
        if (method_exists($errorController, 'error500')) {
            $errorController->error500();
            exit;
        }
    }

    die(APP_ENV === 'development'
        ? 'Error 500: ' . htmlspecialchars($e->getMessage())
        : 'Error 500: Error interno del servidor.');
}