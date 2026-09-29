<?php
// views/admin/auditoria/index.php
// Panel de Auditoría - Solo Admin
// ✅ FIX: $seccion = 'auditoria' (antes 'admin')
// ✅ FIX: Eliminado include_once duplicado y llave extra

if (!isset($seccion)) {
    // ✅ FIX: Sección correcta para resaltar en el sidebar
    $seccion = 'auditoria';
}
if (!isset($titulo)) {
    $titulo = 'Auditoría del Sistema';
}

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
            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalLimpiar">
                <i class="fas fa-trash-alt me-1"></i> Limpiar auditoría
            </button>
        </div>
    </div>

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
                        <div class="stat-number-modern"><?php echo number_format($estadisticas['total'] ?? 0); ?></div>
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
                        <div class="stat-number-modern"><?php echo number_format($estadisticas['usuarios_distintos'] ?? 0); ?></div>
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
                        <div class="stat-number-modern"><?php echo number_format($estadisticas['creaciones'] ?? 0); ?></div>
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
                        <div class="stat-number-modern"><?php echo number_format($estadisticas['actualizaciones'] ?? 0); ?></div>
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
                        <div class="stat-number-modern"><?php echo number_format($estadisticas['eliminaciones'] ?? 0); ?></div>
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
                        <div class="stat-number-modern"><?php echo number_format($estadisticas['logins'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form id="filtrosForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Usuario ID</label>
                    <input type="number" class="form-control" name="usuario_id" placeholder="ID de usuario" value="<?php echo $filtros['usuario_id'] ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Acción</label>
                    <select class="form-select" name="accion">
                        <option value="">Todas</option>
                        <option value="login" <?php echo ($filtros['accion'] ?? '') === 'login' ? 'selected' : ''; ?>>Login</option>
                        <option value="logout" <?php echo ($filtros['accion'] ?? '') === 'logout' ? 'selected' : ''; ?>>Logout</option>
                        <option value="crear" <?php echo ($filtros['accion'] ?? '') === 'crear' ? 'selected' : ''; ?>>Crear</option>
                        <option value="actualizar" <?php echo ($filtros['accion'] ?? '') === 'actualizar' ? 'selected' : ''; ?>>Actualizar</option>
                        <option value="eliminar" <?php echo ($filtros['accion'] ?? '') === 'eliminar' ? 'selected' : ''; ?>>Eliminar</option>
                        <option value="entrada_stock" <?php echo ($filtros['accion'] ?? '') === 'entrada_stock' ? 'selected' : ''; ?>>Entrada Stock</option>
                        <option value="salida_stock" <?php echo ($filtros['accion'] ?? '') === 'salida_stock' ? 'selected' : ''; ?>>Salida Stock</option>
                        <option value="cambiar_estado" <?php echo ($filtros['accion'] ?? '') === 'cambiar_estado' ? 'selected' : ''; ?>>Cambiar Estado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Tabla</label>
                    <select class="form-select" name="tabla">
                        <option value="">Todas</option>
                        <option value="ordenes_mantenimiento" <?php echo ($filtros['tabla'] ?? '') === 'ordenes_mantenimiento' ? 'selected' : ''; ?>>Órdenes</option>
                        <option value="inventario" <?php echo ($filtros['tabla'] ?? '') === 'inventario' ? 'selected' : ''; ?>>Inventario</option>
                        <option value="usuarios" <?php echo ($filtros['tabla'] ?? '') === 'usuarios' ? 'selected' : ''; ?>>Usuarios</option>
                        <option value="tecnicos" <?php echo ($filtros['tabla'] ?? '') === 'tecnicos' ? 'selected' : ''; ?>>Técnicos</option>
                        <option value="supervisores" <?php echo ($filtros['tabla'] ?? '') === 'supervisores' ? 'selected' : ''; ?>>Supervisores</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Desde</label>
                    <input type="date" class="form-control" name="fecha_desde" value="<?php echo $filtros['fecha_desde'] ?? ''; ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Hasta</label>
                    <input type="date" class="form-control" name="fecha_hasta" value="<?php echo $filtros['fecha_hasta'] ?? ''; ?>">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de auditoría -->
    <div class="card border-0 shadow-sm">
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
                            <th>Acciones</th>
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
                                    <td><?php echo $registro['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($registro['usuario_nombre']); ?></strong>
                                        <small class="d-block text-muted">ID: <?php echo $registro['usuario_id']; ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $registro['usuario_rol'] === 'admin' ? 'danger' : ($registro['usuario_rol'] === 'supervisor' ? 'warning' : ($registro['usuario_rol'] === 'almacen' ? 'info' : 'secondary')); ?>">
                                            <?php echo ucfirst($registro['usuario_rol']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $registro['accion'] === 'login' ? 'success' : ($registro['accion'] === 'logout' ? 'secondary' : ($registro['accion'] === 'crear' ? 'primary' : ($registro['accion'] === 'actualizar' ? 'warning' : 'danger'))); ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $registro['accion'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($registro['tabla']); ?></td>
                                    <td><?php echo $registro['registro_id'] ?? 'N/A'; ?></td>
                                    <td><small><?php echo htmlspecialchars($registro['ip']); ?></small></td>
                                    <td><small><?php echo date('d/m/Y H:i:s', strtotime($registro['fecha_creacion'])); ?></small></td>
                                    <td>
                                        <a href="/proyecto/auditoria/ver/<?php echo $registro['id']; ?>" class="btn btn-sm btn-outline-primary" title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
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
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de limpiar la auditoría?</p>
                <p class="text-warning"><strong>⚠️ Se eliminarán todos los registros con más de 90 días.</strong></p>
                <p>Esta acción no se puede deshacer.</p>
                <form method="POST" action="/proyecto/auditoria/limpiar" id="formLimpiar">
                    <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::generateCSRFToken(); ?>">
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
    background: #fff;
    border-radius: 16px;
    padding: 20px 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    transition: all 0.3s ease;
    height: 100%;
}
.stat-card-modern:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.1);
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
    color: #6c757d;
    font-weight: 600;
}
.stat-card-modern .stat-number-modern {
    font-size: 1.6rem;
    font-weight: 700;
    color: #1a1a2e;
    line-height: 1.2;
}
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
</style>

<?php include_once __DIR__ . '/../../layouts/footer.php'; ?>