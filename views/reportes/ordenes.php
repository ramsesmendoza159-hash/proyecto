<?php
// views/reportes/ordenes.php
// Reporte de Órdenes - VERSIÓN CORREGIDA
// ✅ FIX: permisos alineados con ReporteController (admin, supervisor, consultor)

require_once __DIR__ . '/../../helpers/SecurityHelper.php';

if (!SecurityHelper::verificarSesion()) {
    header('Location: /proyecto/auth/login');
    exit();
}

if (!SecurityHelper::verificarRol(['admin', 'supervisor', 'consultor'])) {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    header('Location: /proyecto/dashboard');
    exit();
}

$titulo = "Reporte de Órdenes";
$seccion = "reportes";
include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-file-alt text-primary me-2"></i>Reporte de Órdenes de Trabajo
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-calendar-alt me-1"></i> <?= date('d/m/Y H:i') ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-success btn-sm" onclick="exportarExcel()">
                <i class="fas fa-file-excel me-1"></i> Exportar Excel
            </button>
            <button class="btn btn-danger btn-sm" onclick="exportarPDF()">
                <i class="fas fa-file-pdf me-1"></i> Exportar PDF
            </button>
            <a href="/proyecto/reportes" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="/proyecto/reportes/ordenes" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label for="fecha_desde" class="form-label fw-semibold small">Fecha Desde</label>
                    <input type="date" class="form-control form-control-sm" id="fecha_desde" name="fecha_desde" 
                           value="<?php echo htmlspecialchars($_GET['fecha_desde'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label for="fecha_hasta" class="form-label fw-semibold small">Fecha Hasta</label>
                    <input type="date" class="form-control form-control-sm" id="fecha_hasta" name="fecha_hasta" 
                           value="<?php echo htmlspecialchars($_GET['fecha_hasta'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <label for="estado" class="form-label fw-semibold small">Estado</label>
                    <select class="form-select form-select-sm" id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="PENDIENTE" <?php echo ($_GET['estado'] ?? '') === 'PENDIENTE' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="EN_PROCESO" <?php echo ($_GET['estado'] ?? '') === 'EN_PROCESO' ? 'selected' : ''; ?>>En Progreso</option>
                        <option value="EJECUTADA" <?php echo ($_GET['estado'] ?? '') === 'EJECUTADA' ? 'selected' : ''; ?>>Ejecutada</option>
                        <option value="CERRADA" <?php echo ($_GET['estado'] ?? '') === 'CERRADA' ? 'selected' : ''; ?>>Cerrada</option>
                        <option value="CANCELADA" <?php echo ($_GET['estado'] ?? '') === 'CANCELADA' ? 'selected' : ''; ?>>Cancelada</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="prioridad" class="form-label fw-semibold small">Prioridad</label>
                    <select class="form-select form-select-sm" id="prioridad" name="prioridad">
                        <option value="">Todas</option>
                        <option value="Alta" <?php echo ($_GET['prioridad'] ?? '') === 'Alta' ? 'selected' : ''; ?>>Alta</option>
                        <option value="Media" <?php echo ($_GET['prioridad'] ?? '') === 'Media' ? 'selected' : ''; ?>>Media</option>
                        <option value="Baja" <?php echo ($_GET['prioridad'] ?? '') === 'Baja' ? 'selected' : ''; ?>>Baja</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="tecnico_id" class="form-label fw-semibold small">Técnico</label>
                    <select class="form-select form-select-sm" id="tecnico_id" name="tecnico_id">
                        <option value="">Todos</option>
                        <?php foreach ($tecnicos ?? [] as $tecnico): ?>
                            <option value="<?php echo $tecnico['id']; ?>" 
                                    <?php echo ($_GET['tecnico_id'] ?? '') == $tecnico['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tecnico['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search me-1"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumen -->
    <?php
    $estadisticas = $estadisticas ?? [
        'total' => 0,
        'completadas' => 0,
        'pendientes' => 0,
        'canceladas' => 0,
        'en_proceso' => 0,
        'costo_total' => 0
    ];
    ?>
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(13,110,253,0.1);color:#0d6efd;">
                        <i class="fas fa-list"></i>
                    </div>
                    <div>
                        <div class="stat-label">Total</div>
                        <div class="stat-number-mini"><?php echo number_format($estadisticas['total'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(25,135,84,0.1);color:#198754;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-label">Completadas</div>
                        <div class="stat-number-mini"><?php echo number_format($estadisticas['completadas'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(255,193,7,0.1);color:#ffc107;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <div class="stat-label">Pendientes</div>
                        <div class="stat-number-mini"><?php echo number_format($estadisticas['pendientes'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(13,202,240,0.1);color:#0dcaf0;">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div>
                        <div class="stat-label">En Proceso</div>
                        <div class="stat-number-mini"><?php echo number_format($estadisticas['en_proceso'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(220,53,69,0.1);color:#dc3545;">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div>
                        <div class="stat-label">Canceladas</div>
                        <div class="stat-number-mini"><?php echo number_format($estadisticas['canceladas'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(13,110,253,0.1);color:#0d6efd;">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div>
                        <div class="stat-label">Costo Total</div>
                        <div class="stat-number-mini">S/ <?php echo number_format($estadisticas['costo_total'] ?? 0, 2); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>N° OM</th>
                            <th>Título</th>
                            <th>Planta</th>
                            <th>Área</th>
                            <th>Técnico</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Costo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($ordenes)): ?>
                            <?php foreach ($ordenes as $orden): ?>
                                <tr>
                                    <td><span class="fw-semibold"><?php echo htmlspecialchars($orden['num_om'] ?? 'N/A'); ?></span></td>
                                    <td><?php echo htmlspecialchars($orden['titulo'] ?? 'Sin título'); ?></td>
                                    <td><?php echo htmlspecialchars($orden['nombre_planta'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($orden['nombre_area'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($orden['tecnico_nombre'] ?? 'Sin asignar'); ?></td>
                                    <td>
                                        <?php 
                                        $prioridad = $orden['prioridad'] ?? 'Media';
                                        $badgeClass = match($prioridad) {
                                            'Alta' => 'danger',
                                            'Media' => 'warning',
                                            'Baja' => 'success',
                                            'Urgente' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $badgeClass; ?> bg-opacity-10 text-<?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($prioridad); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $status = $orden['status'] ?? 'PENDIENTE';
                                        $badgeStatus = match($status) {
                                            'PENDIENTE' => 'warning',
                                            'EN_PROCESO' => 'info',
                                            'EJECUTADA' => 'primary',
                                            'CERRADA' => 'success',
                                            'APROBADA' => 'success',
                                            'RECHAZADA' => 'danger',
                                            'CANCELADA' => 'dark',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $badgeStatus; ?> bg-opacity-10 text-<?php echo $badgeStatus; ?>">
                                            <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                            <?php echo htmlspecialchars($status); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($orden['fecha_creacion'] ?? 'now')); ?></td>
                                    <td>S/ <?php echo number_format($orden['costo_total'] ?? 0, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                    No hay órdenes que coincidan con los filtros
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <?php if (!empty($ordenes)): ?>
                            <tr class="table-active fw-bold">
                                <td colspan="8" class="text-end">TOTAL COSTOS:</td>
                                <td>S/ <?php echo number_format(array_sum(array_column($ordenes, 'costo_total')), 2); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="card-footer bg-transparent">
            <span class="text-muted small">
                <i class="fas fa-list me-1"></i> Mostrando <?php echo count($ordenes ?? []); ?> orden(es)
            </span>
        </div>
    </div>

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
</style>

<script>
function exportarExcel() {
    const params = new URLSearchParams(window.location.search);
    window.location.href = '/proyecto/reportes/exportar?tipo=ordenes&formato=excel&' + params.toString();
}

function exportarPDF() {
    const params = new URLSearchParams(window.location.search);
    window.location.href = '/proyecto/reportes/imprimir?tipo=ordenes&' + params.toString();
}
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>