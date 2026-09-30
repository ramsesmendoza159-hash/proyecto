<?php
// views/seguridad/index.php
// Panel de Seguridad - VERSIÓN ESTANDARIZADA
// ✅ FIX NUEVO: botones con btn-icon y btn-header-action

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    header('Location: /proyecto/auth/login');
    exit;
}

if (($_SESSION['rol'] ?? '') !== 'seguridad') {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    header('Location: /proyecto/dashboard');
    exit;
}

// Guards
$seccion = $seccion ?? 'seguridad';
$titulo  = $titulo  ?? 'Panel de Seguridad';
$total_pendientes = $total_pendientes ?? 0;
$pendientes       = $pendientes ?? [];

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-shield-alt text-danger me-2"></i>Panel de Seguridad
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i>Validación final de seguridad de las órdenes
            </p>
        </div>
        <a href="/proyecto/firmas/pendientes" class="btn btn-outline-danger btn-header-action">
            <i class="fas fa-signature"></i> Ver Firmas Pendientes
        </a>
    </div>

    <!-- Tarjeta de Firmas Pendientes -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center seguridad-icon-bg"
                             style="width: 60px; height: 60px;">
                            <i class="fas fa-hourglass-half fa-2x seguridad-icon-color"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Firmas Pendientes</div>
                            <div class="h3 fw-bold mb-0"><?= (int)$total_pendientes ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Órdenes Pendientes -->
    <div class="card border-0">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-list text-primary me-2"></i>Órdenes Esperando Validación de Seguridad
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($pendientes)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-shield-alt fa-4x text-success mb-3"></i>
                    <h5>¡No hay validaciones pendientes!</h5>
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
                                <th class="text-center" style="width:130px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendientes as $p): ?>
                                <?php
                                $orden_id   = (int)($p['id'] ?? 0);
                                $firma_id   = (int)($p['firma_id'] ?? 0);
                                $num_om     = $p['num_om'] ?? 'N/A';
                                $titulo_om  = $p['titulo'] ?? '';
                                $status     = $p['status'] ?? 'PENDIENTE';
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($num_om, ENT_QUOTES, 'UTF-8') ?></strong></td>
                                    <td><?= htmlspecialchars($titulo_om, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge bg-warning">
                                            <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <?php if ($orden_id > 0 && $firma_id > 0): ?>
                                                <a href="/proyecto/firmas/firmar/<?= $orden_id ?>/<?= $firma_id ?>"
                                                   class="btn btn-sm btn-outline-danger btn-icon"
                                                   title="Validar firma de seguridad"
                                                   aria-label="Validar firma">
                                                    <i class="fas fa-signature"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>Datos incompletos
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-transparent">
                    <span class="text-muted small">
                        <i class="fas fa-list me-1"></i>
                        Mostrando <?= count($pendientes) ?> orden(es)
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<style>
.seguridad-icon-bg {
    background: rgba(239, 68, 68, 0.15);
}
.seguridad-icon-color {
    color: #ef4444;
}

[data-theme="dark"] .seguridad-icon-bg {
    background: rgba(248, 113, 113, 0.2) !important;
    box-shadow: 0 0 20px rgba(239, 68, 68, 0.25) !important;
}
[data-theme="dark"] .seguridad-icon-color {
    color: #f87171 !important;
}

.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>