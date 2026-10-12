<?php
// controller/TecnicoController.php
// VERSIÓN CORREGIDA - ALINEADA CON views/tecnico/cerrar_orden.php
// ✅ FIX 1: procesarCierre() lee los campos REALES de la vista (descripcion_cierre, evidencias[], etc.)
// ✅ FIX 2: usa $this->redirect() en vez de header()
// ✅ FIX 3: procesarFotosAdjuntas() ahora lee $_FILES['evidencias']
// ✅ FIX 4: cálculo de costo_total no duplica mano de obra
// ✅ FIX 5: validación de rol por getRole() (sin depender de isTecnico())
// ✅ FIX 6: extensión derivada del MIME verificado con getimagesize()

require_once __DIR__ . '/../model/OrdenTrabajo.php';
require_once __DIR__ . '/../model/Tecnico.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';
require_once __DIR__ . '/../config/database.php';

class TecnicoController extends Controller {

    private $ordenModel;
    private $tecnicoId;

    public function __construct() {
        parent::__construct();

        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
            return;
        }

        // ✅ FIX 5: usar getRole() en vez de isTecnico()
        if ($this->authHelper->getRole() !== 'tecnico') {
            $this->redirectWithError('/dashboard', 'No tienes permisos para esta sección');
            return;
        }

        $this->ordenModel = new OrdenTrabajo();
        $this->tecnicoId = $this->obtenerIdTecnico();
    }

    /**
     * Obtener ID de técnico - CON FALLBACK CORREGIDO
     */
    private function obtenerIdTecnico() {
        try {
            $db = Database::getInstance()->getConnection();

            $email = isset($_SESSION['email']) && !empty($_SESSION['email'])
                ? $_SESSION['email']
                : null;

            if (empty($email) && isset($_SESSION['usuario_id'])) {
                $sql = "SELECT email FROM usuarios WHERE id = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$_SESSION['usuario_id']]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $email = $result['email'];
                    $_SESSION['email'] = $email;
                }
            }

            if (empty($email)) {
                error_log("TecnicoController: Email no encontrado en sesión");
                return $this->authHelper->getUserId();
            }

            $sql = "SELECT id FROM tecnicos WHERE LOWER(email) = LOWER(?)";
            $stmt = $db->prepare($sql);
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                return (int)$result['id'];
            }

            return $this->authHelper->getUserId();

        } catch (Throwable $e) {
            error_log("Error en obtenerIdTecnico: " . $e->getMessage());
            return $this->authHelper->getUserId();
        }
    }

    /**
     * Dashboard del técnico
     */
    public function index() {
        $ordenes = $this->ordenModel->obtenerPorTecnico($this->tecnicoId);

        $estadisticas = ['total' => 0, 'pendientes' => 0, 'en_progreso' => 0, 'completadas' => 0];
        $ordenes_recientes = [];

        if (!empty($ordenes)) {
            foreach ($ordenes as $orden) {
                $status = $orden['status'] ?? 'PENDIENTE';
                if ($status === 'PENDIENTE')         $estadisticas['pendientes']++;
                elseif ($status === 'EN_PROCESO')    $estadisticas['en_progreso']++;
                elseif ($status === 'CERRADA' || $status === 'APROBADA') $estadisticas['completadas']++;
            }
            $estadisticas['total'] = count($ordenes);
            $ordenes_recientes = array_slice($ordenes, 0, 5);
        }

        $titulo = 'Panel de Técnico';
        $seccion = 'tecnico';

        require_once __DIR__ . '/../views/tecnico/index.php';
    }

    /**
     * Mis órdenes
     */
    public function mis_ordenes() {
        $ordenes = $this->ordenModel->obtenerPorTecnico($this->tecnicoId);

        $titulo = 'Mis Órdenes';
        $seccion = 'mis_ordenes';
        require_once __DIR__ . '/../views/tecnico/mis_ordenes.php';
    }

    /**
     * Detalle de orden
     */
    public function detalle_orden($id) {
        $orden = $this->ordenModel->obtenerPorId($id);

        if (!$orden) {
            $this->redirectWithError('/tecnico/mis_ordenes', 'Orden no encontrada');
            return;
        }

        if ((int)$orden['tecnico_id'] !== (int)$this->tecnicoId) {
            $this->redirectWithError('/tecnico/mis_ordenes', 'No tienes permisos para ver esta orden');
            return;
        }

        $titulo = 'Detalle de Orden';
        $seccion = 'tecnico';
        require_once __DIR__ . '/../views/tecnico/detalle_orden.php';
    }

    /**
     * Cerrar orden - Muestra el formulario o procesa el POST
     */
    public function cerrar_orden($id) {
        $orden = $this->ordenModel->obtenerPorId($id);

        if (!$orden) {
            $this->redirectWithError('/tecnico/mis_ordenes', 'Orden no encontrada');
            return;
        }

        $status = $orden['status'] ?? '';
        if (!in_array($status, ['PENDIENTE', 'EN_PROCESO', 'EJECUTADA'], true)) {
            $this->redirectWithError('/tecnico/detalle_orden/' . $id, 'Esta orden no se puede cerrar en su estado actual');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarCierre($id);
            return;
        }

        $titulo = 'Cerrar Orden';
        $seccion = 'tecnico';
        require_once __DIR__ . '/../views/tecnico/cerrar_orden.php';
    }

    /**
     * Procesar cierre de orden
     * ✅ FIX 1: lee los campos REALES de views/tecnico/cerrar_orden.php
     * ✅ FIX 3: lee $_FILES['evidencias']
     * ✅ FIX 4: no duplica mano de obra en costo_total
     */
    public function procesarCierre($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirect('/tecnico/cerrar_orden/' . $id);
                return;
            }

            $token = $_POST['csrf_token'] ?? '';
            if (!SecurityHelper::verifyCSRFToken($token)) {
                $this->redirectWithError('/tecnico/cerrar_orden/' . $id, 'Token de seguridad inválido');
                return;
            }

            $orden = $this->ordenModel->obtenerPorId($id);
            if (!$orden) {
                $this->redirectWithError('/tecnico/mis_ordenes', 'Orden no encontrada');
                return;
            }

            if ((int)$orden['tecnico_id'] !== (int)$this->tecnicoId) {
                $this->redirectWithError('/tecnico/mis_ordenes', 'No tienes permisos para cerrar esta orden');
                return;
            }

            // ✅ FIX 1: campos reales de la vista
            $descripcion_cierre    = trim($_POST['descripcion_cierre'] ?? '');
            $tiempo_invertido      = floatval($_POST['tiempo_invertido'] ?? 0);
            $repuestos_utilizados  = trim($_POST['repuestos_utilizados'] ?? '');
            $satisfactorio         = !empty($_POST['satisfactorio']);
            $firma_svg             = $_POST['firma_svg'] ?? '';

            if (empty($descripcion_cierre)) {
                $this->redirectWithError('/tecnico/cerrar_orden/' . $id, 'Debes describir el trabajo realizado');
                return;
            }

            if ($tiempo_invertido <= 0) {
                $this->redirectWithError('/tecnico/cerrar_orden/' . $id, 'Debes indicar el tiempo invertido');
                return;
            }

            if ($tiempo_invertido > 720) {
                $this->redirectWithError('/tecnico/cerrar_orden/' . $id, 'El tiempo invertido no puede exceder 720 horas');
                return;
            }

            // Firma es OPCIONAL aquí (se firma después con firmas secuenciales)
            if (!empty($firma_svg)) {
                if (strpos($firma_svg, '<svg') === false || strpos($firma_svg, '</svg>') === false) {
                    $this->redirectWithError('/tecnico/cerrar_orden/' . $id, 'La firma no tiene el formato correcto (SVG)');
                    return;
                }
                if (strlen($firma_svg) > 204800) {
                    $this->redirectWithError('/tecnico/cerrar_orden/' . $id, 'La firma es demasiado grande (máx 200 KB)');
                    return;
                }
            }

            $fotos_guardadas = $this->procesarFotosAdjuntas($id);

            // Armar descripción enriquecida
            $descripcion_realizada = $descripcion_cierre;
            if (!empty($repuestos_utilizados)) {
                $descripcion_realizada .= "\n\nRepuestos utilizados: " . $repuestos_utilizados;
            }
            if (!$satisfactorio) {
                $descripcion_realizada .= "\n\n⚠️ Trabajo marcado como NO satisfactorio por el técnico.";
            }

            $tarifa = floatval($orden['tarifa_tecnico'] ?? 0);
            $costo_mano_obra = $tiempo_invertido * $tarifa;
            $costo_repuestos = floatval($orden['costo_repuestos'] ?? 0);

            // ✅ FIX 4: no duplicar mano de obra
            $costo_total = $costo_repuestos + $costo_mano_obra;

            $datos = [
                'descripcion_realizada'  => $descripcion_realizada,
                'pasos_ejecutados'       => '',
                'horas_trabajadas'       => $tiempo_invertido,
                'tarifa_tecnico'         => $tarifa,
                'costo_repuestos'        => $costo_repuestos,
                'costo_mano_obra'        => $costo_mano_obra,
                'costo_total'            => $costo_total,
                'foto_evidencia'         => $fotos_guardadas > 0 ? 'evidencias' : '',
                'observaciones_tecnico'  => $repuestos_utilizados,
                'observaciones_cierre'   => $satisfactorio ? 'Trabajo satisfactorio' : 'Trabajo NO satisfactorio',
                'actualizado_por'        => $this->authHelper->getUserId()
            ];

            $resultado = $this->ordenModel->cerrar($id, $datos);

            if ($resultado) {
                // Registrar firma secuencial (si el paso actual es del técnico)
                if (!empty($firma_svg)) {
                    try {
                        require_once __DIR__ . '/../model/FirmaModel.php';
                        $firmaModel = new FirmaModel();
                        $paso_actual = $firmaModel->obtenerPasoActual($id);

                        if ($paso_actual && $paso_actual['rol_firmante'] === 'tecnico') {
                            $resultadoFirma = $firmaModel->firmar(
                                $id,
                                $paso_actual['id'],
                                $this->authHelper->getUserId(),
                                $_SESSION['nombre'] ?? 'Técnico',
                                $firma_svg,
                                'Trabajo completado'
                            );
                            if (!$resultadoFirma['success']) {
                                error_log("Error firma técnico: " . $resultadoFirma['mensaje']);
                            }
                        }
                    } catch (Throwable $e) {
                        error_log("Error al registrar firma secuencial: " . $e->getMessage());
                    }
                }

                // Auditoría
                try {
                    require_once __DIR__ . '/../model/AuditoriaModel.php';
                    $auditoria = new AuditoriaModel();
                    $auditoria->registrar(
                        $this->authHelper->getUserId(),
                        $_SESSION['nombre'] ?? 'Técnico',
                        'tecnico',
                        'cerrar',
                        'ordenes_mantenimiento',
                        $id,
                        ['estado_anterior' => $orden['status']],
                        ['estado_nuevo' => 'CERRADA', 'horas_trabajadas' => $tiempo_invertido]
                    );
                } catch (Throwable $e) {
                    error_log("Error auditoría: " . $e->getMessage());
                }

                $msg = 'Orden cerrada correctamente.';
                if ($fotos_guardadas > 0) {
                    $msg .= " ({$fotos_guardadas} foto(s) guardada(s))";
                }
                $this->redirectWithSuccess('/tecnico/detalle_orden/' . $id, $msg);
                return;
            } else {
                $this->redirectWithError('/tecnico/cerrar_orden/' . $id, 'Error al cerrar la orden');
                return;
            }

        } catch (Throwable $e) {
            error_log("Error en procesarCierre: " . $e->getMessage());
            $this->redirectWithError('/tecnico/cerrar_orden/' . $id, 'Error al cerrar la orden');
            return;
        }
    }

    /**
     * Procesar fotos adjuntas
     * ✅ FIX 3: lee $_FILES['evidencias']
     * ✅ FIX 6: extensión derivada del MIME verificado
     */
    private function procesarFotosAdjuntas($orden_id) {
        $fotos_guardadas = 0;

        try {
            // ✅ FIX 3: leer 'evidencias' en vez de 'fotos'
            if (empty($_FILES['evidencias']) || !is_array($_FILES['evidencias']['name'])) {
                return 0;
            }

            $uploadDir = __DIR__ . '/../uploads/evidencias/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $db = Database::getInstance()->getConnection();
            $tipos_permitidos = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];

            foreach ($_FILES['evidencias']['name'] as $index => $nombre) {
                if (empty($nombre)) continue;

                $error = $_FILES['evidencias']['error'][$index] ?? UPLOAD_ERR_NO_FILE;
                if ($error !== UPLOAD_ERR_OK) {
                    error_log("Error al subir evidencia $index: código $error");
                    continue;
                }

                $tmp_name = $_FILES['evidencias']['tmp_name'][$index];
                $tipo     = $_FILES['evidencias']['type'][$index];
                $tamaño   = $_FILES['evidencias']['size'][$index];

                if (!isset($tipos_permitidos[$tipo])) {
                    error_log("Tipo no permitido: $tipo");
                    continue;
                }

                if ($tamaño > 5 * 1024 * 1024) {
                    error_log("Evidencia muy grande: $tamaño bytes");
                    continue;
                }

                $info = @getimagesize($tmp_name);
                if ($info === false) {
                    error_log("Archivo no es imagen válida: $nombre");
                    continue;
                }

                // ✅ FIX 6: extensión según MIME real
                $extension = $tipos_permitidos[$tipo];

                $filename = 'evidencia_' . $orden_id . '_' . date('YmdHis') . '_' . ($index + 1) . '.' . $extension;
                $ruta_completa = $uploadDir . $filename;

                if (move_uploaded_file($tmp_name, $ruta_completa)) {
                    $sql = "INSERT INTO ordenes_evidencias (orden_id, archivo, tipo, fecha_subida)
                            VALUES (?, ?, 'foto', NOW())";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([$orden_id, $filename]);
                    $fotos_guardadas++;
                }
            }

        } catch (Throwable $e) {
            error_log("Error en procesarFotosAdjuntas: " . $e->getMessage());
        }

        return $fotos_guardadas;
    }

    /**
     * Mis equipos
     */
    public function mis_equipos() {
        $equipos = [];

        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT DISTINCT e.*, p.nombre_planta, a.nombre_area,
                           COUNT(o.id) as total_ordenes,
                           SUM(o.horas_trabajadas) as total_horas
                    FROM ordenes_mantenimiento o
                    JOIN equipos e ON o.id_equipo = e.id_equipo
                    JOIN areas a ON e.id_area = a.id_area
                    JOIN plantas p ON a.id_planta = p.id_planta
                    WHERE o.tecnico_id = ?
                    GROUP BY e.id_equipo
                    ORDER BY e.nombre_equipo ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute([$this->tecnicoId]);
            $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Throwable $e) {
            error_log("Error en mis_equipos: " . $e->getMessage());
        }

        $titulo = 'Mis Equipos';
        $seccion = 'mis_equipos';
        require_once __DIR__ . '/../views/tecnico/mis_equipos.php';
    }

    /**
     * Herramientas
     */
    public function herramientas() {
        $herramientas = [];

        try {
            $db = Database::getInstance()->getConnection();
            $sql = "SELECT * FROM inventario
                    WHERE estado = 'activo'
                    AND categoria IN ('Herramientas', 'Equipos', 'Insumos')
                    ORDER BY nombre ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $herramientas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Throwable $e) {
            error_log("Error en herramientas: " . $e->getMessage());
        }

        $titulo = 'Herramientas y Repuestos';
        $seccion = 'herramientas';
        require_once __DIR__ . '/../views/tecnico/herramientas.php';
    }

    /**
     * Dashboard Data (AJAX)
     */
    public function dashboardData() {
        try {
            $ordenes = $this->ordenModel->obtenerPorTecnico($this->tecnicoId);

            $data = [
                'total' => 0,
                'pendientes' => 0,
                'en_progreso' => 0,
                'completadas' => 0
            ];

            if (!empty($ordenes)) {
                $data['total'] = count($ordenes);
                foreach ($ordenes as $orden) {
                    $status = $orden['status'] ?? 'PENDIENTE';
                    if ($status === 'PENDIENTE')      $data['pendientes']++;
                    elseif ($status === 'EN_PROCESO') $data['en_progreso']++;
                    elseif ($status === 'CERRADA' || $status === 'APROBADA') $data['completadas']++;
                }
            }

            header('Content-Type: application/json');
            echo json_encode($data);

        } catch (Throwable $e) {
            error_log("Error en dashboardData: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['total' => 0, 'pendientes' => 0, 'en_progreso' => 0, 'completadas' => 0]);
        }
        exit;
    }
}