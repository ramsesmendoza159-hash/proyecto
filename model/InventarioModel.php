<?php
// model/InventarioModel.php
// VERSIÓN COMPLETA CORREGIDA
// ✅ FIX: Filtro de stock usa stock_minimo por producto, no rangos fijos
// ✅ FIX: catch (Throwable) en vez de Exception

require_once __DIR__ . '/../config/database.php';

class InventarioModel {
    private $db;
    private $lastError;

    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Throwable $e) {
            error_log("Error al conectar a la base de datos (Inventario): " . $e->getMessage());
            throw $e;
        }
    }

    public function getLastError() {
        return $this->lastError;
    }

    public function obtenerTodos($filtros = []) {
        try {
            $sql = "SELECT id, codigo, nombre, descripcion, descripcion_tecnica, categoria, cantidad, 
                           precio_unitario, unidad_medida, stock_minimo, stock_maximo,
                           ubicacion, estado, proveedor, fecha_creacion, fecha_actualizacion,
                           fecha_ultima_entrada, dias_permanencia
                    FROM inventario WHERE 1=1";
            $params = [];

            if (!empty($filtros['estado'])) {
                $sql .= " AND estado = ?";
                $params[] = $filtros['estado'];
            }

            if (!empty($filtros['categoria'])) {
                $sql .= " AND categoria = ?";
                $params[] = $filtros['categoria'];
            }

            if (!empty($filtros['buscar'])) {
                $sql .= " AND (nombre LIKE ? OR codigo LIKE ? OR descripcion LIKE ? OR descripcion_tecnica LIKE ?)";
                $buscar = '%' . $filtros['buscar'] . '%';
                $params[] = $buscar;
                $params[] = $buscar;
                $params[] = $buscar;
                $params[] = $buscar;
            }

            if (!empty($filtros['stock'])) {
                switch ($filtros['stock']) {
                    case 'bajo':
                        $sql .= " AND cantidad > 0 AND cantidad <= COALESCE(NULLIF(stock_minimo, 0), 5)";
                        break;
                    case 'medio':
                        $sql .= " AND cantidad > COALESCE(NULLIF(stock_minimo, 0), 5) AND cantidad <= 20";
                        break;
                    case 'alto':
                        $sql .= " AND cantidad > 20";
                        break;
                    case 'agotado':
                        $sql .= " AND cantidad = 0";
                        break;
                }
            }

            if (!empty($filtros['precio_desde']) && is_numeric($filtros['precio_desde'])) {
                $sql .= " AND precio_unitario >= ?";
                $params[] = (float)$filtros['precio_desde'];
            }

            if (!empty($filtros['precio_hasta']) && is_numeric($filtros['precio_hasta'])) {
                $sql .= " AND precio_unitario <= ?";
                $params[] = (float)$filtros['precio_hasta'];
            }

            if (!empty($filtros['ubicacion'])) {
                $sql .= " AND ubicacion LIKE ?";
                $params[] = '%' . $filtros['ubicacion'] . '%';
            }

            if (!empty($filtros['proveedor'])) {
                $sql .= " AND proveedor = ?";
                $params[] = $filtros['proveedor'];
            }

            if (!empty($filtros['fecha_desde'])) {
                $sql .= " AND fecha_creacion >= ?";
                $params[] = $filtros['fecha_desde'] . ' 00:00:00';
            }
            if (!empty($filtros['fecha_hasta'])) {
                $sql .= " AND fecha_creacion <= ?";
                $params[] = $filtros['fecha_hasta'] . ' 23:59:59';
            }

            if (!empty($filtros['orden'])) {
                switch ($filtros['orden']) {
                    case 'nombre': $sql .= " ORDER BY nombre ASC"; break;
                    case 'precio': $sql .= " ORDER BY precio_unitario ASC"; break;
                    case 'stock': $sql .= " ORDER BY cantidad ASC"; break;
                    case 'fecha_creacion': $sql .= " ORDER BY fecha_creacion DESC"; break;
                    default: $sql .= " ORDER BY nombre ASC"; break;
                }
            } else {
                $sql .= " ORDER BY nombre ASC";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($result as &$item) {
                $item['tipo'] = $item['categoria'] ?? 'N/A';
                $item['activo'] = ($item['estado'] ?? 'inactivo') === 'activo' ? 1 : 0;
                if (empty($item['unidad_medida'])) {
                    $item['unidad_medida'] = 'Unidad';
                }
                $item['unidad'] = $item['unidad_medida'];
            }

            return $result;

        } catch (PDOException $e) {
            error_log("Error en InventarioModel::obtenerTodos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerPorId($id) {
        try {
            $sql = "SELECT * FROM inventario WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $result['tipo'] = $result['categoria'] ?? 'N/A';
                $result['activo'] = ($result['estado'] ?? 'inactivo') === 'activo' ? 1 : 0;
                if (empty($result['unidad_medida'])) {
                    $result['unidad_medida'] = 'Unidad';
                }
                $result['unidad'] = $result['unidad_medida'];
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Error en InventarioModel::obtenerPorId: " . $e->getMessage());
            return null;
        }
    }

    public function crear($datos) {
        try {
            if (empty($datos['nombre'])) {
                $this->lastError = 'El nombre es obligatorio';
                return false;
            }

            if (empty($datos['categoria'])) {
                $this->lastError = 'La categoría es obligatoria';
                return false;
            }

            $sql = "INSERT INTO inventario (
                        codigo, nombre, descripcion, descripcion_tecnica, categoria, 
                        cantidad, precio_unitario, unidad_medida, stock_minimo, stock_maximo,
                        ubicacion, estado, proveedor, fecha_creacion, fecha_ultima_entrada, dias_permanencia
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), 0)";

            $stmt = $this->db->prepare($sql);

            $result = $stmt->execute([
                !empty($datos['codigo']) ? $datos['codigo'] : null,
                $datos['nombre'],
                $datos['descripcion'] ?? '',
                $datos['descripcion_tecnica'] ?? '',
                $datos['categoria'],
                (int)($datos['cantidad'] ?? 0),
                (float)($datos['precio_unitario'] ?? 0),
                !empty($datos['unidad_medida']) ? $datos['unidad_medida'] : (!empty($datos['unidad']) ? $datos['unidad'] : 'Unidad'),
                (int)($datos['stock_minimo'] ?? 0),
                !empty($datos['stock_maximo']) ? (int)$datos['stock_maximo'] : null,
                $datos['ubicacion'] ?? '',
                $datos['estado'] ?? 'activo',
                $datos['proveedor'] ?? ''
            ]);

            if ($result) {
                $lastId = (int)$this->db->lastInsertId();
                if ((int)($datos['cantidad'] ?? 0) > 0) {
                    $this->agregarMovimiento($lastId, 'entrada', (int)$datos['cantidad']);
                }
                return $lastId;
            }

            return false;

        } catch (PDOException $e) {
            error_log("Error en InventarioModel::crear: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function actualizar($id, $datos) {
        try {
            if (empty($datos['nombre'])) {
                $this->lastError = 'El nombre es obligatorio';
                return false;
            }

            if (empty($datos['categoria'])) {
                $this->lastError = 'La categoría es obligatoria';
                return false;
            }

            $itemActual = $this->obtenerPorId($id);
            $cantidadAnterior = $itemActual ? (int)$itemActual['cantidad'] : 0;
            $cantidadNueva = (int)($datos['cantidad'] ?? 0);

            $sql = "UPDATE inventario SET 
                        codigo = ?, nombre = ?, descripcion = ?, descripcion_tecnica = ?, 
                        categoria = ?, cantidad = ?, precio_unitario = ?, unidad_medida = ?, 
                        stock_minimo = ?, stock_maximo = ?, ubicacion = ?, estado = ?, 
                        proveedor = ?, fecha_actualizacion = NOW()
                    WHERE id = ?";

            $stmt = $this->db->prepare($sql);

            $result = $stmt->execute([
                !empty($datos['codigo']) ? $datos['codigo'] : null,
                $datos['nombre'],
                $datos['descripcion'] ?? '',
                $datos['descripcion_tecnica'] ?? '',
                $datos['categoria'],
                $cantidadNueva,
                (float)($datos['precio_unitario'] ?? 0),
                !empty($datos['unidad_medida']) ? $datos['unidad_medida'] : (!empty($datos['unidad']) ? $datos['unidad'] : 'Unidad'),
                (int)($datos['stock_minimo'] ?? 0),
                !empty($datos['stock_maximo']) ? (int)$datos['stock_maximo'] : null,
                $datos['ubicacion'] ?? '',
                $datos['estado'] ?? 'activo',
                $datos['proveedor'] ?? '',
                (int)$id
            ]);

            if ($result) {
                $diferencia = $cantidadNueva - $cantidadAnterior;
                if ($diferencia != 0) {
                    $tipo = $diferencia > 0 ? 'entrada' : 'salida';
                    $this->agregarMovimiento($id, $tipo, abs($diferencia));
                }
                return true;
            }

            return false;

        } catch (PDOException $e) {
            error_log("Error en InventarioModel::actualizar: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function eliminar($id) {
        try {
            $id = (int)$id;

            $item = $this->obtenerPorId($id);
            if (!$item) {
                $this->lastError = 'El ítem no existe';
                return false;
            }

            $sqlCheck = "SELECT COUNT(*) as total FROM ordenes_repuestos WHERE repuesto_id = ?";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([$id]);
            $result = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (($result['total'] ?? 0) > 0) {
                $this->lastError = 'No se puede eliminar: el ítem está en uso en ' . $result['total'] . ' orden(es)';
                return false;
            }

            $sqlMov = "SELECT COUNT(*) as total FROM inventario_movimientos WHERE inventario_id = ?";
            $stmtMov = $this->db->prepare($sqlMov);
            $stmtMov->execute([$id]);
            $resultMov = $stmtMov->fetch(PDO::FETCH_ASSOC);

            if (($resultMov['total'] ?? 0) > 0) {
                $sqlUpdate = "UPDATE inventario SET estado = 'inactivo', fecha_actualizacion = NOW() WHERE id = ?";
                $stmtUpdate = $this->db->prepare($sqlUpdate);
                $stmtUpdate->execute([$id]);
                $this->lastError = 'El ítem tiene movimientos. Se ha desactivado en lugar de eliminar.';
                return true;
            }

            $sqlDeleteMov = "DELETE FROM inventario_movimientos WHERE inventario_id = ?";
            $stmtDeleteMov = $this->db->prepare($sqlDeleteMov);
            $stmtDeleteMov->execute([$id]);

            $sql = "DELETE FROM inventario WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);

        } catch (PDOException $e) {
            error_log("Error en InventarioModel::eliminar: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function buscar($termino) {
        try {
            $sql = "SELECT id, codigo, nombre, cantidad, precio_unitario, unidad_medida 
                    FROM inventario 
                    WHERE (nombre LIKE ? OR codigo LIKE ? OR descripcion LIKE ?)
                    AND cantidad > 0
                    AND estado = 'activo'
                    ORDER BY nombre ASC
                    LIMIT 15";
            $buscar = '%' . $termino . '%';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$buscar, $buscar, $buscar]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($result as &$item) {
                if (empty($item['unidad_medida'])) {
                    $item['unidad_medida'] = 'Unidad';
                }
                $item['unidad'] = $item['unidad_medida'];
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Error en InventarioModel::buscar: " . $e->getMessage());
            return [];
        }
    }

    public function codigoExiste($codigo, $excluirId = null) {
        try {
            if (empty($codigo)) {
                return false;
            }

            $sql = "SELECT id FROM inventario WHERE codigo = ?";
            $params = [$codigo];

            if ($excluirId) {
                $sql .= " AND id != ?";
                $params[] = (int)$excluirId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch() !== false;

        } catch (PDOException $e) {
            error_log("Error en InventarioModel::codigoExiste: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerEstadisticas() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(cantidad) as total_stock,
                        AVG(precio_unitario) as precio_promedio,
                        SUM(cantidad * precio_unitario) as valor_total,
                        SUM(CASE WHEN cantidad = 0 THEN 1 ELSE 0 END) as agotados,
                        SUM(CASE WHEN cantidad > 0 AND cantidad <= COALESCE(NULLIF(stock_minimo, 0), 5) THEN 1 ELSE 0 END) as stock_bajo,
                        COUNT(DISTINCT categoria) as total_categorias
                    FROM inventario
                    WHERE estado = 'activo'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total' => (int)($result['total'] ?? 0),
                'total_stock' => (int)($result['total_stock'] ?? 0),
                'precio_promedio' => (float)($result['precio_promedio'] ?? 0),
                'valor_total' => (float)($result['valor_total'] ?? 0),
                'agotados' => (int)($result['agotados'] ?? 0),
                'stock_bajo' => (int)($result['stock_bajo'] ?? 0),
                'total_categorias' => (int)($result['total_categorias'] ?? 0)
            ];
        } catch (PDOException $e) {
            error_log("Error en InventarioModel::obtenerEstadisticas: " . $e->getMessage());
            return [
                'total' => 0, 'total_stock' => 0, 'precio_promedio' => 0,
                'valor_total' => 0, 'agotados' => 0, 'stock_bajo' => 0, 'total_categorias' => 0
            ];
        }
    }

    public function obtenerCategorias() {
        try {
            $sql = "SELECT DISTINCT categoria FROM inventario WHERE categoria != '' ORDER BY categoria ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Error en InventarioModel::obtenerCategorias: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerProveedores() {
        try {
            $sql = "SELECT DISTINCT proveedor FROM inventario WHERE proveedor != '' AND proveedor IS NOT NULL ORDER BY proveedor ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Error en InventarioModel::obtenerProveedores: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerStockBajo() {
        try {
            $sql = "SELECT id, codigo, nombre, descripcion, categoria, cantidad, 
                           stock_minimo, stock_maximo, precio_unitario, unidad_medida, ubicacion,
                           proveedor, estado
                    FROM inventario 
                    WHERE estado = 'activo' 
                    AND cantidad > 0
                    AND cantidad <= COALESCE(NULLIF(stock_minimo, 0), 5)
                    ORDER BY cantidad ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($result as &$item) {
                $item['nombre'] = $item['nombre'] ?? 'Sin nombre';
                $item['stock_minimo'] = $item['stock_minimo'] ?? 5;
                $item['unidad'] = $item['unidad_medida'] ?? 'Unidad';
            }

            return $result;

        } catch (PDOException $e) {
            error_log("Error en InventarioModel::obtenerStockBajo: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerMovimientosPorProducto($producto_id) {
        try {
            $sql = "SELECT 
                        im.id, im.inventario_id, im.tipo, im.cantidad, im.fecha,
                        u.nombre as usuario
                    FROM inventario_movimientos im
                    LEFT JOIN usuarios u ON im.usuario_id = u.id
                    WHERE im.inventario_id = ?
                    ORDER BY im.fecha DESC
                    LIMIT 50";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$producto_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en InventarioModel::obtenerMovimientosPorProducto: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerUltimosMovimientos($limite = 10) {
        try {
            $sql = "SELECT 
                        im.id, im.inventario_id, im.tipo, im.cantidad, im.fecha,
                        i.nombre as nombre_producto, i.codigo as codigo_producto,
                        u.nombre as usuario
                    FROM inventario_movimientos im
                    LEFT JOIN inventario i ON im.inventario_id = i.id
                    LEFT JOIN usuarios u ON im.usuario_id = u.id
                    ORDER BY im.fecha DESC
                    LIMIT ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$limite]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($result as &$item) {
                $item['nombre_producto'] = $item['nombre_producto'] ?? 'Producto eliminado';
                $item['usuario'] = $item['usuario'] ?? 'Sistema';
            }

            return $result;

        } catch (PDOException $e) {
            error_log("Error en InventarioModel::obtenerUltimosMovimientos: " . $e->getMessage());
            return [];
        }
    }

    public function agregarMovimiento($inventario_id, $tipo, $cantidad, $usuario_id = null) {
        try {
            if (!$usuario_id) {
                $usuario_id = $_SESSION['usuario_id'] ?? 1;
            }

            $sql = "INSERT INTO inventario_movimientos (
                        inventario_id, tipo, cantidad, usuario_id, fecha
                    ) VALUES (?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                (int)$inventario_id, $tipo, (int)$cantidad, (int)$usuario_id
            ]);

        } catch (PDOException $e) {
            error_log("Error en InventarioModel::agregarMovimiento: " . $e->getMessage());
            return false;
        }
    }

    public function registrarSalida($producto_id, $cantidad, $usuario_id = null) {
        try {
            if (!$usuario_id) {
                $usuario_id = $_SESSION['usuario_id'] ?? 1;
            }

            $this->db->beginTransaction();

            $sql = "SELECT cantidad FROM inventario WHERE id = ? FOR UPDATE";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$producto_id]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }

            if ((int)$producto['cantidad'] < $cantidad) {
                throw new Exception('Stock insuficiente. Disponible: ' . $producto['cantidad']);
            }

            $sql = "UPDATE inventario SET cantidad = cantidad - ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$cantidad, $producto_id]);

            $sqlMov = "INSERT INTO inventario_movimientos (inventario_id, tipo, cantidad, usuario_id, fecha) 
                       VALUES (?, 'salida', ?, ?, NOW())";
            $stmtMov = $this->db->prepare($sqlMov);
            $stmtMov->execute([$producto_id, $cantidad, $usuario_id]);

            $this->db->commit();
            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en InventarioModel::registrarSalida: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function registrarEntradaConPermanencia($producto_id, $cantidad, $usuario_id = null) {
        try {
            if (!$usuario_id) {
                $usuario_id = $_SESSION['usuario_id'] ?? 1;
            }

            $producto = $this->obtenerPorId($producto_id);
            if (!$producto) {
                $this->lastError = 'Producto no encontrado';
                return false;
            }

            $this->db->beginTransaction();

            $sql = "UPDATE inventario SET cantidad = cantidad + ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$cantidad, $producto_id]);

            $sql = "UPDATE inventario SET fecha_ultima_entrada = NOW(), dias_permanencia = 0 WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$producto_id]);

            $sqlMov = "INSERT INTO inventario_movimientos (inventario_id, tipo, cantidad, usuario_id, fecha) 
                       VALUES (?, 'entrada', ?, ?, NOW())";
            $stmtMov = $this->db->prepare($sqlMov);
            $stmtMov->execute([$producto_id, $cantidad, $usuario_id]);

            $this->db->commit();
            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en InventarioModel::registrarEntradaConPermanencia: " . $e->getMessage());
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function calcularDiasPermanencia() {
        try {
            $sql = "UPDATE inventario 
                    SET dias_permanencia = DATEDIFF(NOW(), fecha_ultima_entrada) 
                    WHERE fecha_ultima_entrada IS NOT NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            error_log("Error en InventarioModel::calcularDiasPermanencia: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerProductosConPermanencia($dias_min = 0) {
        try {
            $this->calcularDiasPermanencia();

            $sql = "SELECT 
                        id, codigo, nombre, categoria, cantidad, unidad_medida, 
                        ubicacion, stock_minimo, fecha_ultima_entrada, dias_permanencia,
                        CASE 
                            WHEN dias_permanencia >= 30 THEN 'Critico'
                            WHEN dias_permanencia >= 15 THEN 'Normal'
                            WHEN dias_permanencia >= 7 THEN 'Reciente'
                            ELSE 'Nuevo'
                        END as estado_permanencia
                    FROM inventario 
                    WHERE estado = 'activo'";

            if ($dias_min > 0) {
                $sql .= " AND dias_permanencia >= ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$dias_min]);
            } else {
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
            }

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($result as &$item) {
                $item['unidad'] = $item['unidad_medida'] ?? 'Unidad';
            }

            return $result;

        } catch (PDOException $e) {
            error_log("Error en InventarioModel::obtenerProductosConPermanencia: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerResumenPermanencia() {
        try {
            $this->calcularDiasPermanencia();

            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN dias_permanencia >= 30 THEN 1 ELSE 0 END) as criticos,
                        SUM(CASE WHEN dias_permanencia >= 15 AND dias_permanencia < 30 THEN 1 ELSE 0 END) as normales,
                        SUM(CASE WHEN dias_permanencia >= 7 AND dias_permanencia < 15 THEN 1 ELSE 0 END) as recientes,
                        SUM(CASE WHEN dias_permanencia < 7 THEN 1 ELSE 0 END) as nuevos,
                        AVG(dias_permanencia) as promedio_dias,
                        MAX(dias_permanencia) as max_dias
                    FROM inventario 
                    WHERE estado = 'activo' AND cantidad > 0";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total' => (int)($result['total'] ?? 0),
                'criticos' => (int)($result['criticos'] ?? 0),
                'normales' => (int)($result['normales'] ?? 0),
                'recientes' => (int)($result['recientes'] ?? 0),
                'nuevos' => (int)($result['nuevos'] ?? 0),
                'promedio_dias' => round($result['promedio_dias'] ?? 0, 1),
                'max_dias' => (int)($result['max_dias'] ?? 0)
            ];

        } catch (PDOException $e) {
            error_log("Error en InventarioModel::obtenerResumenPermanencia: " . $e->getMessage());
            return [
                'total' => 0, 'criticos' => 0, 'normales' => 0,
                'recientes' => 0, 'nuevos' => 0, 'promedio_dias' => 0, 'max_dias' => 0
            ];
        }
    }

    public function obtenerTopProductosMasMovidos($limite = 10) {
        try {
            $sql = "SELECT 
                        i.id, i.codigo, i.nombre, i.categoria,
                        i.cantidad as stock_actual, i.unidad_medida, i.ubicacion,
                        COUNT(im.id) as total_movimientos,
                        SUM(CASE WHEN im.tipo = 'entrada' THEN im.cantidad ELSE 0 END) as total_entradas,
                        SUM(CASE WHEN im.tipo = 'salida' THEN im.cantidad ELSE 0 END) as total_salidas,
                        MAX(im.fecha) as ultimo_movimiento
                    FROM inventario_movimientos im
                    JOIN inventario i ON im.inventario_id = i.id
                    WHERE im.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                    AND i.estado = 'activo'
                    GROUP BY im.inventario_id
                    ORDER BY total_movimientos DESC
                    LIMIT ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$limite]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($result as $index => &$item) {
                $item['ranking'] = $index + 1;
                $item['medalla'] = $index === 0 ? '🥇' : ($index === 1 ? '🥈' : ($index === 2 ? '🥉' : ''));
                $item['unidad'] = $item['unidad_medida'] ?? 'Unidad';
            }

            return $result;

        } catch (PDOException $e) {
            error_log("Error en InventarioModel::obtenerTopProductosMasMovidos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerMovimientosPorMes() {
        try {
            $sql = "SELECT 
                        DATE_FORMAT(fecha, '%Y-%m') as mes,
                        DATE_FORMAT(fecha, '%b %Y') as mes_nombre,
                        SUM(CASE WHEN tipo = 'entrada' THEN cantidad ELSE 0 END) as entradas,
                        SUM(CASE WHEN tipo = 'salida' THEN cantidad ELSE 0 END) as salidas,
                        COUNT(*) as total_movimientos
                    FROM inventario_movimientos
                    WHERE fecha >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                    GROUP BY DATE_FORMAT(fecha, '%Y-%m')
                    ORDER BY mes ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en InventarioModel::obtenerMovimientosPorMes: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerProductosMasMovidos($limite = 5) {
        try {
            $sql = "SELECT 
                        i.id, i.nombre, i.codigo, i.cantidad as stock_actual,
                        i.categoria, i.unidad_medida,
                        COUNT(im.id) as total_movimientos,
                        SUM(CASE WHEN im.tipo = 'entrada' THEN im.cantidad ELSE 0 END) as total_entradas,
                        SUM(CASE WHEN im.tipo = 'salida' THEN im.cantidad ELSE 0 END) as total_salidas
                    FROM inventario_movimientos im
                    JOIN inventario i ON im.inventario_id = i.id
                    WHERE im.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    AND i.estado = 'activo'
                    GROUP BY im.inventario_id
                    ORDER BY total_movimientos DESC
                    LIMIT ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$limite]);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($result as &$item) {
                $item['unidad'] = $item['unidad_medida'] ?? 'Unidad';
            }

            return $result;

        } catch (PDOException $e) {
            error_log("Error en InventarioModel::obtenerProductosMasMovidos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerStockPorCategoria() {
        try {
            $sql = "SELECT 
                        categoria,
                        COUNT(*) as total_items,
                        SUM(cantidad) as total_stock,
                        SUM(cantidad * precio_unitario) as valor_total
                    FROM inventario
                    WHERE estado = 'activo' AND categoria != ''
                    GROUP BY categoria
                    ORDER BY total_stock DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en InventarioModel::obtenerStockPorCategoria: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerResumenDashboard() {
        try {
            $estadisticas = $this->obtenerEstadisticas();
            $stockBajo = $this->obtenerStockBajo();
            $ultimosMovimientos = $this->obtenerUltimosMovimientos(5);
            $movimientosPorMes = $this->obtenerMovimientosPorMes();
            $topProductos = $this->obtenerProductosMasMovidos(5);

            return [
                'estadisticas' => $estadisticas,
                'stock_bajo' => $stockBajo,
                'stock_bajo_count' => count($stockBajo),
                'ultimos_movimientos' => $ultimosMovimientos,
                'movimientos_por_mes' => $movimientosPorMes,
                'top_productos' => $topProductos,
                'total_categorias' => count($this->obtenerCategorias()),
                'fecha_actualizacion' => date('Y-m-d H:i:s')
            ];

        } catch (Throwable $e) {
            error_log("Error en InventarioModel::obtenerResumenDashboard: " . $e->getMessage());
            return [
                'estadisticas' => [
                    'total' => 0, 'total_stock' => 0, 'valor_total' => 0,
                    'stock_bajo' => 0, 'agotados' => 0, 'precio_promedio' => 0
                ],
                'stock_bajo' => [],
                'stock_bajo_count' => 0,
                'ultimos_movimientos' => [],
                'movimientos_por_mes' => [],
                'top_productos' => [],
                'total_categorias' => 0,
                'fecha_actualizacion' => date('Y-m-d H:i:s')
            ];
        }
    }
}