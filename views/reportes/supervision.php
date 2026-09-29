<?php
// views/reportes/supervision.php
// Reporte de Supervisión - VERSIÓN CORREGIDA
// ✅ FIX: eliminado include sidebar.php (ya está en header.php)
// ✅ FIX: permisos alineados con ReporteController

require_once __DIR__ . '/../../helpers/SecurityHelper.php';

if (!SecurityHelper::verificarSesion()) {
    header('Location: /proyecto/auth/login');
    exit;
}

if (!SecurityHelper::verificarRol(['admin', 'supervisor'])) {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    header('Location: /proyecto/dashboard');
    exit;
}

$supervisiones = $supervisiones ?? [];
$estadisticas = $estadisticas ?? [
    'total' => 0,
    'aprobadas' => 0,
    'rechazadas' => 0,
    'pendientes' => 0,
    'calificacion_promedio' => 0
];

$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$estado_filtro = $_GET['estado'] ?? '';

$titulo = "Reporte de Supervisión";
$seccion = "reportes";

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-check text-primary me-2"></i>Reporte de Supervisión
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-calendar-alt me-1"></i> 
                <?= date('d/m/Y H:i') ?>
                <span class="mx-2">|</span>
                <i class="fas fa-list me-1"></i> 
                <?= count($supervisiones) ?> registros
            </p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-success btn-sm" onclick="exportarReporte('excel')">
                <i class="fas fa-file-excel me-1"></i> Excel
            </button>
            <button class="btn btn-danger btn-sm" onclick="exportarReporte('pdf')">
                <i class="fas fa-file-pdf me-1"></i> PDF
            </button>
            <button class="btn btn-secondary btn-sm" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Imprimir
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card border-0 mb-4">
        <div class="card-body">
            <form id="filtrosForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Fecha Inicio</label>
                    <input type="date" class="form-control" name="fecha_inicio" 
                           value="<?= htmlspecialchars($fecha_inicio) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Fecha Fin</label>
                    <input type="date" class="form-control" name="fecha_fin" 
                           value="<?= htmlspecialchars($fecha_fin) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Estado</label>
                    <select class="form-select" name="estado">
                        <option value="">Todos</option>
                        <option value="APROBADA" <?= $estado_filtro === 'APROBADA' ? 'selected' : '' ?>>Aprobada</option>
                        <option value="RECHAZADA" <?= $estado_filtro === 'RECHAZADA' ? 'selected' : '' ?>>Rechazada</option>
                        <option value="PENDIENTE" <?= $estado_filtro === 'PENDIENTE' ? 'selected' : '' ?>>Pendiente</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-50">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                    <button type="reset" class="btn btn-secondary w-50" id="btnLimpiar">
                        <i class="fas fa-undo me-1"></i> Limpiar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-lg-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(13,110,253,0.1);color:#0d6efd;">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div>
                        <div class="stat-label">Total Supervisiones</div>
                        <div class="stat-number-mini"><?= number_format($estadisticas['total'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(25,135,84,0.1);color:#198754;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-label">Aprobadas</div>
                        <div class="stat-number-mini"><?= number_format($estadisticas['aprobadas'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(220,53,69,0.1);color:#dc3545;">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div>
                        <div class="stat-label">Rechazadas</div>
                        <div class="stat-number-mini"><?= number_format($estadisticas['rechazadas'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(13,202,240,0.1);color:#0dcaf0;">
                        <i class="fas fa-star"></i>
                    </div>
                    <div>
                        <div class="stat-label">Calif. Promedio</div>
                        <div class="stat-number-mini"><?= number_format($estadisticas['calificacion_promedio'] ?? 0, 1) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de supervisiones -->
    <div class="card border-0">
        <div class="card-body p-0">
            <?php if (empty($supervisiones)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                    <h5>No hay supervisiones registradas</h5>
                    <p class="text-muted">Ajusta los filtros para ver más resultados.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:60px;">#</th>
                                <th>Orden</th>
                                <th>Supervisor</th>
                                <th>Técnico</th>
                                <th style="width:120px;">Calificación</th>
                                <th style="width:120px;">Estado</th>
                                <th style="width:80px;">Cumple</th>
                                <th style="width:110px;">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($supervisiones as $item): ?>
                                <tr>
                                    <td><span class="fw-semibold"><?= (int)$item['id'] ?></span></td>
                                    <td>
                                        <a href="/proyecto/ordenes/ver/<?= (int)$item['orden_id'] ?>" class="text-decoration-none">
                                            #<?= (int)$item['orden_id'] ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($item['supervisor'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($item['tecnico'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if (!empty($item['calificacion'])): ?>
                                            <div class="d-flex align-items-center gap-1">
                                                <span class="fw-bold me-1"><?= (int)$item['calificacion'] ?></span>
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star<?= $i <= $item['calificacion'] ? '' : '-o' ?> text-warning" style="font-size:0.85rem;"></i>
                                                <?php endfor; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $estado = $item['estado'] ?? 'PENDIENTE';
                                        $badge = match($estado) {
                                            'APROBADA' => 'success',
                                            'RECHAZADA' => 'danger',
                                            'PENDIENTE' => 'warning',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $badge ?> bg-opacity-10 text-<?= $badge ?> px-3 py-2">
                                            <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                            <?= htmlspecialchars($estado) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= ($item['cumple'] ?? 0) ? '✅ Sí' : '❌ No' ?>
                                    </td>
                                    <td>
                                        <small><?= isset($item['fecha_creacion']) ? date('d/m/Y', strtotime($item['fecha_creacion'])) : '-' ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($supervisiones)): ?>
            <div class="card-footer bg-transparent">
                <span class="text-muted small">
                    <i class="fas fa-list me-1"></i> Mostrando <?= count($supervisiones) ?> registro(s)
                </span>
            </div>
        <?php endif; ?>
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
.table th {
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
}
</style>

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('filtrosForm');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const params = new URLSearchParams(new FormData(this));
        window.location.href = '/proyecto/reportes/supervision?' + params.toString();
    });

    document.getElementById('btnLimpiar').addEventListener('click', function(e) {
        e.preventDefault();
        window.location.href = '/proyecto/reportes/supervision';
    });

    const fechaInicio = document.querySelector('[name="fecha_inicio"]');
    const fechaFin = document.querySelector('[name="fecha_fin"]');

    [fechaInicio, fechaFin].forEach(input => {
        input.addEventListener('change', function() {
            if (fechaInicio.value && fechaFin.value && fechaInicio.value > fechaFin.value) {
                alert('⚠️ La fecha de inicio no puede ser mayor que la fecha fin.');
                this.value = '';
            }
        });
    });
});

function exportarReporte(tipo) {
    const form = document.getElementById('filtrosForm');
    const params = new URLSearchParams(new FormData(form));
    window.location.href = '/proyecto/reportes/exportar?tipo=supervision&formato=' + tipo + '&' + params.toString();
}
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>