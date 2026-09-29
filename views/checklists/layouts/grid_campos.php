<?php
// views/checklists/layouts/grid_campos.php
// Layout para grillas de equipos × campos
// ✅ FIX: usa $seccion_data (NO $seccion)
// ✅ FIX: valida $matriz

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
?>

<div class="card border-0 mb-4">
    <div class="card-header bg-transparent border-0 pt-3">
        <h5 class="mb-0 fw-semibold">
            <i class="fas fa-table text-primary me-2"></i>
            <?= htmlspecialchars($seccion_data['nombre'] ?? '') ?>
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0 grilla-check">
                <thead class="table-light">
                    <tr>
                        <th style="min-width:180px;position:sticky;left:0;background:var(--bg-table-header);z-index:2;">
                            EQUIPO
                        </th>
                        <?php foreach ($campos as $c): ?>
                            <th class="text-center" style="min-width:90px;">
                                <?= htmlspecialchars($c['nombre'] ?? '') ?>
                                <?php if (!empty($c['unidad'])): ?>
                                    <br><small class="text-muted">(<?= htmlspecialchars($c['unidad']) ?>)</small>
                                <?php endif; ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipos as $eq): ?>
                        <?php
                            $id_eq = (int)($eq['id_equipo'] ?? 0);
                            $nombre_display = $eq['nombre_display'] ?? ($eq['nombre_custom'] ?? 'N/A');
                        ?>
                        <tr>
                            <td style="position:sticky;left:0;background:var(--bg-card);z-index:1;">
                                <strong><?= htmlspecialchars($nombre_display) ?></strong>
                                <?php if (!empty($eq['equipo_codigo'])): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($eq['equipo_codigo']) ?></small>
                                <?php endif; ?>
                            </td>
                            <?php foreach ($campos as $c): ?>
                                <?php
                                    $campo_id = (int)($c['id'] ?? 0);
                                    $key = (int)($seccion_data['id'] ?? 0) . '_' . $id_eq . '__' . $campo_id . '_0';
                                    $lec = $matriz[$key] ?? null;
                                    $valor = $lec['valor'] ?? '';
                                    $obs = $lec['observacion'] ?? '';
                                    $nivel = $lec['nivel_alerta'] ?? 'OK';
                                    $clase = '';
                                    if ($nivel === 'BAJO') $clase = 'celda-bajo';
                                    elseif ($nivel === 'CRITICO') $clase = 'celda-critico';
                                    elseif ($nivel === 'ALTO') $clase = 'celda-alto';
                                ?>
                                <td class="text-center p-1 <?= $clase ?>">
                                    <?php if (($c['tipo'] ?? '') === 'booleano'): ?>
                                        <input type="checkbox"
                                               class="form-check-input input-lectura <?= !$puede_editar ? 'input-readonly' : '' ?>"
                                               data-registro="<?= $registro_id ?>"
                                               data-seccion="<?= (int)($seccion_data['id'] ?? 0) ?>"
                                               data-equipo="<?= $id_eq ?>"
                                               data-campo="<?= $campo_id ?>"
                                               data-nombre="<?= htmlspecialchars($c['nombre'] ?? '') ?>"
                                               value="1"
                                               <?= ($valor == '1') ? 'checked' : '' ?>
                                               <?= !$puede_editar ? 'disabled' : '' ?>>
                                    <?php elseif (($c['tipo'] ?? '') === 'numero'): ?>
                                        <input type="number"
                                               class="form-control form-control-sm input-lectura text-center <?= !$puede_editar ? 'input-readonly' : '' ?>"
                                               data-registro="<?= $registro_id ?>"
                                               data-seccion="<?= (int)($seccion_data['id'] ?? 0) ?>"
                                               data-equipo="<?= $id_eq ?>"
                                               data-campo="<?= $campo_id ?>"
                                               data-nombre="<?= htmlspecialchars($c['nombre'] ?? '') ?>"
                                               value="<?= htmlspecialchars($valor) ?>"
                                               step="0.01"
                                               <?= !$puede_editar ? 'readonly tabindex="-1"' : '' ?>
                                               placeholder="--">
                                    <?php else: ?>
                                        <input type="text"
                                               class="form-control form-control-sm input-lectura text-center <?= !$puede_editar ? 'input-readonly' : '' ?>"
                                               data-registro="<?= $registro_id ?>"
                                               data-seccion="<?= (int)($seccion_data['id'] ?? 0) ?>"
                                               data-equipo="<?= $id_eq ?>"
                                               data-campo="<?= $campo_id ?>"
                                               data-nombre="<?= htmlspecialchars($c['nombre'] ?? '') ?>"
                                               value="<?= htmlspecialchars($valor) ?>"
                                               <?= !$puede_editar ? 'readonly tabindex="-1"' : '' ?>
                                               placeholder="--">
                                    <?php endif; ?>
                                    <?php if (!empty($obs)): ?>
                                        <small class="text-muted d-block" title="<?= htmlspecialchars($obs) ?>">
                                            <i class="fas fa-comment"></i>
                                        </small>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.grilla-check th { font-size: 0.7rem; text-transform: uppercase; padding: 8px 4px; }
.grilla-check td { padding: 4px; }
.grilla-check td.celda-bajo { background: rgba(255, 193, 7, 0.15); }
.grilla-check td.celda-critico { background: rgba(220, 53, 69, 0.15); }
.grilla-check td.celda-alto { background: rgba(13, 202, 240, 0.15); }
.grilla-check input.form-check-input { margin: 0 auto; display: block; }
.grilla-check input.guardado-ok {
    border-color: #198754 !important;
    box-shadow: 0 0 0 2px rgba(25, 135, 84, 0.2) !important;
}
.grilla-check input.guardado-error {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.2) !important;
}
[data-theme="dark"] .grilla-check td.celda-bajo { background: rgba(234, 179, 8, 0.18) !important; }
[data-theme="dark"] .grilla-check td.celda-critico { background: rgba(239, 68, 68, 0.18) !important; }
[data-theme="dark"] .grilla-check td.celda-alto { background: rgba(6, 182, 212, 0.18) !important; }
</style>

<?php if ($puede_editar): ?>
<script>
(function() {
    const inputs = document.querySelectorAll('.grilla-check .input-lectura');
    inputs.forEach(input => {
        let lastValue = input.type === 'checkbox' ? (input.checked ? '1' : '') : input.value;

        input.addEventListener('focus', function() {
            lastValue = this.type === 'checkbox' ? (this.checked ? '1' : '') : this.value;
        });

        input.addEventListener('change', function() {
            const valor = this.type === 'checkbox' ? (this.checked ? '1' : '') : this.value;
            if (valor === lastValue) return;
            lastValue = valor;

            const payload = {
                registro_id: parseInt(this.dataset.registro),
                seccion_id: parseInt(this.dataset.seccion),
                id_equipo: parseInt(this.dataset.equipo),
                campo_id: parseInt(this.dataset.campo),
                valor: valor,
                observacion: ''
            };

            const td = this.closest('td');
            const self = this;

            guardarLectura(payload).then(data => {
                if (!data.success) {
                    console.warn(data.mensaje);
                    self.classList.add('guardado-error');
                    setTimeout(() => self.classList.remove('guardado-error'), 1500);
                    return;
                }
                self.classList.add('guardado-ok');
                setTimeout(() => self.classList.remove('guardado-ok'), 1000);

                td.classList.remove('celda-bajo', 'celda-critico', 'celda-alto');
                if (data.nivel_alerta === 'BAJO') td.classList.add('celda-bajo');
                if (data.nivel_alerta === 'CRITICO') td.classList.add('celda-critico');
                if (data.nivel_alerta === 'ALTO') td.classList.add('celda-alto');
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