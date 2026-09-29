<?php
// views/ordenes/ver.php
// Detalle de orden de trabajo - VERSIÓN CORREGIDA
// ✅ FIX 1: Total ahora usa solo costo_total (no sumaba duplicado)
// ✅ FIX 2: Botón "Cancelar" solo aparece en estados válidos
// ✅ FIX 3: uses ENT_QUOTES en el onclick

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /proyecto/auth/login');
    exit;
}

if (!isset($orden) || !$orden) {
    $_SESSION['error'] = 'Orden no encontrada';
    header('Location: /proyecto/ordenes');
    exit;
}

$seccion = 'ordenes';
$titulo = 'Detalle de Orden - ' . htmlspecialchars($orden['num_om'] ?? '');

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-list text-primary me-2"></i>
                Orden: <?php echo htmlspecialchars($orden['num_om'] ?? 'N/A'); ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Detalle completo de la orden de trabajo
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/proyecto/ordenes" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <?php if (in_array($orden['status'] ?? '', ['EJECUTADA', 'EN_PROCESO'])): ?>
                <a href="/proyecto/ordenes/cerrar/<?php echo $orden['id']; ?>" class="btn btn-success">
                    <i class="fas fa-check me-1"></i> Cerrar
                </a>
            <?php endif; ?>
            <?php if (($orden['status'] ?? '') === 'PENDIENTE'): ?>
                <a href="/proyecto/ordenes/editar/<?php echo $orden['id']; ?>" class="btn btn-warning">
                    <i class="fas fa-edit me-1"></i> Editar
                </a>
            <?php endif; ?>
            <?php if (in_array($orden['status'] ?? '', ['PENDIENTE', 'EN_PROCESO', 'EJECUTADA'], true)): ?>
                <button class="btn btn-danger" onclick="confirmarCancelar()">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
            <?php endif; ?>
        </div>
    </div>

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

    <div class="row g-4">
        <!-- Columna principal -->
        <div class="col-lg-8">
            <!-- Información general -->
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-info-circle text-primary me-2"></i> Información General
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">N° OM</label>
                                <p class="fw-semibold mb-0"><?php echo htmlspecialchars($orden['num_om'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Título</label>
                                <p class="fw-semibold mb-0"><?php echo htmlspecialchars($orden['titulo'] ?? 'Sin título'); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Estado</label>
                                <p class="mb-0">
                                    <?php
                                    $status = $orden['status'] ?? 'PENDIENTE';
                                    $badgeStatus = match($status) {
                                        'PENDIENTE' => 'warning',
                                        'EN_PROCESO' => 'info',
                                        'EJECUTADA' => 'primary',
                                        'CERRADA' => 'secondary',
                                        'APROBADA' => 'success',
                                        'RECHAZADA' => 'danger',
                                        'CANCELADA' => 'dark',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?php echo $badgeStatus; ?> bg-opacity-10 text-<?php echo $badgeStatus; ?>">
                                        <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Prioridad</label>
                                <p class="mb-0">
                                    <?php 
                                    $prioridad = $orden['prioridad'] ?? 'Media';
                                    $badgeClass = match($prioridad) {
                                        'Alta' => 'danger',
                                        'Media' => 'warning',
                                        'Baja' => 'success',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?php echo $badgeClass; ?> bg-opacity-10 text-<?php echo $badgeClass; ?>">
                                        <?php echo htmlspecialchars($prioridad); ?>
                                    </span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Tipo</label>
                                <p class="fw-semibold mb-0"><?php echo htmlspecialchars($orden['tipo_mantenimiento'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Fecha Creación</label>
                                <p class="fw-semibold mb-0"><?php echo date('d/m/Y H:i', strtotime($orden['fecha_creacion'] ?? 'now')); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Planta</label>
                                <p class="fw-semibold mb-0"><?php echo htmlspecialchars($orden['nombre_planta'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Área</label>
                                <p class="fw-semibold mb-0"><?php echo htmlspecialchars($orden['nombre_area'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Equipo</label>
                                <p class="fw-semibold mb-0"><?php echo htmlspecialchars($orden['nombre_equipo'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Componente</label>
                                <p class="fw-semibold mb-0"><?php echo htmlspecialchars($orden['nombre_componente'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Técnico</label>
                                <p class="fw-semibold mb-0"><?php echo htmlspecialchars($orden['tecnico_nombre'] ?? 'Sin asignar'); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Supervisor</label>
                                <p class="fw-semibold mb-0"><?php echo htmlspecialchars($orden['supervisor_nombre'] ?? 'Sin asignar'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Descripción -->
            <div class="card border-0 mt-4">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-align-left text-primary me-2"></i> Descripción
                    </h5>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($orden['descripcion_mantenimiento'] ?? 'Sin descripción')); ?></p>
                </div>
            </div>

            <!-- Trabajo realizado -->
            <?php if (!empty($orden['descripcion_realizada'])): ?>
                <div class="card border-0 mt-4">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0 fw-semibold text-success">
                            <i class="fas fa-check-circle text-success me-2"></i> Trabajo Realizado
                        </h5>
                    </div>
                    <div class="card-body">
                        <p><?php echo nl2br(htmlspecialchars($orden['descripcion_realizada'])); ?></p>
                        <?php if (!empty($orden['pasos_ejecutados'])): ?>
                            <hr>
                            <h6 class="fw-semibold">Pasos ejecutados:</h6>
                            <p><?php echo nl2br(htmlspecialchars($orden['pasos_ejecutados'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Columna lateral -->
        <div class="col-lg-4">
            <!-- Costos -->
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-money-bill-wave text-primary me-2"></i> Costos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Costo Repuestos</label>
                        <p class="fw-semibold mb-0 text-end">S/ <?php echo number_format($orden['costo_repuestos'] ?? 0, 2); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Costo Mano Obra</label>
                        <p class="fw-semibold mb-0 text-end">S/ <?php echo number_format($orden['costo_mano_obra'] ?? 0, 2); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Tarifa Técnico</label>
                        <p class="fw-semibold mb-0 text-end">S/ <?php echo number_format($orden['tarifa_tecnico'] ?? 0, 2); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Horas Trabajadas</label>
                        <p class="fw-semibold mb-0 text-end"><?php echo number_format($orden['horas_trabajadas'] ?? 0, 1); ?> h</p>
                    </div>
                    <hr>
                    <div class="mb-0">
                        <label class="text-muted small fw-semibold text-uppercase">Costo Total</label>
                        <p class="fw-bold mb-0 text-end text-primary" style="font-size:1.2rem;">
                            S/ <?php echo number_format($orden['costo_total'] ?? 0, 2); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Fechas -->
            <div class="card border-0 mt-4">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-calendar-alt text-primary me-2"></i> Fechas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Fecha Inicio</label>
                        <p class="fw-semibold mb-0"><?php echo date('d/m/Y', strtotime($orden['fecha_inicio'] ?? 'now')); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Fecha Estimada</label>
                        <p class="fw-semibold mb-0"><?php echo $orden['fecha_estimada'] ? date('d/m/Y', strtotime($orden['fecha_estimada'])) : 'N/A'; ?></p>
                    </div>
                    <div class="mb-0">
                        <label class="text-muted small fw-semibold text-uppercase">Fecha Finalización</label>
                        <p class="fw-semibold mb-0"><?php echo $orden['fecha_finalizacion'] ? date('d/m/Y H:i', strtotime($orden['fecha_finalizacion'])) : 'Pendiente'; ?></p>
                    </div>
                </div>
            </div>

            <!-- Observaciones -->
            <?php if (!empty($orden['observaciones_tecnico']) || !empty($orden['observaciones_cierre'])): ?>
                <div class="card border-0 mt-4">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-comment text-primary me-2"></i> Observaciones
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($orden['observaciones_tecnico'])): ?>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Técnico</label>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($orden['observaciones_tecnico'])); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($orden['observaciones_cierre'])): ?>
                            <div>
                                <label class="text-muted small fw-semibold text-uppercase">Cierre</label>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($orden['observaciones_cierre'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
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

<!-- Modal para cancelar -->
<div class="modal fade" id="modalCancelar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-times-circle me-2"></i> Cancelar Orden</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de cancelar la orden <strong><?php echo htmlspecialchars($orden['num_om'] ?? ''); ?></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i> Esta acción no se puede deshacer.
                </div>
                <div class="form-group">
                    <label for="observaciones_cancelacion" class="fw-semibold">Motivo de cancelación:</label>
                    <textarea id="observaciones_cancelacion" class="form-control" rows="3" placeholder="Describir el motivo..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Volver</button>
                <form id="formCancelar" method="POST" action="/proyecto/ordenes/cambiarEstado">
                    <input type="hidden" name="id" value="<?php echo $orden['id']; ?>">
                    <input type="hidden" name="estado" value="CANCELADA">
                    <input type="hidden" name="observaciones" id="observaciones_cancelacion_input">
                    <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::generateCSRFToken(); ?>">
                    <button type="submit" class="btn btn-danger">Confirmar Cancelación</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmarCancelar() {
    new bootstrap.Modal(document.getElementById('modalCancelar')).show();
}

document.getElementById('formCancelar').addEventListener('submit', function(e) {
    var obs = document.getElementById('observaciones_cancelacion').value;
    if (!obs.trim()) {
        e.preventDefault();
        alert('Por favor, ingresa un motivo de cancelación.');
        return false;
    }
    document.getElementById('observaciones_cancelacion_input').value = obs;
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>