<?php
// views/inventario/editar.php
// Editar ítem en inventario - VERSIÓN DEFINITIVA

// Verificar sesión y rol (permitir admin y almacen)
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'] ?? '', ['admin', 'almacen'])) {
    header('Location: /proyecto/auth/login');
    exit;
}

$titulo = "Editar Ítem - Inventario";
$seccion = "inventario";

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-edit text-warning me-2"></i>Editar Ítem - Inventario
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Modifica los datos del ítem
            </p>
        </div>
        <a href="/proyecto/inventario" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
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

    <?php if (isset($_SESSION['success']) && !empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_SESSION['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <!-- Formulario -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="/proyecto/inventario/editar/<?= $item['id'] ?? 0 ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">

                <div class="row g-4">
                    <!-- Nombre -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" 
                                   value="<?= htmlspecialchars($item['nombre'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Código -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Código SKU</label>
                            <input type="text" class="form-control" name="codigo" 
                                   value="<?= htmlspecialchars($item['codigo'] ?? '') ?>">
                            <small class="text-muted">Código único para identificar el producto</small>
                        </div>
                    </div>

                    <!-- Categoría -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Categoría <span class="text-danger">*</span></label>
                            <select class="form-select" name="categoria" required>
                                <option value="">Seleccionar</option>
                                <option value="Mecánica" <?= (isset($item['categoria']) && $item['categoria'] == 'Mecánica') ? 'selected' : '' ?>>Mecánica</option>
                                <option value="Eléctrica" <?= (isset($item['categoria']) && $item['categoria'] == 'Eléctrica') ? 'selected' : '' ?>>Eléctrica</option>
                                <option value="Electrónica" <?= (isset($item['categoria']) && $item['categoria'] == 'Electrónica') ? 'selected' : '' ?>>Electrónica</option>
                                <option value="Hidráulica" <?= (isset($item['categoria']) && $item['categoria'] == 'Hidráulica') ? 'selected' : '' ?>>Hidráulica</option>
                                <option value="Neumática" <?= (isset($item['categoria']) && $item['categoria'] == 'Neumática') ? 'selected' : '' ?>>Neumática</option>
                                <option value="Seguridad" <?= (isset($item['categoria']) && $item['categoria'] == 'Seguridad') ? 'selected' : '' ?>>Seguridad</option>
                                <option value="Herramientas" <?= (isset($item['categoria']) && $item['categoria'] == 'Herramientas') ? 'selected' : '' ?>>Herramientas</option>
                                <option value="Otros" <?= (isset($item['categoria']) && $item['categoria'] == 'Otros') ? 'selected' : '' ?>>Otros</option>
                            </select>
                        </div>
                    </div>

                    <!-- Unidad de Medida -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Unidad de Medida</label>
                            <select class="form-select" name="unidad_medida">
                                <option value="Unidad" <?= (isset($item['unidad_medida']) && $item['unidad_medida'] == 'Unidad') ? 'selected' : '' ?>>Unidad</option>
                                <option value="Kit" <?= (isset($item['unidad_medida']) && $item['unidad_medida'] == 'Kit') ? 'selected' : '' ?>>Kit</option>
                                <option value="Metro" <?= (isset($item['unidad_medida']) && $item['unidad_medida'] == 'Metro') ? 'selected' : '' ?>>Metro</option>
                                <option value="Kilogramo" <?= (isset($item['unidad_medida']) && $item['unidad_medida'] == 'Kilogramo') ? 'selected' : '' ?>>Kilogramo</option>
                                <option value="Litro" <?= (isset($item['unidad_medida']) && $item['unidad_medida'] == 'Litro') ? 'selected' : '' ?>>Litro</option>
                                <option value="Par" <?= (isset($item['unidad_medida']) && $item['unidad_medida'] == 'Par') ? 'selected' : '' ?>>Par</option>
                            </select>
                        </div>
                    </div>

                    <!-- Proveedor -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Proveedor</label>
                            <input type="text" class="form-control" name="proveedor" 
                                   value="<?= htmlspecialchars($item['proveedor'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Cantidad -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Cantidad</label>
                            <input type="number" class="form-control" name="cantidad" 
                                   value="<?= $item['cantidad'] ?? 0 ?>" min="0">
                        </div>
                    </div>

                    <!-- Precio Unitario -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Precio Unitario (S/)</label>
                            <input type="number" step="0.01" class="form-control" name="precio_unitario" 
                                   value="<?= $item['precio_unitario'] ?? 0 ?>" min="0">
                        </div>
                    </div>

                    <!-- Stock Mínimo -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Stock Mínimo</label>
                            <input type="number" class="form-control" name="stock_minimo" 
                                   value="<?= $item['stock_minimo'] ?? 0 ?>" min="0">
                        </div>
                    </div>

                    <!-- Stock Máximo -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Stock Máximo</label>
                            <input type="number" class="form-control" name="stock_maximo" 
                                   value="<?= $item['stock_maximo'] ?? '' ?>" min="0">
                        </div>
                    </div>

                    <!-- Ubicación -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Ubicación</label>
                            <input type="text" class="form-control" name="ubicacion" 
                                   value="<?= htmlspecialchars($item['ubicacion'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Estado -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Estado</label>
                            <select class="form-select" name="estado">
                                <option value="activo" <?= (isset($item['estado']) && $item['estado'] == 'activo') ? 'selected' : '' ?>>Activo</option>
                                <option value="inactivo" <?= (isset($item['estado']) && $item['estado'] == 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <!-- Descripción General -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Descripción General</label>
                            <textarea class="form-control" name="descripcion" rows="3"><?= htmlspecialchars($item['descripcion'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Descripción Técnica -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Descripción Técnica</label>
                            <textarea class="form-control" name="descripcion_tecnica" rows="3"><?= htmlspecialchars($item['descripcion_tecnica'] ?? '') ?></textarea>
                            <small class="text-muted">Especificaciones técnicas detalladas</small>
                        </div>
                    </div>

                    <!-- Imagen -->
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Imagen</label>
                            <?php if (!empty($item['imagen'])): ?>
                                <div class="mb-2">
                                    <img src="/proyecto/uploads/inventario/<?= $item['imagen'] ?>" 
                                         alt="<?= htmlspecialchars($item['nombre'] ?? '') ?>" 
                                         style="max-width: 150px; max-height: 150px; border-radius: 8px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" name="imagen" accept="image/*">
                            <small class="text-muted">Dejar vacío para mantener la imagen actual</small>
                        </div>
                    </div>

                    <!-- Botón -->
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Actualizar Ítem
                        </button>
                        <a href="/proyecto/inventario" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i> Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

<style>
.card {
    border-radius: 16px;
}
.shadow-sm {
    box-shadow: 0 2px 12px rgba(0,0,0,0.04) !important;
}
.form-group {
    margin-bottom: 0;
}
.form-label {
    font-size: 0.85rem;
    margin-bottom: 0.4rem;
    font-weight: 600;
}
.form-control, .form-select {
    border-radius: 10px;
    padding: 10px 14px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}
.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>