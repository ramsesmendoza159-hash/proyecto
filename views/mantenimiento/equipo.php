<?php
// views/mantenimiento/equipo.php
// Plan de Mantenimiento - CON EDICIÓN DE HORÓMETRO Y FECHA
// VERSIÓN UNIFICADA - TEMA AZUL BRILLANTE

if (!isset($seccion)) $seccion = 'mantenimiento';
if (!isset($titulo)) $titulo = 'Plan de Mantenimiento';

$equipo = $equipo ?? null;
$plan = $plan ?? [];
$historial = $historial ?? [];
$historial_horometro = $historial_horometro ?? [];
$proveedores = $proveedores ?? [];
$tecnicos = $tecnicos ?? [];

if (!$equipo) {
    header('Location: /proyecto/equipos');
    exit;
}

$puede_editar_horometro = in_array($_SESSION['rol'] ?? '', ['admin', 'supervisor']);
$puede_eliminar_horometro = ($_SESSION['rol'] ?? '') === 'admin';

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-tools text-primary me-2"></i>Plan de Mantenimiento
            </h4>
            <p class="text-muted small mb-0">
                <strong><?= htmlspecialchars($equipo['nombre_equipo'] ?? 'Equipo') ?></strong> 
                | Código: <code><?= htmlspecialchars($equipo['codigo'] ?? 'N/A') ?></code>
                | Horómetro: <span class="badge bg-primary"><?= number_format($equipo['horometro_actual'] ?? 0) ?> horas</span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/proyecto/mantenimiento/panel" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalHorometro">
                <i class="fas fa-tachometer-alt me-1"></i> Registrar Horómetro
            </button>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaTarea">
                <i class="fas fa-plus me-1"></i> Nueva Tarea
            </button>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?= $_SESSION['mensaje_tipo'] ?? 'success' ?> alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['mensaje'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['errores'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i>
            <ul class="mb-0">
                <?php foreach ($_SESSION['errores'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['errores']); ?>
    <?php endif; ?>

    <!-- ========================================== -->
    <!-- TARJETAS DE INFO DEL EQUIPO - AZUL UNIFICADO -->
    <!-- ========================================== -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern">
                        <i class="fas fa-tachometer-alt fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Horómetro Actual</span>
                        <span class="stat-number-modern"><?= number_format($equipo['horometro_actual'] ?? 0) ?> h</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Promedio Diario</span>
                        <span class="stat-number-modern"><?= number_format($equipo['horas_diarias_promedio'] ?? 0, 1) ?> h/día</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern">
                        <i class="fas fa-clipboard-list fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Tareas Activas</span>
                        <span class="stat-number-modern"><?= count($plan) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern">
                        <i class="fas fa-history fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Mantenimientos</span>
                        <span class="stat-number-modern"><?= count($historial) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tareas Programadas -->
    <div class="card border-0 mb-4">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-calendar-check text-primary me-2"></i> Tareas Programadas
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($plan)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-clipboard-list fa-3x d-block mb-3"></i>
                    <h5>No hay tareas programadas</h5>
                    <p>Crea la primera tarea de mantenimiento</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaTarea">
                        <i class="fas fa-plus me-1"></i> Crear Tarea
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tarea</th>
                                <th>Tipo</th>
                                <th>Frecuencia</th>
                                <th>Último</th>
                                <th>Próximo</th>
                                <th>Responsable</th>
                                <th>Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($plan as $tarea): 
                                $horas_restantes = $tarea['horas_restantes'] ?? null;
                                $dias_restantes = $tarea['dias_restantes'] ?? null;
                                
                                $estado = 'AL_DIA';
                                $color = 'success';
                                $icono = 'fa-check-circle';
                                
                                if (($horas_restantes !== null && $horas_restantes <= 0) || 
                                    ($dias_restantes !== null && $dias_restantes <= 0)) {
                                    $estado = 'VENCIDO';
                                    $color = 'danger';
                                    $icono = 'fa-exclamation-triangle';
                                } elseif (($horas_restantes !== null && $horas_restantes <= 100) || 
                                          ($dias_restantes !== null && $dias_restantes <= 30)) {
                                    $estado = 'PRÓXIMO';
                                    $color = 'warning';
                                    $icono = 'fa-clock';
                                }
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($tarea['nombre_tarea']) ?></div>
                                        <?php if (!empty($tarea['descripcion'])): ?>
                                            <small class="text-muted"><?= htmlspecialchars(substr($tarea['descripcion'], 0, 50)) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                            <?= htmlspecialchars($tarea['tipo_mantenimiento']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($tarea['frecuencia_horas'])): ?>
                                            <div><i class="fas fa-clock text-primary me-1"></i> <?= $tarea['frecuencia_horas'] ?> h</div>
                                        <?php endif; ?>
                                        <?php if (!empty($tarea['frecuencia_dias'])): ?>
                                            <div><i class="fas fa-calendar text-info me-1"></i> <?= $tarea['frecuencia_dias'] ?> días</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($tarea['ultimo_mantenimiento_horas'])): ?>
                                            <div class="small"><?= number_format($tarea['ultimo_mantenimiento_horas']) ?> h</div>
                                        <?php endif; ?>
                                        <?php if (!empty($tarea['ultimo_mantenimiento_fecha'])): ?>
                                            <small class="text-muted"><?= date('d/m/Y', strtotime($tarea['ultimo_mantenimiento_fecha'])) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($tarea['proximo_mantenimiento_horas'])): ?>
                                            <div class="fw-semibold"><?= number_format($tarea['proximo_mantenimiento_horas']) ?> h</div>
                                        <?php endif; ?>
                                        <?php if (!empty($tarea['proximo_mantenimiento_fecha'])): ?>
                                            <small class="text-muted"><?= date('d/m/Y', strtotime($tarea['proximo_mantenimiento_fecha'])) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($tarea['tecnico_asignado_id'])): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success">
                                                <i class="fas fa-user-check me-1"></i>
                                                <?= htmlspecialchars($tarea['tecnico_nombre'] ?? 'Técnico') ?>
                                            </span>
                                        <?php elseif (($tarea['responsable'] ?? '') === 'PROVEEDOR_EXTERNO'): ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning">
                                                <i class="fas fa-truck me-1"></i>
                                                <?= htmlspecialchars($tarea['proveedor_nombre'] ?? 'Proveedor') ?>
                                            </span>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalAsignar<?= $tarea['id'] ?>">
                                                <i class="fas fa-user-plus me-1"></i> Asignar
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?> px-3 py-2">
                                            <i class="fas <?= $icono ?> me-1"></i><?= $estado ?>
                                        </span>
                                        <?php if ($horas_restantes !== null): ?>
                                            <div class="small mt-1">
                                                <?= $horas_restantes > 0 ? number_format($horas_restantes) . ' h restantes' : 'Vencido' ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-sm btn-success" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalCompletar<?= $tarea['id'] ?>"
                                                    title="Registrar Mantenimiento">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalEditar<?= $tarea['id'] ?>"
                                                    title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="/proyecto/mantenimiento/eliminar-tarea/<?= $tarea['id'] ?>" 
                                                  method="POST" class="d-inline" 
                                                  onsubmit="return confirm('¿Eliminar esta tarea?')">
                                                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                                                <input type="hidden" name="id_equipo" value="<?= $equipo['id_equipo'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Historial de Horómetro -->
    <?php if (!empty($historial_horometro)): ?>
    <div class="card border-0 mb-4">
        <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-tachometer-alt text-primary me-2"></i> Historial de Horómetro
            </h5>
            <div>
                <span class="badge bg-info me-2"><?= count($historial_horometro) ?> lecturas</span>
                <?php if ($puede_editar_horometro): ?>
                    <span class="badge bg-warning text-dark">
                        <i class="fas fa-edit me-1"></i>Puedes editar
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Horas</th>
                            <th>Diferencia</th>
                            <th>Usuario</th>
                            <th>Observaciones</th>
                            <?php if ($puede_editar_horometro): ?>
                                <th class="text-center">Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial_horometro as $h): ?>
                            <tr>
                                <td><small><?= date('d/m/Y H:i', strtotime($h['fecha_lectura'])) ?></small></td>
                                <td>
                                    <span class="badge bg-primary fs-6">
                                        <?= number_format($h['horas_actuales']) ?> h
                                    </span>
                                </td>
                                <td>
                                    <?php if ($h['horas_desde_ultimo'] > 0): ?>
                                        <span class="text-success">+<?= number_format($h['horas_desde_ultimo']) ?> h</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($h['usuario_nombre'] ?? 'Sistema') ?></td>
                                <td><small><?= htmlspecialchars($h['observaciones'] ?? '') ?></small></td>
                                <?php if ($puede_editar_horometro): ?>
                                    <td>
                                        <div class="d-flex justify-content-center gap-1">
                                            <button class="btn btn-sm btn-outline-warning" 
                                                    title="Editar lectura"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalEditarHorometro<?= $h['id'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <?php if ($puede_eliminar_horometro): ?>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        title="Eliminar lectura"
                                                        onclick="confirmarEliminarHorometro(<?= $h['id'] ?>, <?= $h['horas_actuales'] ?>, <?= $equipo['id_equipo'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Historial de Mantenimientos -->
    <?php if (!empty($historial)): ?>
    <div class="card border-0">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-history text-primary me-2"></i> Historial de Mantenimientos
                <span class="badge bg-primary ms-2"><?= count($historial) ?></span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Horas</th>
                            <th>Técnico/Proveedor</th>
                            <th>Costo</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial as $m): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= date('d/m/Y', strtotime($m['fecha_mantenimiento'])) ?></div>
                                    <small class="text-muted"><?= date('H:i', strtotime($m['fecha_mantenimiento'])) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info">
                                        <?= htmlspecialchars($m['tipo'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($m['descripcion'] ?? '') ?></td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                        <?= number_format($m['horas_equipo'] ?? 0) ?> h
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($m['tecnico_nombre'])): ?>
                                        <i class="fas fa-user-cog text-primary me-1"></i>
                                        <?= htmlspecialchars($m['tecnico_nombre']) ?>
                                    <?php elseif (!empty($m['proveedor_nombre'])): ?>
                                        <i class="fas fa-truck text-warning me-1"></i>
                                        <?= htmlspecialchars($m['proveedor_nombre']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong>S/ <?= number_format($m['costo'] ?? 0, 2) ?></strong>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center">
                                        <a href="/proyecto/mantenimiento/detalle-mantenimiento/<?= $m['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="Ver detalle completo">
                                            <i class="fas fa-eye me-1"></i> Ver
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- ========================================== -->
<!-- MODAL: REGISTRAR HORÓMETRO (CON FECHA) -->
<!-- ========================================== -->
<div class="modal fade" id="modalHorometro" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/proyecto/mantenimiento/horometro/<?= $equipo['id_equipo'] ?>">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-tachometer-alt me-2"></i>Registrar Lectura de Horómetro</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Horómetro actual:</strong> <?= number_format($equipo['horometro_actual'] ?? 0) ?> horas
                        <?php if (!empty($equipo['horometro_ultima_lectura'])): ?>
                            <br>
                            <small>Última lectura: <?= date('d/m/Y H:i', strtotime($equipo['horometro_ultima_lectura'])) ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nuevas horas <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-lg" name="horas_actuales" 
                               min="<?= $equipo['horometro_actual'] ?? 0 ?>" 
                               value="<?= $equipo['horometro_actual'] ?? 0 ?>" required>
                        <small class="text-muted">Debe ser mayor o igual al horómetro actual</small>
                    </div>
                    
                    <!-- ✅ CAMPO DE FECHA -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-calendar-alt text-primary me-1"></i>
                            Fecha de la lectura
                        </label>
                        <input type="datetime-local" class="form-control" name="fecha_lectura" 
                               value="<?= date('Y-m-d\TH:i') ?>"
                               max="<?= date('Y-m-d\TH:i') ?>">
                        <small class="text-muted">
                            Por defecto es ahora. Puedes retroceder la fecha si la lectura se hizo antes.
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Observaciones</label>
                        <textarea class="form-control" name="observaciones" rows="2" 
                                  placeholder="Notas sobre la lectura..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-save me-1"></i> Guardar Lectura
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Nueva Tarea -->
<div class="modal fade" id="modalNuevaTarea" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="/proyecto/mantenimiento/crear-tarea">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                <input type="hidden" name="id_equipo" value="<?= $equipo['id_equipo'] ?>">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Nueva Tarea de Mantenimiento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Nombre de la Tarea <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre_tarea" required
                                   placeholder="Ej: Cambio de aceite, Revisión de correas...">
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Descripción</label>
                            <textarea class="form-control" name="descripcion" rows="2" 
                                      placeholder="Detalles de la tarea..."></textarea>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tipo</label>
                            <select class="form-select" name="tipo_mantenimiento">
                                <option value="PREVENTIVO">Preventivo</option>
                                <option value="PREDICTIVO">Predictivo</option>
                                <option value="CORRECTIVO">Correctivo</option>
                                <option value="CALIBRACION">Calibración</option>
                                <option value="INSPECCION">Inspección</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Frecuencia en Horas</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="frecuencia_horas" 
                                       min="1" placeholder="Ej: 500">
                                <span class="input-group-text">h</span>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Frecuencia en Días</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="frecuencia_dias" 
                                       min="1" placeholder="Ej: 90">
                                <span class="input-group-text">días</span>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Responsable</label>
                            <select class="form-select" name="responsable" id="selectResponsable">
                                <option value="TECNICO_INTERNO">Técnico Interno</option>
                                <option value="PROVEEDOR_EXTERNO">Proveedor Externo</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6" id="divTecnicoAsignado">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-user-check text-success me-1"></i>
                                Asignar Técnico
                            </label>
                            <select class="form-select" name="tecnico_asignado_id">
                                <option value="">-- Sin asignar (asignar después) --</option>
                                <?php foreach ($tecnicos as $tecnico): ?>
                                    <option value="<?= $tecnico['id'] ?>">
                                        <?= htmlspecialchars($tecnico['nombre']) ?> 
                                        (<?= htmlspecialchars($tecnico['especialidad'] ?? 'General') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6" id="divProveedor" style="display: none;">
                            <label class="form-label fw-semibold">Proveedor</label>
                            <select class="form-select" name="id_proveedor">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($proveedores as $prov): ?>
                                    <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Costo Estimado (S/)</label>
                            <input type="number" step="0.01" class="form-control" name="costo_estimado" 
                                   min="0" value="0.00">
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Repuestos Necesarios</label>
                            <input type="text" class="form-control" name="repuestos_necesarios" 
                                   placeholder="Ej: Filtro, aceite 5W-30">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Crear Tarea
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modales por Tarea -->
<?php foreach ($plan as $tarea): ?>

<!-- Modal Completar -->
<div class="modal fade" id="modalCompletar<?= $tarea['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/proyecto/mantenimiento/completar/<?= $tarea['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Registrar Mantenimiento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Tarea:</strong> <?= htmlspecialchars($tarea['nombre_tarea']) ?><br>
                        <strong>Horómetro actual:</strong> <?= number_format($equipo['horometro_actual'] ?? 0) ?> h
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="2"><?= htmlspecialchars($tarea['nombre_tarea']) ?></textarea>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Costo (S/)</label>
                            <input type="number" step="0.01" class="form-control" name="costo" 
                                   value="<?= $tarea['costo_estimado'] ?? 0 ?>" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Repuestos usados</label>
                            <input type="text" class="form-control" name="repuestos_usados">
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <label class="form-label fw-semibold">Observaciones</label>
                        <textarea class="form-control" name="observaciones" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i> Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Asignar Técnico -->
<div class="modal fade" id="modalAsignar<?= $tarea['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/proyecto/mantenimiento/asignar/<?= $tarea['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                <input type="hidden" name="id_equipo" value="<?= $equipo['id_equipo'] ?>">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-user-check me-2"></i>Asignar Técnico</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Tarea:</strong> <?= htmlspecialchars($tarea['nombre_tarea']) ?><br>
                        <strong>Equipo:</strong> <?= htmlspecialchars($equipo['nombre_equipo']) ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Seleccionar Técnico <span class="text-danger">*</span></label>
                        <select class="form-select" name="tecnico_id" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($tecnicos as $tecnico): ?>
                                <option value="<?= $tecnico['id'] ?>">
                                    <?= htmlspecialchars($tecnico['nombre']) ?> 
                                    (<?= htmlspecialchars($tecnico['especialidad'] ?? 'General') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i> Asignar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Tarea -->
<div class="modal fade" id="modalEditar<?= $tarea['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="/proyecto/mantenimiento/actualizar-tarea/<?= $tarea['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                <input type="hidden" name="id_equipo" value="<?= $equipo['id_equipo'] ?>">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Tarea</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Nombre de la Tarea</label>
                            <input type="text" class="form-control" name="nombre_tarea" 
                                   value="<?= htmlspecialchars($tarea['nombre_tarea']) ?>" required>
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Descripción</label>
                            <textarea class="form-control" name="descripcion" rows="2"><?= htmlspecialchars($tarea['descripcion'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tipo</label>
                            <select class="form-select" name="tipo_mantenimiento">
                                <option value="PREVENTIVO" <?= $tarea['tipo_mantenimiento'] === 'PREVENTIVO' ? 'selected' : '' ?>>Preventivo</option>
                                <option value="PREDICTIVO" <?= $tarea['tipo_mantenimiento'] === 'PREDICTIVO' ? 'selected' : '' ?>>Predictivo</option>
                                <option value="CORRECTIVO" <?= $tarea['tipo_mantenimiento'] === 'CORRECTIVO' ? 'selected' : '' ?>>Correctivo</option>
                                <option value="CALIBRACION" <?= $tarea['tipo_mantenimiento'] === 'CALIBRACION' ? 'selected' : '' ?>>Calibración</option>
                                <option value="INSPECCION" <?= $tarea['tipo_mantenimiento'] === 'INSPECCION' ? 'selected' : '' ?>>Inspección</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Frecuencia Horas</label>
                            <input type="number" class="form-control" name="frecuencia_horas" 
                                   value="<?= $tarea['frecuencia_horas'] ?? '' ?>" min="0">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Frecuencia Días</label>
                            <input type="number" class="form-control" name="frecuencia_dias" 
                                   value="<?= $tarea['frecuencia_dias'] ?? '' ?>" min="0">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Costo Estimado</label>
                            <input type="number" step="0.01" class="form-control" name="costo_estimado" 
                                   value="<?= $tarea['costo_estimado'] ?? 0 ?>" min="0">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Estado</label>
                            <select class="form-select" name="activo">
                                <option value="1" <?= ($tarea['activo'] ?? 1) == 1 ? 'selected' : '' ?>>Activa</option>
                                <option value="0" <?= ($tarea['activo'] ?? 1) == 0 ? 'selected' : '' ?>>Inactiva</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-1"></i> Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endforeach; ?>

<!-- ========================================== -->
<!-- MODALES EDITAR HORÓMETRO (CON FECHA) -->
<!-- ========================================== -->
<?php if ($puede_editar_horometro): ?>
    <?php foreach ($historial_horometro as $h): ?>
    <div class="modal fade" id="modalEditarHorometro<?= $h['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="/proyecto/mantenimiento/editar-horometro/<?= $h['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                    <input type="hidden" name="id_equipo" value="<?= $equipo['id_equipo'] ?>">
                    
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2"></i>Editar Lectura de Horómetro
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-6">
                                    <strong>Equipo:</strong><br>
                                    <?= htmlspecialchars($equipo['nombre_equipo']) ?>
                                </div>
                                <div class="col-6">
                                    <strong>Fecha actual:</strong><br>
                                    <?= date('d/m/Y H:i', strtotime($h['fecha_lectura'])) ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Valor actual</label>
                            <div class="form-control form-control-lg bg-light" readonly>
                                <strong><?= number_format($h['horas_actuales']) ?> horas</strong>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Nuevo valor <span class="text-danger">*</span>
                            </label>
                            <div class="input-group input-group-lg">
                                <input type="number" class="form-control" 
                                       name="horas_actuales" 
                                       value="<?= $h['horas_actuales'] ?>"
                                       min="0" required>
                                <span class="input-group-text">h</span>
                            </div>
                        </div>
                        
                        <!-- ✅ CAMPO DE FECHA EDITABLE -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-calendar-alt text-primary me-1"></i>
                                Nueva fecha de la lectura
                            </label>
                            <input type="datetime-local" class="form-control" name="fecha_lectura" 
                                   value="<?= date('Y-m-d\TH:i', strtotime($h['fecha_lectura'])) ?>"
                                   max="<?= date('Y-m-d\TH:i') ?>">
                            <small class="text-muted">Cambia la fecha si fue registrada incorrectamente</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Motivo de la corrección <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" name="observaciones" rows="3"
                                      placeholder="Ej: Se ingresó 5000 por error, el valor correcto es 500..."
                                      required><?= htmlspecialchars($h['observaciones'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-1"></i> Guardar Corrección
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- ========================================== -->
<!-- ESTILOS - TEMA AZUL BRILLANTE UNIFICADO -->
<!-- ========================================== -->
<style>
/* Tarjetas de estadísticas con azul brillante */
.stat-card-modern {
    background: var(--bg-card) !important;
    border: 1px solid var(--border-color) !important;
    border-radius: 16px !important;
    padding: 20px 24px !important;
    box-shadow: 0 2px 12px var(--shadow-color) !important;
    transition: all 0.3s ease !important;
    height: 100% !important;
}
.stat-card-modern:hover {
    transform: translateY(-4px) !important;
    box-shadow: 0 8px 30px var(--shadow-hover) !important;
    border-color: rgba(59, 130, 246, 0.4) !important;
}
.stat-card-modern .stat-icon-modern {
    width: 56px !important;
    height: 56px !important;
    border-radius: 14px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(96, 165, 250, 0.1)) !important;
    color: #3b82f6 !important;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.15) !important;
}
.stat-card-modern .stat-label-modern {
    font-size: 0.7rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    color: var(--text-secondary) !important;
    font-weight: 600 !important;
    display: block !important;
}
.stat-card-modern .stat-number-modern {
    font-size: 1.6rem !important;
    font-weight: 700 !important;
    color: var(--text-primary) !important;
    display: block !important;
    line-height: 1.2 !important;
}

/* Modo oscuro: azul brillante con glow */
[data-theme="dark"] .stat-card-modern .stat-icon-modern {
    color: #60a5fa !important;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(96, 165, 250, 0.1)) !important;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.25) !important;
}

/* Cards */
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
}

/* Badges con borde en modo oscuro */
[data-theme="dark"] .badge {
    border: 1px solid transparent;
}

/* Modales en modo oscuro */
[data-theme="dark"] .modal-content {
    background: #131320 !important;
    border: 1px solid #2a2a45 !important;
}

[data-theme="dark"] .modal-header {
    background: #1a1a2e !important;
    border-bottom: 1px solid #2a2a45 !important;
}

[data-theme="dark"] .modal-footer {
    background: #1a1a2e !important;
    border-top: 1px solid #2a2a45 !important;
}

/* Inputs de fecha en modo oscuro */
[data-theme="dark"] input[type="datetime-local"] {
    background: #0f0f1a !important;
    border: 1px solid #2a2a45 !important;
    color: #ffffff !important;
    color-scheme: dark;
}

[data-theme="dark"] input[type="datetime-local"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
    cursor: pointer;
}

/* Alert info dentro de modal en modo oscuro */
[data-theme="dark"] .alert-info {
    background: #0c2340 !important;
    color: #93c5fd !important;
    border-left: 4px solid #3b82f6 !important;
}
</style>

<script>
// Mostrar/ocultar proveedor según responsable
document.addEventListener('DOMContentLoaded', function() {
    const selectResponsable = document.getElementById('selectResponsable');
    const divProveedor = document.getElementById('divProveedor');
    const divTecnico = document.getElementById('divTecnicoAsignado');
    
    if (selectResponsable) {
        selectResponsable.addEventListener('change', function() {
            if (this.value === 'PROVEEDOR_EXTERNO') {
                divProveedor.style.display = 'block';
                divTecnico.style.display = 'none';
            } else {
                divProveedor.style.display = 'none';
                divTecnico.style.display = 'block';
            }
        });
    }
});

// Confirmar eliminación de lectura de horómetro
function confirmarEliminarHorometro(lectura_id, horas, id_equipo) {
    if (!confirm(`⚠️ ¿Estás seguro de eliminar esta lectura de ${horas} horas?\n\nEsta acción no se puede deshacer.`)) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/proyecto/mantenimiento/eliminar-horometro/' + lectura_id;
    form.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
        <input type="hidden" name="id_equipo" value="${id_equipo}">
    `;
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>