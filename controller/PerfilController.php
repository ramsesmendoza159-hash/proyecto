<?php

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';
require_once __DIR__ . '/../helpers/SVGSanitizer.php';

class PerfilController extends Controller {
    
    public function __construct() {
        parent::__construct();
    }


    public function index() {
        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        try {
            $usuarioId = $this->authHelper->getUserId();
            $db = Database::getInstance()->getConnection();

            // ✅ FIX CRÍTICO: columnas reales de la tabla 'usuarios'
            $stmt = $db->prepare(
                "SELECT id, nombre, email, rol, estado, firma_svg,
                        fecha_creacion, ultima_conexion
                 FROM usuarios 
                 WHERE id = ?"
            );
            $stmt->execute([$usuarioId]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                $this->redirectWithError('/login', 'Usuario no encontrado');
                return;
            }

            require_once __DIR__ . '/../views/perfil/index.php';

        } catch (Throwable $e) {
            error_log("Error en PerfilController::index: " . $e->getMessage());
            // ✅ FIX: mensaje claro (no redirección silenciosa)
            $this->redirectWithError('/dashboard', 'Error al cargar el perfil');
            return;
        }
    }


    public function actualizar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'mensaje' => 'Método no permitido'], 405);
            return;
        }

        if (!$this->authHelper->isLoggedIn()) {
            $this->jsonResponse(['success' => false, 'mensaje' => 'No autorizado'], 401);
            return;
        }

        try {
            $csrf_token = $_POST['csrf_token'] ?? '';
            if (!SecurityHelper::verifyCSRFToken($csrf_token)) {
                $this->jsonResponse(['success' => false, 'mensaje' => 'Token de seguridad inválido'], 403);
                return;
            }

            $nombre = trim($_POST['nombre'] ?? '');
            $usuarioId = $this->authHelper->getUserId();

            if (empty($nombre)) {
                $this->jsonResponse(['success' => false, 'mensaje' => 'El nombre es obligatorio'], 400);
                return;
            }

            if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 100) {
                $this->jsonResponse(['success' => false, 'mensaje' => 'El nombre debe tener entre 3 y 100 caracteres'], 400);
                return;
            }

            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE usuarios SET nombre = :nombre WHERE id = :id");
            $resultado = $stmt->execute([
                ':nombre' => $nombre,
                ':id' => $usuarioId
            ]);

            if ($resultado) {
                $_SESSION['nombre'] = $nombre;
                $_SESSION['usuario_nombre'] = $nombre;
                $this->jsonResponse(['success' => true, 'mensaje' => 'Perfil actualizado correctamente']);
            } else {
                $this->jsonResponse(['success' => false, 'mensaje' => 'Error al actualizar el perfil'], 500);
            }

        } catch (Throwable $e) {
            error_log("Error en PerfilController::actualizar: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'mensaje' => 'Error al procesar la solicitud'], 500);
        }
    }


    public function guardarFirma() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'mensaje' => 'Método no permitido'], 405);
            return;
        }

        if (!$this->authHelper->isLoggedIn()) {
            $this->jsonResponse(['success' => false, 'mensaje' => 'No autorizado'], 401);
            return;
        }

        try {
            $csrf_token = $_POST['csrf_token'] ?? '';
            if (!SecurityHelper::verifyCSRFToken($csrf_token)) {
                $this->jsonResponse(['success' => false, 'mensaje' => 'Token de seguridad inválido'], 403);
                return;
            }

            $firma_svg = $_POST['firma_svg'] ?? '';

            if (empty($firma_svg)) {
                $this->jsonResponse(['success' => false, 'mensaje' => 'La firma está vacía'], 400);
                return;
            }


            if (!SVGSanitizer::esFormatoValido($firma_svg)) {
                $this->jsonResponse([
                    'success' => false,
                    'mensaje' => 'La firma no tiene formato SVG válido o excede el tamaño máximo (200 KB)'
                ], 400);
                return;
            }

            // ==========================================
            // ✅ FIX: Sanitizar el SVG con el helper centralizado
            // ==========================================
            $firma_svg_limpia = SVGSanitizer::sanitize($firma_svg);

            if (empty($firma_svg_limpia)) {
                $this->jsonResponse([
                    'success' => false,
                    'mensaje' => 'La firma contiene elementos no permitidos y no pudo ser procesada'
                ], 400);
                return;
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
                $this->jsonResponse([
                    'success' => true,
                    'mensaje' => 'Firma guardada correctamente (' . round(strlen($firma_svg_limpia) / 1024, 1) . ' KB)',
                    'bytes' => strlen($firma_svg_limpia)
                ]);
            } elseif ($resultado && $stmt->rowCount() === 0) {
                // El UPDATE no afectó filas — el usuario no existe o la firma es idéntica
                $this->jsonResponse([
                    'success' => false,
                    'mensaje' => 'No se pudo guardar la firma (usuario no encontrado o sin cambios)'
                ], 400);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'mensaje' => 'Error al guardar la firma'
                ], 500);
            }

        } catch (Throwable $e) {
            error_log("Error en PerfilController::guardarFirma: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'mensaje' => 'Error al procesar la solicitud'
            ], 500);
        }
    }
}