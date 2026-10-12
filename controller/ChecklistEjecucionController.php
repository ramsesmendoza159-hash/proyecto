<?php
// controller/ChecklistEjecucionController.php
// Controlador de ejecución de checklists (llenar, guardar lecturas, cerrar, firmar)


require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';
require_once __DIR__ . '/../model/ChecklistModel.php';
require_once __DIR__ . '/../model/AuditoriaModel.php';

class ChecklistEjecucionController extends Controller
{
    private $model;
    private $db;

    public function __construct()
    {
        parent::__construct();

        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
        }

        $this->model = new ChecklistModel();
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Mis checklists (listado de tipos asignados al rol del usuario)
     * URL: /checklists/mis-checklists
     * ✅ FIX 12: prioriza registros asignados específicamente al usuario
     * ✅ FIX 14: separa los registros vencidos (fecha < hoy, BORRADOR)
     */
    public function misChecklists()
    {
        $rol = $this->authHelper->getRole();
        $usuario_id = $this->authHelper->getUserId();

        try {
            $tipos = $this->model->obtenerTiposPorRol($rol, $usuario_id);
            $fecha = $this->get('fecha', date('Y-m-d'));

            // Asignados específicamente al usuario
            $asignados = $this->model->obtenerRegistrosAsignadosAUsuario($usuario_id);

            // Registros vencidos (asignados al usuario, sin cerrar, fecha < hoy)
            $vencidos = $this->model->obtenerRegistrosVencidosPorUsuario($usuario_id);

            // Indexar asignados por tipo_id para búsqueda rápida
            $asignados_por_tipo = [];
            foreach ($asignados as $a) {
                $tipo_id_asig = (int)$a['tipo_id'];
                if ($a['fecha'] === $fecha) {
                    $asignados_por_tipo[$tipo_id_asig] = $a;
                }
            }

            foreach ($tipos as &$tipo) {
                $turno = $tipo['requiere_turno'] ? $this->model->detectarTurnoActual() : null;

                // Prioridad 1: registro asignado a mí (hoy)
                $reg = null;
                $es_asignado_a_mi = false;
                if (isset($asignados_por_tipo[(int)$tipo['id']])) {
                    $reg = $asignados_por_tipo[(int)$tipo['id']];
                    $es_asignado_a_mi = true;
                }

                // Prioridad 2: registro del tipo+fecha+turno (comportamiento previo)
                if (!$reg) {
                    $reg = $this->model->obtenerRegistroPorTipoFechaTurno(
                        $tipo['id'], $fecha, $turno
                    );
                }

                $tipo['registro_hoy'] = $reg;
                $tipo['es_asignado_a_mi'] = $es_asignado_a_mi;
            }
            unset($tipo);

        } catch (Throwable $e) {
            error_log("Error en ChecklistEjecucionController::misChecklists: " . $e->getMessage());
            $tipos = [];
            $vencidos = [];
            $fecha = date('Y-m-d');
            $_SESSION['error'] = 'Error al cargar tus checklists';
        }

        $titulo = 'Mis Checklists';
        $seccion = 'checklists';
        require_once __DIR__ . '/../views/checklists/mis_checklists.php';
    }

    /**
     * Iniciar (o recuperar) un registro del día
     * URL: /checklists/iniciar/{tipo_id}
     */
    public function iniciar($tipo_id)
    {
        $tipo_id = (int)$tipo_id;
        if ($tipo_id <= 0) {
            $this->redirectWithError('/checklists/mis-checklists', 'Tipo inválido');
            return;
        }

        try {
            $tipo = $this->model->obtenerTipoPorId($tipo_id);
            if (!$tipo) {
                $this->redirectWithError('/checklists/mis-checklists', 'Checklist no encontrado');
                return;
            }

            $fecha = $this->get('fecha', date('Y-m-d'));
            $turno = $tipo['requiere_turno'] ? $this->get('turno', $this->model->detectarTurnoActual()) : null;

            $registro = $this->model->iniciarRegistro(
                $tipo_id, $fecha, $turno, $this->authHelper->getUserId()
            );

            if (!$registro) {
                throw new \RuntimeException($this->model->getLastError() ?: 'No se pudo iniciar el registro');
            }

            $this->redirect('/checklists/llenar/' . $registro['id']);
            return;

        } catch (Throwable $e) {
            error_log("Error en ChecklistEjecucionController::iniciar: " . $e->getMessage());
            $this->redirectWithError('/checklists/mis-checklists', 'No se pudo iniciar el registro');
            return;
        }
    }

    /**
     * Formulario de asignación
     * URL: /checklists/asignar
     * Solo admin y operador
     */
    public function asignar()
    {
        $rol = $this->authHelper->getRole();
        if (!in_array($rol, ['admin', 'operador'], true)) {
            $this->redirectWithError('/checklists', 'No tienes permisos para asignar checklists');
            return;
        }

        try {
            $tipos = $this->model->obtenerTipos(['activo' => 1]);
            $tecnicos = $this->model->obtenerTecnicosParaAsignar();
        } catch (Throwable $e) {
            error_log("Error en ChecklistEjecucionController::asignar: " . $e->getMessage());
            $tipos = [];
            $tecnicos = [];
            $_SESSION['error'] = 'Error al cargar datos para la asignación';
        }

        $titulo = 'Asignar Checklist';
        $seccion = 'checklists';
        require_once __DIR__ . '/../views/checklists/asignar.php';
    }

    /**
     * Procesar la asignación
     * URL: /checklists/asignar/guardar (POST)
     * Solo admin y operador
     */
    public function guardarAsignacion()
    {
        $this->requirePost();

        $rol = $this->authHelper->getRole();
        if (!in_array($rol, ['admin', 'operador'], true)) {
            $this->redirectWithError('/checklists', 'No tienes permisos para asignar checklists');
            return;
        }

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $this->redirectWithError('/checklists/asignar', 'Token de seguridad inválido');
            return;
        }

        $tipo_id         = (int)$this->post('tipo_id', 0);
        $asignado_a_id   = (int)$this->post('asignado_a_id', 0);
        $fecha           = trim($this->post('fecha', date('Y-m-d')));
        $turno           = trim($this->post('turno', ''));
        $observaciones   = trim($this->post('observaciones', ''));

        if ($tipo_id <= 0 || $asignado_a_id <= 0) {
            $this->redirectWithError('/checklists/asignar', 'Debes seleccionar tipo y técnico');
            return;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $this->redirectWithError('/checklists/asignar', 'Fecha inválida');
            return;
        }

        $tipo = $this->model->obtenerTipoPorId($tipo_id);
        if ($tipo && !empty($tipo['requiere_turno']) && empty($turno)) {
            $this->redirectWithError('/checklists/asignar', 'Este tipo requiere que indiques el turno');
            return;
        }

        try {
            $registro = $this->model->asignarChecklist(
                $tipo_id,
                $asignado_a_id,
                $fecha,
                !empty($turno) ? $turno : null,
                $this->authHelper->getUserId(),
                $observaciones
            );

            if (!$registro) {
                throw new \RuntimeException($this->model->getLastError() ?: 'No se pudo asignar el checklist');
            }

            try {
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $this->authHelper->getUserId(),
                    $_SESSION['nombre'] ?? 'Usuario',
                    $rol,
                    'asignar_checklist',
                    'checklist_registros',
                    $registro['id'],
                    null,
                    [
                        'tipo_id' => $tipo_id,
                        'asignado_a_id' => $asignado_a_id,
                        'fecha' => $fecha,
                        'turno' => $turno
                    ]
                );
            } catch (Throwable $e) {
                error_log("Error auditoría asignar checklist: " . $e->getMessage());
            }

            $this->redirectWithSuccess(
                '/checklists/historial',
                'Checklist asignado a ' . ($registro['asignado_nombre'] ?? 'técnico')
            );
            return;

        } catch (Throwable $e) {
            error_log("Error en ChecklistEjecucionController::guardarAsignacion: " . $e->getMessage());
            $this->redirectWithError('/checklists/asignar', 'Error al asignar el checklist');
            return;
        }
    }

    /**
     * Llenar un registro
     * URL: /checklists/llenar/{registro_id}
     */
    public function llenar($registro_id)
    {
        $registro_id = (int)$registro_id;
        if ($registro_id <= 0) {
            $this->redirectWithError('/checklists', 'Registro inválido');
            return;
        }

        try {
            $registro = $this->model->obtenerRegistro($registro_id);
            if (!$registro) {
                $this->redirectWithError('/checklists/mis-checklists', 'Registro no encontrado');
                return;
            }

            $puede_editar = ($registro['estado'] === 'BORRADOR');

            $matriz = [];
            foreach ($registro['lecturas'] as $lec) {
                $key = $lec['seccion_id'] . '_' . ($lec['id_equipo'] ?? 0) . '_'
                     . ($lec['hora'] ?? '') . '_' . ($lec['campo_id'] ?? 0)
                     . '_' . ($lec['batch_num'] ?? 0);
                $matriz[$key] = $lec;
            }
            $registro['matriz'] = $matriz;

        } catch (Throwable $e) {
            error_log("Error en ChecklistEjecucionController::llenar: " . $e->getMessage());
            $this->redirectWithError('/checklists/mis-checklists', 'Error al cargar el registro');
            return;
        }

        $titulo = 'Llenar: ' . $registro['tipo_nombre'];
        $seccion = 'checklists';
        require_once __DIR__ . '/../views/checklists/llenar.php';
    }

    /**
     * Guardar una lectura (AJAX)
     * URL: /checklists/guardar-lectura (POST)
     */
    public function guardarLectura()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
            exit;
        }

        $token = $_POST['csrf_token'] ?? '';
        if (!SecurityHelper::verifyCSRFToken($token)) {
            echo json_encode(['success' => false, 'mensaje' => 'Token de seguridad inválido']);
            exit;
        }

        try {
            $resultado = $this->model->guardarLectura([
                'registro_id' => (int)($_POST['registro_id'] ?? 0),
                'seccion_id'  => (int)($_POST['seccion_id'] ?? 0),
                'id_equipo'   => !empty($_POST['id_equipo']) ? (int)$_POST['id_equipo'] : null,
                'hora'        => $_POST['hora'] ?? null,
                'campo_id'    => !empty($_POST['campo_id']) ? (int)$_POST['campo_id'] : null,
                'batch_num'   => !empty($_POST['batch_num']) ? (int)$_POST['batch_num'] : null,
                'valor'       => $_POST['valor'] ?? null,
                'observacion' => $_POST['observacion'] ?? '',
                'usuario_id'  => $this->authHelper->getUserId(),
            ]);

            if ($resultado === false) {
                echo json_encode([
                    'success' => false,
                    'mensaje' => $this->model->getLastError() ?: 'Error al guardar',
                ]);
                exit;
            }

            echo json_encode([
                'success'        => true,
                'nivel_alerta'   => $resultado['nivel_alerta'],
                'fuera_de_rango' => $resultado['fuera_de_rango'],
            ]);

        } catch (Throwable $e) {
            error_log("Error en ChecklistEjecucionController::guardarLectura: " . $e->getMessage());
            echo json_encode(['success' => false, 'mensaje' => 'Error al procesar la solicitud']);
        }
        exit;
    }

    /**
     * Cerrar un registro
     * URL: /checklists/cerrar/{registro_id} (POST)
     */
    public function cerrar($registro_id)
    {
        $this->requirePost();

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $this->redirectWithError('/checklists/llenar/' . $registro_id, 'Token de seguridad inválido');
            return;
        }

        $registro_id = (int)$registro_id;
        if ($registro_id <= 0) {
            $this->redirectWithError('/checklists', 'Registro inválido');
            return;
        }

        try {
            $resultado = $this->model->cerrarRegistro($registro_id, $this->authHelper->getUserId());

            if (!$resultado) {
                throw new \RuntimeException($this->model->getLastError() ?: 'No se pudo cerrar');
            }

            try {
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $this->authHelper->getUserId(),
                    $_SESSION['nombre'] ?? 'Usuario',
                    $_SESSION['rol'] ?? '',
                    'cerrar_checklist',
                    'checklist_registros',
                    $registro_id
                );
            } catch (Throwable $e) {
                error_log("Error auditoría cerrar checklist: " . $e->getMessage());
            }

            $this->redirectWithSuccess(
                '/checklists/firmar/' . $registro_id,
                'Registro cerrado. Ahora firmalo.'
            );
            return;

        } catch (Throwable $e) {
            error_log("Error en ChecklistEjecucionController::cerrar: " . $e->getMessage());
            $this->redirectWithError('/checklists/llenar/' . $registro_id, 'Error al cerrar el registro');
            return;
        }
    }

    /**
     * Omitir un checklist vencido
     * URL: /checklists/omitir (POST)
     * Solo admin/supervisor
     */
    public function omitir()
    {
        $this->requirePost();

        $rol = $this->authHelper->getRole();
        if (!in_array($rol, ['admin', 'supervisor'], true)) {
            $this->redirectWithError('/checklists/mis-checklists', 'No tienes permisos para omitir');
            return;
        }

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $this->redirectWithError('/checklists/mis-checklists', 'Token de seguridad inválido');
            return;
        }

        $registro_id = (int)$this->post('registro_id', 0);
        $motivo      = trim($this->post('motivo', ''));

        if ($registro_id <= 0) {
            $this->redirectWithError('/checklists/mis-checklists', 'Registro inválido');
            return;
        }

        if (empty($motivo)) {
            $this->redirectWithError('/checklists/mis-checklists', 'Debes indicar el motivo');
            return;
        }

        try {
            // Validar que el registro esté en BORRADOR
            $stmt = $this->db->prepare("SELECT estado FROM checklist_registros WHERE id = ?");
            $stmt->execute([$registro_id]);
            $estado = $stmt->fetchColumn();

            if ($estado !== 'BORRADOR') {
                $this->redirectWithError('/checklists/mis-checklists', 'Solo se pueden omitir registros en borrador');
                return;
            }

            $sql = "UPDATE checklist_registros 
                    SET estado = 'OMITIDO',
                        observaciones = CONCAT(COALESCE(observaciones, ''), ?),
                        fecha_actualizacion = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(["\n[OMITIDO por {$rol}] " . $motivo, $registro_id]);

            try {
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $this->authHelper->getUserId(),
                    $_SESSION['nombre'] ?? 'Usuario',
                    $rol,
                    'omitir_checklist',
                    'checklist_registros',
                    $registro_id,
                    null,
                    ['motivo' => $motivo]
                );
            } catch (Throwable $e) {
                error_log("Error auditoría omitir: " . $e->getMessage());
            }

            $this->redirectWithSuccess('/checklists/mis-checklists', 'Checklist marcado como OMITIDO');
            return;

        } catch (Throwable $e) {
            error_log("Error en ChecklistEjecucionController::omitir: " . $e->getMessage());
            $this->redirectWithError('/checklists/mis-checklists', 'Error al omitir el checklist');
            return;
        }
    }

    /**
     * Panel de firmas
     * URL: /checklists/firmar/{registro_id}
     */
    public function firmar($registro_id)
    {
        $registro_id = (int)$registro_id;

        try {
            $registro = $this->model->obtenerRegistro($registro_id);
            if (!$registro) {
                $this->redirectWithError('/checklists/mis-checklists', 'Registro no encontrado');
                return;
            }

            $paso_actual = $this->model->obtenerPasoActual($registro_id);
            $rol = $this->authHelper->getRole();

            $puede_firmar = false;
            if ($paso_actual && $paso_actual['rol_firmante'] === $rol) {
                if (!empty($paso_actual['turno_firma'])) {
                    $turno_actual = $this->model->detectarTurnoFirma();
                    $puede_firmar = ($paso_actual['turno_firma'] === $turno_actual);
                } else {
                    $puede_firmar = true;
                }
            }

        } catch (Throwable $e) {
            error_log("Error en ChecklistEjecucionController::firmar: " . $e->getMessage());
            $this->redirectWithError('/checklists/mis-checklists', 'Error al cargar el registro');
            return;
        }

        $titulo = 'Firmar: ' . $registro['tipo_nombre'];
        $seccion = 'checklists';
        require_once __DIR__ . '/../views/checklists/firmar.php';
    }

    /**
     * Procesar firma
     * URL: /checklists/procesar-firma (POST)
     */
    public function procesarFirma()
    {
        $this->requirePost();

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $this->redirectWithError('/checklists', 'Token de seguridad inválido');
            return;
        }

        $registro_id = (int)$this->post('registro_id', 0);
        $paso_id     = (int)$this->post('paso_id', 0);
        $firma_svg   = $this->post('firma_svg', '');
        $comentario  = $this->post('comentario', '');

        if ($registro_id <= 0 || $paso_id <= 0) {
            $this->redirectWithError('/checklists', 'Datos incompletos');
            return;
        }

        try {
            $paso_actual = $this->model->obtenerPasoActual($registro_id);
            $rol = $this->authHelper->getRole();

            if (!$paso_actual) {
                $this->redirectWithError('/checklists/firmar/' . $registro_id, 'No hay pasos pendientes');
                return;
            }
            if ((int)$paso_actual['id'] !== $paso_id) {
                $this->redirectWithError('/checklists/firmar/' . $registro_id, 'Paso inválido');
                return;
            }
            if ($paso_actual['rol_firmante'] !== $rol) {
                $this->redirectWithError('/checklists/firmar/' . $registro_id, 'No tienes el rol requerido');
                return;
            }
            if (!empty($paso_actual['turno_firma'])) {
                $turno_actual = $this->model->detectarTurnoFirma();
                if ($paso_actual['turno_firma'] !== $turno_actual) {
                    $this->redirectWithError('/checklists/firmar/' . $registro_id,
                        'Este paso corresponde al turno ' . $paso_actual['turno_firma']);
                    return;
                }
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
                        $rol,
                        'firmar_checklist',
                        'checklist_firmas',
                        $paso_id,
                        null,
                        ['registro_id' => $registro_id, 'paso' => $paso_actual['paso']]
                    );
                } catch (Throwable $e) {
                    error_log("Error auditoría firma: " . $e->getMessage());
                }

                $msg = $resultado['completada']
                    ? '¡Firmas completadas! El checklist está aprobado.'
                    : 'Firma registrada. Siguiente paso: ' . ($resultado['siguiente_paso'] ?? 'N/A');

                $this->redirectWithSuccess('/checklists/ver/' . $registro_id, $msg);
                return;
            } else {
                $this->redirectWithError('/checklists/firmar/' . $registro_id, $resultado['mensaje']);
                return;
            }

        } catch (Throwable $e) {
            error_log("Error en ChecklistEjecucionController::procesarFirma: " . $e->getMessage());
            $this->redirectWithError('/checklists/firmar/' . $registro_id, 'Error al procesar la firma');
            return;
        }
    }

    /**
     * Rechazar firma
     * URL: /checklists/rechazar (POST)
     */
    public function rechazar()
    {
        $this->requirePost();

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $this->redirectWithError('/checklists', 'Token de seguridad inválido');
            return;
        }

        $registro_id = (int)$this->post('registro_id', 0);
        $paso_id     = (int)$this->post('paso_id', 0);
        $motivo      = trim($this->post('motivo', ''));

        if (empty($motivo)) {
            $this->redirectWithError('/checklists/firmar/' . $registro_id, 'Debes indicar el motivo');
            return;
        }

        try {
            $resultado = $this->model->rechazar(
                $registro_id,
                $paso_id,
                $this->authHelper->getUserId(),
                $_SESSION['nombre'] ?? 'Usuario',
                $motivo
            );

            if ($resultado['success']) {
                $this->redirectWithWarning('/checklists/ver/' . $registro_id, 'Registro rechazado');
                return;
            } else {
                $this->redirectWithError('/checklists/firmar/' . $registro_id, $resultado['mensaje']);
                return;
            }

        } catch (Throwable $e) {
            error_log("Error en ChecklistEjecucionController::rechazar: " . $e->getMessage());
            $this->redirectWithError('/checklists/firmar/' . $registro_id, 'Error al rechazar el registro');
            return;
        }
    }

    /**
     * Ver un registro
     * URL: /checklists/ver/{registro_id}
     */
    public function ver($registro_id)
    {
        $registro_id = (int)$registro_id;

        try {
            $registro = $this->model->obtenerRegistro($registro_id);
            if (!$registro) {
                $this->redirectWithError('/checklists/mis-checklists', 'Registro no encontrado');
                return;
            }

            $matriz = [];
            foreach ($registro['lecturas'] as $lec) {
                $key = $lec['seccion_id'] . '_' . ($lec['id_equipo'] ?? 0) . '_'
                     . ($lec['hora'] ?? '') . '_' . ($lec['campo_id'] ?? 0)
                     . '_' . ($lec['batch_num'] ?? 0);
                $matriz[$key] = $lec;
            }
            $registro['matriz'] = $matriz;

        } catch (Throwable $e) {
            error_log("Error en ChecklistEjecucionController::ver: " . $e->getMessage());
            $this->redirectWithError('/checklists/mis-checklists', 'Error al cargar el registro');
            return;
        }

        $titulo = 'Ver: ' . $registro['tipo_nombre'];
        $seccion = 'checklists';
        require_once __DIR__ . '/../views/checklists/ver_registro.php';
    }

    /**
     * Historial de registros
     * URL: /checklists/historial
     */
    public function historial()
    {
        try {
            $filtros = [
                'tipo_id'     => $this->get('tipo_id', ''),
                'fecha_desde' => $this->get('fecha_desde', date('Y-m-01')),
                'fecha_hasta' => $this->get('fecha_hasta', date('Y-m-d')),
                'turno'       => $this->get('turno', ''),
                'estado'      => $this->get('estado', ''),
            ];

            $registros = $this->model->obtenerRegistros($filtros);
            $tipos = $this->model->obtenerTipos(['activo' => 1]);

        } catch (Throwable $e) {
            error_log("Error en ChecklistEjecucionController::historial: " . $e->getMessage());
            $registros = [];
            $tipos = [];
            $_SESSION['error'] = 'Error al cargar el historial';
        }

        $titulo = 'Historial de Checklists';
        $seccion = 'checklists';
        require_once __DIR__ . '/../views/checklists/historial.php';
    }

    /**
     * Reporte imprimible
     * URL: /checklists/reporte/{registro_id}
     */
    public function reporte($registro_id)
    {
        $registro_id = (int)$registro_id;

        try {
            $registro = $this->model->obtenerRegistro($registro_id);
            if (!$registro) {
                $this->redirectWithError('/checklists/historial', 'Registro no encontrado');
                return;
            }

            $matriz = [];
            foreach ($registro['lecturas'] as $lec) {
                $key = $lec['seccion_id'] . '_' . ($lec['id_equipo'] ?? 0) . '_'
                     . ($lec['hora'] ?? '') . '_' . ($lec['campo_id'] ?? 0)
                     . '_' . ($lec['batch_num'] ?? 0);
                $matriz[$key] = $lec;
            }
            $registro['matriz'] = $matriz;

        } catch (Throwable $e) {
            error_log("Error en ChecklistEjecucionController::reporte: " . $e->getMessage());
            $this->redirectWithError('/checklists/historial', 'Error al cargar el reporte');
            return;
        }

        $titulo = 'Reporte';
        require_once __DIR__ . '/../views/checklists/reporte.php';
    }
}