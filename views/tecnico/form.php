<?php
// views/tecnicos/form.php
// Formulario de técnicos (crear/editar) - VERSIÓN CORREGIDA CON ASTEROADMIN

// Incluir helpers necesarios
require_once __DIR__ . '/../../helpers/SecurityHelper.php';
require_once __DIR__ . '/../../helpers/ValidationHelper.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: /proyecto/auth/login');
    exit;
}

$seccion = 'tecnicos';
$titulo = isset($tecnico) ? 'Editar Técnico' : 'Crear Técnico';
$accion = isset($tecnico) ? 'actualizar' : 'guardar';
$id = isset($tecnico) ? $tecnico['id'] : '';

include_once __DIR__ . '/../layouts/header.php';
// ❌ ELIMINAR: include_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid px-0">

    <!-- ✅ Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-user-cog text-primary me-2"></i>
                <?php echo isset($tecnico) ? 'Editar Técnico' : 'Crear Técnico'; ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i>
                <?php echo isset($tecnico) ? 'Modifica los datos del técnico' : 'Ingresa los datos del nuevo técnico'; ?>
            </p>
        </div>
        <a href="/proyecto/tecnicos" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <!-- ✅ Mensajes de error -->
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

    <?php if (isset($_SESSION['mensaje']) && !empty($_SESSION['mensaje'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['mensaje']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensaje']); ?>
    <?php endif; ?>

    <!-- ✅ Formulario -->
    <div class="card border-0">
        <div class="card-body">
            <form method="POST" action="/proyecto/tecnicos/<?php echo $accion; ?>/<?php echo $id; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::generateCSRFToken(); ?>">
                
                <div class="row g-4">
                    <!-- Nombre -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" 
                                   placeholder="Nombre completo" required
                                   value="<?php echo htmlspecialchars($tecnico['nombre'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Email -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" 
                                   placeholder="correo@ejemplo.com" required
                                   value="<?php echo htmlspecialchars($tecnico['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Contraseña -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">
                                <?php echo isset($tecnico) ? 'Nueva Contraseña (opcional)' : 'Contraseña <span class="text-danger">*</span>'; ?>
                            </label>
                            <input type="password" class="form-control" name="password" 
                                   placeholder="<?php echo isset($tecnico) ? 'Dejar vacío para mantener' : 'Mínimo 6 caracteres'; ?>"
                                   <?php echo isset($tecnico) ? '' : 'required'; ?>>
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

                    <!-- Especialidad -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Especialidad</label>
                            <input type="text" class="form-control" name="especialidad" 
                                   placeholder="Ej: Mecánica, Eléctrica, Refrigeración"
                                   value="<?php echo htmlspecialchars($tecnico['especialidad'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Tarifa -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Tarifa (S/ hora)</label>
                            <input type="number" step="0.01" class="form-control" name="tarifa" 
                                   placeholder="0.00" min="0"
                                   value="<?php echo htmlspecialchars($tecnico['tarifa'] ?? 0); ?>">
                        </div>
                    </div>
                    
                    <!-- Teléfono -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Teléfono</label>
                            <input type="text" class="form-control" name="telefono" 
                                   placeholder="999 999 999"
                                   value="<?php echo htmlspecialchars($tecnico['telefono'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Estado -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Estado</label>
                            <select class="form-select" name="estado">
                                <option value="activo" <?php echo (isset($tecnico['estado']) && $tecnico['estado'] === 'activo') ? 'selected' : ''; ?>>Activo</option>
                                <option value="inactivo" <?php echo (isset($tecnico['estado']) && $tecnico['estado'] === 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Botón -->
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i> 
                            <?php echo isset($tecnico) ? 'Actualizar Técnico' : 'Guardar Técnico'; ?>
                        </button>
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