<?php
// views/checklists/layouts/batch_por_equipo.php
// Layout para batches por congelador (Sección 7 de R001)
// ✅ FIX: usa $seccion_data (NO $seccion)
// ✅ FIX: valida $matriz
// ✅ FIX NUEVO: valida que los 3 campos requeridos existan antes de mostrar el botón

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

// Buscar los 3 campos por nombre
$campo_hora_ini = null;
$campo_hora_fin = null;
$campo_batch = null;
foreach ($campos as $c) {
    $n = strtolower($c['nombre'] ?? '');
    if (strpos($n, 'inicio') !== false)  $campo_hora_ini = $c;
    elseif (strpos($n, 'fin') !== false) $campo_hora_fin = $c;
    elseif (strpos($n, 'batch') !== false) $campo_batch = $c;
}

$sec_id = (int)($seccion_data['id'] ?? 0);
$campo_ini_id = (int)($campo_hora_ini['id'] ?? 0);
$campo_fin_id = (int)($campo_hora_fin['id'] ?? 0);
$campo_batch_id = (int)($campo_batch['id'] ?? 0);

// ✅ FIX NUEVO: validar que los 3 campos existan
$tiene_todos_los_campos = ($campo_ini_id > 0 && $campo_fin_id > 0 && $campo_batch_id > 0);
?>

<div class="card border-0 mb-4">
    <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-semibold">
            <i class="fas fa-layer-group text-primary me-2"></i>
            <?= htmlspecialchars($seccion_data['nombre'] ?? '') ?>
        </h5>
        <?php if ($puede_editar && $tiene_todos_los_campos): ?>
            <button type="button" class="btn btn-sm btn-outline-primary" 
                    onclick="agregarBatchGlobal(<?= $sec_id ?>, <?= $campo_ini_id ?>, <?= $campo_fin_id ?>, <?= $campo_batch_id ?>)">
                <i class="fas fa-plus me-1"></i> Agregar batch
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">

        <?php if (!$tiene_todos_los_campos): ?>
            <div class="alert alert-warning m-3 mb-0">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Esta sección no tiene configurados los 3 campos requeridos:
                <strong>Hora Inicio</strong>, <strong>Hora Fin</strong> y <strong>N° Batch</strong>.
            </div>
        <?php else: ?>

        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0" id="tabla-batch-<?= $sec_id ?>">
                <thead class="table-light">
                    <tr>
                        <th style="min-width:180px;">CONGELADOR</th>
                        <th colspan="3" class="text-center">Batches</th>
                    </tr>
                    <tr>
                        <th></th>
                        <th class="text-center">Hora Inicio</th>
                        <th class="text-center">Hora Fin</th>
                        <th class="text-center">N° Batch</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipos as $eq): ?>
                        <?php
                            $id_eq = (int)($eq['id_equipo'] ?? 0);
                            $nombre_display = $eq['nombre_display'] ?? ($eq['nombre_custom'] ?? 'N/A');

                            // Recolectar filas de batches existentes
                            $filas = [];
                            foreach ($matriz as $lec) {
                                if ((int)($lec['seccion_id'] ?? 0) === $sec_id && (int)($lec['id_equipo'] ?? 0) === $id_eq) {
                                    $batch_num = (int)($lec['batch_num'] ?? 0);
                                    if (!isset($filas[$batch_num])) $filas[$batch_num] = [];
                                    $filas[$batch_num][(int)($lec['campo_id'] ?? 0)] = $lec['valor'] ?? '';
                                }
                            }
                            ksort($filas);

                            if (empty($filas)) {
                                $filas[0] = [];
                            }

                            $total_filas = count($filas);
                            $fila_num = 0;
                        ?>
                        <?php foreach ($filas as $batch_num => $vals): ?>
                            <?php $fila_num++; ?>
                            <tr class="batch-row" data-equipo="<?= $id_eq ?>" data-batch="<?= $batch_num ?>">
                                <?php if ($fila_num === 1): ?>
                                    <td rowspan="<?= $total_filas ?>" style="vertical-align: middle;">
                                        <strong><?= htmlspecialchars($nombre_display) ?></strong>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <input type="time"
                                           class="form-control form-control-sm input-batch"
                                           data-registro="<?= $registro_id ?>"
                                           data-seccion="<?= $sec_id ?>"
                                           data-equipo="<?= $id_eq ?>"
                                           data-campo="<?= $campo_ini_id ?>"
                                           data-batch="<?= $batch_num ?>"
                                           value="<?= htmlspecialchars($vals[$campo_ini_id] ?? '') ?>"
                                           <?= !$puede_editar ? 'readonly tabindex="-1"' : '' ?>>
                                </td>
                                <td>
                                    <input type="time"
                                           class="form-control form-control-sm input-batch"
                                           data-registro="<?= $registro_id ?>"
                                           data-seccion="<?= $sec_id ?>"
                                           data-equipo="<?= $id_eq ?>"
                                           data-campo="<?= $campo_fin_id ?>"
                                           data-batch="<?= $batch_num ?>"
                                           value="<?= htmlspecialchars($vals[$campo_fin_id] ?? '') ?>"
                                           <?= !$puede_editar ? 'readonly tabindex="-1"' : '' ?>>
                                </td>
                                <td>
                                    <input type="text"
                                           class="form-control form-control-sm input-batch"
                                           data-registro="<?= $registro_id ?>"
                                           data-seccion="<?= $sec_id ?>"
                                           data-equipo="<?= $id_eq ?>"
                                           data-campo="<?= $campo_batch_id ?>"
                                           data-batch="<?= $batch_num ?>"
                                           value="<?= htmlspecialchars($vals[$campo_batch_id] ?? '') ?>"
                                           <?= !$puede_editar ? 'readonly tabindex="-1"' : '' ?>>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php endif; ?>
    </div>
</div>

<?php if ($puede_editar && $tiene_todos_los_campos): ?>
<script>
(function() {
    document.querySelectorAll('#tabla-batch-<?= $sec_id ?> .input-batch').forEach(input => {
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
                batch_num: parseInt(this.dataset.batch),
                valor: this.value,
                observacion: ''
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

// ✅ FIX: usar timestamp como base para evitar colisiones
let batchCounter<?= $sec_id ?> = Math.floor(Date.now() / 1000) % 100000;

function agregarBatchGlobal(secId, campoIniId, campoFinId, campoBatchId) {
    if (typeof window.checklistCtx === 'undefined') {
        alert('Error: contexto del checklist no inicializado');
        return;
    }

    const registroId = window.checklistCtx.registroId;
    const tabla = document.getElementById('tabla-batch-' + secId);
    if (!tabla) return;

    const tbody = tabla.querySelector('tbody');
    if (!tbody) return;

    const equipos = new Set();
    tbody.querySelectorAll('tr.batch-row').forEach(tr => {
        equipos.add(tr.dataset.equipo);
    });

    equipos.forEach(idEq => {
        const batchNum = batchCounter<?= $sec_id ?>++;

        const newRow = document.createElement('tr');
        newRow.className = 'batch-row';
        newRow.dataset.equipo = idEq;
        newRow.dataset.batch = batchNum;

        const td1 = document.createElement('td');
        const input1 = document.createElement('input');
        input1.type = 'time';
        input1.className = 'form-control form-control-sm input-batch';
        input1.dataset.registro = registroId;
        input1.dataset.seccion = secId;
        input1.dataset.equipo = idEq;
        input1.dataset.campo = campoIniId;
        input1.dataset.batch = batchNum;
        td1.appendChild(input1);

        const td2 = document.createElement('td');
        const input2 = document.createElement('input');
        input2.type = 'time';
        input2.className = 'form-control form-control-sm input-batch';
        input2.dataset.registro = registroId;
        input2.dataset.seccion = secId;
        input2.dataset.equipo = idEq;
        input2.dataset.campo = campoFinId;
        input2.dataset.batch = batchNum;
        td2.appendChild(input2);

        const td3 = document.createElement('td');
        const input3 = document.createElement('input');
        input3.type = 'text';
        input3.className = 'form-control form-control-sm input-batch';
        input3.dataset.registro = registroId;
        input3.dataset.seccion = secId;
        input3.dataset.equipo = idEq;
        input3.dataset.campo = campoBatchId;
        input3.dataset.batch = batchNum;
        td3.appendChild(input3);

        newRow.appendChild(td1);
        newRow.appendChild(td2);
        newRow.appendChild(td3);

        newRow.querySelectorAll('.input-batch').forEach(input => {
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
                    batch_num: parseInt(this.dataset.batch),
                    valor: this.value,
                    observacion: ''
                };

                const self = this;
                guardarLectura(payload).then(data => {
                    if (!data.success) console.warn(data.mensaje);
                }).catch(err => console.error(err));
            });
        });

        const filasEquipo = tbody.querySelectorAll(`tr.batch-row[data-equipo="${idEq}"]`);
        if (filasEquipo.length > 0) {
            const ultimaFila = filasEquipo[filasEquipo.length - 1];
            ultimaFila.parentNode.insertBefore(newRow, ultimaFila.nextSibling);
        } else {
            tbody.appendChild(newRow);
        }
    });

    if (window.toastInfo) {
        window.toastInfo('Se agregó una nueva fila de batch a cada equipo');
    }
}
</script>
<?php endif; ?>