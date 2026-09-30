<?php
// views/checklists/ver_registro.php
// Detalle de un registro de checklist (solo lectura)
// ✅ FIX: usar SVGSanitizer centralizado

// Validar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    header('Location: /proyecto/auth/login');
    exit;
}

if (!isset($seccion)) $seccion = 'checklists';
if (!isset($titulo)) $titulo = 'Ver Registro';

$registro = $registro ?? null;
if (!$registro) {
    header('Location: /proyecto/checklists');
    exit;
}

// ✅ Usar el helper centralizado
require_once __DIR__ . '/../../helpers/SVGSanitizer.php';

$secciones = $registro['secciones'] ?? [];
$matriz = $registro['matriz'] ?? [];
$firmas = $registro['firmas'] ?? [];
$resumen = $registro['resumen'] ?? ['total' => 0, 'firmados' => 0, 'porcentaje' => 0, 'completa' => false];
$estado = $registro['estado'] ?? 'BORRADOR';
$puede_editar = false; // Solo lectura en esta vista

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-check text-primary me-2"></i>
                <?= htmlspecialchars($registro['tipo_nombre'] ?? 'Checklist') ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-calendar me-1"></i><?= date('d/m/Y', strtotime($registro['fecha'] ?? 'now')) ?>
                <?php if (!empty($registro['turno'])): ?>
                    <span class="mx-2">|</span>
                    <span class="badge bg-dark"><?= htmlspecialchars($registro['turno']) ?></span>
                <?php endif; ?>
                <span class="mx-2">|</span>
                <span class="badge bg-<?= $estado === 'FIRMADO' ? 'success' : ($estado === 'CERRADO' ? 'info' : 'warning') ?>">
                    <?= htmlspecialchars($estado) ?>
                </span>
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="/proyecto/checklists/historial" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <a href="/proyecto/checklists/reporte/<?= (int)$registro['id'] ?>" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-print me-1"></i> Imprimir
            </a>
            <?php if ($estado === 'CERRADO' && !empty($registro['paso_actual'])): ?>
                <a href="/proyecto/checklists/firmar/<?= (int)$registro['id'] ?>" class="btn btn-primary">
                    <i class="fas fa-signature me-1"></i> Firmar
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Resumen firmas -->
    <div class="card border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <span><strong><?= (int)($resumen['firmados'] ?? 0) ?></strong> / <strong><?= (int)($resumen['total'] ?? 0) ?></strong> firmas</span>
                <span class="badge bg-<?= !empty($resumen['completa']) ? 'success' : 'warning' ?>"><?= (int)($resumen['porcentaje'] ?? 0) ?>%</span>
            </div>
            <div class="progress" style="height: 10px; border-radius: 10px;">
                <div class="progress-bar bg-success" style="width: <?= (int)($resumen['porcentaje'] ?? 0) ?>%; border-radius: 10px;"></div>
            </div>
        </div>
    </div>

    <!-- Secciones -->
    <?php foreach ($secciones as $sec): ?>
        <?php
            $layout = $sec['layout_tipo'] ?? '';

            // Validar path traversal
            if (!preg_match('/^[a-z_]+$/', $layout)) {
                error_log("Layout inválido en ver_registro: {$layout}");
                echo '<div class="alert alert-danger">Layout inválido: ' . htmlspecialchars($layout) . '</div>';
                continue;
            }

            $layout_file = __DIR__ . '/layouts/' . $layout . '.php';

            // ✅ Usar $seccion_data (NO $seccion)
            $seccion_data = $sec;

            if (file_exists($layout_file)) {
                // $seccion_data, $matriz, $registro, $puede_editar están disponibles
                include $layout_file;
            } else {
                error_log("Layout no encontrado: {$layout_file}");
                echo '<div class="alert alert-warning">Layout no encontrado: ' . htmlspecialchars($layout) . '</div>';
            }
        ?>
    <?php endforeach; ?>

    <!-- Firmas -->
    <?php if (!empty($firmas)): ?>
        <div class="card border-0 mt-4">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="mb-0 fw-semibold">
                    <i class="fas fa-signature text-primary me-2"></i>Firmas
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($firmas as $f): ?>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-dark">Paso <?= (int)($f['paso'] ?? 0) ?></span>
                                    <?php if (!empty($f['turno_firma'])): ?>
                                        <span class="badge bg-info"><?= htmlspecialchars($f['turno_firma']) ?></span>
                                    <?php endif; ?>
                                    <span class="badge bg-<?= ($f['estado'] ?? '') === 'FIRMADO' ? 'success' : (($f['estado'] ?? '') === 'RECHAZADO' ? 'danger' : 'secondary') ?>">
                                        <?= htmlspecialchars($f['estado'] ?? '') ?>
                                    </span>
                                </div>
                                <div class="text-capitalize mb-1"><strong><?= htmlspecialchars($f['rol_firmante'] ?? '') ?></strong></div>
                                <?php if (($f['estado'] ?? '') === 'FIRMADO'): ?>
                                    <small class="text-muted d-block">
                                        <?= htmlspecialchars($f['usuario_nombre'] ?? 'N/A') ?>
                                        <br><?= date('d/m/Y H:i', strtotime($f['fecha_firma'] ?? 'now')) ?>
                                    </small>
                                    <?php if (!empty($f['firma_svg'])): ?>
                                        <div class="mt-2 border rounded p-2" style="background:#fff;max-height:80px;overflow:hidden;">
                                            <?= SVGSanitizer::sanitize($f['firma_svg']) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php elseif (($f['estado'] ?? '') === 'RECHAZADO'): ?>
                                    <small class="text-danger"><?= htmlspecialchars($f['motivo_rechazo'] ?? '') ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<style>
.card { border-radius: 16px; box-shadow: 0 2px 12px var(--shadow-color); }
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>