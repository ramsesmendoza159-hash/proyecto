<?php
// views/ingeniero/index.php
// Dashboard del Ingeniero - VERSIÓN ESTANDARIZADA
// ✅ FIX: Verificación de sesión y rol
// ✅ FIX: Guards para $total_pendientes y $pendientes
// ✅ FIX NUEVO: botones con btn-header-action y btn-icon

// Validar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    header('Location: /proyecto/auth/login');
    exit;
}

if (($_SESSION['rol'] ?? '') !== 'ingeniero') {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    header('Location: /proyecto/dashboard');
    exit;
}

// Guard
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

    <!-- Tarjeta de Firmas Pendientes -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 60px; height: 60px; background: rgba(139, 92, 246, 0.15);">
                            <i class="fas fa-hourglass-half fa-2x" style="color: #8b5cf6;"></i>
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
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>