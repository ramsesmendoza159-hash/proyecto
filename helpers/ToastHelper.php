<?php
// helpers/ToastHelper.php
// Helper para notificaciones toast

class ToastHelper {

    private static array $tipos_validos = ['success', 'error', 'warning', 'info'];

    public static function add(string $tipo, string $mensaje, ?string $titulo = null, int $duracion = 5000): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!in_array($tipo, self::$tipos_validos, true)) {
            $tipo = 'info';
        }

        if (!isset($_SESSION['toasts'])) {
            $_SESSION['toasts'] = [];
        }

        if ($titulo === null) {
            $titulo = match($tipo) {
                'success' => '¡Éxito!',
                'error' => 'Error',
                'warning' => 'Advertencia',
                'info' => 'Información',
                default => 'Notificación',
            };
        }

        $_SESSION['toasts'][] = [
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'duracion' => $duracion,
            'id' => uniqid('toast_'),
        ];
    }

    public static function success(string $mensaje, ?string $titulo = null, int $duracion = 5000): void
    {
        self::add('success', $mensaje, $titulo, $duracion);
    }

    public static function error(string $mensaje, ?string $titulo = null, int $duracion = 7000): void
    {
        self::add('error', $mensaje, $titulo, $duracion);
    }

    public static function warning(string $mensaje, ?string $titulo = null, int $duracion = 6000): void
    {
        self::add('warning', $mensaje, $titulo, $duracion);
    }

    public static function info(string $mensaje, ?string $titulo = null, int $duracion = 5000): void
    {
        self::add('info', $mensaje, $titulo, $duracion);
    }

    public static function getAndClear(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $toasts = $_SESSION['toasts'] ?? [];
        unset($_SESSION['toasts']);

        return $toasts;
    }

    public static function hasToasts(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return !empty($_SESSION['toasts']);
    }

    public static function migrarMensajesAntiguos(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($_SESSION['mensaje'])) {
            $tipo = $_SESSION['mensaje_tipo'] ?? 'success';
            if ($tipo === 'danger') {
                $tipo = 'error';
            }
            if (!in_array($tipo, self::$tipos_validos, true)) {
                $tipo = 'info';
            }
            self::add($tipo, (string)$_SESSION['mensaje']);
            unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']);
        }

        if (!empty($_SESSION['error'])) {
            self::error((string)$_SESSION['error']);
            unset($_SESSION['error']);
        }

        if (!empty($_SESSION['success'])) {
            self::success((string)$_SESSION['success']);
            unset($_SESSION['success']);
        }

        if (!empty($_SESSION['errores']) && is_array($_SESSION['errores'])) {
            foreach ($_SESSION['errores'] as $error) {
                self::error((string)$error);
            }
            unset($_SESSION['errores']);
        }
    }
}