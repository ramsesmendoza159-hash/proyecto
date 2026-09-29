<?php
// views/checklists/layouts/grid_horarios.php
// Layout para grillas de equipos × horarios (Sección 1 de R007)
// ✅ FIX: eliminada línea "grid_horarios.php" antes de <?php
// ✅ FIX: usa $seccion_data (NO $seccion)
// ✅ FIX: valida $matriz

$seccion_data = $seccion_data ?? null;
if (!$seccion_data) return;

$campos = $seccion_data['campos'] ?? [];
$equipos = $seccion_data['equipos'] ?? [];
$registro_id = (int)($registro['id'] ?? 0);
$puede_editar = $puede_editar ?? false;
$turno_reg = $registro['turno'] ?? 'DIA';
$matriz = $matriz ?? [];

$layout_tipo = $seccion_data['layout_tipo'] ?? '';
if (!preg_match('/^[a-z_]+$/', $layout_tipo)) {
    echo '<div class="alert alert-danger">Layout inválido</div>';
    return;
}

$sec_id = (int)($seccion_data['id'] ?? 0);

// Obtener horarios del registro o usar los 12 estándar
$horarios = [];
if (!empty($registro['horarios']) && is_array($registro['horarios'])) {
    $horarios = $registro['horarios'];
}

if (empty($horarios)) {
    $horarios = [
        ['hora' => '08:00:00', 'turno' => 'DIA'],
        ['hora' => '10:00:00', 'turno' => 'DIA'],
        ['hora' => '12:00:00', 'turno' => 'DIA'],
        ['hora' => '14:00:00', 'turno' => 'DIA'],
        ['hora' => '16:00:00', 'turno' => 'TARDE'],
        ['hora' => '18:00:00', 'turno' => 'TARDE'],
        ['hora' => '20:00:00', 'turno' => 'TARDE'],
        ['hora' => '22:00:00', 'turno' => 'TARDE'],
        ['hora' => '00:00:00', 'turno' => 'NOCHE'],
        ['hora' => '02:00:00', 'turno' => 'NOCHE'],
        ['hora' => '04:00:00', 'turno' => 'NOCHE'],
        ['hora' => '06:00:00', 'turno' => 'NOCHE'],
    ];
}

$campo_id = (int)($campos[0]['id'] ?? 0);
$unidad = $campos[0]['unidad'] ?? '%';
?>

<div class="card border-0 mb-4">
    <div class="card-header bg-transparent border-0 pt-3">
        <h5 class="mb-0 fw-semibold">
            <i class="fas fa-clock text-primary me-2"></i>
            <?= htmlspecialchars($seccion_data['nombre'] ?? '') ?>
            <span class="badge bg-dark ms-2"><?= htmlspecialchars($turno_reg) ?></span>
        </h5>
        <small class="text-muted">Solo las horas del turno actual son editables.</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0 grilla-monitoreo">
                <thead class="table-light">
                    <tr>
                        <th style="min-width:200px;position:sticky;left:0;background:var(--bg-table-header);z-index:2;">
                            EQUIPO
                        </th>
                        <?php foreach ($horarios as $h):
                            $es_turno_actual = (($h['turno'] ?? '') === $turno_reg);
                        ?>
                            <th class="text-center <?= $es_turno_actual ? 'col-turno-actual' : '' ?>" style="min-width:85px;">
                                <?= htmlspecialchars(substr($h['hora'] ?? '', 0, 5)) ?>
                                <br>
                                <small class="text-muted" style="font-size:0.65rem;">
                                    <?= htmlspecialchars($h['turno'] ?? '') ?>
                                </small>
                                <?php if ($es_turno_actual): ?>
                                    <br><span class="badge bg-primary" style="font-size:0.55rem;">TU TURNO</span>
                                <?php endif; ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipos as $eq):
                        $id_eq = (int)($eq['id_equipo'] ?? 0);
                        $nombre_display = $eq['nombre_display'] ?? ($eq['nombre_equipo'] ?? 'N/A');
                    ?>
                        <tr>
                            <td style="position:sticky;left:0;background:var(--bg-card);z-index:1;">
                                <strong><?= htmlspecialchars($nombre_display) ?></strong>
                                <?php if (!empty($eq['equipo_codigo'])): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($eq['equipo_codigo']) ?></small>
                                <?php endif; ?>
                            </td>
                            <?php foreach ($horarios as $h):
                                $hora = $h['hora'] ?? '';
                                $es_turno_actual = (($h['turno'] ?? '') === $turno_reg);

                                $key = $sec_id . '_' . $id_eq . '_' . $hora . '_' . $campo_id . '_0';
                                $lec = $matriz[$key] ?? null;
                                $valor = $lec['valor'] ?? '';
                                $obs = $lec['observacion'] ?? '';
                                $nivel = $lec['nivel_alerta'] ?? 'OK';
                                $clase = '';
                                if ($nivel === 'BAJO') $clase = 'celda-bajo';
                                elseif ($nivel === 'CRITICO') $clase = 'celda-critico';
                                elseif ($nivel === 'ALTO') $clase = 'celda-alto';

                                $readonly = (!$es_turno_actual || !$puede_editar);
                            ?>
                                <td class="text-center p-1 <?= $clase ?>">
                                    <input type="number"
                                           class="form-control form-control-sm input-lectura-horario text-center <?= $readonly ? 'input-readonly' : '' ?>"
                                           data-registro="<?= $registro_id ?>"
                                           data-seccion="<?= $sec_id ?>"
                                           data-equipo="<?= $id_eq ?>"
                                           data-hora="<?= htmlspecialchars($hora) ?>"
                                           data-campo="<?= $campo_id ?>"
                                           value="<?= htmlspecialchars($valor) ?>"
                                           step="0.01"
                                           min="0"
                                           max="999"
                                           placeholder="--"
                                           <?= $readonly ? 'readonly tabindex="-1"' : '' ?>>
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
.grilla-monitoreo th { font-size: 0.7rem; text-transform: uppercase; padding: 8px 4px; }
.grilla-monitoreo td { padding: 4px; }
.grilla-monitoreo td.celda-bajo { background: rgba(255, 193, 7, 0.15); }
.grilla-monitoreo td.celda-critico { background: rgba(220, 53, 69, 0.15); }
.grilla-monitoreo td.celda-alto { background: rgba(13, 202, 240, 0.15); }
.grilla-monitoreo th.col-turno-actual {
    background: rgba(13,110,253,0.08);
    border-bottom: 2px solid #0d6efd;
}
.grilla-monitoreo input.input-readonly {
    background: var(--bg-input-disabled);
    color: var(--text-muted);
    cursor: not-allowed;
    opacity: 0.7;
}
.grilla-monitoreo input.guardado-ok {
    border-color: #198754 !important;
    box-shadow: 0 0 0 2px rgba(25, 135, 84, 0.2) !important;
}
.grilla-monitoreo input.guardado-error {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.2) !important;
}
[data-theme="dark"] .grilla-monitoreo td.celda-bajo { background: rgba(234, 179, 8, 0.18) !important; }
[data-theme="dark"] .grilla-monitoreo td.celda-critico { background: rgba(239, 68, 68, 0.18) !important; }
[data-theme="dark"] .grilla-monitoreo td.celda-alto { background: rgba(6, 182, 212, 0.18) !important; }
[data-theme="dark"] .grilla-monitoreo th.col-turno-actual {
    background: rgba(59, 130, 246, 0.15) !important;
    border-bottom: 2px solid #3b82f6 !important;
}
</style>

<?php if ($puede_editar): ?>
<script>
(function() {
    const inputs = document.querySelectorAll('.grilla-monitoreo .input-lectura-horario:not([readonly])');
    inputs.forEach(input => {
        let lastValue = input.value;
        input.addEventListener('focus', function() { lastValue = this.value; });
        input.addEventListener('blur', function() {
            if (this.value === lastValue) return;
            lastValue = this.value;

            const payload = {
                registro_id: parseInt(this.dataset.registro),
                seccion_id: parseInt(this.dataset.seccion),
                id_equipo: parseInt(this.dataset.equipo),
                hora: this.dataset.hora,
                campo_id: parseInt(this.dataset.campo),
                valor: this.value,
                observacion: ''
            };

            const td = this.closest('td');
            const self = this;

            guardarLectura(payload).then(data => {
                if (!data.success) {
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
            }).catch(err => console.error(err));
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur();
            }
        });
    });
})();
</script>
<?php endif; ?>