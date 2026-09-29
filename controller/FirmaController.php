<?php
// controller/FirmaController.php
// Controlador para el sistema de firmas secuenciales
// ✅ FIX: pasar validaciones de rol antes de firmar
// ✅ FIX: catch (Throwable) en vez de Exception

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../model/FirmaModel.php';
require_once __DIR__ . '/../model/OrdenTrabajo.php';
require_once __DIR__ . '/../helpers/FirmaSecuenciaHelper.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';
require_once __DIR__ . '/../config/database.php';

class FirmaController extends Controller {

    private $firmaModel;
    private $ordenModel;

    public function __construct() {
        parent::__construct();

        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
            exit;
        }

        $this->firmaModel = new FirmaModel();
        $this->ordenModel = new OrdenTrabajo();
    }

    public function orden($orden_id = null) {
        $orden_id = (int)$orden_id;

        if ($orden_id <= 0) {
            $this->redirectWithError('/ordenes', 'ID de orden inválido');
            return;
        }

        $orden = $this->ordenModel->obtenerPorId($orden_id);
        if (!$orden) {
            $this->redirectWithError('/ordenes', 'Orden no encontrada');
            return;
        }

        $firmas_secuenciales = [];
        $paso_actual_firma = null;
        $resumen = [
            'total' => 0, 'firmados' => 0, 'pendientes' => 0,
            'rechazados' => 0, 'omitidos' => 0, 'completa' => false, 'porcentaje' => 0
        ];
        $puede_firmar_ahora = false;

        try {
            $this->firmaModel->inicializarFirmas($orden_id);

            $firmas_secuenciales = $this->firmaModel->obtenerFirmasPorOrden($orden_id);
            $paso_actual_firma = $this->firmaModel->obtenerPasoActual($orden_id);
            $resumen = $this->firmaModel->obtenerResumen($orden_id);

            $resumen = array_merge([
                'total' => 0, 'firmados' => 0, 'pendientes' => 0,
                'rechazados' => 0, 'omitidos' => 0, 'completa' => false, 'porcentaje' => 0
            ], $resumen);

            $rol = $this->authHelper->getRole();
            if ($paso_actual_firma && $paso_actual_firma['rol_firmante'] === $rol) {
                $puede_firmar_ahora = true;
            }

        } catch (Throwable $e) {
            error_log("Error en FirmaController::orden - " . $e->getMessage());
        }

        $firmas = $firmas_secuenciales;
        $pasoActual = $paso_actual_firma;
        $puedeFirmar = $puede_firmar_ahora;
        $pasoFirmable = $paso_actual_firma;

        $titulo = 'Firmas de Orden ' . ($orden['num_om'] ?? '');
        $seccion = 'ordenes';

        require_once __DIR__ . '/../views/firmas/orden.php';
    }

    public function firmar($orden_id = null, $paso_id = null) {
        $orden_id = (int)$orden_id;
        $paso_id = (int)$paso_id;

        if ($orden_id <= 0) {
            $this->redirectWithError('/ordenes', 'ID de orden inválido');
            return;
        }

        $orden = $this->ordenModel->obtenerPorId($orden_id);
        if (!$orden) {
            $this->redirectWithError('/ordenes', 'Orden no encontrada');
            return;
        }

        if ($paso_id <= 0) {
            $pasoActualTemp = $this->firmaModel->obtenerPasoActual($orden_id);
            if (!$pasoActualTemp) {
                $this->redirectWithError(
                    '/firmas/orden/' . $orden_id,
                    'No hay pasos pendientes para firmar'
                );
                return;
            }
            $paso_id = (int)$pasoActualTemp['id'];
        }

        $pasoActual = $this->firmaModel->obtenerPasoActual($orden_id);

        if (!$pasoActual || (int)$pasoActual['id'] !== $paso_id) {
            $this->redirectWithError(
                '/firmas/orden/' . $orden_id, 
                'Este paso ya no está disponible para firmar'
            );
            return;
        }

        $rol = $this->authHelper->getRole();
        if ($pasoActual['rol_firmante'] !== $rol) {
            $this->redirectWithError(
                '/firmas/orden/' . $orden_id, 
                'No tienes el rol requerido para este paso'
            );
            return;
        }

        $paso_actual = $pasoActual;

        $titulo = 'Firmar Paso ' . $pasoActual['paso'];
        $seccion = 'ordenes';

        require_once __DIR__ . '/../views/firmas/firmar.php';
    }

    public function procesar() {
        $this->requirePost();

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $this->redirectWithError('/ordenes', 'Token de seguridad inválido');
            return;
        }

        $orden_id = (int)$this->post('orden_id', 0);
        $paso_id = (int)$this->post('paso_id', 0);
        $firma_svg = $this->post('firma_svg', '');
        $comentario = $this->post('comentario', '');

        if ($orden_id <= 0 || $paso_id <= 0) {
            $this->redirectWithError('/ordenes', 'Datos incompletos');
            return;
        }

        if (empty($firma_svg)) {
            $this->redirectWithError(
                '/firmas/firmar/' . $orden_id . '/' . $paso_id,
                'Debes dibujar tu firma antes de continuar'
            );
            return;
        }

        if (strpos($firma_svg, '<svg') === false || strpos($firma_svg, '</svg>') === false) {
            $this->redirectWithError(
                '/firmas/firmar/' . $orden_id . '/' . $paso_id,
                'La firma no tiene el formato correcto (SVG)'
            );
            return;
        }

        if (strlen($firma_svg) > 204800) {
            $this->redirectWithError(
                '/firmas/firmar/' . $orden_id . '/' . $paso_id,
                'La firma es demasiado grande (máx 200 KB)'
            );
            return;
        }

        $paso_actual = $this->firmaModel->obtenerPasoActual($orden_id);
        $rol_usuario = $this->authHelper->getRole();

        if (!$paso_actual) {
            $this->redirectWithError('/firmas/orden/' . $orden_id, 'No hay pasos pendientes');
            return;
        }

        if ((int)$paso_actual['id'] !== $paso_id) {
            $this->redirectWithError('/firmas/orden/' . $orden_id, 'Este paso ya no está disponible');
            return;
        }

        if ($paso_actual['rol_firmante'] !== $rol_usuario) {
            $this->redirectWithError(
                '/firmas/orden/' . $orden_id, 
                'No tienes el rol requerido para este paso'
            );
            return;
        }

        $usuario_id = $this->authHelper->getUserId();
        $usuario_nombre = $_SESSION['nombre'] ?? 'Usuario';

        $resultado = $this->firmaModel->firmar(
            $orden_id,
            $paso_id,
            $usuario_id,
            $usuario_nombre,
            $firma_svg,
            $comentario
        );

        if ($resultado['success']) {
            try {
                require_once __DIR__ . '/../model/AuditoriaModel.php';
                $auditoria = new AuditoriaModel();
                $auditoria->registrar(
                    $usuario_id,
                    $usuario_nombre,
                    $_SESSION['rol'] ?? '',
                    'firmar_orden',
                    'firmas_orden',
                    $paso_id,
                    null,
                    ['orden_id' => $orden_id, 'paso' => $paso_id]
                );
            } catch (Throwable $e) {
                error_log("Error auditoría: " . $e->getMessage());
            }

            $mensaje = $resultado['completada'] 
                ? '¡Todas las firmas completadas! La orden ha sido aprobada.'
                : 'Firma registrada. Siguiente paso: ' . $resultado['siguiente_paso'];

            $this->redirectWithSuccess('/firmas/orden/' . $orden_id, $mensaje);
        } else {
            $this->redirectWithError(
                '/firmas/firmar/' . $orden_id . '/' . $paso_id,
                $resultado['mensaje']
            );
        }
    }

    public function rechazar() {
        $this->requirePost();

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $this->redirectWithError('/ordenes', 'Token de seguridad inválido');
            return;
        }

        $orden_id = (int)$this->post('orden_id', 0);
        $paso_id = (int)$this->post('paso_id', 0);
        $motivo = trim($this->post('motivo', ''));

        if (empty($motivo)) {
            $this->redirectWithError(
                '/firmas/firmar/' . $orden_id . '/' . $paso_id,
                'Debes indicar el motivo del rechazo'
            );
            return;
        }

        $usuario_id = $this->authHelper->getUserId();
        $usuario_nombre = $_SESSION['nombre'] ?? 'Usuario';

        $resultado = $this->firmaModel->rechazar(
            $orden_id,
            $paso_id,
            $usuario_id,
            $usuario_nombre,
            $motivo
        );

        if ($resultado['success']) {
            $this->redirectWithWarning(
                '/firmas/orden/' . $orden_id,
                'La orden ha sido rechazada en este paso'
            );
        } else {
            $this->redirectWithError(
                '/firmas/firmar/' . $orden_id . '/' . $paso_id,
                $resultado['mensaje']
            );
        }
    }

    public function pendientes() {
        $rol = $this->authHelper->getRole();
        $pendientes = $this->firmaModel->obtenerPendientesPorRol($rol);

        $titulo = 'Firmas Pendientes';
        $seccion = 'firmas';

        require_once __DIR__ . '/../views/firmas/pendientes.php';
    }

    public function configuracion() {
        if (!$this->authHelper->isAdmin()) {
            $this->redirectWithError('/dashboard', 'No tienes permisos');
            return;
        }

        $secuencia = FirmaSecuenciaHelper::obtenerSecuencia();

        $titulo = 'Configuración de Secuencia de Firmas';
        $seccion = 'firmas';

        require_once __DIR__ . '/../views/firmas/configuracion.php';
    }

    public function guardarConfiguracion() {
        if (!$this->authHelper->isAdmin()) {
            $this->redirectWithError('/dashboard', 'No tienes permisos');
            return;
        }

        $this->requirePost();

        $token = $this->post('csrf_token', '');
        if (!SecurityHelper::verifyCSRFToken($token)) {
            $this->redirectWithError('/firmas/configuracion', 'Token inválido');
            return;
        }

        $roles = $this->post('rol_firmante', []);
        $nombres = $this->post('nombre_paso', []);
        $obligatorios = $this->post('obligatorio', []);
        $activos = $this->post('activo', []);

        $pasos = [];
        foreach ($roles as $paso => $rol) {
            $pasos[] = [
                'paso' => (int)$paso,
                'rol_firmante' => $rol,
                'nombre_paso' => $nombres[$paso] ?? '',
                'obligatorio' => isset($obligatorios[$paso]) ? 1 : 0,
                'activo' => isset($activos[$paso]) ? 1 : 0
            ];
        }

        if ($this->firmaModel->actualizarSecuencia($pasos)) {
            $this->redirectWithSuccess('/firmas/configuracion', 'Configuración guardada');
        } else {
            $this->redirectWithError('/firmas/configuracion', 'Error al guardar');
        }
    }

    public function todas() {
        if (!$this->authHelper->isAdmin()) {
            $this->redirectWithError('/dashboard', 'No tienes permisos');
            return;
        }

        $firmas = [];

        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT o.id, o.num_om, o.titulo, o.status, o.firma_paso_actual, o.firma_estado,
                           f.paso, f.rol_firmante, f.estado as firma_estado, f.fecha_creacion
                    FROM ordenes_mantenimiento o
                    INNER JOIN firmas_orden f ON o.id = f.orden_id
                    WHERE o.firma_estado IN ('PENDIENTE', 'EN_PROCESO')
                    AND f.estado = 'PENDIENTE'
                    AND f.paso = o.firma_paso_actual
                    ORDER BY o.fecha_creacion ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $firmas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log("Error en FirmaController::todas - " . $e->getMessage());
        }

        $titulo = 'Todas las Firmas Pendientes';
        $seccion = 'firmas';

        require_once __DIR__ . '/../views/firmas/todas.php';
    }
}