<?php
// views/ordenes/estadisticas.php
// Estadísticas de Órdenes - CON MODO OSCURO COMPLETO
// ✅ FIX 1: Variables $prioridades, $evolucion_mensual, $rendimiento_tecnicos con fallback
// ✅ FIX 2: ctxEvolucion declarado UNA sola vez para evitar "Cannot redeclare"
// ✅ FIX 3: Gráficos con MutationObserver para detectar cambios de tema

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /proyecto/auth/login');
    exit();
}

$titulo = "Estadísticas de Órdenes";
$seccion = "ordenes";

// ✅ FIX: Inicializar variables con valores por defecto
$estadisticas = $estadisticas ?? [
    'total' => 0, 'pendientes' => 0, 'en_proceso' => 0, 'ejecutadas' => 0,
    'cerradas' => 0, 'canceladas' => 0, 'aprobadas' => 0, 'rechazadas' => 0
];
$prioridades = $prioridades ?? ['urgente' => 0, 'alta' => 0, 'media' => 0, 'baja' => 0];
$evolucion_mensual = $evolucion_mensual ?? [];
$rendimiento_tecnicos = $rendimiento_tecnicos ?? [];
$fechaInicio = $fechaInicio ?? date('Y-m-01');
$fechaFin = $fechaFin ?? date('Y-m-t');

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-chart-bar text-primary me-2"></i>Estadísticas de Órdenes
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Análisis detallado de las órdenes de trabajo
            </p>
        </div>
        <a href="/proyecto/ordenes" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <!-- Filtros -->
    <div class="card border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="/proyecto/ordenes/estadisticas" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="fecha_inicio" class="form-label fw-semibold small">Fecha Inicio</label>
                    <input type="date" class="form-control form-control-sm" id="fecha_inicio" name="fecha_inicio" 
                           value="<?php echo htmlspecialchars($fechaInicio); ?>">
                </div>
                <div class="col-md-3">
                    <label for="fecha_fin" class="form-label fw-semibold small">Fecha Fin</label>
                    <input type="date" class="form-control form-control-sm" id="fecha_fin" name="fecha_fin" 
                           value="<?php echo htmlspecialchars($fechaFin); ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search me-1"></i> Actualizar
                    </button>
                </div>
                <div class="col-md-3">
                    <a href="/proyecto/ordenes/estadisticas" class="btn btn-secondary btn-sm w-100">
                        <i class="fas fa-undo me-1"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background: rgba(102, 126, 234, 0.15); color: #667eea;">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div>
                        <div class="stat-label">Total Órdenes</div>
                        <div class="stat-number-mini"><?php echo number_format($estadisticas['total'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background: rgba(255, 193, 7, 0.15); color: #ffc107;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <div class="stat-label">Pendientes</div>
                        <div class="stat-number-mini"><?php echo number_format($estadisticas['pendientes'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background: rgba(13, 202, 240, 0.15); color: #0dcaf0;">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div>
                        <div class="stat-label">En Proceso</div>
                        <div class="stat-number-mini"><?php echo number_format($estadisticas['en_proceso'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background: rgba(25, 135, 84, 0.15); color: #198754;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-label">Cerradas</div>
                        <div class="stat-number-mini"><?php echo number_format($estadisticas['cerradas'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row g-4">
        <div class="col-xl-6">
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-chart-doughnut text-primary me-2"></i> Distribución por Estado
                    </h5>
                </div>
                <div class="card-body" style="height:300px;">
                    <canvas id="estadoChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-chart-bar text-primary me-2"></i> Órdenes por Prioridad
                    </h5>
                </div>
                <div class="card-body" style="height:300px;">
                    <canvas id="prioridadChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Evolución Mensual -->
    <div class="card border-0 mt-4">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-chart-line text-primary me-2"></i> Evolución Mensual
            </h5>
        </div>
        <div class="card-body" style="height:300px;">
            <canvas id="evolucionChart"></canvas>
        </div>
    </div>

    <!-- Tabla de rendimiento por técnico -->
    <div class="card border-0 mt-4">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-users text-primary me-2"></i> Rendimiento por Técnico
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Técnico</th>
                            <th class="text-center">Órdenes</th>
                            <th class="text-center">Completadas</th>
                            <th class="text-center">Pendientes</th>
                            <th class="text-center">Eficiencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rendimiento_tecnicos)): ?>
                            <?php foreach ($rendimiento_tecnicos as $tecnico): ?>
                                <tr>
                                    <td><span class="fw-semibold"><?php echo htmlspecialchars($tecnico['nombre'] ?? 'N/A'); ?></span></td>
                                    <td class="text-center"><?php echo $tecnico['total'] ?? 0; ?></td>
                                    <td class="text-center"><?php echo $tecnico['completadas'] ?? 0; ?></td>
                                    <td class="text-center"><?php echo ($tecnico['total'] ?? 0) - ($tecnico['completadas'] ?? 0); ?></td>
                                    <td>
                                        <?php 
                                        $eficiencia = ($tecnico['total'] ?? 0) > 0 
                                            ? round((($tecnico['completadas'] ?? 0) / ($tecnico['total'] ?? 0)) * 100, 1) 
                                            : 0;
                                        ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height:8px;">
                                                <div class="progress-bar bg-<?php echo $eficiencia >= 80 ? 'success' : ($eficiencia >= 50 ? 'warning' : 'danger'); ?>" 
                                                     role="progressbar" 
                                                     style="width: <?php echo $eficiencia; ?>%;"></div>
                                            </div>
                                            <span class="fw-semibold small"><?php echo $eficiencia; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                    No hay datos disponibles
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<style>
/* ========================================== */
/* TARJETAS DE ESTADÍSTICAS - ADAPTABLES */
/* ========================================== */
.stat-card-mini {
    background: var(--bg-card, #ffffff);
    border: 1px solid var(--border-color, #e9ecef);
    border-radius: 16px;
    padding: 20px 24px;
    box-shadow: 0 2px 8px var(--shadow-color, rgba(0,0,0,0.04));
    transition: all 0.3s ease;
    height: 100%;
}
.stat-card-mini:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px var(--shadow-hover, rgba(0,0,0,0.08));
}
.stat-card-mini .stat-icon-mini {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}
.stat-card-mini .stat-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-secondary, #6c757d);
    font-weight: 600;
    margin-bottom: 4px;
}
.stat-card-mini .stat-number-mini {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--text-primary, #1a1a2e);
    line-height: 1.2;
}

/* ========================================== */
/* CARDS */
/* ========================================== */
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color, rgba(0,0,0,0.04));
}

/* ========================================== */
/* PROGRESS */
/* ========================================== */
.progress {
    border-radius: 20px;
    background-color: var(--bg-input-disabled, #e9ecef);
}

/* ========================================== */
/* MODO OSCURO - TARJETAS DE ESTADÍSTICAS */
/* ========================================== */
[data-theme="dark"] .stat-card-mini {
    background: linear-gradient(135deg, #131320, #1a1a2e) !important;
    border: 1px solid #1f1f35 !important;
}
[data-theme="dark"] .stat-card-mini:hover {
    border-color: rgba(59, 130, 246, 0.4) !important;
    box-shadow: 0 10px 40px rgba(59, 130, 246, 0.15) !important;
}
[data-theme="dark"] .stat-card-mini .stat-label {
    color: #8a8aa8 !important;
}
[data-theme="dark"] .stat-card-mini .stat-number-mini {
    color: #ffffff !important;
}
[data-theme="dark"] .stat-card-mini .stat-icon-mini {
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.15) !important;
}

/* ========================================== */
/* MODO OSCURO - CARDS */
/* ========================================== */
[data-theme="dark"] .card {
    background: #131320 !important;
    border: 1px solid #1f1f35 !important;
}
[data-theme="dark"] .card-header {
    background: #1a1a2e !important;
    border-bottom: 1px solid #1f1f35 !important;
}

/* ========================================== */
/* MODO OSCURO - PROGRESS */
/* ========================================== */
[data-theme="dark"] .progress {
    background: #1a1a2e !important;
}

/* ========================================== */
/* MODO OSCURO - TABLE */
/* ========================================== */
[data-theme="dark"] .table {
    color: #ffffff !important;
}
[data-theme="dark"] .table thead th {
    background: #1a1a2e !important;
    color: #93c5fd !important;
    border-bottom: 1px solid #2a2a45 !important;
}
[data-theme="dark"] .table tbody td {
    background: #131320 !important;
    color: #ffffff !important;
    border-bottom: 1px solid #1f1f35 !important;
}
[data-theme="dark"] .table tbody tr:hover td {
    background: #1c1c30 !important;
}
[data-theme="dark"] .table tbody tr:nth-of-type(even) td {
    background: #0f0f1a !important;
}
[data-theme="dark"] .table tbody tr:nth-of-type(even):hover td {
    background: #1c1c30 !important;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// ==========================================
// ✅ Variables globales para los gráficos
// ==========================================
let chartEstado = null;
let chartPrioridad = null;
let chartEvolucion = null;

// ==========================================
// ✅ Función para obtener colores según el tema
// ==========================================
function getThemeColors() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    return {
        isDark: isDark,
        text: isDark ? '#ffffff' : '#1a1a2e',
        grid: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
        tooltipBg: isDark ? '#1a1a2e' : '#ffffff',
        tooltipBorder: isDark ? '#2a2a45' : '#e9ecef',
        borderColor: isDark ? '#131320' : '#ffffff'
    };
}

// ==========================================
// ✅ Función principal que crea los gráficos
// ==========================================
function crearGraficos() {
    if (typeof Chart === 'undefined') {
        console.error('Chart.js no está cargado aún');
        return;
    }

    const colors = getThemeColors();

    // ✅ Configurar defaults ANTES de crear los gráficos
    Chart.defaults.color = colors.text;
    Chart.defaults.borderColor = colors.grid;
    Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
    Chart.defaults.font.size = 12;

    // Destruir gráficos existentes antes de recrearlos
    if (chartEstado) { chartEstado.destroy(); chartEstado = null; }
    if (chartPrioridad) { chartPrioridad.destroy(); chartPrioridad = null; }
    if (chartEvolucion) { chartEvolucion.destroy(); chartEvolucion = null; }

    // ==========================================
    // 1. GRÁFICO DE DISTRIBUCIÓN POR ESTADO
    // ==========================================
    const canvasEstado = document.getElementById('estadoChart');
    if (canvasEstado) {
        chartEstado = new Chart(canvasEstado.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Pendientes', 'En Proceso', 'Cerradas', 'Canceladas', 'Aprobadas', 'Rechazadas'],
                datasets: [{
                    data: [
                        <?php echo (int)($estadisticas['pendientes'] ?? 0); ?>,
                        <?php echo (int)($estadisticas['en_proceso'] ?? 0); ?>,
                        <?php echo (int)($estadisticas['cerradas'] ?? 0); ?>,
                        <?php echo (int)($estadisticas['canceladas'] ?? 0); ?>,
                        <?php echo (int)($estadisticas['aprobadas'] ?? 0); ?>,
                        <?php echo (int)($estadisticas['rechazadas'] ?? 0); ?>
                    ],
                    backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545', '#0d6efd', '#6c757d'],
                    borderColor: colors.borderColor,
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
                            color: colors.text,
                            font: { size: 11, weight: '500' },
                            padding: 15,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            boxWidth: 8,
                            boxHeight: 8
                        }
                    },
                    tooltip: {
                        backgroundColor: colors.tooltipBg,
                        titleColor: colors.text,
                        bodyColor: colors.text,
                        borderColor: colors.tooltipBorder,
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: { size: 13, weight: 'bold' },
                        bodyFont: { size: 12 }
                    }
                }
            }
        });
    }

    // ==========================================
    // 2. GRÁFICO DE PRIORIDAD
    // ==========================================
    const canvasPrioridad = document.getElementById('prioridadChart');
    if (canvasPrioridad) {
        chartPrioridad = new Chart(canvasPrioridad.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Alta', 'Media', 'Baja'],
                datasets: [{
                    label: 'Órdenes por Prioridad',
                    data: [
                        <?php echo (int)($prioridades['alta'] ?? 0); ?>,
                        <?php echo (int)($prioridades['media'] ?? 0); ?>,
                        <?php echo (int)($prioridades['baja'] ?? 0); ?>
                    ],
                    backgroundColor: ['rgba(220,53,69,0.85)', 'rgba(255,193,7,0.85)', 'rgba(40,167,69,0.85)'],
                    borderColor: ['#dc3545', '#ffc107', '#28a745'],
                    borderWidth: 1,
                    borderRadius: 6,
                    maxBarThickness: 60
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: colors.text,
                            font: { size: 11 }
                        },
                        grid: {
                            color: colors.grid,
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: {
                            color: colors.text,
                            font: { size: 11, weight: '500' }
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: colors.tooltipBg,
                        titleColor: colors.text,
                        bodyColor: colors.text,
                        borderColor: colors.tooltipBorder,
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8
                    }
                }
            }
        });
    }

    // ==========================================
    // 3. GRÁFICO DE EVOLUCIÓN MENSUAL
    // ✅ ctxEvolucion declarado UNA sola vez
    // ==========================================
    const canvasEvolucion = document.getElementById('evolucionChart');
    if (canvasEvolucion) {
        const ctxEvolucion = canvasEvolucion.getContext('2d');

        <?php if (!empty($evolucion_mensual)): ?>
        chartEvolucion = new Chart(ctxEvolucion, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($evolucion_mensual, 'mes')); ?>,
                datasets: [{
                    label: 'Órdenes por Mes',
                    data: <?php echo json_encode(array_column($evolucion_mensual, 'total')); ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#0d6efd',
                    pointBorderColor: colors.borderColor,
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            color: colors.text,
                            font: { size: 11 }
                        },
                        grid: {
                            color: colors.grid,
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: {
                            color: colors.text,
                            font: { size: 11 }
                        },
                        grid: {
                            color: colors.grid
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: colors.text,
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        backgroundColor: colors.tooltipBg,
                        titleColor: colors.text,
                        bodyColor: colors.text,
                        borderColor: colors.tooltipBorder,
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8
                    }
                }
            }
        });
        <?php else: ?>
        // Sin datos - dibujar mensaje
        ctxEvolucion.fillStyle = colors.text;
        ctxEvolucion.font = '16px Inter, sans-serif';
        ctxEvolucion.textAlign = 'center';
        ctxEvolucion.textBaseline = 'middle';
        ctxEvolucion.fillText(
            'Sin datos de evolución mensual',
            ctxEvolucion.canvas.width / 2,
            ctxEvolucion.canvas.height / 2
        );
        <?php endif; ?>
    }
}

// ==========================================
// ✅ INICIALIZACIÓN
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    // Delay para asegurar que el tema ya se aplicó desde footer.php
    setTimeout(function() {
        crearGraficos();
    }, 200);
});

// ==========================================
// ✅ OBSERVER para detectar cambios en data-theme
// ==========================================
const themeObserver = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.attributeName === 'data-theme') {
            setTimeout(crearGraficos, 100);
        }
    });
});

themeObserver.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['data-theme']
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>