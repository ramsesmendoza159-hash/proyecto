<?php
// views/supervisor/ordenes.php
// Órdenes para Revisar - Vista del Supervisor
// ✅ FIX: Eliminado CSS personalizado que rompía el tema global (modo oscuro)
// ✅ FIX: Botones y tablas usan las clases estándar del sistema

if (!isset($seccion)) { $seccion = 'supervisor'; }
if (!isset($titulo)) { $titulo = 'Órdenes para Revisar'; }

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-list text-primary me-2"></i> Órdenes para Revisar
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Administra y evalúa las órdenes de trabajo del sistema
            </p>
        </div>
        <a href="/proyecto/supervisor" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver al Panel
        </a>
    </div>

    <!-- Filtros -->
    <div class="card border-0 mb-4">
        <div class="card-body">
            <form id="filtrosForm" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Buscar</label>
                    <input type="text" class="form-control form-control-sm" id="buscar" name="buscar" placeholder="Título, ID...">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Estado</label>
                    <select class="form-select form-select-sm" id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="PENDIENTE">Pendiente</option>
                        <option value="EN_PROCESO">En Progreso</option>
                        <option value="CERRADA">Completada</option>
                        <option value="CANCELADA">Cancelada</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Prioridad</label>
                    <select class="form-select form-select-sm" id="prioridad" name="prioridad">
                        <option value="">Todas</option>
                        <option value="Baja">Baja</option>
                        <option value="Media">Media</option>
                        <option value="Alta">Alta</option>
                        <option value="Urgente">Urgente</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Técnico</label>
                    <select class="form-select form-select-sm" id="tecnico" name="tecnico">
                        <option value="">Todos</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search me-1"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de órdenes -->
    <div class="card border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>ORDEN / TÍTULO</th>
                            <th>TÉCNICO</th>
                            <th>PRIORIDAD</th>
                            <th>FECHA</th>
                            <th>ESTADO</th>
                            <th class="text-center pe-4">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody id="ordenesBody">
                        <!-- Cargado por AJAX -->
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center p-3 border-top">
                <div id="paginacion"></div>
                <div class="text-muted small">
                    <i class="fas fa-list me-1"></i> <span id="totalRegistros">Mostrando 0 registros</span>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
let paginaActual = 1;
const porPagina = 10;

function escapeHTML(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function cargarTecnicos() {
    fetch('/proyecto/supervisor/tecnicosList', { credentials: 'same-origin' })
        .then(res => res.ok ? res.json() : Promise.reject(res))
        .then(data => {
            const select = document.getElementById('tecnico');
            data.forEach(tecnico => {
                const option = document.createElement('option');
                option.value = tecnico.id;
                option.textContent = tecnico.nombre;
                select.appendChild(option);
            });
        })
        .catch(error => console.error('Error al obtener la lista de técnicos:', error));
}

function cargarOrdenes(page = 1) {
    paginaActual = page;
    const formData = new FormData(document.getElementById('filtrosForm'));
    const params = new URLSearchParams(formData);
    params.append('page', page);
    params.append('limit', porPagina);

    fetch(`/proyecto/supervisor/ordenesList?${params.toString()}`, { credentials: 'same-origin' })
        .then(res => res.ok ? res.json() : Promise.reject(res))
        .then(data => {
            const tbody = document.getElementById('ordenesBody');
            tbody.innerHTML = '';

            if (data.ordenes && data.ordenes.length > 0) {
                data.ordenes.forEach(orden => {
                    const prioridadColor = {
                        'Baja': 'success',
                        'Media': 'info',
                        'Alta': 'warning',
                        'Urgente': 'danger'
                    }[orden.prioridad] || 'secondary';

                    const estadoColor = {
                        'PENDIENTE': 'warning',
                        'EN_PROCESO': 'info',
                        'EJECUTADA': 'info',
                        'CERRADA': 'success',
                        'APROBADA': 'success',
                        'CANCELADA': 'danger',
                        'RECHAZADA': 'danger'
                    }[orden.estado] || 'secondary';

                    const tr = document.createElement('tr');

                    tr.innerHTML = `
                        <td class="ps-4"><span class="fw-bold">#${escapeHTML(orden.id)}</span></td>
                        <td>
                            <div class="fw-semibold">${escapeHTML(orden.titulo || 'Sin título')}</div>
                            <small class="text-muted">Orden de trabajo</small>
                        </td>
                        <td><span class="text-muted">${escapeHTML(orden.tecnico || 'Sin asignar')}</span></td>
                        <td>
                            <span class="badge bg-${prioridadColor} bg-opacity-10 text-${prioridadColor}">
                                ${escapeHTML(orden.prioridad || 'Media')}
                            </span>
                        </td>
                        <td><small class="text-muted">${escapeHTML(orden.fecha_creacion)}</small></td>
                        <td>
                            <span class="badge-status bg-${estadoColor} bg-opacity-10 text-${estadoColor}">
                                <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                ${escapeHTML(orden.estado || 'PENDIENTE')}
                            </span>
                        </td>
                        <td class="text-center pe-4">
                            <div class="d-flex justify-content-center gap-1">
                                <a href="/proyecto/supervisor/ver_orden/${encodeURIComponent(orden.id)}" class="btn btn-sm btn-outline-info" title="Ver Detalle">
                                    <i class="fas fa-eye"></i>
                                </a>
                                ${orden.estado === 'CERRADA' && orden.supervision_estado !== 'APROBADA' ? `
                                    <a href="/proyecto/supervisor/revisar/${encodeURIComponent(orden.id)}" class="btn btn-sm btn-outline-warning" title="Revisar Orden">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                ` : ''}
                            </div>
                        </td>
                    `;

                    tbody.appendChild(tr);
                });

                actualizarPaginacion(data.total, data.paginas);
                document.getElementById('totalRegistros').textContent = 
                    `Mostrando ${data.ordenes.length} de ${data.total} registros`;
            } else {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                    No se encontraron órdenes para revisar
                </td></tr>`;
                document.getElementById('totalRegistros').textContent = 'Mostrando 0 registros';
            }
        })
        .catch(error => console.error('Error al cargar órdenes:', error));
}

function actualizarPaginacion(total, paginas) {
    const div = document.getElementById('paginacion');
    div.innerHTML = '';
    
    if (paginas <= 1) return;

    const ul = document.createElement('ul');
    ul.className = 'pagination pagination-sm mb-0';
    
    if (paginaActual > 1) {
        const li = document.createElement('li');
        li.className = 'page-item';
        li.innerHTML = `<a class="page-link" href="#"><i class="fas fa-chevron-left"></i></a>`;
        li.querySelector('a').onclick = (e) => {
            e.preventDefault();
            cargarOrdenes(paginaActual - 1);
        };
        ul.appendChild(li);
    }
    
    for (let i = 1; i <= paginas; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === paginaActual ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
        li.querySelector('a').onclick = (e) => {
            e.preventDefault();
            cargarOrdenes(i);
        };
        ul.appendChild(li);
    }
    
    if (paginaActual < paginas) {
        const li = document.createElement('li');
        li.className = 'page-item';
        li.innerHTML = `<a class="page-link" href="#"><i class="fas fa-chevron-right"></i></a>`;
        li.querySelector('a').onclick = (e) => {
            e.preventDefault();
            cargarOrdenes(paginaActual + 1);
        };
        ul.appendChild(li);
    }
    
    div.appendChild(ul);
}

document.getElementById('filtrosForm').addEventListener('submit', function(e) {
    e.preventDefault();
    cargarOrdenes(1);
});

document.addEventListener('DOMContentLoaded', function() {
    cargarTecnicos();
    cargarOrdenes(1);
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>