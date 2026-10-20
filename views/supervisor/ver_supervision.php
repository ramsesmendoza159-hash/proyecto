<?php
// views/supervisor/ver_supervision.php
// Ver Supervisión - VERSIÓN CORREGIDA

if (!isset($seccion)) {
    $seccion = 'supervisor';
}
if (!isset($titulo)) {
    $titulo = 'Detalle de Supervisión';
}
if (!isset($supervision) || !$supervision) {
    header('Location: /proyecto/supervisor/supervisiones');
    exit();
}

// ✅ FIX: blindar TODOS los accesos
$sup_id        = (int)($supervision['id'] ?? 0);
$sup_orden_id  = (int)($supervision['orden_id'] ?? 0);
$sup_tecnico   = $supervision['tecnico'] ?? 'N/A';
$sup_estado    = $supervision['estado'] ?? 'PENDIENTE';
$sup_fecha_cre = $supervision['fecha_creacion'] ?? null;
$sup_calif     = $supervision['calificacion'] ?? null;
$sup_cumple    = !empty($supervision['cumple']);
$sup_obs       = $supervision['observaciones'] ?? 'Sin observaciones';

$sup_num_om    = $supervision['num_om'] ?? 'N/A';
$sup_ord_tit   = $supervision['orden_titulo'] ?? ($supervision['titulo'] ?? 'N/A');
$sup_area      = $supervision['nombre_area'] ?? '';
$sup_prior     = $supervision['prioridad'] ?? '';

$fecha_cre_val = !empty($sup_fecha_cre) && strtotime($sup_fecha_cre) !== false;

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-check text-primary me-2"></i>Detalle de Supervisión #<?= $sup_id ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Información detallada de la supervisión
            </p>
        </div>
        <a href="/proyecto/supervisor/supervisiones" class="btn btn-secondary btn-header-action">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="row g-4">
        <!-- Columna principal -->
        <div class="col-lg-8">
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-info-circle text-primary me-2"></i> Información de la Supervisión
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">ID</label>
                                <p class="fw-semibold mb-0"><?= $sup_id ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Orden</label>
                                <p class="fw-semibold mb-0">#<?= $sup_orden_id ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Técnico</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($sup_tecnico) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Estado</label>
                                <p class="mb-0">
                                    <?php
                                    $estadoRaw = strtoupper($sup_estado);
                                    $color = match($estadoRaw) {
                                        'APROBADA' => 'success',
                                        'RECHAZADA' => 'danger',
                                        default => 'warning'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?>">
                                        <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                        <?= htmlspecialchars($estadoRaw) ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Fecha creación</label>
                                <p class="fw-semibold mb-0">
                                    <?= $fecha_cre_val ? date('d/m/Y H:i', strtotime($sup_fecha_cre)) : 'N/A' ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Calificación</label>
                                <p class="fw-semibold mb-0">
                                    <?php if ($sup_calif !== null && $sup_calif !== ''): ?>
                                        <span class="fw-bold"><?= (int)$sup_calif ?>/5</span>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?= $i <= $sup_calif ? '' : '-o' ?> text-warning" style="font-size:0.85rem;"></i>
                                        <?php endfor; ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Cumple estándares</label>
                                <p class="fw-semibold mb-0"><?= $sup_cumple ? '✅ Sí' : '❌ No' ?></p>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div>
                        <label class="text-muted small fw-semibold text-uppercase">Observaciones</label>
                        <p class="mt-2"><?= nl2br(htmlspecialchars($sup_obs)) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna lateral -->
        <div class="col-lg-4">
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-clipboard-list text-primary me-2"></i> Orden Relacionada
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($sup_num_om) && $sup_num_om !== 'N/A'): ?>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">N° OM</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($sup_num_om) ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Título</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($sup_ord_tit) ?></p>
                        </div>
                        <?php if (!empty($sup_area)): ?>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Área</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($sup_area) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($sup_prior)): ?>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Prioridad</label>
                                <p class="mb-0">
                                    <?php
                                    $prioridadColor = match($sup_prior) {
                                        'Urgente' => 'danger',
                                        'Alta' => 'warning',
                                        'Media' => 'info',
                                        'Baja' => 'success',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $prioridadColor ?> bg-opacity-10 text-<?= $prioridadColor ?>">
                                        <?= htmlspecialchars($sup_prior) ?>
                                    </span>
                                </p>
                            </div>
                        <?php endif; ?>
                        <a href="/proyecto/supervisor/ver_orden/<?= $sup_orden_id ?>" class="btn btn-info text-white w-100">
                            <i class="fas fa-eye me-1"></i> Ver Orden
                        </a>
                    <?php else: ?>
                        <p class="text-muted text-center py-3 mb-0">Orden no disponible</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color, rgba(0,0,0,0.04));
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>