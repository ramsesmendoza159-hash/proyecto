<?php
// views/ordenes/detalle.php
// Detalle Completo de Orden - VERSIÓN CORREGIDA
// ✅ FIX: Columnas correctas según la BD (status, descripcion_mantenimiento, descripcion_realizada, fecha_estimada, fecha_finalizacion, nombre_area, tecnico_nombre, nombre_equipo)

require_once __DIR__ . '/../../helpers/SecurityHelper.php';

if (!SecurityHelper::verificarSesion()) {
    header('Location: /proyecto/auth/login');
    exit();
}

$titulo = "Detalle Completo de Orden";
$seccion = "ordenes";
include_once __DIR__ . '/../layouts/header.php';

$orden = $orden ?? null;
if (!$orden) {
    header('Location: /proyecto/ordenes');
    exit();
}

// ✅ FIX: Usar 'status' (columna real de la BD)
$estado = $orden['status'] ?? 'PENDIENTE';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-list text-primary me-2"></i>
                Detalle de Orden #<?php echo $orden['id']; ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Información completa de la orden de trabajo
            </p>
        </div>
        <a href="/proyecto/ordenes" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <!-- Detalle completo -->
    <div class="card border-0">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6">
                    <h5 class="fw-semibold">
                        <i class="fas fa-info-circle text-primary me-2"></i>Información General
                    </h5>
                    <div class="mt-3">
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">ID</label>
                            <p class="fw-semibold mb-0"><?php echo $orden['id']; ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">N° OM</label>
                            <p class="fw-semibold mb-0"><?php echo htmlspecialchars($orden['num_om'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Título</label>
                            <p class="fw-semibold mb-0"><?php echo htmlspecialchars($orden['titulo'] ?? ''); ?></p>
                        </div>
                        <!-- ✅ FIX: nombre_area (del JOIN) -->
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Área</label>
                            <p class="fw-semibold mb-0"><?php echo htmlspecialchars($orden['nombre_area'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Prioridad</label>
                            <p class="mb-0">
                                <?php 
                                $prioridad = $orden['prioridad'] ?? 'Media';
                                $prioridadColor = match($prioridad) {
                                    'Urgente' => 'danger',
                                    'Alta' => 'warning',
                                    'Media' => 'info',
                                    'Baja' => 'success',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?php echo $prioridadColor; ?> bg-opacity-10 text-<?php echo $prioridadColor; ?>">
                                    <?php echo htmlspecialchars($prioridad); ?>
                                </span>
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Estado</label>
                            <p class="mb-0">
                                <?php 
                                $estadoColor = match($estado) {
                                    'CERRADA', 'APROBADA' => 'success',
                                    'EN_PROCESO' => 'info',
                                    'EJECUTADA' => 'primary',
                                    'CANCELADA', 'RECHAZADA' => 'danger',
                                    default => 'warning'
                                };
                                ?>
                                <span class="badge bg-<?php echo $estadoColor; ?> bg-opacity-10 text-<?php echo $estadoColor; ?>">
                                    <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                    <?php echo htmlspecialchars($estado); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h5 class="fw-semibold">
                        <i class="fas fa-calendar-alt text-primary me-2"></i>Fechas y Asignación
                    </h5>
                    <div class="mt-3">
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Fecha Creación</label>
                            <p class="fw-semibold mb-0"><?php echo date('d/m/Y H:i', strtotime($orden['fecha_creacion'] ?? 'now')); ?></p>
                        </div>
                        <!-- ✅ FIX: fecha_estimada (antes fecha_limite) -->
                        <?php if (!empty($orden['fecha_estimada'])): ?>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Fecha Estimada</label>
                                <p class="fw-semibold mb-0"><?php echo date('d/m/Y', strtotime($orden['fecha_estimada'])); ?></p>
                            </div>
                        <?php endif; ?>
                        <!-- ✅ FIX: fecha_finalizacion (antes fecha_cierre) -->
                        <?php if (!empty($orden['fecha_finalizacion'])): ?>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Fecha Finalización</label>
                                <p class="fw-semibold mb-0"><?php echo date('d/m/Y H:i', strtotime($orden['fecha_finalizacion'])); ?></p>
                            </div>
                        <?php endif; ?>
                        <!-- ✅ FIX: tecnico_nombre (del JOIN) -->
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Técnico</label>
                            <p class="fw-semibold mb-0"><?php echo htmlspecialchars($orden['tecnico_nombre'] ?? 'Sin asignar'); ?></p>
                        </div>
                        <!-- ✅ FIX: nombre_equipo (del JOIN) -->
                        <?php if (!empty($orden['nombre_equipo'])): ?>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Equipo</label>
                                <p class="fw-semibold mb-0"><?php echo htmlspecialchars($orden['nombre_equipo']); ?></p>
                            </div>
                        <?php endif; ?>
                        <!-- ✅ NUEVO: Supervisor -->
                        <?php if (!empty($orden['supervisor_nombre'])): ?>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Supervisor</label>
                                <p class="fw-semibold mb-0"><?php echo htmlspecialchars($orden['supervisor_nombre']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <hr>
            <div class="mt-3">
                <h5 class="fw-semibold">
                    <i class="fas fa-align-left text-primary me-2"></i>Descripción del Mantenimiento
                </h5>
                <!-- ✅ FIX: descripcion_mantenimiento (antes descripcion) -->
                <p class="mt-2"><?php echo nl2br(htmlspecialchars($orden['descripcion_mantenimiento'] ?? 'Sin descripción')); ?></p>
            </div>

            <?php if (!empty($orden['pasos'])): ?>
                <hr>
                <div class="mt-3">
                    <h5 class="fw-semibold">
                        <i class="fas fa-list-ol text-primary me-2"></i>Pasos
                    </h5>
                    <p class="mt-2"><?php echo nl2br(htmlspecialchars($orden['pasos'])); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($estado === 'CERRADA' || $estado === 'APROBADA' || $estado === 'EJECUTADA'): ?>
                <hr>
                <div class="mt-3">
                    <h5 class="fw-semibold text-success">
                        <i class="fas fa-check-circle text-success me-2"></i>Detalles de Cierre
                    </h5>
                    <!-- ✅ FIX: descripcion_realizada (antes descripcion_cierre) -->
                    <?php if (!empty($orden['descripcion_realizada'])): ?>
                        <div class="mt-3 mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Descripción del trabajo</label>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($orden['descripcion_realizada'])); ?></p>
                        </div>
                    <?php endif; ?>
                    <div class="row g-3">
                        <?php if (isset($orden['horas_trabajadas'])): ?>
                            <div class="col-md-4">
                                <label class="text-muted small fw-semibold text-uppercase">Horas trabajadas</label>
                                <p class="fw-semibold mb-0"><?php echo number_format($orden['horas_trabajadas'] ?? 0, 2); ?> horas</p>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($orden['costo_total'])): ?>
                            <div class="col-md-4">
                                <label class="text-muted small fw-semibold text-uppercase">Costo Total</label>
                                <p class="fw-semibold mb-0">S/ <?php echo number_format($orden['costo_total'] ?? 0, 2); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<style>
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>