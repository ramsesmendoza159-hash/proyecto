<?php
// views/checklists/asignar.php
// Formulario para que admin/operador asigne un checklist a un técnico específico
// ✅ NUEVO

if (!isset($seccion)) $seccion = 'checklists';
if (!isset($titulo)) $titulo = 'Asignar Checklist';

$tipos = $tipos ?? [];
$tecnicos = $tecnicos ?? [];

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-user-plus text-primary me-2"></i>Asignar Checklist
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Asigna un checklist a un técnico específico
            </p>
        </div>
        <a href="/proyecto/checklists" class="btn btn-secondary btn-header-action">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
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

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-clipboard-check text-primary me-2"></i>Datos de la Asignación
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="/proyecto/checklists/asignar/guardar" id="formAsignar">
                        <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">

                        <div class="row g-3">
                            <!-- Tipo -->
                            <div class="col-md-12">
                                <label for="tipo_id" class="form-label fw-semibold">
                                    Tipo de Checklist <span class="text-danger">*</span>
                                </label>
                                <select name="tipo_id" id="tipo_id" class="form-select" required>
                                    <option value="">Seleccionar tipo...</option>
                                    <?php foreach ($tipos as $t): ?>
                                        <option value="<?= (int)$t['id'] ?>"
                                                data-requiere-turno="<?= (int)($t['requiere_turno'] ?? 0) ?>"
                                                data-frecuencia="<?= htmlspecialchars($t['frecuencia'] ?? '') ?>">
                                            <?= htmlspecialchars($t['codigo'] . ' - ' . $t['nombre']) ?>
                                            (<?= htmlspecialchars($t['frecuencia'] ?? '') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Técnico asignado -->
                            <div class="col-md-12">
                                <label for="asignado_a_id" class="form-label fw-semibold">
                                    Asignar a <span class="text-danger">*</span>
                                </label>
                                <select name="asignado_a_id" id="asignado_a_id" class="form-select" required>
                                    <option value="">Seleccionar técnico...</option>
                                    <?php 
                                    $tecnicos_por_rol = [];
                                    foreach ($tecnicos as $tec) {
                                        $tecnicos_por_rol[$tec['rol']][] = $tec;
                                    }
                                    foreach ($tecnicos_por_rol as $rol => $lista):
                                    ?>
                                        <optgroup label="<?= ucfirst($rol) ?>">
                                            <?php foreach ($lista as $tec): ?>
                                                <option value="<?= (int)$tec['id'] ?>">
                                                    <?= htmlspecialchars($tec['nombre']) ?> 
                                                    (<?= htmlspecialchars($tec['email']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Fecha -->
                            <div class="col-md-6">
                                <label for="fecha" class="form-label fw-semibold">
                                    Fecha <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="fecha" id="fecha" class="form-control" 
                                       value="<?= date('Y-m-d') ?>" required
                                       max="<?= date('Y-m-d') ?>">
                            </div>

                            <!-- Turno (dinámico) -->
                            <div class="col-md-6" id="div-turno" style="display: none;">
                                <label for="turno" class="form-label fw-semibold">
                                    Turno <span class="text-danger">*</span>
                                </label>
                                <select name="turno" id="turno" class="form-select">
                                    <option value="">Seleccionar...</option>
                                    <option value="DIA">DÍA (8am – 4pm)</option>
                                    <option value="TARDE">TARDE (4pm – 12am)</option>
                                    <option value="NOCHE">NOCHE (12am – 8am)</option>
                                </select>
                                <small class="text-muted">Este tipo de checklist requiere turno</small>
                            </div>

                            <!-- Observaciones -->
                            <div class="col-md-12">
                                <label for="observaciones" class="form-label fw-semibold">
                                    Observaciones
                                </label>
                                <textarea name="observaciones" id="observaciones" class="form-control" 
                                          rows="3" 
                                          placeholder="Motivo, instrucciones especiales, etc. (opcional)"
                                          maxlength="1000"></textarea>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary" id="btnAsignar">
                                <i class="fas fa-user-plus me-2"></i> Asignar Checklist
                            </button>
                            <a href="/proyecto/checklists" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info adicional -->
            <div class="card border-0 mt-4">
                <div class="card-body">
                    <h6 class="fw-semibold">
                        <i class="fas fa-info-circle text-primary me-2"></i>Información
                    </h6>
                    <ul class="small text-muted mb-0 ps-3">
                        <li>El técnico verá este checklist asignado en <strong>"Mis Checklists"</strong>.</li>
                        <li>Si ya existe un registro del mismo tipo+fecha+turno, se reasignará en lugar de crear uno nuevo.</li>
                        <li>Los turnos son: DÍA (8am-4pm), TARDE (4pm-12am), NOCHE (12am-8am).</li>
                        <li>El técnico deberá <strong>firmarlo</strong> al finalizar (según la secuencia configurada).</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
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
    border-color: var(--accent-primary);
    box-shadow: 0 0 0 4px var(--accent-light);
}
.form-label {
    font-size: 0.9rem;
    margin-bottom: 0.4rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectTipo = document.getElementById('tipo_id');
    const divTurno = document.getElementById('div-turno');
    const selectTurno = document.getElementById('turno');
    const form = document.getElementById('formAsignar');
    const btnAsignar = document.getElementById('btnAsignar');

    // Mostrar/ocultar turno según el tipo seleccionado
    function actualizarTurno() {
        const opt = selectTipo.options[selectTipo.selectedIndex];
        const requiere = opt ? parseInt(opt.getAttribute('data-requiere-turno') || '0') : 0;
        
        if (requiere === 1) {
            divTurno.style.display = 'block';
            selectTurno.required = true;
        } else {
            divTurno.style.display = 'none';
            selectTurno.required = false;
            selectTurno.value = '';
        }
    }

    selectTipo.addEventListener('change', actualizarTurno);
    actualizarTurno();

    // Validación antes de enviar
    form.addEventListener('submit', function(e) {
        const tipo = selectTipo.value;
        const asignado = document.getElementById('asignado_a_id').value;
        
        if (!tipo || !asignado) {
            e.preventDefault();
            alert(' Debes seleccionar tipo y técnico');
            return false;
        }
        
        const opt = selectTipo.options[selectTipo.selectedIndex];
        const requiere = opt ? parseInt(opt.getAttribute('data-requiere-turno') || '0') : 0;
        if (requiere === 1 && !selectTurno.value) {
            e.preventDefault();
            alert(' Este tipo de checklist requiere que indiques el turno');
            selectTurno.focus();
            return false;
        }
        
        if (!confirm('¿Confirmas asignar este checklist?')) {
            e.preventDefault();
            return false;
        }
        
        btnAsignar.disabled = true;
        btnAsignar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Asignando...';
    });
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>