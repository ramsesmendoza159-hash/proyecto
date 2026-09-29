<?php
// controller/ReporteController.php
// VERSIÓN CORREGIDA
// ✅ FIX: Agregado método inventario() que faltaba
// ✅ FIX: catch (Throwable) en vez de Exception

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../model/OrdenTrabajo.php';
require_once __DIR__ . '/../model/Tecnico.php';
require_once __DIR__ . '/../model/InventarioModel.php';
require_once __DIR__ . '/../helpers/ValidationHelper.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';
require_once __DIR__ . '/../config/database.php';

class ReporteController extends Controller {

    private $ordenModel;
    private $tecnicoModel;
    private $inventarioModel;
    private $db;

    public function __construct() {
        parent::__construct();

        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
        }

        if (!$this->authHelper->isAdmin() && 
            !$this->authHelper->isSupervisor() && 
            !$this->authHelper->isConsultor()) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            $this->redirect('/dashboard');
        }

        $this->ordenModel = new OrdenTrabajo();
        $this->tecnicoModel = new Tecnico();
        $this->inventarioModel = new InventarioModel();
        $this->db = Database::getInstance()->getConnection();
    }

    private function cargarTecnicos() {
        try {
            $stmt = $this->db->query("SELECT id, nombre FROM tecnicos WHERE estado = 'activo' ORDER BY nombre ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al cargar técnicos: " . $e->getMessage());
            return [];
        }
    }

    public function index() {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');

        if (!ValidationHelper::validateDate($fechaInicio) || !ValidationHelper::validateDate($fechaFin)) {
            $fechaInicio = date('Y-m-01');
            $fechaFin = date('Y-m-t');
        }

        try {
            $stats = $this->ordenModel->obtenerEstadisticas();
            $ordenes_por_mes = $this->getOrdenesPorMes($fechaInicio, $fechaFin);
            $ordenes_por_estado = $this->getOrdenesPorEstado($fechaInicio, $fechaFin);
            $ordenes_por_prioridad = $this->getOrdenesPorPrioridad($fechaInicio, $fechaFin);
            $ordenes_por_tecnico = $this->getOrdenesPorTecnico($fechaInicio, $fechaFin);
            $ordenes_por_planta = $this->ordenModel->obtenerCostosPorPlanta($fechaInicio, $fechaFin);
            $costos_por_mes = $this->getCostosPorMes($fechaInicio, $fechaFin);

            $total = $stats['total'] ?? 0;
            $completadas = ($stats['cerradas'] ?? 0) + ($stats['aprobadas'] ?? 0);
            $eficiencia = $total > 0 ? round(($completadas / $total) * 100, 1) : 0;
            $stats['eficiencia'] = $eficiencia;

        } catch (Throwable $e) {
            error_log("Error en reportes index: " . $e->getMessage());
            $stats = [
                'total' => 0, 'pendientes' => 0, 'en_proceso' => 0,
                'cerradas' => 0, 'canceladas' => 0, 'aprobadas' => 0,
                'total_costos' => 0, 'promedio_horas' => 0, 'eficiencia' => 0
            ];
            $ordenes_por_mes = [];
            $ordenes_por_estado = [];
            $ordenes_por_prioridad = [];
            $ordenes_por_tecnico = [];
            $ordenes_por_planta = [];
            $costos_por_mes = [];
            $_SESSION['error'] = 'Error al cargar los reportes';
        }

        $seccion = 'reportes';
        $titulo = 'Reportes';
        require_once __DIR__ . '/../views/reportes/index.php';
    }

    public function ordenes() {
        try {
            $filtros = [
                'fecha_desde' => $_GET['fecha_desde'] ?? '',
                'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
                'estado' => $_GET['estado'] ?? '',
                'prioridad' => $_GET['prioridad'] ?? '',
                'tecnico_id' => $_GET['tecnico_id'] ?? ''
            ];

            $ordenes = $this->ordenModel->obtenerTodos($filtros);

            $estadisticas = [
                'total' => count($ordenes),
                'completadas' => 0,
                'pendientes' => 0,
                'canceladas' => 0,
                'en_proceso' => 0,
                'costo_total' => 0
            ];

            foreach ($ordenes as $o) {
                $status = $o['status'] ?? 'PENDIENTE';
                if (in_array($status, ['CERRADA', 'APROBADA', 'EJECUTADA'])) {
                    $estadisticas['completadas']++;
                } elseif ($status === 'PENDIENTE') {
                    $estadisticas['pendientes']++;
                } elseif ($status === 'EN_PROCESO') {
                    $estadisticas['en_proceso']++;
                } elseif ($status === 'CANCELADA') {
                    $estadisticas['canceladas']++;
                }
                $estadisticas['costo_total'] += (float)($o['costo_total'] ?? 0);
            }

            $tecnicos = $this->cargarTecnicos();

        } catch (Throwable $e) {
            error_log("Error en reporte ordenes: " . $e->getMessage());
            $ordenes = [];
            $estadisticas = [
                'total' => 0, 'completadas' => 0, 'pendientes' => 0,
                'canceladas' => 0, 'en_proceso' => 0, 'costo_total' => 0
            ];
            $tecnicos = [];
        }

        $seccion = 'reportes';
        $titulo = 'Reporte de Órdenes';
        require_once __DIR__ . '/../views/reportes/ordenes.php';
    }

    public function tecnicos() {
        $tecnicos = $this->cargarTecnicos();

        $seccion = 'reportes';
        $titulo = 'Reporte de Técnicos';
        require_once __DIR__ . '/../views/reportes/tecnicos.php';
    }

    public function tecnicosData() {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
            $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');
            $tecnicoId = !empty($_GET['tecnico_id']) ? (int)$_GET['tecnico_id'] : null;
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = max(1, (int)($_GET['limit'] ?? 10));
            $offset = ($page - 1) * $limit;

            $sql = "SELECT 
                        t.id,
                        t.nombre,
                        t.especialidad,
                        COUNT(o.id) as total,
                        SUM(CASE WHEN o.status IN ('CERRADA', 'APROBADA', 'EJECUTADA') THEN 1 ELSE 0 END) as completadas,
                        COALESCE(SUM(o.costo_total), 0) as costo_total,
                        COALESCE(AVG(o.horas_trabajadas), 0) as promedio_horas
                    FROM tecnicos t
                    LEFT JOIN ordenes_mantenimiento o ON t.id = o.tecnico_id 
                        AND o.fecha_creacion BETWEEN ? AND ?
                    WHERE t.estado = 'activo'";

            $params = [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'];

            if ($tecnicoId) {
                $sql .= " AND t.id = ?";
                $params[] = $tecnicoId;
            }

            $sql .= " GROUP BY t.id ORDER BY completadas DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $tecnicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'tecnicos' => $tecnicos,
                'total' => count($tecnicos)
            ]);

        } catch (Throwable $e) {
            error_log("Error en tecnicosData: " . $e->getMessage());
            echo json_encode(['tecnicos' => [], 'total' => 0, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function supervision() {
        try {
            $sql = "SELECT s.*, 
                           u.nombre as supervisor,
                           t.nombre as tecnico
                    FROM supervisiones s
                    LEFT JOIN usuarios u ON s.supervisor_id = u.id
                    LEFT JOIN ordenes_mantenimiento o ON s.orden_id = o.id
                    LEFT JOIN tecnicos t ON o.tecnico_id = t.id
                    ORDER BY s.fecha_creacion DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $supervisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $estadisticas = [
                'total' => count($supervisiones),
                'aprobadas' => 0,
                'rechazadas' => 0,
                'pendientes' => 0,
                'calificacion_promedio' => 0
            ];

            $suma_calif = 0;
            $count_calif = 0;

            foreach ($supervisiones as $s) {
                if ($s['estado'] === 'APROBADA') $estadisticas['aprobadas']++;
                elseif ($s['estado'] === 'RECHAZADA') $estadisticas['rechazadas']++;
                else $estadisticas['pendientes']++;

                if (!empty($s['calificacion'])) {
                    $suma_calif += (int)$s['calificacion'];
                    $count_calif++;
                }
            }

            $estadisticas['calificacion_promedio'] = $count_calif > 0 ? round($suma_calif / $count_calif, 1) : 0;

        } catch (Throwable $e) {
            error_log("Error en reporte supervision: " . $e->getMessage());
            $supervisiones = [];
            $estadisticas = ['total' => 0, 'aprobadas' => 0, 'rechazadas' => 0, 'pendientes' => 0, 'calificacion_promedio' => 0];
        }

        $seccion = 'reportes';
        $titulo = 'Reporte de Supervisión';
        require_once __DIR__ . '/../views/reportes/supervision.php';
    }

    /**
     * ✅ NUEVO: Reporte de Inventario (faltaba el método)
     */
    public function inventario() {
        try {
            $filtros = [
                'estado' => $_GET['estado'] ?? '',
                'categoria' => $_GET['categoria'] ?? '',
                'buscar' => $_GET['buscar'] ?? '',
                'stock' => $_GET['stock'] ?? ''
            ];

            $items = $this->inventarioModel->obtenerTodos($filtros);
            $estadisticas = $this->inventarioModel->obtenerEstadisticas();
            $categorias = $this->inventarioModel->obtenerCategorias();

            $total_items = count($items);
            $total_valor = 0;
            $total_stock = 0;
            $stock_bajo = 0;
            $agotados = 0;

            foreach ($items as $item) {
                $total_valor += (float)($item['precio_unitario'] ?? 0) * (int)($item['cantidad'] ?? 0);
                $total_stock += (int)($item['cantidad'] ?? 0);

                $cantidad = (int)($item['cantidad'] ?? 0);
                $stock_minimo = (int)($item['stock_minimo'] ?? 5);

                if ($cantidad === 0) {
                    $agotados++;
                } elseif ($cantidad <= $stock_minimo) {
                    $stock_bajo++;
                }
            }

            $estadisticas['total_valor'] = $total_valor;
            $estadisticas['total_stock'] = $total_stock;
            $estadisticas['stock_bajo'] = $stock_bajo;
            $estadisticas['agotados'] = $agotados;
            $estadisticas['total'] = $total_items;

        } catch (Throwable $e) {
            error_log("Error en reporte inventario: " . $e->getMessage());
            $items = [];
            $estadisticas = [
                'total' => 0, 'total_stock' => 0, 'total_valor' => 0,
                'stock_bajo' => 0, 'agotados' => 0, 'precio_promedio' => 0
            ];
            $categorias = [];
            $_SESSION['error'] = 'Error al cargar el reporte de inventario';
        }

        $seccion = 'reportes';
        $titulo = 'Reporte de Inventario';
        require_once __DIR__ . '/../views/reportes/inventario.php';
    }

    public function exportar() {
        $tipo = $_GET['tipo'] ?? 'ordenes';
        $formato = $_GET['formato'] ?? 'excel';

        if ($formato === 'pdf') {
            header('Location: /proyecto/reportes/imprimir?tipo=' . $tipo . '&' . http_build_query($_GET));
            exit;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_' . $tipo . '_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        if ($tipo === 'ordenes') {
            fputcsv($output, ['N° OM', 'Título', 'Planta', 'Área', 'Técnico', 'Prioridad', 'Estado', 'Fecha', 'Costo']);

            $ordenes = $this->ordenModel->obtenerTodos($_GET);
            foreach ($ordenes as $o) {
                fputcsv($output, [
                    $o['num_om'] ?? '',
                    $o['titulo'] ?? '',
                    $o['nombre_planta'] ?? '',
                    $o['nombre_area'] ?? '',
                    $o['tecnico_nombre'] ?? '',
                    $o['prioridad'] ?? '',
                    $o['status'] ?? '',
                    $o['fecha_creacion'] ?? '',
                    $o['costo_total'] ?? 0
                ]);
            }
        } elseif ($tipo === 'inventario') {
            fputcsv($output, ['Código', 'Nombre', 'Categoría', 'Stock', 'Precio Unitario', 'Valor Total', 'Ubicación', 'Estado']);

            $items = $this->inventarioModel->obtenerTodos($_GET);
            foreach ($items as $item) {
                fputcsv($output, [
                    $item['codigo'] ?? '',
                    $item['nombre'] ?? '',
                    $item['categoria'] ?? '',
                    $item['cantidad'] ?? 0,
                    $item['precio_unitario'] ?? 0,
                    (float)($item['precio_unitario'] ?? 0) * (int)($item['cantidad'] ?? 0),
                    $item['ubicacion'] ?? '',
                    $item['estado'] ?? ''
                ]);
            }
        }

        fclose($output);
        exit;
    }

    public function imprimir() {
        $tipo = $_GET['tipo'] ?? 'ordenes';

        try {
            if ($tipo === 'ordenes') {
                $datos = $this->ordenModel->obtenerTodos($_GET);
            } elseif ($tipo === 'tecnicos') {
                $datos = $this->tecnicoModel->obtenerTodos(['estado' => 'activo']);
            } elseif ($tipo === 'inventario') {
                $datos = $this->inventarioModel->obtenerTodos($_GET);
            } else {
                $datos = [];
            }

            $fechaInicio = $_GET['fecha_desde'] ?? date('Y-m-01');
            $fechaFin = $_GET['fecha_hasta'] ?? date('Y-m-t');
            $total_costos = 0;
            $total_ordenes = count($datos);

            foreach ($datos as $d) {
                $total_costos += (float)($d['costo_total'] ?? 0);
            }

        } catch (Throwable $e) {
            error_log("Error en imprimir: " . $e->getMessage());
            $datos = [];
            $fechaInicio = date('Y-m-01');
            $fechaFin = date('Y-m-t');
            $total_costos = 0;
            $total_ordenes = 0;
        }

        $seccion = 'reportes';
        $titulo = 'Imprimir Reporte';
        require_once __DIR__ . '/../views/reportes/imprimir.php';
    }

    private function getOrdenesPorMes($fechaInicio, $fechaFin) {
        try {
            $sql = "SELECT 
                        DATE_FORMAT(fecha_creacion, '%Y-%m') as mes,
                        COUNT(*) as total,
                        SUM(CASE WHEN status IN ('CERRADA', 'APROBADA', 'EJECUTADA') THEN 1 ELSE 0 END) as completadas
                    FROM ordenes_mantenimiento 
                    WHERE fecha_creacion BETWEEN ? AND ?
                    GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m')
                    ORDER BY mes ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $fechaInicio . ' 00:00:00',
                $fechaFin . ' 23:59:59'
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getOrdenesPorMes: " . $e->getMessage());
            return [];
        }
    }

    private function getOrdenesPorEstado($fechaInicio, $fechaFin) {
        try {
            $sql = "SELECT 
                        status,
                        COUNT(*) as total
                    FROM ordenes_mantenimiento 
                    WHERE fecha_creacion BETWEEN ? AND ?
                    GROUP BY status
                    ORDER BY total DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $fechaInicio . ' 00:00:00',
                $fechaFin . ' 23:59:59'
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getOrdenesPorEstado: " . $e->getMessage());
            return [];
        }
    }

    private function getOrdenesPorPrioridad($fechaInicio, $fechaFin) {
        try {
            $sql = "SELECT 
                        prioridad,
                        COUNT(*) as total
                    FROM ordenes_mantenimiento 
                    WHERE fecha_creacion BETWEEN ? AND ?
                    GROUP BY prioridad
                    ORDER BY 
                        CASE prioridad
                            WHEN 'Urgente' THEN 1
                            WHEN 'Alta' THEN 2
                            WHEN 'Media' THEN 3
                            WHEN 'Baja' THEN 4
                            ELSE 5
                        END";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $fechaInicio . ' 00:00:00',
                $fechaFin . ' 23:59:59'
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getOrdenesPorPrioridad: " . $e->getMessage());
            return [];
        }
    }

    private function getOrdenesPorTecnico($fechaInicio, $fechaFin) {
        try {
            $sql = "SELECT 
                        COALESCE(t.nombre, 'Sin Asignar') as tecnico,
                        COUNT(om.id) as total,
                        SUM(CASE WHEN om.status IN ('CERRADA', 'APROBADA', 'EJECUTADA') THEN 1 ELSE 0 END) as completadas
                    FROM ordenes_mantenimiento om
                    LEFT JOIN tecnicos t ON om.tecnico_id = t.id
                    WHERE om.fecha_creacion BETWEEN ? AND ?
                    GROUP BY om.tecnico_id
                    ORDER BY total DESC
                    LIMIT 10";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $fechaInicio . ' 00:00:00',
                $fechaFin . ' 23:59:59'
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getOrdenesPorTecnico: " . $e->getMessage());
            return [];
        }
    }

    private function getCostosPorMes($fechaInicio, $fechaFin) {
        try {
            $sql = "SELECT 
                        DATE_FORMAT(fecha_creacion, '%Y-%m') as mes,
                        SUM(costo_total) as total
                    FROM ordenes_mantenimiento 
                    WHERE fecha_creacion BETWEEN ? AND ?
                    GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m')
                    ORDER BY mes ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $fechaInicio . ' 00:00:00',
                $fechaFin . ' 23:59:59'
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Error en getCostosPorMes: " . $e->getMessage());
            return [];
        }
    }
}