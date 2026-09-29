<?php
// views/supervision/ver.php
// Detalle de Supervisión - VERSIÓN CORREGIDA
// ✅ FIX: eliminados TODOS los [cite: 8]
// ✅ FIX: usa $supervision['orden_titulo'] en vez de $supervision['orden']['titulo']
// ✅ FIX: botón "Volver a la Orden" apunta a /ordenes/ver/{id}

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

$estado = $supervision['estado'] ?? 'PENDIENTE';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-check text-primary me-2"></i>Detalle de Supervisión #<?= htmlspecialchars($supervision['id'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Información detallada de la supervisión
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/proyecto/supervision" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <a href="/proyecto/supervision/editar/<?= htmlspecialchars($supervision['id'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-warning">
                <i class="fas fa-edit me-1"></i> Editar
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
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($supervision['id'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Orden de Trabajo</label>
                                <p class="fw-semibold mb-0">#<?= htmlspecialchars($supervision['orden_id'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Supervisor</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($supervision['supervisor'] ?? $supervision['supervisor_nombre'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></p>
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
                                        <?= htmlspecialchars(ucfirst($estado), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Fecha de supervisión</label>
                                <p class="fw-semibold mb-0">
                                    <?= !empty($supervision['fecha_supervision'])
                                        ? htmlspecialchars($supervision['fecha_supervision'], ENT_QUOTES, 'UTF-8')
                                        : 'Pendiente' ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Calificación</label>
                                <p class="fw-semibold mb-0">
                                    <?php if (!empty($supervision['calificacion'])): ?>
                                        <span class="fw-bold"><?= htmlspecialchars($supervision['calificacion'], ENT_QUOTES, 'UTF-8') ?>/5</span>
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
                        <p class="mt-2"><?= nl2br(htmlspecialchars($supervision['observaciones'] ?? 'Sin observaciones', ENT_QUOTES, 'UTF-8')) ?></p>
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
                    <?php if (!empty($supervision['orden_titulo']) || !empty($supervision['num_om'])): ?>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">N° OM</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($supervision['num_om'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Título</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($supervision['orden_titulo'] ?? $supervision['titulo'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Estado</label>
                            <p class="mb-0">
                                <?php
                                $estadoOrden = $supervision['orden_status'] ?? $supervision['status'] ?? 'PENDIENTE';
                                $estadoOrdenColor = match(strtolower($estadoOrden)) {
                                    'cerrada', 'aprobada', 'ejecutada' => 'success',
                                    'en_proceso' => 'info',
                                    'cancelada', 'rechazada' => 'danger',
                                    default => 'warning'
                                };
                                ?>
                                <span class="badge bg-<?= $estadoOrdenColor ?> bg-opacity-10 text-<?= $estadoOrdenColor ?>">
                                    <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                    <?= htmlspecialchars(ucfirst($estadoOrden), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </p>
                        </div>
                        <a href="/proyecto/ordenes/ver/<?= (int)$supervision['orden_id'] ?>" class="btn btn-outline-info w-100">
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
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
.badge-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.75rem;
    display: inline-flex;
    align-items: center;
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>