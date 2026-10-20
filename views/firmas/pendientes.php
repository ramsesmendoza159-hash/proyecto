<?php
// views/firmas/pendientes.php
// Panel de firmas pendientes


if (!isset($seccion)) $seccion = 'firmas';
if (!isset($titulo)) $titulo = 'Firmas Pendientes';

$pendientes = $pendientes ?? [];

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-signature text-primary me-2"></i>Firmas Pendientes
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Órdenes que esperan tu firma
            </p>
        </div>
        <a href="/proyecto/dashboard" class="btn btn-secondary btn-header-action">
            <i class="fas fa-arrow-left"></i> Volver
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

    <div class="card border-0">
        <div class="card-body p-0">
            <?php if (empty($pendientes)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                    <h5>¡No tienes firmas pendientes!</h5>
                    <p class="text-muted">Todas las órdenes han sido procesadas.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>N° OM</th>
                                <th>Título</th>
                                <th>Paso</th>
                                <th>Estado Orden</th>
                                <th>Fecha</th>
                                <th class="text-center" style="width:120px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendientes as $p): ?>
                                <tr>
                                    <td><span class="fw-semibold"><?= htmlspecialchars($p['num_om'] ?? 'N/A') ?></span></td>
                                    <td><?= htmlspecialchars($p['titulo'] ?? '') ?></td>
                                    <td>
                                        <span class="badge bg-primary">
                                            Paso <?= (int)($p['paso'] ?? 0) ?>: <?= htmlspecialchars($p['nombre_paso'] ?? '') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning"><?= htmlspecialchars($p['status'] ?? '') ?></span>
                                    </td>
                                    <td><small><?= date('d/m/Y', strtotime($p['fecha_creacion'] ?? 'now')) ?></small></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="/proyecto/firmas/firmar/<?= (int)$p['id'] ?>/<?= (int)($p['firma_id'] ?? 0) ?>" 
                                               class="btn btn-sm btn-outline-primary btn-icon"
                                               title="Firmar"
                                               aria-label="Firmar">
                                                <i class="fas fa-signature"></i>
                                            </a>
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
                        Mostrando <?= count($pendientes) ?> firma(s)
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<style>
.card { border-radius: 16px; box-shadow: 0 2px 12px var(--shadow-color); }
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>