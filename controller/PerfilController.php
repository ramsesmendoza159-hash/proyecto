<?php
// controller/PerfilController.php

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

            $titulo = 'Mi Perfil';
            $seccion = 'perfil';
            require_once __DIR__ . '/../views/perfil/index.php';

        } catch (Throwable $e) {
            error_log("Error en PerfilController::index: " . $e->getMessage());
            $this->redirectWithError('/dashboard', 'Error al cargar el perfil');
            return;
        }
    }

    public function editar() {
        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        try {
            $usuarioId = $this->authHelper->getUserId();
            $db = Database::getInstance()->getConnection();

            $stmt = $db->prepare(
                "SELECT id, nombre, email, rol, estado
                 FROM usuarios
                 WHERE id = ?"
            );
            $stmt->execute([$usuarioId]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                $this->redirectWithError('/perfil', 'Usuario no encontrado');
                return;
            }

            $titulo = 'Editar Perfil';
            $seccion = 'perfil';
            require_once __DIR__ . '/../views/perfil/editar.php';

        } catch (Throwable $e) {
            error_log("Error en PerfilController::editar: " . $e->getMessage());
            $this->redirectWithError('/perfil', 'Error al cargar el formulario');
            return;
        }
    }

    public function actualizar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('/perfil', 'Método no permitido');
            return;
        }

        if (!$this->authHelper->isLoggedIn()) {
            $this->redirectWithError('/login', 'No autorizado');
            return;
        }

        try {
            $csrf_token = $_POST['csrf_token'] ?? '';
            if (!SecurityHelper::verifyCSRFToken($csrf_token)) {
                $this->redirectWithError('/perfil/editar', 'Token de seguridad inválido');
                return;
            }

            $usuarioId = $this->authHelper->getUserId();
            $nombre    = trim($_POST['nombre'] ?? '');
            $email     = trim($_POST['email'] ?? '');

            if (empty($nombre)) {
                $this->redirectWithError('/perfil/editar', 'El nombre es obligatorio');
                return;
            }

            if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 100) {
                $this->redirectWithError('/perfil/editar', 'El nombre debe tener entre 3 y 100 caracteres');
                return;
            }

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->redirectWithError('/perfil/editar', 'El email no es válido');
                return;
            }

            $db = Database::getInstance()->getConnection();

            $stmtCheck = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ? LIMIT 1");
            $stmtCheck->execute([$email, $usuarioId]);
            if ($stmtCheck->fetch()) {
                $this->redirectWithError('/perfil/editar', 'Ese email ya está registrado por otro usuario');
                return;
            }

            $stmt = $db->prepare("UPDATE usuarios SET nombre = :nombre, email = :email WHERE id = :id");
            $resultado = $stmt->execute([
                ':nombre' => $nombre,
                ':email'  => $email,
                ':id'     => $usuarioId
            ]);

            if ($resultado) {
                $_SESSION['nombre'] = $nombre;
                $_SESSION['email']  = $email;
                $this->redirectWithSuccess('/perfil', 'Perfil actualizado correctamente');
            } else {
                $this->redirectWithError('/perfil/editar', 'Error al actualizar el perfil');
            }

        } catch (Throwable $e) {
            error_log("Error en PerfilController::actualizar: " . $e->getMessage());
            $this->redirectWithError('/perfil/editar', 'Error al procesar la solicitud');
        }
    }

    /**
     * ✅ FIX: usa password_hash (columna real) y maneja ambos flujos (JSON y redirect)
     */
    public function cambiarPassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectWithError('/perfil', 'Método no permitido');
            return;
        }

        if (!$this->authHelper->isLoggedIn()) {
            $this->redirectWithError('/login', 'No autorizado');
            return;
        }

        $esAjax = (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest')
               || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

        try {
            $csrf_token = $_POST['csrf_token'] ?? '';
            if (!SecurityHelper::verifyCSRFToken($csrf_token)) {
                if ($esAjax) {
                    $this->jsonResponse(['success' => false, 'mensaje' => 'Token de seguridad inválido'], 403);
                } else {
                    $this->redirectWithError('/perfil', 'Token de seguridad inválido');
                }
                return;
            }

            $usuarioId   = $this->authHelper->getUserId();
            $passActual  = $_POST['password_actual'] ?? '';
            $passNueva   = $_POST['password_nueva'] ?? '';
            $passConfirm = $_POST['password_confirmar'] ?? '';

            if (empty($passActual) || empty($passNueva) || empty($passConfirm)) {
                if ($esAjax) {
                    $this->jsonResponse(['success' => false, 'mensaje' => 'Todos los campos son obligatorios'], 400);
                } else {
                    $this->redirectWithError('/perfil', 'Todos los campos son obligatorios');
                }
                return;
            }

            if (strlen($passNueva) < 6) {
                if ($esAjax) {
                    $this->jsonResponse(['success' => false, 'mensaje' => 'La nueva contraseña debe tener al menos 6 caracteres'], 400);
                } else {
                    $this->redirectWithError('/perfil', 'La nueva contraseña debe tener al menos 6 caracteres');
                }
                return;
            }

            if ($passNueva !== $passConfirm) {
                if ($esAjax) {
                    $this->jsonResponse(['success' => false, 'mensaje' => 'Las contraseñas no coinciden'], 400);
                } else {
                    $this->redirectWithError('/perfil', 'Las contraseñas no coinciden');
                }
                return;
            }

            if ($passActual === $passNueva) {
                if ($esAjax) {
                    $this->jsonResponse(['success' => false, 'mensaje' => 'La nueva contraseña debe ser diferente a la actual'], 400);
                } else {
                    $this->redirectWithError('/perfil', 'La nueva contraseña debe ser diferente a la actual');
                }
                return;
            }

            $db = Database::getInstance()->getConnection();

            // ✅ FIX: columna correcta es password_hash
            $stmt = $db->prepare("SELECT password_hash FROM usuarios WHERE id = ?");
            $stmt->execute([$usuarioId]);
            $hashActual = $stmt->fetchColumn();

            if (!$hashActual) {
                if ($esAjax) {
                    $this->jsonResponse(['success' => false, 'mensaje' => 'Usuario no encontrado'], 404);
                } else {
                    $this->redirectWithError('/perfil', 'Usuario no encontrado');
                }
                return;
            }

            if (!password_verify($passActual, $hashActual)) {
                if ($esAjax) {
                    $this->jsonResponse(['success' => false, 'mensaje' => 'La contraseña actual es incorrecta'], 400);
                } else {
                    $this->redirectWithError('/perfil', 'La contraseña actual es incorrecta');
                }
                return;
            }

            $nuevoHash = password_hash($passNueva, PASSWORD_DEFAULT);

            // ✅ FIX: columna password_hash
            $stmt = $db->prepare("UPDATE usuarios SET password_hash = ?, fecha_actualizacion = NOW() WHERE id = ?");
            $resultado = $stmt->execute([$nuevoHash, $usuarioId]);

            if ($resultado && $stmt->rowCount() > 0) {
                if ($esAjax) {
                    $this->jsonResponse(['success' => true, 'mensaje' => 'Contraseña actualizada correctamente']);
                } else {
                    $this->redirectWithSuccess('/perfil', 'Contraseña actualizada correctamente');
                }
            } else {
                if ($esAjax) {
                    $this->jsonResponse(['success' => false, 'mensaje' => 'No se pudo actualizar la contraseña'], 500);
                } else {
                    $this->redirectWithError('/perfil', 'No se pudo actualizar la contraseña');
                }
            }

        } catch (Throwable $e) {
            error_log("Error en PerfilController::cambiarPassword: " . $e->getMessage());
            if ($esAjax) {
                $this->jsonResponse(['success' => false, 'mensaje' => 'Error al procesar la solicitud'], 500);
            } else {
                $this->redirectWithError('/perfil', 'Error al procesar la solicitud');
            }
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

            if ($resultado && $stmt->rowCount() > 0) {
                $this->jsonResponse([
                    'success' => true,
                    'mensaje' => 'Firma guardada correctamente (' . round(strlen($firma_svg_limpia) / 1024, 1) . ' KB)',
                    'bytes' => strlen($firma_svg_limpia)
                ]);
            } elseif ($resultado && $stmt->rowCount() === 0) {
                $this->jsonResponse([
                    'success' => true,
                    'mensaje' => 'Firma sin cambios (idéntica a la anterior)'
                ]);
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