<?php
// views/monitoreo/index.php
// Lista de rutas de monitoreo - VERSIÓN ESTANDARIZADA
// ✅ FIX NUEVO: contenedor .table-actions + btn-icon + btn-header-action

if (!isset($seccion)) $seccion = 'monitoreo';
if (!isset($titulo)) $titulo = 'Monitoreo de Equipos';

$rutas         = $rutas ?? [];
$stats_global  = $stats_global ?? [];
$registros_hoy = $registros_hoy ?? [];
$rol           = $_SESSION['rol'] ?? '';
$es_admin_sup  = in_array($rol, ['admin', 'supervisor'], true);

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-check text-primary me-2"></i>Monitoreo de Equipos
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i>
                Rutas de inspección periódica de equipos
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/proyecto/monitoreo/mis-rutas" class="btn btn-primary btn-header-action">
                <i class="fas fa-play-circle"></i> Mis Rutas
            </a>
            <?php if ($es_admin_sup): ?>
                <a href="/proyecto/monitoreo/crear" class="btn btn-outline-primary btn-header-action">
                    <i class="fas fa-plus-circle"></i> Nueva Ruta
                </a>
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

    <!-- Estadísticas del mes -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(13,110,253,0.1);color:#0d6efd;">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div>
                        <div class="stat-label">Registros del mes</div>
                        <div class="stat-number-mini"><?= (int)($stats_global['total_registros'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(25,135,84,0.1);color:#198754;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-label">Lecturas tomadas</div>
                        <div class="stat-number-mini"><?= (int)($stats_global['total_lecturas'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(255,193,7,0.1);color:#ffc107;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="stat-label">Alertas bajas</div>
                        <div class="stat-number-mini"><?= (int)($stats_global['lecturas_bajas'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(220,53,69,0.1);color:#dc3545;">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div>
                        <div class="stat-label">Críticas</div>
                        <div class="stat-number-mini"><?= (int)($stats_global['lecturas_criticas'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Listado de rutas -->
    <div class="card border-0">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-list text-primary me-2"></i>Rutas Activas
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($rutas)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <h5>No hay rutas configuradas</h5>
                    <p class="text-muted small">Contactá al administrador para crear la primera ruta.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Tipo / Unidad</th>
                                <th class="text-center">Equipos</th>
                                <th class="text-center">Horarios</th>
                                <th class="text-center" style="width:150px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rutas as $r): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($r['codigo'] ?? 'N/A') ?></code></td>
                                    <td>
                                        <strong><?= htmlspecialchars($r['nombre'] ?? '') ?></strong>
                                        <?php if (!empty($r['descripcion'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars(substr($r['descripcion'], 0, 80)) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                            <?= htmlspecialchars($r['tipo_valor'] ?? 'N/A') ?> (<?= htmlspecialchars($r['unidad_medida'] ?? '') ?>)
                                        </span>
                                    </td>
                                    <td class="text-center"><span class="badge bg-info bg-opacity-10 text-info"><?= (int)($r['total_equipos'] ?? 0) ?></span></td>
                                    <td class="text-center"><span class="badge bg-primary bg-opacity-10 text-primary"><?= (int)($r['total_horarios'] ?? 0) ?></span></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="/proyecto/monitoreo/mis-rutas" 
                                               class="btn btn-sm btn-outline-info btn-icon" 
                                               title="Ver"
                                               aria-label="Ver rutas">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($es_admin_sup): ?>
                                                <a href="/proyecto/monitoreo/editar/<?= (int)$r['id'] ?>" 
                                                   class="btn btn-sm btn-outline-warning btn-icon" 
                                                   title="Editar"
                                                   aria-label="Editar ruta">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="/proyecto/monitoreo/historial?ruta_id=<?= (int)$r['id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary btn-icon" 
                                                   title="Historial"
                                                   aria-label="Ver historial">
                                                    <i class="fas fa-history"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-transparent">
                    <span class="text-muted small">
                        <i class="fas fa-list me-1"></i> Mostrando <?= count($rutas) ?> ruta(s)
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Registros del día -->
    <div class="card border-0 mt-4">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-calendar-day text-primary me-2"></i>Registros de Hoy
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($registros_hoy)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                    No hay registros creados para el día de hoy
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ruta</th>
                                <th>Turno</th>
                                <th>Operador</th>
                                <th>Estado</th>
                                <th class="text-center">Lecturas</th>
                                <th class="text-center" style="width:100px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registros_hoy as $reg): ?>
                                <?php
                                $estado_badge = match($reg['estado'] ?? 'BORRADOR') {
                                    'BORRADOR' => 'warning',
                                    'CERRADO'  => 'info',
                                    'FIRMADO'  => 'success',
                                    'RECHAZADO' => 'danger',
                                    default    => 'secondary'
                                };
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($reg['ruta_codigo'] ?? '') ?> - <?= htmlspecialchars(substr($reg['ruta_nombre'] ?? '', 0, 40)) ?></td>
                                    <td><span class="badge bg-dark"><?= htmlspecialchars($reg['turno'] ?? '') ?></span></td>
                                    <td><?= htmlspecialchars($reg['operador_nombre'] ?? 'Sin asignar') ?></td>
                                    <td><span class="badge bg-<?= $estado_badge ?>"><?= htmlspecialchars($reg['estado'] ?? '') ?></span></td>
                                    <td class="text-center"><?= (int)($reg['lecturas_completadas'] ?? 0) ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="/proyecto/monitoreo/ver/<?= (int)$reg['id'] ?>" 
                                               class="btn btn-sm btn-outline-info btn-icon" 
                                               title="Ver"
                                               aria-label="Ver registro">
                                                <i class="fas fa-eye"></i>
                                            </a>
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

</div>

<style>
.stat-card-mini {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 16px 20px;
    transition: all 0.3s ease;
}
.stat-card-mini:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px var(--shadow-hover);
}
.stat-card-mini .stat-icon-mini {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}
.stat-card-mini .stat-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
    font-weight: 600;
}
.stat-card-mini .stat-number-mini {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.2;
}
.card { border-radius: 16px; box-shadow: 0 2px 12px var(--shadow-color); }
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>