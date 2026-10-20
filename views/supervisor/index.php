<?php
// views/supervisor/index.php
// Panel del Supervisor - VERSIÓN COMPLETA

if (!isset($seccion)) $seccion = 'supervisor';
if (!isset($titulo)) $titulo = 'Panel de Supervisor';

$kpis = $kpis ?? [
    'total_ordenes' => 0,
    'pendientes_revision' => 0,
    'aprobadas' => 0,
    'rechazadas' => 0
];
$ordenes_pendientes = $ordenes_pendientes ?? [];

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-user-tie text-primary me-2"></i>Panel de Supervisor
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Revisa y aprueba las órdenes de trabajo cerradas
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/proyecto/supervisor/ordenes" class="btn btn-primary btn-header-action">
                <i class="fas fa-list"></i> Órdenes para Revisar
            </a>
            <a href="/proyecto/supervisor/supervisiones" class="btn btn-outline-info btn-header-action">
                <i class="fas fa-history"></i> Mis Supervisiones
            </a>
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

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(13,110,253,0.1);color:#0d6efd;">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div>
                        <div class="stat-label">Total Órdenes</div>
                        <div class="stat-number-mini"><?= number_format($kpis['total_ordenes'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(255,193,7,0.1);color:#ffc107;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <div class="stat-label">Pendientes Revisión</div>
                        <div class="stat-number-mini"><?= number_format($kpis['pendientes_revision'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(25,135,84,0.1);color:#198754;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-label">Aprobadas</div>
                        <div class="stat-number-mini"><?= number_format($kpis['aprobadas'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(220,53,69,0.1);color:#dc3545;">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div>
                        <div class="stat-label">Rechazadas</div>
                        <div class="stat-number-mini"><?= number_format($kpis['rechazadas'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Órdenes Pendientes -->
    <div class="card border-0">
        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-clock text-warning me-2"></i> Órdenes Pendientes de Revisión
            </h5>
            <a href="/proyecto/supervisor/ordenes" class="btn btn-sm btn-primary">
                Ver todas <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($ordenes_pendientes)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                    <h5>¡Todo al día!</h5>
                    <p class="text-muted small mb-0">No hay órdenes pendientes de revisión.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>N° OM</th>
                                <th>Título</th>
                                <th>Técnico</th>
                                <th>Fecha cierre</th>
                                <th class="text-center" style="width:120px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ordenes_pendientes as $orden):
                                // ✅ FIX: blindar TODOS los accesos
                                $o_id      = (int)($orden['id'] ?? 0);
                                $o_num_om  = $orden['num_om'] ?? 'N/A';
                                $o_titulo  = $orden['titulo'] ?? 'Sin título';
                                $o_tecnico = $orden['tecnico_nombre'] ?? 'Sin asignar';
                                $o_fecha   = $orden['fecha_finalizacion'] ?? null;

                                $fecha_val = !empty($o_fecha) && strtotime($o_fecha) !== false;
                            ?>
                                <tr>
                                    <td><span class="fw-semibold">#<?= $o_id ?></span></td>
                                    <td><?= htmlspecialchars($o_num_om) ?></td>
                                    <td><?= htmlspecialchars($o_titulo) ?></td>
                                    <td><?= htmlspecialchars($o_tecnico) ?></td>
                                    <td>
                                        <small><?= $fecha_val ? date('d/m/Y H:i', strtotime($o_fecha)) : 'N/A' ?></small>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="/proyecto/supervisor/revisar/<?= $o_id ?>"
                                               class="btn btn-sm btn-outline-info btn-icon"
                                               title="Revisar"
                                               aria-label="Revisar orden">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<style>
.stat-card-mini {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 16px 20px;
    box-shadow: 0 2px 8px var(--shadow-color);
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

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>