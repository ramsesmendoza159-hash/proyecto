<?php
// views/tecnico/herramientas.php
// Herramientas Disponibles - Técnico

if (!isset($seccion)) {
    $seccion = 'tecnico';
}
if (!isset($titulo)) {
    $titulo = 'Herramientas y Repuestos';
}

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-tools text-primary me-2"></i>Herramientas y Repuestos
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Stock disponible para tus órdenes
            </p>
        </div>
        <a href="/proyecto/tecnico" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <?php if (empty($herramientas)): ?>
        <div class="card border-0">
            <div class="card-body text-center py-5">
                <i class="fas fa-tools fa-4x text-muted mb-3"></i>
                <h5>No hay herramientas disponibles</h5>
                <p class="text-muted">Contacta al almacén para verificar el inventario.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($herramientas as $item): ?>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between mb-3">
                                <div class="tool-icon" style="width:48px;height:48px;border-radius:12px;background:rgba(255,193,7,0.1);display:flex;align-items:center;justify-content:center;color:#ffc107;font-size:1.5rem;">
                                    <i class="fas fa-wrench"></i>
                                </div>
                                <span class="badge bg-<?php echo ($item['cantidad'] ?? 0) > 0 ? 'success' : 'danger'; ?> bg-opacity-10 text-<?php echo ($item['cantidad'] ?? 0) > 0 ? 'success' : 'danger'; ?>">
                                    <?php echo ($item['cantidad'] ?? 0) > 0 ? 'Disponible' : 'Agotado'; ?>
                                </span>
                            </div>
                            <h5 class="fw-semibold"><?php echo htmlspecialchars($item['nombre'] ?? 'Sin nombre'); ?></h5>
                            <p class="text-muted small mb-2">
                                <i class="fas fa-tag me-1"></i> <?php echo htmlspecialchars($item['codigo'] ?? 'N/A'); ?>
                            </p>
                            <p class="text-muted small mb-2">
                                <i class="fas fa-folder me-1"></i> <?php echo htmlspecialchars($item['categoria'] ?? 'General'); ?>
                            </p>
                            <p class="text-muted small mb-2">
                                <i class="fas fa-map-pin me-1"></i> <?php echo htmlspecialchars($item['ubicacion'] ?? 'Sin ubicación'); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                <div>
                                    <span class="badge bg-<?php echo ($item['cantidad'] ?? 0) <= ($item['stock_minimo'] ?? 5) ? 'warning' : 'success'; ?> bg-opacity-10 text-<?php echo ($item['cantidad'] ?? 0) <= ($item['stock_minimo'] ?? 5) ? 'warning' : 'success'; ?>">
                                        <i class="fas fa-box me-1"></i>
                                        <?php echo $item['cantidad'] ?? 0; ?> unidades
                                    </span>
                                </div>
                                <?php if (($item['cantidad'] ?? 0) > 0): ?>
                                    <span class="text-muted small">
                                        <i class="fas fa-check-circle text-success me-1"></i> Disponible
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">
                                        <i class="fas fa-times-circle text-danger me-1"></i> Agotado
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<style>
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
    transition: all 0.3s ease;
}
.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px var(--shadow-hover);
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>