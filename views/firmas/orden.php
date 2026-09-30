<?php
// views/firmas/orden.php
// Panel de firmas de una orden con timeline - CON OMITIDOS
// ✅ FIX: sanitizar firma_svg al mostrarla (antes era XSS vulnerable)

require_once __DIR__ . '/../../helpers/FirmaSecuenciaHelper.php';
require_once __DIR__ . '/../../helpers/SVGSanitizer.php';

if (!isset($orden) || !$orden) {
    header('Location: /proyecto/ordenes');
    exit;
}

// Guards
$firmas_secuenciales = $firmas_secuenciales ?? [];
$paso_actual_firma   = $paso_actual_firma ?? null;
$resumen             = $resumen ?? [
    'total' => 0, 'firmados' => 0, 'pendientes' => 0,
    'rechazados' => 0, 'omitidos' => 0, 'completa' => false, 'porcentaje' => 0
];
$puede_firmar_ahora  = $puede_firmar_ahora ?? false;

// Alias por compatibilidad
$firmas      = $firmas_secuenciales;
$pasoActual  = $paso_actual_firma;
$puedeFirmar = $puede_firmar_ahora;

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-signature text-primary me-2"></i>Firmas de la Orden
            </h4>
            <p class="text-muted small mb-0">
                <strong><?= htmlspecialchars($orden['num_om'] ?? 'Orden') ?></strong> | 
                <?= htmlspecialchars($orden['titulo'] ?? '') ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/proyecto/ordenes/ver/<?= (int)$orden['id'] ?>" class="btn btn-secondary btn-header-action">
                <i class="fas fa-arrow-left"></i> Volver a la Orden
            </a>
        </div>
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

    <!-- Resumen de progreso -->
    <div class="card border-0 mb-4">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-chart-line text-primary me-2"></i>Progreso de Firmas
            </h5>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2 flex-wrap">
                <span>
                    <strong><?= (int)($resumen['firmados'] ?? 0) ?></strong> de 
                    <strong><?= (int)($resumen['total'] ?? 0) ?></strong> firmas completadas
                    <?php if ((int)($resumen['omitidos'] ?? 0) > 0): ?>
                        <span class="badge bg-secondary ms-2">
                            <?= (int)$resumen['omitidos'] ?> omitidas
                        </span>
                    <?php endif; ?>
                </span>
                <span class="badge bg-<?= !empty($resumen['completa']) ? 'success' : 'warning' ?>">
                    <?= (int)($resumen['porcentaje'] ?? 0) ?>%
                </span>
            </div>
            <div class="progress" style="height: 25px;">
                <div class="progress-bar bg-success" 
                     style="width: <?= (int)($resumen['porcentaje'] ?? 0) ?>%">
                    <?= (int)($resumen['porcentaje'] ?? 0) ?>%
                </div>
            </div>
        </div>
    </div>

    <!-- Línea de tiempo de firmas -->
    <div class="card border-0">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-list-ol text-primary me-2"></i>Secuencia de Firmas
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($firmas_secuenciales)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                    No hay firmas registradas para esta orden
                </div>
            <?php else: ?>
                <?php foreach ($firmas_secuenciales as $firma): 
                    $es_paso_actual = $paso_actual_firma 
                        && (int)($paso_actual_firma['id'] ?? 0) === (int)($firma['id'] ?? 0);
                    $es_omitido = ($firma['estado'] ?? '') === 'OMITIDO';
                    
                    $rol_firmante = $firma['rol_firmante'] ?? '';
                    $color_rol = $es_omitido 
                        ? '#9ca3af' 
                        : FirmaSecuenciaHelper::obtenerColorPaso($rol_firmante);
                    $icono_rol = FirmaSecuenciaHelper::obtenerIconoPaso($rol_firmante);
                    
                    $badgeColor = match($firma['estado'] ?? '') {
                        'FIRMADO'   => 'success',
                        'RECHAZADO' => 'danger',
                        'PENDIENTE' => $es_paso_actual ? 'warning' : 'secondary',
                        'OMITIDO'   => 'secondary',
                        default     => 'secondary'
                    };
                    
                    $opacidad = $es_omitido ? 'opacity: 0.5;' : '';
                ?>
                    <div class="timeline-item d-flex mb-4 <?= $es_paso_actual ? 'timeline-active' : '' ?>"
                         style="<?= $opacidad ?>">
                        <div class="timeline-marker me-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center"
                                 style="width: 60px; height: 60px; background: <?= $color_rol ?>20; border: 2px solid <?= $color_rol ?>;">
                                <i class="fas <?= $icono_rol ?> fa-lg" style="color: <?= $color_rol ?>;"></i>
                            </div>
                        </div>
                        <div class="timeline-content flex-grow-1">
                            <div class="card border-<?= $badgeColor ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                                        <h5 class="card-title mb-1">
                                            <span class="badge bg-primary me-2">Paso <?= (int)($firma['paso'] ?? 0) ?></span>
                                            <span class="text-capitalize"><?= htmlspecialchars($rol_firmante) ?></span>
                                        </h5>
                                        <span class="badge bg-<?= $badgeColor ?>">
                                            <?php if ($es_omitido): ?>
                                                <i class="fas fa-ban me-1"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($firma['estado'] ?? '') ?>
                                        </span>
                                    </div>
                                    
                                    <?php if (($firma['estado'] ?? '') === 'FIRMADO'): ?>
                                        <p class="mb-1">
                                            <i class="fas fa-user me-1"></i>
                                            <strong><?= htmlspecialchars($firma['usuario_nombre'] ?? 'N/A') ?></strong>
                                        </p>
                                        <p class="mb-1 text-muted small">
                                            <i class="fas fa-clock me-1"></i>
                                            <?= !empty($firma['fecha_firma']) 
                                                ? date('d/m/Y H:i', strtotime($firma['fecha_firma'])) 
                                                : 'N/A' ?>
                                        </p>
                                        <?php if (!empty($firma['comentario'])): ?>
                                            <p class="mb-0 mt-2 small fst-italic">
                                                <i class="fas fa-comment me-1"></i>
                                                <?= nl2br(htmlspecialchars($firma['comentario'])) ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($firma['firma_svg'])): ?>
                                            <div class="mt-3 p-2 border rounded" style="background:#fff; max-height:100px; overflow:hidden;">
                                                <?= SVGSanitizer::sanitize($firma['firma_svg']) ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                    <?php elseif (($firma['estado'] ?? '') === 'RECHAZADO'): ?>
                                        <div class="alert alert-danger mb-0 mt-2 py-2">
                                            <strong><i class="fas fa-times-circle me-1"></i> Rechazado por:</strong>
                                            <?= htmlspecialchars($firma['usuario_nombre'] ?? 'N/A') ?><br>
                                            <small>Motivo: <?= htmlspecialchars($firma['motivo_rechazo'] ?? '') ?></small>
                                        </div>
                                        
                                    <?php elseif ($es_omitido): ?>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-ban me-1"></i>
                                            <strong>Paso omitido</strong> - No requiere firma para esta orden
                                        </p>
                                        
                                    <?php elseif ($es_paso_actual): ?>
                                        <p class="text-warning mb-2">
                                            <i class="fas fa-hourglass-half me-1"></i>
                                            <strong>Esperando firma</strong>
                                        </p>
                                        <?php if ($puede_firmar_ahora): ?>
                                            <a href="/proyecto/firmas/firmar/<?= (int)$orden['id'] ?>/<?= (int)$firma['id'] ?>" 
                                               class="btn btn-primary">
                                                <i class="fas fa-signature me-1"></i> Firmar Ahora
                                            </a>
                                        <?php else: ?>
                                            <small class="text-muted">
                                                <i class="fas fa-lock me-1"></i>
                                                Requiere rol: <?= htmlspecialchars($rol_firmante) ?>
                                            </small>
                                        <?php endif; ?>
                                        
                                    <?php else: ?>
                                        <p class="text-muted mb-0">
                                            <i class="fas fa-lock me-1"></i>
                                            Pendiente de pasos anteriores
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<style>
.timeline-item {
    transition: all 0.3s ease;
    position: relative;
}
.timeline-active .timeline-marker {
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}
.timeline-marker {
    flex-shrink: 0;
}
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>