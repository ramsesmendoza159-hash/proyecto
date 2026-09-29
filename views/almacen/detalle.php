<?php
// views/almacen/detalle.php
// Detalle de Producto - Almacén

if (!isset($seccion)) {
    $seccion = 'almacen';
}
if (!isset($titulo)) {
    $titulo = 'Detalle de Producto';
}

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-box text-primary me-2"></i><?php echo htmlspecialchars($producto['nombre'] ?? 'Producto'); ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Detalles del producto y movimientos
            </p>
        </div>
        <div>
            <a href="/proyecto/almacen" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
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

    <?php if (isset($producto) && $producto): ?>
    <div class="row g-4">
        <!-- Columna Izquierda: Información del producto -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <?php if (!empty($producto['imagen'])): ?>
                            <img src="/proyecto/uploads/inventario/<?php echo $producto['imagen']; ?>" 
                                 alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                 class="img-fluid rounded" style="max-height: 250px; width: auto;">
                        <?php else: ?>
                            <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                 style="height: 200px; width: 100%;">
                                <i class="fas fa-box fa-4x text-muted"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Código SKU:</span>
                            <span class="fw-semibold"><?php echo htmlspecialchars($producto['codigo'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Categoría:</span>
                            <span class="fw-semibold"><?php echo htmlspecialchars($producto['categoria'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Unidad:</span>
                            <span class="fw-semibold"><?php echo htmlspecialchars($producto['unidad'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Ubicación:</span>
                            <span class="fw-semibold"><?php echo htmlspecialchars($producto['ubicacion'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Proveedor:</span>
                            <span class="fw-semibold"><?php echo htmlspecialchars($producto['proveedor'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Estado:</span>
                            <span class="badge <?php echo ($producto['estado'] ?? 'inactivo') === 'activo' ? 'bg-success' : 'bg-danger'; ?>">
                                <?php echo ucfirst($producto['estado'] ?? 'inactivo'); ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Última Entrada:</span>
                            <span class="fw-semibold">
                                <?php if ($producto['fecha_ultima_entrada']): ?>
                                    <?php echo date('d/m/Y H:i', strtotime($producto['fecha_ultima_entrada'])); ?>
                                <?php else: ?>
                                    Sin registro
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Días en almacén:</span>
                            <span class="fw-semibold <?php echo ($producto['dias_permanencia'] ?? 0) >= 30 ? 'text-danger' : (($producto['dias_permanencia'] ?? 0) >= 15 ? 'text-warning' : 'text-success'); ?>">
                                <?php echo $producto['dias_permanencia'] ?? 0; ?> días
                            </span>
                        </div>
                    </div>

                    <div class="border-top pt-3 mt-3">
                        <div class="row text-center g-3">
                            <div class="col-6">
                                <div class="bg-light rounded p-3">
                                    <div class="text-muted small">Stock Actual</div>
                                    <div class="h4 fw-bold <?php echo ($producto['cantidad'] ?? 0) <= ($producto['stock_minimo'] ?? 0) ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo $producto['cantidad'] ?? 0; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-light rounded p-3">
                                    <div class="text-muted small">Stock Mínimo</div>
                                    <div class="h4 fw-bold text-warning">
                                        <?php echo $producto['stock_minimo'] ?? 0; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ACCIONES RÁPIDAS -->
                    <div class="d-grid gap-2 mt-3">
                        <a href="/proyecto/almacen/entrada/<?php echo $producto['id']; ?>" class="btn btn-success">
                            <i class="fas fa-arrow-down me-1"></i> Registrar Entrada
                        </a>
                        <?php if (($producto['cantidad'] ?? 0) > 0): ?>
                            <a href="/proyecto/almacen/salida/<?php echo $producto['id']; ?>" class="btn btn-danger">
                                <i class="fas fa-arrow-up me-1"></i> Registrar Salida
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                <i class="fas fa-arrow-up me-1"></i> Sin Stock
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Historial de movimientos -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-history text-info me-2"></i> Historial de Movimientos
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($movimientos)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x d-block mb-3"></i>
                            <p>No hay movimientos registrados para este producto</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Cantidad</th>
                                        <th>Usuario</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($movimientos as $mov): ?>
                                        <tr>
                                            <td>
                                                <span class="badge <?php echo $mov['tipo'] === 'entrada' ? 'bg-success' : 'bg-danger'; ?>">
                                                    <i class="fas <?php echo $mov['tipo'] === 'entrada' ? 'fa-arrow-down' : 'fa-arrow-up'; ?> me-1"></i>
                                                    <?php echo ucfirst($mov['tipo']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $mov['cantidad']; ?></td>
                                            <td><?php echo htmlspecialchars($mov['usuario'] ?? 'Sistema'); ?></td>
                                            <td><small><?php echo date('d/m/Y H:i:s', strtotime($mov['fecha'])); ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Descripciones -->
            <?php if (!empty($producto['descripcion']) || !empty($producto['descripcion_tecnica'])): ?>
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body">
                        <?php if (!empty($producto['descripcion'])): ?>
                            <h6 class="fw-semibold">
                                <i class="fas fa-align-left me-2 text-primary"></i> Descripción General
                            </h6>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($producto['descripcion_tecnica'])): ?>
                            <h6 class="fw-semibold mt-3">
                                <i class="fas fa-microscope me-2 text-primary"></i> Descripción Técnica
                            </h6>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($producto['descripcion_tecnica'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Producto no encontrado.
        </div>
    <?php endif; ?>

</div>

<style>
.card {
    border-radius: 16px;
}
.shadow-sm {
    box-shadow: 0 2px 12px rgba(0,0,0,0.04) !important;
}
.badge {
    font-weight: 500;
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>