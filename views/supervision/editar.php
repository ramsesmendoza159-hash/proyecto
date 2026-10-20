<?php
// views/supervision/editar.php
// Editar Supervisión - VERSIÓN CORREGIDA

require_once __DIR__ . '/../../helpers/SecurityHelper.php';

if (!SecurityHelper::verificarSesion()) {
    header('Location: /proyecto/auth/login');
    exit();
}

if (!SecurityHelper::verificarRol(['admin', 'supervisor'])) {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    header('Location: /proyecto/dashboard');
    exit();
}

$titulo = "Editar Supervisión";
$seccion = "supervision";
include_once __DIR__ . '/../layouts/header.php';

$supervision = $supervision ?? null;
if (!$supervision) {
    header('Location: /proyecto/supervision');
    exit();
}

$ordenes = $ordenes ?? [];
$supervisores = $supervisores ?? [];

// ✅ FIX: blindar TODOS los accesos
$sup_id        = (int)($supervision['id'] ?? 0);
$sup_orden_id  = (int)($supervision['orden_id'] ?? 0);
$sup_sup_id    = (int)($supervision['supervisor_id'] ?? 0);
$sup_calif     = $supervision['calificacion'] ?? '';
$sup_estado    = $supervision['estado'] ?? 'PENDIENTE';
$sup_obs       = $supervision['observaciones'] ?? '';
$sup_cumple    = !empty($supervision['cumple']);
$sup_fecha_sup = $supervision['fecha_supervision'] ?? null;
$sup_fecha_cre = $supervision['fecha_creacion'] ?? null;

// Formatear fecha para datetime-local
$fecha_supervision_valor = '';
if (!empty($sup_fecha_sup) && strtotime($sup_fecha_sup) !== false) {
    $fecha_supervision_valor = date('Y-m-d\TH:i', strtotime($sup_fecha_sup));
} elseif (!empty($sup_fecha_cre) && strtotime($sup_fecha_cre) !== false) {
    $fecha_supervision_valor = date('Y-m-d\TH:i', strtotime($sup_fecha_cre));
} else {
    $fecha_supervision_valor = date('Y-m-d\TH:i');
}
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-edit text-warning me-2"></i>Editar Supervisión #<?= $sup_id ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Modifica los datos de la supervisión
            </p>
        </div>
        <a href="/proyecto/supervision" class="btn btn-secondary btn-header-action">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Formulario -->
    <div class="card border-0">
        <div class="card-body">
            <form action="/proyecto/supervision/actualizar/<?= $sup_id ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken(); ?>">

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="orden_id" class="form-label fw-semibold">Orden de Trabajo <span class="text-danger">*</span></label>
                            <select class="form-select" id="orden_id" name="orden_id" required>
                                <option value="">Seleccionar orden...</option>
                                <?php if (!empty($ordenes)): ?>
                                    <?php foreach ($ordenes as $orden):
                                        $ord_id = (int)($orden['id'] ?? 0);
                                        $ord_tit = $orden['titulo'] ?? 'Sin título';
                                    ?>
                                        <option value="<?= $ord_id ?>"
                                            <?= $sup_orden_id == $ord_id ? 'selected' : '' ?>>
                                            #<?= $ord_id ?> - <?= htmlspecialchars($ord_tit) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">⚠️ No hay órdenes disponibles</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="supervisor_id" class="form-label fw-semibold">Supervisor <span class="text-danger">*</span></label>
                            <select class="form-select" id="supervisor_id" name="supervisor_id" required>
                                <option value="">Seleccionar supervisor...</option>
                                <?php if (!empty($supervisores)): ?>
                                    <?php foreach ($supervisores as $supervisor):
                                        $sup_row_id = (int)($supervisor['id'] ?? 0);
                                        $sup_nom = $supervisor['nombre'] ?? '';
                                    ?>
                                        <option value="<?= $sup_row_id ?>"
                                            <?= $sup_sup_id == $sup_row_id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($sup_nom) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">⚠️ No hay supervisores disponibles</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label for="observaciones" class="form-label fw-semibold">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="4"
                                      placeholder="Observaciones de la supervisión..."><?= htmlspecialchars($sup_obs) ?></textarea>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="calificacion" class="form-label fw-semibold">Calificación</label>
                            <select class="form-select" id="calificacion" name="calificacion">
                                <option value="">Sin calificar</option>
                                <option value="1" <?= $sup_calif == 1 ? 'selected' : '' ?>>⭐ 1 - Muy malo</option>
                                <option value="2" <?= $sup_calif == 2 ? 'selected' : '' ?>>⭐⭐ 2 - Malo</option>
                                <option value="3" <?= $sup_calif == 3 ? 'selected' : '' ?>>⭐⭐⭐ 3 - Regular</option>
                                <option value="4" <?= $sup_calif == 4 ? 'selected' : '' ?>>⭐⭐⭐⭐ 4 - Bueno</option>
                                <option value="5" <?= $sup_calif == 5 ? 'selected' : '' ?>>⭐⭐⭐⭐⭐ 5 - Excelente</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="estado" class="form-label fw-semibold">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="PENDIENTE" <?= $sup_estado === 'PENDIENTE' ? 'selected' : '' ?>>Pendiente</option>
                                <option value="APROBADA" <?= $sup_estado === 'APROBADA' ? 'selected' : '' ?>>✅ Aprobada</option>
                                <option value="RECHAZADA" <?= $sup_estado === 'RECHAZADA' ? 'selected' : '' ?>>❌ Rechazada</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="fecha_supervision" class="form-label fw-semibold">Fecha de Supervisión</label>
                            <input type="datetime-local" class="form-control" id="fecha_supervision"
                                   name="fecha_supervision"
                                   value="<?= $fecha_supervision_valor ?>">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="cumple" name="cumple"
                                   <?= $sup_cumple ? 'checked' : '' ?>>
                            <label class="form-check-label" for="cumple">
                                Cumple con los estándares de calidad
                            </label>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="d-flex gap-2">
                    <a href="/proyecto/supervision" class="btn btn-secondary">
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

<style>
.form-group { margin-bottom: 0; }
.form-label {
    font-size: 0.85rem;
    margin-bottom: 0.4rem;
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
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>