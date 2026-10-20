<?php
// views/almacen/entrada.php
// Registrar Entrada de Stock - Almacén
// ✅ Guards para $producto
// ✅ ToastHelper en vez de alert()

if (!isset($seccion)) {
    $seccion = 'almacen';
}
if (!isset($titulo)) {
    $titulo = 'Registrar Entrada';
}

$producto = $producto ?? null;

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-arrow-down text-success me-2"></i>Registrar Entrada de Stock
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Registrar ingreso de inventario para el producto
            </p>
        </div>
        <a href="/proyecto/almacen" class="btn btn-secondary btn-header-action">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0">
                <div class="card-body">

                    <?php if ($producto): ?>
                    <!-- Información del producto -->
                    <div class="bg-light rounded p-3 mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($producto['imagen'])): ?>
                                        <img src="/proyecto/uploads/inventario/<?= $producto['imagen'] ?>" 
                                             alt="<?= htmlspecialchars($producto['nombre']) ?>"
                                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;" class="me-3">
                                    <?php else: ?>
                                        <div class="bg-secondary bg-opacity-10 rounded d-flex align-items-center justify-content-center me-3" 
                                             style="width: 60px; height: 60px;">
                                            <i class="fas fa-box fa-2x text-secondary"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h6 class="mb-0 fw-semibold"><?= htmlspecialchars($producto['nombre']) ?></h6>
                                        <span class="text-muted small">Código: <?= htmlspecialchars($producto['codigo'] ?? 'N/A') ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 text-md-end mt-2 mt-md-0">
                                <div class="text-muted small">Stock Actual</div>
                                <div class="h5 fw-bold <?= ($producto['cantidad'] ?? 0) <= ($producto['stock_minimo'] ?? 0) ? 'text-danger' : 'text-success' ?>">
                                    <?= $producto['cantidad'] ?? 0 ?> <?= $producto['unidad'] ?? 'u' ?>
                                </div>
                            </div>
                            <div class="col-md-3 text-md-end mt-2 mt-md-0">
                                <div class="text-muted small">Stock Mínimo</div>
                                <div class="h5 fw-bold text-warning">
                                    <?= $producto['stock_minimo'] ?? 0 ?> <?= $producto['unidad'] ?? 'u' ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario -->
                    <form method="POST" action="/proyecto/almacen/entrada/<?= $producto['id'] ?>" id="formEntrada">
                        <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">

                        <div class="mb-3">
                            <label for="cantidad" class="form-label fw-semibold">Cantidad <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-lg" id="cantidad" name="cantidad" 
                                   min="1" step="1" required placeholder="Ingresa la cantidad a ingresar">
                            <div class="form-text">Nuevo stock: <span id="nuevoStock"><?= $producto['cantidad'] ?? 0 ?></span> <?= $producto['unidad'] ?? 'u' ?></div>
                        </div>

                        <div class="mb-3">
                            <label for="motivo" class="form-label fw-semibold">Motivo <span class="text-danger">*</span></label>
                            <select class="form-select" id="motivo" name="motivo" required>
                                <option value="">Seleccionar motivo...</option>
                                <option value="Compra">Compra</option>
                                <option value="Devolución">Devolución</option>
                                <option value="Transferencia">Transferencia</option>
                                <option value="Ajuste de inventario">Ajuste de inventario</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="observaciones" class="form-label fw-semibold">Observaciones</label>
                            <textarea class="form-control" id="observaciones" name="observaciones" rows="3" 
                                      placeholder="Detalles adicionales sobre el ingreso"></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success btn-lg flex-grow-1" id="btnGuardar">
                                <i class="fas fa-save me-2"></i> Registrar Entrada
                            </button>
                            <a href="/proyecto/almacen" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times me-2"></i> Cancelar
                            </a>
                        </div>
                    </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Producto no encontrado. Por favor, selecciona un producto válido.
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

</div>

<style>
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
}
.form-control, .form-select {
    border-radius: 10px;
    padding: 12px 16px;
    border: 2px solid var(--border-color);
    background: var(--bg-input);
    color: var(--text-primary);
    transition: all 0.3s ease;
}
.form-control:focus, .form-select:focus {
    border-color: #198754;
    box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cantidadInput = document.getElementById('cantidad');
    const nuevoStock = document.getElementById('nuevoStock');
    const stockActual = <?= $producto ? (int)($producto['cantidad'] ?? 0) : 0 ?>;
    const btnGuardar = document.getElementById('btnGuardar');
    const form = document.getElementById('formEntrada');

    function mostrarToast(tipo, mensaje) {
        if (typeof window.showToast === 'function') {
            window.showToast(tipo, mensaje);
        }
    }

    if (cantidadInput && nuevoStock) {
        cantidadInput.addEventListener('input', function() {
            const cantidad = parseInt(this.value) || 0;
            nuevoStock.textContent = stockActual + cantidad;
        });
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            const cantidad = parseInt(cantidadInput.value) || 0;
            if (cantidad <= 0) {
                e.preventDefault();
                mostrarToast('error', 'La cantidad debe ser mayor a 0');
                cantidadInput.focus();
                return false;
            }
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Registrando...';
        });
    }
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>