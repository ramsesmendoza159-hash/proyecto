<?php
// views/ingeniero/index.php
// Dashboard del Ingeniero - VERSIÓN CORREGIDA
// ✅ FIX: admin también puede acceder (consistente con IngenieroController)
// ✅ FIX: guards para $total_pendientes y $pendientes
// ✅ FIX: botones con btn-header-action y btn-icon

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    header('Location: /proyecto/auth/login');
    exit;
}

// ✅ FIX: admin también puede acceder
$rol_actual = $_SESSION['rol'] ?? '';
if (!in_array($rol_actual, ['ingeniero', 'admin'], true)) {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    header('Location: /proyecto/dashboard');
    exit;
}

// Guards
$total_pendientes = $total_pendientes ?? 0;
$pendientes       = $pendientes ?? [];

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-drafting-compass text-primary me-2"></i>Panel del Ingeniero
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i>
                Revisión y aprobación de órdenes de trabajo
            </p>
        </div>
        <a href="/proyecto/firmas/pendientes" class="btn btn-primary btn-header-action">
            <i class="fas fa-signature"></i> Ver Firmas Pendientes
        </a>
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

    <!-- Tarjeta de Firmas Pendientes -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">
                        <i class="fas fa-hourglass-half fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Firmas Pendientes</span>
                        <span class="stat-number-modern"><?= (int)$total_pendientes ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Órdenes Pendientes -->
    <div class="card border-0">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-list text-primary me-2"></i>Órdenes Esperando tu Firma
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($pendientes)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                    <h5>¡No hay órdenes pendientes!</h5>
                    <p class="text-muted small mb-0">Todas las órdenes han sido procesadas.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>N° OM</th>
                                <th>Título</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th class="text-center" style="width:130px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendientes as $p): ?>
                                <?php
                                    $orden_id  = (int)($p['id'] ?? 0);
                                    $firma_id  = (int)($p['firma_id'] ?? 0);
                                    $num_om    = $p['num_om'] ?? 'N/A';
                                    $titulo_om = $p['titulo'] ?? '';
                                    $status    = $p['status'] ?? 'PENDIENTE';
                                    $fecha     = $p['fecha_creacion'] ?? null;
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($num_om) ?></strong></td>
                                    <td><?= htmlspecialchars($titulo_om) ?></td>
                                    <td>
                                        <span class="badge bg-warning">
                                            <?= htmlspecialchars($status) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <?= $fecha && strtotime($fecha) !== false 
                                                ? date('d/m/Y', strtotime($fecha)) 
                                                : 'N/A' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="/proyecto/firmas/firmar/<?= $orden_id ?>/<?= $firma_id ?>" 
                                               class="btn btn-sm btn-outline-primary btn-icon"
                                               title="Firmar orden"
                                               aria-label="Firmar orden">
                                                <i class="fas fa-signature"></i>
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
.stat-card-modern {
    background: var(--bg-card) !important;
    border: 1px solid var(--border-color) !important;
    border-radius: 16px !important;
    padding: 20px 24px !important;
    box-shadow: 0 2px 12px var(--shadow-color) !important;
    transition: all 0.3s ease !important;
    height: 100% !important;
}
.stat-card-modern:hover {
    transform: translateY(-4px) !important;
    box-shadow: 0 8px 30px var(--shadow-hover) !important;
}
.stat-card-modern .stat-icon-modern {
    width: 56px !important;
    height: 56px !important;
    border-radius: 14px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
}
.stat-card-modern .stat-label-modern {
    font-size: 0.7rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    color: var(--text-secondary) !important;
    font-weight: 600 !important;
    display: block !important;
}
.stat-card-modern .stat-number-modern {
    font-size: 1.6rem !important;
    font-weight: 700 !important;
    color: var(--text-primary) !important;
    display: block !important;
    line-height: 1.2 !important;
}
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>