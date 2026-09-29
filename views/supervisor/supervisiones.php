<?php
// views/supervisor/supervisiones.php
// Mis Supervisiones - VERSIÓN COMPLETA
// ✅ FIX: Botones cambiados a btn-outline-* (estilo admin)
// ✅ FIX: credentials: 'same-origin' en el fetch de supervisionesList

if (!isset($seccion)) {
    $seccion = 'supervisor';
}
if (!isset($titulo)) {
    $titulo = 'Mis Supervisiones';
}

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-check text-primary me-2"></i>Mis Supervisiones
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Historial de supervisiones realizadas
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
                    <label for="estado" class="form-label fw-semibold small">Estado</label>
                    <select class="form-select form-select-sm" id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="APROBADA">✅ Aprobada</option>
                        <option value="RECHAZADA">❌ Rechazada</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="calificacion" class="form-label fw-semibold small">Calificación</label>
                    <select class="form-select form-select-sm" id="calificacion" name="calificacion">
                        <option value="">Todas</option>
                        <option value="1">⭐ 1 - Muy malo</option>
                        <option value="2">⭐⭐ 2 - Malo</option>
                        <option value="3">⭐⭐⭐ 3 - Regular</option>
                        <option value="4">⭐⭐⭐⭐ 4 - Bueno</option>
                        <option value="5">⭐⭐⭐⭐⭐ 5 - Excelente</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="fecha" class="form-label fw-semibold small">Fecha</label>
                    <input type="date" class="form-control form-control-sm" id="fecha" name="fecha">
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-search me-1"></i> Filtrar
                        </button>
                        <button type="reset" class="btn btn-secondary btn-sm">
                            <i class="fas fa-undo me-1"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de supervisiones -->
    <div class="card border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Orden</th>
                            <th>Técnico</th>
                            <th>Calificación</th>
                            <th>Estado</th>
                            <th>Cumple</th>
                            <th>Fecha</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="supervisionesBody">
                        <!-- Cargado por AJAX -->
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div id="paginacion"></div>
                <div class="text-muted small">
                    <i class="fas fa-list me-1"></i> <span id="totalRegistros">Mostrando 0 registros</span>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Estilos -->
<style>
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
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

<script>
let paginaActual = 1;
const porPagina = 10;

function cargarSupervisiones(page = 1) {
    paginaActual = page;
    const formData = new FormData(document.getElementById('filtrosForm'));
    const params = new URLSearchParams(formData);
    params.append('page', page);
    params.append('limit', porPagina);

    // ✅ FIX: credentials: 'same-origin'
    fetch(`/proyecto/supervisor/supervisionesList?${params.toString()}`, {
        credentials: 'same-origin'
    })
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('supervisionesBody');
            tbody.innerHTML = '';

            if (data.supervisiones && data.supervisiones.length > 0) {
                data.supervisiones.forEach(supervision => {
                    const estadoColor = supervision.estado === 'APROBADA' ? 'success' : 'danger';
                    const calificacionClass = supervision.calificacion >= 4 ? 'text-success' :
                                              supervision.calificacion >= 3 ? 'text-warning' :
                                              'text-danger';

                    const tr = document.createElement('tr');
                    // ✅ FIX: Botones cambiados a btn-outline-* (estilo admin)
                    tr.innerHTML = `
                        <td><span class="fw-semibold">${supervision.id}</span></td>
                        <td>#${supervision.orden_id}</td>
                        <td>${supervision.tecnico || 'N/A'}</td>
                        <td class="${calificacionClass} fw-bold">
                            ${supervision.calificacion ? supervision.calificacion + '/5' : 'N/A'}
                        </td>
                        <td>
                            <span class="badge-status bg-${estadoColor} bg-opacity-10 text-${estadoColor}">
                                <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                ${supervision.estado || 'PENDIENTE'}
                            </span>
                        </td>
                        <td>${supervision.cumple ? '✅ Sí' : '❌ No'}</td>
                        <td><small>${supervision.fecha_creacion}</small></td>
                        <td>
                            <div class="d-flex justify-content-center">
                                <a href="/proyecto/supervisor/ver_supervision/${supervision.id}" class="btn btn-sm btn-outline-info" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

                actualizarPaginacion(data.total, data.paginas);
                document.getElementById('totalRegistros').textContent = 
                    `Mostrando ${data.supervisiones.length} de ${data.total} registros`;
            } else {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                    No hay supervisiones registradas
                </td></tr>`;
                document.getElementById('totalRegistros').textContent = 'Mostrando 0 registros';
            }
        })
        .catch(error => console.error('Error:', error));
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
        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.innerHTML = '&laquo;';
        a.onclick = (e) => {
            e.preventDefault();
            cargarSupervisiones(paginaActual - 1);
        };
        li.appendChild(a);
        ul.appendChild(li);
    }
    
    for (let i = 1; i <= paginas; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === paginaActual ? 'active' : ''}`;
        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.textContent = i;
        a.onclick = (e) => {
            e.preventDefault();
            cargarSupervisiones(i);
        };
        li.appendChild(a);
        ul.appendChild(li);
    }
    
    if (paginaActual < paginas) {
        const li = document.createElement('li');
        li.className = 'page-item';
        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.innerHTML = '&raquo;';
        a.onclick = (e) => {
            e.preventDefault();
            cargarSupervisiones(paginaActual + 1);
        };
        li.appendChild(a);
        ul.appendChild(li);
    }
    
    div.appendChild(ul);
}

document.getElementById('filtrosForm').addEventListener('submit', function(e) {
    e.preventDefault();
    cargarSupervisiones(1);
});

document.getElementById('filtrosForm').addEventListener('reset', function(e) {
    e.preventDefault();
    this.querySelectorAll('select, input').forEach(el => el.value = '');
    cargarSupervisiones(1);
});

document.addEventListener('DOMContentLoaded', function() {
    cargarSupervisiones(1);
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>