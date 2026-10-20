<?php
// views/checklists/ver_registro.php
// Detalle de un registro de checklist (solo lectura)
// ✅ FIX: SVGSanitizer centralizado
// ✅ FIX: blindaje de accesos a $registro['xxx'] con ?? default

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

require_once __DIR__ . '/../../helpers/SVGSanitizer.php';

// ✅ FIX: blindar TODOS los accesos
$secciones = $registro['secciones'] ?? [];
$matriz    = $registro['matriz'] ?? [];
$firmas    = $registro['firmas'] ?? [];
$resumen   = $registro['resumen'] ?? ['total' => 0, 'firmados' => 0, 'porcentaje' => 0, 'completa' => false];
$estado    = $registro['estado'] ?? 'BORRADOR';
$reg_id    = (int)($registro['id'] ?? 0);
$tipo_nom  = $registro['tipo_nombre'] ?? 'Checklist';
$fecha     = $registro['fecha'] ?? null;
$turno     = $registro['turno'] ?? null;
$paso_act  = $registro['paso_actual'] ?? null;

$fecha_valida = !empty($fecha) && strtotime($fecha) !== false;
$puede_editar = false; // Solo lectura en esta vista

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-check text-primary me-2"></i>
                <?= htmlspecialchars($tipo_nom) ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-calendar me-1"></i><?= $fecha_valida ? date('d/m/Y', strtotime($fecha)) : 'N/A' ?>
                <?php if (!empty($turno)): ?>
                    <span class="mx-2">|</span>
                    <span class="badge bg-dark"><?= htmlspecialchars($turno) ?></span>
                <?php endif; ?>
                <span class="mx-2">|</span>
                <span class="badge bg-<?= $estado === 'FIRMADO' ? 'success' : ($estado === 'CERRADO' ? 'info' : 'warning') ?>">
                    <?= htmlspecialchars($estado) ?>
                </span>
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="/proyecto/checklists/historial" class="btn btn-secondary btn-header-action">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <a href="/proyecto/checklists/reporte/<?= $reg_id ?>" target="_blank" class="btn btn-outline-primary btn-header-action">
                <i class="fas fa-print"></i> Imprimir
            </a>
            <?php if ($estado === 'CERRADO' && !empty($paso_act)): ?>
                <a href="/proyecto/checklists/firmar/<?= $reg_id ?>" class="btn btn-primary btn-header-action">
                    <i class="fas fa-signature"></i> Firmar
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
    <?php foreach ($secciones as $sec):
        $layout = $sec['layout_tipo'] ?? '';

        // Validar path traversal
        if (!preg_match('/^[a-z_]+$/', $layout)) {
            error_log("Layout inválido en ver_registro: {$layout}");
            echo '<div class="alert alert-danger">Layout inválido: ' . htmlspecialchars($layout) . '</div>';
            continue;
        }

        $layout_file = __DIR__ . '/layouts/' . $layout . '.php';
        $seccion_data = $sec;

        if (file_exists($layout_file)) {
            include $layout_file;
        } else {
            error_log("Layout no encontrado: {$layout_file}");
            echo '<div class="alert alert-warning">Layout no encontrado: ' . htmlspecialchars($layout) . '</div>';
        }
    endforeach; ?>

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
                    <?php foreach ($firmas as $f):
                        $f_id      = (int)($f['id'] ?? 0);
                        $f_paso    = (int)($f['paso'] ?? 0);
                        $f_rol     = $f['rol_firmante'] ?? '';
                        $f_turno   = $f['turno_firma'] ?? '';
                        $f_estado  = $f['estado'] ?? '';
                        $f_user    = $f['usuario_nombre'] ?? 'N/A';
                        $f_svg     = $f['firma_svg'] ?? '';
                        $f_fecha   = $f['fecha_firma'] ?? null;
                        $f_motivo  = $f['motivo_rechazo'] ?? '';

                        $f_fecha_val = !empty($f_fecha) && strtotime($f_fecha) !== false;
                        $badge = match($f_estado) {
                            'FIRMADO'   => 'success',
                            'RECHAZADO' => 'danger',
                            default     => 'secondary'
                        };
                    ?>
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-dark">Paso <?= $f_paso ?></span>
                                    <?php if (!empty($f_turno)): ?>
                                        <span class="badge bg-info"><?= htmlspecialchars($f_turno) ?></span>
                                    <?php endif; ?>
                                    <span class="badge bg-<?= $badge ?>">
                                        <?= htmlspecialchars($f_estado) ?>
                                    </span>
                                </div>
                                <div class="text-capitalize mb-1">
                                    <strong><?= htmlspecialchars($f_rol) ?></strong>
                                </div>
                                <?php if ($f_estado === 'FIRMADO'): ?>
                                    <small class="text-muted d-block">
                                        <?= htmlspecialchars($f_user) ?>
                                        <br><?= $f_fecha_val ? date('d/m/Y H:i', strtotime($f_fecha)) : 'N/A' ?>
                                    </small>
                                    <?php if (!empty($f_svg)): ?>
                                        <div class="mt-2 border rounded p-2 firma-svg-wrapper">
                                            <?= SVGSanitizer::sanitize($f_svg) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php elseif ($f_estado === 'RECHAZADO'): ?>
                                    <small class="text-danger"><?= htmlspecialchars($f_motivo) ?></small>
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
.firma-svg-wrapper {
    background: #ffffff !important;
    max-height: 80px;
    overflow: hidden;
}
[data-theme="dark"] .firma-svg-wrapper {
    background: #ffffff !important;
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>