<?php
// views/mantenimiento/equipo.php
// Plan de Mantenimiento - VERSIÓN UNIFICADA

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

// ✅ FIX: blindar accesos del equipo
$eq_id      = (int)($equipo['id_equipo'] ?? 0);
$eq_nombre  = $equipo['nombre_equipo'] ?? 'Equipo';
$eq_codigo  = $equipo['codigo'] ?? 'N/A';
$eq_horom   = (int)($equipo['horometro_actual'] ?? 0);
$eq_horas_d = (float)($equipo['horas_diarias_promedio'] ?? 0);
$eq_ultima  = $equipo['horometro_ultima_lectura'] ?? null;

$puede_editar_horometro = in_array($_SESSION['rol'] ?? '', ['admin', 'supervisor']);
$puede_eliminar_horometro = ($_SESSION['rol'] ?? '') === 'admin';

$ultima_valida = !empty($eq_ultima) && strtotime($eq_ultima) !== false;

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
                <strong><?= htmlspecialchars($eq_nombre) ?></strong> 
                | Código: <code><?= htmlspecialchars($eq_codigo) ?></code>
                | Horómetro: <span class="badge bg-primary"><?= number_format($eq_horom) ?> horas</span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/proyecto/mantenimiento/panel" class="btn btn-secondary btn-header-action">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <button class="btn btn-info text-white btn-header-action" data-bs-toggle="modal" data-bs-target="#modalHorometro">
                <i class="fas fa-tachometer-alt"></i> Registrar Horómetro
            </button>
            <button class="btn btn-primary btn-header-action" data-bs-toggle="modal" data-bs-target="#modalNuevaTarea">
                <i class="fas fa-plus"></i> Nueva Tarea
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

    <!-- TARJETAS DE INFO DEL EQUIPO -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern">
                        <i class="fas fa-tachometer-alt fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Horómetro Actual</span>
                        <span class="stat-number-modern"><?= number_format($eq_horom) ?> h</span>
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
                        <span class="stat-number-modern"><?= number_format($eq_horas_d, 1) ?> h/día</span>
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
                                // ✅ FIX: blindar TODOS los accesos
                                $tar_id         = (int)($tarea['id'] ?? 0);
                                $tar_nombre     = $tarea['nombre_tarea'] ?? 'N/A';
                                $tar_desc       = $tarea['descripcion'] ?? '';
                                $tar_tipo       = $tarea['tipo_mantenimiento'] ?? 'N/A';
                                $tar_frec_h     = $tarea['frecuencia_horas'] ?? null;
                                $tar_frec_d     = $tarea['frecuencia_dias'] ?? null;
                                $tar_ult_h      = $tarea['ultimo_mantenimiento_horas'] ?? null;
                                $tar_ult_f      = $tarea['ultimo_mantenimiento_fecha'] ?? null;
                                $tar_prox_h     = $tarea['proximo_mantenimiento_horas'] ?? null;
                                $tar_prox_f     = $tarea['proximo_mantenimiento_fecha'] ?? null;
                                $tar_tec_id     = $tarea['tecnico_asignado_id'] ?? null;
                                $tar_tec_nom    = $tarea['tecnico_nombre'] ?? 'Técnico';
                                $tar_resp       = $tarea['responsable'] ?? 'TECNICO_INTERNO';
                                $tar_prov_nom   = $tarea['proveedor_nombre'] ?? 'Proveedor';
                                $tar_costo_est  = $tarea['costo_estimado'] ?? 0;
                                $tar_horas_r    = $tarea['horas_restantes'] ?? null;
                                $tar_dias_r     = $tarea['dias_restantes'] ?? null;

                                $estado = 'AL_DIA';
                                $color = 'success';
                                $icono = 'fa-check-circle';
                                
                                if (($tar_horas_r !== null && $tar_horas_r <= 0) || 
                                    ($tar_dias_r !== null && $tar_dias_r <= 0)) {
                                    $estado = 'VENCIDO';
                                    $color = 'danger';
                                    $icono = 'fa-exclamation-triangle';
                                } elseif (($tar_horas_r !== null && $tar_horas_r <= 100) || 
                                          ($tar_dias_r !== null && $tar_dias_r <= 30)) {
                                    $estado = 'PRÓXIMO';
                                    $color = 'warning';
                                    $icono = 'fa-clock';
                                }
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($tar_nombre) ?></div>
                                        <?php if (!empty($tar_desc)): ?>
                                            <small class="text-muted"><?= htmlspecialchars(substr($tar_desc, 0, 50)) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                            <?= htmlspecialchars($tar_tipo) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($tar_frec_h)): ?>
                                            <div><i class="fas fa-clock text-primary me-1"></i> <?= $tar_frec_h ?> h</div>
                                        <?php endif; ?>
                                        <?php if (!empty($tar_frec_d)): ?>
                                            <div><i class="fas fa-calendar text-info me-1"></i> <?= $tar_frec_d ?> días</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($tar_ult_h)): ?>
                                            <div class="small"><?= number_format($tar_ult_h) ?> h</div>
                                        <?php endif; ?>
                                        <?php if (!empty($tar_ult_f) && strtotime($tar_ult_f) !== false): ?>
                                            <small class="text-muted"><?= date('d/m/Y', strtotime($tar_ult_f)) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($tar_prox_h)): ?>
                                            <div class="fw-semibold"><?= number_format($tar_prox_h) ?> h</div>
                                        <?php endif; ?>
                                        <?php if (!empty($tar_prox_f) && strtotime($tar_prox_f) !== false): ?>
                                            <small class="text-muted"><?= date('d/m/Y', strtotime($tar_prox_f)) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($tar_tec_id)): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success">
                                                <i class="fas fa-user-check me-1"></i>
                                                <?= htmlspecialchars($tar_tec_nom) ?>
                                            </span>
                                        <?php elseif ($tar_resp === 'PROVEEDOR_EXTERNO'): ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning">
                                                <i class="fas fa-truck me-1"></i>
                                                <?= htmlspecialchars($tar_prov_nom) ?>
                                            </span>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalAsignar<?= $tar_id ?>"
                                                    title="Asignar técnico">
                                                <i class="fas fa-user-plus me-1"></i> Asignar
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?> px-3 py-2">
                                            <i class="fas <?= $icono ?> me-1"></i><?= $estado ?>
                                        </span>
                                        <?php if ($tar_horas_r !== null): ?>
                                            <div class="small mt-1">
                                                <?= $tar_horas_r > 0 ? number_format($tar_horas_r) . ' h restantes' : 'Vencido' ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <button class="btn btn-sm btn-outline-success btn-icon" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalCompletar<?= $tar_id ?>"
                                                    title="Registrar Mantenimiento"
                                                    aria-label="Registrar mantenimiento">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning btn-icon" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalEditar<?= $tar_id ?>"
                                                    title="Editar"
                                                    aria-label="Editar tarea">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="/proyecto/mantenimiento/eliminar-tarea/<?= $tar_id ?>" 
                                                method="POST" class="d-inline" 
                                                onsubmit="return confirm('¿Eliminar esta tarea?')">
                                                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                                                <input type="hidden" name="id_equipo" value="<?= $eq_id ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" 
                                                        title="Eliminar"
                                                        aria-label="Eliminar tarea">
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
                        <?php foreach ($historial_horometro as $h):
                            // ✅ FIX: blindar accesos
                            $h_id       = (int)($h['id'] ?? 0);
                            $h_fecha    = $h['fecha_lectura'] ?? null;
                            $h_horas    = (int)($h['horas_actuales'] ?? 0);
                            $h_dif      = (int)($h['horas_desde_ultimo'] ?? 0);
                            $h_user     = $h['usuario_nombre'] ?? 'Sistema';
                            $h_obs      = $h['observaciones'] ?? '';
                            
                            $h_fecha_valida = !empty($h_fecha) && strtotime($h_fecha) !== false;
                            $h_fecha_fmt = $h_fecha_valida ? date('d/m/Y H:i', strtotime($h_fecha)) : 'N/A';
                        ?>
                            <tr>
                                <td><small><?= $h_fecha_fmt ?></small></td>
                                <td>
                                    <span class="badge bg-primary fs-6">
                                        <?= number_format($h_horas) ?> h
                                    </span>
                                </td>
                                <td>
                                    <?php if ($h_dif > 0): ?>
                                        <span class="text-success">+<?= number_format($h_dif) ?> h</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($h_user) ?></td>
                                <td><small><?= htmlspecialchars($h_obs) ?></small></td>
                                <?php if ($puede_editar_horometro): ?>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-outline-warning btn-icon" 
                                                title="Editar lectura"
                                                aria-label="Editar lectura"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEditarHorometro<?= $h_id ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <?php if ($puede_eliminar_horometro): ?>
                                            <button class="btn btn-sm btn-outline-danger btn-icon" 
                                                    title="Eliminar lectura"
                                                    aria-label="Eliminar lectura"
                                                    onclick="confirmarEliminarHorometro(<?= $h_id ?>, <?= $h_horas ?>, <?= $eq_id ?>)">
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
                        <?php foreach ($historial as $m):
                            // ✅ FIX: blindar accesos
                            $m_id       = (int)($m['id'] ?? 0);
                            $m_fecha    = $m['fecha_mantenimiento'] ?? null;
                            $m_tipo     = $m['tipo'] ?? 'N/A';
                            $m_desc     = $m['descripcion'] ?? '';
                            $m_horas    = (int)($m['horas_equipo'] ?? 0);
                            $m_tec_nom  = $m['tecnico_nombre'] ?? '';
                            $m_prov_nom = $m['proveedor_nombre'] ?? '';
                            $m_costo    = (float)($m['costo'] ?? 0);
                            
                            $m_fecha_valida = !empty($m_fecha) && strtotime($m_fecha) !== false;
                        ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= $m_fecha_valida ? date('d/m/Y', strtotime($m_fecha)) : 'N/A' ?></div>
                                    <?php if ($m_fecha_valida): ?>
                                        <small class="text-muted"><?= date('H:i', strtotime($m_fecha)) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info bg-opacity-10 text-info">
                                        <?= htmlspecialchars($m_tipo) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($m_desc) ?></td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                        <?= number_format($m_horas) ?> h
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($m_tec_nom)): ?>
                                        <i class="fas fa-user-cog text-primary me-1"></i>
                                        <?= htmlspecialchars($m_tec_nom) ?>
                                    <?php elseif (!empty($m_prov_nom)): ?>
                                        <i class="fas fa-truck text-warning me-1"></i>
                                        <?= htmlspecialchars($m_prov_nom) ?>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong>S/ <?= number_format($m_costo, 2) ?></strong>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="/proyecto/mantenimiento/detalle-mantenimiento/<?= $m_id ?>" 
                                        class="btn btn-sm btn-outline-info btn-icon" 
                                        title="Ver detalle"
                                        aria-label="Ver detalle">
                                            <i class="fas fa-eye"></i>
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

<!-- MODAL: REGISTRAR HORÓMETRO -->
<div class="modal fade" id="modalHorometro" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/proyecto/mantenimiento/horometro/<?= $eq_id ?>">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-tachometer-alt me-2"></i>Registrar Lectura de Horómetro</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Horómetro actual:</strong> <?= number_format($eq_horom) ?> horas
                        <?php if ($ultima_valida): ?>
                            <br>
                            <small>Última lectura: <?= date('d/m/Y H:i', strtotime($eq_ultima)) ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nuevas horas <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-lg" name="horas_actuales" 
                               min="<?= $eq_horom ?>" 
                               value="<?= $eq_horom ?>" required>
                        <small class="text-muted">Debe ser mayor o igual al horómetro actual</small>
                    </div>
                    
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
                <input type="hidden" name="id_equipo" value="<?= $eq_id ?>">
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
                            <select class="form-select select-responsable" name="responsable" data-tarea="nueva" id="selectResponsableNueva">
                                <option value="TECNICO_INTERNO">Técnico Interno</option>
                                <option value="PROVEEDOR_EXTERNO">Proveedor Externo</option>
                            </select>
                        </div>

                        <div class="col-md-6 div-tecnico" data-tarea="nueva" id="divTecnicoNueva">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-user-check text-success me-1"></i>
                                Asignar Técnico
                            </label>
                            <select class="form-select" name="tecnico_asignado_id">
                                <option value="">-- Sin asignar (asignar después) --</option>
                                <?php foreach ($tecnicos as $tecnico):
                                    $tec_id = (int)($tecnico['id'] ?? 0);
                                    $tec_nombre = $tecnico['nombre'] ?? '';
                                    $tec_esp = $tecnico['especialidad'] ?? 'General';
                                ?>
                                    <option value="<?= $tec_id ?>">
                                        <?= htmlspecialchars($tec_nombre) ?> 
                                        (<?= htmlspecialchars($tec_esp) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 div-proveedor" data-tarea="nueva" id="divProveedorNueva" style="display: none;">
                            <label class="form-label fw-semibold">Proveedor</label>
                            <select class="form-select" name="id_proveedor">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($proveedores as $prov):
                                    $prov_id = (int)($prov['id'] ?? 0);
                                    $prov_nombre = $prov['nombre'] ?? '';
                                ?>
                                    <option value="<?= $prov_id ?>"><?= htmlspecialchars($prov_nombre) ?></option>
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
<?php foreach ($plan as $tarea):
    $tar_id = (int)($tarea['id'] ?? 0);
    if ($tar_id <= 0) continue;

    $tar_nombre    = $tarea['nombre_tarea'] ?? 'N/A';
    $tar_tipo      = $tarea['tipo_mantenimiento'] ?? 'PREVENTIVO';
    $tar_frec_h    = $tarea['frecuencia_horas'] ?? '';
    $tar_frec_d    = $tarea['frecuencia_dias'] ?? '';
    $tar_tec_id    = $tarea['tecnico_asignado_id'] ?? '';
    $tar_prov_id   = $tarea['id_proveedor'] ?? '';
    $tar_costo_est = $tarea['costo_estimado'] ?? 0;
    $tar_resp      = $tarea['responsable'] ?? 'TECNICO_INTERNO';
    $tar_desc      = $tarea['descripcion'] ?? '';
    $tar_activo    = (int)($tarea['activo'] ?? 1);
?>

<!-- Modal Completar -->
<div class="modal fade" id="modalCompletar<?= $tar_id ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/proyecto/mantenimiento/completar/<?= $tar_id ?>">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-check-circle me-2"></i>Registrar Mantenimiento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Tarea:</strong> <?= htmlspecialchars($tar_nombre) ?><br>
                        <strong>Horómetro actual:</strong> <?= number_format($eq_horom) ?> h
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="2"><?= htmlspecialchars($tar_nombre) ?></textarea>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Costo (S/)</label>
                            <input type="number" step="0.01" class="form-control" name="costo" 
                                   value="<?= $tar_costo_est ?>" min="0">
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
<div class="modal fade" id="modalAsignar<?= $tar_id ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/proyecto/mantenimiento/asignar/<?= $tar_id ?>">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                <input type="hidden" name="id_equipo" value="<?= $eq_id ?>">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-user-check me-2"></i>Asignar Técnico</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Tarea:</strong> <?= htmlspecialchars($tar_nombre) ?><br>
                        <strong>Equipo:</strong> <?= htmlspecialchars($eq_nombre) ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Seleccionar Técnico <span class="text-danger">*</span></label>
                        <select class="form-select" name="tecnico_id" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($tecnicos as $tecnico):
                                $tec_id = (int)($tecnico['id'] ?? 0);
                                $tec_nombre = $tecnico['nombre'] ?? '';
                                $tec_esp = $tecnico['especialidad'] ?? 'General';
                            ?>
                                <option value="<?= $tec_id ?>">
                                    <?= htmlspecialchars($tec_nombre) ?> 
                                    (<?= htmlspecialchars($tec_esp) ?>)
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
<div class="modal fade" id="modalEditar<?= $tar_id ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="/proyecto/mantenimiento/actualizar-tarea/<?= $tar_id ?>">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                <input type="hidden" name="id_equipo" value="<?= $eq_id ?>">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Tarea</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Nombre de la Tarea</label>
                            <input type="text" class="form-control" name="nombre_tarea" 
                                   value="<?= htmlspecialchars($tar_nombre) ?>" required>
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Descripción</label>
                            <textarea class="form-control" name="descripcion" rows="2"><?= htmlspecialchars($tar_desc) ?></textarea>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tipo</label>
                            <select class="form-select" name="tipo_mantenimiento">
                                <option value="PREVENTIVO" <?= $tar_tipo === 'PREVENTIVO' ? 'selected' : '' ?>>Preventivo</option>
                                <option value="PREDICTIVO" <?= $tar_tipo === 'PREDICTIVO' ? 'selected' : '' ?>>Predictivo</option>
                                <option value="CORRECTIVO" <?= $tar_tipo === 'CORRECTIVO' ? 'selected' : '' ?>>Correctivo</option>
                                <option value="CALIBRACION" <?= $tar_tipo === 'CALIBRACION' ? 'selected' : '' ?>>Calibración</option>
                                <option value="INSPECCION" <?= $tar_tipo === 'INSPECCION' ? 'selected' : '' ?>>Inspección</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Frecuencia Horas</label>
                            <input type="number" class="form-control" name="frecuencia_horas" 
                                   value="<?= htmlspecialchars($tar_frec_h) ?>" min="0">
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Frecuencia Días</label>
                            <input type="number" class="form-control" name="frecuencia_dias" 
                                   value="<?= htmlspecialchars($tar_frec_d) ?>" min="0">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Responsable</label>
                            <select class="form-select select-responsable" 
                                    name="responsable" 
                                    data-tarea="<?= $tar_id ?>"
                                    id="selectResponsable<?= $tar_id ?>">
                                <option value="TECNICO_INTERNO" <?= $tar_resp === 'TECNICO_INTERNO' ? 'selected' : '' ?>>Técnico Interno</option>
                                <option value="PROVEEDOR_EXTERNO" <?= $tar_resp === 'PROVEEDOR_EXTERNO' ? 'selected' : '' ?>>Proveedor Externo</option>
                            </select>
                        </div>

                        <div class="col-md-6 div-tecnico" 
                             data-tarea="<?= $tar_id ?>" 
                             id="divTecnico<?= $tar_id ?>" 
                             style="<?= $tar_resp === 'PROVEEDOR_EXTERNO' ? 'display:none;' : '' ?>">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-user-check text-success me-1"></i>
                                Asignar Técnico
                            </label>
                            <select class="form-select" name="tecnico_asignado_id">
                                <option value="">-- Sin asignar --</option>
                                <?php foreach ($tecnicos as $tecnico):
                                    $tec_id = (int)($tecnico['id'] ?? 0);
                                    $tec_nombre = $tecnico['nombre'] ?? '';
                                    $tec_esp = $tecnico['especialidad'] ?? 'General';
                                ?>
                                    <option value="<?= $tec_id ?>" 
                                        <?= $tar_tec_id == $tec_id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($tec_nombre) ?> 
                                        (<?= htmlspecialchars($tec_esp) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 div-proveedor" 
                             data-tarea="<?= $tar_id ?>" 
                             id="divProveedor<?= $tar_id ?>" 
                             style="<?= $tar_resp === 'PROVEEDOR_EXTERNO' ? 'display:block;' : 'display:none;' ?>">
                            <label class="form-label fw-semibold">Proveedor</label>
                            <select class="form-select" name="id_proveedor">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($proveedores as $prov):
                                    $prov_id = (int)($prov['id'] ?? 0);
                                    $prov_nombre = $prov['nombre'] ?? '';
                                ?>
                                    <option value="<?= $prov_id ?>"
                                        <?= $tar_prov_id == $prov_id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($prov_nombre) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Costo Estimado</label>
                            <input type="number" step="0.01" class="form-control" name="costo_estimado" 
                                   value="<?= $tar_costo_est ?>" min="0">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Estado</label>
                            <select class="form-select" name="activo">
                                <option value="1" <?= $tar_activo == 1 ? 'selected' : '' ?>>Activa</option>
                                <option value="0" <?= $tar_activo == 0 ? 'selected' : '' ?>>Inactiva</option>
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

<!-- MODALES EDITAR HORÓMETRO -->
<?php if ($puede_editar_horometro):
    foreach ($historial_horometro as $h):
        $h_id = (int)($h['id'] ?? 0);
        if ($h_id <= 0) continue;
        
        $h_horas = (int)($h['horas_actuales'] ?? 0);
        $h_fecha = $h['fecha_lectura'] ?? null;
        $h_obs = $h['observaciones'] ?? '';
        $h_fecha_valida = !empty($h_fecha) && strtotime($h_fecha) !== false;
?>
<div class="modal fade" id="modalEditarHorometro<?= $h_id ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/proyecto/mantenimiento/editar-horometro/<?= $h_id ?>">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                <input type="hidden" name="id_equipo" value="<?= $eq_id ?>">
                
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
                                <?= htmlspecialchars($eq_nombre) ?>
                            </div>
                            <div class="col-6">
                                <strong>Fecha actual:</strong><br>
                                <?= $h_fecha_valida ? date('d/m/Y H:i', strtotime($h_fecha)) : 'N/A' ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Valor actual</label>
                        <div class="form-control form-control-lg bg-light" readonly>
                            <strong><?= number_format($h_horas) ?> horas</strong>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Nuevo valor <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-lg">
                            <input type="number" class="form-control" 
                                   name="horas_actuales" 
                                   value="<?= $h_horas ?>"
                                   min="0" required>
                            <span class="input-group-text">h</span>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-calendar-alt text-primary me-1"></i>
                            Nueva fecha de la lectura
                        </label>
                        <input type="datetime-local" class="form-control" name="fecha_lectura" 
                               value="<?= $h_fecha_valida ? date('Y-m-d\TH:i', strtotime($h_fecha)) : date('Y-m-d\TH:i') ?>"
                               max="<?= date('Y-m-d\TH:i') ?>">
                        <small class="text-muted">Cambia la fecha si fue registrada incorrectamente</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Motivo de la corrección <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" name="observaciones" rows="3"
                                  placeholder="Ej: Se ingresó 5000 por error, el valor correcto es 500..."
                                  required><?= htmlspecialchars($h_obs) ?></textarea>
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
<?php endforeach; endif; ?>

<style>
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
[data-theme="dark"] .stat-card-modern .stat-icon-modern {
    color: #60a5fa !important;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(96, 165, 250, 0.1)) !important;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.25) !important;
}
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
}
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
</style>

<script>
// ✅ Alternar proveedor/técnico en modales
document.addEventListener('DOMContentLoaded', function() {
    const selectsResponsable = document.querySelectorAll('.select-responsable');
    
    selectsResponsable.forEach(function(select) {
        select.addEventListener('change', function() {
            const tareaId = this.dataset.tarea;
            
            const divProveedor = document.querySelector('.div-proveedor[data-tarea="' + tareaId + '"]');
            const divTecnico   = document.querySelector('.div-tecnico[data-tarea="' + tareaId + '"]');
            
            if (!divProveedor || !divTecnico) return;
            
            if (this.value === 'PROVEEDOR_EXTERNO') {
                divProveedor.style.display = 'block';
                divTecnico.style.display = 'none';
            } else {
                divProveedor.style.display = 'none';
                divTecnico.style.display = 'block';
            }
        });
    });
});

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