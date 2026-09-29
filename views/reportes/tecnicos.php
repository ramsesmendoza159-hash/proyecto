<?php
// views/reportes/tecnicos.php
// Reporte de Técnicos - VERSIÓN CORREGIDA
// ✅ FIX: permisos alineados con ReporteController (admin, supervisor, consultor)

require_once __DIR__ . '/../../helpers/SecurityHelper.php';

if (!SecurityHelper::verificarSesion()) {
    header('Location: /proyecto/auth/login');
    exit();
}

if (!SecurityHelper::verificarRol(['admin', 'supervisor', 'consultor'])) {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    header('Location: /proyecto/dashboard');
    exit();
}

$titulo = "Reporte de Técnicos";
$seccion = "reportes";
include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-users-cog text-primary me-2"></i>Reporte de Técnicos
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-calendar-alt me-1"></i> <?= date('d/m/Y H:i') ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-success btn-sm" onclick="exportarExcel()">
                <i class="fas fa-file-excel me-1"></i> Exportar Excel
            </button>
            <button class="btn btn-danger btn-sm" onclick="exportarPDF()">
                <i class="fas fa-file-pdf me-1"></i> Exportar PDF
            </button>
            <a href="/proyecto/reportes" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card border-0 mb-4">
        <div class="card-body">
            <form id="filtrosForm" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="fecha_inicio" class="form-label fw-semibold small">Fecha Inicio</label>
                    <input type="date" class="form-control form-control-sm" id="fecha_inicio" name="fecha_inicio" 
                           value="<?php echo htmlspecialchars($_GET['fecha_inicio'] ?? date('Y-m-01')); ?>">
                </div>
                <div class="col-md-3">
                    <label for="fecha_fin" class="form-label fw-semibold small">Fecha Fin</label>
                    <input type="date" class="form-control form-control-sm" id="fecha_fin" name="fecha_fin" 
                           value="<?php echo htmlspecialchars($_GET['fecha_fin'] ?? date('Y-m-t')); ?>">
                </div>
                <div class="col-md-3">
                    <label for="tecnico_id" class="form-label fw-semibold small">Técnico</label>
                    <select class="form-select form-select-sm" id="tecnico_id" name="tecnico_id">
                        <option value="">Todos</option>
                        <?php foreach ($tecnicos ?? [] as $tecnico): ?>
                            <option value="<?php echo $tecnico['id']; ?>" 
                                    <?php echo ($_GET['tecnico_id'] ?? '') == $tecnico['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tecnico['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm w-50">
                        <i class="fas fa-search me-1"></i> Filtrar
                    </button>
                    <a href="/proyecto/reportes/tecnicos" class="btn btn-secondary btn-sm w-50">
                        <i class="fas fa-undo me-1"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="reporteTabla">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Técnico</th>
                            <th>Especialidad</th>
                            <th class="text-center">Órdenes</th>
                            <th class="text-center">Completadas</th>
                            <th class="text-center">Pendientes</th>
                            <th class="text-center">Tasa Éxito</th>
                            <th class="text-center">Eficiencia</th>
                            <th class="text-end">Costo Total</th>
                        </tr>
                    </thead>
                    <tbody id="reporteBody">
                        <!-- Cargado por AJAX -->
                    </tbody>
                </table>
            </div>
            <div id="paginacion" class="mt-3"></div>
            <div id="totalRegistros" class="text-muted small mt-2">
                <i class="fas fa-list me-1"></i> Mostrando 0 registros
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row g-4 mt-4">
        <div class="col-md-6">
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-chart-bar text-primary me-2"></i> Órdenes por Técnico
                    </h5>
                </div>
                <div class="card-body" style="height:280px;">
                    <canvas id="tecnicosChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-chart-doughnut text-primary me-2"></i> Eficiencia de Técnicos
                    </h5>
                </div>
                <div class="card-body" style="height:280px;">
                    <canvas id="eficienciaChart"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Estilos -->
<style>
.stat-card-mini {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 16px 20px;
    transition: all 0.3s ease;
}
.stat-card-mini:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px var(--shadow-hover);
}
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let tecnicosChart, eficienciaChart;
let paginaActual = 1;
const porPagina = 10;

function cargarReporte(page = 1) {
    paginaActual = page;
    const formData = new FormData(document.getElementById('filtrosForm'));
    const params = new URLSearchParams(formData);
    params.append('page', page);
    params.append('limit', porPagina);

    fetch(`/proyecto/reportes/tecnicosData?${params.toString()}`, {
        credentials: 'same-origin'
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error HTTP: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            const tbody = document.getElementById('reporteBody');
            tbody.innerHTML = '';

            if (data.tecnicos && data.tecnicos.length > 0) {
                data.tecnicos.forEach((tecnico, index) => {
                    const tasaExito = tecnico.total > 0 ? 
                        Math.round((tecnico.completadas / tecnico.total) * 100) : 0;
                    const eficiencia = tecnico.promedio_horas ? 
                        Math.round((1 / tecnico.promedio_horas) * 100) : 0;

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${((page - 1) * porPagina) + index + 1}</td>
                        <td><strong>${tecnico.nombre || 'N/A'}</strong></td>
                        <td>${tecnico.especialidad || 'General'}</td>
                        <td class="text-center">${tecnico.total || 0}</td>
                        <td class="text-center text-success">${tecnico.completadas || 0}</td>
                        <td class="text-center text-warning">${(tecnico.total || 0) - (tecnico.completadas || 0)}</td>
                        <td class="text-center">
                            <div class="progress" style="height:20px;">
                                <div class="progress-bar bg-${tasaExito >= 80 ? 'success' : tasaExito >= 50 ? 'warning' : 'danger'}" 
                                     style="width: ${tasaExito}%">
                                    ${tasaExito}%
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="progress" style="height:20px;">
                                <div class="progress-bar bg-${eficiencia >= 70 ? 'success' : eficiencia >= 40 ? 'warning' : 'danger'}" 
                                     style="width: ${Math.min(eficiencia, 100)}%">
                                    ${eficiencia}%
                                </div>
                            </div>
                        </td>
                        <td class="text-end">S/ ${parseFloat(tecnico.costo_total || 0).toFixed(2)}</td>
                    `;
                    tbody.appendChild(tr);
                });

                document.getElementById('totalRegistros').textContent = 
                    `Mostrando ${data.tecnicos.length} de ${data.total || data.tecnicos.length} registros`;

                actualizarGraficos(data.tecnicos);
            } else {
                tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                    No hay datos disponibles
                </td></tr>`;
                document.getElementById('totalRegistros').textContent = 'Mostrando 0 registros';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('reporteBody').innerHTML = `
                <tr><td colspan="9" class="text-center py-4 text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x d-block mb-2"></i>
                    Error al cargar los datos: ${error.message}
                </td></tr>`;
            document.getElementById('totalRegistros').textContent = 'Error al cargar';
        });
}

function actualizarGraficos(tecnicos) {
    const labels = tecnicos.map(t => t.nombre || 'N/A');
    const totales = tecnicos.map(t => t.total || 0);
    const completadas = tecnicos.map(t => t.completadas || 0);
    const eficiencias = tecnicos.map(t => 
        t.total > 0 ? Math.round((t.completadas / t.total) * 100) : 0
    );

    const ctxTecnicos = document.getElementById('tecnicosChart').getContext('2d');
    const ctxEficiencia = document.getElementById('eficienciaChart').getContext('2d');

    if (tecnicosChart) tecnicosChart.destroy();
    tecnicosChart = new Chart(ctxTecnicos, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Total',
                    data: totales,
                    backgroundColor: 'rgba(13, 110, 253, 0.7)',
                    borderColor: '#0d6efd',
                    borderWidth: 1
                },
                {
                    label: 'Completadas',
                    data: completadas,
                    backgroundColor: 'rgba(25, 135, 84, 0.7)',
                    borderColor: '#198754',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });

    if (eficienciaChart) eficienciaChart.destroy();
    eficienciaChart = new Chart(ctxEficiencia, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: eficiencias,
                backgroundColor: ['#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#dc3545', '#fd7e14', '#ffc107', '#198754', '#0dcaf0', '#6c757d'],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.label}: ${context.raw}% de éxito`;
                        }
                    }
                }
            }
        }
    });
}

function exportarExcel() {
    const params = new URLSearchParams(new FormData(document.getElementById('filtrosForm')));
    window.location.href = `/proyecto/reportes/exportar?tipo=tecnicos&formato=excel&${params.toString()}`;
}

function exportarPDF() {
    const params = new URLSearchParams(new FormData(document.getElementById('filtrosForm')));
    window.location.href = `/proyecto/reportes/imprimir?tipo=tecnicos&${params.toString()}`;
}

document.getElementById('filtrosForm').addEventListener('submit', function(e) {
    e.preventDefault();
    cargarReporte(1);
});

document.addEventListener('DOMContentLoaded', function() {
    cargarReporte(1);
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>