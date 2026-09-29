<?php
// views/inventario/index.php
// Inventario - LISTADO COMPLETO CON FILTROS AVANZADOS
// ✅ FIX: agregado <meta name="csrf-token"> para que eliminarItem() funcione
// ✅ FIX: agregado credentials: 'same-origin' en todos los fetch

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /proyecto/auth/login');
    exit;
}

$rol = $_SESSION['rol'] ?? '';
if (!in_array($rol, ['admin', 'almacen', 'supervisor', 'consultor'])) {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    header('Location: /proyecto/dashboard');
    exit;
}

$titulo = "Inventario General";
$seccion = "inventario";

$items = $items ?? [];
$estadisticas = $estadisticas ?? [
    'total' => 0,
    'total_stock' => 0,
    'precio_promedio' => 0,
    'valor_total' => 0,
    'agotados' => 0,
    'stock_bajo' => 0
];
$categorias = $categorias ?? [];
$proveedores = $proveedores ?? [];

include_once __DIR__ . '/../layouts/header.php';
?>

<!-- ✅ FIX CRÍTICO: meta CSRF para que eliminarItem() funcione -->
<meta name="csrf-token" content="<?= SecurityHelper::generateCSRFToken() ?>">

<div class="container-fluid px-0">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-boxes text-primary me-2"></i>Inventario General
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-calendar-alt me-1"></i> <?= date('d/m/Y H:i') ?>
                <span class="mx-2">|</span>
                <i class="fas fa-list me-1"></i> <?= $estadisticas['total'] ?? 0 ?> items registrados
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/proyecto/almacen" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <?php if (in_array($rol, ['admin', 'almacen'])): ?>
                <a href="/proyecto/inventario/crear" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus-circle me-2"></i>Agregar
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- TARJETAS DE ESTADÍSTICAS -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-modern border-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(102, 126, 234, 0.15); color: #667eea;">
                        <i class="fas fa-boxes fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Total Items</span>
                        <span class="stat-number-modern"><?= number_format($estadisticas['total'] ?? 0) ?></span>
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
                        <span class="stat-number-modern"><?= number_format($estadisticas['total_stock'] ?? 0) ?></span>
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
                        <i class="fas fa-circle me-1" style="font-size:6px;"></i> Valorizado
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
                        <span class="stat-number-modern"><?= number_format($estadisticas['stock_bajo'] ?? 0) ?></span>
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
                        <span class="stat-number-modern"><?= number_format($estadisticas['agotados'] ?? 0) ?></span>
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

    <!-- FILTROS AVANZADOS -->
    <div class="card border-0 mb-4">
        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-sliders-h text-primary me-2"></i> Filtros Avanzados
                <span class="badge bg-info ms-2" id="filtrosCount">0</span>
            </h5>
            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filtrosCollapse">
                <i class="fas fa-chevron-down me-1"></i> <span id="toggleFiltrosText">Ocultar</span>
            </button>
        </div>
        <div class="collapse show" id="filtrosCollapse">
            <div class="card-body">
                <form id="filtrosForm" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small">
                            <i class="fas fa-search me-1"></i> Buscar
                        </label>
                        <input type="text" class="form-control form-control-sm" id="search" name="search"
                               placeholder="Nombre, código o descripción..." value="">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">
                            <i class="fas fa-tag me-1"></i> Categoría
                        </label>
                        <select class="form-select form-select-sm" id="categoria" name="categoria">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">
                            <i class="fas fa-box me-1"></i> Stock
                        </label>
                        <select class="form-select form-select-sm" id="stock" name="stock">
                            <option value="">Todos</option>
                            <option value="bajo">Bajo (≤5)</option>
                            <option value="medio">Medio (6-20)</option>
                            <option value="alto">Alto (>20)</option>
                            <option value="agotado">Agotado (0)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">
                            <i class="fas fa-circle me-1"></i> Estado
                        </label>
                        <select class="form-select form-select-sm" id="estado" name="estado">
                            <option value="">Todos</option>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small">
                            <i class="fas fa-arrow-up me-1"></i> Ordenar por
                        </label>
                        <select class="form-select form-select-sm" id="orden" name="orden">
                            <option value="nombre">Nombre</option>
                            <option value="precio">Precio</option>
                            <option value="stock">Stock</option>
                            <option value="fecha_creacion">Fecha</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">
                            <i class="fas fa-dollar-sign me-1"></i> Precio desde
                        </label>
                        <input type="number" step="0.01" class="form-control form-control-sm" id="precio_desde"
                               name="precio_desde" placeholder="0.00">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">
                            <i class="fas fa-dollar-sign me-1"></i> Precio hasta
                        </label>
                        <input type="number" step="0.01" class="form-control form-control-sm" id="precio_hasta"
                               name="precio_hasta" placeholder="9999.99">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">
                            <i class="fas fa-map-pin me-1"></i> Ubicación
                        </label>
                        <input type="text" class="form-control form-control-sm" id="ubicacion" name="ubicacion"
                               placeholder="Ej: Estante A-1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small">
                            <i class="fas fa-building me-1"></i> Proveedor
                        </label>
                        <select class="form-select form-select-sm" id="proveedor" name="proveedor">
                            <option value="">Todos</option>
                            <?php foreach ($proveedores as $prov): ?>
                                <option value="<?= htmlspecialchars($prov) ?>"><?= htmlspecialchars($prov) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <hr>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-search me-1"></i> Aplicar Filtros
                            </button>
                            <button type="reset" class="btn btn-secondary btn-sm" id="btnLimpiar">
                                <i class="fas fa-undo me-1"></i> Limpiar Filtros
                            </button>
                            <span class="text-muted small d-flex align-items-center ms-2">
                                <i class="fas fa-info-circle me-1"></i>
                                <span id="totalRegistrosInfo">Cargando...</span>
                            </span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- TABLA DE INVENTARIO -->
    <div class="card border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablaInventario">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px;">#</th>
                            <th>Nombre</th>
                            <th>Código</th>
                            <th>Categoría</th>
                            <th style="width:80px;">Stock</th>
                            <th style="width:100px;">Precio</th>
                            <th>Ubicación</th>
                            <th style="width:100px;">Estado</th>
                            <th style="width:120px;" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="inventarioBody">
                        <!-- Cargado por AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
            <div id="paginacion"></div>
            <div>
                <span class="text-muted small" id="totalRegistros">Cargando...</span>
            </div>
        </div>
    </div>

</div>

<!-- ESTILOS -->
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
.form-control-sm, .form-select-sm {
    font-size: 0.85rem;
    padding: 6px 12px;
}
</style>

<!-- SCRIPTS -->
<script>
let paginaActual = 1;
const porPagina = 15;

function contarFiltros() {
    const form = document.getElementById('filtrosForm');
    const inputs = form.querySelectorAll('input, select');
    let count = 0;
    inputs.forEach(input => {
        if (input.value && input.value !== '' && input.value !== 'todos' && input.value !== 'todas') {
            count++;
        }
    });
    const filtrosCount = document.getElementById('filtrosCount');
    if (filtrosCount) filtrosCount.textContent = count;
}

document.addEventListener('DOMContentLoaded', function() {
    const collapseEl = document.getElementById('filtrosCollapse');
    const toggleText = document.getElementById('toggleFiltrosText');

    if (collapseEl) {
        collapseEl.addEventListener('show.bs.collapse', function() {
            if (toggleText) toggleText.textContent = 'Ocultar';
        });
        collapseEl.addEventListener('hide.bs.collapse', function() {
            if (toggleText) toggleText.textContent = 'Mostrar';
        });
    }
});

function cargarInventario(page = 1) {
    paginaActual = page;
    const form = document.getElementById('filtrosForm');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    params.append('page', page);
    params.append('limit', porPagina);

    document.getElementById('inventarioBody').innerHTML = `
        <tr>
            <td colspan="9" class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 text-muted small">Cargando inventario...</p>
            </td>
        </tr>
    `;

    fetch(`/proyecto/inventario/list?${params.toString()}`, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('La respuesta no es JSON. Probablemente la sesión expiró. Recarga la página.');
            }
            return response.json();
        })
        .then(data => {
            const tbody = document.getElementById('inventarioBody');
            tbody.innerHTML = '';

            if (data.items && data.items.length > 0) {
                data.items.forEach((item, index) => {
                    const tr = document.createElement('tr');
                    const stockClass = item.cantidad <= 5 ? 'text-danger' :
                                      item.cantidad <= 20 ? 'text-warning' : 'text-success';

                    tr.innerHTML = `
                        <td><span class="fw-semibold">${((page - 1) * porPagina) + index + 1}</span></td>
                        <td><strong>${item.nombre || 'N/A'}</strong></td>
                        <td><code>${item.codigo || 'N/A'}</code></td>
                        <td>
                            <span class="badge bg-info bg-opacity-10 text-info">
                                ${item.categoria || 'General'}
                            </span>
                        </td>
                        <td class="fw-bold ${stockClass}">${item.cantidad}</td>
                        <td>S/ ${parseFloat(item.precio_unitario || 0).toFixed(2)}</td>
                        <td>${item.ubicacion || 'N/A'}</td>
                        <td>
                            <span class="badge-status ${item.activo ? 'bg-success bg-opacity-10 text-success' : 'bg-danger bg-opacity-10 text-danger'}">
                                <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                ${item.activo ? 'Activo' : 'Inactivo'}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex justify-content-center gap-1">
                                <a href="/proyecto/inventario/editar/${item.id}" class="btn btn-sm btn-outline-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarItem(${item.id})" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                actualizarPaginacion(data.total, data.paginas);
                document.getElementById('totalRegistros').textContent =
                    `Mostrando ${data.items.length} de ${data.total} registros`;
                document.getElementById('totalRegistrosInfo').textContent =
                    `${data.total} registros encontrados`;
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <i class="fas fa-boxes fa-3x text-muted mb-3"></i>
                            <h6>No hay elementos que coincidan con los filtros</h6>
                            <p class="text-muted small">Ajusta los filtros o agrega un nuevo ítem</p>
                            <a href="/proyecto/inventario/crear" class="btn btn-primary btn-sm mt-2">
                                <i class="fas fa-plus-circle me-1"></i> Agregar Ítem
                            </a>
                        </td>
                    </tr>
                `;
                document.getElementById('totalRegistros').textContent = 'Mostrando 0 registros';
                document.getElementById('totalRegistrosInfo').textContent = '0 registros encontrados';
            }
            contarFiltros();
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('inventarioBody').innerHTML = `
                <tr>
                    <td colspan="9" class="text-center py-5 text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p><strong>Error:</strong> ${error.message}</p>
                        <p class="small text-muted">Si tu sesión expiró, <a href="/proyecto/login">inicia sesión nuevamente</a>.</p>
                        <button class="btn btn-primary btn-sm mt-2" onclick="cargarInventario(1)">
                            <i class="fas fa-sync me-1"></i> Reintentar
                        </button>
                    </td>
                </tr>
            `;
            document.getElementById('totalRegistros').textContent = 'Error al cargar';
            document.getElementById('totalRegistrosInfo').textContent = 'Error al cargar';
        });
}

function actualizarPaginacion(total, paginas) {
    const div = document.getElementById('paginacion');
    div.innerHTML = '';

    if (paginas <= 1) return;

    const ul = document.createElement('ul');
    ul.className = 'pagination pagination-sm mb-0';

    const liPrev = document.createElement('li');
    liPrev.className = `page-item ${paginaActual <= 1 ? 'disabled' : ''}`;
    const aPrev = document.createElement('a');
    aPrev.className = 'page-link';
    aPrev.href = '#';
    aPrev.textContent = '‹';
    aPrev.onclick = (e) => {
        e.preventDefault();
        if (paginaActual > 1) cargarInventario(paginaActual - 1);
    };
    liPrev.appendChild(aPrev);
    ul.appendChild(liPrev);

    let startPage = Math.max(1, paginaActual - 2);
    let endPage = Math.min(paginas, paginaActual + 2);

    for (let i = startPage; i <= endPage; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === paginaActual ? 'active' : ''}`;
        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.textContent = i;
        a.onclick = (e) => {
            e.preventDefault();
            cargarInventario(i);
        };
        li.appendChild(a);
        ul.appendChild(li);
    }

    const liNext = document.createElement('li');
    liNext.className = `page-item ${paginaActual >= paginas ? 'disabled' : ''}`;
    const aNext = document.createElement('a');
    aNext.className = 'page-link';
    aNext.href = '#';
    aNext.textContent = '›';
    aNext.onclick = (e) => {
        e.preventDefault();
        if (paginaActual < paginas) cargarInventario(paginaActual + 1);
    };
    liNext.appendChild(aNext);
    ul.appendChild(liNext);

    div.appendChild(ul);
}

function eliminarItem(id) {
    if (!confirm('¿Estás seguro de eliminar este elemento del inventario?\n\nEsta acción no se puede deshacer.')) {
        return;
    }

    // ✅ FIX: ahora sí encuentra el meta porque lo agregamos al inicio del body
    const token = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!token) {
        alert('❌ Error de seguridad: Token CSRF no encontrado. Recarga la página.');
        return;
    }

    const buttons = document.querySelectorAll(`button[onclick*="eliminarItem(${id})"]`);
    buttons.forEach(btn => {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    });

    fetch(`/proyecto/inventario/eliminar/${id}`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `csrf_token=${encodeURIComponent(token)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            cargarInventario(paginaActual);
        } else {
            alert('❌ ' + (data.message || 'Error desconocido'));
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-trash"></i>';
            });
        }
    })
    .catch(error => {
        alert('❌ Error de conexión: ' + error.message);
        buttons.forEach(btn => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash"></i>';
        });
    });
}

document.getElementById('filtrosForm').addEventListener('submit', function(e) {
    e.preventDefault();
    cargarInventario(1);
});

document.getElementById('btnLimpiar').addEventListener('click', function(e) {
    e.preventDefault();
    const form = this.closest('form');
    form.querySelectorAll('input, select').forEach(el => {
        el.value = '';
        if (el.tagName === 'SELECT') {
            el.selectedIndex = 0;
        }
    });
    cargarInventario(1);
});

document.addEventListener('DOMContentLoaded', function() {
    cargarInventario(1);
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>