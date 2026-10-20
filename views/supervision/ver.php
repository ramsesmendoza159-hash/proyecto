<?php
// views/supervision/ver.php
// Detalle de Supervisión - VERSIÓN CORREGIDA

require_once __DIR__ . '/../../helpers/SecurityHelper.php';

if (!SecurityHelper::verificarSesion()) {
    header('Location: /proyecto/auth/login');
    exit();
}

if (!SecurityHelper::verificarRol(['admin', 'supervisor'])) {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    header('Location: /proyecto/dashboard');
    exit();
}

$titulo = "Detalle de Supervisión";
$seccion = "supervision";
include_once __DIR__ . '/../layouts/header.php';

$supervision = $supervision ?? null;
if (!$supervision) {
    header('Location: /proyecto/supervision');
    exit();
}

// ✅ FIX: blindar TODOS los accesos
$sup_id        = (int)($supervision['id'] ?? 0);
$sup_orden_id  = (int)($supervision['orden_id'] ?? 0);
$sup_sup       = $supervision['supervisor'] ?? ($supervision['supervisor_nombre'] ?? 'N/A');
$sup_calif     = $supervision['calificacion'] ?? null;
$sup_estado    = $supervision['estado'] ?? 'PENDIENTE';
$sup_obs       = $supervision['observaciones'] ?? 'Sin observaciones';
$sup_cumple    = !empty($supervision['cumple']);
$sup_fecha_sup = $supervision['fecha_supervision'] ?? null;
$sup_num_om    = $supervision['num_om'] ?? 'N/A';
$sup_titulo    = $supervision['orden_titulo'] ?? ($supervision['titulo'] ?? 'N/A');
$sup_est_ord   = $supervision['orden_status'] ?? ($supervision['status'] ?? 'PENDIENTE');

$estado = $sup_estado;

$fecha_sup_val = !empty($sup_fecha_sup) && strtotime($sup_fecha_sup) !== false;
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
        <div class="d-flex gap-2">
            <a href="/proyecto/supervision" class="btn btn-secondary btn-header-action">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <a href="/proyecto/supervision/editar/<?= $sup_id ?>" class="btn btn-warning btn-header-action">
                <i class="fas fa-edit"></i> Editar
            </a>
        </div>
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
                                <label class="text-muted small fw-semibold text-uppercase">Orden de Trabajo</label>
                                <p class="fw-semibold mb-0">#<?= $sup_orden_id ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Supervisor</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($sup_sup) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Estado</label>
                                <p class="mb-0">
                                    <?php
                                    $estadoColor = match(strtolower($estado)) {
                                        'aprobada' => 'success',
                                        'rechazada' => 'danger',
                                        default => 'warning'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $estadoColor ?> bg-opacity-10 text-<?= $estadoColor ?>">
                                        <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                        <?= htmlspecialchars(ucfirst($estado)) ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Fecha de supervisión</label>
                                <p class="fw-semibold mb-0">
                                    <?= $fecha_sup_val
                                        ? htmlspecialchars(date('d/m/Y H:i', strtotime($sup_fecha_sup)))
                                        : 'Pendiente' ?>
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
                    <?php if (!empty($sup_titulo) && $sup_titulo !== 'N/A'): ?>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">N° OM</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($sup_num_om) ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Título</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($sup_titulo) ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Estado</label>
                            <p class="mb-0">
                                <?php
                                $estadoOrdenColor = match(strtolower($sup_est_ord)) {
                                    'cerrada', 'aprobada', 'ejecutada' => 'success',
                                    'en_proceso' => 'info',
                                    'cancelada', 'rechazada' => 'danger',
                                    default => 'warning'
                                };
                                ?>
                                <span class="badge bg-<?= $estadoOrdenColor ?> bg-opacity-10 text-<?= $estadoOrdenColor ?>">
                                    <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                    <?= htmlspecialchars(ucfirst($sup_est_ord)) ?>
                                </span>
                            </p>
                        </div>
                        <a href="/proyecto/ordenes/ver/<?= $sup_orden_id ?>" class="btn btn-outline-info w-100">
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
    box-shadow: 0 2px 12px var(--shadow-color);
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>