<?php
// views/operador/ordenes.php
// Mis Órdenes - Operador - VERSIÓN CORREGIDA

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
$puede_eliminar = ($rol === 'admin');
$puede_editar   = in_array($rol, ['admin', 'operador'], true);

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Mensajes -->
    <?php if (isset($_SESSION['mensaje']) && !empty($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?= $_SESSION['mensaje_tipo'] ?? 'success' ?> alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_SESSION['mensaje']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Header -->
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

    <!-- Tabla -->
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
                            <?php foreach ($ordenes as $orden):
                                $estado = strtoupper($orden['status'] ?? 'PENDIENTE');
                                $estadoColor = match($estado) {
                                    'PENDIENTE'  => 'warning',
                                    'EN_PROCESO' => 'info',
                                    'EJECUTADA'  => 'primary',
                                    'CERRADA', 'APROBADA' => 'success',
                                    'CANCELADA', 'RECHAZADA' => 'danger',
                                    default      => 'secondary'
                                };
                                $prioridad = $orden['prioridad'] ?? 'Media';
                                $prioridadColor = match($prioridad) {
                                    'Baja'    => 'success',
                                    'Media'   => 'info',
                                    'Alta'    => 'warning',
                                    'Urgente' => 'danger',
                                    default   => 'secondary'
                                };
                            ?>
                                <tr>
                                    <td><span class="fw-semibold">#<?= (int)$orden['id'] ?></span></td>
                                    <td><?= htmlspecialchars($orden['titulo'] ?? 'Sin título') ?></td>
                                    <td><?= htmlspecialchars($orden['nombre_area'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($orden['tecnico_nombre'] ?? 'Sin asignar') ?></td>
                                    <td>
                                        <span class="badge-status bg-<?= $estadoColor ?> bg-opacity-10 text-<?= $estadoColor ?>">
                                            <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                            <?= htmlspecialchars($estado) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $prioridadColor ?> bg-opacity-10 text-<?= $prioridadColor ?>">
                                            <?= htmlspecialchars($prioridad) ?>
                                        </span>
                                    </td>
                                    <td><small><?= date('d/m/Y', strtotime($orden['fecha_creacion'] ?? 'now')) ?></small></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="/proyecto/ordenes/ver/<?= (int)$orden['id'] ?>"
                                               class="btn btn-sm btn-outline-info btn-icon"
                                               title="Ver"
                                               aria-label="Ver orden">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <?php if ($puede_editar && $estado === 'PENDIENTE'): ?>
                                                <a href="/proyecto/ordenes/editar/<?= (int)$orden['id'] ?>"
                                                   class="btn btn-sm btn-outline-warning btn-icon"
                                                   title="Editar"
                                                   aria-label="Editar orden">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>

                                            <?php if (in_array($estado, ['EN_PROCESO', 'EJECUTADA'], true)): ?>
                                                <a href="/proyecto/ordenes/cerrar/<?= (int)$orden['id'] ?>"
                                                   class="btn btn-sm btn-outline-success btn-icon"
                                                   title="Cerrar"
                                                   aria-label="Cerrar orden">
                                                    <i class="fas fa-check-circle"></i>
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($puede_eliminar && $estado === 'PENDIENTE'): ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger btn-icon"
                                                        onclick="confirmarEliminar(<?= (int)$orden['id'] ?>, '<?= htmlspecialchars($orden['num_om'] ?? '', ENT_QUOTES, 'UTF-8') ?>')"
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
                <i class="fas fa-list me-1"></i> Mostrando <?= count($ordenes) ?> orden(es)
            </div>
        </div>
    </div>

</div>

<style>
.card { border-radius: 16px; box-shadow: 0 2px 12px var(--shadow-color); }
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