<?php
// views/reportes/index.php
// Panel de Reportes - VERSIÓN CORREGIDA
// ✅ FIX: Eliminada doble inclusión de sidebar.php (ya está en header.php)

require_once __DIR__ . '/../../helpers/SecurityHelper.php';

if (!SecurityHelper::verificarSesion()) {
    header('Location: /proyecto/auth/login');
    exit;
}

if (!SecurityHelper::verificarRol('admin')) {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    header('Location: /proyecto/dashboard');
    exit;
}

$titulo = "Panel de Reportes";
$seccion = "reportes";

include_once __DIR__ . '/../layouts/header.php';
// ✅ FIX: Eliminado include_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-chart-bar text-primary me-2"></i>Panel de Reportes
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Genera reportes detallados del sistema
            </p>
        </div>
        <div>
            <span class="badge bg-primary bg-opacity-10 text-primary p-2">
                <i class="fas fa-calendar-alt me-1"></i> <?= date('d/m/Y H:i') ?>
            </span>
        </div>
    </div>

    <!-- Tarjetas de Reportes -->
    <div class="row g-4">
        
        <!-- Reporte de Órdenes -->
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card report-card border-0 h-100">
                <div class="card-body text-center p-4">
                    <div class="report-icon mx-auto mb-3" style="width:64px;height:64px;border-radius:16px;background:rgba(13,110,253,0.1);display:flex;align-items:center;justify-content:center;font-size:2rem;color:#0d6efd;">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h5 class="fw-bold">Reporte de Órdenes</h5>
                    <p class="text-muted small">Genera reportes detallados de todas las órdenes de trabajo con filtros por fecha, estado, prioridad y técnico.</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                        <span class="badge bg-primary bg-opacity-10 text-primary">Resumen</span>
                        <span class="badge bg-success bg-opacity-10 text-success">Detalle</span>
                        <span class="badge bg-info bg-opacity-10 text-info">Tiempos</span>
                    </div>
                    <a href="/proyecto/reportes/ordenes" class="btn btn-primary w-100">
                        <i class="fas fa-file-alt me-2"></i> Generar Reporte
                    </a>
                </div>
            </div>
        </div>

        <!-- Reporte de Técnicos -->
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card report-card border-0 h-100">
                <div class="card-body text-center p-4">
                    <div class="report-icon mx-auto mb-3" style="width:64px;height:64px;border-radius:16px;background:rgba(25,135,84,0.1);display:flex;align-items:center;justify-content:center;font-size:2rem;color:#198754;">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h5 class="fw-bold">Reporte de Técnicos</h5>
                    <p class="text-muted small">Analiza el rendimiento y productividad de los técnicos, con métricas de órdenes completadas y tiempos promedio.</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                        <span class="badge bg-success bg-opacity-10 text-success">Productividad</span>
                        <span class="badge bg-warning bg-opacity-10 text-warning">Ranking</span>
                        <span class="badge bg-info bg-opacity-10 text-info">Eficiencia</span>
                    </div>
                    <a href="/proyecto/reportes/tecnicos" class="btn btn-success w-100">
                        <i class="fas fa-file-alt me-2"></i> Generar Reporte
                    </a>
                </div>
            </div>
        </div>

        <!-- Reporte de Inventario -->
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card report-card border-0 h-100">
                <div class="card-body text-center p-4">
                    <div class="report-icon mx-auto mb-3" style="width:64px;height:64px;border-radius:16px;background:rgba(255,193,7,0.1);display:flex;align-items:center;justify-content:center;font-size:2rem;color:#ffc107;">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <h5 class="fw-bold">Reporte de Inventario</h5>
                    <p class="text-muted small">Visualiza el estado del inventario, incluyendo niveles de stock, valoración y rotación de productos.</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                        <span class="badge bg-warning bg-opacity-10 text-warning">Stock</span>
                        <span class="badge bg-danger bg-opacity-10 text-danger">Bajo stock</span>
                        <span class="badge bg-info bg-opacity-10 text-info">Valoración</span>
                    </div>
                    <a href="/proyecto/reportes/inventario" class="btn btn-warning w-100">
                        <i class="fas fa-file-alt me-2"></i> Generar Reporte
                    </a>
                </div>
            </div>
        </div>

        <!-- Reporte de Supervisión -->
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card report-card border-0 h-100">
                <div class="card-body text-center p-4">
                    <div class="report-icon mx-auto mb-3" style="width:64px;height:64px;border-radius:16px;background:rgba(13,202,240,0.1);display:flex;align-items:center;justify-content:center;font-size:2rem;color:#0dcaf0;">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h5 class="fw-bold">Reporte de Supervisión</h5>
                    <p class="text-muted small">Monitorea la supervisión de órdenes, incluyendo cumplimiento, calidad y observaciones de los supervisores.</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                        <span class="badge bg-info bg-opacity-10 text-info">Cumplimiento</span>
                        <span class="badge bg-success bg-opacity-10 text-success">Calidad</span>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary">Observaciones</span>
                    </div>
                    <a href="/proyecto/reportes/supervision" class="btn btn-info w-100 text-white">
                        <i class="fas fa-file-alt me-2"></i> Generar Reporte
                    </a>
                </div>
            </div>
        </div>

    </div>

    <!-- Gráfico Rápido -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-chart-pie text-primary me-2"></i>Resumen Rápido
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="stat-card-mini">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="stat-icon-mini" style="background:rgba(13,110,253,0.1);color:#0d6efd;">
                                        <i class="fas fa-clipboard-list"></i>
                                    </div>
                                    <div>
                                        <div class="stat-label">Total Órdenes</div>
                                        <div class="stat-number-mini" id="totalOrdenes">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card-mini">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="stat-icon-mini" style="background:rgba(25,135,84,0.1);color:#198754;">
                                        <i class="fas fa-users-cog"></i>
                                    </div>
                                    <div>
                                        <div class="stat-label">Técnicos Activos</div>
                                        <div class="stat-number-mini" id="totalTecnicos">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card-mini">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="stat-icon-mini" style="background:rgba(255,193,7,0.1);color:#ffc107;">
                                        <i class="fas fa-boxes"></i>
                                    </div>
                                    <div>
                                        <div class="stat-label">Ítems Inventario</div>
                                        <div class="stat-number-mini" id="totalInventario">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card-mini">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="stat-icon-mini" style="background:rgba(13,202,240,0.1);color:#0dcaf0;">
                                        <i class="fas fa-clipboard-check"></i>
                                    </div>
                                    <div>
                                        <div class="stat-label">Supervisiones</div>
                                        <div class="stat-number-mini" id="totalSupervisiones">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Estilos -->
<style>
.report-card {
    transition: all 0.3s ease;
    cursor: pointer;
}
.report-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.08);
}
.stat-card-mini {
    background: #fff;
    border-radius: 12px;
    padding: 16px 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: all 0.3s ease;
}
.stat-card-mini:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
}
.stat-card-mini .stat-icon-mini {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}
.stat-card-mini .stat-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    font-weight: 600;
}
.stat-card-mini .stat-number-mini {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a1a2e;
    line-height: 1.2;
}
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
</style>

<!-- Script para cargar estadísticas vía AJAX -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('/proyecto/api/dashboard/stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalOrdenes').textContent = data.data.ordenes.total || 0;
                document.getElementById('totalTecnicos').textContent = data.data.tecnicos.activos || 0;
                document.getElementById('totalInventario').textContent = data.data.inventario?.total || 0;
                document.getElementById('totalSupervisiones').textContent = data.data.supervisiones?.total || 0;
            }
        })
        .catch(() => {
            document.getElementById('totalOrdenes').textContent = '--';
            document.getElementById('totalTecnicos').textContent = '--';
            document.getElementById('totalInventario').textContent = '--';
            document.getElementById('totalSupervisiones').textContent = '--';
        });
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>