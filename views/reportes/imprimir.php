<?php
// views/reportes/imprimir.php
// Reporte imprimible - VERSIÓN CORREGIDA
// ✅ FIX: migrado a Bootstrap 5
// ✅ FIX: agregado Font Awesome
// ✅ FIX: mantiene standalone (no incluye header.php para no romper print)
// ✅ FIX: agregado htmlspecialchars en num_om

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /proyecto/auth/login');
    exit;
}

// Guards
$datos = $datos ?? [];
$tipo = $tipo ?? 'ordenes';
$fechaInicio = $fechaInicio ?? date('Y-m-01');
$fechaFin = $fechaFin ?? date('Y-m-t');
$total_costos = $total_costos ?? 0;
$total_ordenes = $total_ordenes ?? 0;

$titulo = "Imprimir Reporte";
$seccion = "reportes";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte - PROYECTO</title>
    
    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 11px; padding: 5mm; }
            .table { font-size: 10px; }
            .page-break { page-break-after: always; }
        }
        body { background: #fff; padding: 20px; font-family: 'Segoe UI', Arial, sans-serif; }
        .header { text-align: center; border-bottom: 2px solid #0d6efd; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { color: #0d6efd; font-weight: 700; }
        .header small { color: #6c757d; }
        .table th { background: #f8f9fa; font-size: 0.75rem; text-transform: uppercase; }
        .badge { font-size: 10px; }
        .btn-print { margin-bottom: 20px; }
        .footer { text-align: center; border-top: 1px solid #dee2e6; padding-top: 15px; margin-top: 20px; color: #6c757d; font-size: 12px; }
        .resumen-card { background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
        .resumen-card .number { font-size: 24px; font-weight: bold; }
    </style>
</head>
<body>

<div class="no-print text-end mb-3">
    <button onclick="window.print()" class="btn btn-primary">
        <i class="fas fa-print"></i> Imprimir / PDF
    </button>
    <a href="/proyecto/reportes" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

<!-- Encabezado -->
<div class="header">
    <h1><i class="fas fa-chart-bar"></i> PROYECTO</h1>
    <p>Sistema de Gestión de Mantenimiento</p>
    <small>Reporte generado: <?php echo date('d/m/Y H:i'); ?></small>
</div>

<!-- Resumen -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="resumen-card">
            <small class="text-muted">Período</small>
            <p class="mb-0"><strong><?php echo date('d/m/Y', strtotime($fechaInicio)); ?></strong> al <strong><?php echo date('d/m/Y', strtotime($fechaFin)); ?></strong></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="resumen-card">
            <small class="text-muted">Total Registros</small>
            <p class="number"><?php echo (int)$total_ordenes; ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="resumen-card">
            <small class="text-muted">Costo Total</small>
            <p class="number">S/ <?php echo number_format($total_costos, 2); ?></p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="resumen-card">
            <small class="text-muted">Tipo Reporte</small>
            <p class="mb-0"><strong><?php echo ucfirst(htmlspecialchars($tipo)); ?></strong></p>
        </div>
    </div>
</div>

<!-- Contenido -->
<div class="table-responsive">
    <?php if ($tipo === 'ordenes'): ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>N° Orden</th>
                    <th>Título</th>
                    <th>Planta</th>
                    <th>Área</th>
                    <th>Equipo</th>
                    <th>Técnico</th>
                    <th>Prioridad</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th>Costo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($datos)): ?>
                    <tr>
                        <td colspan="10" class="text-center">No hay datos para mostrar</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($datos as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['num_om'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($item['titulo'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($item['nombre_planta'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($item['nombre_area'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($item['nombre_equipo'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($item['tecnico_nombre'] ?? '-'); ?></td>
                            <td>
                                <span class="badge bg-<?php echo ($item['prioridad'] ?? '') === 'Urgente' ? 'danger' : 
                                                         (($item['prioridad'] ?? '') === 'Alta' ? 'warning' : 'info'); ?>">
                                    <?php echo htmlspecialchars($item['prioridad'] ?? 'Media'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo ($item['status'] ?? '') === 'CERRADA' ? 'success' : 
                                                         (($item['status'] ?? '') === 'EN_PROCESO' ? 'info' : 'warning'); ?>">
                                    <?php echo htmlspecialchars($item['status'] ?? 'PENDIENTE'); ?>
                                </span>
                            </td>
                            <td><?php echo isset($item['fecha_creacion']) ? date('d/m/Y', strtotime($item['fecha_creacion'])) : '-'; ?></td>
                            <td>S/ <?php echo number_format($item['costo_total'] ?? 0, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="9" class="text-end">TOTAL COSTOS:</th>
                    <th>S/ <?php echo number_format($total_costos, 2); ?></th>
                </tr>
            </tfoot>
        </table>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Técnico</th>
                    <th>Especialidad</th>
                    <th>Total Órdenes</th>
                    <th>Completadas</th>
                    <th>Costo Total</th>
                    <th>Prom. Horas</th>
                    <th>Eficiencia</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($datos)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No hay datos para mostrar</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($datos as $item): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($item['nombre'] ?? $item['tecnico'] ?? '-'); ?></strong></td>
                            <td><?php echo htmlspecialchars($item['especialidad'] ?? '-'); ?></td>
                            <td><?php echo (int)($item['total_ordenes'] ?? 0); ?></td>
                            <td><?php echo (int)($item['completadas'] ?? 0); ?></td>
                            <td>S/ <?php echo number_format($item['costo_total'] ?? 0, 2); ?></td>
                            <td><?php echo number_format($item['promedio_horas'] ?? 0, 1); ?> h</td>
                            <td><?php echo (int)($item['eficiencia'] ?? 0); ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="6" class="text-end">TOTAL COSTOS:</th>
                    <th>S/ <?php echo number_format($total_costos, 2); ?></th>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>
</div>

<!-- Pie de página -->
<div class="footer">
    <p class="mb-0">Reporte generado automáticamente por el Sistema PROYECTO</p>
    <p class="mb-0">Página 1 de 1</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            window.print();
        }, 1000);
    });
</script>

</body>
</html>