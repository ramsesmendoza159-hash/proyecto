<?php
// views/backdating/ver.php
// Detalle de un registro de backdating
// ✅ FIX: usa $registro['motivo'] (columna real de la BD), no motivo_rechazo

if (!isset($seccion)) $seccion = 'backdating';
if (!isset($titulo)) $titulo = 'Detalle de Backdating';

$registro = $registro ?? null;
if (!$registro) {
    header('Location: /proyecto/backdating');
    exit;
}

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-history text-warning me-2"></i>Detalle de Backdating #<?= $registro['id'] ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Registro retroactivo de mantenimiento
            </p>
        </div>
        <a href="/proyecto/backdating" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Tipo</label>
                                <p><span class="badge bg-secondary"><?= htmlspecialchars(str_replace('_', ' ', $registro['tipo'])) ?></span></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Fecha Original (sistema)</label>
                                <p class="fw-semibold"><?= date('d/m/Y H:i', strtotime($registro['fecha_original'])) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Fecha Retroactiva (real)</label>
                                <p class="fw-semibold text-warning"><?= date('d/m/Y H:i', strtotime($registro['fecha_nueva'])) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Diferencia</label>
                                <p class="fw-semibold">
                                    <?php
                                    $diff = (new DateTime($registro['fecha_original']))->diff(new DateTime($registro['fecha_nueva']));
                                    echo $diff->days . ' días';
                                    ?>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Solicitante</label>
                                <p class="fw-semibold"><?= htmlspecialchars($registro['solicitante_nombre'] ?? 'N/A') ?></p>
                                <small class="text-muted"><?= htmlspecialchars($registro['rol_solicitante'] ?? '') ?></small>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Estado</label>
                                <p>
                                    <?php
                                    $color = match($registro['estado']) {
                                        'aprobado' => 'success',
                                        'rechazado' => 'danger',
                                        default => 'warning'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $color ?> px-3 py-2"><?= ucfirst($registro['estado']) ?></span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Fecha de Registro en Sistema</label>
                                <p><?= date('d/m/Y H:i', strtotime($registro['fecha_creacion'])) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">IP / User Agent</label>
                                <p class="small text-muted"><?= htmlspecialchars($registro['ip'] ?? 'N/A') ?></p>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Motivo del Backdating</label>
                        <div class="alert alert-warning mt-2">
                            <i class="fas fa-quote-left me-2"></i>
                            <?= nl2br(htmlspecialchars($registro['motivo'] ?? '')) ?>
                        </div>
                    </div>

                    <?php
                    // ✅ FIX: si el estado es rechazado, el motivo también contiene el motivo del rechazo
                    // El modelo BackdatingModel::rechazar() sobreescribe 'motivo' con el motivo del rechazo.
                    // Mostramos solo si está rechazado, como aclaración.
                    ?>
                    <?php if (($registro['estado'] ?? '') === 'rechazado'): ?>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Estado del Rechazo</label>
                            <div class="alert alert-danger mt-2">
                                <i class="fas fa-times-circle me-2"></i>
                                Este registro fue <strong>RECHAZADO</strong>. El motivo del rechazo está en el campo "Motivo" de arriba.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <?php if ($registro['estado'] === 'pendiente' && in_array($_SESSION['rol'] ?? '', ['admin', 'ingeniero'])): ?>
            <div class="card border-0 mb-4">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-gavel text-primary me-2"></i> Acciones
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="/proyecto/backdating/aprobar/<?= $registro['id'] ?>" class="mb-3">
                        <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                        <button type="submit" class="btn btn-success w-100"
                                onclick="return confirm('¿Aprobar este registro retroactivo?')">
                            <i class="fas fa-check me-2"></i> Aprobar
                        </button>
                    </form>

                    <hr>

                    <form method="POST" action="/proyecto/backdating/rechazar/<?= $registro['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Motivo del rechazo</label>
                            <textarea class="form-control" name="motivo_rechazo" rows="3" required
                                      placeholder="Explica por qué rechazas este registro..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger w-100"
                                onclick="return confirm('¿Rechazar este registro retroactivo?')">
                            <i class="fas fa-times me-2"></i> Rechazar
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-info-circle text-primary me-2"></i> Auditoría
                    </h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">
                        <i class="fas fa-shield-alt text-success me-1"></i>
                        Este registro fue auditado automáticamente con:
                    </p>
                    <ul class="small text-muted mb-0">
                        <li>IP del solicitante</li>
                        <li>User Agent del navegador</li>
                        <li>Fecha exacta del registro</li>
                        <li>Rol del solicitante</li>
                    </ul>
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