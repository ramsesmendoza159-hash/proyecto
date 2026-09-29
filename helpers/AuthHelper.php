<?php
// helpers/AuthHelper.php
// Helper de autenticación para todos los roles

class AuthHelper {

    public function isLoggedIn(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
    }

    public function getRole(): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['rol'] ?? null;
    }

    public function getUserId(): ?int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : null;
    }

    public function getUsername(): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['nombre'] ?? null;
    }

    public function getUserEmail(): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['email'] ?? null;
    }

    public function hasRole($role): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        if (is_array($role)) {
            return in_array($this->getRole(), $role, true);
        }

        return $this->getRole() === $role;
    }

    // ==========================================
    // VERIFICACIONES POR ROL
    // ==========================================

    public function isAdmin(): bool { return $this->hasRole('admin'); }
    public function isSupervisor(): bool { return $this->hasRole('supervisor'); }
    public function isTecnico(): bool { return $this->hasRole('tecnico'); }
    public function isAlmacen(): bool { return $this->hasRole('almacen'); }
    public function isOperador(): bool { return $this->hasRole('operador'); }
    public function isConsultor(): bool { return $this->hasRole('consultor'); }
    public function isIngeniero(): bool { return $this->hasRole('ingeniero'); }
    public function isCalidad(): bool { return $this->hasRole('calidad'); }
    public function isSeguridad(): bool { return $this->hasRole('seguridad'); }

    public function redirectByRole(): void
    {
        if (!$this->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit();
        }

        $role = $this->getRole();

        $map = [
            'admin'      => '/proyecto/dashboard',
            'supervisor' => '/proyecto/supervisor',
            'tecnico'    => '/proyecto/tecnico',
            'almacen'    => '/proyecto/almacen',
            'operador'   => '/proyecto/operador',
            'consultor'  => '/proyecto/consultor',
            'ingeniero'  => '/proyecto/ingeniero',
            'calidad'    => '/proyecto/calidad',
            'seguridad'  => '/proyecto/seguridad',
        ];

        header('Location: ' . ($map[$role] ?? '/proyecto/auth/login'));
        exit();
    }

    public function checkAccess(array $allowedRoles = []): bool
    {
        if (!$this->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit();
        }

        if (!empty($allowedRoles) && !$this->hasRole($allowedRoles)) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            $this->redirectByRole();
            exit();
        }

        return true;
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();
        header('Location: /proyecto/auth/login');
        exit();
    }
}
