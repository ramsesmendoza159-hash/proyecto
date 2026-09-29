<?php
// views/mantenimiento/ejecutar.php
// Ejecutar Mantenimiento con Checklist + Fotos + Backdating
// ✅ FIX: name="fotos[ID]" (antes "foto_paso[ID]") para que coincida con el controlador
// ✅ FIX: credentials: 'same-origin' en fetch de validarFechaBackdating

if (!isset($seccion)) $seccion = 'mis_tareas';
if (!isset($titulo)) $titulo = 'Ejecutar Mantenimiento';

$plan = $plan ?? null;
$protocolo = $protocolo ?? [];
$checklist_ejecutado = $checklist_ejecutado ?? [];

if (!$plan) {
    header('Location: /proyecto/mantenimiento/mis-tareas');
    exit;
}

// ✅ Info de backdating
require_once __DIR__ . '/../../helpers/BackdatingHelper.php';
$rol_actual = $_SESSION['rol'] ?? '';
$puede_backdating = BackdatingHelper::puedeHacerBackdating($rol_actual);
$limite_dias = BackdatingHelper::getLimitePorRol($rol_actual);

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-play-circle text-success me-2"></i>Ejecutar Mantenimiento
            </h4>
            <p class="text-muted small mb-0">
                <strong><?= htmlspecialchars($plan['nombre_equipo']) ?></strong> | 
                <?= htmlspecialchars($plan['nombre_tarea']) ?>
            </p>
        </div>
        <a href="/proyecto/mantenimiento/mis-tareas" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <form method="POST" action="/proyecto/mantenimiento/completar-ejecucion/<?= $plan['id'] ?>" enctype="multipart/form-data" id="formEjecucion">
        <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
        <input type="hidden" name="id_equipo" value="<?= $plan['id_equipo'] ?>">
        
        <div class="row g-4">
            <!-- Checklist -->
            <div class="col-lg-8">
                <div class="card border-0">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-clipboard-check text-success me-2"></i>
                            Protocolo de Mantenimiento
                        </h5>
                        <p class="text-muted small mb-0 mt-1">Marca cada paso al completarlo</p>
                    </div>
                    <div class="card-body">
                        <?php if (empty($protocolo)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No hay protocolo definido para esta tarea
                            </div>
                        <?php else: ?>
                            <?php foreach ($protocolo as $index => $paso): 
                                $yaEjecutado = false;
                                $observacionGuardada = '';
                                $fotoGuardada = null;
                                foreach ($checklist_ejecutado as $ejec) {
                                    if ($ejec['paso_id'] == $paso['id']) {
                                        $yaEjecutado = $ejec['cumplido'] == 1;
                                        $observacionGuardada = $ejec['observaciones'] ?? '';
                                        $fotoGuardada = $ejec['foto_evidencia'] ?? null;
                                        break;
                                    }
                                }
                            ?>
                                <div class="checklist-paso p-3 mb-3 rounded-3 border" 
                                     style="background: var(--bg-card);"
                                     data-paso-id="<?= $paso['id'] ?>">
                                    
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input checklist-item" 
                                                   type="checkbox" 
                                                   name="pasos[<?= $paso['id'] ?>]" 
                                                   value="1"
                                                   id="paso_<?= $paso['id'] ?>"
                                                   data-paso-id="<?= $paso['id'] ?>"
                                                   data-requiere-foto="<?= $paso['requiere_foto'] ? '1' : '0' ?>"
                                                   <?= $yaEjecutado ? 'checked' : '' ?>
                                                   <?= $paso['obligatorio'] ? 'required' : '' ?>>
                                        </div>
                                        <div class="flex-grow-1">
                                            <label class="form-check-label fw-semibold d-block" for="paso_<?= $paso['id'] ?>">
                                                <span class="badge bg-primary me-2"><?= $paso['paso_numero'] ?></span>
                                                <?= htmlspecialchars($paso['descripcion']) ?>
                                                <?php if ($paso['obligatorio']): ?>
                                                    <span class="text-danger">*</span>
                                                <?php endif; ?>
                                                <?php if ($paso['requiere_foto']): ?>
                                                    <span class="badge bg-warning text-dark ms-2">
                                                        <i class="fas fa-camera me-1"></i>Requiere foto
                                                    </span>
                                                <?php endif; ?>
                                            </label>
                                            
                                            <div class="mt-2">
                                                <input type="text" 
                                                       class="form-control form-control-sm" 
                                                       name="observaciones[<?= $paso['id'] ?>]" 
                                                       placeholder="Observaciones (opcional)"
                                                       value="<?= htmlspecialchars($observacionGuardada) ?>">
                                            </div>

                                            <div class="mt-2 foto-paso-container" data-paso-id="<?= $paso['id'] ?>" style="display: <?= $yaEjecutado ? 'block' : 'none' ?>;">
                                                <label class="form-label small fw-semibold text-muted">
                                                    <i class="fas fa-camera text-primary me-1"></i>
                                                    Foto de evidencia
                                                    <?php if ($paso['requiere_foto']): ?>
                                                        <span class="text-danger">*</span>
                                                    <?php endif; ?>
                                                </label>
                                                
                                                <div class="foto-paso-wrapper d-flex align-items-center gap-2 flex-wrap">
                                                    <!-- ✅ FIX: name="fotos[ID]" (antes era foto_paso[ID]) -->
                                                    <input type="file" 
                                                           class="foto-paso-input" 
                                                           name="fotos[<?= $paso['id'] ?>]" 
                                                           id="foto_paso_<?= $paso['id'] ?>"
                                                           accept="image/*" 
                                                           capture="environment"
                                                           style="display: none;"
                                                           data-paso-id="<?= $paso['id'] ?>"
                                                           onchange="previewFotoPaso(this, <?= $paso['id'] ?>)">
                                                    
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-primary btn-tomar-foto-paso" 
                                                            data-paso-id="<?= $paso['id'] ?>">
                                                        <i class="fas fa-camera me-1"></i> Tomar foto
                                                    </button>
                                                    
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger btn-eliminar-foto-paso" 
                                                            data-paso-id="<?= $paso['id'] ?>"
                                                            style="display: none;"
                                                            onclick="eliminarFotoPaso(<?= $paso['id'] ?>)">
                                                        <i class="fas fa-trash me-1"></i> Eliminar
                                                    </button>
                                                    
                                                    <small class="text-success fw-semibold nombre-foto-paso" 
                                                           data-paso-id="<?= $paso['id'] ?>"
                                                           style="display: none;">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        <span class="nombre-archivo"></span>
                                                    </small>
                                                </div>
                                                
                                                <div class="mt-2 preview-foto-paso" 
                                                     id="preview_paso_<?= $paso['id'] ?>"
                                                     style="display: <?= $fotoGuardada ? 'block' : 'none' ?>;">
                                                    <img src="<?= $fotoGuardada ? '/proyecto/' . htmlspecialchars($fotoGuardada) : '' ?>" 
                                                         alt="Preview" 
                                                         class="img-thumbnail" 
                                                         style="max-height: 150px; max-width: 100%;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Datos del mantenimiento -->
            <div class="col-lg-4">
                <div class="card border-0 mb-4">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            Información del Mantenimiento
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Descripción</label>
                            <textarea class="form-control" name="descripcion" rows="3" 
                                      required><?= htmlspecialchars($plan['nombre_tarea']) ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Costo (S/)</label>
                            <input type="number" step="0.01" class="form-control" 
                                   name="costo" value="<?= $plan['costo_estimado'] ?? 0 ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Repuestos usados</label>
                            <textarea class="form-control" name="repuestos_usados" rows="2" 
                                      placeholder="Ej: Filtro, aceite, arandela..."><?= htmlspecialchars($plan['repuestos_necesarios'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Observaciones generales</label>
                            <textarea class="form-control" name="observaciones_generales" rows="3" 
                                      placeholder="Observaciones del trabajo realizado..."></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- ✅ BACKDATING -->
                <?php if ($puede_backdating): ?>
                <div class="card border-0 mb-4 border-warning">
                    <div class="card-header bg-warning bg-opacity-10 border-0 pt-3">
                        <h5 class="mb-0 fw-semibold text-warning">
                            <i class="fas fa-history me-2"></i>
                            ¿Fue en otra fecha?
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" 
                                   id="es_backdating" name="es_backdating" value="1"
                                   onchange="toggleBackdating(this.checked)">
                            <label class="form-check-label fw-semibold" for="es_backdating">
                                Registrar mantenimiento retroactivo
                            </label>
                        </div>
                        
                        <div id="backdating_fields" style="display: none;">
                            <div class="alert alert-info small mb-3">
                                <i class="fas fa-info-circle me-1"></i>
                                <?php if ($limite_dias === null): ?>
                                    Como <strong><?= htmlspecialchars($rol_actual) ?></strong>, puedes registrar mantenimientos sin límite de antigüedad.
                                <?php else: ?>
                                    Como <strong><?= htmlspecialchars($rol_actual) ?></strong>, puedes registrar mantenimientos hasta <strong><?= $limite_dias ?> días</strong> atrás.
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    Fecha real del mantenimiento <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" 
                                       id="fecha_real" name="fecha_real"
                                       max="<?= date('Y-m-d') ?>"
                                       <?= $limite_dias !== null ? 'min="' . date('Y-m-d', strtotime("-{$limite_dias} days")) . '"' : '' ?>
                                       onchange="validarFechaBackdating()">
                                <small class="text-muted" id="fecha_help">
                                    <?php if ($limite_dias !== null): ?>
                                        Desde <?= date('d/m/Y', strtotime("-{$limite_dias} days")) ?> hasta hoy
                                    <?php else: ?>
                                        Cualquier fecha pasada
                                    <?php endif; ?>
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    Motivo del registro retroactivo <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" 
                                          id="motivo_backdating" name="motivo_backdating"
                                          rows="3" 
                                          placeholder="Ej: No hubo acceso al sistema, olvido de registro, etc."></textarea>
                                <small class="text-muted">Este motivo quedará registrado en la auditoría</small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Progreso -->
                <div class="card border-0 mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="small text-muted fw-semibold">Progreso del checklist</span>
                            <span class="small fw-bold text-success" id="progresoTexto">0%</span>
                        </div>
                        <div class="progress" style="height: 10px; border-radius: 10px;">
                            <div class="progress-bar bg-success" id="progresoBarra" style="width:0%; border-radius:10px;"></div>
                        </div>
                        <div class="mt-2 text-center">
                            <small class="text-muted">
                                <span id="pasosCompletados">0</span> de <span id="totalPasos"><?= count($protocolo) ?></span> pasos completados
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Botón de completar -->
                <div class="card border-0">
                    <div class="card-body">
                        <button type="submit" class="btn btn-success w-100 btn-lg" id="btnCompletar">
                            <i class="fas fa-check-circle me-2"></i> Completar Mantenimiento
                        </button>
                        <a href="/proyecto/mantenimiento/mis-tareas" class="btn btn-secondary w-100 mt-2">
                            <i class="fas fa-times me-2"></i> Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>

<style>
.checklist-paso {
    transition: all 0.3s ease;
}
.checklist-paso:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px var(--shadow-hover);
}
.checklist-paso .form-check-input:checked + .form-check-label {
    color: #198754;
}
.foto-paso-container {
    border-top: 1px dashed var(--border-color);
    padding-top: 10px;
    animation: fadeIn 0.3s ease;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}
.preview-foto-paso img {
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
#backdating_fields {
    animation: fadeIn 0.3s ease;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const totalPasos = document.querySelectorAll('.checklist-item').length;
    document.getElementById('totalPasos').textContent = totalPasos;

    // Mostrar/ocultar foto al marcar
    document.querySelectorAll('.checklist-item').forEach(function(check) {
        function actualizarVisibilidadFoto() {
            const pasoId = check.dataset.pasoId;
            const container = document.querySelector(`.foto-paso-container[data-paso-id="${pasoId}"]`);
            if (container) {
                container.style.display = check.checked ? 'block' : 'none';
            }
        }
        actualizarVisibilidadFoto();
        check.addEventListener('change', function() {
            actualizarVisibilidadFoto();
            actualizarProgreso();
        });
    });

    function actualizarProgreso() {
        const checked = document.querySelectorAll('.checklist-item:checked').length;
        const porcentaje = totalPasos > 0 ? Math.round((checked / totalPasos) * 100) : 0;
        
        document.getElementById('progresoTexto').textContent = porcentaje + '%';
        document.getElementById('progresoBarra').style.width = porcentaje + '%';
        document.getElementById('pasosCompletados').textContent = checked;
        
        const barra = document.getElementById('progresoBarra');
        if (porcentaje < 30) barra.className = 'progress-bar bg-danger';
        else if (porcentaje < 70) barra.className = 'progress-bar bg-warning';
        else barra.className = 'progress-bar bg-success';
    }
    actualizarProgreso();

    // Botones tomar foto
    document.querySelectorAll('.btn-tomar-foto-paso').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const pasoId = this.dataset.pasoId;
            const input = document.getElementById(`foto_paso_${pasoId}`);
            if (input) input.click();
        });
    });

    // Validación antes de enviar
    document.getElementById('formEjecucion').addEventListener('submit', function(e) {
        const obligatorios = document.querySelectorAll('.checklist-item[required]');
        let todosMarcados = true;
        let pasosSinFoto = [];

        obligatorios.forEach(function(check) {
            if (!check.checked) {
                todosMarcados = false;
                check.closest('.checklist-paso').style.borderColor = '#dc3545';
            } else {
                check.closest('.checklist-paso').style.borderColor = '';
                const pasoId = check.dataset.pasoId;
                const requiereFoto = check.dataset.requiereFoto === '1';
                const inputFoto = document.getElementById(`foto_paso_${pasoId}`);
                const tienePreview = document.querySelector(`#preview_paso_${pasoId} img`)?.src;
                
                if (requiereFoto && (!inputFoto || !inputFoto.files || inputFoto.files.length === 0) && !tienePreview) {
                    pasosSinFoto.push(pasoId);
                }
            }
        });

        if (!todosMarcados) {
            e.preventDefault();
            alert('⚠️ Debes marcar TODOS los pasos obligatorios del protocolo');
            return false;
        }

        if (pasosSinFoto.length > 0) {
            e.preventDefault();
            alert('⚠️ Los siguientes pasos requieren foto de evidencia: ' + pasosSinFoto.join(', '));
            return false;
        }

        // Validación de backdating
        const esBackdating = document.getElementById('es_backdating');
        if (esBackdating && esBackdating.checked) {
            const fechaReal = document.getElementById('fecha_real');
            const motivo = document.getElementById('motivo_backdating');
            
            if (!fechaReal.value) {
                e.preventDefault();
                alert('⚠️ Debes indicar la fecha real del mantenimiento');
                fechaReal.focus();
                return false;
            }
            
            if (!motivo.value.trim()) {
                e.preventDefault();
                alert('⚠️ Debes indicar el motivo del registro retroactivo');
                motivo.focus();
                return false;
            }
        }

        if (!confirm('¿Confirmas que completaste el mantenimiento?')) {
            e.preventDefault();
            return false;
        }

        const btn = document.getElementById('btnCompletar');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Completando...';
    });
});

// ==========================================
// BACKDATING
// ==========================================
function toggleBackdating(activar) {
    const fields = document.getElementById('backdating_fields');
    const fechaReal = document.getElementById('fecha_real');
    const motivo = document.getElementById('motivo_backdating');
    
    if (activar) {
        fields.style.display = 'block';
        fechaReal.required = true;
        motivo.required = true;
        // Poner por defecto ayer
        const ayer = new Date();
        ayer.setDate(ayer.getDate() - 1);
        fechaReal.value = ayer.toISOString().split('T')[0];
    } else {
        fields.style.display = 'none';
        fechaReal.required = false;
        motivo.required = false;
        fechaReal.value = '';
        motivo.value = '';
    }
}

// ✅ FIX: Agregado credentials: 'same-origin' para enviar la cookie de sesión
function validarFechaBackdating() {
    const fechaReal = document.getElementById('fecha_real');
    if (!fechaReal.value) return;

    fetch('/proyecto/backdating/validar-fecha', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'fecha=' + encodeURIComponent(fechaReal.value)
    })
    .then(r => r.json())
    .then(data => {
        if (!data.valido) {
            alert('⚠️ ' + data.error);
            fechaReal.value = '';
        }
    })
    .catch(err => console.error('Error validando fecha:', err));
}

// ==========================================
// FOTOS POR PASO
// ==========================================
function previewFotoPaso(input, pasoId) {
    const preview = document.getElementById(`preview_paso_${pasoId}`);
    const btnEliminar = document.querySelector(`.btn-eliminar-foto-paso[data-paso-id="${pasoId}"]`);
    const nombreFoto = document.querySelector(`.nombre-foto-paso[data-paso-id="${pasoId}"]`);
    const nombreArchivo = nombreFoto ? nombreFoto.querySelector('.nombre-archivo') : null;
    const btnTomar = document.querySelector(`.btn-tomar-foto-paso[data-paso-id="${pasoId}"]`);

    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();

        reader.onload = function(e) {
            preview.style.display = 'block';
            preview.querySelector('img').src = e.target.result;
            if (btnEliminar) btnEliminar.style.display = 'inline-block';
            if (nombreFoto && nombreArchivo) {
                nombreFoto.style.display = 'inline-flex';
                nombreFoto.style.alignItems = 'center';
                nombreArchivo.textContent = file.name.length > 20 ? file.name.substring(0, 20) + '...' : file.name;
            }
            if (btnTomar) btnTomar.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Cambiar';
        };
        reader.readAsDataURL(file);
    }
}

function eliminarFotoPaso(pasoId) {
    const input = document.getElementById(`foto_paso_${pasoId}`);
    if (input) input.value = '';
    const preview = document.getElementById(`preview_paso_${pasoId}`);
    if (preview) { preview.style.display = 'none'; preview.querySelector('img').src = ''; }
    const btnEliminar = document.querySelector(`.btn-eliminar-foto-paso[data-paso-id="${pasoId}"]`);
    if (btnEliminar) btnEliminar.style.display = 'none';
    const nombreFoto = document.querySelector(`.nombre-foto-paso[data-paso-id="${pasoId}"]`);
    if (nombreFoto) nombreFoto.style.display = 'none';
    const btnTomar = document.querySelector(`.btn-tomar-foto-paso[data-paso-id="${pasoId}"]`);
    if (btnTomar) btnTomar.innerHTML = '<i class="fas fa-camera me-1"></i> Tomar foto';
}
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>