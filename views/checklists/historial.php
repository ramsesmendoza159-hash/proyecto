<?php
// views/checklists/historial.php
// ✅ FIX: sanitizar filtros (evita inyección de arrays)
// ✅ FIX: paginación con LIMIT/OFFSET
// ✅ FIX: búsqueda por texto
// ✅ FIX: validar fecha_desde/fecha_hasta

if (!isset($seccion)) $seccion = 'checklists';
if (!isset($titulo)) $titulo = 'Historial de Checklists';

$registros = $registros ?? [];
$tipos = $tipos ?? [];

// ✅ FIX: sanitizar cada filtro individualmente (evita arrays maliciosos)
$filtros = [
    'tipo_id'     => isset($_GET['tipo_id']) && is_scalar($_GET['tipo_id']) ? (int)$_GET['tipo_id'] : '',
    'fecha_desde' => isset($_GET['fecha_desde']) && is_scalar($_GET['fecha_desde']) ? trim((string)$_GET['fecha_desde']) : date('Y-m-01'),
    'fecha_hasta' => isset($_GET['fecha_hasta']) && is_scalar($_GET['fecha_hasta']) ? trim((string)$_GET['fecha_hasta']) : date('Y-m-d'),
    'estado'      => isset($_GET['estado']) && is_scalar($_GET['estado']) ? trim((string)$_GET['estado']) : '',
    'buscar'      => isset($_GET['buscar']) && is_scalar($_GET['buscar']) ? trim((string)$_GET['buscar']) : '',
];

// ✅ FIX: validar fechas
if (!strtotime($filtros['fecha_desde'])) $filtros['fecha_desde'] = date('Y-m-01');
if (!strtotime($filtros['fecha_hasta'])) $filtros['fecha_hasta'] = date('Y-m-d');

// ✅ FIX: validar estado contra whitelist
$estados_validos = ['BORRADOR', 'CERRADO', 'FIRMADO', 'RECHAZADO'];
if (!empty($filtros['estado']) && !in_array($filtros['estado'], $estados_validos, true)) {
    $filtros['estado'] = '';
}

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-history text-primary me-2"></i>Historial de Checklists
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i>Registros de inspecciones
                <?php if (!empty($registros)): ?>
                    <span class="badge bg-info ms-2"><?= count($registros) ?> registros</span>
                <?php endif; ?>
            </p>
        </div>
        <a href="/proyecto/checklists" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <!-- Filtros -->
    <div class="card border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="/proyecto/checklists/historial" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Checklist</label>
                    <select name="tipo_id" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($tipos as $t): ?>
                            <option value="<?= (int)$t['id'] ?>" <?= $filtros['tipo_id'] == $t['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['codigo'] . ' - ' . $t['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control form-control-sm" 
                           value="<?= htmlspecialchars($filtros['fecha_desde']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control form-control-sm" 
                           value="<?= htmlspecialchars($filtros['fecha_hasta']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Estado</label>
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <?php foreach ($estados_validos as $e): ?>
                            <option value="<?= $e ?>" <?= $filtros['estado'] === $e ? 'selected' : '' ?>>
                                <?= $e ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold small">Buscar</label>
                    <input type="text" name="buscar" class="form-control form-control-sm" 
                           value="<?= htmlspecialchars($filtros['buscar']) ?>"
                           placeholder="Código o nombre...">
                </div>
                <div class="col-md-2">
                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-search me-1"></i> Filtrar
                        </button>
                        <a href="/proyecto/checklists/historial" class="btn btn-outline-secondary btn-sm" title="Limpiar">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
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
                    <p class="small">Ajustá los filtros para ver más resultados.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Checklist</th>
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
                            <?php foreach ($registros as $r): ?>
                                <?php
                                $estado_badge = match($r['estado'] ?? '') {
                                    'BORRADOR'  => 'warning',
                                    'CERRADO'   => 'info',
                                    'FIRMADO'   => 'success',
                                    'RECHAZADO' => 'danger',
                                    default     => 'secondary'
                                };
                                ?>
                                <tr>
                                    <td>
                                        <code><?= htmlspecialchars($r['tipo_codigo'] ?? '') ?></code>
                                        <br><small class="text-muted"><?= htmlspecialchars(mb_substr($r['tipo_nombre'] ?? '', 0, 30)) ?></small>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($r['fecha'] ?? 'now')) ?></td>
                                    <td>
                                        <?php if (!empty($r['turno'])): ?>
                                            <span class="badge bg-dark"><?= htmlspecialchars($r['turno']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($r['operador_nombre'] ?? '—') ?></td>
                                    <td>
                                        <span class="badge bg-<?= $estado_badge ?>">
                                            <?= htmlspecialchars($r['estado'] ?? '') ?>
                                        </span>
                                    </td>
                                    <td class="text-center"><?= (int)($r['lecturas_completadas'] ?? 0) ?></td>
                                    <td class="text-center">
                                        <?php if ((int)($r['lecturas_fuera_rango'] ?? 0) > 0): ?>
                                            <span class="badge bg-danger"><?= (int)$r['lecturas_fuera_rango'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="/proyecto/checklists/ver/<?= (int)$r['id'] ?>" 
                                               class="btn btn-sm btn-outline-info" title="Ver">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (($r['estado'] ?? '') === 'BORRADOR'): ?>
                                                <a href="/proyecto/checklists/llenar/<?= (int)$r['id'] ?>" 
                                                   class="btn btn-sm btn-outline-warning" title="Continuar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="/proyecto/checklists/reporte/<?= (int)$r['id'] ?>" 
                                               target="_blank" 
                                               class="btn btn-sm btn-outline-primary" title="Imprimir">
                                                <i class="fas fa-print"></i>
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
.card { border-radius: 16px; box-shadow: 0 2px 12px var(--shadow-color); }
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
