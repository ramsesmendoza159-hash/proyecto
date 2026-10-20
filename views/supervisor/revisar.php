<?php
// views/supervisor/revisar.php
// Revisar Orden - VERSIÓN CORREGIDA
// ✅ FIX: eliminado '}' sobrante al inicio
// ✅ FIX: botón "Ver Orden" apunta a /supervisor/ver_orden/
// ✅ FIX: blindaje de TODOS los accesos a $orden['xxx'] con ?? default

if (!isset($seccion)) {
    $seccion = 'supervisor';
}
if (!isset($titulo)) {
    $titulo = 'Revisar Orden de Trabajo';
}
if (!isset($orden) || !$orden) {
    header('Location: /proyecto/supervisor/ordenes');
    exit();
}

$evidencias = $evidencias ?? [];

// ✅ FIX: blindar TODOS los accesos
$ord_id       = (int)($orden['id'] ?? 0);
$ord_num_om   = $orden['num_om'] ?? 'N/A';
$ord_titulo   = $orden['titulo'] ?? 'Sin título';
$ord_area     = $orden['nombre_area'] ?? 'N/A';
$ord_prior    = $orden['prioridad'] ?? 'Media';
$ord_tec_nom  = $orden['tecnico_nombre'] ?? 'Sin asignar';
$ord_fecha_c  = $orden['fecha_creacion'] ?? null;
$ord_fecha_f  = $orden['fecha_finalizacion'] ?? null;
$ord_horas    = $orden['horas_trabajadas'] ?? null;
$ord_repuestos= $orden['repuestos_usados'] ?? 'Ninguno';
$ord_desc_m   = $orden['descripcion_mantenimiento'] ?? 'Sin descripción';
$ord_desc_r   = $orden['descripcion_realizada'] ?? '';
$ord_costo_t  = $orden['costo_total'] ?? 0;
$ord_costo_r  = $orden['costo_repuestos'] ?? 0;
$ord_costo_mo = $orden['costo_mano_obra'] ?? 0;
$ord_tarifa   = $orden['tarifa_tecnico'] ?? 0;

$fecha_c_val = !empty($ord_fecha_c) && strtotime($ord_fecha_c) !== false;
$fecha_f_val = !empty($ord_fecha_f) && strtotime($ord_fecha_f) !== false;

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-check text-primary me-2"></i>Revisar Orden #<?= $ord_id ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Revisa y evalúa el trabajo realizado
            </p>
        </div>
        <a href="/proyecto/supervisor/ordenes" class="btn btn-secondary btn-header-action">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

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
                                <label class="text-muted small fw-semibold text-uppercase">N° OM</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($ord_num_om) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Título</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($ord_titulo) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Área</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($ord_area) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Prioridad</label>
                                <p class="mb-0">
                                    <?php
                                    $color = match($ord_prior) {
                                        'Urgente' => 'danger',
                                        'Alta' => 'warning',
                                        'Media' => 'info',
                                        'Baja' => 'success',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?>">
                                        <?= htmlspecialchars($ord_prior) ?>
                                    </span>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Técnico</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($ord_tec_nom) ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Fecha creación</label>
                                <p class="fw-semibold mb-0">
                                    <?= $fecha_c_val ? date('d/m/Y H:i', strtotime($ord_fecha_c)) : 'N/A' ?>
                                </p>
                            </div>
                            <?php if ($fecha_f_val): ?>
                                <div class="mb-3">
                                    <label class="text-muted small fw-semibold text-uppercase">Fecha cierre</label>
                                    <p class="fw-semibold mb-0">
                                        <?= date('d/m/Y H:i', strtotime($ord_fecha_f)) ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Horas trabajadas</label>
                                <p class="fw-semibold mb-0">
                                    <?= !empty($ord_horas) ? number_format($ord_horas, 2) . ' horas' : 'N/A' ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Repuestos usados</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($ord_repuestos) ?></p>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div>
                        <label class="text-muted small fw-semibold text-uppercase">Descripción del mantenimiento</label>
                        <p class="mt-2"><?= nl2br(htmlspecialchars($ord_desc_m)) ?></p>
                    </div>
                    <?php if (!empty($ord_desc_r)): ?>
                        <hr>
                        <div>
                            <label class="text-muted small fw-semibold text-uppercase">Trabajo Realizado</label>
                            <p class="mt-2"><?= nl2br(htmlspecialchars($ord_desc_r)) ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Costos -->
                    <?php if (!empty($ord_costo_t)): ?>
                        <hr>
                        <label class="text-muted small fw-semibold text-uppercase d-block mb-3">Desglose de Costos</label>
                        <div class="row g-2">
                            <div class="col-md-3 col-6">
                                <div class="costo-mini">
                                    <small class="text-muted d-block">Horas</small>
                                    <strong><?= number_format($ord_horas ?? 0, 2) ?> h</strong>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="costo-mini">
                                    <small class="text-muted d-block">Tarifa</small>
                                    <strong>S/ <?= number_format($ord_tarifa, 2) ?></strong>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="costo-mini">
                                    <small class="text-muted d-block">Mano de Obra</small>
                                    <strong>S/ <?= number_format($ord_costo_mo, 2) ?></strong>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="costo-mini">
                                    <small class="text-muted d-block">Repuestos</small>
                                    <strong>S/ <?= number_format($ord_costo_r, 2) ?></strong>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="costo-mini costo-total-mini">
                                    <small class="text-muted d-block">Costo Total</small>
                                    <strong class="text-success" style="font-size:1.2rem;">
                                        S/ <?= number_format($ord_costo_t, 2) ?>
                                    </strong>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Evidencias -->
            <?php if (!empty($evidencias)): ?>
                <div class="card border-0 mt-4">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-images text-primary me-2"></i> Evidencias
                            <span class="badge bg-primary ms-2"><?= count($evidencias) ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <?php foreach ($evidencias as $evidencia):
                                $evidencia = trim((string)$evidencia);
                                if (empty($evidencia)) continue;
                            ?>
                                <div class="col-md-3 col-6">
                                    <a href="/proyecto/uploads/evidencias/<?= htmlspecialchars($evidencia, ENT_QUOTES, 'UTF-8') ?>"
                                       target="_blank" class="d-block">
                                        <img src="/proyecto/uploads/evidencias/<?= htmlspecialchars($evidencia, ENT_QUOTES, 'UTF-8') ?>"
                                             alt="Evidencia" class="img-fluid rounded"
                                             style="height: 150px; width: 100%; object-fit: cover;">
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Columna lateral - Formulario -->
        <div class="col-lg-4">
            <div class="card border-0">
                <div class="card-header bg-primary text-white border-0">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-clipboard-check me-2"></i> Revisión de Supervisión
                    </h5>
                </div>
                <div class="card-body">
                    <form action="/proyecto/supervisor/guardar_revision" method="POST" id="formRevision">
                        <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                        <input type="hidden" name="orden_id" value="<?= $ord_id ?>">

                        <div class="mb-3">
                            <label for="calificacion" class="form-label fw-semibold">Calificación <span class="text-danger">*</span></label>
                            <select class="form-select" id="calificacion" name="calificacion" required>
                                <option value="">Seleccionar...</option>
                                <option value="1">⭐ 1 - Muy malo</option>
                                <option value="2">⭐⭐ 2 - Malo</option>
                                <option value="3">⭐⭐⭐ 3 - Regular</option>
                                <option value="4">⭐⭐⭐⭐ 4 - Bueno</option>
                                <option value="5">⭐⭐⭐⭐⭐ 5 - Excelente</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="estado" class="form-label fw-semibold">Decisión <span class="text-danger">*</span></label>
                            <select class="form-select" id="estado" name="estado" required>
                                <option value="">Seleccionar...</option>
                                <option value="APROBADA">✅ Aprobar</option>
                                <option value="RECHAZADA">❌ Rechazar</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="observaciones" class="form-label fw-semibold">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="4"
                                      placeholder="Si rechazas, explica el motivo..."></textarea>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="cumple" name="cumple" value="1" checked>
                            <label class="form-check-label" for="cumple">
                                Cumple con los estándares de calidad
                            </label>
                        </div>

                        <hr>
                        <button type="submit" class="btn btn-primary w-100" id="btnGuardar">
                            <i class="fas fa-save me-2"></i> Guardar Revisión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Estilos -->
<style>
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color, rgba(0,0,0,0.04));
}
.costo-mini {
    padding: 10px 12px;
    background: var(--bg-card-hover, #f8f9fa);
    border: 1px solid var(--border-color, #e9ecef);
    border-radius: 10px;
    text-align: center;
}
.costo-total-mini {
    background: linear-gradient(135deg, rgba(25, 135, 84, 0.1), rgba(25, 135, 84, 0.05));
    border-color: rgba(25, 135, 84, 0.3);
}
[data-theme="dark"] .costo-mini {
    background: #1a1a2e;
    border-color: #2a2a45;
}
[data-theme="dark"] .costo-total-mini {
    background: linear-gradient(135deg, rgba(25, 135, 84, 0.15), rgba(25, 135, 84, 0.05));
}
</style>

<script>
document.getElementById('formRevision').addEventListener('submit', function(e) {
    const estado = document.getElementById('estado').value;
    const observaciones = document.getElementById('observaciones').value.trim();

    if (estado === 'RECHAZADA' && !observaciones) {
        e.preventDefault();
        alert('⚠️ Debes indicar el motivo del rechazo');
        document.getElementById('observaciones').focus();
        return false;
    }

    if (!confirm('¿Confirmas guardar esta revisión?')) {
        e.preventDefault();
        return false;
    }

    const btn = document.getElementById('btnGuardar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Guardando...';
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>