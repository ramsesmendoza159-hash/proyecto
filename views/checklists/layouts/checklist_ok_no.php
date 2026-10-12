<?php
// views/checklists/layouts/checklist_ok_no.php
// Layout: items con opciones OK / NO + observaciones
// ✅ NUEVO para MTTO R008 - Inspección de Túneles YantaImoon
// ✅ FIX: usa $seccion_data (NO $seccion)
// ✅ FIX: evita duplicar nombre de equipo si es igual al de la sección
// ✅ FIX: radios OK=verde, NO=rojo

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

$sec_id = (int)($seccion_data['id'] ?? 0);
$id_eq = !empty($equipos[0]['id_equipo']) ? (int)$equipos[0]['id_equipo'] : 0;
$nombre_eq = $equipos[0]['nombre_display']
    ?? ($equipos[0]['nombre_custom']
    ?? ($equipos[0]['nombre_equipo'] ?? 'Equipo'));
$nombre_sec = $seccion_data['nombre'] ?? '';

// Solo mostrar el nombre del equipo si es distinto al de la sección
$mostrar_nombre_eq = (!empty($nombre_eq)
    && $nombre_eq !== 'Equipo'
    && strcasecmp(trim($nombre_eq), trim($nombre_sec)) !== 0);
?>

<div class="card border-0 mb-4">
    <div class="card-header bg-transparent border-0 pt-3">
        <h5 class="mb-0 fw-semibold">
            <i class="fas fa-check-square text-primary me-2"></i>
            <?= htmlspecialchars($nombre_sec) ?>
        </h5>
        <?php if ($mostrar_nombre_eq): ?>
            <small class="text-muted d-block mt-1">
                <i class="fas fa-industry me-1"></i><?= htmlspecialchars($nombre_eq) ?>
            </small>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 grilla-okno">
                <thead class="table-light">
                    <tr>
                        <th style="min-width:280px;">TAREA</th>
                        <th class="text-center" style="width:80px;">OK</th>
                        <th class="text-center" style="width:80px;">NO</th>
                        <th>OBSERVACIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campos as $campo):
                        $campo_id = (int)($campo['id'] ?? 0);
                        $key = $sec_id . '_' . $id_eq . '__' . $campo_id . '_0';
                        $lec = $matriz[$key] ?? null;
                        $valor = $lec['valor'] ?? '';
                        $obs = $lec['observacion'] ?? '';
                        $es_no = ($valor === 'NO');
                    ?>
                        <tr class="<?= $es_no ? 'fila-no' : '' ?>">
                            <td>
                                <strong><?= htmlspecialchars($campo['nombre'] ?? '') ?></strong>
                            </td>
                            <td class="text-center">
                                <input type="radio"
                                       class="form-check-input input-radio-okno radio-ok"
                                       name="okno_<?= $sec_id ?>_<?= $campo_id ?>"
                                       value="OK"
                                       data-registro="<?= $registro_id ?>"
                                       data-seccion="<?= $sec_id ?>"
                                       data-equipo="<?= $id_eq ?>"
                                       data-campo="<?= $campo_id ?>"
                                       <?= ($valor === 'OK') ? 'checked' : '' ?>
                                       <?= !$puede_editar ? 'disabled' : '' ?>>
                            </td>
                            <td class="text-center">
                                <input type="radio"
                                       class="form-check-input input-radio-okno radio-no"
                                       name="okno_<?= $sec_id ?>_<?= $campo_id ?>"
                                       value="NO"
                                       data-registro="<?= $registro_id ?>"
                                       data-seccion="<?= $sec_id ?>"
                                       data-equipo="<?= $id_eq ?>"
                                       data-campo="<?= $campo_id ?>"
                                       <?= ($valor === 'NO') ? 'checked' : '' ?>
                                       <?= !$puede_editar ? 'disabled' : '' ?>>
                            </td>
                            <td>
                                <input type="text"
                                       class="form-control form-control-sm input-obs-okno"
                                       data-registro="<?= $registro_id ?>"
                                       data-seccion="<?= $sec_id ?>"
                                       data-equipo="<?= $id_eq ?>"
                                       data-campo="<?= $campo_id ?>"
                                       value="<?= htmlspecialchars($obs) ?>"
                                       placeholder="Observaciones..."
                                       maxlength="500"
                                       <?= !$puede_editar ? 'readonly tabindex="-1"' : '' ?>>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.grilla-okno th { font-size: 0.7rem; text-transform: uppercase; padding: 8px 4px; }
.grilla-okno td { padding: 6px 8px; vertical-align: middle; }
.grilla-okno tr.fila-no { background: rgba(220, 53, 69, 0.08); }
.grilla-okno input.form-check-input {
    width: 20px;
    height: 20px;
    cursor: pointer;
    margin: 0 auto;
    display: block;
}

/* OK en verde */
.grilla-okno input.radio-ok:checked {
    background-color: #198754 !important;
    border-color: #198754 !important;
    box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.15) !important;
}

/* NO en rojo */
.grilla-okno input.radio-no:checked {
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15) !important;
}

.grilla-okno input.form-check-input:disabled { cursor: not-allowed; }

.grilla-okno input.guardado-ok {
    border-color: #198754 !important;
    box-shadow: 0 0 0 2px rgba(25, 135, 84, 0.2) !important;
}
.grilla-okno input.guardado-error {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.2) !important;
}

/* Modo oscuro */
[data-theme="dark"] .grilla-okno tr.fila-no {
    background: rgba(239, 68, 68, 0.15) !important;
}
[data-theme="dark"] .grilla-okno input.radio-ok:checked {
    background-color: #4ade80 !important;
    border-color: #4ade80 !important;
}
[data-theme="dark"] .grilla-okno input.radio-no:checked {
    background-color: #f87171 !important;
    border-color: #f87171 !important;
}
</style>

<?php if ($puede_editar): ?>
<script>
(function() {
    document.querySelectorAll('.input-radio-okno').forEach(radio => {
        radio.addEventListener('change', function() {
            const campoId = this.dataset.campo;
            const secId = this.dataset.seccion;
            const eqId = this.dataset.equipo;

            const obsInput = document.querySelector(
                `.input-obs-okno[data-campo="${campoId}"][data-seccion="${secId}"][data-equipo="${eqId}"]`
            );

            const payload = {
                registro_id: parseInt(this.dataset.registro),
                seccion_id: parseInt(secId),
                id_equipo: parseInt(eqId),
                campo_id: parseInt(campoId),
                valor: this.value,
                observacion: obsInput ? obsInput.value : ''
            };

            const tr = this.closest('tr');
            if (this.value === 'NO') {
                tr.classList.add('fila-no');
            } else {
                tr.classList.remove('fila-no');
            }

            const self = this;
            guardarLectura(payload).then(data => {
                if (!data.success) {
                    console.warn(data.mensaje);
                    self.classList.add('guardado-error');
                    setTimeout(() => self.classList.remove('guardado-error'), 1500);
                } else {
                    self.classList.add('guardado-ok');
                    setTimeout(() => self.classList.remove('guardado-ok'), 1000);
                }
            }).catch(err => {
                console.error(err);
                self.classList.add('guardado-error');
                setTimeout(() => self.classList.remove('guardado-error'), 1500);
            });
        });
    });

    document.querySelectorAll('.input-obs-okno').forEach(input => {
        let lastValue = input.value;
        input.addEventListener('focus', function() { lastValue = this.value; });
        input.addEventListener('blur', function() {
            if (this.value === lastValue) return;
            lastValue = this.value;

            const campoId = this.dataset.campo;
            const secId = this.dataset.seccion;
            const eqId = this.dataset.equipo;

            const radioChecked = document.querySelector(
                `.input-radio-okno[data-campo="${campoId}"][data-seccion="${secId}"][data-equipo="${eqId}"]:checked`
            );

            const payload = {
                registro_id: parseInt(this.dataset.registro),
                seccion_id: parseInt(secId),
                id_equipo: parseInt(eqId),
                campo_id: parseInt(campoId),
                valor: radioChecked ? radioChecked.value : '',
                observacion: this.value
            };

            const self = this;
            guardarLectura(payload).then(data => {
                if (!data.success) {
                    console.warn(data.mensaje);
                    self.classList.add('guardado-error');
                    setTimeout(() => self.classList.remove('guardado-error'), 1500);
                } else {
                    self.classList.add('guardado-ok');
                    setTimeout(() => self.classList.remove('guardado-ok'), 1000);
                }
            }).catch(err => {
                console.error(err);
                self.classList.add('guardado-error');
                setTimeout(() => self.classList.remove('guardado-error'), 1500);
            });
        });
    });
})();
</script>
<?php endif; ?>