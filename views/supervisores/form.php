<?php
// views/supervisores/form.php
// Formulario de supervisores (crear/editar) - VERSIÓN CORREGIDA

// Incluir helpers necesarios
require_once __DIR__ . '/../../helpers/SecurityHelper.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: /proyecto/auth/login');
    exit;
}

$seccion = 'supervisores';
$titulo = isset($supervisor) ? 'Editar Supervisor' : 'Crear Supervisor';
$accion = isset($supervisor) ? 'actualizar' : 'guardar';
$id = isset($supervisor) ? $supervisor['id'] : '';

include_once __DIR__ . '/../layouts/header.php';
// ❌ ELIMINAR: include_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid px-0">

    <!-- ✅ Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-user-tie text-primary me-2"></i>
                <?php echo isset($supervisor) ? 'Editar Supervisor' : 'Crear Supervisor'; ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i>
                <?php echo isset($supervisor) ? 'Modifica los datos del supervisor' : 'Ingresa los datos del nuevo supervisor'; ?>
            </p>
        </div>
        <a href="/proyecto/supervisores" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <!-- ✅ Mostrar errores -->
    <?php if (isset($_SESSION['errores']) && !empty($_SESSION['errores'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i>
            <ul class="mb-0">
                <?php foreach ($_SESSION['errores'] as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['errores']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- ✅ Formulario -->
    <div class="card border-0">
        <div class="card-body">
            <form method="POST" action="/proyecto/supervisores/<?php echo $accion; ?>/<?php echo $id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::generateCSRFToken(); ?>">
                
                <div class="row g-4">
                    <!-- Nombre -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" 
                                   placeholder="Nombre completo" required
                                   value="<?php echo htmlspecialchars($supervisor['nombre'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Email -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" 
                                   placeholder="correo@ejemplo.com" required
                                   value="<?php echo htmlspecialchars($supervisor['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Contraseña -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">
                                <?php echo isset($supervisor) ? 'Nueva Contraseña (opcional)' : 'Contraseña <span class="text-danger">*</span>'; ?>
                            </label>
                            <input type="password" class="form-control" name="password" 
                                   placeholder="<?php echo isset($supervisor) ? 'Dejar vacío para mantener' : 'Mínimo 6 caracteres'; ?>"
                                   <?php echo isset($supervisor) ? '' : 'required'; ?>>
                            <small class="text-muted">Mínimo 6 caracteres</small>
                        </div>
                    </div>
                    
                    <!-- Confirmar contraseña -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Confirmar Contraseña</label>
                            <input type="password" class="form-control" name="confirmar_password" 
                                   placeholder="Repetir contraseña">
                        </div>
                    </div>

                    <!-- Área de Supervisión -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Área de Supervisión</label>
                            <input type="text" class="form-control" name="area" 
                                   placeholder="Ej: Mantenimiento, Producción, Calidad"
                                   value="<?php echo htmlspecialchars($supervisor['area'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Teléfono -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Teléfono</label>
                            <input type="text" class="form-control" name="telefono" 
                                   placeholder="999 999 999"
                                   value="<?php echo htmlspecialchars($supervisor['telefono'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Estado -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Estado</label>
                            <select class="form-select" name="estado">
                                <option value="activo" <?php echo (isset($supervisor['estado']) && $supervisor['estado'] === 'activo') ? 'selected' : ''; ?>>Activo</option>
                                <option value="inactivo" <?php echo (isset($supervisor['estado']) && $supervisor['estado'] === 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Botón -->
                    <div class="col-md-12">
                        <hr>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> 
                                <?php echo isset($supervisor) ? 'Actualizar Supervisor' : 'Guardar Supervisor'; ?>
                            </button>
                            <a href="/proyecto/supervisores" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i> Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- ✅ Estilos -->
<style>
.form-group {
    margin-bottom: 0;
}
.form-label {
    font-size: 0.85rem;
    margin-bottom: 0.4rem;
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

<!-- ✅ Validación de contraseña -->
<script>
document.querySelector('form').addEventListener('submit', function(e) {
    var password = document.querySelector('[name="password"]');
    var confirmar = document.querySelector('[name="confirmar_password"]');
    
    if (password && confirmar) {
        var pwd = password.value;
        var conf = confirmar.value;
        
        if (pwd || conf) {
            if (pwd !== conf) {
                e.preventDefault();
                alert('❌ Las contraseñas no coinciden.');
                return false;
            }
            if (pwd.length < 6) {
                e.preventDefault();
                alert('❌ La contraseña debe tener al menos 6 caracteres.');
                return false;
            }
        }
    }
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>