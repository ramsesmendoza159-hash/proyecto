<?php
// views/operador/index.php
// Panel de Operador - VERSIÓN COMPLETA

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
            <a href="/proyecto/ordenes/crear" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> Nueva Orden
            </a>
            <a href="/proyecto/operador/ordenes" class="btn btn-secondary">
                <i class="fas fa-list me-1"></i> Mis Órdenes
            </a>
        </div>
    </div>

    <!-- ✅ Tarjetas de estadísticas -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card border-0">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label text-muted text-uppercase small fw-semibold">Total Órdenes</div>
                        <div class="stat-number fw-bold"><?php echo $estadisticas['total'] ?? 0; ?></div>
                    </div>
                    <div class="stat-icon" style="background:rgba(13,110,253,0.1);color:#0d6efd;">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card border-0">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label text-muted text-uppercase small fw-semibold">Pendientes</div>
                        <div class="stat-number fw-bold"><?php echo $estadisticas['pendientes'] ?? 0; ?></div>
                    </div>
                    <div class="stat-icon" style="background:rgba(255,193,7,0.1);color:#ffc107;">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card border-0">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label text-muted text-uppercase small fw-semibold">En Proceso</div>
                        <div class="stat-number fw-bold"><?php echo $estadisticas['en_proceso'] ?? 0; ?></div>
                    </div>
                    <div class="stat-icon" style="background:rgba(13,202,240,0.1);color:#0dcaf0;">
                        <i class="fas fa-spinner"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card border-0">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label text-muted text-uppercase small fw-semibold">Completadas</div>
                        <div class="stat-number fw-bold"><?php echo $estadisticas['completadas'] ?? 0; ?></div>
                    </div>
                    <div class="stat-icon" style="background:rgba(25,135,84,0.1);color:#198754;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ✅ Estilos -->
<style>
.stat-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
}
.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}
.stat-card .stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}
.stat-card .stat-number {
    font-size: 2rem;
    margin: 4px 0 2px;
    color: #1a1a2e;
}
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>