<?php
// helpers/Controller.php
// Clase base para todos los controladores
// ✅ FIX: view() sin extract() inseguro
// ✅ FIX: redirect() valida URLs externas
// ✅ FIX: requirePost() usa http_response_code en vez de header HTTP/1.0

if (!class_exists('AuthHelper')) {
    require_once __DIR__ . '/AuthHelper.php';
}
if (!class_exists('ToastHelper')) {
    require_once __DIR__ . '/ToastHelper.php';
}

class Controller
{
    protected $authHelper;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->authHelper = new AuthHelper();
    }

    protected function requireAuth(): void
    {
        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
        }
    }

    protected function requireRole($roles): void
    {
        $this->requireAuth();
        if (!$this->authHelper->hasRole($roles)) {
            $this->toastError('No tienes permisos para acceder a esta sección');
            $this->redirect('/dashboard');
        }
    }

    /**
     * ✅ FIX: No usa extract(), asigna variables una por una con guard
     */
    protected function view(string $view, array $data = []): void
    {
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            error_log("Vista no encontrada: {$view}");
            die("Vista no encontrada: " . htmlspecialchars($view));
        }

        foreach ($data as $key => $value) {
            if ($key === 'view' || $key === 'viewFile') {
                continue;
            }
            $$key = $value;
        }

        require $viewFile;
    }

    /**
     * ✅ FIX: Bloquea redirecciones a URLs externas
     */
    protected function redirect(string $url): void
    {
        if (preg_match('#^https?://#i', $url)) {
            error_log("Intento de redirección externa bloqueado: {$url}");
            $url = '/';
        }

        if (strpos($url, '/proyecto') === 0) {
            header('Location: ' . $url);
        } else {
            header('Location: /proyecto' . $url);
        }
        exit();
    }

    protected function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die('Método no permitido');
        }
    }

    protected function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    protected function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    protected function jsonResponse($data, int $status = 200): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }

    // ==========================================
    // TOASTS
    // ==========================================

    protected function toastSuccess(string $mensaje, ?string $titulo = null): void
    {
        ToastHelper::success($mensaje, $titulo);
    }

    protected function toastError(string $mensaje, ?string $titulo = null): void
    {
        ToastHelper::error($mensaje, $titulo);
    }

    protected function toastWarning(string $mensaje, ?string $titulo = null): void
    {
        ToastHelper::warning($mensaje, $titulo);
    }

    protected function toastInfo(string $mensaje, ?string $titulo = null): void
    {
        ToastHelper::info($mensaje, $titulo);
    }

    protected function redirectWithSuccess(string $url, string $mensaje, ?string $titulo = null): void
    {
        $this->toastSuccess($mensaje, $titulo);
        $this->redirect($url);
    }

    protected function redirectWithError(string $url, string $mensaje, ?string $titulo = null): void
    {
        $this->toastError($mensaje, $titulo);
        $this->redirect($url);
    }

    protected function redirectWithWarning(string $url, string $mensaje, ?string $titulo = null): void
    {
        $this->toastWarning($mensaje, $titulo);
        $this->redirect($url);
    }

    protected function redirectWithInfo(string $url, string $mensaje, ?string $titulo = null): void
    {
        $this->toastInfo($mensaje, $titulo);
        $this->redirect($url);
    }
}