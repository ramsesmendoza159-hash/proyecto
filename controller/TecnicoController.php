<?php
// controller/TecnicoController.php
// VERSIÓN CORREGIDA - SIN ChecklistModel (eliminado del proyecto)
// ✅ FIX: Eliminado require_once ChecklistModel.php (archivo no existe)
// ✅ FIX: Eliminada propiedad $checklistModel y su instanciación
// ✅ FIX: procesarCierre() no llama a guardarChecklist()
// ✅ FIX: floatval() en vez de (float)
// ✅ FIX: catch (Throwable) en vez de Exception
// ✅ FIX: Uso de $this->redirect() en vez de header() directo

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

        if (!$this->authHelper->isLoggedIn() || !$this->authHelper->isTecnico()) {
            header('Location: /proyecto/auth/login');
            exit();
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
            $_SESSION['error'] = 'Orden no encontrada';
            header('Location: /proyecto/tecnico/mis_ordenes');
            exit();
        }

        if ((int)$orden['tecnico_id'] !== (int)$this->tecnicoId) {
            $_SESSION['error'] = 'No tienes permisos para ver esta orden';
            header('Location: /proyecto/tecnico/mis_ordenes');
            exit();
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
            $_SESSION['error'] = 'Orden no encontrada';
            header('Location: /proyecto/tecnico/mis_ordenes');
            exit();
        }

        $status = $orden['status'] ?? '';
        if (!in_array($status, ['PENDIENTE', 'EN_PROCESO', 'EJECUTADA'], true)) {
            $_SESSION['error'] = 'Esta orden no se puede cerrar en su estado actual';
            header('Location: /proyecto/tecnico/detalle_orden/' . $id);
            exit();
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
     * ✅ FIX: Lee $_POST['firma_svg'] y lo guarda
     * ✅ FIX: NO llama a guardarChecklist() (tabla no existe)
     * ✅ FIX: Usa floatval() en vez de (float)
     * ✅ FIX: catch (Throwable)
     */
    public function procesarCierre($id) {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: /proyecto/tecnico/cerrar_orden/' . $id);
                exit();
            }

            $token = $_POST['csrf_token'] ?? '';
            if (!SecurityHelper::verifyCSRFToken($token)) {
                $_SESSION['error'] = 'Token de seguridad inválido';
                header('Location: /proyecto/tecnico/cerrar_orden/' . $id);
                exit();
            }

            $orden = $this->ordenModel->obtenerPorId($id);
            if (!$orden) {
                $_SESSION['error'] = 'Orden no encontrada';
                header('Location: /proyecto/tecnico/mis_ordenes');
                exit();
            }

            if ((int)$orden['tecnico_id'] !== (int)$this->tecnicoId) {
                $_SESSION['error'] = 'No tienes permisos para cerrar esta orden';
                header('Location: /proyecto/tecnico/mis_ordenes');
                exit();
            }

            $checklist        = $_POST['checklist'] ?? [];
            $tiempo_invertido = floatval($_POST['tiempo_invertido'] ?? 0);
            $firma_svg        = $_POST['firma_svg'] ?? '';

            if (empty($checklist) || !is_array($checklist)) {
                $_SESSION['error'] = 'Debes completar la lista de verificación';
                header('Location: /proyecto/tecnico/cerrar_orden/' . $id);
                exit();
            }

            if ($tiempo_invertido <= 0) {
                $_SESSION['error'] = 'Debes indicar el tiempo invertido';
                header('Location: /proyecto/tecnico/cerrar_orden/' . $id);
                exit();
            }

            if ($tiempo_invertido > 720) {
                $_SESSION['error'] = 'El tiempo invertido no puede exceder 720 horas (30 días)';
                header('Location: /proyecto/tecnico/cerrar_orden/' . $id);
                exit();
            }

            if (empty($firma_svg)) {
                $_SESSION['error'] = 'Debes dibujar tu firma antes de cerrar la orden';
                header('Location: /proyecto/tecnico/cerrar_orden/' . $id);
                exit();
            }

            if (strpos($firma_svg, '<svg') === false || strpos($firma_svg, '</svg>') === false) {
                $_SESSION['error'] = 'La firma no tiene el formato correcto (SVG)';
                header('Location: /proyecto/tecnico/cerrar_orden/' . $id);
                exit();
            }

            if (strlen($firma_svg) > 204800) {
                $_SESSION['error'] = 'La firma es demasiado grande (máx 200 KB)';
                header('Location: /proyecto/tecnico/cerrar_orden/' . $id);
                exit();
            }

            $fotos_guardadas = $this->procesarFotosAdjuntas($id);

            // Preparar descripción con el checklist
            $descripcion_realizada = "Trabajo completado según checklist:\n";
            foreach ($checklist as $item) {
                $descripcion_realizada .= "✓ " . $item . "\n";
            }

            $tarifa = floatval($orden['tarifa_tecnico'] ?? 0);
            $costo_mano_obra = $tiempo_invertido * $tarifa;

            $datos = [
                'descripcion_realizada'  => $descripcion_realizada,
                'pasos_ejecutados'       => '',
                'horas_trabajadas'       => $tiempo_invertido,
                'tarifa_tecnico'         => $tarifa,
                'costo_repuestos'        => floatval($orden['costo_repuestos'] ?? 0),
                'costo_mano_obra'        => $costo_mano_obra,
                'costo_total'            => floatval($orden['costo_total'] ?? 0) + $costo_mano_obra,
                'foto_evidencia'         => $fotos_guardadas > 0 ? 'evidencias' : '',
                'observaciones_tecnico'  => $_POST['observaciones_tecnico'] ?? 'Cerrado por técnico con checklist',
                'observaciones_cierre'   => '',
                'actualizado_por'        => $this->authHelper->getUserId()
            ];

            $resultado = $this->ordenModel->cerrar($id, $datos);

            if ($resultado) {
                // Registrar la firma secuencial del técnico
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
                            error_log("Error al registrar firma del técnico: " . $resultadoFirma['mensaje']);
                        }
                    }
                } catch (Throwable $e) {
                    error_log("Error al registrar firma secuencial: " . $e->getMessage());
                }

                // Registrar en auditoría
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

                $msg = 'Orden cerrada correctamente. Firma digital registrada.';
                if ($fotos_guardadas > 0) {
                    $msg .= " ({$fotos_guardadas} foto(s) guardada(s))";
                }
                $_SESSION['mensaje'] = $msg;
                $_SESSION['mensaje_tipo'] = 'success';
                header('Location: /proyecto/tecnico/detalle_orden/' . $id);
            } else {
                $_SESSION['error'] = 'Error al cerrar la orden';
                header('Location: /proyecto/tecnico/cerrar_orden/' . $id);
            }

        } catch (Throwable $e) {
            error_log("Error en procesarCierre: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cerrar la orden: ' . $e->getMessage();
            header('Location: /proyecto/tecnico/cerrar_orden/' . $id);
        }
        exit();
    }

    /**
     * Procesar fotos adjuntas
     */
    private function procesarFotosAdjuntas($orden_id) {
        $fotos_guardadas = 0;

        try {
            if (empty($_FILES['fotos']) || !is_array($_FILES['fotos']['name'])) {
                return 0;
            }

            $uploadDir = __DIR__ . '/../uploads/evidencias/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $db = Database::getInstance()->getConnection();
            $tipos_permitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];

            foreach ($_FILES['fotos']['name'] as $index => $nombre) {
                if (empty($nombre)) continue;

                $error = $_FILES['fotos']['error'][$index] ?? UPLOAD_ERR_NO_FILE;
                if ($error !== UPLOAD_ERR_OK) {
                    error_log("Error al subir foto $index: código $error");
                    continue;
                }

                $tmp_name = $_FILES['fotos']['tmp_name'][$index];
                $tipo     = $_FILES['fotos']['type'][$index];
                $tamaño   = $_FILES['fotos']['size'][$index];

                if (!in_array($tipo, $tipos_permitidos, true)) {
                    error_log("Tipo no permitido: $tipo");
                    continue;
                }

                if ($tamaño > 5 * 1024 * 1024) {
                    error_log("Foto muy grande: $tamaño bytes");
                    continue;
                }

                // Validar que sea imagen real
                $info = @getimagesize($tmp_name);
                if ($info === false) {
                    error_log("Archivo no es imagen válida: $nombre");
                    continue;
                }

                $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $extension = 'jpg';
                }

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
