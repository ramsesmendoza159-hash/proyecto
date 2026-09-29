<?php
// controller/OrdenController.php
// VERSIÓN COMPLETA CORREGIDA
// ✅ FIX 1: guardar() usa transacción (crear + inicializarFirmas + extras)
// ✅ FIX 2: obtenerTecnicoIdPorEmail() usa Tecnico::obtenerPorEmail()
// ✅ FIX 3: index() NO fuerza status='CERRADA' para supervisor
// ✅ FIX 4: ver() permite operador ver cualquier orden
// ✅ FIX 5: guardar() calcula costo_mano_obra = horas_duracion * tarifa_tecnico
// ✅ FIX 6: guardar() valida firmas_requeridas contra whitelist
// ✅ FIX 7: actualizar() valida status='PENDIENTE' en POST
// ✅ FIX 8: ver() para supervisor acepta CERRADA + APROBADA + EJECUTADA
// ✅ FIX 9: catch (Throwable) en vez de Exception
// ✅ FIX 10: cambiarEstado() valida transiciones
// ✅ FIX 11: procesarCierre() revalida paso de firma del técnico

require_once __DIR__ . '/../model/OrdenTrabajo.php';
require_once __DIR__ . '/../model/Tecnico.php';
require_once __DIR__ . '/../model/Supervisor.php';
require_once __DIR__ . '/../model/AuditoriaModel.php';
require_once __DIR__ . '/../model/FirmaModel.php';
require_once __DIR__ . '/../model/PlantasModel.php';
require_once __DIR__ . '/../model/AreasModel.php';
require_once __DIR__ . '/../model/EquiposModel.php';
require_once __DIR__ . '/../model/ComponentesModel.php';
require_once __DIR__ . '/../model/InventarioModel.php';
require_once __DIR__ . '/../model/ProveedoresModel.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';
require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../helpers/FirmaHelper.php';
require_once __DIR__ . '/../config/database.php';

class OrdenController extends Controller {

    private $ordenModel;
    private $db;

    public function __construct() {
        parent::__construct();
        $this->ordenModel = new OrdenTrabajo();
        $this->db = Database::getInstance()->getConnection();
    }

    // ==========================================
    // LISTADO
    // ==========================================

    public function index() {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }

        $rol = $this->authHelper->getRole();

        if ($rol === 'tecnico') {
            header('Location: /proyecto/tecnico/mis_ordenes');
            exit;
        }

        // ✅ FIX 3: NO forzar status='CERRADA' para supervisor
        // El modelo ya filtra por CERRADA + APROBADA + EJECUTADA
        $ordenes = $this->ordenModel->obtenerTodos();
        $estadisticas = $this->ordenModel->obtenerEstadisticas();

        $titulo = 'Gestión de Órdenes';
        $seccion = 'ordenes';

        require_once __DIR__ . '/../views/ordenes/index.php';
    }

    // ==========================================
    // VER
    // ==========================================

    public function ver($id) {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }

        $orden = $this->ordenModel->obtenerPorId($id);

        if (!$orden) {
            $_SESSION['error'] = 'Orden no encontrada';
            header('Location: /proyecto/ordenes');
            exit;
        }

        $rol = $this->authHelper->getRole();

        // ✅ FIX 2: técnico solo ve sus órdenes
        if ($rol === 'tecnico') {
            $tecnicoId = $this->obtenerTecnicoIdPorEmail();
            if ($tecnicoId && (int)$orden['tecnico_id'] !== (int)$tecnicoId) {
                $_SESSION['error'] = 'No tienes permisos para ver esta orden';
                header('Location: /proyecto/tecnico/mis_ordenes');
                exit;
            }
        }

        // ✅ FIX 4: operador VE TODAS las órdenes (regla del prompt)
        // (Ya no se valida $orden['creado_por'])

        // ✅ FIX 8: supervisor acepta CERRADA + APROBADA + EJECUTADA
        if ($rol === 'supervisor') {
            $estados_visibles = ['CERRADA', 'APROBADA', 'EJECUTADA'];
            if (!in_array($orden['status'], $estados_visibles, true)) {
                $_SESSION['error'] = 'Solo puedes ver órdenes cerradas, aprobadas o ejecutadas';
                header('Location: /proyecto/supervisor/ordenes');
                exit;
            }
        }

        $tecnicos_adicionales = $this->ordenModel->obtenerTecnicosDeOrden($id);
        $repuestos = $this->ordenModel->obtenerRepuestosDeOrden($id);

        $titulo = 'Detalle de Orden';
        $seccion = 'ordenes';
        require_once __DIR__ . '/../views/ordenes/ver.php';
    }

    /**
     * ✅ FIX 2: usa Tecnico::obtenerPorEmail() en vez de query directa.
     */
    private function obtenerTecnicoIdPorEmail() {
        try {
            $email = $_SESSION['email'] ?? '';
            if (empty($email)) return null;

            require_once __DIR__ . '/../model/Tecnico.php';
            $tecnicoModel = new Tecnico();
            $tecnico = $tecnicoModel->obtenerPorEmail($email);

            return $tecnico ? (int)$tecnico['id'] : null;

        } catch (Throwable $e) {
            error_log("Error en OrdenController::obtenerTecnicoIdPorEmail: " . $e->getMessage());
            return null;
        }
    }

    public function detalle($id) {
        $this->ver($id);
    }

    // ==========================================
    // CREAR
    // ==========================================

    public function crear() {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }

        if (!$this->authHelper->isAdmin() && !$this->authHelper->isOperador()) {
            $_SESSION['error'] = 'No tienes permisos para crear órdenes';
            header('Location: /proyecto/dashboard');
            exit;
        }

        try {
            // ✅ FIX 8: usar modelos en vez de queries directas
            $plantasModel = new PlantasModel();
            $areasModel = new AreasModel();
            $equiposModel = new EquiposModel();
            $componentesModel = new ComponentesModel();
            $tecnicoModel = new Tecnico();
            $supervisorModel = new Supervisor();
            $proveedorModel = new ProveedoresModel();
            $inventarioModel = new InventarioModel();

            $plantas = $plantasModel->obtenerActivos();
            $areas = $areasModel->obtenerActivos();
            $equipos = $equiposModel->obtenerActivos();
            $componentes = $componentesModel->obtenerTodos(['estado' => 'activo']);
            $tecnicos = $tecnicoModel->obtenerTodos(['estado' => 'activo']);
            $supervisores = $supervisorModel->obtenerTodos(['estado' => 'activo']);
            $proveedores = $proveedorModel->obtenerTodos(['estado' => 'activo']);
            $repuestos = $inventarioModel->obtenerTodos(['estado' => 'activo']);

        } catch (Throwable $e) {
            error_log("Error cargando datos para crear orden: " . $e->getMessage());
            $plantas = $areas = $equipos = $componentes = [];
            $tecnicos = $supervisores = $proveedores = $repuestos = [];
        }

        $titulo = 'Crear Orden de Trabajo';
        $seccion = 'crear_orden';

        require_once __DIR__ . '/../views/ordenes/crear.php';
    }

    // ==========================================
    // GUARDAR
    // ==========================================

    /**
     * ✅ FIX 1: usa transacción.
     * ✅ FIX 5: calcula costo_mano_obra.
     * ✅ FIX 6: valida firmas_requeridas.
     */
    public function guardar() {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }

        $rol = $this->authHelper->getRole();
        if (!in_array($rol, ['admin', 'operador'], true)) {
            $_SESSION['error'] = 'No tienes permisos para crear órdenes';
            header('Location: /proyecto/dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/ordenes');
            exit;
        }

        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /proyecto/ordenes/crear');
            exit;
        }

        // Validaciones básicas
        $errores = [];
        if (empty($_POST['titulo'])) {
            $errores[] = 'El título es obligatorio';
        }
        if (empty($_POST['descripcion_mantenimiento'])) {
            $errores[] = 'La descripción del mantenimiento es obligatoria';
        }
        if (empty($_POST['id_area'])) {
            $errores[] = 'El área es obligatoria';
        }
        if (empty($_POST['id_equipo'])) {
            $errores[] = 'El equipo es obligatorio';
        }

        if (!empty($errores)) {
            $_SESSION['errores'] = $errores;
            header('Location: /proyecto/ordenes/crear');
            exit;
        }

        // ✅ FIX 5: calcular tarifa y costo mano de obra en base al técnico
        $tecnico_id = !empty($_POST['tecnico_id']) ? (int)$_POST['tecnico_id'] : null;
        $tarifa_tecnico = 0;

        if ($tecnico_id) {
            $tecnicoModel = new Tecnico();
            $tarifa_tecnico = $tecnicoModel->obtenerTarifa($tecnico_id);
        }

        $horas_duracion = (float)($_POST['horas_duracion'] ?? 0);
        $costo_mano_obra = $horas_duracion * $tarifa_tecnico;
        $costo_repuestos = (float)($_POST['costo_repuestos'] ?? 0);
        $costo_total = $costo_repuestos + $costo_mano_obra;

        $supervisor_id = !empty($_POST['id_supervisor']) ? (int)$_POST['id_supervisor'] : null;

        $datos = [
            'num_om' => $_POST['num_om'] ?? $this->ordenModel->generarNumeroOM(),
            'titulo' => $_POST['titulo'] ?? '',
            'id_planta' => !empty($_POST['id_planta']) ? (int)$_POST['id_planta'] : null,
            'id_area' => !empty($_POST['id_area']) ? (int)$_POST['id_area'] : null,
            'id_equipo' => !empty($_POST['id_equipo']) ? (int)$_POST['id_equipo'] : null,
            'id_componente' => !empty($_POST['id_componente']) ? (int)$_POST['id_componente'] : null,
            'tecnico_id' => $tecnico_id,
            'n_tecnicos' => (int)($_POST['n_tecnicos'] ?? 1),
            'id_supervisor' => $supervisor_id,
            'id_proveedor' => !empty($_POST['id_proveedor']) ? (int)$_POST['id_proveedor'] : null,
            'proveedor_servicio' => $_POST['proveedor_servicio'] ?? '',
            'prioridad' => $_POST['prioridad'] ?? 'Media',
            'tipo_mantenimiento' => $_POST['tipo_mantenimiento'] ?? 'CORRECTIVO',
            'tipo_actividad' => $_POST['tipo_actividad'] ?? '',
            'solicitante' => $_POST['solicitante'] ?? '',
            'supervisor_solicitante' => $_POST['supervisor_solicitante'] ?? '',
            'fecha_inicio' => $_POST['fecha_inicio'] ?? date('Y-m-d'),
            'fecha_estimada' => !empty($_POST['fecha_estimada']) ? $_POST['fecha_estimada'] : null,
            'horas_duracion' => $horas_duracion,
            'descripcion_mantenimiento' => $_POST['descripcion_mantenimiento'] ?? '',
            'pasos' => $_POST['pasos'] ?? '',
            'tarifa_tecnico' => $tarifa_tecnico,
            'costo_repuestos' => $costo_repuestos,
            'costo_mano_obra' => $costo_mano_obra,
            'costo_total' => $costo_total,
            'status' => 'PENDIENTE',
            'creado_por' => $this->authHelper->getUserId()
        ];

        // ✅ FIX 1: transacción completa
        try {
            $this->db->beginTransaction();

            // 1. Crear orden
            $orden_id = $this->ordenModel->crear($datos);

            if (!$orden_id) {
                throw new Exception('Error al crear la orden');
            }

            // ✅ FIX 6: validar firmas_requeridas contra whitelist
            $firmas_requeridas = $_POST['firmas_requeridas'] ?? [];
            if (!is_array($firmas_requeridas)) {
                $firmas_requeridas = [];
            }

            $pasos_opcionales_validos = ['paso_2', 'paso_5', 'paso_6'];
            $firmas_requeridas = array_intersect($firmas_requeridas, $pasos_opcionales_validos);

            // Determinar pasos a omitir
            $pasos_opcionales_nums = [2, 5, 6];
            $pasos_a_omitir = [];
            foreach ($pasos_opcionales_nums as $paso_num) {
                $key = 'paso_' . $paso_num;
                if (!in_array($key, $firmas_requeridas, true)) {
                    $pasos_a_omitir[] = $paso_num;
                }
            }

            // 2. Inicializar firmas
            $firmaModel = new FirmaModel();
            $firmaModel->inicializarFirmas($orden_id, $pasos_a_omitir);

            // 3. Agregar técnicos adicionales
            if (isset($_POST['n_tecnicos']) && (int)$_POST['n_tecnicos'] > 1) {
                $tecnicos_adicionales = [];
                for ($i = 2; $i <= (int)$_POST['n_tecnicos']; $i++) {
                    if (!empty($_POST["tecnico_{$i}_id"])) {
                        $tecnicos_adicionales[] = [
                            'id' => (int)$_POST["tecnico_{$i}_id"],
                            'tarifa' => (float)($_POST["tarifa_tecnico_{$i}"] ?? 0)
                        ];
                    }
                }
                if (!empty($tecnicos_adicionales)) {
                    $this->ordenModel->agregarTecnicosAdicionales($orden_id, $tecnicos_adicionales);
                }
            }

            // 4. Agregar repuestos
            if (isset($_POST['repuestos_json']) && !empty($_POST['repuestos_json'])) {
                $repuestos = json_decode($_POST['repuestos_json'], true);
                if (is_array($repuestos) && !empty($repuestos)) {
                    $this->ordenModel->agregarRepuestos($orden_id, $repuestos);
                }
            }

            // 5. Auditoría
            try {
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $this->authHelper->getUserId(),
                    $_SESSION['nombre'] ?? 'Sistema',
                    $_SESSION['rol'] ?? 'operador',
                    'crear',
                    'ordenes_mantenimiento',
                    $orden_id,
                    null,
                    ['titulo' => $datos['titulo'], 'num_om' => $datos['num_om']]
                );
            } catch (Throwable $e) {
                error_log("Error al registrar auditoría de creación: " . $e->getMessage());
            }

            $this->db->commit();

            $_SESSION['mensaje'] = 'Orden creada. Se inicializó la secuencia de firmas.';
            $_SESSION['mensaje_tipo'] = 'success';
            header('Location: /proyecto/firmas/orden/' . $orden_id);
            exit;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en OrdenController::guardar: " . $e->getMessage());
            $_SESSION['error'] = 'Error al crear la orden: ' . $e->getMessage();
            header('Location: /proyecto/ordenes/crear');
            exit;
        }
    }

    // ==========================================
    // EDITAR
    // ==========================================

    public function editar($id) {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }

        $rol = $this->authHelper->getRole();
        $usuarioId = $this->authHelper->getUserId();

        if (!in_array($rol, ['admin', 'operador'], true)) {
            $_SESSION['error'] = 'No tienes permisos para editar órdenes';
            header('Location: /proyecto/dashboard');
            exit;
        }

        $orden = $this->ordenModel->obtenerPorId($id);
        if (!$orden) {
            $_SESSION['error'] = 'Orden no encontrada';
            header('Location: /proyecto/ordenes');
            exit;
        }

        if ($rol === 'operador' && (int)$orden['creado_por'] !== $usuarioId) {
            $_SESSION['error'] = 'No tienes permisos para editar esta orden';
            header('Location: /proyecto/operador/ordenes');
            exit;
        }

        if ($orden['status'] !== 'PENDIENTE') {
            $_SESSION['error'] = 'Solo se pueden editar órdenes en estado PENDIENTE';
            header('Location: /proyecto/ordenes/ver/' . $id);
            exit;
        }

        try {
            $plantasModel = new PlantasModel();
            $areasModel = new AreasModel();
            $equiposModel = new EquiposModel();
            $componentesModel = new ComponentesModel();
            $tecnicoModel = new Tecnico();
            $supervisorModel = new Supervisor();
            $proveedorModel = new ProveedoresModel();
            $inventarioModel = new InventarioModel();

            $plantas = $plantasModel->obtenerActivos();
            $areas = $areasModel->obtenerActivos();
            $equipos = $equiposModel->obtenerActivos();
            $componentes = $componentesModel->obtenerTodos(['estado' => 'activo']);
            $tecnicos = $tecnicoModel->obtenerTodos(['estado' => 'activo']);
            $supervisores = $supervisorModel->obtenerTodos(['estado' => 'activo']);
            $proveedores = $proveedorModel->obtenerTodos(['estado' => 'activo']);
            $repuestos = $inventarioModel->obtenerTodos(['estado' => 'activo']);

        } catch (Throwable $e) {
            error_log("Error cargando datos para editar orden: " . $e->getMessage());
            $plantas = $areas = $equipos = $componentes = [];
            $tecnicos = $supervisores = $proveedores = $repuestos = [];
        }

        $titulo = 'Editar Orden';
        $seccion = 'ordenes';
        require_once __DIR__ . '/../views/ordenes/editar.php';
    }

    // ==========================================
    // ACTUALIZAR
    // ==========================================

    /**
     * ✅ FIX 7: valida status='PENDIENTE' en POST.
     */
    public function actualizar($id) {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }

        $rol = $this->authHelper->getRole();
        $usuarioId = $this->authHelper->getUserId();

        if (!in_array($rol, ['admin', 'operador'], true)) {
            $_SESSION['error'] = 'No tienes permisos para editar órdenes';
            header('Location: /proyecto/dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/ordenes');
            exit;
        }

        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /proyecto/ordenes/editar/' . $id);
            exit;
        }

        $orden_original = $this->ordenModel->obtenerPorId($id);
        if (!$orden_original) {
            $_SESSION['error'] = 'Orden no encontrada';
            header('Location: /proyecto/ordenes');
            exit;
        }

        if ($rol === 'operador' && (int)$orden_original['creado_por'] !== $usuarioId) {
            $_SESSION['error'] = 'No tienes permisos para editar esta orden';
            header('Location: /proyecto/operador/ordenes');
            exit;
        }

        // ✅ FIX 7: revalidar status en POST
        if ($orden_original['status'] !== 'PENDIENTE') {
            $_SESSION['error'] = 'Solo se pueden editar órdenes en estado PENDIENTE';
            header('Location: /proyecto/ordenes/ver/' . $id);
            exit;
        }

        $errores = [];
        if (empty($_POST['titulo'])) {
            $errores[] = 'El título es obligatorio';
        }
        if (empty($_POST['descripcion_mantenimiento'])) {
            $errores[] = 'La descripción del mantenimiento es obligatoria';
        }

        if (!empty($errores)) {
            $_SESSION['errores'] = $errores;
            header('Location: /proyecto/ordenes/editar/' . $id);
            exit;
        }

        $datos = [
            'titulo' => $_POST['titulo'] ?? '',
            'descripcion_mantenimiento' => $_POST['descripcion_mantenimiento'] ?? '',
            'tipo_mantenimiento' => $_POST['tipo_mantenimiento'] ?? 'CORRECTIVO',
            'tipo_actividad' => $_POST['tipo_actividad'] ?? '',
            'prioridad' => $_POST['prioridad'] ?? 'Media',
            'tecnico_id' => !empty($_POST['tecnico_id']) ? (int)$_POST['tecnico_id'] : null,
            'id_supervisor' => !empty($_POST['id_supervisor']) ? (int)$_POST['id_supervisor'] : null,
            'id_planta' => !empty($_POST['id_planta']) ? (int)$_POST['id_planta'] : null,
            'id_area' => !empty($_POST['id_area']) ? (int)$_POST['id_area'] : null,
            'id_equipo' => !empty($_POST['id_equipo']) ? (int)$_POST['id_equipo'] : null,
            'id_componente' => !empty($_POST['id_componente']) ? (int)$_POST['id_componente'] : null,
            'solicitante' => $_POST['solicitante'] ?? '',
            'supervisor_solicitante' => $_POST['supervisor_solicitante'] ?? '',
            'fecha_inicio' => $_POST['fecha_inicio'] ?? date('Y-m-d'),
            'fecha_estimada' => !empty($_POST['fecha_estimada']) ? $_POST['fecha_estimada'] : null,
            'horas_duracion' => (float)($_POST['horas_duracion'] ?? 0),
            'tarifa_tecnico' => (float)($_POST['tarifa_tecnico'] ?? 0),
            'costo_repuestos' => (float)($_POST['costo_repuestos'] ?? 0),
            'costo_mano_obra' => (float)($_POST['costo_mano_obra'] ?? 0),
            'costo_total' => (float)($_POST['costo_total'] ?? 0),
            'pasos' => $_POST['pasos'] ?? '',
            'id_proveedor' => !empty($_POST['id_proveedor']) ? (int)$_POST['id_proveedor'] : null,
            'proveedor_servicio' => $_POST['proveedor_servicio'] ?? '',
            'n_tecnicos' => (int)($_POST['n_tecnicos'] ?? 1),
            'actualizado_por' => $this->authHelper->getUserId()
        ];

        $resultado = $this->ordenModel->actualizar($id, $datos);

        if ($resultado) {
            try {
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $this->authHelper->getUserId(),
                    $_SESSION['nombre'] ?? 'Sistema',
                    $_SESSION['rol'] ?? 'admin',
                    'actualizar',
                    'ordenes_mantenimiento',
                    $id,
                    ['titulo' => $orden_original['titulo']],
                    ['titulo' => $datos['titulo']]
                );
            } catch (Throwable $e) {
                error_log("Error al registrar auditoría de actualización: " . $e->getMessage());
            }

            $_SESSION['mensaje'] = 'Orden actualizada correctamente';
            $_SESSION['mensaje_tipo'] = 'success';
            header('Location: /proyecto/ordenes');
        } else {
            $_SESSION['error'] = 'Error al actualizar la orden';
            header('Location: /proyecto/ordenes/editar/' . $id);
        }
        exit;
    }

    // ==========================================
    // ELIMINAR
    // ==========================================

    public function eliminar($id) {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }

        if (!$this->authHelper->isAdmin()) {
            $_SESSION['error'] = 'No tienes permisos para eliminar órdenes';
            header('Location: /proyecto/dashboard');
            exit;
        }

        $orden = $this->ordenModel->obtenerPorId($id);
        if (!$orden) {
            $_SESSION['error'] = 'Orden no encontrada';
            header('Location: /proyecto/ordenes');
            exit;
        }

        if ($orden['status'] !== 'PENDIENTE') {
            $_SESSION['error'] = 'Solo se pueden eliminar órdenes en estado PENDIENTE';
            header('Location: /proyecto/ordenes/ver/' . $id);
            exit;
        }

        $resultado = $this->ordenModel->eliminar($id);

        if ($resultado) {
            try {
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $this->authHelper->getUserId(),
                    $_SESSION['nombre'] ?? 'Sistema',
                    $_SESSION['rol'] ?? 'admin',
                    'eliminar',
                    'ordenes_mantenimiento',
                    $id,
                    ['titulo' => $orden['titulo'], 'num_om' => $orden['num_om']],
                    null
                );
            } catch (Throwable $e) {
                error_log("Error al registrar auditoría de eliminación: " . $e->getMessage());
            }

            $_SESSION['mensaje'] = 'Orden eliminada correctamente';
            $_SESSION['mensaje_tipo'] = 'success';
        } else {
            $_SESSION['error'] = 'Error al eliminar la orden';
        }

        header('Location: /proyecto/ordenes');
        exit;
    }

    // ==========================================
    // CERRAR
    // ==========================================

    public function cerrar($id) {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }

        $orden = $this->ordenModel->obtenerPorId($id);

        if (!$orden) {
            $_SESSION['error'] = 'Orden no encontrada';
            header('Location: /proyecto/ordenes');
            exit;
        }

        $rol = $this->authHelper->getRole();

        if ($rol === 'tecnico') {
            $tecnicoId = $this->obtenerTecnicoIdPorEmail();

            if ($tecnicoId && (int)$orden['tecnico_id'] !== (int)$tecnicoId) {
                $_SESSION['error'] = 'No tienes permisos para cerrar esta orden';
                header('Location: /proyecto/tecnico/mis_ordenes');
                exit;
            }
        }

        if (!in_array($orden['status'], ['PENDIENTE', 'EN_PROCESO', 'EJECUTADA'], true)) {
            $_SESSION['error'] = 'La orden no se puede cerrar en su estado actual';
            header('Location: /proyecto/ordenes/ver/' . $id);
            exit;
        }

        if ($rol === 'tecnico') {
            try {
                $firmaModel = new FirmaModel();
                $paso_actual = $firmaModel->obtenerPasoActual($id);

                if (!$paso_actual || $paso_actual['rol_firmante'] !== 'tecnico') {
                    $_SESSION['error'] = 'No puedes cerrar esta orden. El paso actual requiere firma de: '
                        . ($paso_actual['rol_firmante'] ?? 'otro rol');
                    header('Location: /proyecto/tecnico/detalle_orden/' . $id);
                    exit;
                }
            } catch (Throwable $e) {
                error_log("Error verificando firma: " . $e->getMessage());
            }
        }

        $titulo = 'Cerrar Orden';
        $seccion = 'ordenes';
        require_once __DIR__ . '/../views/ordenes/cerrar.php';
    }

    /**
     * ✅ FIX 11: revalida paso de firma del técnico.
     */
    public function procesarCierre($id) {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/ordenes');
            exit;
        }

        $orden = $this->ordenModel->obtenerPorId($id);
        if (!$orden) {
            $_SESSION['error'] = 'Orden no encontrada';
            header('Location: /proyecto/ordenes');
            exit;
        }

        $rol = $this->authHelper->getRole();
        $usuarioId = $this->authHelper->getUserId();

        if ($rol === 'tecnico') {
            $tecnicoId = $this->obtenerTecnicoIdPorEmail();

            if ($tecnicoId && (int)$orden['tecnico_id'] !== (int)$tecnicoId) {
                $_SESSION['error'] = 'No tienes permisos para cerrar esta orden';
                header('Location: /proyecto/tecnico/mis_ordenes');
                exit;
            }
        }

        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /proyecto/ordenes/cerrar/' . $id);
            exit;
        }

        if (empty($_POST['descripcion_realizada'])) {
            $_SESSION['error'] = 'La descripción del trabajo realizado es obligatoria';
            header('Location: /proyecto/ordenes/cerrar/' . $id);
            exit;
        }

        if (empty($_POST['horas_trabajadas']) || (float)$_POST['horas_trabajadas'] <= 0) {
            $_SESSION['error'] = 'Las horas trabajadas son obligatorias';
            header('Location: /proyecto/ordenes/cerrar/' . $id);
            exit;
        }

        // ✅ FIX 11: revalidar paso actual de firma
        if ($rol === 'tecnico') {
            try {
                $firmaModel = new FirmaModel();
                $paso_actual = $firmaModel->obtenerPasoActual($id);

                if (!$paso_actual || $paso_actual['rol_firmante'] !== 'tecnico') {
                    $_SESSION['error'] = 'No puedes cerrar esta orden. El paso actual ya no está disponible.';
                    header('Location: /proyecto/tecnico/detalle_orden/' . $id);
                    exit;
                }
            } catch (Throwable $e) {
                error_log("Error revalidando firma: " . $e->getMessage());
            }
        }

        $datos = [
            'descripcion_realizada' => $_POST['descripcion_realizada'] ?? '',
            'pasos_ejecutados' => $_POST['pasos_ejecutados'] ?? '',
            'horas_trabajadas' => (float)($_POST['horas_trabajadas'] ?? 0),
            'tarifa_tecnico' => (float)($_POST['tarifa_tecnico'] ?? 0),
            'costo_repuestos' => (float)($_POST['costo_repuestos'] ?? 0),
            'costo_mano_obra' => (float)($_POST['costo_mano_obra'] ?? 0),
            'costo_total' => (float)($_POST['costo_total'] ?? 0),
            'foto_evidencia' => $_POST['foto_evidencia'] ?? '',
            'observaciones_tecnico' => $_POST['observaciones_tecnico'] ?? '',
            'observaciones_cierre' => $_POST['observaciones_cierre'] ?? '',
            'actualizado_por' => $usuarioId
        ];

        $resultado = $this->ordenModel->cerrar($id, $datos);

        if ($resultado) {
            // Firmar paso del técnico
            try {
                $firmaModel = new FirmaModel();
                $paso_actual = $firmaModel->obtenerPasoActual($id);

                if ($paso_actual && $paso_actual['rol_firmante'] === 'tecnico') {
                    $firma_svg = $_POST['firma_tecnico'] ?? '';

                    if (!empty($firma_svg)) {
                        $resultadoFirma = $firmaModel->firmar(
                            $id,
                            $paso_actual['id'],
                            $usuarioId,
                            $_SESSION['nombre'] ?? 'Técnico',
                            $firma_svg,
                            $_POST['observaciones_tecnico'] ?? 'Trabajo completado'
                        );

                        if (!$resultadoFirma['success']) {
                            error_log("Error al registrar firma del técnico: " . $resultadoFirma['mensaje']);
                        }
                    }
                }
            } catch (Throwable $e) {
                error_log("Error al registrar firma secuencial: " . $e->getMessage());
            }

            try {
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $this->authHelper->getUserId(),
                    $_SESSION['nombre'] ?? 'Sistema',
                    $_SESSION['rol'] ?? 'tecnico',
                    'cerrar',
                    'ordenes_mantenimiento',
                    $id,
                    ['estado_anterior' => $orden['status']],
                    ['estado_nuevo' => 'CERRADA', 'horas_trabajadas' => $datos['horas_trabajadas']]
                );
            } catch (Throwable $e) {
                error_log("Error al registrar auditoría de cierre: " . $e->getMessage());
            }

            $_SESSION['mensaje'] = 'Orden cerrada y firmada correctamente';
            $_SESSION['mensaje_tipo'] = 'success';

            if ($rol === 'tecnico') {
                header('Location: /proyecto/firmas/orden/' . $id);
            } else {
                header('Location: /proyecto/ordenes');
            }
        } else {
            $_SESSION['error'] = 'Error al cerrar la orden';
            header('Location: /proyecto/ordenes/cerrar/' . $id);
        }
        exit;
    }

    // ==========================================
    // CAMBIAR ESTADO
    // ==========================================

    /**
     * ✅ FIX 10: valida transiciones.
     */
    public function cambiarEstado() {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/ordenes');
            exit;
        }

        if (!$this->authHelper->isAdmin() && !$this->authHelper->isSupervisor()) {
            $_SESSION['error'] = 'No tienes permisos para cambiar estados';
            header('Location: /proyecto/dashboard');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $estado = $_POST['estado'] ?? '';
        $observaciones = $_POST['observaciones'] ?? '';

        if (empty($id) || empty($estado)) {
            $_SESSION['error'] = 'Datos incompletos';
            header('Location: /proyecto/ordenes');
            exit;
        }

        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /proyecto/ordenes');
            exit;
        }

        $orden_original = $this->ordenModel->obtenerPorId($id);
        if (!$orden_original) {
            $_SESSION['error'] = 'Orden no encontrada';
            header('Location: /proyecto/ordenes');
            exit;
        }

        // ✅ FIX 10: validar transición
        $transiciones_validas = [
            'PENDIENTE'  => ['EN_PROCESO', 'CANCELADA'],
            'EN_PROCESO' => ['EJECUTADA', 'CANCELADA'],
            'EJECUTADA'  => ['CERRADA', 'CANCELADA'],
            'CERRADA'    => ['APROBADA', 'RECHAZADA', 'DEVUELTA'],
            'APROBADA'   => [],
            'RECHAZADA'  => ['PENDIENTE'],
            'DEVUELTA'   => ['CERRADA', 'APROBADA'],
            'CANCELADA'  => [],
        ];

        $estado_actual = $orden_original['status'] ?? 'PENDIENTE';
        $permitidos = $transiciones_validas[$estado_actual] ?? [];

        if (!in_array($estado, $permitidos, true)) {
            $_SESSION['error'] = "No se puede cambiar de $estado_actual a $estado";
            header('Location: /proyecto/ordenes/ver/' . $id);
            exit;
        }

        $resultado = $this->ordenModel->cambiarEstado($id, $estado, $observaciones);

        if ($resultado) {
            try {
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $this->authHelper->getUserId(),
                    $_SESSION['nombre'] ?? 'Sistema',
                    $_SESSION['rol'] ?? 'supervisor',
                    'cambiar_estado',
                    'ordenes_mantenimiento',
                    $id,
                    ['estado_anterior' => $orden_original['status']],
                    ['estado_nuevo' => $estado]
                );
            } catch (Throwable $e) {
                error_log("Error al registrar auditoría de cambio de estado: " . $e->getMessage());
            }

            $_SESSION['mensaje'] = 'Estado actualizado correctamente';
            $_SESSION['mensaje_tipo'] = 'success';
        } else {
            $_SESSION['error'] = 'Error al cambiar el estado';
        }

        header('Location: /proyecto/ordenes/ver/' . $id);
        exit;
    }

    // ==========================================
    // DEVOLUCIONES
    // ==========================================

    public function devolver() {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/ordenes');
            exit;
        }

        if (!$this->authHelper->isAdmin() && !$this->authHelper->isSupervisor()) {
            $_SESSION['error'] = 'No tienes permisos para devolver órdenes';
            header('Location: /proyecto/dashboard');
            exit;
        }

        $orden_id = (int)($_POST['orden_id'] ?? 0);
        $motivo = $_POST['motivo'] ?? '';

        if (empty($orden_id) || empty($motivo)) {
            $_SESSION['error'] = 'Datos incompletos';
            header('Location: /proyecto/ordenes');
            exit;
        }

        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /proyecto/ordenes');
            exit;
        }

        $resultado = $this->ordenModel->registrarDevolucion($orden_id, $motivo, $this->authHelper->getUserId());

        if ($resultado['success']) {
            $_SESSION['mensaje'] = 'Devolución registrada correctamente';
            $_SESSION['mensaje_tipo'] = 'success';
        } else {
            $_SESSION['error'] = $resultado['error'] ?? 'Error al registrar la devolución';
        }

        header('Location: /proyecto/ordenes/ver/' . $orden_id);
        exit;
    }

    public function aprobarDevolucion() {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/ordenes');
            exit;
        }

        if (!$this->authHelper->isAdmin() && !$this->authHelper->isSupervisor()) {
            $_SESSION['error'] = 'No tienes permisos para aprobar devoluciones';
            header('Location: /proyecto/dashboard');
            exit;
        }

        $devolucion_id = (int)($_POST['devolucion_id'] ?? 0);

        if (empty($devolucion_id)) {
            $_SESSION['error'] = 'ID de devolución inválido';
            header('Location: /proyecto/ordenes');
            exit;
        }

        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /proyecto/ordenes');
            exit;
        }

        $resultado = $this->ordenModel->aprobarDevolucion($devolucion_id, $this->authHelper->getUserId());

        if ($resultado['success']) {
            $_SESSION['mensaje'] = 'Devolución aprobada correctamente';
            $_SESSION['mensaje_tipo'] = 'success';
        } else {
            $_SESSION['error'] = $resultado['error'] ?? 'Error al aprobar la devolución';
        }

        header('Location: /proyecto/ordenes');
        exit;
    }

    public function devoluciones() {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }

        if (!$this->authHelper->isAdmin() && !$this->authHelper->isSupervisor()) {
            $_SESSION['error'] = 'No tienes permisos para ver devoluciones';
            header('Location: /proyecto/dashboard');
            exit;
        }

        $devoluciones = $this->ordenModel->obtenerDevolucionesPendientes();

        $titulo = 'Devoluciones Pendientes';
        $seccion = 'devoluciones';
        require_once __DIR__ . '/../views/ordenes/devoluciones.php';
    }

    // ==========================================
    // ESTADÍSTICAS
    // ==========================================

    public function estadisticas() {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }

        $estadisticas = $this->ordenModel->obtenerEstadisticas();

        $prioridades = ['alta' => 0, 'media' => 0, 'baja' => 0];
        $evolucion_mensual = [];
        $rendimiento_tecnicos = [];

        try {
            $sql = "SELECT prioridad, COUNT(*) as total 
                    FROM ordenes_mantenimiento 
                    GROUP BY prioridad";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $key = strtolower($row['prioridad']);
                if (isset($prioridades[$key])) {
                    $prioridades[$key] = (int)$row['total'];
                }
            }

            $sql = "SELECT DATE_FORMAT(fecha_creacion, '%Y-%m') as mes, 
                           DATE_FORMAT(fecha_creacion, '%b %Y') as mes_label,
                           COUNT(*) as total
                    FROM ordenes_mantenimiento 
                    WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m')
                    ORDER BY mes ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $evolucion_mensual = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $sql = "SELECT t.nombre, 
                           COUNT(o.id) as total,
                           SUM(CASE WHEN o.status IN ('CERRADA', 'APROBADA', 'EJECUTADA') THEN 1 ELSE 0 END) as completadas
                    FROM tecnicos t
                    LEFT JOIN ordenes_mantenimiento o ON t.id = o.tecnico_id
                    WHERE t.estado = 'activo'
                    GROUP BY t.id
                    HAVING total > 0
                    ORDER BY completadas DESC
                    LIMIT 10";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $rendimiento_tecnicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Throwable $e) {
            error_log("Error cargando estadísticas: " . $e->getMessage());
        }

        $titulo = 'Estadísticas de Órdenes';
        $seccion = 'ordenes';
        require_once __DIR__ . '/../views/ordenes/estadisticas.php';
    }

    public function porEstado($estado) {
        if (!$this->authHelper->isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }

        $ordenes = $this->ordenModel->obtenerPorEstado($estado);
        header('Content-Type: application/json');
        echo json_encode($ordenes);
        exit;
    }

    // ==========================================
    // API DASHBOARD
    // ==========================================

    public function apiDashboard() {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
            echo json_encode(['success' => false, 'error' => 'No autorizado - Sesión no iniciada']);
            exit;
        }

        try {
            $filtros = [];

            if (isset($_GET['fecha_desde']) && !empty($_GET['fecha_desde'])) {
                $filtros['fecha_desde'] = $_GET['fecha_desde'];
            }
            if (isset($_GET['fecha_hasta']) && !empty($_GET['fecha_hasta'])) {
                $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
            }
            if (isset($_GET['id_planta']) && !empty($_GET['id_planta'])) {
                $filtros['id_planta'] = (int)$_GET['id_planta'];
            }
            if (isset($_GET['tipo_actividad']) && !empty($_GET['tipo_actividad'])) {
                $filtros['tipo_actividad'] = $_GET['tipo_actividad'];
            }
            if (isset($_GET['tipo_mantenimiento']) && !empty($_GET['tipo_mantenimiento'])) {
                $filtros['tipo_mantenimiento'] = $_GET['tipo_mantenimiento'];
            }

            $data = [
                'success' => true,
                'kpis' => $this->ordenModel->obtenerEstadisticasDashboard($filtros),
                'ordenes_mes' => $this->ordenModel->obtenerOrdenesPorMes($filtros),
                'ordenes_planta' => $this->ordenModel->obtenerOrdenesPorPlantaDashboard($filtros),
                'costo_planta' => $this->ordenModel->obtenerCostoPorPlanta($filtros),
                'tipo_actividad' => $this->ordenModel->obtenerOrdenesPorTipoActividad($filtros),
                'costo_mantenimiento' => $this->ordenModel->obtenerCostoPorTipoMantenimiento($filtros),
                'equipos_top' => $this->ordenModel->obtenerEquiposMayorRecurrencia(5, $filtros),
                'productividad_tecnicos' => $this->ordenModel->obtenerProductividadPorTecnico($filtros)
            ];

            echo json_encode($data);

        } catch (Throwable $e) {
            error_log("Error en apiDashboard: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function apiCalendario() {
        header('Content-Type: application/json');

        if (!$this->authHelper->isLoggedIn()) {
            echo json_encode([]);
            exit;
        }

        $usuarioId = $this->authHelper->getUserId();
        $rol = $this->authHelper->getRole();

        try {
            $sql = "SELECT id, titulo, fecha_inicio, fecha_estimada, status, prioridad 
                    FROM ordenes_mantenimiento 
                    WHERE 1=1";
            $params = [];

            if ($rol === 'tecnico') {
                $email = $_SESSION['email'] ?? '';
                $sql .= " AND tecnico_id = (SELECT id FROM tecnicos WHERE LOWER(email) = LOWER(?) LIMIT 1)";
                $params[] = $email;
            } elseif ($rol === 'operador') {
                $sql .= " AND creado_por = ?";
                $params[] = $usuarioId;
            }
            // admin y supervisor ven todas

            $sql .= " AND fecha_inicio IS NOT NULL";
            $sql .= " ORDER BY fecha_inicio ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $events = [];
            foreach ($ordenes as $orden) {
                $color = match($orden['prioridad'] ?? 'Media') {
                    'Urgente' => '#dc3545',
                    'Alta' => '#ffc107',
                    'Media' => '#0d6efd',
                    'Baja' => '#198754',
                    default => '#6c757d'
                };

                $events[] = [
                    'id' => $orden['id'],
                    'title' => $orden['titulo'] . ' (' . ($orden['status'] ?? 'PENDIENTE') . ')',
                    'start' => $orden['fecha_inicio'],
                    'end' => $orden['fecha_estimada'] ?? $orden['fecha_inicio'],
                    'color' => $color,
                    'extendedProps' => [
                        'status' => $orden['status'],
                        'prioridad' => $orden['prioridad']
                    ]
                ];
            }

            echo json_encode($events);

        } catch (Throwable $e) {
            error_log("Error en apiCalendario: " . $e->getMessage());
            echo json_encode([]);
        }
        exit;
    }

    public function verificarFirma($id) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
            echo json_encode([
                'success' => false,
                'valida' => false,
                'mensaje' => 'No autorizado - Sesión no iniciada'
            ]);
            exit;
        }

        $id = (int)$id;
        if ($id <= 0) {
            echo json_encode([
                'success' => false,
                'valida' => false,
                'mensaje' => 'ID de orden inválido'
            ]);
            exit;
        }

        try {
            $resultado = $this->ordenModel->verificarFirma($id);

            echo json_encode([
                'success' => true,
                'valida' => $resultado['valida'] ?? false,
                'firma_guardada' => $resultado['firma_guardada'] ?? '',
                'firma_calculada' => $resultado['firma_calculada'] ?? '',
                'mensaje' => $resultado['mensaje'] ?? ''
            ]);

        } catch (Throwable $e) {
            error_log("Error en verificarFirma: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'valida' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}