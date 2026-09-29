<?php
// views/tecnico/mis_ordenes.php
// Mis Órdenes - VERSIÓN CORREGIDA

// ✅ Verificar que las variables existan
if (!isset($seccion)) {
    $seccion = 'mis_ordenes';
}
if (!isset($titulo)) {
    $titulo = 'Mis Órdenes de Trabajo';
}
if (!isset($ordenes)) {
    $ordenes = [];
}

include_once __DIR__ . '/../layouts/header.php';
// ❌ NO incluir sidebar aquí (ya está en header)
?>

<div class="container-fluid px-0">

    <!-- ✅ Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-list text-primary me-2"></i>Mis Órdenes de Trabajo
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Gestiona tus órdenes de trabajo asignadas
            </p>
        </div>
        <a href="/proyecto/tecnico" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver al Panel
        </a>
    </div>

    <!-- ✅ Filtros -->
    <div class="card border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="/proyecto/tecnico/mis_ordenes" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Estado</label>
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="PENDIENTE" <?php echo (isset($_GET['estado']) && $_GET['estado'] === 'PENDIENTE') ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="EN_PROCESO" <?php echo (isset($_GET['estado']) && $_GET['estado'] === 'EN_PROCESO') ? 'selected' : ''; ?>>En Progreso</option>
                        <option value="EJECUTADA" <?php echo (isset($_GET['estado']) && $_GET['estado'] === 'EJECUTADA') ? 'selected' : ''; ?>>Ejecutada</option>
                        <option value="CERRADA" <?php echo (isset($_GET['estado']) && $_GET['estado'] === 'CERRADA') ? 'selected' : ''; ?>>Completada</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Prioridad</label>
                    <select name="prioridad" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <option value="Baja">Baja</option>
                        <option value="Media">Media</option>
                        <option value="Alta">Alta</option>
                        <option value="Urgente">Urgente</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Fecha</label>
                    <input type="date" name="fecha" class="form-control form-control-sm" value="<?php echo isset($_GET['fecha']) ? htmlspecialchars($_GET['fecha']) : ''; ?>">
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-search me-1"></i> Filtrar
                        </button>
                        <a href="/proyecto/tecnico/mis_ordenes" class="btn btn-secondary btn-sm">
                            <i class="fas fa-undo me-1"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ✅ Tabla de órdenes -->
    <div class="card border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Área</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ordenes)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                    No hay órdenes asignadas
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ordenes as $orden): ?>
                                <tr>
                                    <td><span class="fw-semibold">#<?php echo $orden['id']; ?></span></td>
                                    <td><?php echo htmlspecialchars($orden['titulo'] ?? 'Sin título'); ?></td>
                                    <td><?php echo htmlspecialchars($orden['nombre_area'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php
                                        $prioridadColor = match($orden['prioridad'] ?? 'Media') {
                                            'Baja' => 'success',
                                            'Media' => 'info',
                                            'Alta' => 'warning',
                                            'Urgente' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $prioridadColor; ?> bg-opacity-10 text-<?php echo $prioridadColor; ?>">
                                            <?php echo $orden['prioridad'] ?? 'Media'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $estadoColor = match($orden['status'] ?? 'PENDIENTE') {
                                            'PENDIENTE' => 'warning',
                                            'EN_PROCESO' => 'info',
                                            'EJECUTADA' => 'primary',
                                            'CERRADA' => 'success',
                                            'APROBADA' => 'success',
                                            'CANCELADA' => 'danger',
                                            'RECHAZADA' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge-status bg-<?php echo $estadoColor; ?> bg-opacity-10 text-<?php echo $estadoColor; ?>">
                                            <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                            <?php echo $orden['status'] ?? 'PENDIENTE'; ?>
                                        </span>
                                    </td>
                                    <td><small><?php echo date('d/m/Y', strtotime($orden['fecha_creacion'] ?? 'now')); ?></small></td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="/proyecto/tecnico/detalle_orden/<?php echo $orden['id']; ?>" class="btn btn-sm btn-info" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (!in_array($orden['status'] ?? '', ['CERRADA', 'APROBADA', 'CANCELADA'])): ?>
                                                <a href="/proyecto/tecnico/cerrar_orden/<?php echo $orden['id']; ?>" class="btn btn-sm btn-success" title="Cerrar">
                                                    <i class="fas fa-check-circle"></i>
                                                </a>
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
                <i class="fas fa-list me-1"></i> Mostrando <?php echo count($ordenes); ?> órden(es)
            </div>
        </div>
    </div>

</div>

<!-- ✅ Estilos -->
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

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>