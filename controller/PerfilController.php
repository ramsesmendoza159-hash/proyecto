<?php
// controller/PerfilController.php
// Controlador para la gestión del perfil de usuario y firma digital SVG
// ✅ FIX: Integración de SVGSanitizer centralizado para firmas vectoriales

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';
require_once __DIR__ . '/../helpers/SVGSanitizer.php';  // ✅ NUEVO: Sanitizador SVG

class PerfilController {
    private $authHelper;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->authHelper = new AuthHelper();
    }

    /**
     * Muestra la vista del perfil de usuario
     * URL: /perfil
     */
    public function index() {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /login');
            exit;
        }

        try {
            $usuarioId = $this->authHelper->getUserId();
            $db = Database::getInstance()->getConnection();

            $stmt = $db->prepare("SELECT id, nombre, email, usuario, rol, firma_svg, creado_en FROM usuarios WHERE id = ?");
            $stmt->execute([$usuarioId]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                header('Location: /login');
                exit;
            }

            require_once __DIR__ . '/../views/perfil/index.php';

        } catch (Throwable $e) {
            error_log("Error en PerfilController::index: " . $e->getMessage());
            header('Location: /dashboard?error=1');
            exit;
        }
    }

    /**
     * Actualiza los datos del usuario (POST)
     * URL: /perfil/actualizar
     */
    public function actualizar() {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
                exit;
            }

            if (!$this->authHelper->isLoggedIn()) {
                echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
                exit;
            }

            $csrf_token = $_POST['csrf_token'] ?? '';
            if (!SecurityHelper::verifyCSRFToken($csrf_token)) {
                echo json_encode(['success' => false, 'mensaje' => 'Token de seguridad inválido']);
                exit;
            }

            $nombre = trim($_POST['nombre'] ?? '');
            $usuarioId = $this->authHelper->getUserId();

            if (empty($nombre)) {
                echo json_encode(['success' => false, 'mensaje' => 'El nombre es obligatorio']);
                exit;
            }

            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE usuarios SET nombre = :nombre WHERE id = :id");
            $resultado = $stmt->execute([
                ':nombre' => $nombre,
                ':id' => $usuarioId
            ]);

            if ($resultado) {
                $_SESSION['usuario_nombre'] = $nombre;
                echo json_encode(['success' => true, 'mensaje' => 'Perfil actualizado correctamente']);
            } else {
                echo json_encode(['success' => false, 'mensaje' => 'Error al actualizar el perfil']);
            }

        } catch (Throwable $e) {
            error_log("Error en PerfilController::actualizar: " . $e->getMessage());
            echo json_encode(['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Guardar firma del usuario (AJAX)
     * URL: /perfil/guardar-firma (POST)
     * ✅ FIX: usar SVGSanitizer centralizado (antes usaba validación de tamaño + búsqueda de <script>)
     * ✅ FIX: verificar rowCount()
     */
    public function guardarFirma() {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
                exit;
            }

            if (!$this->authHelper->isLoggedIn()) {
                echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
                exit;
            }

            $csrf_token = $_POST['csrf_token'] ?? '';
            if (!SecurityHelper::verifyCSRFToken($csrf_token)) {
                echo json_encode(['success' => false, 'mensaje' => 'Token de seguridad inválido']);
                exit;
            }

            $firma_svg = $_POST['firma_svg'] ?? '';

            if (empty($firma_svg)) {
                echo json_encode(['success' => false, 'mensaje' => 'La firma está vacía']);
                exit;
            }

            // ==========================================
            // ✅ FIX: Validar formato con el helper
            // ==========================================
            if (!SVGSanitizer::esFormatoValido($firma_svg)) {
                echo json_encode([
                    'success' => false,
                    'mensaje' => 'La firma no tiene formato SVG válido o excede el tamaño máximo (200 KB)'
                ]);
                exit;
            }

            // ==========================================
            // ✅ FIX: Sanitizar el SVG con el helper centralizado
            // ==========================================
            $firma_svg_limpia = SVGSanitizer::sanitize($firma_svg);

            if (empty($firma_svg_limpia)) {
                echo json_encode([
                    'success' => false,
                    'mensaje' => 'La firma contiene elementos no permitidos y no pudo ser procesada'
                ]);
                exit;
            }

            $usuarioId = $this->authHelper->getUserId();
            $db = Database::getInstance()->getConnection();

            $sql = "UPDATE usuarios SET firma_svg = :firma_svg WHERE id = :id";
            $stmt = $db->prepare($sql);
            $resultado = $stmt->execute([
                ':firma_svg' => $firma_svg_limpia,
                ':id' => $usuarioId
            ]);

            // Verificar rowCount
            if ($resultado && $stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'mensaje' => 'Firma guardada correctamente (' . round(strlen($firma_svg_limpia) / 1024, 1) . ' KB)',
                    'bytes' => strlen($firma_svg_limpia)
                ]);
            } elseif ($resultado && $stmt->rowCount() === 0) {
                // El UPDATE no afectó filas — probablemente el usuario no existe o la firma es idéntica
                echo json_encode([
                    'success' => false,
                    'mensaje' => 'No se pudo guardar la firma (usuario no encontrado o sin cambios)'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'mensaje' => 'Error al guardar la firma'
                ]);
            }

        } catch (Throwable $e) {
            error_log("Error en guardarFirma: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }
}