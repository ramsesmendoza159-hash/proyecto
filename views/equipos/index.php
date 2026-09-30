<?php
// views/equipos/index.php
// Gestión de Equipos - Listado - VERSIÓN ESTANDARIZADA
// ✅ FIX: botón "Ver" cambiado a btn-outline-info
// ✅ FIX: contenedor .table-actions + btn-icon + btn-header-action

if (!isset($seccion)) $seccion = 'equipos';
if (!isset($titulo)) $titulo = 'Gestión de Equipos';

$equipos = $equipos ?? [];
$estadisticas = $estadisticas ?? [
    'total_equipos' => 0, 'total_areas' => 0, 'activos' => 0, 'inactivos' => 0,
    'operativos' => 0, 'en_mantenimiento' => 0, 'averiados' => 0, 'fuera_servicio' => 0
];
$areas = $areas ?? [];
$plantas = $plantas ?? [];
$rol = $_SESSION['rol'] ?? 'usuario';

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-industry text-primary me-2"></i>Gestión de Equipos
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Administra las máquinas y equipos del sistema
            </p>
        </div>
        <?php if (in_array($rol, ['admin', 'supervisor'])): ?>
            <a href="/proyecto/equipos/crear" class="btn btn-primary btn-header-action">
                <i class="fas fa-plus-circle"></i> Nuevo Equipo
            </a>
        <?php endif; ?>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?= $_SESSION['mensaje_tipo'] ?? 'success' ?> alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_SESSION['mensaje']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Tarjetas de Estadísticas -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(102, 126, 234, 0.15); color: #667eea;">
                        <i class="fas fa-industry fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Total Equipos</span>
                        <span class="stat-number-modern"><?= number_format($estadisticas['total_equipos'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(46, 213, 115, 0.15); color: #2ed573;">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Operativos</span>
                        <span class="stat-number-modern"><?= number_format($estadisticas['operativos'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(255, 193, 7, 0.15); color: #ffc107;">
                        <i class="fas fa-tools fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">En Mantenimiento</span>
                        <span class="stat-number-modern"><?= number_format($estadisticas['en_mantenimiento'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(220, 53, 69, 0.15); color: #dc3545;">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Averiados</span>
                        <span class="stat-number-modern"><?= number_format($estadisticas['averiados'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Buscar</label>
                    <input type="text" name="buscar" class="form-control form-control-sm"
                           placeholder="Nombre, código, serie..."
                           value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Estado Operativo</label>
                    <select name="estado_operativo" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="Operativo" <?= ($_GET['estado_operativo'] ?? '') === 'Operativo' ? 'selected' : '' ?>>Operativo</option>
                        <option value="En Mantenimiento" <?= ($_GET['estado_operativo'] ?? '') === 'En Mantenimiento' ? 'selected' : '' ?>>En Mantenimiento</option>
                        <option value="Averiado" <?= ($_GET['estado_operativo'] ?? '') === 'Averiado' ? 'selected' : '' ?>>Averiado</option>
                        <option value="Fuera de Servicio" <?= ($_GET['estado_operativo'] ?? '') === 'Fuera de Servicio' ? 'selected' : '' ?>>Fuera de Servicio</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Estado</label>
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="activo" <?= ($_GET['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="inactivo" <?= ($_GET['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Área</label>
                    <select name="id_area" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <?php foreach ($areas as $area): ?>
                            <option value="<?= (int)$area['id_area'] ?>" <?= ($_GET['id_area'] ?? '') == $area['id_area'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($area['nombre_area']) ?>
                            </option>
                        <?php endforeach; ?>
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

    <!-- Tabla -->
    <div class="card border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Equipo</th>
                            <th>Marca/Modelo</th>
                            <th>Planta / Área</th>
                            <th>Estado</th>
                            <th class="text-center" style="width:180px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($equipos)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fas fa-industry fa-2x d-block mb-2"></i>
                                    No hay equipos registrados
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($equipos as $equipo): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($equipo['codigo'] ?? 'N/A') ?></code></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div style="width:40px;height:40px;border-radius:10px;background:rgba(13,110,253,0.1);display:flex;align-items:center;justify-content:center;color:#0d6efd;">
                                                <i class="fas fa-industry"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($equipo['nombre_equipo'] ?? '') ?></div>
                                                <small class="text-muted">Serie: <?= htmlspecialchars($equipo['serie'] ?? 'N/A') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($equipo['marca'] ?? 'N/A') ?>
                                        <?php if (!empty($equipo['modelo'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($equipo['modelo']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($equipo['nombre_planta'] ?? 'N/A') ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($equipo['nombre_area'] ?? 'N/A') ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $estadoOp = $equipo['estado_operativo'] ?? 'Operativo';
                                        $color = 'success';
                                        if ($estadoOp === 'En Mantenimiento') $color = 'warning';
                                        elseif ($estadoOp === 'Averiado') $color = 'danger';
                                        elseif ($estadoOp === 'Fuera de Servicio') $color = 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?> px-3 py-2">
                                            <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                            <?= htmlspecialchars($estadoOp) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="/proyecto/equipos/ver/<?= (int)$equipo['id_equipo'] ?>" 
                                               class="btn btn-sm btn-outline-info btn-icon" 
                                               title="Ver"
                                               aria-label="Ver equipo">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <?php if (in_array($rol, ['admin', 'supervisor'])): ?>
                                                <a href="/proyecto/equipos/editar/<?= (int)$equipo['id_equipo'] ?>" 
                                                   class="btn btn-sm btn-outline-warning btn-icon" 
                                                   title="Editar"
                                                   aria-label="Editar equipo">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <?php if ($rol === 'admin'): ?>
                                                    <button class="btn btn-sm btn-outline-danger btn-icon" 
                                                            title="Eliminar"
                                                            aria-label="Eliminar equipo"
                                                            onclick="confirmarEliminar(<?= (int)$equipo['id_equipo'] ?>, '<?= htmlspecialchars($equipo['nombre_equipo'] ?? '', ENT_QUOTES, 'UTF-8') ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-transparent">
                <span class="text-muted small">
                    <i class="fas fa-list me-1"></i> Mostrando <?= count($equipos) ?> equipo(s)
                </span>
            </div>
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
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
</style>

<script>
function confirmarEliminar(id, nombre) {
    if (confirm('¿Estás seguro de eliminar el equipo "' + nombre + '"?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/proyecto/equipos/eliminar/' + id;
        form.innerHTML = '<input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>