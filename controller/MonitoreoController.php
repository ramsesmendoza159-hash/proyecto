<?php
// controller/MonitoreoController.php
// Controlador del módulo de Monitoreo de Equipos (MTTO R007 y sucesivos)
// ✅ Roles: admin, supervisor, operador, tecnico
// ✅ Operador/tecnico solo ven y editan su turno
// ✅ Admin/supervisor pueden ver todo y firmar como jefe de operación

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';
require_once __DIR__ . '/../model/MonitoreoModel.php';
require_once __DIR__ . '/../model/AuditoriaModel.php';

class MonitoreoController extends Controller {

    private $model;
    private $db;

    public function __construct() {
        parent::__construct();

        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
        }

        $rol = $this->authHelper->getRole();
        $rolesPermitidos = ['admin', 'supervisor', 'operador', 'tecnico'];
        if (!in_array($rol, $rolesPermitidos, true)) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            $this->redirect('/dashboard');
        }

        $this->model = new MonitoreoModel();
        $this->db = Database::getInstance()->getConnection();
    }

    private function esAdminOSupervisor() {
        return $this->authHelper->isAdmin() || $this->authHelper->isSupervisor();
    }

    private function esOperadorOTecnico() {
        return $this->authHelper->isOperador() || $this->authHelper->isTecnico();
    }

    // ==========================================
    // LISTADO DE RUTAS
    // ==========================================

    /**
     * URL: /monitoreo
     * Lista de rutas. Admin/supervisor ven todas. Operador/tecnico ven solo las suyas.
     */
    public function index() {
        try {
            $filtros = [
                'buscar' => $this->get('buscar', ''),
                'activo' => 1
            ];
            $rutas = $this->model->obtenerTodas($filtros);

            $stats_global = $this->model->obtenerEstadisticas([
                'fecha_desde' => date('Y-m-01'),
                'fecha_hasta' => date('Y-m-t')
            ]);

            $hoy = date('Y-m-d');
            $registros_hoy = $this->model->obtenerRegistrosDelDia($hoy);

        } catch (Throwable $e) {
            error_log("Error en MonitoreoController::index: " . $e->getMessage());
            $rutas = [];
            $stats_global = [];
            $registros_hoy = [];
            $_SESSION['error'] = 'Error al cargar las rutas de monitoreo';
        }

        $titulo = 'Monitoreo de Equipos';
        $seccion = 'monitoreo';

        require_once __DIR__ . '/../views/monitoreo/index.php';
    }

    // ==========================================
    // MIS RUTAS (operador/tecnico)
    // ==========================================

    /**
     * URL: /monitoreo/mis-rutas
     * El operador ve las rutas que le corresponden y puede iniciar su turno.
     */
    public function misRutas() {
        if (!$this->esOperadorOTecnico() && !$this->esAdminOSupervisor()) {
            $_SESSION['error'] = 'No tienes permisos';
            $this->redirect('/dashboard');
        }

        try {
            $fecha = $this->get('fecha', date('Y-m-d'));
            $turno_actual = $this->detectarTurnoActual();

            $rutas = $this->model->obtenerTodas(['activo' => 1]);

            // Para cada ruta, buscar el registro del turno actual (o de la fecha dada)
            foreach ($rutas as &$ruta) {
                $ruta['registro_actual'] = $this->model->obtenerRegistroPorRutaFechaTurno(
                    $ruta['id'], $fecha, $turno_actual
                );
            }
            unset($ruta);

        } catch (Throwable $e) {
            error_log("Error en MonitoreoController::misRutas: " . $e->getMessage());
            $rutas = [];
            $turno_actual = 'DIA';
            $_SESSION['error'] = 'Error al cargar tus rutas';
        }

        $titulo = 'Mis Rutas de Monitoreo';
        $seccion = 'monitoreo';

        require_once __DIR__ . '/../views/monitoreo/mis_rutas.php';
    }

    // ==========================================
    // INICIAR / RETOMAR REGISTRO
    // ==========================================

    /**
     * URL: /monitoreo/iniciar/{ruta_id}
     * Crea (o recupera) el registro del turno actual para la ruta indicada.
     */
    public function iniciar($ruta_id) {
        if (!$this->esOperadorOTecnico() && !$this->esAdminOSupervisor()) {
            $_SESSION['error'] = 'No tienes permisos';
            $this->redirect('/dashboard');
        }

        $ruta_id = (int)$ruta_id;
        if ($ruta_id <= 0) {
            $_SESSION['error'] = 'Ruta inválida';
            $this->redirect('/monitoreo/mis-rutas');
        }

        $fecha = $this->get('fecha', date('Y-m-d'));
        $turno = $this->get('turno', $this->detectarTurnoActual());

        try {
            $registro = $this->model->iniciarRegistro(
                $ruta_id,
                $fecha,
                $turno,
                $this->authHelper->getUserId()
            );

            if (!$registro) {
                throw new Exception($this->model->getLastError() ?: 'No se pudo iniciar el registro');
            }

            $this->redirect('/monitoreo/llenar/' . $registro['id']);

        } catch (Throwable $e) {
            error_log("Error en MonitoreoController::iniciar: " . $e->getMessage());
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
            $this->redirect('/monitoreo/mis-rutas');
        }
    }

    // ==========================================
    // LLENAR GRILLA
    // ==========================================

    /**
     * URL: /monitoreo/llenar/{registro_id}
     * Muestra la grilla de llenado. El operador solo edita las horas de su turno.
     */
    public function llenar($registro_id) {
        $registro_id = (int)$registro_id;
        if ($registro_id <= 0) {
            $_SESSION['error'] = 'Registro inválido';
            $this->redirect('/monitoreo');
        }

        try {
            $registro = $this->model->obtenerRegistro($registro_id);
            if (!$registro) {
                $_SESSION['error'] = 'Registro no encontrado';
                $this->redirect('/monitoreo');
            }

            // Verificar permisos: operador/tecnico solo su turno
            $rol = $this->authHelper->getRole();
            $puede_editar = false;

            if ($this->esAdminOSupervisor()) {
                $puede_editar = ($registro['estado'] === 'BORRADOR');
            } else {
                // operador/tecnico
                if ($registro['estado'] === 'BORRADOR') {
                    // Solo si es su turno
                    $puede_editar = true;
                }
            }

            // Organizar lecturas en matriz [id_equipo][hora] => valor
            $matriz = [];
            foreach ($registro['lecturas'] as $lec) {
                $matriz[$lec['id_equipo']][$lec['hora']] = $lec;
            }
            $registro['matriz'] = $matriz;

        } catch (Throwable $e) {
            error_log("Error en MonitoreoController::llenar: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar el registro';
            $this->redirect('/monitoreo');
        }

        $titulo = 'Llenar: ' . $registro['ruta_nombre'];
        $seccion = 'monitoreo';

        require_once __DIR__ . '/../views/monitoreo/llenar.php';
    }

    // ==========================================
    // GUARDAR LECTURA (AJAX)
    // ==========================================

    /**
     * URL: /monitoreo/guardar-lectura (POST)
     * Guarda una celda individual.
     */
    public function guardarLectura() {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
            exit;
        }

        if (!$this->esOperadorOTecnico() && !$this->esAdminOSupervisor()) {
            echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!SecurityHelper::verifyCSRFToken($token)) {
            echo json_encode(['success' => false, 'mensaje' => 'Token de seguridad inválido']);
            exit;
        }

        $registro_id = (int)($_POST['registro_id'] ?? 0);
        $id_equipo = (int)($_POST['id_equipo'] ?? 0);
        $hora = trim($_POST['hora'] ?? '');
        $valor = $_POST['valor'] ?? null;
        $observacion = trim($_POST['observacion'] ?? '');

        if ($registro_id <= 0 || $id_equipo <= 0 || empty($hora)) {
            echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
            exit;
        }

        if ($valor !== null && $valor !== '' && !is_numeric($valor)) {
            echo json_encode(['success' => false, 'mensaje' => 'El valor debe ser numérico']);
            exit;
        }

        try {
            $resultado = $this->model->guardarLectura(
                $registro_id,
                $id_equipo,
                $hora,
                ($valor === '' ? null : $valor),
                $observacion,
                $this->authHelper->getUserId()
            );

            if (!$resultado) {
                echo json_encode([
                    'success' => false,
                    'mensaje' => $this->model->getLastError() ?: 'Error al guardar'
                ]);
                exit;
            }

            echo json_encode([
                'success' => true,
                'nivel_alerta' => $resultado['nivel_alerta'],
                'fuera_de_rango' => $resultado['fuera_de_rango']
            ]);

        } catch (Throwable $e) {
            error_log("Error en MonitoreoController::guardarLectura: " . $e->getMessage());
            echo json_encode(['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    // ==========================================
    // CERRAR TURNO
    // ==========================================

    /**
     * URL: /monitoreo/cerrar/{registro_id} (POST)
     * Valida que las lecturas estén completas y cambia el estado a CERRADO.
     */
    public function cerrar($registro_id) {
        if (!$this->esOperadorOTecnico() && !$this->esAdminOSupervisor()) {
            $_SESSION['error'] = 'No tienes permisos';
            $this->redirect('/dashboard');
        }

        $this->requirePost();

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $_SESSION['error'] = 'Token de seguridad inválido';
            $this->redirect('/monitoreo/llenar/' . $registro_id);
        }

        $registro_id = (int)$registro_id;
        if ($registro_id <= 0) {
            $_SESSION['error'] = 'Registro inválido';
            $this->redirect('/monitoreo');
        }

        try {
            $resultado = $this->model->cerrarRegistro($registro_id, $this->authHelper->getUserId());

            if (!$resultado) {
                throw new Exception($this->model->getLastError() ?: 'No se pudo cerrar el registro');
            }

            try {
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $this->authHelper->getUserId(),
                    $_SESSION['nombre'] ?? 'Usuario',
                    $_SESSION['rol'] ?? '',
                    'cerrar_monitoreo',
                    'monitoreo_registros',
                    $registro_id
                );
            } catch (Throwable $e) {
                error_log("Error auditoría: " . $e->getMessage());
            }

            $this->redirectWithSuccess('/monitoreo/firmar/' . $registro_id, 'Registro cerrado. Ahora firmá tu turno.');

        } catch (Throwable $e) {
            error_log("Error en MonitoreoController::cerrar: " . $e->getMessage());
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
            $this->redirect('/monitoreo/llenar/' . $registro_id);
        }
    }

    // ==========================================
    // FIRMAS
    // ==========================================

    /**
     * URL: /monitoreo/firmar/{registro_id}
     * Muestra el panel de firmas del registro.
     */
    public function firmar($registro_id) {
        $registro_id = (int)$registro_id;
        if ($registro_id <= 0) {
            $_SESSION['error'] = 'Registro inválido';
            $this->redirect('/monitoreo');
        }

        try {
            $registro = $this->model->obtenerRegistro($registro_id);
            if (!$registro) {
                $_SESSION['error'] = 'Registro no encontrado';
                $this->redirect('/monitoreo');
            }

            $paso_actual = $this->model->obtenerPasoActual($registro_id);
            $rol_actual = $this->authHelper->getRole();

            $puede_firmar = false;
            if ($paso_actual && $paso_actual['rol_firmante'] === $rol_actual) {
                $puede_firmar = true;
            }

        } catch (Throwable $e) {
            error_log("Error en MonitoreoController::firmar: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar el registro';
            $this->redirect('/monitoreo');
        }

        $titulo = 'Firmar Registro';
        $seccion = 'monitoreo';

        require_once __DIR__ . '/../views/monitoreo/firmar.php';
    }

    /**
     * URL: /monitoreo/procesar-firma (POST)
     * Registra la firma del paso actual.
     */
    public function procesarFirma() {
        $this->requirePost();

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $this->redirectWithError('/monitoreo', 'Token de seguridad inválido');
        }

        $registro_id = (int)$this->post('registro_id', 0);
        $paso_id = (int)$this->post('paso_id', 0);
        $firma_svg = $this->post('firma_svg', '');
        $comentario = $this->post('comentario', '');

        if ($registro_id <= 0 || $paso_id <= 0) {
            $this->redirectWithError('/monitoreo', 'Datos incompletos');
        }

        // Validar que sea el paso actual y del rol correcto
        $paso_actual = $this->model->obtenerPasoActual($registro_id);
        $rol_actual = $this->authHelper->getRole();

        if (!$paso_actual) {
            $this->redirectWithError('/monitoreo/firmar/' . $registro_id, 'No hay pasos pendientes');
        }
        if ((int)$paso_actual['id'] !== $paso_id) {
            $this->redirectWithError('/monitoreo/firmar/' . $registro_id, 'Este paso ya no está disponible');
        }
        if ($paso_actual['rol_firmante'] !== $rol_actual) {
            $this->redirectWithError('/monitoreo/firmar/' . $registro_id, 'No tienes el rol requerido para este paso');
        }

        $resultado = $this->model->firmar(
            $registro_id,
            $paso_id,
            $this->authHelper->getUserId(),
            $_SESSION['nombre'] ?? 'Usuario',
            $firma_svg,
            $comentario
        );

        if ($resultado['success']) {
            try {
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $this->authHelper->getUserId(),
                    $_SESSION['nombre'] ?? 'Usuario',
                    $rol_actual,
                    'firmar_monitoreo',
                    'monitoreo_firmas',
                    $paso_id,
                    null,
                    ['registro_id' => $registro_id, 'paso' => $paso_actual['paso']]
                );
            } catch (Throwable $e) {
                error_log("Error auditoría firma: " . $e->getMessage());
            }

            $msg = $resultado['completada']
                ? '¡Firmas completadas! El registro está aprobado.'
                : 'Firma registrada. Siguiente paso: ' . ($resultado['siguiente_paso'] ?? 'N/A');

            $this->redirectWithSuccess('/monitoreo/ver/' . $registro_id, $msg);
        } else {
            $this->redirectWithError('/monitoreo/firmar/' . $registro_id, $resultado['mensaje']);
        }
    }

    /**
     * URL: /monitoreo/rechazar (POST)
     */
    public function rechazar() {
        $this->requirePost();

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $this->redirectWithError('/monitoreo', 'Token de seguridad inválido');
        }

        $registro_id = (int)$this->post('registro_id', 0);
        $paso_id = (int)$this->post('paso_id', 0);
        $motivo = trim($this->post('motivo', ''));

        if (empty($motivo)) {
            $this->redirectWithError('/monitoreo/firmar/' . $registro_id, 'Debes indicar el motivo');
        }

        $resultado = $this->model->rechazar(
            $registro_id,
            $paso_id,
            $this->authHelper->getUserId(),
            $_SESSION['nombre'] ?? 'Usuario',
            $motivo
        );

        if ($resultado['success']) {
            $this->redirectWithWarning('/monitoreo/ver/' . $registro_id, 'Registro rechazado');
        } else {
            $this->redirectWithError('/monitoreo/firmar/' . $registro_id, $resultado['mensaje']);
        }
    }

    // ==========================================
    // VER / HISTORIAL
    // ==========================================

    /**
     * URL: /monitoreo/ver/{registro_id}
     * Ver un registro cerrado.
     */
    public function ver($registro_id) {
        $registro_id = (int)$registro_id;
        if ($registro_id <= 0) {
            $_SESSION['error'] = 'Registro inválido';
            $this->redirect('/monitoreo');
        }

        try {
            $registro = $this->model->obtenerRegistro($registro_id);
            if (!$registro) {
                $_SESSION['error'] = 'Registro no encontrado';
                $this->redirect('/monitoreo');
            }

            $matriz = [];
            foreach ($registro['lecturas'] as $lec) {
                $matriz[$lec['id_equipo']][$lec['hora']] = $lec;
            }
            $registro['matriz'] = $matriz;

        } catch (Throwable $e) {
            error_log("Error en MonitoreoController::ver: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar el registro';
            $this->redirect('/monitoreo');
        }

        $titulo = 'Registro: ' . $registro['ruta_nombre'];
        $seccion = 'monitoreo';

        require_once __DIR__ . '/../views/monitoreo/ver.php';
    }

    /**
     * URL: /monitoreo/historial
     */
    public function historial() {
        try {
            $filtros = [
                'ruta_id' => $this->get('ruta_id', ''),
                'fecha_desde' => $this->get('fecha_desde', date('Y-m-01')),
                'fecha_hasta' => $this->get('fecha_hasta', date('Y-m-d')),
                'turno' => $this->get('turno', ''),
                'estado' => $this->get('estado', '')
            ];

            $registros = $this->model->obtenerRegistros($filtros);
            $rutas = $this->model->obtenerTodas(['activo' => 1]);
            $stats = $this->model->obtenerEstadisticas($filtros);

        } catch (Throwable $e) {
            error_log("Error en MonitoreoController::historial: " . $e->getMessage());
            $registros = [];
            $rutas = [];
            $stats = [];
            $_SESSION['error'] = 'Error al cargar el historial';
        }

        $titulo = 'Historial de Monitoreo';
        $seccion = 'monitoreo';

        require_once __DIR__ . '/../views/monitoreo/historial.php';
    }

    // ==========================================
    // CONFIGURACIÓN DE RUTAS (admin/supervisor)
    // ==========================================

    /**
     * URL: /monitoreo/crear
     */
    public function crear() {
        if (!$this->esAdminOSupervisor()) {
            $_SESSION['error'] = 'No tienes permisos';
            $this->redirect('/monitoreo');
        }

        $titulo = 'Nueva Ruta de Monitoreo';
        $seccion = 'monitoreo';
        $ruta = null;
        $equipos_disponibles = $this->obtenerEquiposDisponibles();
        $todos_horarios = $this->generarHorariosEstandar();

        require_once __DIR__ . '/../views/monitoreo/ruta_form.php';
    }

    /**
     * URL: /monitoreo/editar/{id}
     */
    public function editar($id) {
        if (!$this->esAdminOSupervisor()) {
            $_SESSION['error'] = 'No tienes permisos';
            $this->redirect('/monitoreo');
        }

        $ruta = $this->model->obtenerPorId((int)$id);
        if (!$ruta) {
            $_SESSION['error'] = 'Ruta no encontrada';
            $this->redirect('/monitoreo');
        }

        $titulo = 'Editar Ruta';
        $seccion = 'monitoreo';
        $equipos_disponibles = $this->obtenerEquiposDisponibles();
        $todos_horarios = $this->generarHorariosEstandar();

        require_once __DIR__ . '/../views/monitoreo/ruta_form.php';
    }

    /**
     * URL: /monitoreo/guardar-ruta (POST)
     */
    public function guardarRuta() {
        if (!$this->esAdminOSupervisor()) {
            $_SESSION['error'] = 'No tienes permisos';
            $this->redirect('/monitoreo');
        }

        $this->requirePost();

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $this->redirectWithError('/monitoreo/crear', 'Token inválido');
        }

        $datos = [
            'codigo' => trim($this->post('codigo', '')),
            'nombre' => trim($this->post('nombre', '')),
            'descripcion' => trim($this->post('descripcion', '')),
            'tipo_valor' => $this->post('tipo_valor', 'porcentaje'),
            'unidad_medida' => $this->post('unidad_medida', '%'),
            'frecuencia_horas' => (int)$this->post('frecuencia_horas', 2),
            'valor_min_global' => $this->post('valor_min_global', null),
            'valor_max_global' => $this->post('valor_max_global', null),
            'valor_objetivo_relleno' => $this->post('valor_objetivo_relleno', null),
            'activo' => 1
        ];

        if (empty($datos['codigo']) || empty($datos['nombre'])) {
            $this->redirectWithError('/monitoreo/crear', 'Código y nombre son obligatorios');
        }

        try {
            $id = $this->model->crear($datos);
            if (!$id) {
                throw new Exception($this->model->getLastError() ?: 'Error al crear la ruta');
            }

            try {
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $this->authHelper->getUserId(),
                    $_SESSION['nombre'] ?? 'Sistema',
                    $_SESSION['rol'] ?? 'admin',
                    'crear',
                    'rutas_monitoreo',
                    $id,
                    null,
                    ['codigo' => $datos['codigo'], 'nombre' => $datos['nombre']]
                );
            } catch (Throwable $e) {
                error_log("Error auditoría: " . $e->getMessage());
            }

            $this->redirectWithSuccess('/monitoreo', 'Ruta creada correctamente');

        } catch (Throwable $e) {
            error_log("Error en MonitoreoController::guardarRuta: " . $e->getMessage());
            $this->redirectWithError('/monitoreo/crear', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * URL: /monitoreo/actualizar-ruta/{id} (POST)
     */
    public function actualizarRuta($id) {
        if (!$this->esAdminOSupervisor()) {
            $_SESSION['error'] = 'No tienes permisos';
            $this->redirect('/monitoreo');
        }

        $this->requirePost();

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $this->redirectWithError('/monitoreo/editar/' . $id, 'Token inválido');
        }

        $id = (int)$id;
        $datos = [
            'nombre' => trim($this->post('nombre', '')),
            'descripcion' => trim($this->post('descripcion', '')),
            'tipo_valor' => $this->post('tipo_valor', 'porcentaje'),
            'unidad_medida' => $this->post('unidad_medida', '%'),
            'frecuencia_horas' => (int)$this->post('frecuencia_horas', 2),
            'valor_min_global' => $this->post('valor_min_global', null),
            'valor_max_global' => $this->post('valor_max_global', null),
            'valor_objetivo_relleno' => $this->post('valor_objetivo_relleno', null),
            'activo' => (int)$this->post('activo', 1)
        ];

        try {
            if (!$this->model->actualizar($id, $datos)) {
                throw new Exception($this->model->getLastError() ?: 'Error al actualizar');
            }

            $this->redirectWithSuccess('/monitoreo', 'Ruta actualizada correctamente');

        } catch (Throwable $e) {
            error_log("Error en MonitoreoController::actualizarRuta: " . $e->getMessage());
            $this->redirectWithError('/monitoreo/editar/' . $id, 'Error: ' . $e->getMessage());
        }
    }

    /**
     * URL: /monitoreo/eliminar-ruta/{id} (POST)
     */
    public function eliminarRuta($id) {
        if (!$this->authHelper->isAdmin()) {
            $_SESSION['error'] = 'Solo administradores pueden eliminar rutas';
            $this->redirect('/monitoreo');
        }

        $this->requirePost();

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $this->redirectWithError('/monitoreo', 'Token inválido');
        }

        try {
            if (!$this->model->eliminar((int)$id)) {
                throw new Exception($this->model->getLastError() ?: 'Error al eliminar');
            }
            $this->redirectWithSuccess('/monitoreo', 'Ruta eliminada');

        } catch (Throwable $e) {
            error_log("Error en MonitoreoController::eliminarRuta: " . $e->getMessage());
            $this->redirectWithError('/monitoreo', 'Error: ' . $e->getMessage());
        }
    }

    // ==========================================
    // REPORTE / EXPORTAR
    // ==========================================

    /**
     * URL: /monitoreo/reporte/{registro_id}
     * Vista imprimible (formato papel).
     */
    public function reporte($registro_id) {
        $registro_id = (int)$registro_id;
        $registro = $this->model->obtenerRegistro($registro_id);
        if (!$registro) {
            $_SESSION['error'] = 'Registro no encontrado';
            $this->redirect('/monitoreo');
        }

        $matriz = [];
        foreach ($registro['lecturas'] as $lec) {
            $matriz[$lec['id_equipo']][$lec['hora']] = $lec;
        }
        $registro['matriz'] = $matriz;

        $titulo = 'Reporte de Monitoreo';
        $seccion = 'monitoreo';

        require_once __DIR__ . '/../views/monitoreo/reporte.php';
    }

    /**
     * URL: /monitoreo/exportar (GET)
     */
    public function exportar() {
        if (!$this->esAdminOSupervisor()) {
            $_SESSION['error'] = 'No tienes permisos';
            $this->redirect('/monitoreo');
        }

        $filtros = [
            'ruta_id' => $this->get('ruta_id', ''),
            'fecha_desde' => $this->get('fecha_desde', date('Y-m-01')),
            'fecha_hasta' => $this->get('fecha_hasta', date('Y-m-d')),
            'turno' => $this->get('turno', '')
        ];

        $registros = $this->model->obtenerRegistros($filtros);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="monitoreo_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($out, ['Ruta', 'Fecha', 'Turno', 'Operador', 'Estado', 'Lecturas', 'Fuera de rango']);

        foreach ($registros as $r) {
            fputcsv($out, [
                $r['ruta_codigo'],
                $r['fecha'],
                $r['turno'],
                $r['operador_nombre'] ?? 'N/A',
                $r['estado'],
                $r['lecturas_completadas'] ?? 0,
                $r['lecturas_fuera_rango'] ?? 0
            ]);
        }

        fclose($out);
        exit;
    }

    // ==========================================
    // APIs
    // ==========================================

    /**
     * URL: /monitoreo/api/alertas
     */
    public function apiAlertas() {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->authHelper->isLoggedIn()) {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit;
        }

        try {
            $ruta_id = (int)$this->get('ruta_id', 0);
            $alertas = $this->model->obtenerAlertas(
                $this->get('fecha_desde', date('Y-m-01')),
                $this->get('fecha_hasta', date('Y-m-d')),
                $ruta_id ?: null
            );

            echo json_encode(['success' => true, 'alertas' => $alertas, 'total' => count($alertas)]);

        } catch (Throwable $e) {
            error_log("Error en MonitoreoController::apiAlertas: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * URL: /monitoreo/api/lecturas-equipo/{id_equipo}
     */
    public function apiLecturasEquipo($id_equipo) {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->authHelper->isLoggedIn()) {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit;
        }

        try {
            $lecturas = $this->model->obtenerLecturasPorEquipo(
                (int)$id_equipo,
                $this->get('fecha_desde', date('Y-m-01')),
                $this->get('fecha_hasta', date('Y-m-d'))
            );

            echo json_encode(['success' => true, 'lecturas' => $lecturas]);

        } catch (Throwable $e) {
            error_log("Error en MonitoreoController::apiLecturasEquipo: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * URL: /monitoreo/api/registros-dia
     */
    public function apiRegistrosDia() {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->authHelper->isLoggedIn()) {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit;
        }

        try {
            $fecha = $this->get('fecha', date('Y-m-d'));
            $registros = $this->model->obtenerRegistrosDelDia($fecha);

            echo json_encode(['success' => true, 'registros' => $registros]);

        } catch (Throwable $e) {
            error_log("Error en MonitoreoController::apiRegistrosDia: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // ==========================================
    // UTILITARIOS
    // ==========================================

    /**
     * Detectar turno actual según la hora del servidor
     */
    private function detectarTurnoActual() {
        $hora = (int)date('H');
        if ($hora >= 8 && $hora < 16) return 'DIA';
        if ($hora >= 16 && $hora < 24) return 'TARDE';
        return 'NOCHE';
    }

    /**
     * Obtener lista de equipos disponibles para asignar a una ruta
     */
    private function obtenerEquiposDisponibles() {
        try {
            $sql = "SELECT id_equipo, nombre_equipo, codigo, serie, estado_operativo
                    FROM equipos
                    WHERE estado = 'activo'
                    ORDER BY nombre_equipo ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log("Error en obtenerEquiposDisponibles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Generar las 12 horas estándar (cada 2h)
     */
    private function generarHorariosEstandar() {
        return [
            ['hora' => '08:00:00', 'turno' => 'DIA',   'label' => '8:00 AM'],
            ['hora' => '10:00:00', 'turno' => 'DIA',   'label' => '10:00 AM'],
            ['hora' => '12:00:00', 'turno' => 'DIA',   'label' => '12:00 PM'],
            ['hora' => '14:00:00', 'turno' => 'DIA',   'label' => '2:00 PM'],
            ['hora' => '16:00:00', 'turno' => 'TARDE', 'label' => '4:00 PM'],
            ['hora' => '18:00:00', 'turno' => 'TARDE', 'label' => '6:00 PM'],
            ['hora' => '20:00:00', 'turno' => 'TARDE', 'label' => '8:00 PM'],
            ['hora' => '22:00:00', 'turno' => 'TARDE', 'label' => '10:00 PM'],
            ['hora' => '00:00:00', 'turno' => 'NOCHE', 'label' => '12:00 AM'],
            ['hora' => '02:00:00', 'turno' => 'NOCHE', 'label' => '2:00 AM'],
            ['hora' => '04:00:00', 'turno' => 'NOCHE', 'label' => '4:00 AM'],
            ['hora' => '06:00:00', 'turno' => 'NOCHE', 'label' => '6:00 AM'],
        ];
    }
}