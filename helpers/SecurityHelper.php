<?php
// helpers/SecurityHelper.php
// Helper de seguridad: sesión, CSRF, sanitización

class SecurityHelper {

    public static function verificarSesion(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
    }

    public static function verificarRol($rolesPermitidos): bool
    {
        if (!self::verificarSesion()) {
            return false;
        }

        $rol = $_SESSION['rol'] ?? '';

        if (!is_array($rolesPermitidos)) {
            $rolesPermitidos = [$rolesPermitidos];
        }

        return in_array($rol, $rolesPermitidos, true);
    }

    public static function generateCSRFToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCSRFToken($token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], (string)$token);
    }

    public static function sanitize($input)
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
    }

    public static function preventXSS($input)
    {
        if (is_array($input)) {
            return array_map([self::class, 'preventXSS'], $input);
        }
        return htmlspecialchars((string)$input, ENT_QUOTES, 'UTF-8');
    }

    public static function sanitizeForDB($input)
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeForDB'], $input);
        }
        return trim(strip_tags((string)$input));
    }

    public static function isAdmin(): bool
    {
        if (!self::verificarSesion()) return false;
        return ($_SESSION['rol'] ?? '') === 'admin';
    }

    public static function isSupervisor(): bool
    {
        if (!self::verificarSesion()) return false;
        return ($_SESSION['rol'] ?? '') === 'supervisor';
    }

    public static function isTecnico(): bool
    {
        if (!self::verificarSesion()) return false;
        return ($_SESSION['rol'] ?? '') === 'tecnico';
    }

    public static function getUserId(): ?int
    {
        if (!self::verificarSesion()) return null;
        return (int)($_SESSION['usuario_id'] ?? 0) ?: null;
    }

    public static function getRol(): ?string
    {
        if (!self::verificarSesion()) return null;
        return $_SESSION['rol'] ?? null;
    }

    public static function requireAuth(): void
    {
        if (!self::verificarSesion()) {
            $_SESSION['error'] = 'Debes iniciar sesión para acceder a esta página';
            header('Location: /proyecto/auth/login');
            exit;
        }
    }

    public static function requireRole($roles): void
    {
        self::requireAuth();

        if (!self::verificarRol($roles)) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta página';
            header('Location: /proyecto/dashboard');
            exit;
        }
    }
}