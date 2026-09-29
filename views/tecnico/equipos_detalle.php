<?php
// views/tecnico/equipo_detalle.php
// Detalle de Equipo - Técnico
// ✅ FIX: Inicializar $equipo y $ordenes con fallback
// ✅ FIX: Validar que $equipo exista antes de usar
// ✅ FIX: Corregido el estilo inline roto (falta un `"` antes de `>;`)
// ✅ FIX: Escapar el qr_url con htmlspecialchars
// ✅ FIX: Salida consistente con `status`

if (!isset($seccion)) {
    $seccion = 'tecnico';
}
if (!isset($titulo)) {
    $titulo = 'Detalle de Equipo';
}

// ✅ FIX: Fallback si el controlador no pasó las variables
$equipo = $equipo ?? null;
$ordenes = $ordenes ?? [];

if (!$equipo) {
    $_SESSION['error'] = 'Equipo no encontrado';
    header('Location: /proyecto/tecnico/mis_equipos');
    exit;
}

include_once __DIR__ . '/../layouts/header.php';

// ✅ FIX: Preparar variables para la vista (evita errores de undefined)
$estadoOp = $equipo['estado_operativo'] ?? 'Operativo';

$colorBg = 'rgba(13,110,253,0.1)';
$colorText = '#0d6efd';
$iconoEstado = 'fa-circle';

switch ($estadoOp) {
    case 'Operativo':
        $colorBg = 'rgba(25,135,84,0.1)';
        $colorText = '#198754';
        $iconoEstado = 'fa-check-circle';
        break;
    case 'En Mantenimiento':
        $colorBg = 'rgba(255,193,7,0.1)';
        $colorText = '#ffc107';
        $iconoEstado = 'fa-tools';
        break;
    case 'Averiado':
        $colorBg = 'rgba(220,53,69,0.1)';
        $colorText = '#dc3545';
        $iconoEstado = 'fa-exclamation-triangle';
        break;
    case 'Fuera de Servicio':
        $colorBg = 'rgba(108,117,125,0.1)';
        $colorText = '#6c757d';
        $iconoEstado = 'fa-times-circle';
        break;
}
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-microchip text-primary me-2"></i><?= htmlspecialchars($equipo['nombre_equipo'] ?? 'Equipo') ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Información del equipo y su historial
            </p>
        </div>
        <a href="/proyecto/tecnico/mis_equipos" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="row g-4">
        <!-- Información del equipo -->
        <div class="col-lg-4">

            <!-- QR Code -->
            <div class="card border-0 mb-4">
                <div class="card-body text-center">
                    <h6 class="fw-semibold mb-3">
                        <i class="fas fa-qrcode text-primary me-2"></i> QR del Equipo
                    </h6>
                    <?php if (!empty($equipo['qr_url'])): ?>
                        <img src="<?= htmlspecialchars($equipo['qr_url']) ?>" 
                             alt="QR del equipo" 
                             class="img-fluid" 
                             style="max-width:150px;">
                        <p class="text-muted small mt-2">Escanea para ver información</p>
                        <div class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Imprimir
                            </button>
                            <a href="<?= htmlspecialchars($equipo['qr_url']) ?>" 
                               download="qr_equipo_<?= (int)($equipo['id_equipo'] ?? 0) ?>.png" 
                               class="btn btn-sm btn-outline-success">
                                <i class="fas fa-download me-1"></i> Descargar
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-muted py-3">
                            <i class="fas fa-qrcode fa-3x d-block mb-2"></i>
                            <p class="mb-0">No hay código QR disponible</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Información -->
            <div class="card border-0">
                <div class="card-body">

                    <!-- Estado operativo -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width:48px;height:48px;border-radius:50%;background:<?= $colorBg ?>;color:<?= $colorText ?>;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">
                                <i class="fas <?= $iconoEstado ?>"></i>
                            </div>
                            <div>
                                <div class="text-muted small fw-semibold text-uppercase">Estado Operativo</div>
                                <div class="fw-bold" style="font-size:1.1rem;color:<?= $colorText ?>;">
                                    <?= htmlspecialchars($estadoOp) ?>
                                </div>
                                <?php if (!empty($equipo['observaciones_estado'])): ?>
                                    <small class="text-muted"><?= htmlspecialchars($equipo['observaciones_estado']) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Nombre</label>
                        <p class="fw-semibold mb-0"><?= htmlspecialchars($equipo['nombre_equipo'] ?? 'N/A') ?></p>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Marca / Modelo</label>
                        <p class="fw-semibold mb-0">
                            <?= htmlspecialchars($equipo['marca'] ?? 'N/A') ?> / 
                            <?= htmlspecialchars($equipo['modelo'] ?? 'N/A') ?>
                        </p>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Serie</label>
                        <p class="fw-semibold mb-0"><?= htmlspecialchars($equipo['serie'] ?? 'N/A') ?></p>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Ubicación</label>
                        <p class="fw-semibold mb-0">
                            <?= htmlspecialchars($equipo['nombre_planta'] ?? 'N/A') ?> → 
                            <?= htmlspecialchars($equipo['nombre_area'] ?? 'N/A') ?>
                        </p>
                    </div>

                    <?php if (!empty($equipo['especificaciones_tecnicas'])): ?>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Especificaciones Técnicas</label>
                            <p class="small mb-0"><?= nl2br(htmlspecialchars($equipo['especificaciones_tecnicas'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($equipo['manual_url'])): ?>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Manual</label>
                            <div>
                                <a href="<?= htmlspecialchars($equipo['manual_url']) ?>" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-file-pdf me-1"></i> Ver Manual
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($equipo['descripcion'])): ?>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Descripción</label>
                            <p class="small mb-0"><?= nl2br(htmlspecialchars($equipo['descripcion'])) ?></p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- Historial de órdenes -->
        <div class="col-lg-8">
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-history text-primary me-2"></i> Historial de Mantenimiento
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($ordenes)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                            No hay órdenes registradas para este equipo
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Título</th>
                                        <th>Técnico</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ordenes as $orden): 
                                        $status = $orden['status'] ?? 'PENDIENTE';
                                        $estadoColor = match($status) {
                                            'PENDIENTE' => 'warning',
                                            'EN_PROCESO' => 'info',
                                            'EJECUTADA' => 'primary',
                                            'CERRADA', 'APROBADA' => 'success',
                                            'CANCELADA', 'RECHAZADA' => 'danger',
                                            default => 'secondary',
                                        };
                                    ?>
                                        <tr>
                                            <td><span class="fw-semibold">#<?= (int)$orden['id'] ?></span></td>
                                            <td><?= htmlspecialchars($orden['titulo'] ?? 'Sin título') ?></td>
                                            <td><?= htmlspecialchars($orden['tecnico_nombre'] ?? 'Sin asignar') ?></td>
                                            <td>
                                                <span class="badge bg-<?= $estadoColor ?> bg-opacity-10 text-<?= $estadoColor ?>">
                                                    <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                                    <?= htmlspecialchars($status) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small>
                                                    <?= !empty($orden['fecha_creacion']) 
                                                        ? date('d/m/Y', strtotime($orden['fecha_creacion'])) 
                                                        : 'N/A' ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center">
                                                    <a href="/proyecto/ordenes/ver/<?= (int)$orden['id'] ?>" 
                                                       class="btn btn-sm btn-outline-info" 
                                                       title="Ver">
                                                        <i class="fas fa-eye"></i>
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
    </div>

</div>

<style>
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>