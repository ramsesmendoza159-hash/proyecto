<?php
// views/supervisor/editar.php
// Editar Supervisión - VERSIÓN CORREGIDA

require_once __DIR__ . '/../../helpers/SecurityHelper.php';

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

$supervision = $supervision ?? null;
if (!$supervision) {
    header('Location: /proyecto/supervisor/supervisiones');
    exit();
}

// ✅ FIX: blindar TODOS los accesos
$sup_id       = (int)($supervision['id'] ?? 0);
$sup_sup_id   = (int)($supervision['supervisor_id'] ?? 0);
$sup_orden_id = (int)($supervision['orden_id'] ?? 0);
$sup_calif    = $supervision['calificacion'] ?? '';
$sup_estado   = $supervision['estado'] ?? 'PENDIENTE';
$sup_obs      = $supervision['observaciones'] ?? '';
$sup_cumple   = !empty($supervision['cumple']);
$sup_fecha_c  = $supervision['fecha_creacion'] ?? null;

$fecha_c_val = !empty($sup_fecha_c) && strtotime($sup_fecha_c) !== false;
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-edit text-warning me-2"></i>Editar Supervisión #<?= $sup_id ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Modifica la revisión de la orden de trabajo
            </p>
        </div>
        <a href="/proyecto/supervisor/supervisiones" class="btn btn-secondary btn-header-action">
            <i class="fas fa-arrow-left"></i> Volver
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
                    <form action="/proyecto/supervisor/actualizar_supervision/<?= $sup_id ?>" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken(); ?>">
                        <input type="hidden" name="_method" value="PUT">
                        <input type="hidden" name="orden_id" value="<?= $sup_orden_id ?>">
                        
                        <div class="mb-3">
                            <label for="calificacion" class="form-label fw-semibold">Calificación <span class="text-danger">*</span></label>
                            <select class="form-select" id="calificacion" name="calificacion" required>
                                <option value="">Seleccionar...</option>
                                <option value="1" <?= $sup_calif == 1 ? 'selected' : '' ?>>⭐ 1 - Muy malo</option>
                                <option value="2" <?= $sup_calif == 2 ? 'selected' : '' ?>>⭐⭐ 2 - Malo</option>
                                <option value="3" <?= $sup_calif == 3 ? 'selected' : '' ?>>⭐⭐⭐ 3 - Regular</option>
                                <option value="4" <?= $sup_calif == 4 ? 'selected' : '' ?>>⭐⭐⭐⭐ 4 - Bueno</option>
                                <option value="5" <?= $sup_calif == 5 ? 'selected' : '' ?>>⭐⭐⭐⭐⭐ 5 - Excelente</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="estado" class="form-label fw-semibold">Decisión <span class="text-danger">*</span></label>
                            <select class="form-select" id="estado" name="estado" required>
                                <option value="">Seleccionar...</option>
                                <option value="APROBADA" <?= $sup_estado === 'APROBADA' ? 'selected' : '' ?>>✅ Aprobar</option>
                                <option value="RECHAZADA" <?= $sup_estado === 'RECHAZADA' ? 'selected' : '' ?>>❌ Rechazar</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="observaciones" class="form-label fw-semibold">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="4" placeholder="Si rechazas, explica el motivo..."><?= htmlspecialchars($sup_obs) ?></textarea>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="cumple" name="cumple" value="1" 
                                   <?= $sup_cumple ? 'checked' : '' ?>>
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
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-info-circle text-primary me-2"></i> Información de la Supervisión
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Fecha creación</label>
                        <p class="fw-semibold mb-0">
                            <?= $fecha_c_val ? date('d/m/Y H:i', strtotime($sup_fecha_c)) : 'N/A' ?>
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Estado actual</label>
                        <p class="mb-0">
                            <?php
                            $estadoRaw = strtoupper($sup_estado);
                            $estadoColor = match($estadoRaw) {
                                'APROBADA' => 'success',
                                'RECHAZADA' => 'danger',
                                default => 'warning'
                            };
                            ?>
                            <span class="badge bg-<?= $estadoColor ?> bg-opacity-10 text-<?= $estadoColor ?>">
                                <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                <?= htmlspecialchars($estadoRaw) ?>
                            </span>
                        </p>
                    </div>
                    <a href="/proyecto/supervisor/ver_orden/<?= $sup_orden_id ?>" class="btn btn-info text-white w-100">
                        <i class="fas fa-eye me-1"></i> Ver Orden
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
.form-control, .form-select {
    border-radius: 10px;
    padding: 10px 14px;
    border: 2px solid var(--border-color);
    background: var(--bg-input);
    color: var(--text-primary);
    transition: all 0.3s ease;
}
.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>