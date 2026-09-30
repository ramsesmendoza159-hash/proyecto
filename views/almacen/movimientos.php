<?php
// views/almacen/movimientos.php
// Historial de Movimientos - VERSIÓN CORREGIDA
// ✅ FIX: $seccion = 'movimientos' (antes 'almacen')

if (!isset($movimientos)) {
    $movimientos = [];
}

// ✅ FIX: Sección correcta para resaltar en el sidebar
$seccion = 'movimientos';
$titulo = 'Historial de Movimientos';

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-history text-info me-2"></i>Historial de Movimientos
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Registro completo de entradas y salidas
                <?php if (!empty($movimientos) && is_array($movimientos)): ?>
                    <span class="badge bg-info ms-2"><?= count($movimientos) ?> movimientos</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/proyecto/almacen" class="btn btn-secondary btn-header-action">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <button onclick="window.location.reload()" class="btn btn-outline-primary btn-header-action">
                <i class="fas fa-sync-alt"></i> Actualizar
            </button>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['mensaje']) && !empty($_SESSION['mensaje'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['mensaje']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['mensaje']); ?>
        </div>
    <?php endif; ?>

    <!-- Resumen rápido -->
    <?php if (!empty($movimientos) && is_array($movimientos)): 
        $entradas = 0;
        $salidas = 0;
        foreach ($movimientos as $mov) {
            if (isset($mov['tipo']) && $mov['tipo'] === 'entrada') {
                $entradas++;
            } else {
                $salidas++;
            }
        }
    ?>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(13,110,253,0.1);color:#0d6efd;">
                        <i class="fas fa-list"></i>
                    </div>
                    <div>
                        <div class="stat-label">Total Movimientos</div>
                        <div class="stat-number-mini"><?= count($movimientos) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(25,135,84,0.1);color:#198754;">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div>
                        <div class="stat-label">Entradas</div>
                        <div class="stat-number-mini"><?= $entradas ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(220,53,69,0.1);color:#dc3545;">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div>
                        <div class="stat-label">Salidas</div>
                        <div class="stat-number-mini"><?= $salidas ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(13,202,240,0.1);color:#0dcaf0;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <div class="stat-label">Usuarios Distintos</div>
                        <div class="stat-number-mini"><?= count(array_unique(array_column($movimientos, 'usuario'))) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabla de movimientos -->
    <div class="card border-0">
        <div class="card-body p-0">
            <?php if (empty($movimientos) || !is_array($movimientos) || count($movimientos) === 0): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                    <h5>No hay movimientos registrados</h5>
                    <p class="text-muted">Registra una entrada o salida para comenzar</p>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="/proyecto/almacen/entrada_seleccion" class="btn btn-success">
                            <i class="fas fa-arrow-down me-1"></i> Registrar Entrada
                        </a>
                        <a href="/proyecto/almacen/salida_seleccion" class="btn btn-danger">
                            <i class="fas fa-arrow-up me-1"></i> Registrar Salida
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Producto</th>
                                <th>Código</th>
                                <th style="width:100px;">Tipo</th>
                                <th style="width:80px;">Cantidad</th>
                                <th>Usuario</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientos as $index => $mov): ?>
                                <tr>
                                    <td><span class="fw-semibold"><?= $index + 1 ?></span></td>
                                    <td>
                                        <?php if (!empty($mov['inventario_id'])): ?>
                                            <a href="/proyecto/almacen/detalle/<?= $mov['inventario_id'] ?>" class="text-decoration-none fw-semibold">
                                                <?= htmlspecialchars($mov['nombre_producto'] ?? 'Producto eliminado') ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="fw-semibold"><?= htmlspecialchars($mov['nombre_producto'] ?? 'Producto eliminado') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?= htmlspecialchars($mov['codigo_producto'] ?? 'N/A') ?></code></td>
                                    <td>
                                        <?php if (isset($mov['tipo']) && $mov['tipo'] === 'entrada'): ?>
                                            <span class="badge bg-success px-3 py-2">
                                                <i class="fas fa-arrow-down me-1"></i> Entrada
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger px-3 py-2">
                                                <i class="fas fa-arrow-up me-1"></i> Salida
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold text-center"><?= $mov['cantidad'] ?? 0 ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-sm" style="width:28px;height:28px;border-radius:50%;background:#6c757d;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:0.7rem;">
                                                <?= strtoupper(substr($mov['usuario'] ?? 'S', 0, 1)) ?>
                                            </div>
                                            <?= htmlspecialchars($mov['usuario'] ?? 'Sistema') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <small><?= date('d/m/Y H:i:s', strtotime($mov['fecha'] ?? 'now')) ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-transparent d-flex justify-content-between">
                    <span class="text-muted small">
                        <i class="fas fa-list me-1"></i> Mostrando <?= count($movimientos) ?> movimiento(s)
                    </span>
                    <?php if (count($movimientos) >= 50): ?>
                        <span class="text-warning small">
                            <i class="fas fa-info-circle me-1"></i> Mostrando últimos 50 movimientos
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Estilos -->
<style>
.stat-card-mini {
    background: var(--bg-card) !important;
    border: 1px solid var(--border-color) !important;
    border-radius: 12px !important;
    padding: 16px 20px !important;
    transition: all 0.3s ease !important;
}
.stat-card-mini:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 8px 25px var(--shadow-hover) !important;
}
.stat-card-mini .stat-icon-mini {
    width: 44px !important;
    height: 44px !important;
    border-radius: 10px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 1.2rem !important;
    flex-shrink: 0 !important;
}
.stat-card-mini .stat-label {
    font-size: 0.65rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    color: var(--text-secondary) !important;
    font-weight: 600 !important;
}
.stat-card-mini .stat-number-mini {
    font-size: 1.5rem !important;
    font-weight: 700 !important;
    color: var(--text-primary) !important;
    line-height: 1.2 !important;
}
.avatar-sm {
    width: 28px !important;
    height: 28px !important;
    border-radius: 50% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-weight: 600 !important;
    font-size: 0.7rem !important;
    flex-shrink: 0 !important;
}
.card {
    border-radius: 16px !important;
    box-shadow: 0 2px 12px var(--shadow-color) !important;
}
.badge {
    font-weight: 500 !important;
}
.table th {
    font-weight: 600 !important;
    font-size: 0.75rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    color: var(--text-secondary) !important;
}
.table td {
    color: var(--text-primary) !important;
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
