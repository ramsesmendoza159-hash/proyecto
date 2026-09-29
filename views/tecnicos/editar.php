<?php
// views/tecnicos/editar.php
// Ubicación: C:\xampp\htdocs\proyecto\views\tecnicos\editar.php
// VERSIÓN CORREGIDA CON ASTEROADMIN

// Incluir helpers necesarios
require_once __DIR__ . '/../../helpers/SecurityHelper.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: /proyecto/auth/login');
    exit;
}

$titulo = "Editar Técnico";
$seccion = "tecnicos";

$tecnico = $tecnico ?? null;
if (!$tecnico) {
    header('Location: /proyecto/tecnicos');
    exit;
}

include_once __DIR__ . '/../layouts/header.php';
// ❌ ELIMINAR: include_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid px-0">

    <!-- ✅ Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-user-edit text-warning me-2"></i>Editar Técnico
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Modifica los datos del técnico
            </p>
        </div>
        <a href="/proyecto/tecnicos" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <!-- ✅ Mensajes de error -->
    <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['errores']) && !empty($_SESSION['errores'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Por favor corrige los siguientes errores:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($_SESSION['errores'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['errores']); ?>
        </div>
    <?php endif; ?>

    <?php 
    $old = $_SESSION['old'] ?? [];
    unset($_SESSION['old']);
    ?>

    <!-- ✅ Formulario -->
    <div class="card border-0">
        <div class="card-body">
            <form action="/proyecto/tecnicos/actualizar/<?php echo $tecnico['id']; ?>" method="POST" id="formTecnico">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">

                <div class="row g-4">
                    <!-- Nombre completo -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Nombre completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" 
                                   value="<?= htmlspecialchars($old['nombre'] ?? $tecnico['nombre']) ?>" required>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?= htmlspecialchars($old['email'] ?? $tecnico['email']) ?>" required>
                        </div>
                    </div>

                    <!-- Teléfono -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Teléfono</label>
                            <input type="text" class="form-control" name="telefono" 
                                   value="<?= htmlspecialchars($old['telefono'] ?? $tecnico['telefono'] ?? '') ?>"
                                   placeholder="999 999 999">
                        </div>
                    </div>

                    <!-- Especialidad -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Especialidad <span class="text-danger">*</span></label>
                            <select class="form-select" name="especialidad" required>
                                <option value="">Seleccionar...</option>
                                <option value="Mecánica" <?= (($old['especialidad'] ?? $tecnico['especialidad'] ?? '') === 'Mecánica') ? 'selected' : '' ?>>Mecánica</option>
                                <option value="Eléctrica" <?= (($old['especialidad'] ?? $tecnico['especialidad'] ?? '') === 'Eléctrica') ? 'selected' : '' ?>>Eléctrica</option>
                                <option value="Electrónica" <?= (($old['especialidad'] ?? $tecnico['especialidad'] ?? '') === 'Electrónica') ? 'selected' : '' ?>>Electrónica</option>
                                <option value="Hidráulica" <?= (($old['especialidad'] ?? $tecnico['especialidad'] ?? '') === 'Hidráulica') ? 'selected' : '' ?>>Hidráulica</option>
                                <option value="Refrigeración" <?= (($old['especialidad'] ?? $tecnico['especialidad'] ?? '') === 'Refrigeración') ? 'selected' : '' ?>>Refrigeración</option>
                                <option value="Metalmecánica" <?= (($old['especialidad'] ?? $tecnico['especialidad'] ?? '') === 'Metalmecánica') ? 'selected' : '' ?>>Metalmecánica</option>
                                <option value="General" <?= (($old['especialidad'] ?? $tecnico['especialidad'] ?? '') === 'General') ? 'selected' : '' ?>>General</option>
                            </select>
                        </div>
                    </div>

                    <!-- Contraseña (opcional) -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Nueva Contraseña</label>
                            <input type="password" class="form-control" name="password" 
                                   minlength="6" placeholder="Mínimo 6 caracteres">
                            <small class="text-muted">Dejar en blanco para mantener la contraseña actual</small>
                        </div>
                    </div>

                    <!-- Confirmar contraseña -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Confirmar Contraseña</label>
                            <input type="password" class="form-control" name="confirmar_password" 
                                   placeholder="Repetir nueva contraseña">
                        </div>
                    </div>

                    <!-- Tarifa -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Tarifa por hora (S/)</label>
                            <input type="number" step="0.01" class="form-control" name="tarifa" 
                                   value="<?= htmlspecialchars($old['tarifa'] ?? $tecnico['tarifa'] ?? 0) ?>"
                                   min="0" placeholder="0.00">
                        </div>
                    </div>

                    <!-- Estado -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Estado</label>
                            <select class="form-select" name="estado">
                                <option value="activo" <?= (($old['estado'] ?? $tecnico['estado'] ?? 'activo') === 'activo') ? 'selected' : '' ?>>Activo</option>
                                <option value="inactivo" <?= (($old['estado'] ?? $tecnico['estado'] ?? '') === 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                        <i class="fas fa-save me-2"></i> Actualizar Técnico
                    </button>
                    <a href="/proyecto/tecnicos" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i> Cancelar
                    </a>
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

<!-- ✅ Validación JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formTecnico');
    const password = document.querySelector('[name="password"]');
    const confirmPassword = document.querySelector('[name="confirmar_password"]');
    const btnGuardar = document.getElementById('btnGuardar');
    
    function validarContraseña() {
        if (password.value.length > 0 && password.value.length < 6) {
            password.classList.add('is-invalid');
            return false;
        } else {
            password.classList.remove('is-invalid');
            return true;
        }
    }
    
    function validarConfirmacion() {
        if (confirmPassword.value.length > 0) {
            if (password.value !== confirmPassword.value) {
                confirmPassword.classList.add('is-invalid');
                return false;
            } else {
                confirmPassword.classList.remove('is-invalid');
                return true;
            }
        }
        return true;
    }
    
    password.addEventListener('input', function() {
        validarContraseña();
        validarConfirmacion();
    });
    
    confirmPassword.addEventListener('input', validarConfirmacion);
    
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validar nombre
        const nombre = document.querySelector('[name="nombre"]');
        if (!nombre.value.trim()) {
            nombre.classList.add('is-invalid');
            isValid = false;
        } else {
            nombre.classList.remove('is-invalid');
        }
        
        // Validar email
        const email = document.querySelector('[name="email"]');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email.value.trim())) {
            email.classList.add('is-invalid');
            isValid = false;
        } else {
            email.classList.remove('is-invalid');
        }
        
        // Validar especialidad
        const especialidad = document.querySelector('[name="especialidad"]');
        if (!especialidad.value) {
            especialidad.classList.add('is-invalid');
            isValid = false;
        } else {
            especialidad.classList.remove('is-invalid');
        }
        
        // Validar contraseña (solo si se ingresó)
        if (password.value.length > 0 && !validarContraseña()) {
            isValid = false;
        }
        
        // Validar confirmación (solo si se ingresó)
        if (confirmPassword.value.length > 0 && !validarConfirmacion()) {
            isValid = false;
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
            btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Actualizando...';
        }
    });
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>