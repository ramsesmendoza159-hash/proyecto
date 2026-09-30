<?php
// views/mantenimiento/editar_mantenimiento.php
// Formulario para editar un mantenimiento registrado - VERSIÓN ESTANDARIZADA
// ✅ FIX: NO cargar EquiposModel dentro de la vista (usar $equipo del controlador)
// ✅ FIX NUEVO: guard robusto para $equipo y $mantenimiento
// ✅ FIX NUEVO: botones de header con btn-header-action

if (!isset($seccion)) $seccion = 'mantenimiento';
if (!isset($titulo)) $titulo = 'Editar Mantenimiento';

// ✅ Guards: si el controlador no pasa los datos, no romper
$mantenimiento = $mantenimiento ?? null;
$equipo        = $equipo ?? null;
$tecnicos      = $tecnicos ?? [];
$proveedores   = $proveedores ?? [];

if (!$mantenimiento) {
    $_SESSION['error'] = 'Mantenimiento no encontrado';
    header('Location: /proyecto/mantenimiento/panel');
    exit;
}

// ✅ Si $equipo no viene del controlador, mostrar warning pero no romper
$equipo_faltante = false;
if (!$equipo) {
    $equipo_faltante = true;
    $equipo = [
        'id_equipo' => $mantenimiento['id_equipo'] ?? 0,
        'nombre_equipo' => 'Equipo no cargado (ID: ' . ($mantenimiento['id_equipo'] ?? '?') . ')',
        'codigo' => 'N/A',
    ];
}

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-edit text-warning me-2"></i>Editar Mantenimiento #<?= (int)$mantenimiento['id'] ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Modifica los datos del mantenimiento registrado
            </p>
        </div>
        <a href="/proyecto/mantenimiento/detalle-mantenimiento/<?= (int)$mantenimiento['id'] ?>" class="btn btn-secondary btn-header-action">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <?php if ($equipo_faltante): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Equipo no cargado.</strong> El controlador no pasó la variable <code>$equipo</code>.
            Verificá que <code>MantenimientoEquiposController::editarMantenimiento()</code> la esté enviando.
        </div>
    <?php endif; ?>

    <!-- Info del equipo (no editable) -->
    <div class="alert alert-info">
        <div class="d-flex align-items-center gap-3">
            <i class="fas fa-industry fa-2x"></i>
            <div>
                <strong>Equipo:</strong> <?= htmlspecialchars($equipo['nombre_equipo'] ?? 'N/A') ?>
                <br>
                <small>El equipo y el plan de mantenimiento no se pueden cambiar</small>
            </div>
        </div>
    </div>

    <form method="POST" action="/proyecto/mantenimiento/actualizar-mantenimiento/<?= (int)$mantenimiento['id'] ?>" id="formEditar">
        <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0 fw-semibold text-primary">
                            <i class="fas fa-clipboard-list me-2"></i>Datos del Mantenimiento
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Descripción <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="descripcion" rows="3" required><?= htmlspecialchars($mantenimiento['descripcion'] ?? '') ?></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Horómetro (horas) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="horas_equipo" 
                                       value="<?= (int)($mantenimiento['horas_equipo'] ?? 0) ?>" 
                                       min="0" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Fecha del Mantenimiento <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" name="fecha_mantenimiento" 
                                       value="<?= date('Y-m-d\TH:i', strtotime($mantenimiento['fecha_mantenimiento'])) ?>" required>
                                <small class="text-muted">Si cambias a una fecha anterior, se marcará como retroactivo</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Técnico Ejecutor</label>
                                <select class="form-select" name="tecnico_id">
                                    <option value="">Sin asignar</option>
                                    <?php foreach ($tecnicos as $tec): ?>
                                        <option value="<?= (int)$tec['id'] ?>" 
                                            <?= ($mantenimiento['tecnico_id'] ?? '') == $tec['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tec['nombre']) ?> 
                                            (<?= htmlspecialchars($tec['especialidad'] ?? 'General') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Proveedor Externo</label>
                                <select class="form-select" name="proveedor_id">
                                    <option value="">Sin proveedor</option>
                                    <?php foreach ($proveedores as $prov): ?>
                                        <option value="<?= (int)$prov['id'] ?>" 
                                            <?= ($mantenimiento['proveedor_id'] ?? '') == $prov['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($prov['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Costo Total (S/)</label>
                                <input type="number" step="0.01" class="form-control" name="costo" 
                                       value="<?= number_format($mantenimiento['costo'] ?? 0, 2, '.', '') ?>" min="0">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Repuestos Usados</label>
                                <input type="text" class="form-control" name="repuestos_usados" 
                                       value="<?= htmlspecialchars($mantenimiento['repuestos_usados'] ?? '') ?>"
                                       placeholder="Ej: Filtro, aceite 5W-30...">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Observaciones</label>
                                <textarea class="form-control" name="observaciones" rows="3"><?= htmlspecialchars($mantenimiento['observaciones'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Motivo de edición (obligatorio) -->
                <div class="card border-0 mb-4 border-warning">
                    <div class="card-header bg-warning bg-opacity-10 border-0 pt-3">
                        <h5 class="mb-0 fw-semibold text-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>Motivo de la Edición
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Obligatorio para auditoría. Explica por qué estás editando este mantenimiento.</p>
                        <textarea class="form-control" name="motivo_edicion" rows="4" required
                                  placeholder="Ej: Se ingresó mal el costo, el correcto es S/ 100..."></textarea>
                    </div>
                </div>

                <!-- Advertencia -->
                <div class="card border-0 mb-4">
                    <div class="card-body">
                        <div class="alert alert-danger mb-0 py-2">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <small>Esta edición quedará registrada en la auditoría del sistema.</small>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="card border-0">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning btn-lg" id="btnGuardar">
                                <i class="fas fa-save me-2"></i> Guardar Cambios
                            </button>
                            <a href="/proyecto/mantenimiento/detalle-mantenimiento/<?= (int)$mantenimiento['id'] ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i> Cancelar
                            </a>
                            <?php if (($_SESSION['rol'] ?? '') === 'admin'): ?>
                                <hr>
                                <button type="button" class="btn btn-outline-danger" 
                                        onclick="confirmarEliminar()">
                                    <i class="fas fa-trash me-2"></i> Eliminar Mantenimiento
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/proyecto/mantenimiento/eliminar-mantenimiento/<?= (int)$mantenimiento['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Eliminar Mantenimiento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de eliminar el mantenimiento <strong>#<?= (int)$mantenimiento['id'] ?></strong>?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Esta acción no se puede deshacer.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar Definitivamente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmarEliminar() {
    new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}

document.getElementById('formEditar').addEventListener('submit', function(e) {
    const motivo = document.querySelector('[name="motivo_edicion"]').value.trim();
    if (!motivo) {
        e.preventDefault();
        alert('⚠️ Debes indicar el motivo de la edición');
        document.querySelector('[name="motivo_edicion"]').focus();
        return;
    }

    const btn = document.getElementById('btnGuardar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Guardando...';
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>