<?php
// helpers/FirmaSecuenciaHelper.php
// Helper para gestionar la secuencia de firmas de órdenes
// ✅ FIX: catch (Throwable) y tipos declarados

if (!class_exists('FirmaSecuenciaHelper')) {

    class FirmaSecuenciaHelper {

        private static array $secuencia_default = [
            ['paso' => 1, 'rol_firmante' => 'ingeniero',  'nombre_paso' => 'Revisión y Aprobación del Ingeniero',    'obligatorio' => 1],
            ['paso' => 2, 'rol_firmante' => 'calidad',    'nombre_paso' => 'Validación de Calidad (Pre-ejecución)',  'obligatorio' => 0],
            ['paso' => 3, 'rol_firmante' => 'tecnico',    'nombre_paso' => 'Ejecución y Firma del Técnico',          'obligatorio' => 1],
            ['paso' => 4, 'rol_firmante' => 'supervisor', 'nombre_paso' => 'Supervisión y Aprobación',               'obligatorio' => 1],
            ['paso' => 5, 'rol_firmante' => 'calidad',    'nombre_paso' => 'Validación de Calidad (Post-ejecución)', 'obligatorio' => 0],
            ['paso' => 6, 'rol_firmante' => 'seguridad',  'nombre_paso' => 'Validación de Seguridad',                'obligatorio' => 0],
        ];

        public static function obtenerSecuencia(): array
        {
            try {
                if (!class_exists('Database')) {
                    require_once __DIR__ . '/../config/database.php';
                }
                $db = Database::getInstance()->getConnection();

                $sql = "SELECT paso, rol_firmante, nombre_paso, obligatorio
                        FROM firma_secuencia_config
                        WHERE activo = 1
                        ORDER BY paso ASC";
                $stmt = $db->prepare($sql);
                $stmt->execute();
                $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($resultado)) {
                    return self::$secuencia_default;
                }

                return $resultado;

            } catch (Throwable $e) {
                error_log("FirmaSecuenciaHelper::obtenerSecuencia - " . $e->getMessage());
                return self::$secuencia_default;
            }
        }

        public static function obtenerPaso(int $numero_paso): ?array
        {
            $secuencia = self::obtenerSecuencia();
            foreach ($secuencia as $paso) {
                if ((int)$paso['paso'] === $numero_paso) {
                    return $paso;
                }
            }
            return null;
        }

        public static function obtenerRolPorPaso(int $numero_paso): ?string
        {
            $paso = self::obtenerPaso($numero_paso);
            return $paso ? $paso['rol_firmante'] : null;
        }

        public static function obtenerNombrePaso(int $numero_paso): string
        {
            $paso = self::obtenerPaso($numero_paso);
            return $paso ? $paso['nombre_paso'] : 'Paso ' . $numero_paso;
        }

        public static function obtenerTotalPasos(): int
        {
            return count(self::obtenerSecuencia());
        }

        public static function puedeFirmarPaso(string $rol, int $numero_paso): bool
        {
            return self::obtenerRolPorPaso($numero_paso) === $rol;
        }

        public static function obtenerPasosPorRol(string $rol): array
        {
            $secuencia = self::obtenerSecuencia();
            $pasos = [];
            foreach ($secuencia as $paso) {
                if ($paso['rol_firmante'] === $rol) {
                    $pasos[] = $paso;
                }
            }
            return $pasos;
        }

        public static function obtenerIconoPaso(string $rol): string
        {
            return match($rol) {
                'ingeniero'  => 'fa-drafting-compass',
                'calidad'    => 'fa-clipboard-check',
                'tecnico'    => 'fa-tools',
                'supervisor' => 'fa-user-tie',
                'seguridad'  => 'fa-shield-alt',
                default      => 'fa-signature',
            };
        }

        public static function obtenerColorPaso(string $rol): string
        {
            return match($rol) {
                'ingeniero'  => '#8b5cf6',
                'calidad'    => '#06b6d4',
                'tecnico'    => '#3b82f6',
                'supervisor' => '#f59e0b',
                'seguridad'  => '#ef4444',
                default      => '#6b7280',
            };
        }
    }
}