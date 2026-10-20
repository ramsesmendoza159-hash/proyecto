<?php
// views/admin/auditoria/index.php
// Panel de Auditoría - Solo Admin
// ✅ FIX: $seccion = 'auditoria' (antes 'admin')
// ✅ FIX: contenedor .table-actions + btn-icon

if (!isset($seccion)) {
    $seccion = 'auditoria';
}
if (!isset($titulo)) {
    $titulo = 'Auditoría del Sistema';
}

$registros = $registros ?? [];
$estadisticas = $estadisticas ?? [
    'total' => 0, 'usuarios_distintos' => 0, 'acciones_distintas' => 0,
    'tablas_distintas' => 0, 'logins' => 0, 'logouts' => 0,
    'creaciones' => 0, 'actualizaciones' => 0, 'eliminaciones' => 0,
    'cambios_estado' => 0, 'cierres' => 0
];
$acciones = $acciones ?? [];
$filtros = $filtros ?? [];

include_once __DIR__ . '/../../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-list text-primary me-2"></i>Auditoría del Sistema
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Registro de todas las acciones realizadas en el sistema
            </p>
        </div>
        <div>
            <button class="btn btn-outline-danger btn-header-action" data-bs-toggle="modal" data-bs-target="#modalLimpiar">
                <i class="fas fa-trash-alt"></i> Limpiar auditoría
            </button>
        </div>
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

    <!-- Estadísticas -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-modern" style="background: linear-gradient(135deg, #0d6efd, #0dcaf0);">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="ms-3">
                        <div class="stat-label-modern">Total Acciones</div>
                        <div class="stat-number-modern"><?= number_format($estadisticas['total'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-modern" style="background: linear-gradient(135deg, #198754, #20c997);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="ms-3">
                        <div class="stat-label-modern">Usuarios Distintos</div>
                        <div class="stat-number-modern"><?= number_format($estadisticas['usuarios_distintos'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-modern" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="ms-3">
                        <div class="stat-label-modern">Creaciones</div>
                        <div class="stat-number-modern"><?= number_format($estadisticas['creaciones'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-modern" style="background: linear-gradient(135deg, #0dcaf0, #0d6efd);">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="ms-3">
                        <div class="stat-label-modern">Actualizaciones</div>
                        <div class="stat-number-modern"><?= number_format($estadisticas['actualizaciones'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-modern" style="background: linear-gradient(135deg, #dc3545, #ff6b6b);">
                        <i class="fas fa-trash-alt"></i>
                    </div>
                    <div class="ms-3">
                        <div class="stat-label-modern">Eliminaciones</div>
                        <div class="stat-number-modern"><?= number_format($estadisticas['eliminaciones'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center">
                    <div class="stat-icon-modern" style="background: linear-gradient(135deg, #6f42c1, #e83e8c);">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <div class="ms-3">
                        <div class="stat-label-modern">Inicios de Sesión</div>
                        <div class="stat-number-modern"><?= number_format($estadisticas['logins'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card border-0 mb-4">
        <div class="card-body">
            <form id="filtrosForm" method="GET" action="/proyecto/auditoria" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Usuario ID</label>
                    <input type="number" class="form-control form-control-sm" name="usuario_id" 
                           placeholder="ID" value="<?= htmlspecialchars($filtros['usuario_id'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Acción</label>
                    <select class="form-select form-select-sm" name="accion">
                        <option value="">Todas</option>
                        <?php foreach ($acciones as $acc): ?>
                            <option value="<?= htmlspecialchars($acc) ?>" <?= ($filtros['accion'] ?? '') === $acc ? 'selected' : '' ?>>
                                <?= htmlspecialchars($acc) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Tabla</label>
                    <input type="text" class="form-control form-control-sm" name="tabla" 
                           placeholder="Tabla" value="<?= htmlspecialchars($filtros['tabla'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Desde</label>
                    <input type="date" class="form-control form-control-sm" name="fecha_desde" 
                           value="<?= htmlspecialchars($filtros['fecha_desde'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Hasta</label>
                    <input type="date" class="form-control form-control-sm" name="fecha_hasta" 
                           value="<?= htmlspecialchars($filtros['fecha_hasta'] ?? '') ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search me-1"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de auditoría -->
    <div class="card border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Acción</th>
                            <th>Tabla</th>
                            <th>Registro</th>
                            <th>IP</th>
                            <th>Fecha</th>
                            <th class="text-center" style="width:80px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($registros)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                    No hay registros de auditoría
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($registros as $registro): ?>
                                <tr>
                                    <td><?= $registro['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($registro['usuario_nombre']) ?></strong>
                                        <small class="d-block text-muted">ID: <?= $registro['usuario_id'] ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $rol_badge = match($registro['usuario_rol']) {
                                            'admin' => 'danger',
                                            'supervisor' => 'warning',
                                            'almacen' => 'info',
                                            'tecnico' => 'primary',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $rol_badge ?>">
                                            <?= ucfirst($registro['usuario_rol']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $accion_badge = match($registro['accion']) {
                                            'login' => 'success',
                                            'logout' => 'secondary',
                                            'crear' => 'primary',
                                            'actualizar' => 'warning',
                                            'eliminar' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $accion_badge ?>">
                                            <?= ucfirst(str_replace('_', ' ', $registro['accion'])) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($registro['tabla']) ?></td>
                                    <td><?= $registro['registro_id'] ?? 'N/A' ?></td>
                                    <td><small><?= htmlspecialchars($registro['ip']) ?></small></td>
                                    <td><small><?= date('d/m/Y H:i:s', strtotime($registro['fecha_creacion'])) ?></small></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="/proyecto/auditoria/ver/<?= $registro['id'] ?>" 
                                               class="btn btn-sm btn-outline-info btn-icon" 
                                               title="Ver detalle"
                                               aria-label="Ver detalle">
                                                <i class="fas fa-eye"></i>
                                            </a>
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

<!-- Modal Limpiar Auditoría -->
<div class="modal fade" id="modalLimpiar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Limpiar Auditoría</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de limpiar la auditoría?</p>
                <p class="text-warning"><strong>⚠️ Se eliminarán todos los registros con más de 90 días.</strong></p>
                <p>Esta acción no se puede deshacer.</p>
                <form method="POST" action="/proyecto/auditoria/limpiar" id="formLimpiar">
                    <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                    <input type="hidden" name="confirmar" value="si">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger" form="formLimpiar">
                    <i class="fas fa-trash me-1"></i> Confirmar Limpieza
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card-modern {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 20px 24px;
    box-shadow: 0 2px 12px var(--shadow-color);
    transition: all 0.3s ease;
    height: 100%;
}
.stat-card-modern:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px var(--shadow-hover);
}
.stat-card-modern .stat-icon-modern {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: #fff;
    flex-shrink: 0;
}
.stat-card-modern .stat-label-modern {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
    font-weight: 600;
}
.stat-card-modern .stat-number-modern {
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.2;
}
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
}
</style>

<?php include_once __DIR__ . '/../../layouts/footer.php'; ?>