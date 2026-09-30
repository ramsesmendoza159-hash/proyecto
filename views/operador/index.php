<?php
// views/operador/index.php
// Panel de Operador - VERSIÓN ESTANDARIZADA
// ✅ FIX: cards usan variables CSS (funciona en modo claro y oscuro)
// ✅ FIX: agregada clase .stat-label
// ✅ FIX NUEVO: botones del header con btn-header-action
// ✅ FIX NUEVO: botón "Estadísticas" agregado (acceso directo al dashboard de admin)

if (!isset($seccion)) {
    $seccion = 'operador';
}
if (!isset($titulo)) {
    $titulo = 'Panel de Operador';
}
if (!isset($estadisticas)) {
    $estadisticas = ['total' => 0, 'pendientes' => 0, 'en_proceso' => 0, 'completadas' => 0];
}

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-user-cog text-primary me-2"></i>Panel de Operador
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Gestiona las órdenes de trabajo
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/proyecto/ordenes/crear" class="btn btn-primary btn-header-action">
                <i class="fas fa-plus-circle"></i> Nueva Orden
            </a>
            <a href="/proyecto/operador/ordenes" class="btn btn-secondary btn-header-action">
                <i class="fas fa-list"></i> Mis Órdenes
            </a>
            <a href="/proyecto/ordenes/estadisticas" class="btn btn-outline-info btn-header-action">
                <i class="fas fa-chart-bar"></i> Estadísticas
            </a>
        </div>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background:rgba(13,110,253,0.1);color:#0d6efd;">
                        <i class="fas fa-clipboard-list fa-2x"></i>
                    </div>
                    <div>
                        <div class="stat-label-modern">Total Órdenes</div>
                        <div class="stat-number-modern"><?php echo $estadisticas['total'] ?? 0; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background:rgba(255,193,7,0.1);color:#ffc107;">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                    <div>
                        <div class="stat-label-modern">Pendientes</div>
                        <div class="stat-number-modern"><?php echo $estadisticas['pendientes'] ?? 0; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background:rgba(13,202,240,0.1);color:#0dcaf0;">
                        <i class="fas fa-spinner fa-2x"></i>
                    </div>
                    <div>
                        <div class="stat-label-modern">En Proceso</div>
                        <div class="stat-number-modern"><?php echo $estadisticas['en_proceso'] ?? 0; ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background:rgba(25,135,84,0.1);color:#198754;">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <div>
                        <div class="stat-label-modern">Completadas</div>
                        <div class="stat-number-modern"><?php echo $estadisticas['completadas'] ?? 0; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Estilos -->
<style>
.stat-card-modern {
    background: var(--bg-card) !important;
    border: 1px solid var(--border-color) !important;
    border-radius: 16px !important;
    padding: 20px 24px !important;
    box-shadow: 0 2px 12px var(--shadow-color) !important;
    transition: all 0.3s ease !important;
    height: 100% !important;
}
.stat-card-modern:hover {
    transform: translateY(-4px) !important;
    box-shadow: 0 8px 30px var(--shadow-hover) !important;
}
.stat-card-modern .stat-icon-modern {
    width: 56px !important;
    height: 56px !important;
    border-radius: 14px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
}
.stat-card-modern .stat-label-modern {
    font-size: 0.7rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    color: var(--text-secondary) !important;
    font-weight: 600 !important;
    display: block !important;
}
.stat-card-modern .stat-number-modern {
    font-size: 1.6rem !important;
    font-weight: 700 !important;
    color: var(--text-primary) !important;
    display: block !important;
    line-height: 1.2 !important;
}
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>