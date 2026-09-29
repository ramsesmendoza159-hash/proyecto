<?php
// views/monitoreo/ruta_form.php
// Formulario para crear/editar una ruta de monitoreo
// ✅ FIX: Coerción de $ruta a array para evitar warnings del linter
// ✅ FIX: $ruta_id separado, sin acceder a $ruta['id'] en zonas inseguras
// ✅ FIX: $es_edicion basado en !empty($ruta)

if (!isset($seccion)) $seccion = 'monitoreo';

$ruta = $ruta ?? [];
if (!is_array($ruta)) {
    $ruta = [];
}

$equipos_disponibles = $equipos_disponibles ?? [];
$todos_horarios      = $todos_horarios ?? [];

$es_edicion = !empty($ruta['id']);
$ruta_id    = $es_edicion ? (int)$ruta['id'] : 0;

$accion_url = $es_edicion
    ? '/proyecto/monitoreo/actualizar-ruta/' . $ruta_id
    : '/proyecto/monitoreo/guardar-ruta';

$titulo = $es_edicion ? 'Editar Ruta' : 'Nueva Ruta';

$equipos_seleccionados   = $es_edicion ? array_column($ruta['equipos']   ?? [], 'id_equipo') : [];
$horarios_seleccionados  = $es_edicion ? array_column($ruta['horarios']  ?? [], 'hora')       : [];

$es_admin = (($_SESSION['rol'] ?? '') === 'admin');

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-<?= $es_edicion ? 'edit' : 'plus-circle' ?> text-primary me-2"></i>
                <?= $es_edicion ? 'Editar Ruta' : 'Nueva Ruta de Monitoreo' ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i>Configurá la ruta, sus equipos y sus horarios
            </p>
        </div>
        <a href="/proyecto/monitoreo" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form method="POST" action="<?= htmlspecialchars($accion_url, ENT_QUOTES, 'UTF-8') ?>" id="formRuta">
        <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">

        <div class="row g-4">
            <div class="col-lg-8">

                <!-- Info básica -->
                <div class="card border-0 mb-4">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-info-circle text-primary me-2"></i>Información Básica
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Código <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="codigo" required
                                       value="<?= htmlspecialchars($ruta['codigo'] ?? '') ?>"
                                       placeholder="Ej: MTTO R007"
                                       <?= $es_edicion ? 'readonly' : '' ?>>
                                <?php if ($es_edicion): ?>
                                    <small class="text-muted">El código no se puede cambiar</small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold small">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="nombre" required
                                       value="<?= htmlspecialchars($ruta['nombre'] ?? '') ?>"
                                       placeholder="Ej: Monitoreo de Nivel de Aceite">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small">Descripción</label>
                                <textarea class="form-control form-control-sm" name="descripcion" rows="2"><?= htmlspecialchars($ruta['descripcion'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Tipo de valor</label>
                                <select name="tipo_valor" class="form-select form-select-sm">
                                    <?php foreach (['porcentaje' => 'Porcentaje', 'numero' => 'Número', 'texto' => 'Texto', 'booleano' => 'Sí/No'] as $k => $v): ?>
                                        <option value="<?= $k ?>" <?= ($ruta['tipo_valor'] ?? 'porcentaje') === $k ? 'selected' : '' ?>>
                                            <?= $v ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Unidad de medida</label>
                                <input type="text" class="form-control form-control-sm" name="unidad_medida"
                                       value="<?= htmlspecialchars($ruta['unidad_medida'] ?? '%') ?>"
                                       placeholder="%, psi, °C">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Frecuencia (horas)</label>
                                <input type="number" class="form-control form-control-sm" name="frecuencia_horas" min="1" max="24"
                                       value="<?= (int)($ruta['frecuencia_horas'] ?? 2) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Activo</label>
                                <select name="activo" class="form-select form-select-sm">
                                    <option value="1" <?= (int)($ruta['activo'] ?? 1) === 1 ? 'selected' : '' ?>>Sí</option>
                                    <option value="0" <?= (int)($ruta['activo'] ?? 1) === 0 ? 'selected' : '' ?>>No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Límites globales -->
                <div class="card border-0 mb-4">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>Límites Globales (por defecto)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Valor mínimo</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" name="valor_min_global"
                                       value="<?= htmlspecialchars($ruta['valor_min_global'] ?? '') ?>"
                                       placeholder="Ej: 50">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Valor máximo</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" name="valor_max_global"
                                       value="<?= htmlspecialchars($ruta['valor_max_global'] ?? '') ?>"
                                       placeholder="Ej: 100">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Objetivo de relleno</label>
                                <input type="number" step="0.01" class="form-control form-control-sm" name="valor_objetivo_relleno"
                                       value="<?= htmlspecialchars($ruta['valor_objetivo_relleno'] ?? '') ?>"
                                       placeholder="Ej: 85">
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2">
                            Los equipos sin override usarán estos límites.
                        </small>
                    </div>
                </div>

                <!-- Equipos -->
                <div class="card border-0 mb-4">
                    <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-industry text-primary me-2"></i>Equipos de la ruta
                        </h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="marcarTodosEquipos(true)">Marcar todos</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="marcarTodosEquipos(false)">Desmarcar</button>
                        </div>
                    </div>
                    <div class="card-body" style="max-height:350px;overflow-y:auto;">
                        <?php if (empty($equipos_disponibles)): ?>
                            <p class="text-muted mb-0">No hay equipos activos.</p>
                        <?php else: ?>
                            <div class="row g-2">
                                <?php foreach ($equipos_disponibles as $eq): ?>
                                    <div class="col-md-6">
                                        <div class="form-check p-2 rounded border">
                                            <input class="form-check-input input-equipo" type="checkbox"
                                                   name="equipos[]" value="<?= (int)$eq['id_equipo'] ?>"
                                                   id="eq_<?= (int)$eq['id_equipo'] ?>"
                                                   <?= in_array((int)$eq['id_equipo'], $equipos_seleccionados, true) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="eq_<?= (int)$eq['id_equipo'] ?>">
                                                <strong><?= htmlspecialchars($eq['nombre_equipo'] ?? '') ?></strong>
                                                <br><small class="text-muted"><?= htmlspecialchars($eq['codigo'] ?? '') ?></small>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Horarios -->
                <div class="card border-0">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-clock text-primary me-2"></i>Horarios
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <?php foreach ($todos_horarios as $h): ?>
                                <?php $hora_id = str_replace(':', '', $h['hora'] ?? ''); ?>
                                <div class="col-md-3 col-6">
                                    <div class="form-check p-2 rounded border">
                                        <input class="form-check-input" type="checkbox"
                                               name="horarios[]" value="<?= htmlspecialchars($h['hora'] ?? '') ?>"
                                               id="h_<?= htmlspecialchars($hora_id) ?>"
                                               <?= (in_array($h['hora'] ?? '', $horarios_seleccionados, true) || !$es_edicion) ? 'checked' : '' ?>>
                                        <label class="form-check-label small" for="h_<?= htmlspecialchars($hora_id) ?>">
                                            <strong><?= htmlspecialchars($h['label'] ?? '') ?></strong>
                                            <br><span class="badge bg-dark" style="font-size:0.6rem;"><?= htmlspecialchars($h['turno'] ?? '') ?></span>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Columna lateral: acciones -->
            <div class="col-lg-4">
                <div class="card border-0 mb-4">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-cogs text-primary me-2"></i>Acciones
                        </h5>
                    </div>
                    <div class="card-body d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> <?= $es_edicion ? 'Actualizar' : 'Crear Ruta' ?>
                        </button>
                        <a href="/proyecto/monitoreo" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </a>
                        <?php if ($es_edicion && $es_admin): ?>
                            <hr>
                            <button type="button" class="btn btn-outline-danger"
                                    onclick="eliminarRuta(<?= $ruta_id ?>)">
                                <i class="fas fa-trash me-1"></i> Eliminar ruta
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card border-0">
                    <div class="card-body">
                        <h6 class="fw-semibold">
                            <i class="fas fa-lightbulb text-warning me-1"></i> Ayuda
                        </h6>
                        <ul class="small text-muted mb-0 ps-3">
                            <li>Los <strong>límites globales</strong> aplican a todos los equipos que no tengan override.</li>
                            <li>Los <strong>horarios</strong> se agrupan por turno automáticamente.</li>
                            <li>Cada turno firma su propio registro.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Form oculto para eliminar -->
<form id="formEliminarRuta" method="POST" action="" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
</form>

<script>
function marcarTodosEquipos(valor) {
    document.querySelectorAll('.input-equipo').forEach(function(cb) { cb.checked = valor; });
}

function eliminarRuta(id) {
    if (!confirm('¿Estás seguro de eliminar esta ruta?\n\nEsta acción no se puede deshacer.')) return;
    var form = document.getElementById('formEliminarRuta');
    form.action = '/proyecto/monitoreo/eliminar-ruta/' + id;
    form.submit();
}

document.getElementById('formRuta').addEventListener('submit', function(e) {
    var equipos = document.querySelectorAll('.input-equipo:checked');
    if (equipos.length === 0) {
        e.preventDefault();
        alert('⚠️ Debés seleccionar al menos un equipo.');
        return false;
    }
    var horarios = document.querySelectorAll('input[name="horarios[]"]:checked');
    if (horarios.length === 0) {
        e.preventDefault();
        alert('⚠️ Debés seleccionar al menos un horario.');
        return false;
    }
});
</script>

<style>
.card { border-radius: 16px; box-shadow: 0 2px 12px var(--shadow-color); }
.form-check { transition: background 0.2s ease; }
.form-check:hover { background: var(--bg-card-hover); }
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>