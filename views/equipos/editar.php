<?php
// views/equipos/editar.php
// Editar Equipo - VERSIÓN CORREGIDA

if (!isset($seccion)) $seccion = 'equipos';
if (!isset($titulo)) $titulo = 'Editar Equipo';

$equipo = $equipo ?? null;
if (!$equipo) {
    header('Location: /proyecto/equipos');
    exit;
}

$areas = $areas ?? [];
$plantas = $plantas ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);

// ✅ IMPORTANTE: Incluir el header ANTES de cualquier HTML
include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-edit text-warning me-2"></i>Editar Equipo
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Modifica los datos del equipo: <strong><?= htmlspecialchars($equipo['nombre_equipo'] ?? '') ?></strong>
            </p>
        </div>
        <a href="/proyecto/equipos/ver/<?= $equipo['id_equipo'] ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <!-- Errores -->
    <?php if (isset($_SESSION['errores'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i>
            <ul class="mb-0">
                <?php foreach ($_SESSION['errores'] as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['errores']); ?>
    <?php endif; ?>

    <!-- Formulario -->
    <div class="card border-0">
        <div class="card-body">
            <form method="POST" action="/proyecto/equipos/actualizar/<?= $equipo['id_equipo'] ?>" id="formEquipo">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                
                <div class="row g-4">
                    <!-- Información Básica -->
                    <div class="col-12">
                        <h5 class="fw-semibold mb-3">
                            <i class="fas fa-info-circle text-primary me-2"></i>Información Básica
                        </h5>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nombre del Equipo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nombre" required
                               value="<?= htmlspecialchars($old['nombre'] ?? $equipo['nombre_equipo'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Código</label>
                        <input type="text" class="form-control" name="codigo"
                               value="<?= htmlspecialchars($old['codigo'] ?? $equipo['codigo'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Área <span class="text-danger">*</span></label>
                        <select class="form-select" name="id_area" required>
                            <option value="">Seleccionar...</option>
                            <?php foreach ($areas as $area): ?>
                                <option value="<?= $area['id_area'] ?>" 
                                    <?= (($old['id_area'] ?? $equipo['id_area'] ?? '') == $area['id_area']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($area['nombre_area']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Marca</label>
                        <input type="text" class="form-control" name="marca"
                               value="<?= htmlspecialchars($old['marca'] ?? $equipo['marca'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Modelo</label>
                        <input type="text" class="form-control" name="modelo"
                               value="<?= htmlspecialchars($old['modelo'] ?? $equipo['modelo'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Serie</label>
                        <input type="text" class="form-control" name="serie"
                               value="<?= htmlspecialchars($old['serie'] ?? $equipo['serie'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Fabricante</label>
                        <input type="text" class="form-control" name="fabricante"
                               value="<?= htmlspecialchars($old['fabricante'] ?? $equipo['fabricante'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Año de Fabricación</label>
                        <input type="number" class="form-control" name="año_fabricacion" min="1900" max="2030"
                               value="<?= htmlspecialchars($old['año_fabricacion'] ?? $equipo['año_fabricacion'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Fecha Instalación</label>
                        <input type="date" class="form-control" name="fecha_instalacion"
                               value="<?= htmlspecialchars($old['fecha_instalacion'] ?? $equipo['fecha_instalacion'] ?? '') ?>">
                    </div>

                    <!-- Descripciones -->
                    <div class="col-12 mt-4">
                        <h5 class="fw-semibold mb-3">
                            <i class="fas fa-align-left text-primary me-2"></i>Descripciones
                        </h5>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Descripción General</label>
                        <textarea class="form-control" name="descripcion" rows="3"><?= htmlspecialchars($old['descripcion'] ?? $equipo['descripcion'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Descripción Técnica</label>
                        <textarea class="form-control" name="descripcion_tec" rows="3"><?= htmlspecialchars($old['descripcion_tec'] ?? $equipo['descripcion_tec'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Especificación</label>
                        <input type="text" class="form-control" name="especificacion"
                               value="<?= htmlspecialchars($old['especificacion'] ?? $equipo['especificacion'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Riesgo</label>
                        <input type="text" class="form-control" name="ries"
                               value="<?= htmlspecialchars($old['ries'] ?? $equipo['ries'] ?? '') ?>">
                    </div>

                    <!-- Estado -->
                    <div class="col-12 mt-4">
                        <h5 class="fw-semibold mb-3">
                            <i class="fas fa-toggle-on text-primary me-2"></i>Estado
                        </h5>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Estado</label>
                        <select class="form-select" name="estado">
                            <option value="activo" <?= ($equipo['estado'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= ($equipo['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Estado Operativo</label>
                        <select class="form-select" name="estado_operativo">
                            <option value="Operativo" <?= ($equipo['estado_operativo'] ?? 'Operativo') === 'Operativo' ? 'selected' : '' ?>>Operativo</option>
                            <option value="En Mantenimiento" <?= ($equipo['estado_operativo'] ?? '') === 'En Mantenimiento' ? 'selected' : '' ?>>En Mantenimiento</option>
                            <option value="Averiado" <?= ($equipo['estado_operativo'] ?? '') === 'Averiado' ? 'selected' : '' ?>>Averiado</option>
                            <option value="Fuera de Servicio" <?= ($equipo['estado_operativo'] ?? '') === 'Fuera de Servicio' ? 'selected' : '' ?>>Fuera de Servicio</option>
                        </select>
                    </div>
                </div>

                <hr>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Actualizar Equipo
                    </button>
                    <a href="/proyecto/equipos/ver/<?= $equipo['id_equipo'] ?>" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

</div>

<style>
.form-control, .form-select {
    border-radius: 10px;
    padding: 10px 14px;
    border: 2px solid #e9ecef;
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

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>