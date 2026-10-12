<?php
// controller/InventarioController.php


require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';
require_once __DIR__ . '/../model/InventarioModel.php';

class InventarioController extends Controller {

    private $db;
    private $inventarioModel;

    public function __construct() {
        parent::__construct();

        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
            return;
        }

        // ✅ FIX 2: permisos via getRole()
        $rol = $this->authHelper->getRole();
        if (!in_array($rol, ['admin', 'almacen', 'supervisor'], true)) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            $this->redirect('/dashboard');
            return;
        }

        try {
            $this->db = Database::getInstance()->getConnection();
            $this->inventarioModel = new InventarioModel();
        } catch (Throwable $e) {
            error_log("Error al instanciar modelos en InventarioController: " . $e->getMessage());
            $this->redirectWithError('/dashboard', 'Error al cargar el módulo');
            return;
        }
    }

    private function puedeEditar() {
        $rol = $this->authHelper->getRole();
        return in_array($rol, ['admin', 'almacen'], true);
    }

    /**
     * Guardar ítem en inventario (POST desde el formulario)
     * URL: /inventario/guardar
     */
    public function guardar() {
        if (!$this->puedeEditar()) {
            $this->redirectWithError('/inventario', 'No tienes permisos para crear ítems');
            return;
        }

        $this->crear();
    }

    /**
     * Listado de inventario
     * URL: /inventario
     */
    public function index() {
        try {
            $categorias = $this->inventarioModel->obtenerCategorias();
            $proveedores = $this->inventarioModel->obtenerProveedores();
        } catch (Throwable $e) {
            error_log("Error al obtener categorías/proveedores: " . $e->getMessage());
            $categorias = [];
            $proveedores = [];
        }

        $filtros = [
            'buscar'       => trim((string)($_GET['buscar'] ?? '')),
            'categoria'    => trim((string)($_GET['categoria'] ?? '')),
            'estado'       => trim((string)($_GET['estado'] ?? '')),
            'stock'        => trim((string)($_GET['stock'] ?? '')),
            'orden'        => trim((string)($_GET['orden'] ?? 'nombre')),
            'precio_desde' => isset($_GET['precio_desde']) && is_numeric($_GET['precio_desde']) ? (float)$_GET['precio_desde'] : null,
            'precio_hasta' => isset($_GET['precio_hasta']) && is_numeric($_GET['precio_hasta']) ? (float)$_GET['precio_hasta'] : null,
            'ubicacion'    => trim((string)($_GET['ubicacion'] ?? '')),
            'proveedor'    => trim((string)($_GET['proveedor'] ?? '')),
            'fecha_desde'  => trim((string)($_GET['fecha_desde'] ?? '')),
            'fecha_hasta'  => trim((string)($_GET['fecha_hasta'] ?? '')),
        ];

        try {
            $items = $this->inventarioModel->obtenerTodos($filtros);
            $estadisticas = $this->inventarioModel->obtenerEstadisticas();

            if (!is_array($items)) {
                $items = [];
            }
        } catch (Throwable $e) {
            error_log("Error en inventario index: " . $e->getMessage());
            $items = [];
            $estadisticas = ['total' => 0, 'total_stock' => 0, 'precio_promedio' => 0, 'valor_total' => 0];
            $_SESSION['error'] = 'Error al cargar el inventario';
        }

        $titulo = 'Inventario General';
        $seccion = 'inventario';

        require_once __DIR__ . '/../views/inventario/index.php';
    }

    /**
     * LISTAR INVENTARIO PARA AJAX (JSON)
     * URL: /inventario/list
     */
    public function list() {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
            echo json_encode([
                'items' => [],
                'total' => 0,
                'paginas' => 0,
                'error' => 'No autorizado - Sesión no iniciada'
            ]);
            exit;
        }

        $rol = $_SESSION['rol'] ?? '';
        if (!in_array($rol, ['admin', 'almacen', 'supervisor', 'consultor'], true)) {
            echo json_encode([
                'items' => [],
                'total' => 0,
                'paginas' => 0,
                'error' => 'No tienes permisos para ver el inventario'
            ]);
            exit;
        }

        try {
            $filtros = [];

            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $filtros['buscar'] = trim((string)$_GET['search']);
            }
            if (isset($_GET['categoria']) && !empty($_GET['categoria'])) {
                $filtros['categoria'] = trim((string)$_GET['categoria']);
            }
            if (isset($_GET['stock']) && !empty($_GET['stock'])) {
                $filtros['stock'] = trim((string)$_GET['stock']);
            }
            if (isset($_GET['estado']) && !empty($_GET['estado'])) {
                $filtros['estado'] = trim((string)$_GET['estado']);
            }
            if (isset($_GET['orden']) && !empty($_GET['orden'])) {
                $filtros['orden'] = trim((string)$_GET['orden']);
            }
            if (isset($_GET['precio_desde']) && is_numeric($_GET['precio_desde']) && $_GET['precio_desde'] > 0) {
                $filtros['precio_desde'] = (float)$_GET['precio_desde'];
            }
            if (isset($_GET['precio_hasta']) && is_numeric($_GET['precio_hasta']) && $_GET['precio_hasta'] > 0) {
                $filtros['precio_hasta'] = (float)$_GET['precio_hasta'];
            }
            if (isset($_GET['ubicacion']) && !empty($_GET['ubicacion'])) {
                $filtros['ubicacion'] = trim((string)$_GET['ubicacion']);
            }
            if (isset($_GET['proveedor']) && !empty($_GET['proveedor'])) {
                $filtros['proveedor'] = trim((string)$_GET['proveedor']);
            }
            if (isset($_GET['fecha_desde']) && !empty($_GET['fecha_desde'])) {
                $filtros['fecha_desde'] = trim((string)$_GET['fecha_desde']);
            }
            if (isset($_GET['fecha_hasta']) && !empty($_GET['fecha_hasta'])) {
                $filtros['fecha_hasta'] = trim((string)$_GET['fecha_hasta']);
            }

            $items = $this->inventarioModel->obtenerTodos($filtros);

            if (!is_array($items)) {
                $items = [];
            }

            $total = count($items);
            $paginas = ceil($total / 15);

            echo json_encode([
                'success' => true,
                'items' => $items,
                'total' => $total,
                'paginas' => $paginas,
                'filtros_aplicados' => $filtros
            ]);

        } catch (Throwable $e) {
            error_log("Error en list inventario: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            echo json_encode([
                'success' => false,
                'items' => [],
                'total' => 0,
                'paginas' => 0,
                'error' => 'Error al procesar la solicitud'
            ]);
        }
        exit;
    }

    /**
     * Crear ítem en inventario
     * URL: /inventario/crear
     */
    public function crear() {
        if (!$this->puedeEditar()) {
            $this->redirectWithError('/inventario', 'No tienes permisos para crear ítems');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $token = $_POST['csrf_token'] ?? '';
                if (!SecurityHelper::verifyCSRFToken($token)) {
                    throw new RuntimeException('Token de seguridad inválido');
                }

                $datos = [
                    'codigo'              => trim((string)($_POST['codigo'] ?? '')),
                    'nombre'              => trim((string)($_POST['nombre'] ?? '')),
                    'descripcion'         => trim((string)($_POST['descripcion'] ?? '')),
                    'descripcion_tecnica' => trim((string)($_POST['descripcion_tecnica'] ?? '')),
                    'categoria'           => trim((string)($_POST['categoria'] ?? '')),
                    'cantidad'            => (int)($_POST['cantidad'] ?? 0),
                    'precio_unitario'     => (float)($_POST['precio_unitario'] ?? 0),
                    'unidad'              => trim((string)($_POST['unidad'] ?? '')),
                    'stock_minimo'        => (int)($_POST['stock_minimo'] ?? 0),
                    'stock_maximo'        => !empty($_POST['stock_maximo']) ? (int)$_POST['stock_maximo'] : null,
                    'ubicacion'           => trim((string)($_POST['ubicacion'] ?? '')),
                    'estado'              => $_POST['estado'] ?? 'activo',
                    'proveedor'           => trim((string)($_POST['proveedor'] ?? '')),
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
                    throw new RuntimeException(implode('<br>', $errores));
                }

                if (!empty($datos['codigo']) && $this->inventarioModel->codigoExiste($datos['codigo'])) {
                    throw new RuntimeException("El código SKU ya está registrado");
                }

                $id = $this->inventarioModel->crear($datos);

                if ($id) {
                    $this->redirectWithSuccess('/inventario', "Ítem '" . $datos['nombre'] . "' creado exitosamente");
                    return;
                } else {
                    throw new RuntimeException("Error al crear el ítem");
                }

            } catch (Throwable $e) {
                error_log("Error en InventarioController::crear: " . $e->getMessage());
                $_SESSION['error'] = "Error: " . $e->getMessage();
                $_SESSION['old'] = $_POST;
                $this->redirect('/inventario/crear');
                return;
            }
        }

        $titulo = 'Crear Ítem de Inventario';
        $seccion = 'inventario';
        require_once __DIR__ . '/../views/inventario/crear.php';
    }

    /**
     * Editar ítem en inventario
     * URL: /inventario/editar/{id}
     */
    public function editar($id) {
        if (!$this->puedeEditar()) {
            $this->redirectWithError('/inventario', 'No tienes permisos para editar ítems');
            return;
        }

        $id = (int)$id;
        if ($id <= 0) {
            $this->redirectWithError('/inventario', "ID de ítem inválido");
            return;
        }

        $item = $this->inventarioModel->obtenerPorId($id);

        if (!$item) {
            $this->redirectWithError('/inventario', "Ítem no encontrado");
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $token = $_POST['csrf_token'] ?? '';
                if (!SecurityHelper::verifyCSRFToken($token)) {
                    throw new RuntimeException('Token de seguridad inválido');
                }

                $datos = [
                    'codigo'              => trim((string)($_POST['codigo'] ?? '')),
                    'nombre'              => trim((string)($_POST['nombre'] ?? '')),
                    'descripcion'         => trim((string)($_POST['descripcion'] ?? '')),
                    'descripcion_tecnica' => trim((string)($_POST['descripcion_tecnica'] ?? '')),
                    'categoria'           => trim((string)($_POST['categoria'] ?? '')),
                    'cantidad'            => (int)($_POST['cantidad'] ?? 0),
                    'precio_unitario'     => (float)($_POST['precio_unitario'] ?? 0),
                    'unidad'              => trim((string)($_POST['unidad'] ?? '')),
                    'stock_minimo'        => (int)($_POST['stock_minimo'] ?? 0),
                    'stock_maximo'        => !empty($_POST['stock_maximo']) ? (int)$_POST['stock_maximo'] : null,
                    'ubicacion'           => trim((string)($_POST['ubicacion'] ?? '')),
                    'estado'              => $_POST['estado'] ?? 'activo',
                    'proveedor'           => trim((string)($_POST['proveedor'] ?? '')),
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
                    throw new RuntimeException(implode('<br>', $errores));
                }

                if (!empty($datos['codigo']) && $this->inventarioModel->codigoExiste($datos['codigo'], $id)) {
                    throw new RuntimeException("El código SKU ya está registrado");
                }

                if ($this->inventarioModel->actualizar($id, $datos)) {
                    $this->redirectWithSuccess('/inventario', "Ítem actualizado exitosamente");
                    return;
                } else {
                    throw new RuntimeException("Error al actualizar el ítem");
                }

            } catch (Throwable $e) {
                error_log("Error en InventarioController::editar: " . $e->getMessage());
                $_SESSION['error'] = "Error: " . $e->getMessage();
                $_SESSION['old'] = $_POST;
                $this->redirect('/inventario/editar/' . $id);
                return;
            }
        }

        $titulo = 'Editar Ítem de Inventario';
        $seccion = 'inventario';
        require_once __DIR__ . '/../views/inventario/editar.php';
    }

    /**
     * Eliminar ítem del inventario
     * URL: /inventario/eliminar/{id}
     */
    public function eliminar($id) {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');

        if (!$this->authHelper->isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'No autorizado. Inicia sesión.']);
            exit;
        }

        if (!$this->puedeEditar()) {
            echo json_encode(['success' => false, 'message' => 'No tienes permisos para eliminar ítems']);
            exit;
        }

        $id = (int)$id;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de ítem inválido']);
            exit;
        }

        $item = $this->inventarioModel->obtenerPorId($id);
        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Ítem no encontrado']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido. Usa POST.']);
            exit;
        }

        try {
            $token = $_POST['csrf_token'] ?? '';
            if (!SecurityHelper::verifyCSRFToken($token)) {
                echo json_encode(['success' => false, 'message' => 'Token de seguridad inválido']);
                exit;
            }

            if ($this->inventarioModel->eliminar($id)) {
                echo json_encode(['success' => true, 'message' => '✅ Ítem eliminado exitosamente']);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al eliminar el ítem'
                ]);
            }

        } catch (Throwable $e) {
            error_log("Error en eliminar inventario: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al procesar la solicitud']);
        }
        exit;
    }

    /**
     * Buscar ítems (para autocomplete)
     * URL: /inventario/buscar
     */
    public function buscar() {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['usuario_id'])) {
            echo json_encode([]);
            exit;
        }

        $term = isset($_GET['term']) ? trim((string)$_GET['term']) : '';

        if (strlen($term) < 2) {
            echo json_encode([]);
            exit;
        }

        try {
            $resultados = $this->inventarioModel->buscar($term);
            echo json_encode($resultados);
        } catch (Throwable $e) {
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
            $this->redirectWithError('/inventario', 'ID de producto inválido');
            return;
        }

        try {
            $producto = $this->inventarioModel->obtenerPorId($id);

            if (!$producto) {
                $this->redirectWithError('/inventario', 'Producto no encontrado');
                return;
            }

            $movimientos = $this->inventarioModel->obtenerMovimientosPorProducto($id);

        } catch (Throwable $e) {
            error_log("Error en detalle inventario: " . $e->getMessage());
            $this->redirectWithError('/inventario', 'Error al cargar el detalle del producto');
            return;
        }

        $titulo = 'Detalle de Producto';
        $seccion = 'inventario';
        require_once __DIR__ . '/../views/inventario/detalle.php';
    }

    /**
     * Obtener detalle de producto (JSON - PARA API)
     * URL: /inventario/api/detalle/{id}
     */
    public function apiDetalle($id) {
        header('Content-Type: application/json; charset=utf-8');

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

        } catch (Throwable $e) {
            error_log("Error en apiDetalle inventario: " . $e->getMessage());
            echo json_encode(['error' => 'Error al procesar la solicitud']);
        }
        exit;
    }

    /**
     * Obtener top productos más movidos (JSON)
     * URL: /almacen/top_productos
     */
    public function topProductos() {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['usuario_id'])) {
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }

        if (!in_array($_SESSION['rol'] ?? '', ['almacen', 'admin'], true)) {
            echo json_encode(['error' => 'No tienes permisos']);
            exit;
        }

        try {
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

        } catch (Throwable $e) {
            error_log("Error en topProductos: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'error' => 'Error al procesar la solicitud',
                'productos' => [],
                'total_productos_movidos' => 0,
                'total_mostrados' => 0,
                'periodo' => 30
            ]);
        }
        exit;
    }

    /**
     * Redirigir a inventario (para almacen/inventario)
     */
    public function inventario() {
        $this->redirect('/inventario');
        return;
    }
}