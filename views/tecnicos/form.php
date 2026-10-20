<?php
// views/tecnicos/form.php
// Formulario de técnicos (crear/editar) - VERSIÓN UNIFICADA

require_once __DIR__ . '/../../helpers/SecurityHelper.php';

if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: /proyecto/auth/login');
    exit;
}

// ✅ FIX: inicializar $tecnico como null si no viene del controlador
$tecnico = $tecnico ?? null;

$es_edicion = !empty($tecnico);
$accion = $es_edicion ? 'actualizar' : 'guardar';
$id = $es_edicion ? (int)($tecnico['id'] ?? 0) : '';
$titulo = $es_edicion ? 'Editar Técnico' : 'Crear Técnico';

$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-<?= $es_edicion ? 'user-edit text-warning' : 'user-plus text-primary' ?> me-2"></i>
                <?= $es_edicion ? 'Editar Técnico' : 'Crear Técnico' ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i>
                <?= $es_edicion ? 'Modifica los datos del técnico' : 'Ingresa los datos del nuevo técnico' ?>
            </p>
        </div>
        <a href="/proyecto/tecnicos" class="btn btn-secondary btn-header-action">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Mensajes -->
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
        </div>
        <?php unset($_SESSION['errores']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Formulario -->
    <div class="card border-0">
        <div class="card-body">
            <form method="POST" action="/proyecto/tecnicos/<?= $accion ?>/<?= $id ?>" id="formTecnico">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                
                <div class="row g-4">
                    <!-- Nombre -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nombre" 
                                   placeholder="Nombre completo" required
                                   value="<?= htmlspecialchars($old['nombre'] ?? ($tecnico['nombre'] ?? '')) ?>">
                        </div>
                    </div>
                    
                    <!-- Email -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" 
                                   placeholder="correo@ejemplo.com" required
                                   value="<?= htmlspecialchars($old['email'] ?? ($tecnico['email'] ?? '')) ?>">
                        </div>
                    </div>

                    <!-- Contraseña -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">
                                <?= $es_edicion ? 'Nueva Contraseña (opcional)' : 'Contraseña <span class="text-danger">*</span>' ?>
                            </label>
                            <input type="password" class="form-control" name="password" 
                                   placeholder="<?= $es_edicion ? 'Dejar vacío para mantener' : 'Mínimo 6 caracteres' ?>"
                                   <?= $es_edicion ? '' : 'required' ?>>
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
                                   value="<?= htmlspecialchars($old['especialidad'] ?? ($tecnico['especialidad'] ?? '')) ?>">
                        </div>
                    </div>
                    
                    <!-- Tarifa -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Tarifa (S/ hora)</label>
                            <input type="number" step="0.01" class="form-control" name="tarifa" 
                                   placeholder="0.00" min="0"
                                   value="<?= htmlspecialchars($old['tarifa'] ?? ($tecnico['tarifa'] ?? 0)) ?>">
                        </div>
                    </div>
                    
                    <!-- Teléfono -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Teléfono</label>
                            <input type="text" class="form-control" name="telefono" 
                                   placeholder="999 999 999"
                                   value="<?= htmlspecialchars($old['telefono'] ?? ($tecnico['telefono'] ?? '')) ?>">
                        </div>
                    </div>

                    <!-- Estado -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Estado</label>
                            <select class="form-select" name="estado">
                                <option value="activo" <?= ($old['estado'] ?? ($tecnico['estado'] ?? 'activo')) === 'activo' ? 'selected' : '' ?>>Activo</option>
                                <option value="inactivo" <?= ($old['estado'] ?? ($tecnico['estado'] ?? '')) === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Botón -->
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100" id="btnGuardar">
                            <i class="fas fa-save me-2"></i> 
                            <?= $es_edicion ? 'Actualizar Técnico' : 'Guardar Técnico' ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

<style>
.form-group { margin-bottom: 0; }
.form-label {
    font-size: 0.85rem;
    margin-bottom: 0.4rem;
}
.form-control, .form-select {
    border-radius: 10px;
    padding: 10px 14px;
    border: 2px solid var(--border-color);
    background: var(--bg-input);
    color: var(--text-primary);
    transition: all 0.3s ease;
}
.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
}
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formTecnico');
    const password = document.querySelector('[name="password"]');
    const confirmPassword = document.querySelector('[name="confirmar_password"]');
    const btnGuardar = document.getElementById('btnGuardar');

    function mostrarToast(tipo, mensaje) {
        if (typeof window.showToast === 'function') {
            window.showToast(tipo, mensaje);
        }
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;

            const nombre = document.querySelector('[name="nombre"]');
            if (!nombre.value.trim()) {
                nombre.classList.add('is-invalid');
                isValid = false;
            } else {
                nombre.classList.remove('is-invalid');
            }

            const email = document.querySelector('[name="email"]');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value.trim())) {
                email.classList.add('is-invalid');
                isValid = false;
            } else {
                email.classList.remove('is-invalid');
            }

            if (password.value || confirmPassword.value) {
                if (password.value !== confirmPassword.value) {
                    e.preventDefault();
                    mostrarToast('error', 'Las contraseñas no coinciden');
                    confirmPassword.focus();
                    return false;
                }
                if (password.value.length < 6) {
                    e.preventDefault();
                    mostrarToast('error', 'La contraseña debe tener al menos 6 caracteres');
                    password.focus();
                    return false;
                }
            }

            if (!isValid) {
                e.preventDefault();
                const firstError = document.querySelector('.is-invalid');
                if (firstError) {
                    firstError.focus();
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                mostrarToast('warning', 'Por favor corrige los errores en el formulario');
                return false;
            }

            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Guardando...';
        });
    }
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>