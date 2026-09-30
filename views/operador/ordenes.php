<?php
// views/operador/ordenes.php
// Mis Órdenes - Operador - VERSIÓN ESTANDARIZADA
// ✅ FIX: contenedor de acciones usa .table-actions + btn-icon
// ✅ FIX: botones de header con btn-header-action
// ✅ FIX NUEVO: botón "Estadísticas" agregado (acceso directo al dashboard de admin)

if (!isset($seccion)) {
    $seccion = 'operador';
}
if (!isset($titulo)) {
    $titulo = 'Mis Órdenes';
}
if (!isset($ordenes)) {
    $ordenes = [];
}

$rol = $_SESSION['rol'] ?? 'usuario';

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Mensajes -->
    <?php if (isset($_SESSION['mensaje']) && !empty($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?php echo $_SESSION['mensaje_tipo'] ?? 'success'; ?> alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['mensaje']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-list text-primary me-2"></i>Gestión de Órdenes
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Órdenes de trabajo del sistema
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/proyecto/ordenes/crear" class="btn btn-primary btn-header-action">
                <i class="fas fa-plus-circle"></i> Nueva Orden
            </a>
            <a href="/proyecto/ordenes/estadisticas" class="btn btn-outline-info btn-header-action">
                <i class="fas fa-chart-bar"></i> Estadísticas
            </a>
            <a href="/proyecto/operador" class="btn btn-secondary btn-header-action">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="card border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Área</th>
                            <th>Técnico</th>
                            <th>Estado</th>
                            <th>Prioridad</th>
                            <th>Fecha</th>
                            <th class="text-center" style="width:180px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ordenes)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                    No hay órdenes registradas
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ordenes as $orden): ?>
                                <tr>
                                    <td><span class="fw-semibold">#<?php echo $orden['id']; ?></span></td>
                                    <td><?php echo htmlspecialchars($orden['titulo'] ?? 'Sin título'); ?></td>
                                    <td><?php echo htmlspecialchars($orden['nombre_area'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($orden['tecnico_nombre'] ?? 'Sin asignar'); ?></td>
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
                                    <td><small><?php echo date('d/m/Y', strtotime($orden['fecha_creacion'] ?? 'now')); ?></small></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="/proyecto/ordenes/ver/<?php echo $orden['id']; ?>" 
                                               class="btn btn-sm btn-outline-info btn-icon" 
                                               title="Ver"
                                               aria-label="Ver orden">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <?php if (($orden['status'] ?? '') === 'PENDIENTE'): ?>
                                                <a href="/proyecto/ordenes/editar/<?php echo $orden['id']; ?>" 
                                                   class="btn btn-sm btn-outline-warning btn-icon" 
                                                   title="Editar"
                                                   aria-label="Editar orden">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (in_array($orden['status'] ?? '', ['EN_PROCESO', 'EJECUTADA'])): ?>
                                                <a href="/proyecto/ordenes/cerrar/<?php echo $orden['id']; ?>" 
                                                   class="btn btn-sm btn-outline-success btn-icon" 
                                                   title="Cerrar"
                                                   aria-label="Cerrar orden">
                                                    <i class="fas fa-check-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (($orden['status'] ?? '') === 'PENDIENTE'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger btn-icon" 
                                                        onclick="confirmarEliminar(<?php echo $orden['id']; ?>, '<?php echo htmlspecialchars($orden['num_om'] ?? '', ENT_QUOTES, 'UTF-8'); ?>')" 
                                                        title="Eliminar"
                                                        aria-label="Eliminar orden">
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
                <i class="fas fa-list me-1"></i> Mostrando <?php echo count($ordenes); ?> orden(es)
            </div>
        </div>
    </div>

</div>

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
function confirmarEliminar(id, numOm) {
    if (confirm(`¿Estás seguro de eliminar la orden "${numOm}"?\n\nEsta acción no se puede deshacer.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/proyecto/ordenes/eliminar/${id}`;
        form.innerHTML = `<input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">`;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>