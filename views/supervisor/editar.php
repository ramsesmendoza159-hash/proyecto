<?php
// views/supervisor/editar_supervision.php
// Editar Supervisión - VERSIÓN CORREGIDA

// Incluir helpers necesarios
require_once __DIR__ . '/../../helpers/SecurityHelper.php';

// ✅ Verificar si la sesión ya está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'supervisor') {
    header('Location: /proyecto/auth/login');
    exit();
}

$titulo = "Editar Supervisión";
$seccion = "supervisiones";
include_once __DIR__ . '/../layouts/header.php';
// ❌ ELIMINAR: include_once __DIR__ . '/../layouts/sidebar.php';

$supervision = $supervision ?? null;
if (!$supervision) {
    header('Location: /proyecto/supervisor/supervisiones');
    exit();
}

// Verificar que la supervisión pertenezca al supervisor actual
if ($supervision['supervisor_id'] != $_SESSION['usuario_id']) {
    header('Location: /proyecto/supervisor/supervisiones');
    exit();
}

$estado = $supervision['estado'] ?? 'PENDIENTE';
?>

<div class="container-fluid px-0">

    <!-- ✅ Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-edit text-warning me-2"></i>Editar Supervisión #<?php echo $supervision['id']; ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Modifica la revisión de la orden de trabajo
            </p>
        </div>
        <a href="/proyecto/supervisor/supervisiones" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="row g-4">
        <!-- Columna principal - Formulario -->
        <div class="col-lg-8">
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-clipboard-check text-warning me-2"></i> Editar Revisión
                    </h5>
                </div>
                <div class="card-body">
                    <form action="/proyecto/supervisor/actualizar_supervision/<?php echo $supervision['id']; ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::generateCSRFToken(); ?>">
                        <input type="hidden" name="_method" value="PUT">
                        <input type="hidden" name="orden_id" value="<?php echo $supervision['orden_id']; ?>">
                        
                        <div class="mb-3">
                            <label for="calificacion" class="form-label fw-semibold">Calificación <span class="text-danger">*</span></label>
                            <select class="form-select" id="calificacion" name="calificacion" required>
                                <option value="">Seleccionar...</option>
                                <option value="1" <?php echo $supervision['calificacion'] == 1 ? 'selected' : ''; ?>>⭐ 1 - Muy malo</option>
                                <option value="2" <?php echo $supervision['calificacion'] == 2 ? 'selected' : ''; ?>>⭐⭐ 2 - Malo</option>
                                <option value="3" <?php echo $supervision['calificacion'] == 3 ? 'selected' : ''; ?>>⭐⭐⭐ 3 - Regular</option>
                                <option value="4" <?php echo $supervision['calificacion'] == 4 ? 'selected' : ''; ?>>⭐⭐⭐⭐ 4 - Bueno</option>
                                <option value="5" <?php echo $supervision['calificacion'] == 5 ? 'selected' : ''; ?>>⭐⭐⭐⭐⭐ 5 - Excelente</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="estado" class="form-label fw-semibold">Decisión <span class="text-danger">*</span></label>
                            <select class="form-select" id="estado" name="estado" required>
                                <option value="">Seleccionar...</option>
                                <option value="APROBADA" <?php echo $supervision['estado'] === 'APROBADA' ? 'selected' : ''; ?>>✅ Aprobar</option>
                                <option value="RECHAZADA" <?php echo $supervision['estado'] === 'RECHAZADA' ? 'selected' : ''; ?>>❌ Rechazar</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="observaciones" class="form-label fw-semibold">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="4" placeholder="Si rechazas, explica el motivo..."><?php echo htmlspecialchars($supervision['observaciones'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="cumple" name="cumple" value="1" 
                                   <?php echo ($supervision['cumple'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="cumple">
                                Cumple con los estándares de calidad
                            </label>
                        </div>

                        <hr>
                        <div class="d-flex gap-2">
                            <a href="/proyecto/supervisor/supervisiones" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Actualizar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Columna lateral -->
        <div class="col-lg-4">
            <!-- Información de la orden -->
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-clipboard-list text-primary me-2"></i> Información de la Orden
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($supervision['orden'])): ?>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Orden</label>
                            <p class="fw-semibold mb-0">#<?php echo $supervision['orden']['id']; ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Título</label>
                            <p class="fw-semibold mb-0"><?php echo htmlspecialchars($supervision['orden']['titulo']); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Técnico</label>
                            <p class="fw-semibold mb-0"><?php echo $supervision['orden']['tecnico'] ?? 'N/A'; ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Prioridad</label>
                            <p class="mb-0">
                                <?php 
                                $prioridad = $supervision['orden']['prioridad'] ?? 'Media';
                                $prioridadColor = match($prioridad) {
                                    'Urgente' => 'danger',
                                    'Alta' => 'warning',
                                    'Media' => 'info',
                                    'Baja' => 'success',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?php echo $prioridadColor; ?> bg-opacity-10 text-<?php echo $prioridadColor; ?>">
                                    <?php echo $prioridad; ?>
                                </span>
                            </p>
                        </div>
                        <a href="/proyecto/supervisor/ver_orden/<?php echo $supervision['orden_id']; ?>" class="btn btn-info w-100">
                            <i class="fas fa-eye me-1"></i> Ver Orden
                        </a>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">Orden no disponible</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Información de la supervisión -->
            <div class="card border-0 mt-4">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-info-circle text-primary me-2"></i> Información de la Supervisión
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Fecha creación</label>
                        <p class="fw-semibold mb-0"><?php echo date('d/m/Y H:i', strtotime($supervision['fecha_creacion'])); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Estado actual</label>
                        <p class="mb-0">
                            <?php 
                            $estadoColor = match($estado) {
                                'APROBADA' => 'success',
                                'RECHAZADA' => 'danger',
                                default => 'warning'
                            };
                            ?>
                            <span class="badge bg-<?php echo $estadoColor; ?> bg-opacity-10 text-<?php echo $estadoColor; ?>">
                                <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                <?php echo $estado; ?>
                            </span>
                        </p>
                    </div>
                </div>
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
.form-control, .form-select {
    border-radius: 10px;
    padding: 10px 14px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}
.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>