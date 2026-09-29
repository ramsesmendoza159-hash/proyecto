<?php
// controller/ReporteFinancieroController.php
// VERSIÓN COMPLETA CORREGIDA - CON PERMISOS PARA CONSULTOR
// ✅ FIX CRÍTICO: Se agregaron JOINs con plantas y areas (columnas nombre_planta y nombre_area NO existen en ordenes_mantenimiento)

require_once __DIR__ . '/../helpers/Controller.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/OrdenTrabajo.php';

class ReporteFinancieroController extends Controller {
    
    private $ordenModel;
    private $db;
    
    public function __construct() {
        parent::__construct();
        
        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
        }
        
        // ✅ PERMITIR consultor (solo lectura)
        if (!$this->authHelper->isAdmin() && 
            !$this->authHelper->isSupervisor() && 
            !$this->authHelper->isConsultor()) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            $this->redirect('/dashboard');
        }
        
        $this->ordenModel = new OrdenTrabajo();
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Página principal de reportes financieros
     */
    public function index() {
        $stats = [
            'total_ordenes' => 0, 'total_costos' => 0, 'promedio_costo' => 0,
            'total_horas' => 0, 'promedio_horas' => 0,
            'total_repuestos' => 0, 'total_mano_obra' => 0
        ];
        $costos_por_planta = [];
        $costos_por_tecnico = [];
        $costos_por_mes = [];
        $evolucion_mensual = [];
        
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');
        
        if (!strtotime($fechaInicio) || !strtotime($fechaFin)) {
            $fechaInicio = date('Y-m-01');
            $fechaFin = date('Y-m-t');
        }
        
        if ($fechaInicio > $fechaFin) {
            $temp = $fechaInicio;
            $fechaInicio = $fechaFin;
            $fechaFin = $temp;
        }
        
        try {
            $stats = $this->obtenerEstadisticasFinancieras($fechaInicio, $fechaFin);
            $costos_por_planta = $this->obtenerCostosPorPlanta($fechaInicio, $fechaFin);
            $costos_por_tecnico = $this->obtenerCostosPorTecnico($fechaInicio, $fechaFin);
            $costos_por_mes = $this->obtenerCostosPorMes();
            $evolucion_mensual = $this->obtenerEvolucionMensual();
            
        } catch (Exception $e) {
            error_log("Error en reporte financiero: " . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar los datos financieros: ' . $e->getMessage();
        }
        
        $seccion = 'financieros';
        $titulo = 'Reportes Financieros';
        require_once __DIR__ . '/../views/reportes/financieros.php';
    }
    
    /**
     * Obtener estadísticas financieras
     */
    private function obtenerEstadisticasFinancieras($fechaInicio, $fechaFin) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_ordenes,
                        COALESCE(SUM(costo_total), 0) as total_costos,
                        COALESCE(SUM(costo_repuestos), 0) as total_repuestos,
                        COALESCE(SUM(costo_mano_obra), 0) as total_mano_obra,
                        COALESCE(AVG(costo_total), 0) as promedio_costo,
                        COALESCE(AVG(horas_trabajadas), 0) as promedio_horas,
                        COALESCE(SUM(horas_trabajadas), 0) as total_horas
                    FROM ordenes_mantenimiento
                    WHERE status IN ('CERRADA', 'APROBADA', 'EJECUTADA')
                    AND fecha_creacion BETWEEN ? AND ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total_ordenes' => (int)($result['total_ordenes'] ?? 0),
                'total_costos' => (float)($result['total_costos'] ?? 0),
                'total_repuestos' => (float)($result['total_repuestos'] ?? 0),
                'total_mano_obra' => (float)($result['total_mano_obra'] ?? 0),
                'promedio_costo' => round((float)($result['promedio_costo'] ?? 0), 2),
                'promedio_horas' => round((float)($result['promedio_horas'] ?? 0), 1),
                'total_horas' => round((float)($result['total_horas'] ?? 0), 1)
            ];
            
        } catch (PDOException $e) {
            error_log("Error en obtenerEstadisticasFinancieras: " . $e->getMessage());
            return [
                'total_ordenes' => 0, 'total_costos' => 0, 'total_repuestos' => 0,
                'total_mano_obra' => 0, 'promedio_costo' => 0, 'promedio_horas' => 0, 'total_horas' => 0
            ];
        }
    }
    
    /**
     * Obtener costos por planta
     * ✅ FIX: JOIN con plantas (la columna nombre_planta NO existe en ordenes_mantenimiento)
     */
    private function obtenerCostosPorPlanta($fechaInicio, $fechaFin) {
        try {
            $sql = "SELECT 
                        COALESCE(p.nombre_planta, 'Sin Planta') as nombre_planta,
                        COUNT(o.id) as total_ordenes,
                        COALESCE(SUM(o.costo_total), 0) as total_costos,
                        COALESCE(SUM(o.costo_repuestos), 0) as total_repuestos,
                        COALESCE(SUM(o.costo_mano_obra), 0) as total_mano_obra
                    FROM ordenes_mantenimiento o
                    LEFT JOIN plantas p ON o.id_planta = p.id_planta
                    WHERE o.status IN ('CERRADA', 'APROBADA', 'EJECUTADA')
                    AND o.fecha_creacion BETWEEN ? AND ?
                    AND o.id_planta IS NOT NULL
                    GROUP BY o.id_planta
                    ORDER BY total_costos DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en obtenerCostosPorPlanta: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener costos por técnico
     */
    private function obtenerCostosPorTecnico($fechaInicio, $fechaFin) {
        try {
            $sql = "SELECT 
                        t.nombre as tecnico,
                        COUNT(o.id) as total_ordenes,
                        COALESCE(SUM(o.costo_total), 0) as total_costos,
                        COALESCE(SUM(o.horas_trabajadas), 0) as total_horas,
                        COALESCE(AVG(o.horas_trabajadas), 0) as promedio_horas,
                        COALESCE(SUM(o.costo_mano_obra), 0) as total_mano_obra
                    FROM ordenes_mantenimiento o
                    LEFT JOIN tecnicos t ON o.tecnico_id = t.id
                    WHERE o.status IN ('CERRADA', 'APROBADA', 'EJECUTADA')
                    AND o.fecha_creacion BETWEEN ? AND ?
                    AND o.tecnico_id IS NOT NULL
                    GROUP BY o.tecnico_id
                    ORDER BY total_costos DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en obtenerCostosPorTecnico: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener costos por mes
     */
    private function obtenerCostosPorMes() {
        try {
            $anio = date('Y');
            
            $sql = "SELECT 
                        DATE_FORMAT(fecha_creacion, '%Y-%m') as mes,
                        COUNT(*) as total_ordenes,
                        COALESCE(SUM(costo_total), 0) as total_costos,
                        COALESCE(SUM(costo_repuestos), 0) as total_repuestos,
                        COALESCE(SUM(costo_mano_obra), 0) as total_mano_obra
                    FROM ordenes_mantenimiento
                    WHERE status IN ('CERRADA', 'APROBADA', 'EJECUTADA')
                    AND YEAR(fecha_creacion) = ?
                    GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m')
                    ORDER BY mes ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$anio]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en obtenerCostosPorMes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener evolución mensual para gráficos
     */
    private function obtenerEvolucionMensual() {
        try {
            $anio = date('Y');
            
            $sql = "SELECT 
                        DATE_FORMAT(fecha_creacion, '%b %Y') as mes_label,
                        DATE_FORMAT(fecha_creacion, '%Y-%m') as mes,
                        COUNT(*) as total_ordenes,
                        COALESCE(SUM(costo_total), 0) as total_costos,
                        COALESCE(SUM(costo_repuestos), 0) as total_repuestos,
                        COALESCE(SUM(costo_mano_obra), 0) as total_mano_obra
                    FROM ordenes_mantenimiento
                    WHERE status IN ('CERRADA', 'APROBADA', 'EJECUTADA')
                    AND YEAR(fecha_creacion) = ?
                    GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m')
                    ORDER BY mes ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$anio]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error en obtenerEvolucionMensual: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Exportar reporte a CSV
     * ✅ FIX: JOIN con plantas y areas
     */
    public function exportar() {
        $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
        $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');
        $tipo = $_GET['tipo'] ?? 'detallado';
        
        if (!strtotime($fechaInicio) || !strtotime($fechaFin)) {
            $_SESSION['error'] = 'Fechas inválidas';
            $this->redirect('/reportes/financieros');
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_financiero_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        
        if ($tipo === 'detallado') {
            fputcsv($output, [
                'ID', 'N° OM', 'Título', 'Planta', 'Área', 'Técnico', 
                'Horas', 'Costo Repuestos', 'Costo Mano Obra', 'Costo Total', 'Estado'
            ]);
            
            // ✅ FIX: JOIN con plantas y areas
            $sql = "SELECT 
                        o.id, o.num_om, o.titulo, o.horas_trabajadas, 
                        o.costo_repuestos, o.costo_mano_obra, o.costo_total, o.status,
                        t.nombre as tecnico_nombre,
                        p.nombre_planta,
                        a.nombre_area
                    FROM ordenes_mantenimiento o
                    LEFT JOIN tecnicos t ON o.tecnico_id = t.id
                    LEFT JOIN plantas p ON o.id_planta = p.id_planta
                    LEFT JOIN areas a ON o.id_area = a.id_area
                    WHERE o.status IN ('CERRADA', 'APROBADA', 'EJECUTADA')
                    AND o.fecha_creacion BETWEEN ? AND ?
                    ORDER BY o.fecha_creacion DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
            $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($ordenes as $orden) {
                fputcsv($output, [
                    $orden['id'] ?? '',
                    $orden['num_om'] ?? '',
                    $orden['titulo'] ?? '',
                    $orden['nombre_planta'] ?? '',
                    $orden['nombre_area'] ?? '',
                    $orden['tecnico_nombre'] ?? '',
                    $orden['horas_trabajadas'] ?? 0,
                    $orden['costo_repuestos'] ?? 0,
                    $orden['costo_mano_obra'] ?? 0,
                    $orden['costo_total'] ?? 0,
                    $orden['status'] ?? ''
                ]);
            }
        } else {
            $stats = $this->obtenerEstadisticasFinancieras($fechaInicio, $fechaFin);
            fputcsv($output, ['REPORTE FINANCIERO RESUMEN']);
            fputcsv($output, ['Período:', $fechaInicio . ' al ' . $fechaFin]);
            fputcsv($output, ['Fecha de generación:', date('d/m/Y H:i:s')]);
            fputcsv($output, []);
            fputcsv($output, ['Métrica', 'Valor']);
            fputcsv($output, ['Total Órdenes', $stats['total_ordenes']]);
            fputcsv($output, ['Costo Total', 'S/ ' . number_format($stats['total_costos'], 2)]);
            fputcsv($output, ['Costo Repuestos', 'S/ ' . number_format($stats['total_repuestos'], 2)]);
            fputcsv($output, ['Costo Mano de Obra', 'S/ ' . number_format($stats['total_mano_obra'], 2)]);
            fputcsv($output, ['Promedio por Orden', 'S/ ' . number_format($stats['promedio_costo'], 2)]);
            fputcsv($output, ['Total Horas', $stats['total_horas']]);
            fputcsv($output, ['Promedio Horas', $stats['promedio_horas']]);
        }
        
        fclose($output);
        exit;
    }
}
?>