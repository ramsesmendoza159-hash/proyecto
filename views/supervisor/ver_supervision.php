<?php
// views/supervisor/ver_supervision.php
// Ver Supervisión - VERSIÓN CORREGIDA
// ✅ FIX: usa campos planos del JOIN, no $supervision['orden'][...]

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

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-check text-primary me-2"></i>Detalle de Supervisión #<?= (int)$supervision['id'] ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Información detallada de la supervisión
            </p>
        </div>
        <a href="/proyecto/supervisor/supervisiones" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
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
                                <p class="fw-semibold mb-0"><?= (int)$supervision['id'] ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Orden</label>
                                <p class="fw-semibold mb-0">#<?= (int)($supervision['orden_id'] ?? 0) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Técnico</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($supervision['tecnico'] ?? 'N/A') ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Estado</label>
                                <p class="mb-0">
                                    <?php
                                    $estado = $supervision['estado'] ?? 'PENDIENTE';
                                    $color = match(strtoupper($estado)) {
                                        'APROBADA' => 'success',
                                        'RECHAZADA' => 'danger',
                                        default => 'warning'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?>">
                                        <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                        <?= htmlspecialchars($estado) ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Fecha creación</label>
                                <p class="fw-semibold mb-0">
                                    <?= !empty($supervision['fecha_creacion']) ? date('d/m/Y H:i', strtotime($supervision['fecha_creacion'])) : 'N/A' ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Calificación</label>
                                <p class="fw-semibold mb-0">
                                    <?php if (!empty($supervision['calificacion'])): ?>
                                        <span class="fw-bold"><?= (int)$supervision['calificacion'] ?>/5</span>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?= $i <= $supervision['calificacion'] ? '' : '-o' ?> text-warning" style="font-size:0.85rem;"></i>
                                        <?php endfor; ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Cumple estándares</label>
                                <p class="fw-semibold mb-0"><?= ($supervision['cumple'] ?? false) ? '✅ Sí' : '❌ No' ?></p>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div>
                        <label class="text-muted small fw-semibold text-uppercase">Observaciones</label>
                        <p class="mt-2"><?= nl2br(htmlspecialchars($supervision['observaciones'] ?? 'Sin observaciones')) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna lateral -->
        <div class="col-lg-4">
            <!-- Detalle de la orden -->
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-clipboard-list text-primary me-2"></i> Orden Relacionada
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($supervision['num_om']) || !empty($supervision['orden_titulo'])): ?>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">N° OM</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($supervision['num_om'] ?? 'N/A') ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Título</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($supervision['orden_titulo'] ?? $supervision['titulo'] ?? 'N/A') ?></p>
                        </div>
                        <?php if (!empty($supervision['nombre_area'])): ?>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Área</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($supervision['nombre_area']) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($supervision['prioridad'])): ?>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Prioridad</label>
                                <p class="mb-0">
                                    <?php
                                    $prioridad = $supervision['prioridad'];
                                    $prioridadColor = match($prioridad) {
                                        'Urgente' => 'danger',
                                        'Alta' => 'warning',
                                        'Media' => 'info',
                                        'Baja' => 'success',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $prioridadColor ?> bg-opacity-10 text-<?= $prioridadColor ?>">
                                        <?= htmlspecialchars($prioridad) ?>
                                    </span>
                                </p>
                            </div>
                        <?php endif; ?>
                        <a href="/proyecto/supervisor/ver_orden/<?= (int)$supervision['orden_id'] ?>" class="btn btn-info text-white w-100">
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