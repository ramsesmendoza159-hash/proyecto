<?php
// views/calidad/index.php
// Dashboard de Calidad - VERSIÓN ESTANDARIZADA
// ✅ FIX: guards para $total_pendientes y $pendientes
// ✅ FIX: verificación de sesión y rol
// ✅ FIX NUEVO: botones con btn-icon y btn-header-action

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    header('Location: /proyecto/auth/login');
    exit;
}

if (($_SESSION['rol'] ?? '') !== 'calidad') {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    header('Location: /proyecto/dashboard');
    exit;
}

// Guards
$total_pendientes = $total_pendientes ?? 0;
$pendientes = $pendientes ?? [];

if (!isset($seccion)) $seccion = 'calidad';
if (!isset($titulo)) $titulo = 'Panel de Calidad';

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-check text-info me-2"></i>Panel de Calidad
            </h4>
            <p class="text-muted small mb-0">
                Validación de calidad pre y post ejecución
            </p>
        </div>
        <a href="/proyecto/firmas/pendientes" class="btn btn-info text-white btn-header-action">
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

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 60px; height: 60px; background: rgba(6, 182, 212, 0.15);">
                            <i class="fas fa-hourglass-half fa-2x" style="color: #06b6d4;"></i>
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

    <div class="card border-0">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-list text-primary me-2"></i>Órdenes Esperando Validación
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($pendientes)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
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
                                <th>Paso</th>
                                <th>Estado</th>
                                <th class="text-center" style="width:130px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendientes as $p): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($p['num_om'] ?? 'N/A') ?></strong></td>
                                    <td><?= htmlspecialchars($p['titulo'] ?? '') ?></td>
                                    <td>
                                        <span class="badge bg-info text-white">
                                            Paso <?= (int)($p['paso'] ?? 0) ?>: <?= htmlspecialchars($p['nombre_paso'] ?? '') ?>
                                        </span>
                                    </td>
                                    <td><span class="badge bg-warning"><?= htmlspecialchars($p['status'] ?? '') ?></span></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="/proyecto/firmas/firmar/<?= (int)$p['id'] ?>/<?= (int)$p['firma_id'] ?>"
                                               class="btn btn-sm btn-outline-info btn-icon"
                                               title="Validar firma"
                                               aria-label="Validar firma">
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