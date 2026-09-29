<?php
// helpers/BackdatingHelper.php
// Helper para gestionar el backdating (registros retroactivos)
// ✅ FIX: catch (Throwable) en vez de Exception
// ✅ FIX: validación de fechas con DateTime
// ✅ FIX: whitelist de roles que pueden hacer backdating
// ✅ FIX: límites de días por rol

if (!class_exists('BackdatingHelper')) {

    class BackdatingHelper {

        /**
         * Límites de días retroactivos permitidos por rol.
         * null = sin límite
         */
        private static array $limites_por_rol = [
            'admin'      => null,   // sin límite
            'ingeniero'  => 365,    // 1 año
            'supervisor' => 90,     // 3 meses
            'calidad'    => 30,     // 1 mes
            'tecnico'    => 7,      // 1 semana
            'almacen'    => 15,
            'operador'   => 3,
            'consultor'  => 0,      // no puede
            'seguridad'  => 0,      // no puede
        ];

        /**
         * Roles que PUEDEN hacer backdating.
         */
        private static array $roles_permitidos = [
            'admin',
            'ingeniero',
            'supervisor',
            'calidad',
            'tecnico',
            'almacen',
            'operador',
        ];

        // ==========================================
        // VALIDACIONES
        // ==========================================

        /**
         * Valida si una fecha es aceptable para backdating según el rol.
         *
         * @param string $fecha  Formato Y-m-d o Y-m-d H:i:s
         * @param string $rol
         * @return array ['valido' => bool, 'error' => string|null, 'dias_diferencia' => int]
         */
        public static function validarFecha($fecha, $rol) {
            $resultado = [
                'valido'          => false,
                'error'           => null,
                'dias_diferencia' => 0,
                'limite_dias'     => self::getLimitePorRol($rol),
            ];

            try {
                // 1. Verificar que el rol pueda hacer backdating
                if (!self::puedeHacerBackdating($rol)) {
                    $resultado['error'] = 'Tu rol no tiene permisos para registrar retroactivos';
                    return $resultado;
                }

                // 2. Validar formato de fecha
                if (empty($fecha)) {
                    $resultado['error'] = 'La fecha es obligatoria';
                    return $resultado;
                }

                $timestamp = strtotime($fecha);
                if ($timestamp === false) {
                    $resultado['error'] = 'El formato de fecha es inválido';
                    return $resultado;
                }

                // 3. No permitir fechas futuras (más de 1 min de margen por desfase de reloj)
                if ($timestamp > time() + 60) {
                    $resultado['error'] = 'No se pueden registrar fechas futuras';
                    return $resultado;
                }

                // 4. Calcular días de diferencia con hoy
                $hoy = new DateTime('today');
                $fecha_dt = new DateTime(date('Y-m-d', $timestamp));
                $diff = $hoy->diff($fecha_dt);
                $dias_diferencia = (int)$diff->days;
                $resultado['dias_diferencia'] = $dias_diferencia;

                // 5. Validar contra el límite del rol
                $limite = self::getLimitePorRol($rol);

                if ($limite === 0) {
                    $resultado['error'] = 'Tu rol no tiene permitido registrar retroactivos';
                    return $resultado;
                }

                if ($limite !== null && $dias_diferencia > $limite) {
                    $resultado['error'] = "Solo puedes registrar retroactivos hasta {$limite} días atrás. "
                                        . "Esta fecha tiene {$dias_diferencia} días de antigüedad.";
                    return $resultado;
                }

                $resultado['valido'] = true;
                return $resultado;

            } catch (Throwable $e) {
                error_log("Error en BackdatingHelper::validarFecha: " . $e->getMessage());
                $resultado['error'] = 'Error al validar la fecha: ' . $e->getMessage();
                return $resultado;
            }
        }

        /**
         * Verifica si un rol puede hacer backdating en general.
         */
        public static function puedeHacerBackdating($rol) {
            return in_array($rol, self::$roles_permitidos, true);
        }

        /**
         * Devuelve el límite de días permitidos para un rol.
         * null = sin límite. 0 = no permitido.
         */
        public static function getLimitePorRol($rol) {
            return self::$limites_por_rol[$rol] ?? 0;
        }

        /**
         * Devuelve una descripción legible del límite.
         */
        public static function getDescripcionLimite($rol) {
            $limite = self::getLimitePorRol($rol);

            if ($limite === null) {
                return 'Sin límite de antigüedad';
            }
            if ($limite === 0) {
                return 'No permitido';
            }
            if ($limite === 1) {
                return 'Hasta 1 día atrás';
            }
            return "Hasta {$limite} días atrás";
        }

        // ==========================================
        // CÁLCULOS
        // ==========================================

        /**
         * Calcula la fecha mínima permitida para un rol.
         * Devuelve null si no hay límite.
         */
        public static function getFechaMinimaPermitida($rol) {
            $limite = self::getLimitePorRol($rol);

            if ($limite === null) return null;
            if ($limite === 0) return null;

            return date('Y-m-d', strtotime("-{$limite} days"));
        }

        /**
         * Devuelve la fecha máxima permitida (siempre hoy).
         */
        public static function getFechaMaximaPermitida() {
            return date('Y-m-d');
        }

        /**
         * Calcula los días de diferencia entre dos fechas.
         * Positivo si $fecha1 > $fecha2.
         */
        public static function calcularDiasDiferencia($fecha1, $fecha2) {
            try {
                $dt1 = new DateTime($fecha1);
                $dt2 = new DateTime($fecha2);
                $diff = $dt1->diff($dt2);
                $dias = (int)$diff->days;
                return $dt1 > $dt2 ? $dias : -$dias;
            } catch (Throwable $e) {
                error_log("Error en calcularDiasDiferencia: " . $e->getMessage());
                return 0;
            }
        }

        /**
         * Determina si un cambio de fecha es retroactivo.
         * Un cambio es retroactivo si la nueva fecha es MENOR que la original.
         */
        public static function esCambioRetroactivo($fecha_original, $fecha_nueva) {
            try {
                $ts_original = strtotime($fecha_original);
                $ts_nueva = strtotime($fecha_nueva);

                if ($ts_original === false || $ts_nueva === false) {
                    return false;
                }

                return $ts_nueva < $ts_original;

            } catch (Throwable $e) {
                error_log("Error en esCambioRetroactivo: " . $e->getMessage());
                return false;
            }
        }

        // ==========================================
        // REGISTRO EN AUDITORÍA
        // ==========================================

        /**
         * Prepara los datos de backdating para ser registrados.
         * NO guarda en BD, solo arma el array.
         */
        public static function prepararRegistro($datos) {
            return [
                'tipo'                    => $datos['tipo'] ?? 'mantenimiento_preventivo',
                'referencia_id'           => $datos['referencia_id'] ?? null,
                'mantenimiento_id'        => $datos['mantenimiento_id'] ?? null,
                'fecha_original'          => $datos['fecha_original'] ?? date('Y-m-d H:i:s'),
                'fecha_nueva'             => $datos['fecha_nueva'] ?? null,
                'fecha_limite_permitida'  => $datos['fecha_limite_permitida'] ?? null,
                'motivo'                  => trim($datos['motivo'] ?? ''),
                'solicitado_por'          => $datos['solicitado_por'] ?? null,
                'rol_solicitante'         => $datos['rol_solicitante'] ?? null,
                'estado'                  => $datos['estado'] ?? 'pendiente',
            ];
        }

        // ==========================================
        // UTILIDADES
        // ==========================================

        /**
         * Devuelve un resumen legible del cambio de fecha.
         */
        public static function getResumenCambio($fecha_original, $fecha_nueva) {
            try {
                $dias = self::calcularDiasDiferencia($fecha_original, $fecha_nueva);

                $direccion = $dias < 0 ? 'retroactivo' : ($dias > 0 ? 'futuro' : 'sin cambio');

                return [
                    'dias'      => abs($dias),
                    'direccion' => $direccion,
                    'original'  => date('d/m/Y H:i', strtotime($fecha_original)),
                    'nueva'     => date('d/m/Y H:i', strtotime($fecha_nueva)),
                    'texto'     => abs($dias) . ' día(s) ' . $direccion,
                ];

            } catch (Throwable $e) {
                error_log("Error en getResumenCambio: " . $e->getMessage());
                return [
                    'dias'      => 0,
                    'direccion' => 'desconocido',
                    'original'  => $fecha_original,
                    'nueva'     => $fecha_nueva,
                    'texto'     => 'Error al calcular',
                ];
            }
        }

        /**
         * Devuelve el listado de roles permitidos (útil para selects).
         */
        public static function getRolesPermitidos() {
            return self::$roles_permitidos;
        }

        /**
         * Devuelve el mapa completo de límites (útil para documentación).
         */
        public static function getLimitesCompletos() {
            return self::$limites_por_rol;
        }
    }
}