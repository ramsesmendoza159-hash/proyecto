<?php
// views/tecnico/detalle_orden.php
// Detalle de Orden - VERSIÓN CORREGIDA CON DESGLOSE DE COSTOS

// Verificar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'tecnico') {
    header('Location: /proyecto/auth/login');
    exit();
}

$titulo = "Detalle de Orden";
$seccion = "tecnico";
include_once __DIR__ . '/../layouts/header.php';

$orden = $orden ?? null;
if (!$orden) {
    header('Location: /proyecto/tecnico/mis_ordenes');
    exit();
}

$estado = $orden['status'] ?? 'PENDIENTE';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-list text-primary me-2"></i>Detalle de Orden #<?php echo $orden['id']; ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Información completa de la orden de trabajo
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/proyecto/tecnico/mis_ordenes" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <?php if ($estado === 'PENDIENTE' || $estado === 'EN_PROCESO'): ?>
                <a href="/proyecto/tecnico/cerrar_orden/<?php echo $orden['id']; ?>" class="btn btn-success">
                    <i class="fas fa-check-circle me-1"></i> Cerrar Orden
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <!-- Columna principal -->
        <div class="col-lg-8">

            <!-- Información de la orden -->
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-info-circle text-primary me-2"></i> Información de la Orden
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Título</label>
                                <p class="fw-semibold mb-0"><?php echo htmlspecialchars($orden['titulo'] ?? ''); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Área</label>
                                <p class="fw-semibold mb-0"><?php echo $orden['nombre_area'] ?? 'N/A'; ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Prioridad</label>
                                <p class="mb-0">
                                    <?php 
                                    $prioridad = $orden['prioridad'] ?? 'Media';
                                    $color = match($prioridad) {
                                        'Urgente' => 'danger',
                                        'Alta' => 'warning',
                                        'Media' => 'info',
                                        'Baja' => 'success',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?php echo $color; ?> bg-opacity-10 text-<?php echo $color; ?>">
                                        <?php echo $prioridad; ?>
                                    </span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Estado</label>
                                <p class="mb-0">
                                    <?php 
                                    $color = match($estado) {
                                        'CERRADA', 'APROBADA' => 'success',
                                        'EN_PROCESO' => 'info',
                                        'CANCELADA', 'RECHAZADA' => 'danger',
                                        default => 'warning'
                                    };
                                    ?>
                                    <span class="badge bg-<?php echo $color; ?> bg-opacity-10 text-<?php echo $color; ?>">
                                        <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                        <?php echo $estado; ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Fecha creación</label>
                                <p class="fw-semibold mb-0"><?php echo date('d/m/Y H:i', strtotime($orden['fecha_creacion'] ?? 'now')); ?></p>
                            </div>
                            <?php if (!empty($orden['fecha_limite'])): ?>
                                <div class="mb-3">
                                    <label class="text-muted small fw-semibold text-uppercase">Fecha límite</label>
                                    <p class="fw-semibold mb-0"><?php echo date('d/m/Y', strtotime($orden['fecha_limite'])); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($orden['fecha_finalizacion'])): ?>
                                <div class="mb-3">
                                    <label class="text-muted small fw-semibold text-uppercase">Fecha finalización</label>
                                    <p class="fw-semibold mb-0"><?php echo date('d/m/Y H:i', strtotime($orden['fecha_finalizacion'])); ?></p>
                                </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Técnico asignado</label>
                                <p class="fw-semibold mb-0"><?php echo $orden['tecnico_nombre'] ?? 'Sin asignar'; ?></p>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div>
                        <label class="text-muted small fw-semibold text-uppercase">Descripción</label>
                        <p class="mt-2"><?php echo nl2br(htmlspecialchars($orden['descripcion_mantenimiento'] ?? 'Sin descripción')); ?></p>
                    </div>
                    
                    <!-- Pasos a realizar -->
                    <?php if (!empty($orden['pasos'])): ?>
                        <hr>
                        <div>
                            <label class="text-muted small fw-semibold text-uppercase">Pasos a realizar</label>
                            <p class="mt-2"><?php echo nl2br(htmlspecialchars($orden['pasos'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Trabajo realizado (solo si está cerrada) -->
            <?php if ($estado === 'CERRADA' || $estado === 'APROBADA'): ?>
                <div class="card border-0 mt-4">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0 fw-semibold text-success">
                            <i class="fas fa-check-circle text-success me-2"></i> Trabajo Realizado
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($orden['descripcion_realizada'])): ?>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Descripción del trabajo</label>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($orden['descripcion_realizada'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($orden['pasos_ejecutados'])): ?>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Pasos ejecutados</label>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($orden['pasos_ejecutados'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- ✅ DESGLOSE DE COSTOS -->
                        <hr>
                        <h6 class="fw-semibold text-uppercase small text-muted mb-3">
                            <i class="fas fa-calculator me-1"></i> Desglose de Costos
                        </h6>
                        
                        <div class="row g-3">
                            <!-- Horas trabajadas -->
                            <?php if (!empty($orden['horas_trabajadas'])): ?>
                                <div class="col-md-4">
                                    <div class="costo-item">
                                        <label class="costo-label">Horas trabajadas</label>
                                        <p class="costo-value">
                                            <?php echo number_format($orden['horas_trabajadas'], 2); ?> h
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Tarifa por hora -->
                            <?php if (!empty($orden['tarifa_tecnico'])): ?>
                                <div class="col-md-4">
                                    <div class="costo-item">
                                        <label class="costo-label">Tarifa por hora</label>
                                        <p class="costo-value">
                                            S/ <?php echo number_format($orden['tarifa_tecnico'], 2); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Costo mano de obra -->
                            <?php if (!empty($orden['costo_mano_obra'])): ?>
                                <div class="col-md-4">
                                    <div class="costo-item">
                                        <label class="costo-label">Costo Mano de Obra</label>
                                        <p class="costo-value">
                                            S/ <?php echo number_format($orden['costo_mano_obra'], 2); ?>
                                        </p>
                                        <small class="costo-formula">
                                            <?php echo number_format($orden['horas_trabajadas'] ?? 0, 2); ?> h × 
                                            S/ <?php echo number_format($orden['tarifa_tecnico'] ?? 0, 2); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Costo repuestos -->
                            <div class="col-md-4">
                                <div class="costo-item">
                                    <label class="costo-label">Costo Repuestos</label>
                                    <p class="costo-value">
                                        S/ <?php echo number_format($orden['costo_repuestos'] ?? 0, 2); ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Costo total -->
                            <?php if (!empty($orden['costo_total'])): ?>
                                <div class="col-md-4">
                                    <div class="costo-item costo-total-item">
                                        <label class="costo-label">Costo Total</label>
                                        <p class="costo-value costo-total-value">
                                            S/ <?php echo number_format($orden['costo_total'], 2); ?>
                                        </p>
                                        <small class="costo-formula">
                                            Mano de obra + Repuestos
                                        </small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Columna lateral -->
        <div class="col-lg-4">
            <!-- Información de costos -->
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-money-bill-wave text-primary me-2"></i> Costos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Tarifa Técnico</label>
                        <p class="fw-semibold mb-0 text-end">S/ <?php echo number_format($orden['tarifa_tecnico'] ?? 0, 2); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Costo Repuestos</label>
                        <p class="fw-semibold mb-0 text-end">S/ <?php echo number_format($orden['costo_repuestos'] ?? 0, 2); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Costo Mano Obra</label>
                        <p class="fw-semibold mb-0 text-end">S/ <?php echo number_format($orden['costo_mano_obra'] ?? 0, 2); ?></p>
                    </div>
                    <hr>
                    <div>
                        <label class="text-muted small fw-semibold text-uppercase">Costo Total</label>
                        <p class="fw-bold mb-0 text-end text-primary" style="font-size:1.2rem;">
                            S/ <?php echo number_format($orden['costo_total'] ?? 0, 2); ?>
                        </p>
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

            <!-- Acciones -->
            <?php if ($estado === 'PENDIENTE' || $estado === 'EN_PROCESO'): ?>
                <div class="card border-0 mt-4">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-tasks text-warning me-2"></i> Acciones
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">¿Ya completaste el trabajo?</p>
                        <a href="/proyecto/tecnico/cerrar_orden/<?php echo $orden['id']; ?>" class="btn btn-success w-100">
                            <i class="fas fa-check-circle me-2"></i> Cerrar Orden
                        </a>
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

/* ========================================== */
/* DESGLOSE DE COSTOS */
/* ========================================== */
.costo-item {
    padding: 14px 16px;
    background: var(--bg-card-hover, #f8f9fa);
    border: 1px solid var(--border-color, #e9ecef);
    border-radius: 12px;
    height: 100%;
    transition: all 0.3s ease;
}

.costo-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.costo-total-item {
    background: linear-gradient(135deg, rgba(25, 135, 84, 0.1), rgba(25, 135, 84, 0.05));
    border-color: rgba(25, 135, 84, 0.3);
}

.costo-label {
    display: block;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    color: var(--text-muted, #6c757d);
    margin-bottom: 6px;
}

.costo-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-primary, #1a1a2e);
    margin-bottom: 4px;
}

.costo-total-value {
    font-size: 1.3rem;
    color: #198754;
}

.costo-formula {
    display: block;
    font-size: 0.7rem;
    color: var(--text-muted, #8a8aa8);
    font-style: italic;
    margin-top: 4px;
}

/* Modo oscuro */
[data-theme="dark"] .costo-item {
    background: #1a1a2e;
    border-color: #2a2a45;
}

[data-theme="dark"] .costo-total-item {
    background: linear-gradient(135deg, rgba(25, 135, 84, 0.15), rgba(25, 135, 84, 0.05));
    border-color: rgba(25, 135, 84, 0.4);
}

[data-theme="dark"] .costo-value {
    color: #ffffff;
}

[data-theme="dark"] .costo-total-value {
    color: #4ade80;
}

[data-theme="dark"] .costo-label,
[data-theme="dark"] .costo-formula {
    color: #8a8aa8;
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>