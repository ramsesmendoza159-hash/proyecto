<?php
// views/supervisor/ver_orden.php
// Ver Orden - VERSIÓN CORREGIDA

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

// ✅ FIX: blindar TODOS los accesos
$ord_id         = (int)($orden['id'] ?? 0);
$ord_num_om     = $orden['num_om'] ?? 'N/A';
$ord_titulo     = $orden['titulo'] ?? 'Sin título';
$ord_area       = $orden['nombre_area'] ?? 'N/A';
$ord_prior      = $orden['prioridad'] ?? 'Media';
$ord_status     = $orden['status'] ?? 'PENDIENTE';
$ord_tec_nom    = $orden['tecnico_nombre'] ?? 'Sin asignar';
$ord_fecha_c    = $orden['fecha_creacion'] ?? null;
$ord_fecha_e    = $orden['fecha_estimada'] ?? null;
$ord_fecha_f    = $orden['fecha_finalizacion'] ?? null;
$ord_horas      = $orden['horas_trabajadas'] ?? null;
$ord_repuestos  = $orden['repuestos_usados'] ?? 'Ninguno';
$ord_equipo     = $orden['nombre_equipo'] ?? 'N/A';
$ord_desc_m     = $orden['descripcion_mantenimiento'] ?? 'Sin descripción';
$ord_desc_r     = $orden['descripcion_realizada'] ?? '';
$ord_costo_t    = $orden['costo_total'] ?? 0;
$ord_costo_r    = $orden['costo_repuestos'] ?? 0;
$ord_costo_mo   = $orden['costo_mano_obra'] ?? 0;
$ord_tarifa     = $orden['tarifa_tecnico'] ?? 0;

$sup_estado     = $orden['supervision_estado'] ?? null;
$sup_calif      = $orden['supervision_calificacion'] ?? null;
$sup_obs        = $orden['supervision_observaciones'] ?? '';

$fecha_c_val = !empty($ord_fecha_c) && strtotime($ord_fecha_c) !== false;
$fecha_e_val = !empty($ord_fecha_e) && strtotime($ord_fecha_e) !== false;
$fecha_f_val = !empty($ord_fecha_f) && strtotime($ord_fecha_f) !== false;

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-list text-primary me-2"></i>Detalle de Orden #<?= $ord_id ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Información completa de la orden de trabajo
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/proyecto/supervisor/ordenes" class="btn btn-secondary btn-header-action">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <?php if ($ord_status === 'CERRADA' && empty($sup_estado)): ?>
                <a href="/proyecto/supervisor/revisar/<?= $ord_id ?>" class="btn btn-primary btn-header-action">
                    <i class="fas fa-clipboard-check"></i> Revisar
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
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($ord_num_om) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Título</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($ord_titulo) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Área</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($ord_area) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Prioridad</label>
                                <p class="mb-0">
                                    <?php
                                    $color = match($ord_prior) {
                                        'Urgente' => 'danger',
                                        'Alta'    => 'warning',
                                        'Media'   => 'info',
                                        'Baja'    => 'success',
                                        default   => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?>">
                                        <?= htmlspecialchars($ord_prior) ?>
                                    </span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Estado</label>
                                <p class="mb-0">
                                    <?php
                                    $estadoRaw = strtoupper($ord_status);
                                    $color = match($estadoRaw) {
                                        'CERRADA', 'APROBADA'   => 'success',
                                        'EN_PROCESO'            => 'info',
                                        'EJECUTADA'             => 'primary',
                                        'CANCELADA', 'RECHAZADA' => 'danger',
                                        default                  => 'warning'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?>">
                                        <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                        <?= htmlspecialchars($estadoRaw) ?>
                                    </span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Técnico</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($ord_tec_nom) ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Fecha creación</label>
                                <p class="fw-semibold mb-0">
                                    <?= $fecha_c_val ? date('d/m/Y H:i', strtotime($ord_fecha_c)) : 'N/A' ?>
                                </p>
                            </div>
                            <?php if ($fecha_e_val): ?>
                                <div class="mb-3">
                                    <label class="text-muted small fw-semibold text-uppercase">Fecha estimada</label>
                                    <p class="fw-semibold mb-0"><?= date('d/m/Y', strtotime($ord_fecha_e)) ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ($fecha_f_val): ?>
                                <div class="mb-3">
                                    <label class="text-muted small fw-semibold text-uppercase">Fecha cierre</label>
                                    <p class="fw-semibold mb-0"><?= date('d/m/Y H:i', strtotime($ord_fecha_f)) ?></p>
                                </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Horas trabajadas</label>
                                <p class="fw-semibold mb-0">
                                    <?= !empty($ord_horas) ? number_format($ord_horas, 2) . ' h' : 'N/A' ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Repuestos usados</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($ord_repuestos) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Equipo</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($ord_equipo) ?></p>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div>
                        <label class="text-muted small fw-semibold text-uppercase">Descripción del mantenimiento</label>
                        <p class="mt-2"><?= nl2br(htmlspecialchars($ord_desc_m)) ?></p>
                    </div>
                    <?php if (!empty($ord_desc_r)): ?>
                        <hr>
                        <div>
                            <label class="text-muted small fw-semibold text-uppercase">Trabajo Realizado</label>
                            <p class="mt-2"><?= nl2br(htmlspecialchars($ord_desc_r)) ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Costos -->
                    <?php if (!empty($ord_costo_t)): ?>
                        <hr>
                        <label class="text-muted small fw-semibold text-uppercase d-block mb-3">Desglose de Costos</label>
                        <div class="row g-2">
                            <div class="col-md-3 col-6">
                                <div class="costo-mini">
                                    <small class="text-muted d-block">Horas</small>
                                    <strong><?= number_format($ord_horas ?? 0, 2) ?> h</strong>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="costo-mini">
                                    <small class="text-muted d-block">Tarifa</small>
                                    <strong>S/ <?= number_format($ord_tarifa, 2) ?></strong>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="costo-mini">
                                    <small class="text-muted d-block">Mano de Obra</small>
                                    <strong>S/ <?= number_format($ord_costo_mo, 2) ?></strong>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="costo-mini">
                                    <small class="text-muted d-block">Repuestos</small>
                                    <strong>S/ <?= number_format($ord_costo_r, 2) ?></strong>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="costo-mini costo-total-mini">
                                    <small class="text-muted d-block">Costo Total</small>
                                    <strong class="text-success" style="font-size:1.2rem;">
                                        S/ <?= number_format($ord_costo_t, 2) ?>
                                    </strong>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Columna lateral -->
        <div class="col-lg-4">
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-clipboard-check text-primary me-2"></i> Estado de Supervisión
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($sup_estado)): ?>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Estado</label>
                            <p class="mb-0">
                                <?php
                                $supColor = match(strtoupper($sup_estado)) {
                                    'APROBADA'  => 'success',
                                    'RECHAZADA' => 'danger',
                                    default      => 'warning'
                                };
                                ?>
                                <span class="badge bg-<?= $supColor ?> bg-opacity-10 text-<?= $supColor ?>">
                                    <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                    <?= htmlspecialchars(ucfirst(strtolower($sup_estado))) ?>
                                </span>
                            </p>
                        </div>
                        <?php if (!empty($sup_calif)): ?>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Calificación</label>
                                <p class="fw-semibold mb-0">
                                    <?= (int)$sup_calif ?>/5
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?= $i <= $sup_calif ? '' : '-o' ?> text-warning" style="font-size:0.75rem;"></i>
                                    <?php endfor; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($sup_obs)): ?>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Observaciones</label>
                                <p class="small mb-0"><?= nl2br(htmlspecialchars($sup_obs)) ?></p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-clock fa-2x text-muted mb-2 d-block"></i>
                            <p class="text-muted mb-0">Esta orden aún no ha sido supervisada</p>
                        </div>
                        <?php if ($ord_status === 'CERRADA'): ?>
                            <a href="/proyecto/supervisor/revisar/<?= $ord_id ?>" class="btn btn-primary w-100 mt-3">
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
.card { border-radius: 16px; box-shadow: 0 2px 12px var(--shadow-color, rgba(0,0,0,0.04)); }
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