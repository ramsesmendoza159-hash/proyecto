<?php
// views/backdating/index.php
// Listado de registros de backdating - VERSIÓN ESTANDARIZADA
// ✅ FIX NUEVO: contenedor .table-actions + btn-icon

if (!isset($seccion)) $seccion = 'backdating';
if (!isset($titulo)) $titulo = 'Registros Retroactivos';

$registros = $registros ?? [];
$estadisticas = $estadisticas ?? ['total' => 0, 'pendientes' => 0, 'aprobados' => 0, 'rechazados' => 0, 'hoy' => 0];
$filtros = $filtros ?? [];

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-history text-warning me-2"></i>Registros Retroactivos (Backdating)
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Auditoría de mantenimientos registrados con fecha pasada
            </p>
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

    <!-- Tarjetas de resumen -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(13,110,253,0.1);color:#0d6efd;">
                        <i class="fas fa-list"></i>
                    </div>
                    <div>
                        <div class="stat-label">Total</div>
                        <div class="stat-number-mini"><?= $estadisticas['total'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(255,193,7,0.1);color:#ffc107;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <div class="stat-label">Pendientes</div>
                        <div class="stat-number-mini"><?= $estadisticas['pendientes'] ?></div>
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
                        <div class="stat-label">Aprobados</div>
                        <div class="stat-number-mini"><?= $estadisticas['aprobados'] ?></div>
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
                        <div class="stat-label">Rechazados</div>
                        <div class="stat-number-mini"><?= $estadisticas['rechazados'] ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Estado</label>
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="pendiente" <?= ($filtros['estado'] ?? '') === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="aprobado" <?= ($filtros['estado'] ?? '') === 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                        <option value="rechazado" <?= ($filtros['estado'] ?? '') === 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Tipo</label>
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="mantenimiento_preventivo" <?= ($filtros['tipo'] ?? '') === 'mantenimiento_preventivo' ? 'selected' : '' ?>>Mantenimiento Preventivo</option>
                        <option value="orden" <?= ($filtros['tipo'] ?? '') === 'orden' ? 'selected' : '' ?>>Orden de Trabajo</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control form-control-sm" value="<?= htmlspecialchars($filtros['fecha_desde'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="<?= htmlspecialchars($filtros['fecha_hasta'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search me-1"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Equipo</th>
                            <th>Fecha Original</th>
                            <th>Fecha Nueva</th>
                            <th>Solicitante</th>
                            <th>Estado</th>
                            <th class="text-center" style="width:100px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($registros)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                    No hay registros de backdating
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($registros as $r): ?>
                                <tr>
                                    <td>#<?= $r['id'] ?></td>
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                            <?= htmlspecialchars(str_replace('_', ' ', $r['tipo'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($r['nombre_equipo'])): ?>
                                            <div class="fw-semibold"><?= htmlspecialchars($r['nombre_equipo']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($r['equipo_codigo'] ?? '') ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?= date('d/m/Y H:i', strtotime($r['fecha_original'])) ?></small>
                                    </td>
                                    <td>
                                        <small class="fw-semibold"><?= date('d/m/Y H:i', strtotime($r['fecha_nueva'])) ?></small>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($r['solicitante_nombre'] ?? 'N/A') ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($r['rol_solicitante'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $estadoColor = match($r['estado']) {
                                            'aprobado' => 'success',
                                            'rechazado' => 'danger',
                                            default => 'warning'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $estadoColor ?> bg-opacity-10 text-<?= $estadoColor ?> px-3 py-2">
                                            <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                            <?= ucfirst($r['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="/proyecto/backdating/ver/<?= $r['id'] ?>" 
                                               class="btn btn-sm btn-outline-info btn-icon" 
                                               title="Ver"
                                               aria-label="Ver registro">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3 text-muted small">
                <i class="fas fa-list me-1"></i> Mostrando <?= count($registros) ?> registro(s)
            </div>
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
    transform: translateY(-2px);
    box-shadow: 0 4px 16px var(--shadow-hover);
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
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>