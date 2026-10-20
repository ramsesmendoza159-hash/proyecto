<?php
// views/almacen/permanencia.php
// Control de Permanencia - VERSIÓN FINAL BLINDADA
// ✅ FIX: $seccion = 'permanencia'
// ✅ FIX: blindaje de TODOS los accesos a $item['xxx'] y $resumen['xxx']

if (!isset($seccion)) {
    $seccion = 'permanencia';
}
if (!isset($titulo)) {
    $titulo = 'Control de Permanencia';
}

if (!isset($productos) || !is_array($productos)) {
    $productos = [];
}
if (!isset($resumen) || !is_array($resumen)) {
    $resumen = [
        'total' => 0, 'criticos' => 0, 'normales' => 0,
        'recientes' => 0, 'nuevos' => 0, 'promedio_dias' => 0, 'max_dias' => 0
    ];
}

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clock text-warning me-2"></i>Control de Permanencia
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> 
                Productos con mayor tiempo en almacén sin movimiento
            </p>
        </div>
        <a href="/proyecto/almacen" class="btn btn-secondary btn-header-action">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['mensaje']) && !empty($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?= $_SESSION['mensaje_tipo'] ?? 'success' ?> alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_SESSION['mensaje']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Resumen -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(13,110,253,0.1);color:#0d6efd;">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div>
                        <div class="stat-label">Total Productos</div>
                        <div class="stat-number-mini"><?= number_format($resumen['total'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(220,53,69,0.1);color:#dc3545;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="stat-label">Críticos (>30 días)</div>
                        <div class="stat-number-mini"><?= number_format($resumen['criticos'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(255,193,7,0.1);color:#ffc107;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <div class="stat-label">Normal (15-30 días)</div>
                        <div class="stat-number-mini"><?= number_format($resumen['normales'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(13,202,240,0.1);color:#0dcaf0;">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div>
                        <div class="stat-label">Reciente (7-15 días)</div>
                        <div class="stat-number-mini"><?= number_format($resumen['recientes'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(25,135,84,0.1);color:#198754;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-label">Nuevo (<7 días)</div>
                        <div class="stat-number-mini"><?= number_format($resumen['nuevos'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(108,117,125,0.1);color:#6c757d;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div>
                        <div class="stat-label">Promedio Días</div>
                        <div class="stat-number-mini"><?= number_format($resumen['promedio_dias'] ?? 0, 1) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de productos -->
    <div class="card border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Stock</th>
                            <th>Ubicación</th>
                            <th>Última Entrada</th>
                            <th>Días</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($productos)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                    No hay productos en el inventario
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($productos as $index => $item):
                                // ✅ FIX: blindar TODOS los accesos
                                $it_codigo   = $item['codigo'] ?? 'N/A';
                                $it_nombre   = $item['nombre'] ?? 'N/A';
                                $it_cat      = $item['categoria'] ?? 'General';
                                $it_cant     = (int)($item['cantidad'] ?? 0);
                                $it_ubic     = $item['ubicacion'] ?? 'N/A';
                                $it_fecha    = $item['fecha_ultima_entrada'] ?? null;
                                $it_dias     = (int)($item['dias_permanencia'] ?? 0);
                                $it_estado   = $item['estado_permanencia'] ?? 'Nuevo';

                                $fecha_val = !empty($it_fecha) && strtotime($it_fecha) !== false;

                                $badgeColor = match(true) {
                                    strpos($it_estado, 'Crítico') !== false => 'danger',
                                    strpos($it_estado, 'Normal') !== false => 'warning',
                                    strpos($it_estado, 'Reciente') !== false => 'info',
                                    strpos($it_estado, 'Nuevo') !== false => 'success',
                                    default => 'secondary'
                                };
                            ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><code><?= htmlspecialchars($it_codigo) ?></code></td>
                                    <td><span class="fw-semibold"><?= htmlspecialchars($it_nombre) ?></span></td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            <?= htmlspecialchars($it_cat) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($it_cant <= 0): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger">Agotado</span>
                                        <?php elseif ($it_cant <= 5): ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning"><?= $it_cant ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-success bg-opacity-10 text-success"><?= $it_cant ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($it_ubic) ?></td>
                                    <td>
                                        <?= $fecha_val ? date('d/m/Y', strtotime($it_fecha)) : 'N/A' ?>
                                    </td>
                                    <td class="fw-bold <?= $it_dias >= 30 ? 'text-danger' : ($it_dias >= 15 ? 'text-warning' : 'text-success') ?>">
                                        <?= $it_dias ?> días
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $badgeColor ?> bg-opacity-10 text-<?= $badgeColor ?> px-3 py-2">
                                            <?= htmlspecialchars($it_estado) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-transparent">
                <span class="text-muted small">
                    <i class="fas fa-list me-1"></i> Mostrando <?= count($productos) ?> producto(s)
                </span>
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
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>