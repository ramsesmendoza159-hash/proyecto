<?php
// views/checklists/ver.php
// Detalle de un TIPO de checklist
// ✅ FIX: (int) sobre campo['orden'] con fallback

if (!isset($seccion)) $seccion = 'checklists';
if (!isset($titulo)) $titulo = 'Detalle del Checklist';

$tipo = $tipo ?? null;
if (!$tipo) {
    header('Location: /proyecto/checklists');
    exit;
}

$secciones = $tipo['secciones'] ?? [];
$firmas_config = $tipo['firmas_config'] ?? [];

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-check text-primary me-2"></i>
                <?= htmlspecialchars($tipo['nombre'] ?? 'Checklist') ?>
            </h4>
            <p class="text-muted small mb-0">
                <code><?= htmlspecialchars($tipo['codigo'] ?? '') ?></code>
                <span class="mx-2">|</span>
                <span class="badge bg-info bg-opacity-10 text-info">
                    <i class="fas fa-tag me-1"></i><?= htmlspecialchars($tipo['area'] ?? '') ?>
                </span>
                <span class="mx-2">|</span>
                <span class="badge bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-clock me-1"></i><?= htmlspecialchars($tipo['frecuencia'] ?? '') ?>
                </span>
                <?php if (!empty($tipo['requiere_turno'])): ?>
                    <span class="mx-2">|</span>
                    <span class="badge bg-warning text-dark">
                        <i class="fas fa-sun me-1"></i>Requiere Turno
                    </span>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="/proyecto/checklists" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <a href="/proyecto/checklists/iniciar/<?= (int)$tipo['id'] ?>" class="btn btn-primary">
                <i class="fas fa-play-circle me-1"></i> Iniciar Registro
            </a>
        </div>
    </div>

    <!-- Descripción -->
    <?php if (!empty($tipo['descripcion'])): ?>
    <div class="card border-0 mb-4">
        <div class="card-body">
            <label class="text-muted small fw-semibold text-uppercase mb-2">
                <i class="fas fa-align-left me-1"></i>Descripción
            </label>
            <p class="mb-0"><?= nl2br(htmlspecialchars($tipo['descripcion'])) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Resumen: secciones + firmas -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(13, 110, 253, 0.15); color: #0d6efd;">
                        <i class="fas fa-layer-group fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Secciones</span>
                        <span class="stat-number-modern"><?= count($secciones) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(25, 135, 84, 0.15); color: #198754;">
                        <i class="fas fa-signature fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Firmas Requeridas</span>
                        <span class="stat-number-modern"><?= count($firmas_config) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Secciones -->
    <div class="card border-0 mb-4">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-layer-group text-primary me-2"></i>Secciones Configuradas
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($secciones)): ?>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Este checklist no tiene secciones configuradas.
                </div>
            <?php else: ?>
                <?php foreach ($secciones as $index => $sec): ?>
                    <div class="seccion-card mb-3 p-3 rounded-3 border">
                        <div class="d-flex align-items-start gap-3">
                            <div class="seccion-badge flex-shrink-0">
                                <span class="badge bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                      style="width: 40px; height: 40px; font-size: 1.1rem;">
                                    <?= $index + 1 ?>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                    <div>
                                        <h6 class="fw-bold mb-1"><?= htmlspecialchars($sec['nombre'] ?? '') ?></h6>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                            <i class="fas fa-th-large me-1"></i>
                                            Layout: <?= htmlspecialchars($sec['layout_tipo'] ?? '') ?>
                                        </span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            <i class="fas fa-industry me-1"></i>
                                            <?= count($sec['equipos'] ?? []) ?> equipos
                                        </span>
                                        <span class="badge bg-warning bg-opacity-10 text-warning">
                                            <i class="fas fa-list me-1"></i>
                                            <?= count($sec['campos'] ?? []) ?> campos
                                        </span>
                                    </div>
                                </div>

                                <!-- Campos -->
                                <?php if (!empty($sec['campos'])): ?>
                                    <div class="mt-3">
                                        <small class="text-muted fw-semibold text-uppercase d-block mb-2">
                                            <i class="fas fa-columns me-1"></i>Campos / Columnas
                                        </small>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-0 small">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th style="width:40px;">#</th>
                                                        <th>Nombre</th>
                                                        <th>Tipo</th>
                                                        <th>Unidad</th>
                                                        <th class="text-center">Rango</th>
                                                        <th class="text-center">Opc.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($sec['campos'] as $campo): ?>
                                                        <tr>
                                                            <td><?= (int)($campo['orden'] ?? 0) ?></td>
                                                            <td><strong><?= htmlspecialchars($campo['nombre'] ?? '') ?></strong></td>
                                                            <td>
                                                                <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                                    <?= htmlspecialchars($campo['tipo'] ?? '') ?>
                                                                </span>
                                                            </td>
                                                            <td><?= htmlspecialchars($campo['unidad'] ?? '—') ?></td>
                                                            <td class="text-center">
                                                                <?php if (($campo['valor_min'] ?? null) !== null || ($campo['valor_max'] ?? null) !== null): ?>
                                                                    <small>
                                                                        <?= ($campo['valor_min'] ?? null) !== null ? htmlspecialchars($campo['valor_min']) : '∞' ?>
                                                                        -
                                                                        <?= ($campo['valor_max'] ?? null) !== null ? htmlspecialchars($campo['valor_max']) : '∞' ?>
                                                                    </small>
                                                                <?php else: ?>
                                                                    <span class="text-muted">—</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-center">
                                                                <?php if (!empty($campo['obligatorio'])): ?>
                                                                    <i class="fas fa-check text-success" title="Obligatorio"></i>
                                                                <?php else: ?>
                                                                    <i class="fas fa-times text-muted" title="Opcional"></i>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Equipos -->
                                <?php if (!empty($sec['equipos'])): ?>
                                    <div class="mt-3">
                                        <small class="text-muted fw-semibold text-uppercase d-block mb-2">
                                            <i class="fas fa-industry me-1"></i>Equipos Vinculados
                                        </small>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($sec['equipos'] as $eq): ?>
                                                <?php
                                                $nombre_eq = $eq['nombre_display']
                                                    ?? $eq['nombre_custom']
                                                    ?? $eq['nombre_equipo']
                                                    ?? 'Sin nombre';
                                                ?>
                                                <span class="badge bg-primary bg-opacity-10 text-primary p-2">
                                                    <i class="fas fa-cog me-1"></i>
                                                    <?= htmlspecialchars($nombre_eq) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Firmas configuradas -->
    <div class="card border-0">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-signature text-success me-2"></i>Secuencia de Firmas
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($firmas_config)): ?>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Este checklist no tiene firmas configuradas.
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($firmas_config as $firma): ?>
                        <div class="col-md-6">
                            <div class="firma-card p-3 rounded-3 border">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-dark">Paso <?= (int)($firma['paso'] ?? 0) ?></span>
                                    <strong class="text-capitalize"><?= htmlspecialchars($firma['rol_firmante'] ?? '') ?></strong>
                                    <?php if (!empty($firma['turno_firma'])): ?>
                                        <span class="badge bg-info ms-auto"><?= htmlspecialchars($firma['turno_firma']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="small mb-1"><?= htmlspecialchars($firma['nombre_paso'] ?? '') ?></p>
                                <?php if (!empty($firma['obligatorio'])): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success small">
                                        <i class="fas fa-check me-1"></i>Obligatorio
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary small">
                                        <i class="fas fa-clock me-1"></i>Opcional
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<style>
.stat-card-modern {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 20px 24px;
    transition: all 0.3s ease;
}
.stat-card-modern:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px var(--shadow-hover);
}
.stat-card-modern .stat-icon-modern {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.stat-card-modern .stat-label-modern {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
    font-weight: 600;
    display: block;
}
.stat-card-modern .stat-number-modern {
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--text-primary);
    display: block;
    line-height: 1.2;
}
.seccion-card {
    background: var(--bg-card);
    border-color: var(--border-color) !important;
    transition: all 0.3s ease;
}
.seccion-card:hover {
    border-color: rgba(13, 110, 253, 0.3) !important;
    box-shadow: 0 4px 12px var(--shadow-hover);
}
.firma-card {
    background: var(--bg-card);
    border-color: var(--border-color) !important;
    transition: all 0.3s ease;
}
.firma-card:hover {
    border-color: rgba(25, 135, 84, 0.3) !important;
}
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
