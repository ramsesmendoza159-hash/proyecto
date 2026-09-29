<?php
// views/almacen/index.php
// Dashboard de Almacén - VERSIÓN COMPLETA CON GRÁFICOS

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /proyecto/auth/login');
    exit;
}

$rol = $_SESSION['rol'] ?? '';
if ($rol !== 'almacen' && $rol !== 'admin') {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    header('Location: /proyecto/dashboard');
    exit;
}

$titulo = 'Panel de Almacén';
$seccion = 'almacen';

// Asegurar que las variables existan
$estadisticas = $estadisticas ?? [
    'total' => 0,
    'total_stock' => 0,
    'valor_total' => 0,
    'precio_promedio' => 0,
    'stock_bajo' => 0,
    'agotados' => 0
];
$stock_bajo = $stock_bajo ?? [];
$ultimos_movimientos = $ultimos_movimientos ?? [];
$movimientos_por_mes = $movimientos_por_mes ?? [];
$top_productos = $top_productos ?? [];

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-warehouse text-primary me-2"></i>Panel de Almacén
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-calendar-alt me-1"></i> <?= date('l, d \d\e F \d\e Y') ?>
                <span class="mx-2">|</span>
                <i class="fas fa-clock me-1"></i> <?= date('H:i') ?>
                <span class="mx-2">|</span>
                <i class="fas fa-boxes me-1"></i> <?= $estadisticas['total'] ?? 0 ?> items registrados
            </p>
        </div>
        <div>
            <a href="/proyecto/almacen/entrada_seleccion" class="btn btn-success btn-sm">
                <i class="fas fa-arrow-down me-1"></i> Entrada
            </a>
            <a href="/proyecto/almacen/salida_seleccion" class="btn btn-danger btn-sm">
                <i class="fas fa-arrow-up me-1"></i> Salida
            </a>
            <a href="/proyecto/almacen/movimientos" class="btn btn-info btn-sm">
                <i class="fas fa-history me-1"></i> Movimientos
            </a>
        </div>
    </div>

    <!-- Tarjetas de Estadísticas -->
    <div class="row g-3 mb-4">

        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-modern border-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(102, 126, 234, 0.15); color: #667eea;">
                        <i class="fas fa-boxes fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Total Items</span>
                        <span class="stat-number-modern"><?= $estadisticas['total'] ?? 0 ?></span>
                    </div>
                </div>
                <div class="stat-footer-modern mt-2">
                    <span class="badge bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-circle me-1" style="font-size:6px;"></i> En inventario
                    </span>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-modern border-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(46, 213, 115, 0.15); color: #2ed573;">
                        <i class="fas fa-cubes fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Stock Total</span>
                        <span class="stat-number-modern"><?= $estadisticas['total_stock'] ?? 0 ?></span>
                    </div>
                </div>
                <div class="stat-footer-modern mt-2">
                    <span class="badge bg-success bg-opacity-10 text-success">
                        <i class="fas fa-circle me-1" style="font-size:6px;"></i> Unidades
                    </span>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-modern border-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(255, 193, 7, 0.15); color: #ffc107;">
                        <i class="fas fa-money-bill-wave fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Valor Total</span>
                        <span class="stat-number-modern">S/ <?= number_format($estadisticas['valor_total'] ?? 0, 0) ?></span>
                    </div>
                </div>
                <div class="stat-footer-modern mt-2">
                    <span class="badge bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-circle me-1" style="font-size:6px;"></i> Inventario valorizado
                    </span>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-modern border-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(13, 202, 240, 0.15); color: #0dcaf0;">
                        <i class="fas fa-tag fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Precio Promedio</span>
                        <span class="stat-number-modern">S/ <?= number_format($estadisticas['precio_promedio'] ?? 0, 2) ?></span>
                    </div>
                </div>
                <div class="stat-footer-modern mt-2">
                    <span class="badge bg-info bg-opacity-10 text-info">
                        <i class="fas fa-circle me-1" style="font-size:6px;"></i> Por unidad
                    </span>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-modern border-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(220, 53, 69, 0.15); color: #dc3545;">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Stock Bajo</span>
                        <span class="stat-number-modern"><?= $estadisticas['stock_bajo'] ?? 0 ?></span>
                    </div>
                </div>
                <div class="stat-footer-modern mt-2">
                    <span class="badge bg-danger bg-opacity-10 text-danger">
                        <i class="fas fa-circle me-1" style="font-size:6px;"></i> Necesitan reposición
                    </span>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-modern border-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(108, 117, 125, 0.15); color: #6c757d;">
                        <i class="fas fa-ban fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Agotados</span>
                        <span class="stat-number-modern"><?= $estadisticas['agotados'] ?? 0 ?></span>
                    </div>
                </div>
                <div class="stat-footer-modern mt-2">
                    <span class="badge bg-secondary bg-opacity-10 text-secondary">
                        <i class="fas fa-circle me-1" style="font-size:6px;"></i> Sin stock
                    </span>
                </div>
            </div>
        </div>

    </div>

    <!-- Gráficos -->
    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="card border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-chart-bar text-primary me-2"></i> Movimientos (Últimos 30 días)
                    </h5>
                </div>
                <div class="card-body" style="height: 280px;">
                    <canvas id="movimientosChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 h-100">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-chart-doughnut text-primary me-2"></i> Top Productos
                    </h5>
                </div>
                <div class="card-body" style="height: 280px;">
                    <canvas id="topProductosChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas de Stock Bajo -->
    <?php if (!empty($stock_bajo)): ?>
    <div class="card border-0 mb-4">
        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                Alertas de Stock Bajo
                <span class="badge bg-danger ms-2"><?= count($stock_bajo) ?></span>
            </h5>
            <a href="/proyecto/inventario" class="btn btn-sm btn-primary">
                Ver inventario <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Stock Actual</th>
                            <th>Stock Mínimo</th>
                            <th>Faltante</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stock_bajo as $item): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($item['codigo'] ?? 'N/A') ?></code></td>
                                <td><?= htmlspecialchars($item['nombre'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($item['categoria'] ?? 'General') ?></td>
                                <td class="text-danger fw-bold"><?= $item['cantidad'] ?? 0 ?></td>
                                <td><?= $item['stock_minimo'] ?? 5 ?></td>
                                <td class="text-danger"><?= ($item['stock_minimo'] ?? 5) - ($item['cantidad'] ?? 0) ?></td>
                                <td>
                                    <a href="/proyecto/almacen/entrada/<?= $item['id'] ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-arrow-down me-1"></i> Reponer
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Últimos Movimientos -->
    <div class="card border-0">
        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-history text-info me-2"></i> Últimos Movimientos
            </h5>
            <a href="/proyecto/almacen/movimientos" class="btn btn-sm btn-primary">
                Ver todos <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($ultimos_movimientos)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h6 class="text-muted">No hay movimientos registrados</h6>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Producto</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Usuario</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimos_movimientos as $movimiento): ?>
                                <tr>
                                    <td><?= htmlspecialchars($movimiento['nombre_producto'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if (($movimiento['tipo'] ?? '') === 'entrada'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success">
                                                <i class="fas fa-arrow-down me-1"></i> Entrada
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger">
                                                <i class="fas fa-arrow-up me-1"></i> Salida
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $movimiento['cantidad'] ?? 0 ?></td>
                                    <td><?= htmlspecialchars($movimiento['usuario'] ?? 'Sistema') ?></td>
                                    <td><small><?= date('d/m/Y H:i', strtotime($movimiento['fecha'] ?? 'now')) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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
.stat-card-modern .stat-footer-modern {
    margin-top: 8px !important;
}
.stat-card-modern .stat-footer-modern .badge {
    font-size: 0.65rem !important;
    padding: 4px 12px !important;
    border-radius: 20px !important;
    font-weight: 500 !important;
}
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
</style>

<!-- Scripts con Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    // 1. Gráfico de Movimientos (Últimos 30 días)
    <?php if (!empty($movimientos_por_mes)): ?>
    const ctxMovimientos = document.getElementById('movimientosChart').getContext('2d');
    const meses = <?= json_encode(array_column($movimientos_por_mes, 'mes_nombre')) ?>;
    const entradas = <?= json_encode(array_column($movimientos_por_mes, 'entradas')) ?>;
    const salidas = <?= json_encode(array_column($movimientos_por_mes, 'salidas')) ?>;

    new Chart(ctxMovimientos, {
        type: 'bar',
        data: {
            labels: meses,
            datasets: [
                {
                    label: 'Entradas',
                    data: entradas,
                    backgroundColor: 'rgba(59, 165, 93, 0.7)',
                    borderColor: '#3ba55d',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Salidas',
                    data: salidas,
                    backgroundColor: 'rgba(237, 66, 69, 0.7)',
                    borderColor: '#ed4245',
                    borderWidth: 1,
                    borderRadius: 4
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
    <?php endif; ?>

    // 2. Gráfico de Top Productos
    <?php if (!empty($top_productos)): ?>
    const ctxTop = document.getElementById('topProductosChart').getContext('2d');
    const nombres = <?= json_encode(array_column($top_productos, 'nombre')) ?>;
    const movimientos = <?= json_encode(array_column($top_productos, 'total_movimientos')) ?>;

    const colores = [
        '#5865f2', '#3ba55d', '#faa61a', '#ed4245', '#60a5fa',
        '#a18cd1', '#2ed573', '#ff6b6b', '#fdcb6e', '#487eb0'
    ];

    new Chart(ctxTop, {
        type: 'doughnut',
        data: {
            labels: nombres,
            datasets: [{
                data: movimientos,
                backgroundColor: colores.slice(0, movimientos.length),
                borderColor: '#2b2d31',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { 
                        font: { size: 9 },
                        padding: 8,
                        boxWidth: 12
                    }
                }
            },
            cutout: '65%'
        }
    });
    <?php endif; ?>

});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
