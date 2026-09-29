<?php
// helpers/FirmaHelper.php
// Helper para generar y verificar firmas hasheadas de órdenes
// ✅ FIX: catch (Throwable) en vez de Exception
// ✅ FIX: guard contra doble declaración

if (!class_exists('FirmaHelper')) {

    class FirmaHelper {

        private static string $salt = 'MantenimientoProyecto2026_FirmaSecreta_SHA256_Cambiar_Esto';

        private static array $camposFirma = [
            'id',
            'num_om',
            'tecnico_id',
            'horas_trabajadas',
            'costo_total',
            'costo_repuestos',
            'costo_mano_obra',
            'descripcion_realizada',
            'fecha_finalizacion',
            'status',
        ];

        public static function generarFirma($orden): string
        {
            try {
                $datos = [];
                foreach (self::$camposFirma as $campo) {
                    $valor = $orden[$campo] ?? '';

                    if (is_null($valor)) {
                        $valor = '';
                    } elseif (is_numeric($valor)) {
                        $valor = number_format((float)$valor, 4, '.', '');
                    } else {
                        $valor = trim((string)$valor);
                    }

                    $datos[] = $campo . '=' . $valor;
                }

                $cadena = implode('|', $datos);
                return hash('sha256', $cadena . '|SALT:' . self::$salt);

            } catch (Throwable $e) {
                error_log("Error en FirmaHelper::generarFirma: " . $e->getMessage());
                return '';
            }
        }

        public static function verificarFirma($orden): array
        {
            try {
                $firmaGuardada = $orden['firma_hash'] ?? '';

                if (empty($firmaGuardada)) {
                    return [
                        'valida' => false,
                        'firma_guardada' => '',
                        'firma_calculada' => '',
                        'mensaje' => 'Esta orden no tiene firma registrada',
                    ];
                }

                $firmaCalculada = self::generarFirma($orden);
                $valida = hash_equals($firmaGuardada, $firmaCalculada);

                return [
                    'valida' => $valida,
                    'firma_guardada' => $firmaGuardada,
                    'firma_calculada' => $firmaCalculada,
                    'mensaje' => $valida
                        ? 'La firma es válida. La orden no ha sido alterada.'
                        : '⚠️ ALERTA: Los datos de la orden han sido modificados después de su cierre.',
                ];

            } catch (Throwable $e) {
                error_log("Error en FirmaHelper::verificarFirma: " . $e->getMessage());
                return [
                    'valida' => false,
                    'firma_guardada' => '',
                    'firma_calculada' => '',
                    'mensaje' => 'Error al verificar la firma: ' . $e->getMessage(),
                ];
            }
        }

        public static function getCamposFirma(): array
        {
            return self::$camposFirma;
        }

        public static function getResumenDatosFirmados($orden): array
        {
            $resumen = [];
            foreach (self::$camposFirma as $campo) {
                $valor = $orden[$campo] ?? '';

                if (in_array($campo, ['costo_total', 'costo_repuestos', 'costo_mano_obra'])) {
                    $valor = 'S/ ' . number_format((float)$valor, 2);
                } elseif ($campo === 'horas_trabajadas') {
                    $valor = number_format((float)$valor, 2) . ' h';
                } elseif ($campo === 'fecha_finalizacion' && !empty($valor)) {
                    $valor = date('d/m/Y H:i', strtotime($valor));
                } elseif (empty($valor)) {
                    $valor = '(vacío)';
                }

                $resumen[$campo] = $valor;
            }
            return $resumen;
        }

        public static function acortarHash(?string $hash, int $longitud = 16): string
        {
            if (empty($hash)) return '';
            if (strlen($hash) <= $longitud) return $hash;
            return substr($hash, 0, $longitud) . '...';
        }
    }
}