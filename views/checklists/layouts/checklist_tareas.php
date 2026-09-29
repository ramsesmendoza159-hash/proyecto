<?php
// views/checklists/layouts/checklist_tareas.php
// Layout para tareas tipo R / NR / OBS
// ✅ FIX: usa $seccion_data (NO $seccion)

$seccion_data = $seccion_data ?? null;
if (!$seccion_data) return;

$campos = $seccion_data['campos'] ?? [];
$equipos = $seccion_data['equipos'] ?? [];
$registro_id = (int)($registro['id'] ?? 0);
$puede_editar = $puede_editar ?? false;
$matriz = $matriz ?? [];

$layout_tipo = $seccion_data['layout_tipo'] ?? '';
if (!preg_match('/^[a-z_]+$/', $layout_tipo)) {
    echo '<div class="alert alert-danger">Layout inválido</div>';
    return;
}

// Buscar campo "Estado" (radio R/NR) y "OBS"
$campo_estado = null;
$campo_obs = null;
foreach ($campos as $c) {
    $n = strtolower($c['nombre'] ?? '');
    if ($n === 'estado') $campo_estado = $c;
    if ($n === 'obs')    $campo_obs = $c;
}

$sec_id = (int)($seccion_data['id'] ?? 0);
$campo_estado_id = (int)($campo_estado['id'] ?? 0);
$campo_obs_id = (int)($campo_obs['id'] ?? 0);
?>

<div class="card border-0 mb-4">
    <div class="card-header bg-transparent border-0 pt-3">
        <h5 class="mb-0 fw-semibold">
            <i class="fas fa-list-check text-primary me-2"></i>
            <?= htmlspecialchars($seccion_data['nombre'] ?? '') ?>
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="min-width:250px;">TAREA</th>
                        <th class="text-center" style="width:80px;">R</th>
                        <th class="text-center" style="width:80px;">NR</th>
                        <th>OBSERVACIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipos as $eq):
                        $id_eq = (int)($eq['id_equipo'] ?? 0);
                        $nombre_display = $eq['nombre_display'] ?? ($eq['nombre_custom'] ?? 'N/A');

                        $key_estado = $sec_id . '_' . $id_eq . '__' . $campo_estado_id . '_0';
                        $lec_estado = $matriz[$key_estado] ?? null;
                        $valor_estado = $lec_estado['valor'] ?? '';

                        $key_obs = $sec_id . '_' . $id_eq . '__' . $campo_obs_id . '_0';
                        $lec_obs = $matriz[$key_obs] ?? null;
                        $valor_obs = $lec_obs['valor'] ?? '';
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($nombre_display) ?></strong></td>
                            <td class="text-center">
                                <input type="radio"
                                       class="form-check-input input-radio-tarea"
                                       name="tarea_<?= $sec_id ?>_<?= $id_eq ?>"
                                       value="R"
                                       data-registro="<?= $registro_id ?>"
                                       data-seccion="<?= $sec_id ?>"
                                       data-equipo="<?= $id_eq ?>"
                                       data-campo="<?= $campo_estado_id ?>"
                                       <?= ($valor_estado === 'R') ? 'checked' : '' ?>
                                       <?= !$puede_editar ? 'disabled' : '' ?>>
                            </td>
                            <td class="text-center">
                                <input type="radio"
                                       class="form-check-input input-radio-tarea"
                                       name="tarea_<?= $sec_id ?>_<?= $id_eq ?>"
                                       value="NR"
                                       data-registro="<?= $registro_id ?>"
                                       data-seccion="<?= $sec_id ?>"
                                       data-equipo="<?= $id_eq ?>"
                                       data-campo="<?= $campo_estado_id ?>"
                                       <?= ($valor_estado === 'NR') ? 'checked' : '' ?>
                                       <?= !$puede_editar ? 'disabled' : '' ?>>
                            </td>
                            <td>
                                <input type="text"
                                       class="form-control form-control-sm input-obs-tarea"
                                       data-registro="<?= $registro_id ?>"
                                       data-seccion="<?= $sec_id ?>"
                                       data-equipo="<?= $id_eq ?>"
                                       data-campo="<?= $campo_obs_id ?>"
                                       value="<?= htmlspecialchars($valor_obs) ?>"
                                       placeholder="Observaciones..."
                                       <?= !$puede_editar ? 'readonly tabindex="-1"' : '' ?>>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($puede_editar): ?>
<script>
(function() {
    document.querySelectorAll('.input-radio-tarea').forEach(radio => {
        radio.addEventListener('change', function() {
            const payload = {
                registro_id: parseInt(this.dataset.registro),
                seccion_id: parseInt(this.dataset.seccion),
                id_equipo: parseInt(this.dataset.equipo),
                campo_id: parseInt(this.dataset.campo),
                valor: this.value,
                observacion: ''
            };
            guardarLectura(payload).then(data => {
                if (!data.success) console.warn(data.mensaje);
            }).catch(err => console.error(err));
        });
    });

    document.querySelectorAll('.input-obs-tarea').forEach(input => {
        let lastValue = input.value;
        input.addEventListener('focus', function() { lastValue = this.value; });
        input.addEventListener('blur', function() {
            if (this.value === lastValue) return;
            lastValue = this.value;
            const payload = {
                registro_id: parseInt(this.dataset.registro),
                seccion_id: parseInt(this.dataset.seccion),
                id_equipo: parseInt(this.dataset.equipo),
                campo_id: parseInt(this.dataset.campo),
                valor: this.value,
                observacion: ''
            };
            guardarLectura(payload).then(data => {
                if (!data.success) console.warn(data.mensaje);
            }).catch(err => console.error(err));
        });
    });
})();
</script>
<?php endif; ?>