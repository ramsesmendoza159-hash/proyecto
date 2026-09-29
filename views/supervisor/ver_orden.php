<?php
// views/supervisor/ver_orden.php
// Ver Orden - VERSIÓN CORREGIDA
// ✅ FIX: usa los campos reales del JOIN plano
// ✅ FIX: supervisor_estado en vez de supervision['estado']

if (!isset($seccion)) {
    $seccion = 'supervisor';
}
if (!isset($titulo)) {
    $titulo = 'Detalle de Orden';
}
if (!isset($orden) || !$orden) {
    header('Location: /proyecto/supervisor/ordenes');
    exit();
}

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-list text-primary me-2"></i>Detalle de Orden #<?= (int)$orden['id'] ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Información completa de la orden de trabajo
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/proyecto/supervisor/ordenes" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <?php if (($orden['status'] ?? '') === 'CERRADA' && empty($orden['supervision_estado'])): ?>
                <a href="/proyecto/supervisor/revisar/<?= (int)$orden['id'] ?>" class="btn btn-primary">
                    <i class="fas fa-clipboard-check me-1"></i> Revisar
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?= $_SESSION['mensaje_tipo'] ?? 'success' ?> alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_SESSION['mensaje']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Columna principal -->
        <div class="col-lg-8">
            <!-- Información de la orden -->
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-info-circle text-primary me-2"></i> Información de la Orden
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">N° OM</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($orden['num_om'] ?? 'N/A') ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Título</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($orden['titulo'] ?? 'Sin título') ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Área</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($orden['nombre_area'] ?? 'N/A') ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Prioridad</label>
                                <p class="mb-0">
                                    <?php
                                    $prioridad = $orden['prioridad'] ?? 'Media';
                                    $color = match($prioridad) {
                                        'Urgente' => 'danger',
                                        'Alta' => 'warning',
                                        'Media' => 'info',
                                        'Baja' => 'success',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?>">
                                        <?= htmlspecialchars($prioridad) ?>
                                    </span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Estado</label>
                                <p class="mb-0">
                                    <?php
                                    $estado = $orden['status'] ?? 'PENDIENTE';
                                    $color = match($estado) {
                                        'CERRADA', 'APROBADA' => 'success',
                                        'EN_PROCESO' => 'info',
                                        'EJECUTADA' => 'primary',
                                        'CANCELADA', 'RECHAZADA' => 'danger',
                                        default => 'warning'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?>">
                                        <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                        <?= htmlspecialchars($estado) ?>
                                    </span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Técnico</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($orden['tecnico_nombre'] ?? 'Sin asignar') ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Fecha creación</label>
                                <p class="fw-semibold mb-0">
                                    <?= !empty($orden['fecha_creacion']) ? date('d/m/Y H:i', strtotime($orden['fecha_creacion'])) : 'N/A' ?>
                                </p>
                            </div>
                            <?php if (!empty($orden['fecha_estimada'])): ?>
                                <div class="mb-3">
                                    <label class="text-muted small fw-semibold text-uppercase">Fecha estimada</label>
                                    <p class="fw-semibold mb-0"><?= date('d/m/Y', strtotime($orden['fecha_estimada'])) ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($orden['fecha_finalizacion'])): ?>
                                <div class="mb-3">
                                    <label class="text-muted small fw-semibold text-uppercase">Fecha cierre</label>
                                    <p class="fw-semibold mb-0"><?= date('d/m/Y H:i', strtotime($orden['fecha_finalizacion'])) ?></p>
                                </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Horas trabajadas</label>
                                <p class="fw-semibold mb-0">
                                    <?= !empty($orden['horas_trabajadas']) ? number_format($orden['horas_trabajadas'], 2) . ' h' : 'N/A' ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Repuestos usados</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($orden['repuestos_usados'] ?? 'Ninguno') ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Equipo</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($orden['nombre_equipo'] ?? 'N/A') ?></p>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div>
                        <label class="text-muted small fw-semibold text-uppercase">Descripción del mantenimiento</label>
                        <p class="mt-2"><?= nl2br(htmlspecialchars($orden['descripcion_mantenimiento'] ?? 'Sin descripción')) ?></p>
                    </div>
                    <?php if (!empty($orden['descripcion_realizada'])): ?>
                        <hr>
                        <div>
                            <label class="text-muted small fw-semibold text-uppercase">Trabajo Realizado</label>
                            <p class="mt-2"><?= nl2br(htmlspecialchars($orden['descripcion_realizada'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Costos desglosados -->
                    <?php if (!empty($orden['costo_total'])): ?>
                        <hr>
                        <label class="text-muted small fw-semibold text-uppercase d-block mb-3">Desglose de Costos</label>
                        <div class="row g-2">
                            <div class="col-md-3 col-6">
                                <div class="costo-mini">
                                    <small class="text-muted d-block">Horas</small>
                                    <strong><?= number_format($orden['horas_trabajadas'] ?? 0, 2) ?> h</strong>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="costo-mini">
                                    <small class="text-muted d-block">Tarifa</small>
                                    <strong>S/ <?= number_format($orden['tarifa_tecnico'] ?? 0, 2) ?></strong>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="costo-mini">
                                    <small class="text-muted d-block">Mano de Obra</small>
                                    <strong>S/ <?= number_format($orden['costo_mano_obra'] ?? 0, 2) ?></strong>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="costo-mini">
                                    <small class="text-muted d-block">Repuestos</small>
                                    <strong>S/ <?= number_format($orden['costo_repuestos'] ?? 0, 2) ?></strong>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="costo-mini costo-total-mini">
                                    <small class="text-muted d-block">Costo Total</small>
                                    <strong class="text-success" style="font-size:1.2rem;">
                                        S/ <?= number_format($orden['costo_total'] ?? 0, 2) ?>
                                    </strong>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Columna lateral - Estado de supervisión -->
        <div class="col-lg-4">
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-clipboard-check text-primary me-2"></i> Estado de Supervisión
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($orden['supervision_estado'])): ?>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Estado</label>
                            <p class="mb-0">
                                <?php
                                $supEstado = $orden['supervision_estado'] ?? '';
                                $supColor = match($supEstado) {
                                    'APROBADA', 'aprobada' => 'success',
                                    'RECHAZADA', 'rechazada' => 'danger',
                                    default => 'warning'
                                };
                                ?>
                                <span class="badge bg-<?= $supColor ?> bg-opacity-10 text-<?= $supColor ?>">
                                    <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                    <?= htmlspecialchars($supEstado) ?>
                                </span>
                            </p>
                        </div>
                        <?php if (!empty($orden['supervision_calificacion'])): ?>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Calificación</label>
                                <p class="fw-semibold mb-0">
                                    <?= (int)$orden['supervision_calificacion'] ?>/5
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?= $i <= $orden['supervision_calificacion'] ? '' : '-o' ?> text-warning" style="font-size:0.75rem;"></i>
                                    <?php endfor; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($orden['supervision_observaciones'])): ?>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Observaciones</label>
                                <p class="small mb-0"><?= nl2br(htmlspecialchars($orden['supervision_observaciones'])) ?></p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-clock fa-2x text-muted mb-2 d-block"></i>
                            <p class="text-muted mb-0">Esta orden aún no ha sido supervisada</p>
                        </div>
                        <?php if (($orden['status'] ?? '') === 'CERRADA'): ?>
                            <a href="/proyecto/supervisor/revisar/<?= (int)$orden['id'] ?>" class="btn btn-primary w-100 mt-3">
                                <i class="fas fa-clipboard-check me-2"></i> Revisar Orden
                            </a>
                        <?php endif; ?>
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
.costo-mini {
    padding: 10px 12px;
    background: var(--bg-card-hover, #f8f9fa);
    border: 1px solid var(--border-color, #e9ecef);
    border-radius: 10px;
    text-align: center;
}
.costo-total-mini {
    background: linear-gradient(135deg, rgba(25, 135, 84, 0.1), rgba(25, 135, 84, 0.05));
    border-color: rgba(25, 135, 84, 0.3);
}
[data-theme="dark"] .costo-mini {
    background: #1a1a2e;
    border-color: #2a2a45;
}
[data-theme="dark"] .costo-total-mini {
    background: linear-gradient(135deg, rgba(25, 135, 84, 0.15), rgba(25, 135, 84, 0.05));
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>