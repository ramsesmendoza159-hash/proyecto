<?php
// views/reportes/financieros.php
// Reportes Financieros - VERSIÓN CORREGIDA
// ✅ FIX: valida rol con SecurityHelper (admin, supervisor, consultor)

require_once __DIR__ . '/../../helpers/SecurityHelper.php';

if (!SecurityHelper::verificarSesion()) {
    header('Location: /proyecto/auth/login');
    exit;
}

if (!SecurityHelper::verificarRol(['admin', 'supervisor', 'consultor'])) {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    header('Location: /proyecto/dashboard');
    exit;
}

$seccion = 'financieros';
$titulo = 'Reportes Financieros';

$stats = $stats ?? [
    'total_ordenes' => 0,
    'total_costos' => 0,
    'total_repuestos' => 0,
    'total_mano_obra' => 0,
    'promedio_costo' => 0,
    'promedio_horas' => 0,
    'total_horas' => 0
];
$costos_por_planta = $costos_por_planta ?? [];
$costos_por_tecnico = $costos_por_tecnico ?? [];
$costos_por_mes = $costos_por_mes ?? [];
$fechaInicio = $fechaInicio ?? date('Y-m-01');
$fechaFin = $fechaFin ?? date('Y-m-t');

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-chart-line text-primary me-2"></i>Reportes Financieros
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-calendar-alt me-1"></i> 
                <?= date('d/m/Y H:i') ?>
                <span class="mx-2">|</span>
                <i class="fas fa-info-circle me-1"></i>
                <?= $stats['total_ordenes'] ?? 0 ?> órdenes procesadas
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/proyecto/reportes/financieros/exportar?tipo=resumen&fecha_inicio=<?= $fechaInicio ?>&fecha_fin=<?= $fechaFin ?>" 
               class="btn btn-success btn-sm">
                <i class="fas fa-file-excel me-1"></i> Exportar Resumen
            </a>
            <a href="/proyecto/reportes/financieros/exportar?tipo=detallado&fecha_inicio=<?= $fechaInicio ?>&fecha_fin=<?= $fechaFin ?>" 
               class="btn btn-primary btn-sm">
                <i class="fas fa-file-csv me-1"></i> Exportar Detallado
            </a>
            <a href="/proyecto/reportes" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="/proyecto/reportes/financieros" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control" 
                           value="<?= htmlspecialchars($fechaInicio) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Fecha Fin</label>
                    <input type="date" name="fecha_fin" class="form-control" 
                           value="<?= htmlspecialchars($fechaFin) ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Tarjetas de Estadísticas -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-lg-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(13,110,253,0.1);color:#0d6efd;">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div>
                        <div class="stat-label">Total Órdenes</div>
                        <div class="stat-number-mini"><?= number_format($stats['total_ordenes'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(25,135,84,0.1);color:#198754;">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div>
                        <div class="stat-label">Costo Total</div>
                        <div class="stat-number-mini">S/ <?= number_format($stats['total_costos'] ?? 0, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(255,193,7,0.1);color:#ffc107;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <div class="stat-label">Total Horas</div>
                        <div class="stat-number-mini"><?= number_format($stats['total_horas'] ?? 0, 1) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(220,53,69,0.1);color:#dc3545;">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <div>
                        <div class="stat-label">Promedio Costo</div>
                        <div class="stat-number-mini">S/ <?= number_format($stats['promedio_costo'] ?? 0, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- COSTOS POR PLANTA -->
    <?php if (!empty($costos_por_planta)): ?>
    <div class="card border-0 mb-4">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-building text-primary me-2"></i> Costos por Planta
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Planta</th>
                            <th class="text-center">Órdenes</th>
                            <th class="text-end">Costo Repuestos</th>
                            <th class="text-end">Costo Mano Obra</th>
                            <th class="text-end">Costo Total</th>
                            <th class="text-center">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_general = array_sum(array_column($costos_por_planta, 'total_costos'));
                        $total_ordenes_general = array_sum(array_column($costos_por_planta, 'total_ordenes'));
                        $total_repuestos_general = array_sum(array_column($costos_por_planta, 'total_repuestos'));
                        $total_mano_obra_general = array_sum(array_column($costos_por_planta, 'total_mano_obra'));
                        ?>
                        <?php foreach ($costos_por_planta as $item): 
                            $porcentaje = $total_general > 0 ? round(($item['total_costos'] ?? 0) / $total_general * 100, 1) : 0;
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($item['nombre_planta'] ?? 'N/A') ?></strong></td>
                            <td class="text-center"><?= number_format($item['total_ordenes'] ?? 0) ?></td>
                            <td class="text-end">S/ <?= number_format($item['total_repuestos'] ?? 0, 2) ?></td>
                            <td class="text-end">S/ <?= number_format($item['total_mano_obra'] ?? 0, 2) ?></td>
                            <td class="text-end"><strong>S/ <?= number_format($item['total_costos'] ?? 0, 2) ?></strong></td>
                            <td class="text-center">
                                <div class="progress" style="height:6px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width:<?= $porcentaje ?>%;"></div>
                                </div>
                                <small><?= $porcentaje ?>%</small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-active fw-bold">
                            <td><strong>TOTAL GENERAL</strong></td>
                            <td class="text-center"><?= number_format($total_ordenes_general) ?></td>
                            <td class="text-end">S/ <?= number_format($total_repuestos_general, 2) ?></td>
                            <td class="text-end">S/ <?= number_format($total_mano_obra_general, 2) ?></td>
                            <td class="text-end text-primary" style="font-size:1.1rem;">S/ <?= number_format($total_general, 2) ?></td>
                            <td class="text-center">100%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- COSTOS POR TÉCNICO -->
    <?php if (!empty($costos_por_tecnico)): ?>
    <div class="card border-0 mb-4">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-user-cog text-success me-2"></i> Costos por Técnico
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Técnico</th>
                            <th class="text-center">Órdenes</th>
                            <th class="text-end">Horas</th>
                            <th class="text-end">Prom. Horas</th>
                            <th class="text-end">Costo Mano Obra</th>
                            <th class="text-end">Costo Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($costos_por_tecnico as $item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-sm" style="width:32px;height:32px;border-radius:50%;background:#6c757d;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:0.75rem;">
                                        <?= strtoupper(substr($item['tecnico'] ?? '?', 0, 1)) ?>
                                    </div>
                                    <?= htmlspecialchars($item['tecnico'] ?? 'N/A') ?>
                                </div>
                            </td>
                            <td class="text-center"><?= number_format($item['total_ordenes'] ?? 0) ?></td>
                            <td class="text-end"><?= number_format($item['total_horas'] ?? 0, 1) ?></td>
                            <td class="text-end"><?= number_format($item['promedio_horas'] ?? 0, 1) ?></td>
                            <td class="text-end">S/ <?= number_format($item['total_mano_obra'] ?? 0, 2) ?></td>
                            <td class="text-end"><strong>S/ <?= number_format($item['total_costos'] ?? 0, 2) ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- COSTOS POR MES -->
    <?php if (!empty($costos_por_mes)): ?>
    <div class="card border-0 mb-4">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-calendar-alt text-info me-2"></i> Costos por Mes (<?= date('Y') ?>)
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Mes</th>
                            <th class="text-center">Órdenes</th>
                            <th class="text-end">Costo Repuestos</th>
                            <th class="text-end">Costo Mano Obra</th>
                            <th class="text-end">Costo Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $meses = [
                            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo',
                            '04' => 'Abril', '05' => 'Mayo', '06' => 'Junio',
                            '07' => 'Julio', '08' => 'Agosto', '09' => 'Septiembre',
                            '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
                        ];
                        ?>
                        <?php foreach ($costos_por_mes as $item): 
                            $mes_num = substr($item['mes'], -2);
                        ?>
                        <tr>
                            <td><strong><?= $meses[$mes_num] ?? $item['mes'] ?></strong></td>
                            <td class="text-center"><?= number_format($item['total_ordenes'] ?? 0) ?></td>
                            <td class="text-end">S/ <?= number_format($item['total_repuestos'] ?? 0, 2) ?></td>
                            <td class="text-end">S/ <?= number_format($item['total_mano_obra'] ?? 0, 2) ?></td>
                            <td class="text-end"><strong>S/ <?= number_format($item['total_costos'] ?? 0, 2) ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Mensaje cuando no hay datos -->
    <?php if (empty($costos_por_planta) && empty($costos_por_tecnico) && empty($costos_por_mes)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i> 
        No hay datos financieros disponibles para el período seleccionado.
        <?php if (!empty($stats['total_ordenes'])): ?>
            <br><small>Sin embargo, hay <?= $stats['total_ordenes'] ?> órdenes en el sistema.</small>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<!-- Estilos -->
<style>
.stat-card-mini {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 16px 20px;
    transition: all 0.3s ease;
}
.stat-card-mini:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px var(--shadow-hover);
}
.stat-card-mini .stat-icon-mini {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}
.stat-card-mini .stat-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
    font-weight: 600;
}
.stat-card-mini .stat-number-mini {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.2;
}
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
}
.table th {
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
}
.avatar-sm {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.75rem;
    flex-shrink: 0;
}
.progress {
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>