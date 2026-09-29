<?php
// model/Tecnico.php
// Modelo de Técnicos
// ✅ FIX 1: eliminar() valida que no tenga órdenes asignadas
// ✅ FIX 2: actualizar() acepta 'password' o 'password_hash' (consistencia con crear())
// ✅ FIX 3: obtenerEstadisticas() incluye total_ordenes
// ✅ FIX 4: agregado cambiarPassword() que hashea internamente
// ✅ FIX 5: catch (Throwable) en vez de Exception

require_once __DIR__ . '/../config/database.php';

class Tecnico {
    private $db;

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Throwable $e) {
            error_log("Error al conectar a la base de datos (Tecnico): " . $e->getMessage());
            throw $e;
        }
    }

    // ==========================================
    // LISTADO
    // ==========================================

    public function obtenerTodos($filtros = []) {
        try {
            $sql = "SELECT id, nombre, email, telefono, especialidad, tarifa, estado,
                           fecha_creacion, fecha_actualizacion
                    FROM tecnicos WHERE 1=1";
            $params = [];

            if (!empty($filtros['estado'])) {
                $sql .= " AND estado = ?";
                $params[] = $filtros['estado'];
            }

            if (!empty($filtros['especialidad'])) {
                $sql .= " AND especialidad = ?";
                $params[] = $filtros['especialidad'];
            }

            if (!empty($filtros['buscar'])) {
                $sql .= " AND (nombre LIKE ? OR email LIKE ? OR telefono LIKE ?)";
                $buscar = '%' . $filtros['buscar'] . '%';
                $params[] = $buscar;
                $params[] = $buscar;
                $params[] = $buscar;
            }

            $sql .= " ORDER BY nombre ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en Tecnico::obtenerTodos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerPorId($id) {
        try {
            $sql = "SELECT id, nombre, email, telefono, especialidad, tarifa, estado,
                           fecha_creacion, fecha_actualizacion
                    FROM tecnicos WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en Tecnico::obtenerPorId: " . $e->getMessage());
            return null;
        }
    }

    public function obtenerPorEmail($email) {
        try {
            $sql = "SELECT * FROM tecnicos WHERE LOWER(email) = LOWER(?) LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Tecnico::obtenerPorEmail: " . $e->getMessage());
            return null;
        }
    }

    public function obtenerTarifa($id) {
        try {
            $sql = "SELECT tarifa FROM tecnicos WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (float)$result['tarifa'] : 0;
        } catch (PDOException $e) {
            error_log("Error en Tecnico::obtenerTarifa: " . $e->getMessage());
            return 0;
        }
    }

    // ==========================================
    // CREAR
    // ==========================================

    /**
     * Crea un nuevo técnico.
     * Acepta 'password' (hashea) o 'password_hash' (ya hasheado).
     *
     * @param array $datos
     * @return int|false ID del técnico o false si falla
     */
    public function crear($datos) {
        try {
            if (empty($datos['nombre'])) {
                error_log("Tecnico::crear - Nombre vacío");
                return false;
            }
            if (empty($datos['email'])) {
                error_log("Tecnico::crear - Email vacío");
                return false;
            }

            // Determinar el hash de la contraseña
            $passwordHash = '';
            if (!empty($datos['password_hash'])) {
                $passwordHash = $datos['password_hash'];
            } elseif (!empty($datos['password'])) {
                $passwordHash = password_hash($datos['password'], PASSWORD_DEFAULT);
            } else {
                error_log("Tecnico::crear - Sin contraseña");
                return false;
            }

            // Verificar email duplicado
            if ($this->emailExiste($datos['email'])) {
                error_log("Tecnico::crear - Email ya registrado: " . $datos['email']);
                return false;
            }

            $sql = "INSERT INTO tecnicos (
                        nombre, email, telefono, especialidad, tarifa,
                        password_hash, estado, fecha_creacion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $datos['nombre'],
                $datos['email'],
                $datos['telefono'] ?? null,
                $datos['especialidad'] ?? null,
                $datos['tarifa'] ?? 0,
                $passwordHash,
                $datos['estado'] ?? 'activo'
            ]);

            if ($result) {
                return (int)$this->db->lastInsertId();
            }

            return false;

        } catch (PDOException $e) {
            error_log("Error en Tecnico::crear: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // ACTUALIZAR
    // ==========================================

    /**
     * Actualiza un técnico.
     * ✅ FIX 2: acepta 'password' o 'password_hash' (consistencia con crear()).
     */
    public function actualizar($id, $datos) {
        try {
            $id = (int)$id;
            $tecnico = $this->obtenerPorId($id);
            if (!$tecnico) {
                error_log("Tecnico::actualizar - Técnico no encontrado: $id");
                return false;
            }

            if (!empty($datos['email']) && $datos['email'] !== $tecnico['email']) {
                if ($this->emailExiste($datos['email'], $id)) {
                    error_log("Tecnico::actualizar - Email ya registrado: " . $datos['email']);
                    return false;
                }
            }

            // ✅ FIX 2: acepta password_hash directo o password en texto plano
            $passwordHash = null;
            if (!empty($datos['password_hash'])) {
                $passwordHash = $datos['password_hash'];
            } elseif (!empty($datos['password'])) {
                $passwordHash = password_hash($datos['password'], PASSWORD_DEFAULT);
            }

            if ($passwordHash !== null) {
                $sql = "UPDATE tecnicos SET 
                            nombre = ?,
                            email = ?,
                            telefono = ?,
                            especialidad = ?,
                            tarifa = ?,
                            password_hash = ?,
                            estado = ?,
                            fecha_actualizacion = NOW()
                        WHERE id = ?";

                $stmt = $this->db->prepare($sql);
                return $stmt->execute([
                    $datos['nombre'] ?? $tecnico['nombre'],
                    $datos['email'] ?? $tecnico['email'],
                    $datos['telefono'] ?? $tecnico['telefono'],
                    $datos['especialidad'] ?? $tecnico['especialidad'],
                    $datos['tarifa'] ?? $tecnico['tarifa'],
                    $passwordHash,
                    $datos['estado'] ?? $tecnico['estado'],
                    $id
                ]);
            }

            // Sin cambio de contraseña
            $sql = "UPDATE tecnicos SET 
                        nombre = ?,
                        email = ?,
                        telefono = ?,
                        especialidad = ?,
                        tarifa = ?,
                        estado = ?,
                        fecha_actualizacion = NOW()
                    WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $datos['nombre'] ?? $tecnico['nombre'],
                $datos['email'] ?? $tecnico['email'],
                $datos['telefono'] ?? $tecnico['telefono'],
                $datos['especialidad'] ?? $tecnico['especialidad'],
                $datos['tarifa'] ?? $tecnico['tarifa'],
                $datos['estado'] ?? $tecnico['estado'],
                $id
            ]);

        } catch (PDOException $e) {
            error_log("Error en Tecnico::actualizar: " . $e->getMessage());
            return false;
        }
    }

    public function actualizarPassword($id, $password_hash) {
        try {
            $sql = "UPDATE tecnicos SET 
                        password_hash = ?,
                        fecha_actualizacion = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$password_hash, (int)$id]);

        } catch (PDOException $e) {
            error_log("Error en Tecnico::actualizarPassword: " . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ FIX 4: cambia la contraseña hasheando internamente.
     * Más ergonómico que actualizarPassword().
     */
    public function cambiarPassword($id, $password_plano) {
        try {
            if (empty($password_plano)) {
                return false;
            }
            $hash = password_hash($password_plano, PASSWORD_DEFAULT);
            return $this->actualizarPassword($id, $hash);
        } catch (Throwable $e) {
            error_log("Error en Tecnico::cambiarPassword: " . $e->getMessage());
            return false;
        }
    }

    public function cambiarEstado($id, $estado) {
        try {
            $sql = "UPDATE tecnicos SET 
                        estado = ?,
                        fecha_actualizacion = NOW()
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$estado, (int)$id]);

        } catch (PDOException $e) {
            error_log("Error en Tecnico::cambiarEstado: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // ELIMINAR
    // ==========================================

    /**
     * Elimina un técnico.
     * ✅ FIX 1: valida que no tenga órdenes asignadas antes de eliminar.
     */
    public function eliminar($id) {
        try {
            $id = (int)$id;

            // ✅ FIX 1: validar órdenes asignadas
            $sqlCheck = "SELECT COUNT(*) as total FROM ordenes_mantenimiento WHERE tecnico_id = ?";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([$id]);
            $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (($result['total'] ?? 0) > 0) {
                error_log("Tecnico::eliminar - Técnico ID $id tiene " . $result['total'] . " órdenes asignadas");
                return false;
            }

            // Validar también técnicos adicionales
            $sqlCheck2 = "SELECT COUNT(*) as total FROM ordenes_tecnicos WHERE tecnico_id = ?";
            $stmtCheck2 = $this->db->prepare($sqlCheck2);
            $stmtCheck2->execute([$id]);
            $result2 = $stmtCheck2->fetch(PDO::FETCH_ASSOC);

            if (($result2['total'] ?? 0) > 0) {
                error_log("Tecnico::eliminar - Técnico ID $id tiene " . $result2['total'] . " asignaciones adicionales");
                return false;
            }

            $sql = "DELETE FROM tecnicos WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);

        } catch (PDOException $e) {
            error_log("Error en Tecnico::eliminar: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // VALIDACIONES
    // ==========================================

    public function emailExiste($email, $excluirId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM tecnicos WHERE LOWER(email) = LOWER(?)";
            $params = [$email];

            if ($excluirId !== null) {
                $sql .= " AND id != ?";
                $params[] = (int)$excluirId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn() > 0;

        } catch (PDOException $e) {
            error_log("Error en Tecnico::emailExiste: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // ESTADÍSTICAS
    // ==========================================

    /**
     * ✅ FIX 3: incluye total_ordenes (antes lo calculaba el controlador).
     */
    public function obtenerEstadisticas() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                        SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos,
                        COUNT(DISTINCT especialidad) as especialidades,
                        AVG(tarifa) as tarifa_promedio
                    FROM tecnicos";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // ✅ FIX 3: contar órdenes únicas asignadas a técnicos
            $sqlOrdenes = "SELECT COUNT(DISTINCT id) as total_ordenes 
                           FROM ordenes_mantenimiento 
                           WHERE tecnico_id IS NOT NULL";
            $stmtOrdenes = $this->db->prepare($sqlOrdenes);
            $stmtOrdenes->execute();
            $resultOrdenes = $stmtOrdenes->fetch(PDO::FETCH_ASSOC);

            return [
                'total' => (int)($result['total'] ?? 0),
                'activos' => (int)($result['activos'] ?? 0),
                'inactivos' => (int)($result['inactivos'] ?? 0),
                'especialidades' => (int)($result['especialidades'] ?? 0),
                'tarifa_promedio' => (float)($result['tarifa_promedio'] ?? 0),
                'total_ordenes' => (int)($resultOrdenes['total_ordenes'] ?? 0)
            ];

        } catch (PDOException $e) {
            error_log("Error en Tecnico::obtenerEstadisticas: " . $e->getMessage());
            return [
                'total' => 0, 'activos' => 0, 'inactivos' => 0,
                'especialidades' => 0, 'tarifa_promedio' => 0, 'total_ordenes' => 0
            ];
        }
    }

    public function obtenerTotal() {
        try {
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM tecnicos");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error en Tecnico::obtenerTotal: " . $e->getMessage());
            return 0;
        }
    }

    public function obtenerTarifaPromedio() {
        try {
            $sql = "SELECT AVG(tarifa) as promedio FROM tecnicos WHERE estado = 'activo'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float)($result['promedio'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error en Tecnico::obtenerTarifaPromedio: " . $e->getMessage());
            return 0;
        }
    }

    // ==========================================
    // CONSULTAS FILTRADAS
    // ==========================================

    public function obtenerPorEspecialidad($especialidad) {
        try {
            $sql = "SELECT id, nombre, email, telefono, tarifa, estado 
                    FROM tecnicos 
                    WHERE especialidad = ? AND estado = 'activo'
                    ORDER BY nombre ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$especialidad]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en Tecnico::obtenerPorEspecialidad: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerActivos() {
        return $this->obtenerTodos(['estado' => 'activo']);
    }

    public function obtenerEspecialidades() {
        try {
            $sql = "SELECT DISTINCT especialidad FROM tecnicos 
                    WHERE especialidad IS NOT NULL AND especialidad != ''
                    ORDER BY especialidad ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);

        } catch (PDOException $e) {
            error_log("Error en Tecnico::obtenerEspecialidades: " . $e->getMessage());
            return [];
        }
    }

    public function buscar($termino) {
        try {
            $sql = "SELECT * FROM tecnicos 
                    WHERE (nombre LIKE ? OR email LIKE ?) 
                    AND estado = 'activo'
                    ORDER BY nombre ASC
                    LIMIT 10";
            $buscar = '%' . $termino . '%';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$buscar, $buscar]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Tecnico::buscar: " . $e->getMessage());
            return [];
        }
    }

    // ==========================================
    // CON ÓRDENES
    // ==========================================

    public function obtenerTodosConOrdenes($limit = null, $offset = null) {
        try {
            $sql = "SELECT t.*, COUNT(om.id) as total_ordenes 
                    FROM tecnicos t 
                    LEFT JOIN ordenes_mantenimiento om ON t.id = om.tecnico_id 
                    GROUP BY t.id 
                    ORDER BY t.nombre ASC";

            if ($limit !== null && $offset !== null) {
                $sql .= " LIMIT :limit OFFSET :offset";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
            } else {
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Tecnico::obtenerTodosConOrdenes: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerTopTecnicos($limite = 5) {
        try {
            $sql = "SELECT 
                        t.id,
                        t.nombre,
                        t.especialidad,
                        COUNT(om.id) as total_ordenes,
                        SUM(CASE WHEN om.status IN ('CERRADA', 'APROBADA', 'EJECUTADA') THEN 1 ELSE 0 END) as completadas,
                        ROUND(SUM(CASE WHEN om.status IN ('CERRADA', 'APROBADA', 'EJECUTADA') THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(om.id), 0), 1) as eficiencia
                    FROM tecnicos t
                    LEFT JOIN ordenes_mantenimiento om ON t.id = om.tecnico_id
                    WHERE t.estado = 'activo'
                    GROUP BY t.id
                    HAVING total_ordenes > 0
                    ORDER BY completadas DESC, eficiencia DESC
                    LIMIT :limite";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Tecnico::obtenerTopTecnicos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerOrdenesPorTecnico($tecnicoId, $limit = null, $offset = null) {
        try {
            $sql = "SELECT om.*, 
                           p.nombre_planta,
                           a.nombre_area,
                           e.nombre_equipo,
                           c.nombre_componente
                    FROM ordenes_mantenimiento om
                    LEFT JOIN plantas p ON om.id_planta = p.id_planta
                    LEFT JOIN areas a ON om.id_area = a.id_area
                    LEFT JOIN equipos e ON om.id_equipo = e.id_equipo
                    LEFT JOIN componentes c ON om.id_componente = c.id_componente
                    WHERE om.tecnico_id = :tecnico_id
                    ORDER BY om.fecha_creacion DESC";

            if ($limit !== null && $offset !== null) {
                $sql .= " LIMIT :limit OFFSET :offset";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':tecnico_id', $tecnicoId, PDO::PARAM_INT);
                $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
            } else {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([':tecnico_id' => $tecnicoId]);
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en Tecnico::obtenerOrdenesPorTecnico: " . $e->getMessage());
            return [];
        }
    }
}