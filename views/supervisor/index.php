<?php
// views/supervision/index.php
// Panel de Supervisión - VERSIÓN COMPLETA
// ✅ FIX: eliminadas referencias [cite: 3]
// ✅ FIX: botones en outline-* según la regla
// ✅ FIX: botón "Eliminar" solo para admin

require_once __DIR__ . '/../../helpers/SecurityHelper.php';

if (!SecurityHelper::verificarSesion()) {
    header('Location: /proyecto/auth/login');
    exit;
}

if (!SecurityHelper::verificarRol(['admin', 'supervisor'])) {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    header('Location: /proyecto/dashboard');
    exit;
}

$supervisiones = $supervisiones ?? [];
$estadisticas = $estadisticas ?? [
    'total' => 0, 'pendientes' => 0, 'aprobadas' => 0, 'rechazadas' => 0
];
$tecnicos = $tecnicos ?? [];
$filtros = $filtros ?? [];

$titulo = "Supervisión de Órdenes";
$seccion = "supervision";
$rol_actual = $_SESSION['rol'] ?? '';

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-check text-primary me-2"></i>Supervisión de Órdenes
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Revisa y supervisa las órdenes de trabajo
            </p>
        </div>
        <div>
            <a href="/proyecto/supervision/reporte" class="btn btn-info text-white">
                <i class="fas fa-chart-bar me-2"></i> Reporte
            </a>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['success']) && !empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Tarjetas de Estadísticas -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card border-0">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label text-muted text-uppercase small fw-semibold">Total</div>
                        <div class="stat-number fw-bold"><?= number_format($estadisticas['total'] ?? 0) ?></div>
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
                        <div class="stat-number fw-bold"><?= number_format($estadisticas['pendientes'] ?? 0) ?></div>
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
                        <div class="stat-label text-muted text-uppercase small fw-semibold">Aprobadas</div>
                        <div class="stat-number fw-bold"><?= number_format($estadisticas['aprobadas'] ?? 0) ?></div>
                    </div>
                    <div class="stat-icon" style="background:rgba(25,135,84,0.1);color:#198754;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card border-0">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-label text-muted text-uppercase small fw-semibold">Rechazadas</div>
                        <div class="stat-number fw-bold"><?= number_format($estadisticas['rechazadas'] ?? 0) ?></div>
                    </div>
                    <div class="stat-icon" style="background:rgba(220,53,69,0.1);color:#dc3545;">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="/proyecto/supervision" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Estado</label>
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="PENDIENTE" <?= isset($_GET['estado']) && $_GET['estado'] === 'PENDIENTE' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="APROBADA" <?= isset($_GET['estado']) && $_GET['estado'] === 'APROBADA' ? 'selected' : '' ?>>Aprobada</option>
                        <option value="RECHAZADA" <?= isset($_GET['estado']) && $_GET['estado'] === 'RECHAZADA' ? 'selected' : '' ?>>Rechazada</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Técnico</label>
                    <select name="tecnico_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php if (!empty($tecnicos)): ?>
                            <?php foreach ($tecnicos as $tecnico): ?>
                                <option value="<?= htmlspecialchars($tecnico['id'] ?? '') ?>"
                                    <?= isset($_GET['tecnico_id']) && $_GET['tecnico_id'] == $tecnico['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tecnico['nombre'] ?? 'Técnico #' . ($tecnico['id'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Buscar</label>
                    <input type="text" name="buscar" class="form-control form-control-sm" placeholder="N° Orden, título..."
                           value="<?= isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control form-control-sm"
                           value="<?= isset($_GET['fecha_desde']) ? htmlspecialchars($_GET['fecha_desde']) : '' ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control form-control-sm"
                           value="<?= isset($_GET['fecha_hasta']) ? htmlspecialchars($_GET['fecha_hasta']) : '' ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search me-1"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Supervisiones -->
    <div class="card border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Orden</th>
                            <th>Supervisor</th>
                            <th>Calificación</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Cumple</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($supervisiones)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                    No hay supervisiones registradas
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($supervisiones as $supervision): ?>
                                <tr>
                                    <td><span class="fw-semibold"><?= (int)($supervision['id'] ?? 0) ?></span></td>
                                    <td>#<?= (int)($supervision['orden_id'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars($supervision['supervisor'] ?? $supervision['supervisor_nombre'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if (!empty($supervision['calificacion'])): ?>
                                            <span class="fw-semibold"><?= (int)$supervision['calificacion'] ?>/5</span>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?= $i <= $supervision['calificacion'] ? '' : '-o' ?> text-warning" style="font-size:0.7rem;"></i>
                                            <?php endfor; ?>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $estadoClases = [
                                            'PENDIENTE' => 'warning',
                                            'APROBADA' => 'success',
                                            'RECHAZADA' => 'danger'
                                        ];
                                        $estadoClase = $estadoClases[$supervision['estado'] ?? 'PENDIENTE'] ?? 'secondary';
                                        ?>
                                        <span class="badge-status bg-<?= $estadoClase ?> bg-opacity-10 text-<?= $estadoClase ?>">
                                            <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                            <?= htmlspecialchars($supervision['estado'] ?? 'PENDIENTE') ?>
                                        </span>
                                    </td>
                                    <td><small><?= isset($supervision['fecha_creacion']) ? date('d/m/Y', strtotime($supervision['fecha_creacion'])) : '-' ?></small></td>
                                    <td><?= ($supervision['cumple'] ?? 0) ? '✅ Sí' : '❌ No' ?></td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-1">
                                            <!-- Ver -->
                                            <a href="/proyecto/supervision/ver/<?= (int)$supervision['id'] ?>" class="btn btn-sm btn-outline-info" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <!-- Editar -->
                                            <a href="/proyecto/supervision/editar/<?= (int)$supervision['id'] ?>" class="btn btn-sm btn-outline-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                            <!-- Eliminar (solo admin) -->
                                            <?php if ($rol_actual === 'admin'): ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Eliminar"
                                                        onclick="confirmarEliminarSupervision(<?= (int)$supervision['id'] ?>, '<?= htmlspecialchars($supervision['num_om'] ?? 'Orden #' . $supervision['orden_id'], ENT_QUOTES, 'UTF-8') ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-3 text-muted small">
                <i class="fas fa-list me-1"></i> Mostrando <?= count($supervisiones) ?> registro(s)
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
.table th {
    font-weight: 600 !important;
    font-size: 0.75rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    color: var(--text-secondary) !important;
}
.table td {
    color: var(--text-primary) !important;
}
</style>

<!-- Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-ocultar alertas
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(function(alert) {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        });
    }, 5000);
});

function confirmarEliminarSupervision(id, referencia) {
    if (confirm('¿Estás seguro de eliminar la supervisión "' + referencia + '"?\n\nEsta acción no se puede deshacer.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/proyecto/supervision/eliminar/' + id;
        form.innerHTML = '<input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>