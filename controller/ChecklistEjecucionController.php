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
     */
    public function misChecklists()
    {
        $rol = $this->authHelper->getRole();

        try {
            $tipos = $this->model->obtenerTiposPorRol($rol);
            $fecha = $this->get('fecha', date('Y-m-d'));

            // Para cada tipo, buscar si ya existe un registro hoy
            foreach ($tipos as &$tipo) {
                $turno = $tipo['requiere_turno'] ? $this->model->detectarTurnoActual() : null;
                $tipo['registro_hoy'] = $this->model->obtenerRegistroPorTipoFechaTurno(
                    $tipo['id'], $fecha, $turno
                );
            }
            unset($tipo);

        } catch (Throwable $e) {
            error_log("Error en ChecklistEjecucionController::misChecklists: " . $e->getMessage());
            $tipos = [];
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
            $_SESSION['error'] = 'Tipo inválido';
            $this->redirect('/checklists/mis-checklists');
        }

        $tipo = $this->model->obtenerTipoPorId($tipo_id);
        if (!$tipo) {
            $_SESSION['error'] = 'Checklist no encontrado';
            $this->redirect('/checklists/mis-checklists');
        }

        $fecha = $this->get('fecha', date('Y-m-d'));
        $turno = $tipo['requiere_turno'] ? $this->get('turno', $this->model->detectarTurnoActual()) : null;

        try {
            $registro = $this->model->iniciarRegistro(
                $tipo_id, $fecha, $turno, $this->authHelper->getUserId()
            );

            if (!$registro) {
                throw new Exception($this->model->getLastError() ?: 'No se pudo iniciar el registro');
            }

            $this->redirect('/checklists/llenar/' . $registro['id']);

        } catch (Throwable $e) {
            error_log("Error en ChecklistEjecucionController::iniciar: " . $e->getMessage());
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
            $this->redirect('/checklists/mis-checklists');
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
            $_SESSION['error'] = 'Registro inválido';
            $this->redirect('/checklists');
        }

        try {
            $registro = $this->model->obtenerRegistro($registro_id);
            if (!$registro) {
                $_SESSION['error'] = 'Registro no encontrado';
                $this->redirect('/checklists/mis-checklists');
            }

            $puede_editar = ($registro['estado'] === 'BORRADOR');

            // Organizar lecturas en matriz
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
            $_SESSION['error'] = 'Error al cargar el registro';
            $this->redirect('/checklists/mis-checklists');
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
            echo json_encode(['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
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
            $_SESSION['error'] = 'Token de seguridad inválido';
            $this->redirect('/checklists/llenar/' . $registro_id);
        }

        $registro_id = (int)$registro_id;

        try {
            $resultado = $this->model->cerrarRegistro($registro_id, $this->authHelper->getUserId());

            if (!$resultado) {
                throw new Exception($this->model->getLastError() ?: 'No se pudo cerrar');
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

        } catch (Throwable $e) {
            error_log("Error en ChecklistEjecucionController::cerrar: " . $e->getMessage());
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
            $this->redirect('/checklists/llenar/' . $registro_id);
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
                $_SESSION['error'] = 'Registro no encontrado';
                $this->redirect('/checklists/mis-checklists');
            }

            $paso_actual = $this->model->obtenerPasoActual($registro_id);
            $rol = $this->authHelper->getRole();

            $puede_firmar = false;
            if ($paso_actual && $paso_actual['rol_firmante'] === $rol) {
                // Si el paso tiene turno_firma, validar que coincida con el turno actual
                if (!empty($paso_actual['turno_firma'])) {
                    $turno_actual = $this->model->detectarTurnoFirma();
                    $puede_firmar = ($paso_actual['turno_firma'] === $turno_actual);
                } else {
                    $puede_firmar = true;
                }
            }

        } catch (Throwable $e) {
            error_log("Error en ChecklistEjecucionController::firmar: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar el registro';
            $this->redirect('/checklists/mis-checklists');
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
        }

        $registro_id = (int)$this->post('registro_id', 0);
        $paso_id     = (int)$this->post('paso_id', 0);
        $firma_svg   = $this->post('firma_svg', '');
        $comentario  = $this->post('comentario', '');

        if ($registro_id <= 0 || $paso_id <= 0) {
            $this->redirectWithError('/checklists', 'Datos incompletos');
        }

        // Validar paso
        $paso_actual = $this->model->obtenerPasoActual($registro_id);
        $rol = $this->authHelper->getRole();

        if (!$paso_actual) {
            $this->redirectWithError('/checklists/firmar/' . $registro_id, 'No hay pasos pendientes');
        }
        if ((int)$paso_actual['id'] !== $paso_id) {
            $this->redirectWithError('/checklists/firmar/' . $registro_id, 'Paso inválido');
        }
        if ($paso_actual['rol_firmante'] !== $rol) {
            $this->redirectWithError('/checklists/firmar/' . $registro_id, 'No tienes el rol requerido');
        }
        if (!empty($paso_actual['turno_firma'])) {
            $turno_actual = $this->model->detectarTurnoFirma();
            if ($paso_actual['turno_firma'] !== $turno_actual) {
                $this->redirectWithError('/checklists/firmar/' . $registro_id,
                    'Este paso corresponde al turno ' . $paso_actual['turno_firma']);
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
        } else {
            $this->redirectWithError('/checklists/firmar/' . $registro_id, $resultado['mensaje']);
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
        }

        $registro_id = (int)$this->post('registro_id', 0);
        $paso_id     = (int)$this->post('paso_id', 0);
        $motivo      = trim($this->post('motivo', ''));

        if (empty($motivo)) {
            $this->redirectWithError('/checklists/firmar/' . $registro_id, 'Debes indicar el motivo');
        }

        $resultado = $this->model->rechazar(
            $registro_id,
            $paso_id,
            $this->authHelper->getUserId(),
            $_SESSION['nombre'] ?? 'Usuario',
            $motivo
        );

        if ($resultado['success']) {
            $this->redirectWithWarning('/checklists/ver/' . $registro_id, 'Registro rechazado');
        } else {
            $this->redirectWithError('/checklists/firmar/' . $registro_id, $resultado['mensaje']);
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
                $_SESSION['error'] = 'Registro no encontrado';
                $this->redirect('/checklists/mis-checklists');
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
            $_SESSION['error'] = 'Error al cargar el registro';
            $this->redirect('/checklists/mis-checklists');
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
                $_SESSION['error'] = 'Registro no encontrado';
                $this->redirect('/checklists/historial');
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
            $_SESSION['error'] = 'Error al cargar el reporte';
            $this->redirect('/checklists/historial');
        }

        $titulo = 'Reporte';
        require_once __DIR__ . '/../views/checklists/reporte.php';
    }
}
?>
