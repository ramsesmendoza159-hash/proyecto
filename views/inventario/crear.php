<?php
// views/inventario/crear.php
// Crear ítem en inventario - VERSIÓN CORREGIDA CON TODOS LOS CAMPOS

// Verificar sesión y rol (permitir admin y almacen)
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['rol'] ?? '', ['admin', 'almacen'])) {
    header('Location: /proyecto/auth/login');
    exit;
}

$titulo = "Agregar al Inventario";
$seccion = "inventario";

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-boxes text-primary me-2"></i>Agregar al Inventario
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Ingresa los datos del nuevo ítem
            </p>
        </div>
        <a href="/proyecto/inventario" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <!-- Mensajes de error -->
    <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php 
    $old = $_SESSION['old'] ?? [];
    unset($_SESSION['old']);
    ?>

    <!-- Formulario CORREGIDO -->
    <div class="card border-0">
        <div class="card-body">
            <form action="/proyecto/inventario/guardar" method="POST" enctype="multipart/form-data" id="formInventario">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">

                <div class="row g-4">
                    <!-- Nombre -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Nombre del repuesto/equipo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" 
                                   value="<?= htmlspecialchars($old['nombre'] ?? '') ?>"
                                   required placeholder="Ej: Filtro de aceite">
                        </div>
                    </div>

                    <!-- Código SKU -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Código SKU</label>
                            <input type="text" class="form-control" name="codigo" 
                                   value="<?= htmlspecialchars($old['codigo'] ?? '') ?>"
                                   placeholder="Ej: SKU-001">
                            <small class="text-muted">Código único para identificar el producto</small>
                        </div>
                    </div>

                    <!-- Categoría -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Categoría <span class="text-danger">*</span></label>
                            <select class="form-select" name="categoria" required>
                                <option value="">Seleccionar...</option>
                                <option value="Mecánica" <?= ($old['categoria'] ?? '') === 'Mecánica' ? 'selected' : '' ?>>Mecánica</option>
                                <option value="Eléctrica" <?= ($old['categoria'] ?? '') === 'Eléctrica' ? 'selected' : '' ?>>Eléctrica</option>
                                <option value="Electrónica" <?= ($old['categoria'] ?? '') === 'Electrónica' ? 'selected' : '' ?>>Electrónica</option>
                                <option value="Hidráulica" <?= ($old['categoria'] ?? '') === 'Hidráulica' ? 'selected' : '' ?>>Hidráulica</option>
                                <option value="Neumática" <?= ($old['categoria'] ?? '') === 'Neumática' ? 'selected' : '' ?>>Neumática</option>
                                <option value="Seguridad" <?= ($old['categoria'] ?? '') === 'Seguridad' ? 'selected' : '' ?>>Seguridad</option>
                                <option value="Herramientas" <?= ($old['categoria'] ?? '') === 'Herramientas' ? 'selected' : '' ?>>Herramientas</option>
                                <option value="Otros" <?= ($old['categoria'] ?? '') === 'Otros' ? 'selected' : '' ?>>Otros</option>
                            </select>
                        </div>
                    </div>

                    <!-- Unidad de Medida -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Unidad de Medida</label>
                            <select class="form-select" name="unidad_medida">
                                <option value="Unidad" <?= ($old['unidad_medida'] ?? '') === 'Unidad' ? 'selected' : '' ?>>Unidad</option>
                                <option value="Kit" <?= ($old['unidad_medida'] ?? '') === 'Kit' ? 'selected' : '' ?>>Kit</option>
                                <option value="Metro" <?= ($old['unidad_medida'] ?? '') === 'Metro' ? 'selected' : '' ?>>Metro</option>
                                <option value="Kilogramo" <?= ($old['unidad_medida'] ?? '') === 'Kilogramo' ? 'selected' : '' ?>>Kilogramo</option>
                                <option value="Litro" <?= ($old['unidad_medida'] ?? '') === 'Litro' ? 'selected' : '' ?>>Litro</option>
                                <option value="Par" <?= ($old['unidad_medida'] ?? '') === 'Par' ? 'selected' : '' ?>>Par</option>
                            </select>
                        </div>
                    </div>

                    <!-- Proveedor -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Proveedor</label>
                            <input type="text" class="form-control" name="proveedor" 
                                   value="<?= htmlspecialchars($old['proveedor'] ?? '') ?>"
                                   placeholder="Ej: Proveedor S.A.">
                        </div>
                    </div>

                    <!-- Cantidad -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Cantidad <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="cantidad" 
                                   value="<?= htmlspecialchars($old['cantidad'] ?? 0) ?>"
                                   min="0" step="1" required>
                        </div>
                    </div>

                    <!-- Precio Unitario -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Precio Unitario (S/)</label>
                            <input type="number" class="form-control" name="precio_unitario" 
                                   value="<?= htmlspecialchars($old['precio_unitario'] ?? 0) ?>"
                                   min="0" step="0.01" placeholder="0.00">
                        </div>
                    </div>

                    <!-- Stock Mínimo -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Stock Mínimo</label>
                            <input type="number" class="form-control" name="stock_minimo" 
                                   value="<?= htmlspecialchars($old['stock_minimo'] ?? 0) ?>"
                                   min="0" placeholder="0">
                            <small class="text-muted">Alerta cuando el stock baje de este número</small>
                        </div>
                    </div>

                    <!-- Stock Máximo -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Stock Máximo</label>
                            <input type="number" class="form-control" name="stock_maximo" 
                                   value="<?= htmlspecialchars($old['stock_maximo'] ?? '') ?>"
                                   min="0" placeholder="Opcional">
                        </div>
                    </div>

                    <!-- Ubicación -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Ubicación</label>
                            <input type="text" class="form-control" name="ubicacion" 
                                   value="<?= htmlspecialchars($old['ubicacion'] ?? '') ?>"
                                   placeholder="Ej: Estante A-1, Pasillo 2">
                        </div>
                    </div>

                    <!-- Estado -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Estado</label>
                            <select class="form-select" name="estado">
                                <option value="activo" <?= ($old['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                                <option value="inactivo" <?= ($old['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <!-- Descripción General -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Descripción General</label>
                            <textarea class="form-control" name="descripcion" 
                                      rows="3" placeholder="Descripción general del ítem"><?= htmlspecialchars($old['descripcion'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Descripción Técnica -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Descripción Técnica</label>
                            <textarea class="form-control" name="descripcion_tecnica" 
                                      rows="3" placeholder="Especificaciones técnicas, dimensiones, material..."><?= htmlspecialchars($old['descripcion_tecnica'] ?? '') ?></textarea>
                            <small class="text-muted">Especificaciones técnicas detalladas</small>
                        </div>
                    </div>

                    <!-- Imagen -->
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Imagen del producto</label>
                            <input type="file" class="form-control" name="imagen" 
                                   id="imagen" accept="image/*">
                            <small class="text-muted">Formatos: JPG, PNG, WEBP. Máx 5MB</small>
                            <div id="preview-container" class="mt-2"></div>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                        <i class="fas fa-save me-2"></i> Guardar
                    </button>
                    <a href="/proyecto/inventario" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- Estilos -->
<style>
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
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
</style>

<!-- Validación JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formInventario');
    const btnGuardar = document.getElementById('btnGuardar');
    const imagenInput = document.getElementById('imagen');
    const previewContainer = document.getElementById('preview-container');
    
    // Previsualización de imagen
    imagenInput.addEventListener('change', function(e) {
        const file = this.files[0];
        previewContainer.innerHTML = '';
        
        if (file) {
            const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                alert('❌ Formato no permitido. Usa JPG, PNG o WEBP.');
                this.value = '';
                return;
            }
            
            if (file.size > 5 * 1024 * 1024) {
                alert('❌ El archivo no debe superar los 5MB.');
                this.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'img-thumbnail';
                img.style.maxHeight = '150px';
                previewContainer.appendChild(img);
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Validación antes de enviar
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        const nombre = document.querySelector('[name="nombre"]');
        if (!nombre.value.trim()) {
            nombre.classList.add('is-invalid');
            isValid = false;
        } else {
            nombre.classList.remove('is-invalid');
        }
        
        const categoria = document.querySelector('[name="categoria"]');
        if (!categoria.value) {
            categoria.classList.add('is-invalid');
            isValid = false;
        } else {
            categoria.classList.remove('is-invalid');
        }
        
        const cantidad = document.querySelector('[name="cantidad"]');
        if (isNaN(cantidad.value) || parseInt(cantidad.value) < 0) {
            cantidad.classList.add('is-invalid');
            isValid = false;
        } else {
            cantidad.classList.remove('is-invalid');
        }
        
        if (!isValid) {
            e.preventDefault();
            const firstError = document.querySelector('.is-invalid');
            if (firstError) {
                firstError.focus();
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } else {
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Guardando...';
        }
    });
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>