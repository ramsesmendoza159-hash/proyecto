<?php
// views/tecnicos/crear.php
// Crear técnico - VERSIÓN CORREGIDA CON DEBUG

// Incluir helpers necesarios
require_once __DIR__ . '/../../helpers/SecurityHelper.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: /proyecto/auth/login');
    exit;
}

$titulo = "Nuevo Técnico";
$seccion = "tecnicos";

include_once __DIR__ . '/../layouts/header.php';

$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);
?>

<div class="container-fluid px-0">

    <!-- ✅ Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-user-plus text-primary me-2"></i>Nuevo Técnico
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Ingresa los datos del nuevo técnico
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

    <!-- ✅ Formulario -->
    <div class="card border-0">
        <div class="card-body">
            <form action="/proyecto/tecnicos/guardar" method="POST" id="formTecnico">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">

                <div class="row g-4">
                    <!-- Nombre completo -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Nombre completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" 
                                   value="<?= htmlspecialchars($old['nombre'] ?? '') ?>"
                                   required placeholder="Ej: Juan Pérez">
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                                   required placeholder="ejemplo@correo.com">
                        </div>
                    </div>

                    <!-- Teléfono -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Teléfono</label>
                            <input type="text" class="form-control" name="telefono" 
                                   value="<?= htmlspecialchars($old['telefono'] ?? '') ?>"
                                   placeholder="999 999 999">
                        </div>
                    </div>

                    <!-- Especialidad -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Especialidad <span class="text-danger">*</span></label>
                            <select class="form-select" name="especialidad" required>
                                <option value="">Seleccionar...</option>
                                <option value="Mecánica" <?= ($old['especialidad'] ?? '') === 'Mecánica' ? 'selected' : '' ?>>Mecánica</option>
                                <option value="Eléctrica" <?= ($old['especialidad'] ?? '') === 'Eléctrica' ? 'selected' : '' ?>>Eléctrica</option>
                                <option value="Electrónica" <?= ($old['especialidad'] ?? '') === 'Electrónica' ? 'selected' : '' ?>>Electrónica</option>
                                <option value="Hidráulica" <?= ($old['especialidad'] ?? '') === 'Hidráulica' ? 'selected' : '' ?>>Hidráulica</option>
                                <option value="Refrigeración" <?= ($old['especialidad'] ?? '') === 'Refrigeración' ? 'selected' : '' ?>>Refrigeración</option>
                                <option value="Metalmecánica" <?= ($old['especialidad'] ?? '') === 'Metalmecánica' ? 'selected' : '' ?>>Metalmecánica</option>
                                <option value="General" <?= ($old['especialidad'] ?? '') === 'General' ? 'selected' : '' ?>>General</option>
                            </select>
                        </div>
                    </div>

                    <!-- Contraseña - CON VALIDACIÓN CLARA -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" 
                                   id="password" required minlength="6" placeholder="Mínimo 6 caracteres">
                            <small class="text-muted" id="passwordHelp">Mínimo 6 caracteres</small>
                            <div class="invalid-feedback" id="passwordFeedback"></div>
                        </div>
                    </div>

                    <!-- Confirmar contraseña -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Confirmar Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="confirmar_password" 
                                   id="confirmar_password" required placeholder="Repetir contraseña">
                            <div class="invalid-feedback" id="confirmFeedback"></div>
                        </div>
                    </div>

                    <!-- Tarifa -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Tarifa por hora (S/)</label>
                            <input type="number" step="0.01" class="form-control" name="tarifa" 
                                   value="<?= htmlspecialchars($old['tarifa'] ?? '0.00') ?>"
                                   min="0" placeholder="0.00">
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
                </div>

                <hr>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                        <i class="fas fa-save me-2"></i> Guardar Técnico
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

<!-- ✅ Validación JavaScript MEJORADA -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formTecnico');
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmar_password');
    const btnGuardar = document.getElementById('btnGuardar');
    const passwordFeedback = document.getElementById('passwordFeedback');
    const confirmFeedback = document.getElementById('confirmFeedback');
    
    // ✅ Función para validar contraseña en tiempo real
    function validarPassword() {
        if (password.value.length === 0) {
            password.classList.remove('is-invalid');
            passwordFeedback.textContent = '';
            return false;
        }
        
        if (password.value.length < 6) {
            password.classList.add('is-invalid');
            passwordFeedback.textContent = 'La contraseña debe tener al menos 6 caracteres';
            return false;
        } else {
            password.classList.remove('is-invalid');
            passwordFeedback.textContent = '';
            return true;
        }
    }
    
    // ✅ Función para validar confirmación en tiempo real
    function validarConfirmacion() {
        if (confirmPassword.value.length === 0) {
            confirmPassword.classList.remove('is-invalid');
            confirmFeedback.textContent = '';
            return false;
        }
        
        if (password.value !== confirmPassword.value) {
            confirmPassword.classList.add('is-invalid');
            confirmFeedback.textContent = 'Las contraseñas no coinciden';
            return false;
        } else {
            confirmPassword.classList.remove('is-invalid');
            confirmFeedback.textContent = '✅ Las contraseñas coinciden';
            confirmFeedback.style.color = '#198754';
            return true;
        }
    }
    
    // ✅ Eventos en tiempo real
    password.addEventListener('input', function() {
        validarPassword();
        if (confirmPassword.value.length > 0) {
            validarConfirmacion();
        }
    });
    
    confirmPassword.addEventListener('input', validarConfirmacion);
    
    // ✅ Validación antes de enviar
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
        
        // ✅ Validar contraseña (si tiene required)
        if (password.hasAttribute('required')) {
            if (!validarPassword()) {
                isValid = false;
            }
        }
        
        // ✅ Validar confirmación
        if (confirmPassword.value.length > 0 || password.value.length > 0) {
            if (!validarConfirmacion()) {
                isValid = false;
            }
        }
        
        if (!isValid) {
            e.preventDefault();
            // Scroll al primer error
            const firstError = document.querySelector('.is-invalid');
            if (firstError) {
                firstError.focus();
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        } else {
            // ✅ Deshabilitar botón para evitar doble envío
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Guardando...';
        }
    });
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>