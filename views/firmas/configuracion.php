<?php
// views/firmas/configuracion.php
// Configuración de la secuencia de firmas (solo admin)
// ✅ FIX: Agregado <?php al inicio (antes era ?php)
// ✅ FIX: Verificación de sesión y rol
// ✅ FIX: Guard para $secuencia
// ✅ FIX: checkbox "activo" respeta el valor de la BD
// ✅ FIX: htmlspecialchars con ?? '' en todos los campos

// ✅ Validar sesión y rol
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    header('Location: /proyecto/auth/login');
    exit;
}

if (($_SESSION['rol'] ?? '') !== 'admin') {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    header('Location: /proyecto/dashboard');
    exit;
}

// ✅ Guard: si $secuencia no viene del controlador, inicializarla vacía
$secuencia = $secuencia ?? [];

// Roles permitidos en la secuencia de firmas
$roles_disponibles = [
    'ingeniero'  => 'Ingeniero',
    'calidad'    => 'Calidad',
    'tecnico'    => 'Técnico',
    'supervisor' => 'Supervisor',
    'seguridad'  => 'Seguridad',
];

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-cog text-primary me-2"></i>Configuración de Secuencia de Firmas
            </h4>
            <p class="text-muted small mb-0">
                Define el orden y los roles que deben firmar cada orden
            </p>
        </div>
        <a href="/proyecto/dashboard" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?= $_SESSION['mensaje_tipo'] ?? 'success' ?> alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_SESSION['mensaje']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form method="POST" action="/proyecto/firmas/guardar-configuracion">
        <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
        
        <div class="card border-0">
            <div class="card-header bg-transparent border-0 pt-3">
                <h5 class="mb-0 fw-semibold">
                    <i class="fas fa-list-ol text-primary me-2"></i>Pasos de la Secuencia
                </h5>
                <p class="text-muted small mb-0 mt-1">
                    Cada paso corresponde a una firma que se debe completar en orden.
                </p>
            </div>
            <div class="card-body">
                <?php if (empty($secuencia)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-3x d-block mb-3"></i>
                        <h5>No hay pasos configurados</h5>
                        <p class="small">Ejecuta el script SQL de inicialización de la tabla <code>firma_secuencia_config</code>.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($secuencia as $paso): 
                        $paso_num = (int)($paso['paso'] ?? 0);
                        $rol_actual = $paso['rol_firmante'] ?? '';
                        $nombre_paso = $paso['nombre_paso'] ?? '';
                        $es_obligatorio = !empty($paso['obligatorio']);
                        $es_activo = !isset($paso['activo']) || (int)$paso['activo'] === 1;
                    ?>
                        <div class="card mb-3 border">
                            <div class="card-body">
                                <div class="row align-items-center g-3">
                                    <div class="col-md-1 text-center">
                                        <span class="badge bg-primary fs-5"><?= $paso_num ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold small">
                                            Rol Firmante <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" 
                                                name="rol_firmante[<?= $paso_num ?>]" 
                                                required>
                                            <option value="">Seleccionar...</option>
                                            <?php foreach ($roles_disponibles as $rol_key => $rol_label): ?>
                                                <option value="<?= htmlspecialchars($rol_key) ?>"
                                                    <?= $rol_actual === $rol_key ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($rol_label) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label fw-semibold small">
                                            Nombre del Paso <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" 
                                               name="nombre_paso[<?= $paso_num ?>]" 
                                               value="<?= htmlspecialchars($nombre_paso) ?>"
                                               placeholder="Ej: Revisión y aprobación del ingeniero"
                                               required>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-check">
                                            <input type="checkbox" 
                                                   class="form-check-input" 
                                                   id="obligatorio_<?= $paso_num ?>"
                                                   name="obligatorio[<?= $paso_num ?>]" 
                                                   value="1"
                                                   <?= $es_obligatorio ? 'checked' : '' ?>>
                                            <label class="form-check-label small" for="obligatorio_<?= $paso_num ?>">
                                                Obligatorio
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input type="checkbox" 
                                                   class="form-check-input" 
                                                   id="activo_<?= $paso_num ?>"
                                                   name="activo[<?= $paso_num ?>]" 
                                                   value="1"
                                                   <?= $es_activo ? 'checked' : '' ?>>
                                            <label class="form-check-label small" for="activo_<?= $paso_num ?>">
                                                Activo
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-transparent">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" <?= empty($secuencia) ? 'disabled' : '' ?>>
                        <i class="fas fa-save me-2"></i> Guardar Configuración
                    </button>
                    <a href="/proyecto/dashboard" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i> Cancelar
                    </a>
                </div>
                <small class="text-muted d-block mt-2">
                    <i class="fas fa-info-circle me-1"></i>
                    Los pasos desmarcados como <strong>activo</strong> no se aplicarán a nuevas órdenes.
                </small>
            </div>
        </div>
    </form>

</div>

<style>
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>