<?php
// views/consultor/ordenes.php
// Órdenes - Consultor (solo lectura) - VERSIÓN OFICIAL

if (!isset($seccion)) {
    $seccion = 'consultor';
}
if (!isset($titulo)) {
    $titulo = 'Órdenes de Trabajo';
}
if (!isset($ordenes)) {
    $ordenes = [];
}

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-list text-primary me-2"></i>Órdenes de Trabajo
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Visualización de todas las órdenes
            </p>
        </div>
        <a href="/proyecto/consultor" class="btn btn-secondary btn-header-action">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
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
                            <th class="text-center" style="width:100px;">Acciones</th>
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
                                // ✅ FIX: blindar todos los accesos
                                $orden_id   = (int)($orden['id'] ?? 0);
                                $titulo_om  = $orden['titulo'] ?? 'Sin título';
                                $area_om    = $orden['nombre_area'] ?? 'N/A';
                                $tecnico_om = $orden['tecnico_nombre'] ?? 'Sin asignar';
                                $status_om  = strtoupper($orden['status'] ?? 'PENDIENTE');
                                $prioridad  = $orden['prioridad'] ?? 'Media';
                                $fecha_om   = $orden['fecha_creacion'] ?? null;

                                $estadoColor = match($status_om) {
                                    'PENDIENTE'  => 'warning',
                                    'EN_PROCESO' => 'info',
                                    'EJECUTADA'  => 'primary',
                                    'CERRADA', 'APROBADA' => 'success',
                                    'CANCELADA', 'RECHAZADA' => 'danger',
                                    'DEVUELTA'   => 'secondary',
                                    default      => 'secondary'
                                };
                                $prioridadColor = match($prioridad) {
                                    'Baja'    => 'success',
                                    'Media'   => 'info',
                                    'Alta'    => 'warning',
                                    'Urgente' => 'danger',
                                    default   => 'secondary'
                                };
                            ?>
                                <tr>
                                    <td><span class="fw-semibold">#<?= $orden_id ?></span></td>
                                    <td><?= htmlspecialchars($titulo_om) ?></td>
                                    <td><?= htmlspecialchars($area_om) ?></td>
                                    <td><?= htmlspecialchars($tecnico_om) ?></td>
                                    <td>
                                        <span class="badge-status bg-<?= $estadoColor ?> bg-opacity-10 text-<?= $estadoColor ?>">
                                            <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                            <?= htmlspecialchars($status_om) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $prioridadColor ?> bg-opacity-10 text-<?= $prioridadColor ?>">
                                            <?= htmlspecialchars($prioridad) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <?= $fecha_om && strtotime($fecha_om) !== false
                                                ? date('d/m/Y', strtotime($fecha_om))
                                                : 'N/A' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="/proyecto/consultor/ver_orden/<?= $orden_id ?>"
                                               class="btn btn-sm btn-outline-info btn-icon"
                                               title="Ver detalle"
                                               aria-label="Ver detalle de orden">
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
            <div class="mt-3 text-muted small">
                <i class="fas fa-list me-1"></i> Mostrando <?= count($ordenes) ?> órden(es)
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

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>