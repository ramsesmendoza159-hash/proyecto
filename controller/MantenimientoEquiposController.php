<?php
// controller/MantenimientoEquiposController.php
// Controlador del sistema de mantenimiento preventivo
// VERSIÓN CORREGIDA - CON PERMISOS PARA CONSULTOR

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../model/MantenimientoEquiposModel.php';
require_once __DIR__ . '/../model/EquiposModel.php';
require_once __DIR__ . '/../model/Tecnico.php';
require_once __DIR__ . '/../model/ProveedoresModel.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';

class MantenimientoEquiposController extends Controller {
    
    private $mantenimientoModel;
    private $equipoModel;
    private $tecnicoModel;
    private $proveedorModel;

    public function __construct() {
        parent::__construct();
        
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }
        
        // ✅ PERMITIR consultor (solo lectura)
        $allowedRoles = ['admin', 'supervisor', 'almacen', 'tecnico', 'consultor'];
        if (!$this->authHelper->hasRole($allowedRoles)) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            header('Location: /proyecto/dashboard');
            exit;
        }
        
        $this->mantenimientoModel = new MantenimientoEquiposModel();
        $this->equipoModel = new EquiposModel();
        $this->tecnicoModel = new Tecnico();
        $this->proveedorModel = new ProveedoresModel();
    }
    
    /**
     * Verificar si el usuario es consultor (solo lectura)
     */
    private function esConsultor() {
        return $this->authHelper->isConsultor();
    }

    // ==========================================
    // PANEL GENERAL
    // ==========================================
    
    public function panel() {
        $alertas = $this->mantenimientoModel->obtenerMantenimientosProximos(30, 100);
        $resumen = $this->mantenimientoModel->obtenerResumenAlertas();
        $estadisticas = $this->mantenimientoModel->obtenerEstadisticas();
        $historial_reciente = $this->mantenimientoModel->obtenerHistorialMantenimientos(null, 10);
        
        $titulo = 'Panel de Mantenimiento Preventivo';
        $seccion = 'mantenimiento';
        require_once __DIR__ . '/../views/mantenimiento/panel.php';
    }

    // ==========================================
    // PLAN POR EQUIPO
    // ==========================================
    
    public function equipo($id) {
        $equipo = $this->equipoModel->obtenerPorId($id);
        if (!$equipo) {
            $_SESSION['error'] = 'Equipo no encontrado';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        $plan = $this->mantenimientoModel->obtenerPlanPorEquipo($id);
        $historial = $this->mantenimientoModel->obtenerHistorialMantenimientos($id, 20);
        $historial_horometro = $this->mantenimientoModel->obtenerHistorialHorometro($id, 20);
        $proveedores = $this->proveedorModel->obtenerProveedoresServicios();
        $tecnicos = $this->tecnicoModel->obtenerTodos(['estado' => 'activo']);
        
        $titulo = 'Plan de Mantenimiento - ' . ($equipo['nombre_equipo'] ?? 'Equipo');
        $seccion = 'mantenimiento';
        require_once __DIR__ . '/../views/mantenimiento/equipo.php';
    }

    // ==========================================
    // DETALLE DE MANTENIMIENTO
    // ==========================================
    
    public function detalleMantenimiento($id) {
        $id = (int)$id;
        
        if ($id <= 0) {
            $_SESSION['error'] = 'ID de mantenimiento inválido';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        $mantenimiento = $this->mantenimientoModel->obtenerMantenimientoPorId($id);
        
        if (!$mantenimiento) {
            $_SESSION['error'] = 'Mantenimiento no encontrado';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        $equipo = $this->equipoModel->obtenerPorId($mantenimiento['id_equipo']);
        
        $plan = !empty($mantenimiento['plan_id']) 
            ? $this->mantenimientoModel->obtenerPlanPorId($mantenimiento['plan_id'])
            : null;
        
        $checklist = [];
        if (!empty($mantenimiento['plan_id'])) {
            if (!empty($mantenimiento['tecnico_id'])) {
                $checklist = $this->mantenimientoModel->obtenerChecklistEjecutado(
                    $mantenimiento['plan_id'],
                    $mantenimiento['tecnico_id']
                );
            }
            
            if (empty($checklist) && !empty($plan)) {
                $checklist = $this->mantenimientoModel->obtenerProtocolo(
                    $mantenimiento['plan_id'],
                    $plan['tipo_mantenimiento'] ?? 'PREVENTIVO'
                );
            }
        }
        
        $titulo = 'Detalle del Mantenimiento';
        $seccion = 'mantenimiento';
        
        require_once __DIR__ . '/../views/mantenimiento/detalle_mantenimiento.php';
    }

    // ==========================================
    // HORÓMETRO
    // ==========================================
    
    public function horometro($id) {
        // ✅ Consultor NO puede registrar horómetro
        if ($this->esConsultor()) {
            $_SESSION['error'] = 'No tienes permisos para registrar horómetro';
            header('Location: /proyecto/mantenimiento/equipo/' . $id);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/mantenimiento/equipo/' . $id);
            exit;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /proyecto/mantenimiento/equipo/' . $id);
            exit;
        }
        
        $horas = (int)$_POST['horas_actuales'];
        $observaciones = $_POST['observaciones'] ?? '';
        $usuario_id = $this->authHelper->getUserId();
        
        $fecha_lectura = null;
        if (!empty($_POST['fecha_lectura'])) {
            $timestamp = strtotime($_POST['fecha_lectura']);
            if ($timestamp !== false) {
                $fecha_lectura = date('Y-m-d H:i:s', $timestamp);
            }
        }
        
        $resultado = $this->mantenimientoModel->registrarHorometro(
            $id, $horas, $usuario_id, $observaciones, $fecha_lectura
        );
        
        if ($resultado['success']) {
            $fecha_msg = !empty($resultado['fecha_lectura']) 
                ? ' el ' . date('d/m/Y H:i', strtotime($resultado['fecha_lectura'])) 
                : '';
            $_SESSION['mensaje'] = "✅ Horómetro actualizado: <strong>{$horas} horas</strong> (+{$resultado['horas_desde_ultimo']} horas){$fecha_msg}";
            $_SESSION['mensaje_tipo'] = 'success';
        } else {
            $_SESSION['error'] = '❌ Error: ' . $resultado['error'];
        }
        
        header('Location: /proyecto/mantenimiento/equipo/' . $id);
        exit;
    }

    public function editarHorometro($id) {
        // ✅ Consultor NO puede editar horómetro
        if ($this->esConsultor()) {
            $_SESSION['error'] = 'No tienes permisos para editar el horómetro';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if (!$this->authHelper->isAdmin() && !$this->authHelper->isSupervisor()) {
            $_SESSION['error'] = 'Solo administradores y supervisores pueden editar el horómetro';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token inválido';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        $nuevas_horas = (int)($_POST['horas_actuales'] ?? 0);
        $observaciones = $_POST['observaciones'] ?? '';
        $id_equipo = (int)($_POST['id_equipo'] ?? 0);
        
        $nueva_fecha = null;
        if (!empty($_POST['fecha_lectura'])) {
            $timestamp = strtotime($_POST['fecha_lectura']);
            if ($timestamp !== false) {
                $nueva_fecha = date('Y-m-d H:i:s', $timestamp);
            }
        }
        
        if ($nuevas_horas < 0) {
            $_SESSION['error'] = 'Las horas no pueden ser negativas';
            header('Location: /proyecto/mantenimiento/equipo/' . $id_equipo);
            exit;
        }
        
        $resultado = $this->mantenimientoModel->editarLecturaHorometro(
            $id, $nuevas_horas, $observaciones, $this->authHelper->getUserId(), $nueva_fecha
        );
        
        if ($resultado['success']) {
            try {
                require_once __DIR__ . '/../model/AuditoriaModel.php';
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $this->authHelper->getUserId(),
                    $_SESSION['nombre'] ?? 'Sistema',
                    $_SESSION['rol'] ?? 'admin',
                    'editar_horometro',
                    'equipos_horometro',
                    $id,
                    ['horas' => $resultado['valor_anterior'] ?? 0, 'fecha' => $resultado['fecha_anterior'] ?? null],
                    ['horas' => $resultado['valor_nuevo'] ?? 0, 'fecha' => $resultado['fecha_nueva'] ?? null, 'observaciones' => $observaciones]
                );
            } catch (Exception $e) {
                error_log("Error auditoría: " . $e->getMessage());
            }
            
            $_SESSION['mensaje'] = '✅ Lectura de horómetro actualizada correctamente';
            $_SESSION['mensaje_tipo'] = 'success';
        } else {
            $_SESSION['error'] = '❌ Error: ' . $resultado['error'];
        }
        
        header('Location: /proyecto/mantenimiento/equipo/' . $id_equipo);
        exit;
    }

    public function eliminarHorometro($id) {
        // ✅ Consultor NO puede eliminar horómetro
        if ($this->esConsultor()) {
            $_SESSION['error'] = 'No tienes permisos para eliminar horómetro';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if (!$this->authHelper->isAdmin()) {
            $_SESSION['error'] = 'Solo administradores pueden eliminar lecturas de horómetro';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token inválido';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        $id_equipo = (int)($_POST['id_equipo'] ?? 0);
        $lectura = $this->mantenimientoModel->obtenerLecturaHorometro($id);
        
        $resultado = $this->mantenimientoModel->eliminarLecturaHorometro($id);
        
        if ($resultado['success']) {
            try {
                require_once __DIR__ . '/../model/AuditoriaModel.php';
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $this->authHelper->getUserId(),
                    $_SESSION['nombre'] ?? 'Sistema',
                    $_SESSION['rol'] ?? 'admin',
                    'eliminar_horometro',
                    'equipos_horometro',
                    $id,
                    $lectura ? ['horas' => $lectura['horas_actuales']] : null,
                    null
                );
            } catch (Exception $e) {
                error_log("Error auditoría: " . $e->getMessage());
            }
            
            $_SESSION['mensaje'] = '✅ Lectura de horómetro eliminada correctamente';
            $_SESSION['mensaje_tipo'] = 'success';
        } else {
            $_SESSION['error'] = '❌ Error: ' . $resultado['error'];
        }
        
        header('Location: /proyecto/mantenimiento/equipo/' . $id_equipo);
        exit;
    }

    // ==========================================
    // CREAR / ACTUALIZAR / ELIMINAR TAREAS
    // ==========================================
    
    public function crearTarea() {
        // ✅ Consultor NO puede crear tareas
        if ($this->esConsultor()) {
            $_SESSION['error'] = 'No tienes permisos para crear tareas';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        $id_equipo = (int)($_POST['id_equipo'] ?? 0);
        
        if ($id_equipo <= 0) {
            $_SESSION['error'] = 'Equipo inválido';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        $datos = [
            'id_equipo' => $id_equipo,
            'nombre_tarea' => trim($_POST['nombre_tarea'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'tipo_mantenimiento' => $_POST['tipo_mantenimiento'] ?? 'PREVENTIVO',
            'frecuencia_horas' => !empty($_POST['frecuencia_horas']) ? (int)$_POST['frecuencia_horas'] : null,
            'frecuencia_dias' => !empty($_POST['frecuencia_dias']) ? (int)$_POST['frecuencia_dias'] : null,
            'responsable' => $_POST['responsable'] ?? 'TECNICO_INTERNO',
            'id_proveedor' => !empty($_POST['id_proveedor']) ? (int)$_POST['id_proveedor'] : null,
            'costo_estimado' => (float)($_POST['costo_estimado'] ?? 0),
            'repuestos_necesarios' => trim($_POST['repuestos_necesarios'] ?? ''),
            'tecnico_asignado_id' => !empty($_POST['tecnico_asignado_id']) ? (int)$_POST['tecnico_asignado_id'] : null
        ];
        
        if (empty($datos['nombre_tarea'])) {
            $_SESSION['error'] = 'El nombre de la tarea es obligatorio';
            header('Location: /proyecto/mantenimiento/equipo/' . $id_equipo);
            exit;
        }
        
        $resultado = $this->mantenimientoModel->crearTarea($datos);
        
        if ($resultado) {
            $_SESSION['mensaje'] = '✅ Tarea de mantenimiento creada correctamente';
            $_SESSION['mensaje_tipo'] = 'success';
        } else {
            $_SESSION['error'] = '❌ Error al crear la tarea';
        }
        
        header('Location: /proyecto/mantenimiento/equipo/' . $id_equipo);
        exit;
    }
    
    public function actualizarTarea($id) {
        // ✅ Consultor NO puede actualizar tareas
        if ($this->esConsultor()) {
            $_SESSION['error'] = 'No tienes permisos para actualizar tareas';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        $id_equipo = (int)($_POST['id_equipo'] ?? 0);
        
        $datos = [
            'nombre_tarea' => trim($_POST['nombre_tarea'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'tipo_mantenimiento' => $_POST['tipo_mantenimiento'] ?? 'PREVENTIVO',
            'frecuencia_horas' => !empty($_POST['frecuencia_horas']) ? (int)$_POST['frecuencia_horas'] : null,
            'frecuencia_dias' => !empty($_POST['frecuencia_dias']) ? (int)$_POST['frecuencia_dias'] : null,
            'responsable' => $_POST['responsable'] ?? 'TECNICO_INTERNO',
            'id_proveedor' => !empty($_POST['id_proveedor']) ? (int)$_POST['id_proveedor'] : null,
            'costo_estimado' => (float)($_POST['costo_estimado'] ?? 0),
            'repuestos_necesarios' => trim($_POST['repuestos_necesarios'] ?? ''),
            'activo' => isset($_POST['activo']) ? (int)$_POST['activo'] : 1
        ];
        
        $resultado = $this->mantenimientoModel->actualizarTarea($id, $datos);
        
        if ($resultado) {
            $_SESSION['mensaje'] = '✅ Tarea actualizada correctamente';
            $_SESSION['mensaje_tipo'] = 'success';
        } else {
            $_SESSION['error'] = '❌ Error al actualizar la tarea';
        }
        
        header('Location: /proyecto/mantenimiento/equipo/' . $id_equipo);
        exit;
    }
    
    public function eliminarTarea($id) {
        // ✅ Consultor NO puede eliminar tareas
        if ($this->esConsultor()) {
            $_SESSION['error'] = 'No tienes permisos para eliminar tareas';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        $id_equipo = (int)($_POST['id_equipo'] ?? 0);
        
        $resultado = $this->mantenimientoModel->eliminarTarea($id);
        
        if ($resultado) {
            $_SESSION['mensaje'] = '✅ Tarea eliminada correctamente';
            $_SESSION['mensaje_tipo'] = 'success';
        } else {
            $_SESSION['error'] = '❌ Error al eliminar la tarea';
        }
        
        header('Location: /proyecto/mantenimiento/equipo/' . $id_equipo);
        exit;
    }

    // ==========================================
    // REGISTRAR MANTENIMIENTO REALIZADO
    // ==========================================
    
    public function completar($id) {
        // ✅ Consultor NO puede completar mantenimientos
        if ($this->esConsultor()) {
            $_SESSION['error'] = 'No tienes permisos para completar mantenimientos';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        $datos = [
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'tecnico_id' => !empty($_POST['tecnico_id']) ? (int)$_POST['tecnico_id'] : null,
            'proveedor_id' => !empty($_POST['proveedor_id']) ? (int)$_POST['proveedor_id'] : null,
            'costo' => (float)($_POST['costo'] ?? 0),
            'repuestos_usados' => trim($_POST['repuestos_usados'] ?? ''),
            'observaciones' => trim($_POST['observaciones'] ?? '')
        ];
        
        if ($this->authHelper->isTecnico() && empty($datos['tecnico_id'])) {
            $tecnico_id = $this->mantenimientoModel->obtenerTecnicoIdByEmail($_SESSION['email'] ?? '');
            if ($tecnico_id) {
                $datos['tecnico_id'] = $tecnico_id;
            }
        }
        
        $resultado = $this->mantenimientoModel->registrarMantenimientoRealizado($id, $datos);
        
        if ($resultado['success']) {
            $_SESSION['mensaje'] = '✅ Mantenimiento registrado correctamente';
            $_SESSION['mensaje_tipo'] = 'success';
            
            if ($this->authHelper->isTecnico()) {
                header('Location: /proyecto/mantenimiento/mis-tareas');
                exit;
            }
        } else {
            $_SESSION['error'] = '❌ Error: ' . $resultado['error'];
        }
        
        header('Location: /proyecto/mantenimiento/panel');
        exit;
    }

    // ==========================================
    // ASIGNACIÓN A TÉCNICOS
    // ==========================================
    
    public function asignar($id) {
        // ✅ Consultor NO puede asignar técnicos
        if ($this->esConsultor()) {
            $_SESSION['error'] = 'No tienes permisos para asignar técnicos';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if (!$this->authHelper->isAdmin() && !$this->authHelper->isSupervisor()) {
            $_SESSION['error'] = 'No tienes permisos para asignar tareas';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        $tecnico_id = (int)($_POST['tecnico_id'] ?? 0);
        $id_equipo = (int)($_POST['id_equipo'] ?? 0);
        
        if ($tecnico_id <= 0) {
            $_SESSION['error'] = 'Debes seleccionar un técnico válido';
            header('Location: /proyecto/mantenimiento/equipo/' . $id_equipo);
            exit;
        }
        
        $resultado = $this->mantenimientoModel->asignarTecnico(
            $id, $tecnico_id, $this->authHelper->getUserId()
        );
        
        if ($resultado) {
            try {
                require_once __DIR__ . '/../model/AuditoriaModel.php';
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $this->authHelper->getUserId(),
                    $_SESSION['nombre'] ?? 'Sistema',
                    $_SESSION['rol'] ?? 'admin',
                    'asignar_tarea',
                    'equipos_mantenimiento_plan',
                    $id,
                    null,
                    ['tecnico_id' => $tecnico_id]
                );
            } catch (Exception $e) {
                error_log("Error auditoría: " . $e->getMessage());
            }
            
            $_SESSION['mensaje'] = '✅ Técnico asignado correctamente';
            $_SESSION['mensaje_tipo'] = 'success';
        } else {
            $_SESSION['error'] = '❌ Error al asignar el técnico';
        }
        
        header('Location: /proyecto/mantenimiento/equipo/' . $id_equipo);
        exit;
    }

    // ==========================================
    // MIS TAREAS - PANEL DEL TÉCNICO
    // ==========================================
    
    public function mis_tareas() {
        if (!$this->authHelper->isTecnico()) {
            $_SESSION['error'] = 'Solo los técnicos pueden acceder a esta sección';
            header('Location: /proyecto/dashboard');
            exit;
        }
        
        $email = $_SESSION['email'] ?? '';
        $tecnico_id = $this->mantenimientoModel->obtenerTecnicoIdByEmail($email);
        
        if (!$tecnico_id) {
            $tareas = [];
            $estadisticas = ['total' => 0, 'vencidas' => 0, 'proximas' => 0, 'al_dia' => 0];
            $_SESSION['error'] = 'No se encontró tu perfil de técnico. Contacta al administrador.';
            
            $titulo = 'Mis Mantenimientos';
            $seccion = 'mis_tareas';
            require_once __DIR__ . '/../views/mantenimiento/mis_tareas.php';
            return;
        }
        
        $tareas = $this->mantenimientoModel->obtenerTareasAsignadas($tecnico_id);
        
        $estadisticas = [
            'total' => count($tareas),
            'vencidas' => 0,
            'proximas' => 0,
            'al_dia' => 0
        ];
        
        foreach ($tareas as $t) {
            $alerta = $t['estado_alerta'] ?? 'AL_DIA';
            if ($alerta === 'VENCIDO') $estadisticas['vencidas']++;
            elseif ($alerta === 'PROXIMO') $estadisticas['proximas']++;
            else $estadisticas['al_dia']++;
        }
        
        $titulo = 'Mis Mantenimientos';
        $seccion = 'mis_tareas';
        
        require_once __DIR__ . '/../views/mantenimiento/mis_tareas.php';
    }

    // ==========================================
    // EJECUTAR MANTENIMIENTO CON CHECKLIST
    // ==========================================
    
    public function ejecutar($id) {
        if (!$this->authHelper->isTecnico()) {
            $_SESSION['error'] = 'No tienes permisos para ejecutar mantenimientos';
            header('Location: /proyecto/dashboard');
            exit;
        }
        
        $plan = $this->mantenimientoModel->obtenerPlanPorId($id);
        
        if (!$plan) {
            $_SESSION['error'] = 'Tarea no encontrada';
            header('Location: /proyecto/mantenimiento/mis-tareas');
            exit;
        }
        
        $tecnico_id = $this->mantenimientoModel->obtenerTecnicoIdByEmail($_SESSION['email'] ?? '');
        
        if ($plan['tecnico_asignado_id'] != $tecnico_id) {
            $_SESSION['error'] = 'Esta tarea no está asignada a ti';
            header('Location: /proyecto/mantenimiento/mis-tareas');
            exit;
        }
        
        $tipo_mant = $plan['tipo_mantenimiento'] ?? 'PREVENTIVO';
        $protocolo = $this->mantenimientoModel->obtenerProtocolo($id, $tipo_mant);
        
        $checklist_ejecutado = $tecnico_id 
            ? $this->mantenimientoModel->obtenerChecklistEjecutado($id, $tecnico_id)
            : [];
        
        $titulo = 'Ejecutar Mantenimiento';
        $seccion = 'mis_tareas';
        
        require_once __DIR__ . '/../views/mantenimiento/ejecutar.php';
    }

    // ==========================================
    // COMPLETAR EJECUCIÓN CON FOTOS
    // ==========================================
    
    public function completarEjecucion($id) {
        // ✅ Consultor NO puede completar ejecuciones
        if ($this->esConsultor()) {
            $_SESSION['error'] = 'No tienes permisos para completar ejecuciones';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/mantenimiento/mis-tareas');
            exit;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /proyecto/mantenimiento/ejecutar/' . $id);
            exit;
        }
        
        $plan = $this->mantenimientoModel->obtenerPlanPorId($id);
        
        if (!$plan) {
            $_SESSION['error'] = 'Tarea no encontrada';
            header('Location: /proyecto/mantenimiento/mis-tareas');
            exit;
        }
        
        $tecnico_id = $this->mantenimientoModel->obtenerTecnicoIdByEmail($_SESSION['email'] ?? '');
        
        if ($plan['tecnico_asignado_id'] != $tecnico_id) {
            $_SESSION['error'] = 'Esta tarea no está asignada a ti';
            header('Location: /proyecto/mantenimiento/mis-tareas');
            exit;
        }
        
        $pasos = $_POST['pasos'] ?? [];
        $observaciones_pasos = $_POST['observaciones'] ?? [];
        $fotos_subidas = $_FILES['fotos'] ?? [];
        
        $this->mantenimientoModel->cambiarEstadoAsignacion($id, 'EN_PROCESO');
        
        $uploadDir = __DIR__ . '/../uploads/mantenimiento/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fotos_guardadas = 0;
        
        if (!empty($pasos) && is_array($pasos)) {
            foreach ($pasos as $paso_id => $cumplido) {
                $foto_nombre = null;
                
                if (!empty($fotos_subidas['name'][$paso_id])) {
                    $file = [
                        'name' => $fotos_subidas['name'][$paso_id],
                        'type' => $fotos_subidas['type'][$paso_id],
                        'tmp_name' => $fotos_subidas['tmp_name'][$paso_id],
                        'error' => $fotos_subidas['error'][$paso_id],
                        'size' => $fotos_subidas['size'][$paso_id]
                    ];
                    
                    $resultado_foto = $this->procesarFotoEvidencia($file, $uploadDir);
                    
                    if ($resultado_foto['success']) {
                        $foto_nombre = $resultado_foto['filename'];
                        $fotos_guardadas++;
                    } else {
                        error_log("Error al subir foto del paso $paso_id: " . $resultado_foto['error']);
                    }
                }
                
                $this->mantenimientoModel->guardarPasoChecklist([
                    'plan_id' => $id,
                    'id_equipo' => $plan['id_equipo'],
                    'tecnico_id' => $tecnico_id,
                    'paso_id' => (int)$paso_id,
                    'cumplido' => $cumplido ? 1 : 0,
                    'observaciones' => $observaciones_pasos[$paso_id] ?? '',
                    'foto_evidencia' => $foto_nombre
                ]);
            }
        }
        
        $datos = [
            'descripcion' => trim($_POST['descripcion'] ?? $plan['nombre_tarea']),
            'tecnico_id' => $tecnico_id,
            'proveedor_id' => null,
            'costo' => (float)($_POST['costo'] ?? 0),
            'repuestos_usados' => trim($_POST['repuestos_usados'] ?? ''),
            'observaciones' => trim($_POST['observaciones_generales'] ?? '')
        ];
        
        $resultado = $this->mantenimientoModel->registrarMantenimientoRealizado($id, $datos);
        
        if ($resultado['success']) {
            $msg = '✅ Mantenimiento completado correctamente';
            if ($fotos_guardadas > 0) {
                $msg .= " ({$fotos_guardadas} foto" . ($fotos_guardadas > 1 ? 's' : '') . " guardada" . ($fotos_guardadas > 1 ? 's' : '') . ")";
            }
            $_SESSION['mensaje'] = $msg;
            $_SESSION['mensaje_tipo'] = 'success';
        } else {
            $_SESSION['error'] = '❌ Error: ' . ($resultado['error'] ?? 'Error desconocido');
            header('Location: /proyecto/mantenimiento/ejecutar/' . $id);
            exit;
        }
        
        header('Location: /proyecto/mantenimiento/mis-tareas');
        exit;
    }

    /**
     * Procesar y validar subida de foto de evidencia
     */
    private function procesarFotoEvidencia($file, $uploadDir) {
        try {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                return ['success' => false, 'error' => 'Error de subida: código ' . $file['error']];
            }
            
            if ($file['size'] > 5 * 1024 * 1024) {
                return ['success' => false, 'error' => 'La foto excede los 5MB'];
            }
            
            $tipos_permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
            if (!in_array($file['type'], $tipos_permitidos)) {
                return ['success' => false, 'error' => 'Formato no permitido'];
            }
            
            $info_imagen = @getimagesize($file['tmp_name']);
            if ($info_imagen === false) {
                return ['success' => false, 'error' => 'El archivo no es una imagen válida'];
            }
            
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                $extension = 'jpg';
            }
            
            $filename = 'evidencia_' . date('YmdHis') . '_' . uniqid() . '.' . $extension;
            $ruta_completa = $uploadDir . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $ruta_completa)) {
                return ['success' => false, 'error' => 'Error al guardar el archivo'];
            }
            
            return ['success' => true, 'filename' => $filename];
            
        } catch (Exception $e) {
            error_log("Error en procesarFotoEvidencia: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Eliminar una foto de evidencia
     */
    public function eliminarFoto($id) {
        // ✅ Consultor NO puede eliminar fotos
        if ($this->esConsultor()) {
            echo json_encode(['success' => false, 'error' => 'No tienes permisos']);
            exit;
        }
        
        if (!$this->authHelper->isAdmin() && !$this->authHelper->isSupervisor()) {
            echo json_encode(['success' => false, 'error' => 'No tienes permisos']);
            exit;
        }
        
        try {
            $resultado = $this->mantenimientoModel->eliminarFotoEvidencia($id);
            echo json_encode($resultado);
        } catch (Exception $e) {
            error_log("Error en eliminarFoto: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // ==========================================
    // EDITAR MANTENIMIENTO
    // ==========================================
    
    public function editarMantenimiento($id) {
        // ✅ Consultor NO puede editar
        if ($this->esConsultor()) {
            $_SESSION['error'] = 'No tienes permisos para editar mantenimientos';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if (!$this->authHelper->isAdmin() && !$this->authHelper->isSupervisor()) {
            $_SESSION['error'] = 'Solo administradores y supervisores pueden editar mantenimientos';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        $id = (int)$id;
        $mantenimiento = $this->mantenimientoModel->obtenerMantenimientoPorId($id);
        
        if (!$mantenimiento) {
            $_SESSION['error'] = 'Mantenimiento no encontrado';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        $tecnicos = $this->tecnicoModel->obtenerTodos(['estado' => 'activo']);
        $proveedores = $this->proveedorModel->obtenerProveedoresServicios();
        $equipo = $this->equipoModel->obtenerPorId($mantenimiento['id_equipo']);
        
        $titulo = 'Editar Mantenimiento';
        $seccion = 'mantenimiento';
        
        require_once __DIR__ . '/../views/mantenimiento/editar_mantenimiento.php';
    }
    
    public function actualizarMantenimiento($id) {
        // ✅ Consultor NO puede actualizar
        if ($this->esConsultor()) {
            $_SESSION['error'] = 'No tienes permisos';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if (!$this->authHelper->isAdmin() && !$this->authHelper->isSupervisor()) {
            $_SESSION['error'] = 'No tienes permisos';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            header('Location: /proyecto/mantenimiento/editar-mantenimiento/' . $id);
            exit;
        }
        
        $motivo_edicion = trim($_POST['motivo_edicion'] ?? '');
        if (empty($motivo_edicion)) {
            $_SESSION['error'] = 'Debes indicar el motivo de la edición';
            header('Location: /proyecto/mantenimiento/editar-mantenimiento/' . $id);
            exit;
        }
        
        $datos = [
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'horas_equipo' => (int)($_POST['horas_equipo'] ?? 0),
            'fecha_mantenimiento' => $_POST['fecha_mantenimiento'] ?? date('Y-m-d H:i:s'),
            'tecnico_id' => !empty($_POST['tecnico_id']) ? (int)$_POST['tecnico_id'] : null,
            'proveedor_id' => !empty($_POST['proveedor_id']) ? (int)$_POST['proveedor_id'] : null,
            'costo' => (float)($_POST['costo'] ?? 0),
            'repuestos_usados' => trim($_POST['repuestos_usados'] ?? ''),
            'observaciones' => trim($_POST['observaciones'] ?? '')
        ];
        
        $resultado = $this->mantenimientoModel->actualizarMantenimiento(
            $id, $datos, $this->authHelper->getUserId(), $motivo_edicion
        );
        
        if ($resultado['success']) {
            $_SESSION['mensaje'] = '✅ Mantenimiento actualizado correctamente';
            $_SESSION['mensaje_tipo'] = 'success';
            header('Location: /proyecto/mantenimiento/detalle-mantenimiento/' . $id);
        } else {
            $_SESSION['error'] = '❌ Error: ' . $resultado['error'];
            header('Location: /proyecto/mantenimiento/editar-mantenimiento/' . $id);
        }
        exit;
    }
    
    // ==========================================
    // EDITAR CHECKLIST
    // ==========================================
    
    public function editarChecklist($id) {
        // ✅ Consultor NO puede editar checklist
        if ($this->esConsultor()) {
            $_SESSION['error'] = 'No tienes permisos';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if (!$this->authHelper->isAdmin() && !$this->authHelper->isSupervisor()) {
            $_SESSION['error'] = 'No tienes permisos';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        $id = (int)$id;
        $mantenimiento = $this->mantenimientoModel->obtenerMantenimientoPorId($id);
        
        if (!$mantenimiento) {
            $_SESSION['error'] = 'Mantenimiento no encontrado';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        $checklist = $this->mantenimientoModel->obtenerChecklistDeMantenimiento($id);
        
        if (empty($checklist) && !empty($mantenimiento['plan_id'])) {
            $checklist = $this->mantenimientoModel->obtenerProtocolo(
                $mantenimiento['plan_id'],
                $mantenimiento['plan_tipo'] ?? 'PREVENTIVO'
            );
        }
        
        $titulo = 'Editar Checklist';
        $seccion = 'mantenimiento';
        
        require_once __DIR__ . '/../views/mantenimiento/editar_checklist.php';
    }
    
    public function actualizarChecklist($id) {
        // ✅ Consultor NO puede actualizar checklist
        if ($this->esConsultor()) {
            $_SESSION['error'] = 'No tienes permisos';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if (!$this->authHelper->isAdmin() && !$this->authHelper->isSupervisor()) {
            $_SESSION['error'] = 'No tienes permisos';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token inválido';
            header('Location: /proyecto/mantenimiento/editar-checklist/' . $id);
            exit;
        }
        
        $pasos = [];
        if (!empty($_POST['pasos']) && is_array($_POST['pasos'])) {
            foreach ($_POST['pasos'] as $paso_id => $datos_paso) {
                $pasos[$paso_id] = [
                    'cumplido' => !empty($datos_paso['cumplido']) ? 1 : 0,
                    'observaciones' => $datos_paso['observaciones'] ?? ''
                ];
            }
        }
        
        $resultado = $this->mantenimientoModel->actualizarChecklistEjecutado(
            $id, $pasos, $this->authHelper->getUserId()
        );
        
        if ($resultado['success']) {
            $_SESSION['mensaje'] = '✅ Checklist actualizado';
            $_SESSION['mensaje_tipo'] = 'success';
            header('Location: /proyecto/mantenimiento/detalle-mantenimiento/' . $id);
        } else {
            $_SESSION['error'] = '❌ Error: ' . $resultado['error'];
            header('Location: /proyecto/mantenimiento/editar-checklist/' . $id);
        }
        exit;
    }
    
    /**
     * Eliminar un mantenimiento
     */
    public function eliminarMantenimiento($id) {
        // ✅ Consultor NO puede eliminar
        if ($this->esConsultor()) {
            $_SESSION['error'] = 'No tienes permisos';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if (!$this->authHelper->isAdmin()) {
            $_SESSION['error'] = 'Solo administradores pueden eliminar mantenimientos';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token inválido';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        $mantenimiento = $this->mantenimientoModel->obtenerMantenimientoPorId($id);
        $id_equipo = $mantenimiento['id_equipo'] ?? 0;
        
        $resultado = $this->mantenimientoModel->eliminarMantenimiento($id);
        
        if ($resultado['success']) {
            $_SESSION['mensaje'] = '✅ Mantenimiento eliminado';
            $_SESSION['mensaje_tipo'] = 'success';
        } else {
            $_SESSION['error'] = '❌ Error: ' . $resultado['error'];
        }
        
        header('Location: /proyecto/mantenimiento/equipo/' . $id_equipo);
        exit;
    }

    // ==========================================
    // API DE ALERTAS (AJAX)
    // ==========================================
    
    public function apiAlertas() {
        header('Content-Type: application/json');
        
        try {
            $resumen = $this->mantenimientoModel->obtenerResumenAlertas();
            $alertas = $this->mantenimientoModel->obtenerMantenimientosProximos(30, 100);
            
            echo json_encode([
                'success' => true,
                'resumen' => $resumen,
                'alertas' => $alertas,
                'total' => count($alertas)
            ]);
        } catch (Exception $e) {
            error_log("Error en apiAlertas: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'resumen' => ['total_alertas' => 0],
                'alertas' => []
            ]);
        }
        exit;
    }

    // ==========================================
    // PROTOCOLOS DE CHECKLIST
    // ==========================================
    
    public function protocolos() {
        // ✅ Consultor PUEDE ver pero no editar
        if (!$this->authHelper->isAdmin() && 
            !$this->authHelper->isSupervisor() && 
            !$this->authHelper->isConsultor()) {
            $_SESSION['error'] = 'No tienes permisos';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        $protocolos = $this->mantenimientoModel->obtenerTodosLosProtocolos();
        
        $titulo = 'Protocolos de Checklist';
        $seccion = 'mantenimiento';
        
        require_once __DIR__ . '/../views/mantenimiento/protocolos.php';
    }

    public function editarProtocolo($tipo) {
        // ✅ Consultor NO puede editar
        if ($this->esConsultor()) {
            $_SESSION['error'] = 'No tienes permisos para editar protocolos';
            header('Location: /proyecto/mantenimiento/protocolos');
            exit;
        }
        
        if (!$this->authHelper->isAdmin() && !$this->authHelper->isSupervisor()) {
            $_SESSION['error'] = 'No tienes permisos';
            header('Location: /proyecto/mantenimiento/panel');
            exit;
        }
        
        $tipo = urldecode($tipo);
        $pasos = $this->mantenimientoModel->obtenerProtocoloPorTipo($tipo);
        
        $titulo = 'Editar Protocolo: ' . $tipo;
        $seccion = 'mantenimiento';
        
        require_once __DIR__ . '/../views/mantenimiento/editar_protocolo.php';
    }

    public function guardarPaso() {
        // ✅ Consultor NO puede guardar pasos
        if ($this->esConsultor()) {
            echo json_encode(['success' => false, 'error' => 'Sin permisos']);
            exit;
        }
        
        if (!$this->authHelper->isAdmin() && !$this->authHelper->isSupervisor()) {
            echo json_encode(['success' => false, 'error' => 'Sin permisos']);
            exit;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'error' => 'Token inválido']);
            exit;
        }
        
        $datos = [
            'plan_id' => $_POST['plan_id'] ?? null,
            'tipo_mantenimiento' => $_POST['tipo_mantenimiento'] ?? 'PREVENTIVO',
            'paso_numero' => $_POST['paso_numero'] ?? 1,
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'obligatorio' => isset($_POST['obligatorio']),
            'requiere_foto' => isset($_POST['requiere_foto'])
        ];
        
        if (empty($datos['descripcion'])) {
            echo json_encode(['success' => false, 'error' => 'Descripción obligatoria']);
            exit;
        }
        
        $resultado = $this->mantenimientoModel->crearPasoProtocolo($datos);
        
        if ($resultado) {
            echo json_encode(['success' => true, 'id' => $resultado]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al crear']);
        }
        exit;
    }

    public function actualizarPaso($id) {
        if ($this->esConsultor()) {
            echo json_encode(['success' => false, 'error' => 'Sin permisos']);
            exit;
        }
        
        if (!$this->authHelper->isAdmin() && !$this->authHelper->isSupervisor()) {
            echo json_encode(['success' => false, 'error' => 'Sin permisos']);
            exit;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'error' => 'Token inválido']);
            exit;
        }
        
        $datos = [
            'paso_numero' => $_POST['paso_numero'] ?? 1,
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'obligatorio' => isset($_POST['obligatorio']),
            'requiere_foto' => isset($_POST['requiere_foto'])
        ];
        
        $resultado = $this->mantenimientoModel->actualizarPasoProtocolo($id, $datos);
        echo json_encode(['success' => (bool)$resultado]);
        exit;
    }

    public function eliminarPaso($id) {
        if ($this->esConsultor()) {
            echo json_encode(['success' => false, 'error' => 'Sin permisos']);
            exit;
        }
        
        if (!$this->authHelper->isAdmin() && !$this->authHelper->isSupervisor()) {
            echo json_encode(['success' => false, 'error' => 'Sin permisos']);
            exit;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'error' => 'Token inválido']);
            exit;
        }
        
        $resultado = $this->mantenimientoModel->eliminarPasoProtocolo($id);
        echo json_encode(['success' => (bool)$resultado]);
        exit;
    }

    public function reordenarPasos() {
        if ($this->esConsultor()) {
            echo json_encode(['success' => false, 'error' => 'Sin permisos']);
            exit;
        }
        
        if (!$this->authHelper->isAdmin() && !$this->authHelper->isSupervisor()) {
            echo json_encode(['success' => false, 'error' => 'Sin permisos']);
            exit;
        }
        
        if (!SecurityHelper::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'error' => 'Token inválido']);
            exit;
        }
        
        $orden = $_POST['orden'] ?? [];
        
        if (empty($orden)) {
            echo json_encode(['success' => false, 'error' => 'Sin orden']);
            exit;
        }
        
        $resultado = $this->mantenimientoModel->reordenarPasos($orden);
        echo json_encode(['success' => (bool)$resultado]);
        exit;
    }
}
?>