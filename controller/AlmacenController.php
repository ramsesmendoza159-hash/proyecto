<?php
// controller/AlmacenController.php
// MÓDULO DE ALMACÉN - CON AUDITORÍA DE MOVIMIENTOS - VERSIÓN CORREGIDA

require_once __DIR__ . '/../model/InventarioModel.php';
require_once __DIR__ . '/../model/AuditoriaModel.php';
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';
require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../config/database.php';

class AlmacenController extends Controller {
    
    private $inventarioModel;

    public function __construct() {
        parent::__construct();
        $this->inventarioModel = new InventarioModel();
    }

    /**
     * Dashboard del Almacén
     * URL: /almacen
     */
    public function index() {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }
        
        if (!$this->authHelper->isAlmacen() && !$this->authHelper->isAdmin()) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            header('Location: /proyecto/dashboard');
            exit;
        }

        $estadisticas = $this->inventarioModel->obtenerEstadisticas();
        $stock_bajo = $this->inventarioModel->obtenerStockBajo();
        $ultimos_movimientos = $this->inventarioModel->obtenerUltimosMovimientos(10);
        $movimientos_por_mes = $this->inventarioModel->obtenerMovimientosPorMes();
        $top_productos = $this->inventarioModel->obtenerProductosMasMovidos(5);

        $titulo = 'Panel de Almacén';
        $seccion = 'almacen';
        require_once __DIR__ . '/../views/almacen/index.php';
    }

    /**
     * Dashboard Data (JSON)
     * URL: /almacen/dashboardData
     */
    public function dashboardData() {
        if (!$this->authHelper->isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }

        $estadisticas = $this->inventarioModel->obtenerEstadisticas();
        $stock_bajo = $this->inventarioModel->obtenerStockBajo();
        $movimientos_por_mes = $this->inventarioModel->obtenerMovimientosPorMes();
        $top_productos = $this->inventarioModel->obtenerProductosMasMovidos(5);

        $data = [
            'total_items' => $estadisticas['total'] ?? 0,
            'stock_bajo' => count($stock_bajo),
            'valor_total' => $estadisticas['valor_total'] ?? 0,
            'movimientos_por_mes' => $movimientos_por_mes,
            'top_productos' => $top_productos
        ];

        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Notificaciones de stock bajo (JSON)
     * URL: /almacen/notificaciones
     */
    public function notificaciones() {
        if (!$this->authHelper->isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }

        $stock_bajo = $this->inventarioModel->obtenerStockBajo();
        
        $productos = [];
        foreach ($stock_bajo as $item) {
            $productos[] = [
                'id' => $item['id'],
                'nombre' => $item['nombre'],
                'codigo' => $item['codigo'] ?? 'N/A',
                'cantidad' => $item['cantidad'],
                'stock_minimo' => $item['stock_minimo'],
                'unidad_medida' => $item['unidad'] ?? 'u',
                'diferencia' => ($item['stock_minimo'] ?? 0) - ($item['cantidad'] ?? 0)
            ];
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'total' => count($productos),
            'productos' => $productos
        ]);
        exit;
    }

    /**
     * Seleccionar producto para entrada
     * URL: /almacen/entrada_seleccion
     */
    public function entrada_seleccion() {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }
        
        if (!$this->authHelper->isAlmacen() && !$this->authHelper->isAdmin()) {
            $_SESSION['error'] = 'No tienes permisos para realizar entradas';
            header('Location: /proyecto/dashboard');
            exit;
        }

        $productos = $this->inventarioModel->obtenerTodos(['estado' => 'activo']);
        $titulo = 'Registrar Entrada - Seleccionar Producto';
        $seccion = 'entrada_stock';
        require_once __DIR__ . '/../views/almacen/entrada_seleccion.php';
    }

    /**
     * Registrar entrada de repuesto - CON AUDITORÍA
     * URL: /almacen/entrada/{id}
     */
    public function entrada($id = null) {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }
        
        if (!$this->authHelper->isAlmacen() && !$this->authHelper->isAdmin()) {
            $_SESSION['error'] = 'No tienes permisos para realizar entradas';
            header('Location: /proyecto/dashboard');
            exit;
        }

        if (!$id) {
            $this->entrada_seleccion();
            return;
        }

        $producto = $this->inventarioModel->obtenerPorId($id);
        if (!$producto) {
            $_SESSION['error'] = 'Producto no encontrado';
            header('Location: /proyecto/almacen');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $cantidad = (int)$_POST['cantidad'];
                $motivo = $_POST['motivo'] ?? 'Compra';
                $observaciones = $_POST['observaciones'] ?? '';

                if ($cantidad <= 0) {
                    throw new Exception('La cantidad debe ser mayor a 0');
                }

                $cantidadAnterior = $producto['cantidad'];

                $resultado = $this->inventarioModel->registrarEntradaConPermanencia(
                    $id,
                    $cantidad,
                    $this->authHelper->getUserId()
                );

                if ($resultado) {
                    // REGISTRAR EN AUDITORÍA
                    $auditoria = new AuditoriaModel();
                    $auditoria->registrar(
                        $this->authHelper->getUserId(),
                        $_SESSION['nombre'] ?? 'Sistema',
                        $_SESSION['rol'] ?? 'almacen',
                        'entrada_stock',
                        'inventario',
                        $id,
                        ['cantidad' => $cantidadAnterior],
                        ['cantidad' => $cantidadAnterior + $cantidad, 'motivo' => $motivo]
                    );

                    $_SESSION['mensaje'] = "✅ Entrada registrada correctamente. Cantidad: $cantidad";
                    $_SESSION['mensaje_tipo'] = 'success';
                    header('Location: /proyecto/almacen/detalle/' . $id);
                    exit;
                } else {
                    throw new Exception($this->inventarioModel->getLastError() ?: 'Error al registrar entrada');
                }

            } catch (Exception $e) {
                $_SESSION['error'] = '❌ ' . $e->getMessage();
                header('Location: /proyecto/almacen/entrada/' . $id);
                exit;
            }
        }

        $titulo = 'Registrar Entrada';
        $seccion = 'entrada_stock';
        require_once __DIR__ . '/../views/almacen/entrada.php';
    }

    /**
     * Seleccionar producto para salida
     * URL: /almacen/salida_seleccion
     */
    public function salida_seleccion() {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }
        
        if (!$this->authHelper->isAlmacen() && !$this->authHelper->isAdmin()) {
            $_SESSION['error'] = 'No tienes permisos para realizar salidas';
            header('Location: /proyecto/dashboard');
            exit;
        }

        $productos = $this->inventarioModel->obtenerTodos(['estado' => 'activo', 'cantidad >' => 0]);
        $titulo = 'Registrar Salida - Seleccionar Producto';
        $seccion = 'salida_stock';
        require_once __DIR__ . '/../views/almacen/salida_seleccion.php';
    }

    /**
     * Registrar salida de repuesto - CON AUDITORÍA
     * URL: /almacen/salida/{id}
     */
    public function salida($id = null) {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }
        
        if (!$this->authHelper->isAlmacen() && !$this->authHelper->isAdmin()) {
            $_SESSION['error'] = 'No tienes permisos para realizar salidas';
            header('Location: /proyecto/dashboard');
            exit;
        }

        if (!$id) {
            $this->salida_seleccion();
            return;
        }

        $producto = $this->inventarioModel->obtenerPorId($id);
        if (!$producto) {
            $_SESSION['error'] = 'Producto no encontrado';
            header('Location: /proyecto/almacen');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $cantidad = (int)$_POST['cantidad'];
                $motivo = $_POST['motivo'] ?? 'Uso interno';
                $destino = $_POST['destino'] ?? '';

                if ($cantidad <= 0) {
                    throw new Exception('La cantidad debe ser mayor a 0');
                }

                if (empty($destino)) {
                    throw new Exception('El destino es obligatorio');
                }

                if ($cantidad > $producto['cantidad']) {
                    throw new Exception('Stock insuficiente. Disponible: ' . $producto['cantidad']);
                }

                $cantidadAnterior = $producto['cantidad'];

                $resultado = $this->inventarioModel->registrarSalida(
                    $id,
                    $cantidad,
                    $this->authHelper->getUserId()
                );

                if ($resultado) {
                    // REGISTRAR EN AUDITORÍA
                    $auditoria = new AuditoriaModel();
                    $auditoria->registrar(
                        $this->authHelper->getUserId(),
                        $_SESSION['nombre'] ?? 'Sistema',
                        $_SESSION['rol'] ?? 'almacen',
                        'salida_stock',
                        'inventario',
                        $id,
                        ['cantidad' => $cantidadAnterior],
                        ['cantidad' => $cantidadAnterior - $cantidad, 'destino' => $destino]
                    );

                    $_SESSION['mensaje'] = "✅ Salida registrada correctamente. Cantidad: $cantidad";
                    $_SESSION['mensaje_tipo'] = 'success';
                    header('Location: /proyecto/almacen/detalle/' . $id);
                    exit;
                } else {
                    throw new Exception($this->inventarioModel->getLastError() ?: 'Error al registrar salida');
                }

            } catch (Exception $e) {
                $_SESSION['error'] = '❌ ' . $e->getMessage();
                header('Location: /proyecto/almacen/salida/' . $id);
                exit;
            }
        }

        $titulo = 'Registrar Salida';
        $seccion = 'salida_stock';
        require_once __DIR__ . '/../views/almacen/salida.php';
    }

    /**
     * Historial de movimientos - CORREGIDO
     * URL: /almacen/movimientos
     */
    public function movimientos() {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }
        
        if (!$this->authHelper->isAlmacen() && !$this->authHelper->isAdmin()) {
            $_SESSION['error'] = 'No tienes permisos para ver movimientos';
            header('Location: /proyecto/dashboard');
            exit;
        }

        // ✅ OBTENER MOVIMIENTOS
        $movimientos = $this->inventarioModel->obtenerUltimosMovimientos(50);
        
        // ✅ DEBUG: Verificar que los datos lleguen
        error_log("=== AlmacenController::movimientos() ===");
        error_log("Movimientos encontrados: " . count($movimientos));
        error_log("Tipo de datos: " . gettype($movimientos));
        
        // ✅ PASAR LA VARIABLE A LA VISTA
        $titulo = 'Historial de Movimientos';
        $seccion = 'movimientos';
        
        // ✅ INCLUIR LA VISTA CON LOS DATOS
        require_once __DIR__ . '/../views/almacen/movimientos.php';
    }

    /**
     * Detalle de producto
     * URL: /almacen/detalle/{id}
     */
    public function detalle($id) {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }
        
        if (!$this->authHelper->isAlmacen() && !$this->authHelper->isAdmin()) {
            $_SESSION['error'] = 'No tienes permisos';
            header('Location: /proyecto/dashboard');
            exit;
        }

        $producto = $this->inventarioModel->obtenerPorId($id);
        if (!$producto) {
            $_SESSION['error'] = 'Producto no encontrado';
            header('Location: /proyecto/almacen');
            exit;
        }

        $movimientos = $this->inventarioModel->obtenerMovimientosPorProducto($id);

        $titulo = 'Detalle de Producto';
        $seccion = 'almacen';
        require_once __DIR__ . '/../views/almacen/detalle.php';
    }

    /**
     * Buscar productos (JSON)
     * URL: /almacen/buscar
     */
    public function buscar() {
        if (!$this->authHelper->isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }

        $term = isset($_GET['term']) ? trim($_GET['term']) : '';
        if (strlen($term) < 2) {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }

        $resultados = $this->inventarioModel->buscar($term);
        header('Content-Type: application/json');
        echo json_encode($resultados);
        exit;
    }

    /**
     * CONTROL DE PERMANENCIA
     * URL: /almacen/permanencia
     */
    public function permanencia() {
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }
        
        if (!$this->authHelper->isAlmacen() && !$this->authHelper->isAdmin()) {
            $_SESSION['error'] = 'No tienes permisos';
            header('Location: /proyecto/dashboard');
            exit;
        }

        try {
            $db = Database::getInstance()->getConnection();
            
            $db->exec("UPDATE inventario SET dias_permanencia = DATEDIFF(NOW(), fecha_ultima_entrada) WHERE fecha_ultima_entrada IS NOT NULL");
            
            $sql = "SELECT 
                        id, 
                        codigo, 
                        nombre, 
                        categoria, 
                        cantidad, 
                        unidad, 
                        ubicacion, 
                        stock_minimo,
                        fecha_ultima_entrada,
                        dias_permanencia,
                        CASE 
                            WHEN dias_permanencia >= 30 THEN '⚠️ Crítico'
                            WHEN dias_permanencia >= 15 THEN '🟡 Normal'
                            WHEN dias_permanencia >= 7 THEN '🟢 Reciente'
                            ELSE '✅ Nuevo'
                        END as estado_permanencia
                    FROM inventario 
                    WHERE estado = 'activo'
                    ORDER BY dias_permanencia DESC";
            
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $sqlResumen = "SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN dias_permanencia >= 30 THEN 1 ELSE 0 END) as criticos,
                            SUM(CASE WHEN dias_permanencia >= 15 AND dias_permanencia < 30 THEN 1 ELSE 0 END) as normales,
                            SUM(CASE WHEN dias_permanencia >= 7 AND dias_permanencia < 15 THEN 1 ELSE 0 END) as recientes,
                            SUM(CASE WHEN dias_permanencia < 7 THEN 1 ELSE 0 END) as nuevos,
                            AVG(dias_permanencia) as promedio_dias,
                            MAX(dias_permanencia) as max_dias
                        FROM inventario 
                        WHERE estado = 'activo' AND cantidad > 0 AND dias_permanencia IS NOT NULL";
            
            $stmtResumen = $db->prepare($sqlResumen);
            $stmtResumen->execute();
            $resumen = $stmtResumen->fetch(PDO::FETCH_ASSOC);
            
            if (!$resumen) {
                $resumen = [
                    'total' => 0,
                    'criticos' => 0,
                    'normales' => 0,
                    'recientes' => 0,
                    'nuevos' => 0,
                    'promedio_dias' => 0,
                    'max_dias' => 0
                ];
            }
            
        } catch (Exception $e) {
            error_log("❌ Error en permanencia: " . $e->getMessage());
            $productos = [];
            $resumen = [
                'total' => 0,
                'criticos' => 0,
                'normales' => 0,
                'recientes' => 0,
                'nuevos' => 0,
                'promedio_dias' => 0,
                'max_dias' => 0
            ];
        }
        
        $titulo = 'Control de Permanencia';
        $seccion = 'permanencia';
        
        require_once __DIR__ . '/../views/almacen/permanencia.php';
    }

    /**
     * Redirigir al inventario
     * URL: /almacen/inventario
     */
    public function inventario() {
        header('Location: /proyecto/inventario');
        exit;
    }

    /**
     * Estadísticas (JSON)
     * URL: /almacen/estadisticas
     */
    public function estadisticas() {
        if (!$this->authHelper->isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }

        $estadisticas = $this->inventarioModel->obtenerEstadisticas();
        $stockPorCategoria = $this->inventarioModel->obtenerStockPorCategoria();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'estadisticas' => $estadisticas,
            'stock_por_categoria' => $stockPorCategoria
        ]);
        exit;
    }

    /**
     * Obtener datos para gráficos (JSON)
     * URL: /almacen/graficos
     */
    public function graficos() {
        if (!$this->authHelper->isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }

        $movimientos_por_mes = $this->inventarioModel->obtenerMovimientosPorMes();
        $top_productos = $this->inventarioModel->obtenerProductosMasMovidos(5);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'movimientos_por_mes' => $movimientos_por_mes,
            'top_productos' => $top_productos
        ]);
        exit;
    }
}
?>
