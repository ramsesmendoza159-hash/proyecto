<?php
// model/UsuariosModel.php
// Modelo de usuarios del sistema
// ✅ FIX 1: catch (Throwable) en autenticar()
// ✅ FIX 2: obtenerPorId() incluye firma_svg
// ✅ FIX 3: actualizar() valida email duplicado
// ✅ FIX 4: cambiarPassword() valida largo mínimo
// ✅ FIX 5: agregados crear(), eliminar(), obtenerTodos(), cambiarEstado()
// ✅ FIX 6: emailExiste() acepta $rol opcional
// ✅ FIX 7: agregado buscar()
// ✅ FIX 8: agregado obtenerTodosConFiltros()

require_once __DIR__ . '/../config/database.php';

class UsuariosModel {
    private $db;

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Throwable $e) {
            error_log("Error al conectar a la base de datos (UsuariosModel): " . $e->getMessage());
            throw $e;
        }
    }

    // ==========================================
    // AUTENTICACIÓN
    // ==========================================

    public function autenticar($email, $password) {
        try {
            $sql = "SELECT id, nombre, email, password_hash, rol, estado 
                    FROM usuarios 
                    WHERE email = :email";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                error_log("Usuario no encontrado: " . $email);
                return false;
            }
            
            if ($usuario['estado'] !== 'activo') {
                error_log("Usuario inactivo: " . $email);
                return false;
            }
            
            if (!password_verify($password, $usuario['password_hash'])) {
                error_log("Contraseña incorrecta para: " . $email);
                return false;
            }
            
            unset($usuario['password_hash']);
            error_log("Autenticación exitosa para: " . $email);
            return $usuario;
            
        } catch (PDOException $e) {
            error_log("Error en UsuariosModel::autenticar (PDO): " . $e->getMessage());
            return false;
        } catch (Throwable $e) {
            // ✅ FIX 1: Throwable en vez de Exception
            error_log("Error en UsuariosModel::autenticar: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // OBTENER
    // ==========================================

    /**
     * ✅ FIX 2: incluye firma_svg.
     */
    public function obtenerPorId($id) {
        try {
            if (!is_numeric($id) || (int)$id <= 0) {
                error_log("UsuariosModel::obtenerPorId - ID inválido: $id");
                return null;
            }
            
            $sql = "SELECT id, nombre, email, rol, estado, 
                           fecha_creacion, fecha_actualizacion, ultima_conexion,
                           firma_svg
                    FROM usuarios 
                    WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => (int)$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                error_log("UsuariosModel::obtenerPorId - Usuario con ID $id no encontrado");
                return null;
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Error en UsuariosModel::obtenerPorId: " . $e->getMessage());
            return null;
        }
    }

    public function obtenerPorEmail($email) {
        try {
            $sql = "SELECT id, nombre, email, rol, estado, fecha_creacion, ultima_conexion
                    FROM usuarios WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en UsuariosModel::obtenerPorEmail: " . $e->getMessage());
            return null;
        }
    }

    /**
     * ✅ FIX 5: agregado listado general.
     */
    public function obtenerTodos($filtros = []) {
        try {
            $sql = "SELECT id, nombre, email, rol, estado, fecha_creacion, ultima_conexion
                    FROM usuarios WHERE 1=1";
            $params = [];

            if (!empty($filtros['rol'])) {
                $sql .= " AND rol = ?";
                $params[] = $filtros['rol'];
            }

            if (!empty($filtros['estado'])) {
                $sql .= " AND estado = ?";
                $params[] = $filtros['estado'];
            }

            if (!empty($filtros['buscar'])) {
                $sql .= " AND (nombre LIKE ? OR email LIKE ?)";
                $buscar = '%' . $filtros['buscar'] . '%';
                $params[] = $buscar;
                $params[] = $buscar;
            }

            $sql .= " ORDER BY nombre ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en UsuariosModel::obtenerTodos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * ✅ FIX 8: alias semántico.
     */
    public function obtenerTodosConFiltros($filtros = []) {
        return $this->obtenerTodos($filtros);
    }

    /**
     * ✅ FIX 7: agregado buscar() para autocompletar.
     */
    public function buscar($termino) {
        try {
            $sql = "SELECT id, nombre, email, rol 
                    FROM usuarios 
                    WHERE (nombre LIKE ? OR email LIKE ?) 
                    AND estado = 'activo'
                    ORDER BY nombre ASC
                    LIMIT 10";
            $buscar = '%' . $termino . '%';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$buscar, $buscar]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error en UsuariosModel::buscar: " . $e->getMessage());
            return [];
        }
    }

    // ==========================================
    // VALIDACIONES
    // ==========================================

    /**
     * ✅ FIX 6: acepta $rol opcional.
     */
    public function emailExiste($email, $excluirId = null, $rol = null) {
        try {
            $sql = "SELECT COUNT(*) as total FROM usuarios WHERE email = :email";
            $params = [':email' => $email];

            if ($excluirId !== null) {
                $sql .= " AND id != :id";
                $params[':id'] = (int)$excluirId;
            }

            // ✅ FIX 6: filtrar por rol si se especifica
            if ($rol !== null) {
                $sql .= " AND rol = :rol";
                $params[':rol'] = $rol;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return ($result['total'] ?? 0) > 0;

        } catch (PDOException $e) {
            error_log("Error en UsuariosModel::emailExiste: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // CREAR
    // ==========================================

    /**
     * ✅ FIX 5: agregado crear().
     * Acepta 'password' (hashea) o 'password_hash' (ya hasheado).
     */
    public function crear($datos) {
        try {
            if (empty($datos['nombre']) || empty($datos['email']) || empty($datos['rol'])) {
                error_log("UsuariosModel::crear - Datos incompletos");
                return false;
            }

            if ($this->emailExiste($datos['email'])) {
                error_log("UsuariosModel::crear - Email ya registrado");
                return false;
            }

            // Determinar hash
            $passwordHash = '';
            if (!empty($datos['password_hash'])) {
                $passwordHash = $datos['password_hash'];
            } elseif (!empty($datos['password'])) {
                $passwordHash = password_hash($datos['password'], PASSWORD_DEFAULT);
            } else {
                error_log("UsuariosModel::crear - Sin contraseña");
                return false;
            }

            $sql = "INSERT INTO usuarios (nombre, email, password_hash, rol, estado, fecha_creacion)
                    VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $datos['nombre'],
                $datos['email'],
                $passwordHash,
                $datos['rol'],
                $datos['estado'] ?? 'activo'
            ]);

            if ($result) {
                return (int)$this->db->lastInsertId();
            }

            return false;

        } catch (PDOException $e) {
            error_log("Error en UsuariosModel::crear: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // ACTUALIZAR
    // ==========================================

    /**
     * ✅ FIX 3: valida email duplicado.
     */
    public function actualizar($id, $datos) {
        try {
            $id = (int)$id;

            // ✅ FIX 3: validar email duplicado si cambia
            if (!empty($datos['email'])) {
                if ($this->emailExiste($datos['email'], $id)) {
                    error_log("UsuariosModel::actualizar - Email ya registrado: " . $datos['email']);
                    return false;
                }
            }

            $sql = "UPDATE usuarios SET 
                        nombre = :nombre,
                        email = :email,
                        fecha_actualizacion = NOW()";
            
            $params = [
                ':nombre' => $datos['nombre'] ?? '',
                ':email' => $datos['email'] ?? '',
                ':id' => $id
            ];
            
            if (!empty($datos['password'])) {
                $sql .= ", password_hash = :password_hash";
                $params[':password_hash'] = password_hash($datos['password'], PASSWORD_DEFAULT);
            }

            if (!empty($datos['rol'])) {
                $sql .= ", rol = :rol";
                $params[':rol'] = $datos['rol'];
            }

            if (isset($datos['estado'])) {
                $sql .= ", estado = :estado";
                $params[':estado'] = $datos['estado'];
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);

        } catch (PDOException $e) {
            error_log("Error en UsuariosModel::actualizar: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // ELIMINAR
    // ==========================================

    /**
     * ✅ FIX 5: agregado eliminar().
     */
    public function eliminar($id) {
        try {
            $sql = "DELETE FROM usuarios WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([(int)$id]);

        } catch (PDOException $e) {
            error_log("Error en UsuariosModel::eliminar: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // CAMBIAR ESTADO
    // ==========================================

    /**
     * ✅ FIX 5: agregado cambiarEstado() con validación.
     */
    public function cambiarEstado($id, $estado) {
        try {
            if (!in_array($estado, ['activo', 'inactivo'], true)) {
                error_log("UsuariosModel::cambiarEstado - Estado inválido: $estado");
                return false;
            }

            $sql = "UPDATE usuarios SET estado = ?, fecha_actualizacion = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$estado, (int)$id]);

        } catch (PDOException $e) {
            error_log("Error en UsuariosModel::cambiarEstado: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // CAMBIAR CONTRASEÑA
    // ==========================================

    /**
     * ✅ FIX 4: valida largo mínimo.
     */
    public function cambiarPassword($id, $password) {
        try {
            // ✅ FIX 4: validar largo mínimo
            if (empty($password) || strlen($password) < 6) {
                error_log("UsuariosModel::cambiarPassword - Contraseña muy corta");
                return false;
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET password_hash = :password_hash, fecha_actualizacion = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':password_hash' => $passwordHash,
                ':id' => (int)$id
            ]);

        } catch (PDOException $e) {
            error_log("Error en UsuariosModel::cambiarPassword: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // ACTUALIZAR ÚLTIMA CONEXIÓN
    // ==========================================

    public function actualizarUltimaConexion($id) {
        try {
            $sql = "UPDATE usuarios SET ultima_conexion = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([(int)$id]);
        } catch (PDOException $e) {
            error_log("Error en UsuariosModel::actualizarUltimaConexion: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // FIRMA
    // ==========================================

    public function actualizarFirma($id, $firma_svg) {
        try {
            $sql = "UPDATE usuarios SET firma_svg = :firma_svg WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':firma_svg' => $firma_svg,
                ':id' => (int)$id
            ]);
        } catch (PDOException $e) {
            error_log("Error en UsuariosModel::actualizarFirma: " . $e->getMessage());
            return false;
        }
    }
}