<?php
// views/tecnico/index.php
// Panel de Técnico - VERSIÓN COMPLETA CON DASHBOARD
// ✅ FIX: Variables $urgentes, $vencidas, $contadores calculadas localmente
// ✅ FIX: Botones cambiados a btn-outline-* (estilo admin)

if (!isset($seccion)) {
    $seccion = 'tecnico';
}
if (!isset($titulo)) {
    $titulo = 'Panel de Técnico';
}
if (!isset($estadisticas)) {
    $estadisticas = ['total' => 0, 'pendientes' => 0, 'en_progreso' => 0, 'completadas' => 0];
}
if (!isset($ordenes_recientes)) {
    $ordenes_recientes = [];
}

// ✅ FIX: Calcular variables que faltaban
$urgentes_count = 0;
$vencidas_count = 0;
$total_horas = 0;

foreach ($ordenes_recientes as $o) {
    if (($o['prioridad'] ?? '') === 'Urgente') {
        $urgentes_count++;
    }
    if (!empty($o['fecha_estimada']) 
        && strtotime($o['fecha_estimada']) < time() 
        && !in_array($o['status'] ?? '', ['CERRADA', 'APROBADA', 'CANCELADA'])) {
        $vencidas_count++;
    }
    $total_horas += (float)($o['horas_trabajadas'] ?? 0);
}

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-user-cog text-primary me-2"></i>Panel de Técnico
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/proyecto/tecnico/mis_ordenes" class="btn btn-primary">
                <i class="fas fa-list me-1"></i> Mis Órdenes
            </a>
        </div>
    </div>

    <!-- ✅ Alertas de órdenes críticas -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="alert alert-danger d-flex align-items-center gap-3 mb-0">
                <i class="fas fa-exclamation-triangle fa-2x"></i>
                <div>
                    <div class="fw-bold">Órdenes Urgentes</div>
                    <div class="h4 mb-0"><?php echo $urgentes_count; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="alert alert-warning d-flex align-items-center gap-3 mb-0">
                <i class="fas fa-clock fa-2x"></i>
                <div>
                    <div class="fw-bold">Órdenes Vencidas</div>
                    <div class="h4 mb-0"><?php echo $vencidas_count; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="alert alert-success d-flex align-items-center gap-3 mb-0">
                <i class="fas fa-chart-line fa-2x"></i>
                <div>
                    <div class="fw-bold">Horas Trabajadas</div>
                    <div class="h4 mb-0"><?php echo number_format($total_horas, 1); ?> h</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card border-0">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label text-muted text-uppercase small fw-semibold">Total Asignadas</div>
                        <div class="stat-number fw-bold" id="total"><?php echo $estadisticas['total'] ?? 0; ?></div>
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
                        <div class="stat-number fw-bold" id="pendientes"><?php echo $estadisticas['pendientes'] ?? 0; ?></div>
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
                        <div class="stat-label text-muted text-uppercase small fw-semibold">En Progreso</div>
                        <div class="stat-number fw-bold" id="en_progreso"><?php echo $estadisticas['en_progreso'] ?? 0; ?></div>
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
                        <div class="stat-number fw-bold" id="completadas"><?php echo $estadisticas['completadas'] ?? 0; ?></div>
                    </div>
                    <div class="stat-icon" style="background:rgba(25,135,84,0.1);color:#198754;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimas órdenes asignadas -->
    <div class="card border-0">
        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-clock text-info me-2"></i> Últimas Órdenes Asignadas
            </h5>
            <a href="/proyecto/tecnico/mis_ordenes" class="btn btn-sm btn-primary">
                Ver todas <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ordenes_recientes)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                    No hay órdenes asignadas
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ordenes_recientes as $orden): ?>
                                <tr>
                                    <td><span class="fw-semibold">#<?php echo $orden['id']; ?></span></td>
                                    <td><?php echo htmlspecialchars($orden['titulo'] ?? 'Sin título'); ?></td>
                                    <td>
                                        <?php
                                        $prioridadColor = match($orden['prioridad'] ?? 'Media') {
                                            'Baja' => 'success',
                                            'Media' => 'info',
                                            'Alta' => 'warning',
                                            'Urgente' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $prioridadColor; ?> bg-opacity-10 text-<?php echo $prioridadColor; ?>">
                                            <?php echo $orden['prioridad'] ?? 'Media'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $estadoColor = match($orden['status'] ?? 'PENDIENTE') {
                                            'PENDIENTE' => 'warning',
                                            'EN_PROCESO' => 'info',
                                            'EJECUTADA' => 'primary',
                                            'CERRADA' => 'success',
                                            'APROBADA' => 'success',
                                            'CANCELADA' => 'danger',
                                            'RECHAZADA' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge-status bg-<?php echo $estadoColor; ?> bg-opacity-10 text-<?php echo $estadoColor; ?>">
                                            <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                            <?php echo $orden['status'] ?? 'PENDIENTE'; ?>
                                        </span>
                                    </td>
                                    <td><small><?php echo date('d/m/Y', strtotime($orden['fecha_creacion'] ?? 'now')); ?></small></td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-1">
                                            <!-- ✅ FIX: Botón "Ver" cambiado a btn-outline-info -->
                                            <a href="/proyecto/tecnico/detalle_orden/<?php echo $orden['id']; ?>" class="btn btn-sm btn-outline-info" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (!in_array($orden['status'] ?? '', ['CERRADA', 'APROBADA', 'CANCELADA'])): ?>
                                                <!-- ✅ FIX: Botón "Cerrar" cambiado a btn-outline-success -->
                                                <a href="/proyecto/tecnico/cerrar_orden/<?php echo $orden['id']; ?>" class="btn btn-sm btn-outline-success" title="Cerrar">
                                                    <i class="fas fa-check-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Estilos -->
<style>
.stat-card {
    background: var(--bg-card) !important;
    border: 1px solid var(--border-color) !important;
    border-radius: 16px !important;
    padding: 1.5rem !important;
    box-shadow: 0 2px 12px var(--shadow-color) !important;
    transition: all 0.3s ease !important;
}
.stat-card:hover {
    transform: translateY(-4px) !important;
    box-shadow: 0 8px 25px var(--shadow-hover) !important;
}
.stat-card .stat-icon {
    width: 48px !important;
    height: 48px !important;
    border-radius: 12px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 1.4rem !important;
    flex-shrink: 0 !important;
}
.stat-card .stat-number {
    font-size: 2rem !important;
    margin: 4px 0 2px !important;
    color: var(--text-primary) !important;
}
.badge-status {
    padding: 4px 12px !important;
    border-radius: 20px !important;
    font-weight: 500 !important;
    font-size: 0.75rem !important;
    display: inline-flex !important;
    align-items: center !important;
}
.card {
    border-radius: 16px !important;
    box-shadow: 0 2px 12px var(--shadow-color) !important;
}
.alert {
    border-radius: 12px !important;
    border: none !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('/proyecto/tecnico/dashboardData')
        .then(response => response.json())
        .then(data => {
            if (data) {
                document.getElementById('total').textContent = data.total || 0;
                document.getElementById('pendientes').textContent = data.pendientes || 0;
                document.getElementById('en_progreso').textContent = data.en_progreso || 0;
                document.getElementById('completadas').textContent = data.completadas || 0;
            }
        })
        .catch(error => console.error('Error:', error));
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>