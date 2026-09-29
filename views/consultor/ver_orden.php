<?php
// views/consultor/ver_orden.php
// Ver Orden - Consultor (solo lectura) - VERSIÓN CORREGIDA
// ✅ FIX: campos reales del modelo OrdenTrabajo

if (!isset($seccion)) {
    $seccion = 'consultor';
}
if (!isset($titulo)) {
    $titulo = 'Detalle de Orden';
}
if (!isset($orden) || !$orden) {
    header('Location: /proyecto/consultor/ordenes');
    exit();
}

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-list text-primary me-2"></i>Detalle de Orden #<?= (int)$orden['id'] ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Información de la orden de trabajo
            </p>
        </div>
        <a href="/proyecto/consultor/ordenes" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
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
                                <label class="text-muted small fw-semibold text-uppercase">Título</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($orden['titulo'] ?? 'Sin título') ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Área</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($orden['nombre_area'] ?? 'N/A') ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Equipo</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($orden['nombre_equipo'] ?? 'N/A') ?></p>
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
                                    <label class="text-muted small fw-semibold text-uppercase">Fecha finalización</label>
                                    <p class="fw-semibold mb-0"><?= date('d/m/Y H:i', strtotime($orden['fecha_finalizacion'])) ?></p>
                                </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Técnico asignado</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($orden['tecnico_nombre'] ?? 'Sin asignar') ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Tipo de mantenimiento</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($orden['tipo_mantenimiento'] ?? 'N/A') ?></p>
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
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-money-bill-wave text-primary me-2"></i> Costos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Costo Repuestos</label>
                        <p class="fw-semibold mb-0 text-end">S/ <?= number_format($orden['costo_repuestos'] ?? 0, 2) ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Costo Mano Obra</label>
                        <p class="fw-semibold mb-0 text-end">S/ <?= number_format($orden['costo_mano_obra'] ?? 0, 2) ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Tarifa Técnico</label>
                        <p class="fw-semibold mb-0 text-end">S/ <?= number_format($orden['tarifa_tecnico'] ?? 0, 2) ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Horas Trabajadas</label>
                        <p class="fw-semibold mb-0 text-end"><?= number_format($orden['horas_trabajadas'] ?? 0, 1) ?> h</p>
                    </div>
                    <hr>
                    <div>
                        <label class="text-muted small fw-semibold text-uppercase">Costo Total</label>
                        <p class="fw-bold mb-0 text-end text-primary" style="font-size:1.2rem;">
                            S/ <?= number_format($orden['costo_total'] ?? 0, 2) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>