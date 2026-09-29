<?php
// views/monitoreo/llenar.php
// Grilla de llenado del formulario (replica del papel MTTO R007)
// ✅ Las horas del turno del operador están editables
// ✅ Las horas de otros turnos quedan readonly (gris)
// ✅ Alertas visuales automáticas si el valor es bajo/crítico
// ✅ FIX: Colores consistentes con modo claro/oscuro
// ✅ FIX: Feedback visual en celdas guardadas (verde/rojo al guardar)
// ✅ FIX: Badge grande de "TU TURNO" en el header

if (!isset($seccion)) $seccion = 'monitoreo';
if (!isset($titulo)) $titulo = 'Llenar Monitoreo';

$registro = $registro ?? null;
if (!$registro) {
    header('Location: /proyecto/monitoreo');
    exit;
}

$equipos   = $registro['equipos'] ?? [];
$horarios  = $registro['horarios'] ?? [];
$matriz    = $registro['matriz'] ?? [];
$turno_reg = $registro['turno'] ?? 'DIA';
$estado    = $registro['estado'] ?? 'BORRADOR';
$unidad    = $registro['unidad_medida'] ?? '%';
$puede_editar = ($estado === 'BORRADOR');

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-check text-primary me-2"></i>
                <?= htmlspecialchars($registro['ruta_nombre'] ?? 'Monitoreo') ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-calendar me-1"></i>
                <?= date('d/m/Y', strtotime($registro['fecha'])) ?>
                <span class="mx-2">|</span>
                <span class="badge bg-dark"><?= htmlspecialchars($turno_reg) ?></span>
                <span class="mx-2">|</span>
                <span class="badge bg-<?= $estado === 'BORRADOR' ? 'warning' : ($estado === 'FIRMADO' ? 'success' : 'info') ?>">
                    <?= htmlspecialchars($estado) ?>
                </span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/proyecto/monitoreo/mis-rutas" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <?php if ($puede_editar): ?>
                <form method="POST" action="/proyecto/monitoreo/cerrar/<?= (int)$registro['id'] ?>"
                      id="formCerrar" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle me-1"></i> Cerrar Turno
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?= $_SESSION['mensaje_tipo'] ?? 'success' ?> alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_SESSION['mensaje']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if (!$puede_editar): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Este registro está en estado <strong><?= htmlspecialchars($estado) ?></strong>
            y no puede ser editado.
            <a href="/proyecto/monitoreo/ver/<?= (int)$registro['id'] ?>">Ver detalle</a>.
        </div>
    <?php endif; ?>

    <!-- Banner: turno actual destacado -->
    <?php if ($puede_editar): ?>
    <div class="alert alert-primary d-flex align-items-center gap-3 mb-4 turno-banner">
        <i class="fas fa-clock fa-2x"></i>
        <div>
            <strong class="d-block">Estás llenando el turno: <?= htmlspecialchars($turno_reg) ?></strong>
            <small class="mb-0">
                Solo las celdas de tu turno son editables. Las de otros turnos están bloqueadas.
            </small>
        </div>
    </div>
    <?php endif; ?>

    <!-- Info de límites -->
    <div class="card border-0 mb-4">
        <div class="card-body d-flex flex-wrap gap-4 align-items-center">
            <div>
                <span class="text-muted small d-block">Valor mínimo</span>
                <span class="badge bg-warning bg-opacity-10 text-warning">
                    <?= htmlspecialchars($registro['valor_min_global'] ?? 'N/A') ?> <?= htmlspecialchars($unidad) ?>
                </span>
            </div>
            <div>
                <span class="text-muted small d-block">Valor máximo</span>
                <span class="badge bg-info bg-opacity-10 text-info">
                    <?= htmlspecialchars($registro['valor_max_global'] ?? 'N/A') ?> <?= htmlspecialchars($unidad) ?>
                </span>
            </div>
            <div>
                <span class="text-muted small d-block">Objetivo de relleno</span>
                <span class="badge bg-success bg-opacity-10 text-success">
                    <?= htmlspecialchars($registro['valor_objetivo_relleno'] ?? 'N/A') ?> <?= htmlspecialchars($unidad) ?>
                </span>
            </div>
            <div class="ms-auto">
                <span class="text-muted small">
                    <i class="fas fa-info-circle me-1"></i>
                    Se marca automáticamente: <span class="badge bg-warning">BAJO</span> <span class="badge bg-danger">CRÍTICO</span>
                </span>
            </div>
        </div>
    </div>

    <!-- Leyenda -->
    <div class="d-flex gap-3 flex-wrap mb-3 small text-muted">
        <span><span class="legend-box legend-ok"></span> Valor OK</span>
        <span><span class="legend-box legend-bajo"></span> Bajo (&lt; mínimo)</span>
        <span><span class="legend-box legend-critico"></span> Crítico (&lt; 50% del mínimo)</span>
        <span><span class="legend-box legend-guardado"></span> Guardado</span>
    </div>

    <!-- Grilla principal -->
    <div class="card border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0 grilla-monitoreo">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:200px;position:sticky;left:0;background:var(--bg-table-header);z-index:2;">
                                EQUIPO
                            </th>
                            <?php foreach ($horarios as $h): ?>
                                <?php $es_turno_actual = ($h['turno'] === $turno_reg); ?>
                                <th class="text-center <?= $es_turno_actual ? 'col-turno-actual' : '' ?>" style="min-width:85px;">
                                    <?= htmlspecialchars(substr($h['hora'], 0, 5)) ?>
                                    <br>
                                    <small class="text-muted" style="font-size:0.65rem;">
                                        <?= htmlspecialchars($h['turno']) ?>
                                    </small>
                                    <?php if ($es_turno_actual): ?>
                                        <br><span class="badge bg-primary" style="font-size:0.55rem;">TU TURNO</span>
                                    <?php endif; ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($equipos as $eq): ?>
                            <?php $id_eq = (int)$eq['id_equipo']; ?>
                            <tr>
                                <td style="position:sticky;left:0;background:var(--bg-card);z-index:1;">
                                    <strong><?= htmlspecialchars($eq['nombre_equipo'] ?? '') ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($eq['equipo_codigo'] ?? '') ?></small>
                                </td>
                                <?php foreach ($horarios as $h): ?>
                                    <?php
                                    $hora = $h['hora'];
                                    $es_turno_actual = ($h['turno'] === $turno_reg);
                                    $lec = $matriz[$id_eq][$hora] ?? null;
                                    $valor_actual = $lec['valor'] ?? '';
                                    $nivel = $lec['nivel_alerta'] ?? 'OK';
                                    $obs = $lec['observacion'] ?? '';

                                    $clase_celda = '';
                                    if ($nivel === 'BAJO')    $clase_celda = 'celda-bajo';
                                    if ($nivel === 'CRITICO') $clase_celda = 'celda-critico';
                                    if ($nivel === 'ALTO')    $clase_celda = 'celda-alto';

                                    $readonly = (!$es_turno_actual || !$puede_editar);
                                    ?>
                                    <td class="text-center p-1 <?= $clase_celda ?>">
                                        <input type="number"
                                               class="form-control form-control-sm input-lectura text-center <?= $readonly ? 'input-readonly' : '' ?>"
                                               data-registro="<?= (int)$registro['id'] ?>"
                                               data-equipo="<?= $id_eq ?>"
                                               data-hora="<?= htmlspecialchars($hora) ?>"
                                               data-nivel="<?= htmlspecialchars($nivel) ?>"
                                               value="<?= htmlspecialchars($valor_actual) ?>"
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

    <!-- Footer info -->
    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
        <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i>
            Los valores se guardan automáticamente al salir de cada celda.
        </small>
        <small class="text-muted">
            <i class="fas fa-save me-1"></i>
            Última actualización: <?= date('d/m/Y H:i', strtotime($registro['fecha_actualizacion'] ?? 'now')) ?>
        </small>
    </div>

</div>

<style>
/* ========================================== */
/* BANNER DE TURNO */
/* ========================================== */
.turno-banner {
    border-left: 4px solid #0d6efd;
}

/* ========================================== */
/* GRILLA */
/* ========================================== */
.grilla-monitoreo input.form-control-sm {
    padding: 4px 6px;
    font-size: 0.8rem;
    border-radius: 6px;
    border: 1px solid var(--border-color);
    background: var(--bg-input);
    color: var(--text-primary);
    transition: all 0.15s ease;
}
.grilla-monitoreo input.form-control-sm:not(.input-readonly):focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 2px rgba(13,110,253,0.15);
    background: var(--bg-card);
}
.grilla-monitoreo input.form-control-sm:not(.input-readonly):hover {
    border-color: #0d6efd;
}

/* Readonly: gris y sin interacción */
.grilla-monitoreo input.input-readonly {
    background: var(--bg-input-disabled);
    color: var(--text-muted);
    cursor: not-allowed;
    opacity: 0.7;
}

/* Columna del turno actual */
.grilla-monitoreo th.col-turno-actual {
    background: rgba(13,110,253,0.08);
    border-bottom: 2px solid #0d6efd;
}

/* ========================================== */
/* CELDAS CON ALERTA */
/* ========================================== */
.grilla-monitoreo td.celda-bajo {
    background: rgba(255, 193, 7, 0.15);
}
.grilla-monitoreo td.celda-critico {
    background: rgba(220, 53, 69, 0.15);
}
.grilla-monitoreo td.celda-alto {
    background: rgba(13, 202, 240, 0.15);
}

/* Feedback visual al guardar */
.grilla-monitoreo input.guardado-ok {
    border-color: #198754 !important;
    box-shadow: 0 0 0 2px rgba(25, 135, 84, 0.2) !important;
}
.grilla-monitoreo input.guardado-error {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.2) !important;
}

/* Modo oscuro - reforzar celdas de alerta */
[data-theme="dark"] .grilla-monitoreo td.celda-bajo {
    background: rgba(234, 179, 8, 0.18) !important;
}
[data-theme="dark"] .grilla-monitoreo td.celda-critico {
    background: rgba(239, 68, 68, 0.18) !important;
}
[data-theme="dark"] .grilla-monitoreo td.celda-alto {
    background: rgba(6, 182, 212, 0.18) !important;
}
[data-theme="dark"] .grilla-monitoreo th.col-turno-actual {
    background: rgba(59, 130, 246, 0.15) !important;
    border-bottom: 2px solid #3b82f6 !important;
}

/* ========================================== */
/* LEYENDA */
/* ========================================== */
.legend-box {
    display: inline-block;
    width: 14px;
    height: 14px;
    border-radius: 3px;
    vertical-align: middle;
    margin-right: 4px;
    border: 1px solid var(--border-color);
}
.legend-ok         { background: var(--bg-input); }
.legend-bajo       { background: rgba(255,193,7,0.35); }
.legend-critico    { background: rgba(220,53,69,0.35); }
.legend-guardado   { background: rgba(25,135,84,0.35); }

/* ========================================== */
/* GENERAL */
/* ========================================== */
.grilla-monitoreo th {
    font-size: 0.7rem;
    text-transform: uppercase;
    padding: 8px 4px;
}
.grilla-monitoreo td {
    padding: 4px;
}
.card { border-radius: 16px; box-shadow: 0 2px 12px var(--shadow-color); }
</style>

<script>
(function() {
    const csrfToken = '<?= SecurityHelper::generateCSRFToken() ?>';
    const inputs = document.querySelectorAll('.input-lectura:not([readonly])');

    function guardar(input) {
        if (input.readOnly) return;

        const valor = input.value.trim();
        const registro = input.dataset.registro;
        const equipo = input.dataset.equipo;
        const hora = input.dataset.hora;

        input.classList.remove('guardado-ok', 'guardado-error');

        const formData = new URLSearchParams();
        formData.append('csrf_token', csrfToken);
        formData.append('registro_id', registro);
        formData.append('id_equipo', equipo);
        formData.append('hora', hora);
        formData.append('valor', valor);

        fetch('/proyecto/monitoreo/guardar-lectura', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                console.warn('Error al guardar:', data.mensaje);
                input.classList.add('guardado-error');
                setTimeout(() => input.classList.remove('guardado-error'), 2000);
                return;
            }

            // Feedback visual de "guardado"
            input.classList.add('guardado-ok');
            setTimeout(() => input.classList.remove('guardado-ok'), 1500);

            // Actualizar color de la celda
            const nivel = data.nivel_alerta;
            input.dataset.nivel = nivel;

            const td = input.closest('td');
            td.classList.remove('celda-bajo', 'celda-critico', 'celda-alto');
            if (nivel === 'BAJO')    td.classList.add('celda-bajo');
            if (nivel === 'CRITICO') td.classList.add('celda-critico');
            if (nivel === 'ALTO')    td.classList.add('celda-alto');

            // Toast si es crítico
            if (nivel === 'CRITICO') {
                if (window.showToast) {
                    window.showToast('error',
                        'Nivel crítico en equipo #' + equipo + ' a las ' + hora,
                        '⚠️ Alerta');
                }
            } else if (nivel === 'BAJO') {
                if (window.showToast) {
                    window.showToast('warning',
                        'Nivel bajo en equipo #' + equipo + ' a las ' + hora,
                        'Advertencia');
                }
            }
        })
        .catch(err => {
            console.error('Error fetch:', err);
            input.classList.add('guardado-error');
            setTimeout(() => input.classList.remove('guardado-error'), 2000);
        });
    }

    inputs.forEach(input => {
        let lastValue = input.value;

        input.addEventListener('focus', function() {
            lastValue = this.value;
        });

        input.addEventListener('blur', function() {
            if (this.value !== lastValue) {
                guardar(this);
            }
        });

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur();
            }
        });
    });

    // Advertir al usuario si intenta cerrar sin guardar
    const formCerrar = document.getElementById('formCerrar');
    if (formCerrar) {
        formCerrar.addEventListener('submit', function(e) {
            if (!confirm('¿Confirmas cerrar el turno? Después deberás firmarlo.')) {
                e.preventDefault();
            }
        });
    }
})();
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>