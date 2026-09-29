<?php
// controller/InventarioController.php
// VERSIÓN COMPLETA CON FILTROS AVANZADOS Y ENDPOINT AJAX CORREGIDO

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';

class InventarioController extends Controller {
    
    private $db;
    private $inventarioModel;

    public function __construct() {
        parent::__construct();
        
        if (!$this->authHelper->isLoggedIn()) {
            header('Location: /proyecto/auth/login');
            exit;
        }
        
        if (!$this->authHelper->isAdmin() && !$this->authHelper->isAlmacen() && !$this->authHelper->isSupervisor()) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            header('Location: /proyecto/dashboard');
            exit;
        }
        
        $this->db = Database::getInstance()->getConnection();
        
        require_once __DIR__ . '/../model/InventarioModel.php';
        $this->inventarioModel = new InventarioModel();
    }

    /**
     * Guardar ítem en inventario (POST desde el formulario)
     * URL: /inventario/guardar
     */
    public function guardar() {
        if (!$this->authHelper->isAdmin() && !$this->authHelper->isAlmacen()) {
            $_SESSION['error'] = 'No tienes permisos para crear ítems';
            $this->redirect('/inventario');
        }
        
        $this->crear();
    }

    /**
     * Listado de inventario
     * URL: /inventario
     */
    public function index() {
        // Obtener categorías y proveedores para los filtros
        $categorias = $this->inventarioModel->obtenerCategorias();
        $proveedores = $this->inventarioModel->obtenerProveedores();
        
        $filtros = [
            'buscar' => isset($_GET['buscar']) ? trim($_GET['buscar']) : '',
            'categoria' => isset($_GET['categoria']) ? trim($_GET['categoria']) : '',
            'estado' => isset($_GET['estado']) ? trim($_GET['estado']) : '',
            'stock' => isset($_GET['stock']) ? trim($_GET['stock']) : '',
            'orden' => isset($_GET['orden']) ? trim($_GET['orden']) : 'nombre',
            'precio_desde' => isset($_GET['precio_desde']) ? (float)$_GET['precio_desde'] : null,
            'precio_hasta' => isset($_GET['precio_hasta']) ? (float)$_GET['precio_hasta'] : null,
            'ubicacion' => isset($_GET['ubicacion']) ? trim($_GET['ubicacion']) : '',
            'proveedor' => isset($_GET['proveedor']) ? trim($_GET['proveedor']) : '',
            'fecha_desde' => isset($_GET['fecha_desde']) ? trim($_GET['fecha_desde']) : '',
            'fecha_hasta' => isset($_GET['fecha_hasta']) ? trim($_GET['fecha_hasta']) : ''
        ];
        
        try {
            $items = $this->inventarioModel->obtenerTodos($filtros);
            $estadisticas = $this->inventarioModel->obtenerEstadisticas();
            
            if (!is_array($items)) {
                $items = [];
            }
            
        } catch (Exception $e) {
            error_log("Error en inventario index: " . $e->getMessage());
            $items = [];
            $estadisticas = ['total' => 0, 'total_stock' => 0, 'precio_promedio' => 0, 'valor_total' => 0];
            $_SESSION['error'] = 'Error al cargar el inventario: ' . $e->getMessage();
        }
        
        $this->view('inventario/index', [
            'items' => $items,
            'estadisticas' => $estadisticas,
            'filtros' => $filtros,
            'categorias' => $categorias,
            'proveedores' => $proveedores
        ]);
    }

    /**
     * ✅ LISTAR INVENTARIO PARA AJAX (JSON) - VERSIÓN CORREGIDA
     * URL: /inventario/list
     * 
     * IMPORTANTE: SIEMPRE devuelve JSON, NUNCA redirige.
     */
    public function list() {
        // ✅ 1. SIEMPRE devolver JSON, sin redirecciones
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        
        // ✅ 2. Verificar sesión SIN redirigir
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
            echo json_encode([
                'items' => [],
                'total' => 0,
                'paginas' => 0,
                'error' => 'No autorizado - Sesión no iniciada',
                'debug' => [
                    'session_id' => session_id(),
                    'usuario_id' => $_SESSION['usuario_id'] ?? null,
                    'rol' => $_SESSION['rol'] ?? null
                ]
            ]);
            exit;
        }
        
        // ✅ 3. Verificar rol SIN redirigir
        $rol = $_SESSION['rol'] ?? '';
        if (!in_array($rol, ['admin', 'almacen', 'supervisor', 'consultor'])) {
            echo json_encode([
                'items' => [],
                'total' => 0,
                'paginas' => 0,
                'error' => 'No tienes permisos para ver el inventario',
                'rol_actual' => $rol
            ]);
            exit;
        }
        
        // ✅ 4. Procesar filtros
        try {
            $filtros = [];
            
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $filtros['buscar'] = trim($_GET['search']);
            }
            
            if (isset($_GET['categoria']) && !empty($_GET['categoria'])) {
                $filtros['categoria'] = $_GET['categoria'];
            }
            
            if (isset($_GET['stock']) && !empty($_GET['stock'])) {
                $filtros['stock'] = $_GET['stock'];
            }
            
            if (isset($_GET['estado']) && !empty($_GET['estado'])) {
                $filtros['estado'] = $_GET['estado'];
            }
            
            if (isset($_GET['orden']) && !empty($_GET['orden'])) {
                $filtros['orden'] = $_GET['orden'];
            }
            
            if (isset($_GET['precio_desde']) && is_numeric($_GET['precio_desde']) && $_GET['precio_desde'] > 0) {
                $filtros['precio_desde'] = (float)$_GET['precio_desde'];
            }
            
            if (isset($_GET['precio_hasta']) && is_numeric($_GET['precio_hasta']) && $_GET['precio_hasta'] > 0) {
                $filtros['precio_hasta'] = (float)$_GET['precio_hasta'];
            }
            
            if (isset($_GET['ubicacion']) && !empty($_GET['ubicacion'])) {
                $filtros['ubicacion'] = trim($_GET['ubicacion']);
            }
            
            if (isset($_GET['proveedor']) && !empty($_GET['proveedor'])) {
                $filtros['proveedor'] = $_GET['proveedor'];
            }
            
            if (isset($_GET['fecha_desde']) && !empty($_GET['fecha_desde'])) {
                $filtros['fecha_desde'] = $_GET['fecha_desde'];
            }
            if (isset($_GET['fecha_hasta']) && !empty($_GET['fecha_hasta'])) {
                $filtros['fecha_hasta'] = $_GET['fecha_hasta'];
            }
            
            error_log("📋 Filtros aplicados en list(): " . print_r($filtros, true));
            
            // ✅ 5. Obtener items del modelo
            $items = $this->inventarioModel->obtenerTodos($filtros);
            
            if (!is_array($items)) {
                $items = [];
            }
            
            $total = count($items);
            $paginas = ceil($total / 15);
            
            // ✅ 6. Devolver JSON
            echo json_encode([
                'success' => true,
                'items' => $items,
                'total' => $total,
                'paginas' => $paginas,
                'filtros_aplicados' => $filtros
            ]);
            
        } catch (Exception $e) {
            error_log("❌ Error en list inventario: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            echo json_encode([
                'success' => false,
                'items' => [],
                'total' => 0,
                'paginas' => 0,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        exit;
    }

    /**
     * Crear ítem en inventario
     * URL: /inventario/crear
     */
    public function crear() {
        if (!$this->authHelper->isAdmin() && !$this->authHelper->isAlmacen()) {
            $_SESSION['error'] = 'No tienes permisos para crear ítems';
            $this->redirect('/inventario');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $token = $_POST['csrf_token'] ?? '';
                if (!SecurityHelper::verifyCSRFToken($token)) {
                    throw new Exception('Token de seguridad inválido');
                }
                
                $datos = [
                    'codigo' => trim($_POST['codigo'] ?? ''),
                    'nombre' => trim($_POST['nombre'] ?? ''),
                    'descripcion' => trim($_POST['descripcion'] ?? ''),
                    'descripcion_tecnica' => trim($_POST['descripcion_tecnica'] ?? ''),
                    'categoria' => trim($_POST['categoria'] ?? ''),
                    'cantidad' => (int)($_POST['cantidad'] ?? 0),
                    'precio_unitario' => (float)($_POST['precio_unitario'] ?? 0),
                    'unidad' => trim($_POST['unidad'] ?? ''),
                    'stock_minimo' => (int)($_POST['stock_minimo'] ?? 0),
                    'stock_maximo' => !empty($_POST['stock_maximo']) ? (int)$_POST['stock_maximo'] : null,
                    'ubicacion' => trim($_POST['ubicacion'] ?? ''),
                    'estado' => isset($_POST['estado']) ? $_POST['estado'] : 'activo',
                    'proveedor' => trim($_POST['proveedor'] ?? '')
                ];
                
                $errores = [];
                
                if (empty($datos['nombre'])) {
                    $errores[] = "El nombre es obligatorio";
                }
                if (empty($datos['categoria'])) {
                    $errores[] = "La categoría es obligatoria";
                }
                if ($datos['cantidad'] < 0) {
                    $errores[] = "La cantidad no puede ser negativa";
                }
                if ($datos['precio_unitario'] < 0) {
                    $errores[] = "El precio no puede ser negativo";
                }
                
                if (!empty($errores)) {
                    throw new Exception(implode('<br>', $errores));
                }
                
                if (!empty($datos['codigo']) && $this->inventarioModel->codigoExiste($datos['codigo'])) {
                    throw new Exception("El código SKU ya está registrado");
                }
                
                $id = $this->inventarioModel->crear($datos);
                
                if ($id) {
                    $_SESSION['success'] = "Ítem '" . $datos['nombre'] . "' creado exitosamente";
                    $this->redirect('/inventario');
                } else {
                    throw new Exception("Error al crear el ítem");
                }
                
            } catch (Exception $e) {
                $_SESSION['error'] = "Error: " . $e->getMessage();
                $_SESSION['old'] = $_POST;
                $this->redirect('/inventario/crear');
            }
        }
        
        $this->view('inventario/crear');
    }

    /**
     * Editar ítem en inventario
     * URL: /inventario/editar/{id}
     */
    public function editar($id) {
        if (!$this->authHelper->isAdmin() && !$this->authHelper->isAlmacen()) {
            $_SESSION['error'] = 'No tienes permisos para editar ítems';
            $this->redirect('/inventario');
        }

        $id = (int)$id;
        if ($id <= 0) {
            $_SESSION['error'] = "ID de ítem inválido";
            $this->redirect('/inventario');
        }
        
        $item = $this->inventarioModel->obtenerPorId($id);
        
        if (!$item) {
            $_SESSION['error'] = "Ítem no encontrado";
            $this->redirect('/inventario');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $token = $_POST['csrf_token'] ?? '';
                if (!SecurityHelper::verifyCSRFToken($token)) {
                    throw new Exception('Token de seguridad inválido');
                }
                
                $datos = [
                    'codigo' => trim($_POST['codigo'] ?? ''),
                    'nombre' => trim($_POST['nombre'] ?? ''),
                    'descripcion' => trim($_POST['descripcion'] ?? ''),
                    'descripcion_tecnica' => trim($_POST['descripcion_tecnica'] ?? ''),
                    'categoria' => trim($_POST['categoria'] ?? ''),
                    'cantidad' => (int)($_POST['cantidad'] ?? 0),
                    'precio_unitario' => (float)($_POST['precio_unitario'] ?? 0),
                    'unidad' => trim($_POST['unidad'] ?? ''),
                    'stock_minimo' => (int)($_POST['stock_minimo'] ?? 0),
                    'stock_maximo' => !empty($_POST['stock_maximo']) ? (int)$_POST['stock_maximo'] : null,
                    'ubicacion' => trim($_POST['ubicacion'] ?? ''),
                    'estado' => isset($_POST['estado']) ? $_POST['estado'] : 'activo',
                    'proveedor' => trim($_POST['proveedor'] ?? '')
                ];
                
                $errores = [];
                
                if (empty($datos['nombre'])) {
                    $errores[] = "El nombre es obligatorio";
                }
                if ($datos['cantidad'] < 0) {
                    $errores[] = "La cantidad no puede ser negativa";
                }
                if ($datos['precio_unitario'] < 0) {
                    $errores[] = "El precio no puede ser negativo";
                }
                
                if (!empty($errores)) {
                    throw new Exception(implode('<br>', $errores));
                }
                
                if (!empty($datos['codigo']) && $this->inventarioModel->codigoExiste($datos['codigo'], $id)) {
                    throw new Exception("El código SKU ya está registrado");
                }
                
                if ($this->inventarioModel->actualizar($id, $datos)) {
                    $_SESSION['success'] = "Ítem actualizado exitosamente";
                    $this->redirect('/inventario');
                } else {
                    throw new Exception("Error al actualizar el ítem");
                }
                
            } catch (Exception $e) {
                $_SESSION['error'] = "Error: " . $e->getMessage();
                $_SESSION['old'] = $_POST;
                $this->redirect('/inventario/editar/' . $id);
            }
        }
        
        $this->view('inventario/editar', ['item' => $item]);
    }

    /**
     * Eliminar ítem del inventario
     * URL: /inventario/eliminar/{id}
     */
    public function eliminar($id) {
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
        if (!$this->authHelper->isLoggedIn()) {
            echo json_encode([
                'success' => false,
                'message' => 'No autorizado. Inicia sesión.'
            ]);
            exit;
        }
        
        if (!$this->authHelper->isAdmin() && !$this->authHelper->isAlmacen()) {
            echo json_encode([
                'success' => false,
                'message' => 'No tienes permisos para eliminar ítems'
            ]);
            exit;
        }

        $id = (int)$id;
        if ($id <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'ID de ítem inválido'
            ]);
            exit;
        }
        
        $item = $this->inventarioModel->obtenerPorId($id);
        if (!$item) {
            echo json_encode([
                'success' => false,
                'message' => 'Ítem no encontrado'
            ]);
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'success' => false,
                'message' => 'Método no permitido. Usa POST.'
            ]);
            exit;
        }
        
        try {
            $token = $_POST['csrf_token'] ?? '';
            if (!SecurityHelper::verifyCSRFToken($token)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Token de seguridad inválido. Recarga la página.'
                ]);
                exit;
            }
            
            if ($this->inventarioModel->eliminar($id)) {
                echo json_encode([
                    'success' => true,
                    'message' => '✅ Ítem eliminado exitosamente'
                ]);
            } else {
                $error = $this->inventarioModel->getLastError() ?: 'Error al eliminar el ítem';
                echo json_encode([
                    'success' => false,
                    'message' => $error
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Buscar ítems (para autocomplete)
     * URL: /inventario/buscar
     */
    public function buscar() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['usuario_id'])) {
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
            
        } catch (Exception $e) {
            error_log("Error en buscar inventario: " . $e->getMessage());
            echo json_encode([]);
        }
        exit;
    }

    /**
     * Mostrar detalle de producto (VISTA HTML)
     * URL: /inventario/detalle/{id}
     */
    public function detalle($id) {
        $id = (int)$id;
        
        if ($id <= 0) {
            $_SESSION['error'] = 'ID de producto inválido';
            $this->redirect('/inventario');
            return;
        }
        
        try {
            $producto = $this->inventarioModel->obtenerPorId($id);
            
            if (!$producto) {
                $_SESSION['error'] = 'Producto no encontrado';
                $this->redirect('/inventario');
                return;
            }
            
            $movimientos = $this->inventarioModel->obtenerMovimientosPorProducto($id);
            
        } catch (Exception $e) {
            error_log("Error en detalle inventario: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar el detalle del producto';
            $this->redirect('/inventario');
            return;
        }
        
        $this->view('inventario/detalle', [
            'producto' => $producto,
            'movimientos' => $movimientos
        ]);
    }

    /**
     * Obtener detalle de producto (JSON - PARA API)
     * URL: /inventario/api/detalle/{id}
     */
    public function apiDetalle($id) {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['usuario_id'])) {
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
        
        $id = (int)$id;
        if ($id <= 0) {
            echo json_encode(['error' => 'ID inválido']);
            exit;
        }
        
        try {
            $item = $this->inventarioModel->obtenerPorId($id);
            
            if ($item) {
                echo json_encode($item);
            } else {
                echo json_encode(['error' => 'Ítem no encontrado']);
            }
            
        } catch (Exception $e) {
            error_log("Error en apiDetalle inventario: " . $e->getMessage());
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Obtener top productos más movidos (JSON)
     * URL: /almacen/top_productos
     */
    public function topProductos() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['usuario_id'])) {
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
        
        if (!in_array($_SESSION['rol'] ?? '', ['almacen', 'admin'])) {
            echo json_encode(['error' => 'No tienes permisos']);
            exit;
        }

        $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 10;
        $productos = $this->inventarioModel->obtenerTopProductosMasMovidos($limite);
        
        $stmt = $this->db->query("SELECT COUNT(DISTINCT inventario_id) as total_productos_movidos FROM inventario_movimientos WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $total_movidos = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'productos' => $productos,
            'total_productos_movidos' => $total_movidos['total_productos_movidos'] ?? 0,
            'total_mostrados' => count($productos),
            'periodo' => 30
        ]);
        exit;
    }

    /**
     * Redirigir a inventario (para almacen/inventario)
     */
    public function inventario() {
        header('Location: /proyecto/inventario');
        exit;
    }
}
?>