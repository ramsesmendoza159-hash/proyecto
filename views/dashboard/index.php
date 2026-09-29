<?php
// views/admin/dashboard.php
// Dashboard de administrador - VERSIÓN FINAL OPTIMIZADA

// Verificar sesión
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: /proyecto/auth/login');
    exit;
}

$seccion = 'dashboard';
$titulo = 'Dashboard - Administrador';

// Obtener estadísticas
require_once __DIR__ . '/../../model/OrdenTrabajo.php';
require_once __DIR__ . '/../../model/Tecnico.php';
require_once __DIR__ . '/../../model/Supervisor.php';
require_once __DIR__ . '/../../config/database.php';

$ordenModel = new OrdenTrabajo();
$tecnicoModel = new Tecnico();
$supervisorModel = new Supervisor();

$estadisticas_ordenes = $ordenModel->obtenerEstadisticas();
$estadisticas_tecnicos = $tecnicoModel->obtenerEstadisticas();
$estadisticas_supervisores = $supervisorModel->obtenerEstadisticas();

// ✅ Estadísticas de usuarios manualmente
try {
    $db = Database::getInstance()->getConnection();
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as inactivos
            FROM usuarios";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $estadisticas_usuarios = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'activos' => 0, 'inactivos' => 0];
} catch (Exception $e) {
    error_log("Error al obtener estadísticas de usuarios: " . $e->getMessage());
    $estadisticas_usuarios = ['total' => 0, 'activos' => 0, 'inactivos' => 0];
}

// ✅ Órdenes recientes
$ordenes_recientes = $ordenModel->obtenerTodos([], 5, 0);

include_once __DIR__ . '/../layouts/header.php';
// ❌ ELIMINAR: include_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid px-0">

    <!-- ✅ Saludo -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">👋 ¡Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?>!</h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-calendar-alt me-1"></i> <?php echo date('l, d \d\e F \d\e Y'); ?>
                <span class="mx-2">|</span>
                <i class="fas fa-clock me-1"></i> <?php echo date('H:i'); ?>
            </p>
        </div>
        <div>
            <span class="badge bg-primary bg-opacity-10 text-primary p-2">
                <i class="fas fa-user-shield me-1"></i> Administrador
            </span>
        </div>
    </div>

    <!-- ✅ Tarjetas de Estadísticas -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div>
                        <div class="stat-label">Total Órdenes</div>
                        <div class="stat-number-mini"><?php echo number_format($estadisticas_ordenes['total'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="mt-2 d-flex gap-1 flex-wrap">
                    <span class="badge bg-warning bg-opacity-10 text-warning small">
                        <i class="fas fa-clock me-1" style="font-size:8px;"></i> <?php echo $estadisticas_ordenes['pendientes'] ?? 0; ?> pend.
                    </span>
                    <span class="badge bg-success bg-opacity-10 text-success small">
                        <i class="fas fa-check-circle me-1" style="font-size:8px;"></i> <?php echo $estadisticas_ordenes['cerradas'] ?? 0; ?> cerr.
                    </span>
                    <span class="badge bg-info bg-opacity-10 text-info small">
                        <i class="fas fa-play-circle me-1" style="font-size:8px;"></i> <?php echo $estadisticas_ordenes['en_proceso'] ?? 0; ?> prog.
                    </span>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <div>
                        <div class="stat-label">Técnicos</div>
                        <div class="stat-number-mini"><?php echo number_format($estadisticas_tecnicos['total'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="mt-2 d-flex gap-1 flex-wrap">
                    <span class="badge bg-success bg-opacity-10 text-success small">
                        <i class="fas fa-circle me-1" style="font-size:8px;"></i> <?php echo $estadisticas_tecnicos['activos'] ?? 0; ?> activos
                    </span>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary small">
                        <i class="fas fa-circle me-1" style="font-size:8px;"></i> <?php echo $estadisticas_tecnicos['inactivos'] ?? 0; ?> inact.
                    </span>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background: rgba(13, 202, 240, 0.1); color: #0dcaf0;">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div>
                        <div class="stat-label">Supervisores</div>
                        <div class="stat-number-mini"><?php echo number_format($estadisticas_supervisores['total'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="mt-2 d-flex gap-1 flex-wrap">
                    <span class="badge bg-success bg-opacity-10 text-success small">
                        <i class="fas fa-circle me-1" style="font-size:8px;"></i> <?php echo $estadisticas_supervisores['activos'] ?? 0; ?> activos
                    </span>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary small">
                        <i class="fas fa-circle me-1" style="font-size:8px;"></i> <?php echo $estadisticas_supervisores['inactivos'] ?? 0; ?> inact.
                    </span>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background: rgba(255, 193, 7, 0.1); color: #ffc107;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <div class="stat-label">Usuarios</div>
                        <div class="stat-number-mini"><?php echo number_format($estadisticas_usuarios['total'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="mt-2 d-flex gap-1 flex-wrap">
                    <span class="badge bg-success bg-opacity-10 text-success small">
                        <i class="fas fa-circle me-1" style="font-size:8px;"></i> <?php echo $estadisticas_usuarios['activos'] ?? 0; ?> activos
                    </span>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary small">
                        <i class="fas fa-circle me-1" style="font-size:8px;"></i> <?php echo $estadisticas_usuarios['inactivos'] ?? 0; ?> inact.
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ Gráficos -->
    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="card border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-chart-doughnut text-primary me-2"></i> Distribución de Órdenes
                    </h5>
                </div>
                <div class="card-body" style="height: 280px;">
                    <canvas id="ordenesChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-chart-bar text-success me-2"></i> Resumen por Tipo
                    </h5>
                </div>
                <div class="card-body" style="height: 280px;">
                    <canvas id="resumenChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ Accesos Rápidos y Últimas Órdenes -->
    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-rocket text-warning me-2"></i> Accesos Rápidos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="/proyecto/ordenes/crear" class="text-decoration-none">
                                <div class="quick-action p-3 text-center rounded-3 border">
                                    <i class="fas fa-plus-circle fa-2x text-primary"></i>
                                    <h6 class="mt-2 mb-0 small">Nueva Orden</h6>
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="/proyecto/tecnicos/crear" class="text-decoration-none">
                                <div class="quick-action p-3 text-center rounded-3 border">
                                    <i class="fas fa-user-plus fa-2x text-success"></i>
                                    <h6 class="mt-2 mb-0 small">Nuevo Técnico</h6>
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="/proyecto/supervisores/crear" class="text-decoration-none">
                                <div class="quick-action p-3 text-center rounded-3 border">
                                    <i class="fas fa-user-tie fa-2x text-info"></i>
                                    <h6 class="mt-2 mb-0 small">Nuevo Supervisor</h6>
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="/proyecto/reportes/financieros" class="text-decoration-none">
                                <div class="quick-action p-3 text-center rounded-3 border">
                                    <i class="fas fa-money-bill-wave fa-2x text-warning"></i>
                                    <h6 class="mt-2 mb-0 small">Financieros</h6>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card border-0 h-100">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-clock text-info me-2"></i> Últimas Órdenes
                    </h5>
                    <a href="/proyecto/ordenes" class="btn btn-sm btn-primary">Ver todas <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
                <div class="card-body">
                    <?php if (count($ordenes_recientes) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:50px;">#</th>
                                    <th>Descripción</th>
                                    <th style="width:120px;">Estado</th>
                                    <th style="width:100px;">Prioridad</th>
                                    <th style="width:100px;">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ordenes_recientes as $orden): ?>
                                <tr>
                                    <td><span class="fw-semibold">#<?php echo $orden['id']; ?></span></td>
                                    <td><?php echo htmlspecialchars(substr($orden['descripcion'] ?? '', 0, 40)); ?>...</td>
                                    <td>
                                        <?php
                                        $estado = $orden['status'] ?? $orden['estado'] ?? 'PENDIENTE';
                                        $badge = match(strtoupper($estado)) {
                                            'PENDIENTE' => 'warning',
                                            'EN_PROCESO' => 'info',
                                            'EJECUTADA' => 'primary',
                                            'CERRADA' => 'success',
                                            'CANCELADA' => 'danger',
                                            'APROBADA' => 'success',
                                            'RECHAZADA' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $badge; ?> bg-opacity-10 text-<?php echo $badge; ?> px-3 py-2">
                                            <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                            <?php echo str_replace('_', ' ', $estado); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $prioridad = $orden['prioridad'] ?? 'Media';
                                        $color = match($prioridad) {
                                            'Urgente' => 'danger',
                                            'Alta' => 'warning',
                                            'Media' => 'info',
                                            'Baja' => 'success',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?> bg-opacity-10 text-<?php echo $color; ?> px-3 py-2">
                                            <?php echo $prioridad; ?>
                                        </span>
                                    </td>
                                    <td><small><?php echo date('d/m/Y', strtotime($orden['fecha_creacion'] ?? 'now')); ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No hay órdenes recientes</h6>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ✅ Estilos personalizados -->
<style>
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
.quick-action {
    transition: all 0.3s ease;
    background: #fff;
    cursor: pointer;
}
.quick-action:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
.table th {
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
}
.badge-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.75rem;
    display: inline-flex;
    align-items: center;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ✅ Gráfico de distribución de órdenes
    <?php if (isset($estadisticas_ordenes)): ?>
    const ctxOrdenes = document.getElementById('ordenesChart').getContext('2d');
    new Chart(ctxOrdenes, {
        type: 'doughnut',
        data: {
            labels: ['Pendientes', 'En Proceso', 'Ejecutadas', 'Cerradas', 'Canceladas', 'Aprobadas', 'Rechazadas'],
            datasets: [{
                data: [
                    <?php echo $estadisticas_ordenes['pendientes'] ?? 0; ?>,
                    <?php echo $estadisticas_ordenes['en_proceso'] ?? 0; ?>,
                    <?php echo $estadisticas_ordenes['ejecutadas'] ?? 0; ?>,
                    <?php echo $estadisticas_ordenes['cerradas'] ?? 0; ?>,
                    <?php echo $estadisticas_ordenes['canceladas'] ?? 0; ?>,
                    <?php echo $estadisticas_ordenes['aprobadas'] ?? 0; ?>,
                    <?php echo $estadisticas_ordenes['rechazadas'] ?? 0; ?>
                ],
                backgroundColor: ['#ffc107', '#17a2b8', '#0d6efd', '#28a745', '#dc3545', '#198754', '#6c757d'],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { size: 10 }, padding: 10 }
                }
            }
        }
    });
    <?php endif; ?>

    // ✅ Gráfico de resumen por tipo
    const ctxResumen = document.getElementById('resumenChart').getContext('2d');
    new Chart(ctxResumen, {
        type: 'bar',
        data: {
            labels: ['Técnicos', 'Supervisores', 'Usuarios'],
            datasets: [
                {
                    label: 'Total',
                    data: [
                        <?php echo $estadisticas_tecnicos['total'] ?? 0; ?>,
                        <?php echo $estadisticas_supervisores['total'] ?? 0; ?>,
                        <?php echo $estadisticas_usuarios['total'] ?? 0; ?>
                    ],
                    backgroundColor: 'rgba(13, 110, 253, 0.7)',
                    borderColor: '#0d6efd',
                    borderWidth: 1,
                    borderRadius: 6
                },
                {
                    label: 'Activos',
                    data: [
                        <?php echo $estadisticas_tecnicos['activos'] ?? 0; ?>,
                        <?php echo $estadisticas_supervisores['activos'] ?? 0; ?>,
                        <?php echo $estadisticas_usuarios['activos'] ?? 0; ?>
                    ],
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: '#28a745',
                    borderWidth: 1,
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { font: { size: 11 } }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>