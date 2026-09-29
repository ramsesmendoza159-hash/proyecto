<?php
// views/monitoreo/historial.php
// Historial de registros de monitoreo con filtros

if (!isset($seccion)) $seccion = 'monitoreo';
if (!isset($titulo)) $titulo = 'Historial de Monitoreo';

$registros = $registros ?? [];
$rutas     = $rutas ?? [];
$stats     = $stats ?? [];
$filtros   = $_GET;

$estado_badge = [
    'BORRADOR'  => 'warning',
    'CERRADO'   => 'info',
    'FIRMADO'   => 'success',
    'RECHAZADO' => 'danger'
];

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-history text-primary me-2"></i>Historial de Monitoreo
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i>Registros de rutas de inspección
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="/proyecto/monitoreo" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <a href="/proyecto/monitoreo/exportar?<?= http_build_query($filtros) ?>" class="btn btn-outline-primary">
                <i class="fas fa-file-csv me-1"></i> Exportar CSV
            </a>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(13,110,253,0.1);color:#0d6efd;">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div>
                        <div class="stat-label">Registros</div>
                        <div class="stat-number-mini"><?= (int)($stats['total_registros'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(25,135,84,0.1);color:#198754;">
                        <i class="fas fa-list"></i>
                    </div>
                    <div>
                        <div class="stat-label">Lecturas</div>
                        <div class="stat-number-mini"><?= (int)($stats['total_lecturas'] ?? 0) ?></div>
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
                        <div class="stat-label">Bajas</div>
                        <div class="stat-number-mini"><?= (int)($stats['lecturas_bajas'] ?? 0) ?></div>
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
                        <div class="stat-number-mini"><?= (int)($stats['lecturas_criticas'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="/proyecto/monitoreo/historial" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Ruta</label>
                    <select name="ruta_id" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <?php foreach ($rutas as $r): ?>
                            <option value="<?= (int)$r['id'] ?>" <?= ($filtros['ruta_id'] ?? '') == $r['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['codigo'] . ' - ' . $r['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control form-control-sm" value="<?= htmlspecialchars($filtros['fecha_desde'] ?? date('Y-m-01')) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="<?= htmlspecialchars($filtros['fecha_hasta'] ?? date('Y-m-d')) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Turno</label>
                    <select name="turno" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="DIA"   <?= ($filtros['turno'] ?? '') === 'DIA'   ? 'selected' : '' ?>>DÍA</option>
                        <option value="TARDE" <?= ($filtros['turno'] ?? '') === 'TARDE' ? 'selected' : '' ?>>TARDE</option>
                        <option value="NOCHE" <?= ($filtros['turno'] ?? '') === 'NOCHE' ? 'selected' : '' ?>>NOCHE</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Estado</label>
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="BORRADOR"  <?= ($filtros['estado'] ?? '') === 'BORRADOR'  ? 'selected' : '' ?>>Borrador</option>
                        <option value="CERRADO"   <?= ($filtros['estado'] ?? '') === 'CERRADO'   ? 'selected' : '' ?>>Cerrado</option>
                        <option value="FIRMADO"   <?= ($filtros['estado'] ?? '') === 'FIRMADO'   ? 'selected' : '' ?>>Firmado</option>
                        <option value="RECHAZADO" <?= ($filtros['estado'] ?? '') === 'RECHAZADO' ? 'selected' : '' ?>>Rechazado</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card border-0">
        <div class="card-body p-0">
            <?php if (empty($registros)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-inbox fa-3x d-block mb-3"></i>
                    <h5>No hay registros</h5>
                    <p>Ajustá los filtros para ver más resultados.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ruta</th>
                                <th>Fecha</th>
                                <th>Turno</th>
                                <th>Operador</th>
                                <th>Estado</th>
                                <th class="text-center">Lecturas</th>
                                <th class="text-center">Alertas</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registros as $reg): ?>
                                <tr>
                                    <td>
                                        <code><?= htmlspecialchars($reg['ruta_codigo'] ?? '') ?></code>
                                        <br><small class="text-muted"><?= htmlspecialchars(substr($reg['ruta_nombre'] ?? '', 0, 30)) ?></small>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($reg['fecha'])) ?></td>
                                    <td><span class="badge bg-dark"><?= htmlspecialchars($reg['turno']) ?></span></td>
                                    <td><?= htmlspecialchars($reg['operador_nombre'] ?? 'Sin asignar') ?></td>
                                    <td>
                                        <span class="badge bg-<?= $estado_badge[$reg['estado']] ?? 'secondary' ?>">
                                            <?= htmlspecialchars($reg['estado']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center"><?= (int)($reg['lecturas_completadas'] ?? 0) ?></td>
                                    <td class="text-center">
                                        <?php if ((int)($reg['lecturas_fuera_rango'] ?? 0) > 0): ?>
                                            <span class="badge bg-danger"><?= (int)$reg['lecturas_fuera_rango'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="/proyecto/monitoreo/ver/<?= (int)$reg['id'] ?>" class="btn btn-sm btn-outline-info" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($reg['estado'] === 'BORRADOR'): ?>
                                                <a href="/proyecto/monitoreo/llenar/<?= (int)$reg['id'] ?>" class="btn btn-sm btn-outline-warning" title="Continuar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (in_array($reg['estado'], ['CERRADO', 'FIRMADO', 'RECHAZADO'], true)): ?>
                                                <a href="/proyecto/monitoreo/firmar/<?= (int)$reg['id'] ?>" class="btn btn-sm btn-outline-primary" title="Firmas">
                                                    <i class="fas fa-signature"></i>
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
                        <i class="fas fa-list me-1"></i>Mostrando <?= count($registros) ?> registro(s)
                    </span>
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
}
.stat-card-mini .stat-icon-mini {
    width: 44px; height: 44px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.stat-card-mini .stat-label { font-size: 0.65rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary); font-weight: 600; }
.stat-card-mini .stat-number-mini { font-size: 1.5rem; font-weight: 700; color: var(--text-primary); line-height: 1.2; }
.card { border-radius: 16px; box-shadow: 0 2px 12px var(--shadow-color); }
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>