<?php
// controller/AlmacenController.php
// MÓDULO DE ALMACÉN - CON AUDITORÍA DE MOVIMIENTOS - VERSIÓN CORREGIDA
// ✅ FIX: catch (Exception) → catch (Throwable) en TODOS los métodos (3 ocurrencias)
// ✅ FIX: removidos error_log() de debug innecesarios
// ✅ FIX: uso consistente de redirectWithSuccess/Error (ToastHelper)
// ✅ FIX: headers JSON con charset=utf-8
// ✅ FIX: verificación de rol estricta con in_array(..., true)
// ⚠️ TODO: permanencia() ejecuta UPDATE masivo en cada request. Mover a cron.

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
            $this->redirect('/auth/login');
            return;
        }
        
        if (!$this->authHelper->isAlmacen() && !$this->authHelper->isAdmin()) {
            $this->redirectWithError('/dashboard', 'No tienes permisos para acceder a esta sección');
            return;
        }

        try {
            $estadisticas = $this->inventarioModel->obtenerEstadisticas();
            $stock_bajo = $this->inventarioModel->obtenerStockBajo();
            $ultimos_movimientos = $this->inventarioModel->obtenerUltimosMovimientos(10);
            $movimientos_por_mes = $this->inventarioModel->obtenerMovimientosPorMes();
            $top_productos = $this->inventarioModel->obtenerProductosMasMovidos(5);
        } catch (Throwable $e) {
            error_log("Error en AlmacenController::index: " . $e->getMessage());
            $estadisticas = ['total' => 0, 'valor_total' => 0, 'stock_bajo' => 0];
            $stock_bajo = [];
            $ultimos_movimientos = [];
            $movimientos_por_mes = [];
            $top_productos = [];
        }

        $titulo = 'Panel de Almacén';
        $seccion = 'almacen';
        require_once __DIR__ . '/../views/almacen/index.php';
    }

    /**
     * Dashboard Data (JSON)
     * URL: /almacen/dashboardData
     */
    public function dashboardData() {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!$this->authHelper->isLoggedIn()) {
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }

        try {
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

            echo json_encode($data);
        } catch (Throwable $e) {
            error_log("Error en dashboardData: " . $e->getMessage());
            echo json_encode([
                'total_items' => 0,
                'stock_bajo' => 0,
                'valor_total' => 0,
                'movimientos_por_mes' => [],
                'top_productos' => []
            ]);
        }
        exit;
    }

    /**
     * Notificaciones de stock bajo (JSON)
     * URL: /almacen/notificaciones
     */
    public function notificaciones() {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!$this->authHelper->isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }

        try {
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

            echo json_encode([
                'success' => true,
                'total' => count($productos),
                'productos' => $productos
            ]);
        } catch (Throwable $e) {
            error_log("Error en notificaciones: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'total' => 0,
                'productos' => [],
                'error' => 'Error al procesar la solicitud'
            ]);
        }
        exit;
    }

    /**
     * Seleccionar producto para entrada
     * URL: /almacen/entrada_seleccion
     */
    public function entrada_seleccion() {
        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
            return;
        }
        
        if (!$this->authHelper->isAlmacen() && !$this->authHelper->isAdmin()) {
            $this->redirectWithError('/dashboard', 'No tienes permisos para realizar entradas');
            return;
        }

        try {
            $productos = $this->inventarioModel->obtenerTodos(['estado' => 'activo']);
        } catch (Throwable $e) {
            error_log("Error en entrada_seleccion: " . $e->getMessage());
            $productos = [];
        }

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
            $this->redirect('/auth/login');
            return;
        }
        
        if (!$this->authHelper->isAlmacen() && !$this->authHelper->isAdmin()) {
            $this->redirectWithError('/dashboard', 'No tienes permisos para realizar entradas');
            return;
        }

        if (!$id) {
            $this->entrada_seleccion();
            return;
        }

        $id = (int)$id;
        $producto = $this->inventarioModel->obtenerPorId($id);
        if (!$producto) {
            $this->redirectWithError('/almacen', 'Producto no encontrado');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $cantidad = (int)($_POST['cantidad'] ?? 0);
                $motivo = $_POST['motivo'] ?? 'Compra';
                $observaciones = $_POST['observaciones'] ?? '';

                if ($cantidad <= 0) {
                    throw new RuntimeException('La cantidad debe ser mayor a 0');
                }

                $cantidadAnterior = (int)$producto['cantidad'];

                $resultado = $this->inventarioModel->registrarEntradaConPermanencia(
                    $id,
                    $cantidad,
                    $this->authHelper->getUserId()
                );

                if ($resultado) {
                    // REGISTRAR EN AUDITORÍA
                    try {
                        $auditoria = new AuditoriaModel();
                        $auditoria->registrar(
                            $this->authHelper->getUserId(),
                            $_SESSION['nombre'] ?? 'Sistema',
                            $_SESSION['rol'] ?? 'almacen',
                            'entrada_stock',
                            'inventario',
                            $id,
                            ['cantidad' => $cantidadAnterior],
                            ['cantidad' => $cantidadAnterior + $cantidad, 'motivo' => $motivo, 'observaciones' => $observaciones]
                        );
                    } catch (Throwable $e) {
                        error_log("Error auditoría entrada_stock: " . $e->getMessage());
                    }

                    $this->redirectWithSuccess('/almacen/detalle/' . $id, "Entrada registrada correctamente. Cantidad: $cantidad");
                    return;
                } else {
                    throw new RuntimeException($this->inventarioModel->getLastError() ?: 'Error al registrar entrada');
                }

            } catch (Throwable $e) {   // ✅ FIX: Throwable
                error_log("Error en entrada($id): " . $e->getMessage());
                $this->redirectWithError('/almacen/entrada/' . $id, $e->getMessage());
                return;
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
            $this->redirect('/auth/login');
            return;
        }
        
        if (!$this->authHelper->isAlmacen() && !$this->authHelper->isAdmin()) {
            $this->redirectWithError('/dashboard', 'No tienes permisos para realizar salidas');
            return;
        }

        try {
            $productos = $this->inventarioModel->obtenerTodos(['estado' => 'activo', 'cantidad >' => 0]);
        } catch (Throwable $e) {
            error_log("Error en salida_seleccion: " . $e->getMessage());
            $productos = [];
        }

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
            $this->redirect('/auth/login');
            return;
        }
        
        if (!$this->authHelper->isAlmacen() && !$this->authHelper->isAdmin()) {
            $this->redirectWithError('/dashboard', 'No tienes permisos para realizar salidas');
            return;
        }

        if (!$id) {
            $this->salida_seleccion();
            return;
        }

        $id = (int)$id;
        $producto = $this->inventarioModel->obtenerPorId($id);
        if (!$producto) {
            $this->redirectWithError('/almacen', 'Producto no encontrado');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $cantidad = (int)($_POST['cantidad'] ?? 0);
                $motivo = $_POST['motivo'] ?? 'Uso interno';
                $destino = $_POST['destino'] ?? '';

                if ($cantidad <= 0) {
                    throw new RuntimeException('La cantidad debe ser mayor a 0');
                }

                if (empty($destino)) {
                    throw new RuntimeException('El destino es obligatorio');
                }

                if ($cantidad > $producto['cantidad']) {
                    throw new RuntimeException('Stock insuficiente. Disponible: ' . $producto['cantidad']);
                }

                $cantidadAnterior = (int)$producto['cantidad'];

                $resultado = $this->inventarioModel->registrarSalida(
                    $id,
                    $cantidad,
                    $this->authHelper->getUserId()
                );

                if ($resultado) {
                    // REGISTRAR EN AUDITORÍA
                    try {
                        $auditoria = new AuditoriaModel();
                        $auditoria->registrar(
                            $this->authHelper->getUserId(),
                            $_SESSION['nombre'] ?? 'Sistema',
                            $_SESSION['rol'] ?? 'almacen',
                            'salida_stock',
                            'inventario',
                            $id,
                            ['cantidad' => $cantidadAnterior],
                            ['cantidad' => $cantidadAnterior - $cantidad, 'destino' => $destino, 'motivo' => $motivo]
                        );
                    } catch (Throwable $e) {
                        error_log("Error auditoría salida_stock: " . $e->getMessage());
                    }

                    $this->redirectWithSuccess('/almacen/detalle/' . $id, "Salida registrada correctamente. Cantidad: $cantidad");
                    return;
                } else {
                    throw new RuntimeException($this->inventarioModel->getLastError() ?: 'Error al registrar salida');
                }

            } catch (Throwable $e) {   // ✅ FIX: Throwable
                error_log("Error en salida($id): " . $e->getMessage());
                $this->redirectWithError('/almacen/salida/' . $id, $e->getMessage());
                return;
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
            $this->redirect('/auth/login');
            return;
        }
        
        if (!$this->authHelper->isAlmacen() && !$this->authHelper->isAdmin()) {
            $this->redirectWithError('/dashboard', 'No tienes permisos para ver movimientos');
            return;
        }

        try {
            $movimientos = $this->inventarioModel->obtenerUltimosMovimientos(50);
        } catch (Throwable $e) {
            error_log("Error en movimientos: " . $e->getMessage());
            $movimientos = [];
        }

        $titulo = 'Historial de Movimientos';
        $seccion = 'movimientos';

        require_once __DIR__ . '/../views/almacen/movimientos.php';
    }

    /**
     * Detalle de producto
     * URL: /almacen/detalle/{id}
     */
    public function detalle($id) {
        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
            return;
        }
        
        if (!$this->authHelper->isAlmacen() && !$this->authHelper->isAdmin()) {
            $this->redirectWithError('/dashboard', 'No tienes permisos');
            return;
        }

        $id = (int)$id;
        $producto = $this->inventarioModel->obtenerPorId($id);
        if (!$producto) {
            $this->redirectWithError('/almacen', 'Producto no encontrado');
            return;
        }

        try {
            $movimientos = $this->inventarioModel->obtenerMovimientosPorProducto($id);
        } catch (Throwable $e) {
            error_log("Error en detalle: " . $e->getMessage());
            $movimientos = [];
        }

        $titulo = 'Detalle de Producto';
        $seccion = 'almacen';
        require_once __DIR__ . '/../views/almacen/detalle.php';
    }

    /**
     * Buscar productos (JSON)
     * URL: /almacen/buscar
     */
    public function buscar() {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!$this->authHelper->isLoggedIn()) {
            echo json_encode([]);
            exit;
        }

        $term = isset($_GET['term']) ? trim($_GET['term']) : '';
        if (strlen($term) < 2) {
            echo json_encode([]);
            exit;
        }

        try {
            $resultados = $this->inventarioModel->buscar($term);
            echo json_encode($resultados);
        } catch (Throwable $e) {
            error_log("Error en buscar almacen: " . $e->getMessage());
            echo json_encode([]);
        }
        exit;
    }

    /**
     * CONTROL DE PERMANENCIA
     * URL: /almacen/permanencia
     * 
     * ⚠️ TODO: Este método ejecuta un UPDATE masivo en cada request.
     *          Migrar a cron (cron/actualizar_permanencia.php) que corra 1 vez al día,
     *          o calcular dias_permanencia on-the-fly en el SELECT.
     */
    public function permanencia() {
        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
            return;
        }
        
        if (!$this->authHelper->isAlmacen() && !$this->authHelper->isAdmin()) {
            $this->redirectWithError('/dashboard', 'No tienes permisos');
            return;
        }

        try {
            $db = Database::getInstance()->getConnection();
            
            // ⚠️ TODO: mover a cron
            $db->exec("UPDATE inventario SET dias_permanencia = DATEDIFF(NOW(), fecha_ultima_entrada) WHERE fecha_ultima_entrada IS NOT NULL");
            
            $sql = "SELECT 
                        id, 
                        codigo, 
                        nombre, 
                        categoria, 
                        cantidad, 
                        unidad_medida AS unidad, 
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
            
        } catch (Throwable $e) {   // ✅ FIX: Throwable
            error_log("Error en permanencia: " . $e->getMessage());
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
        $this->redirect('/inventario');
    }

    /**
     * Estadísticas (JSON)
     * URL: /almacen/estadisticas
     */
    public function estadisticas() {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!$this->authHelper->isLoggedIn()) {
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }

        try {
            $estadisticas = $this->inventarioModel->obtenerEstadisticas();
            $stockPorCategoria = $this->inventarioModel->obtenerStockPorCategoria();

            echo json_encode([
                'success' => true,
                'estadisticas' => $estadisticas,
                'stock_por_categoria' => $stockPorCategoria
            ]);
        } catch (Throwable $e) {
            error_log("Error en estadisticas: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'estadisticas' => [],
                'stock_por_categoria' => [],
                'error' => 'Error al procesar la solicitud'
            ]);
        }
        exit;
    }

    /**
     * Obtener datos para gráficos (JSON)
     * URL: /almacen/graficos
     */
    public function graficos() {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!$this->authHelper->isLoggedIn()) {
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }

        try {
            $movimientos_por_mes = $this->inventarioModel->obtenerMovimientosPorMes();
            $top_productos = $this->inventarioModel->obtenerProductosMasMovidos(5);

            echo json_encode([
                'success' => true,
                'movimientos_por_mes' => $movimientos_por_mes,
                'top_productos' => $top_productos
            ]);
        } catch (Throwable $e) {
            error_log("Error en graficos: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'movimientos_por_mes' => [],
                'top_productos' => [],
                'error' => 'Error al procesar la solicitud'
            ]);
        }
        exit;
    }
}