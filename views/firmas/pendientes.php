<?php
// views/firmas/pendientes.php
// Panel de firmas pendientes
// ✅ FIX: guards para $pendientes

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
                Órdenes que esperan tu firma
            </p>
        </div>
        <a href="/proyecto/dashboard" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

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
                                <th class="text-center">Acciones</th>
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
                                    <td class="text-center">
                                        <a href="/proyecto/firmas/firmar/<?= (int)$p['id'] ?>/<?= (int)($p['firma_id'] ?? 0) ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-signature me-1"></i> Firmar
                                        </a>
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

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>