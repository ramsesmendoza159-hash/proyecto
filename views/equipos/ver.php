<?php
// views/equipos/ver.php
// Detalle del Equipo - CON CONTROL DE ROLES
// ✅ FIX: confirmarEliminar() ahora recibe id y nombre correctamente

if (!isset($seccion)) $seccion = 'equipos';
if (!isset($titulo)) $titulo = 'Detalle del Equipo';

$equipo = $equipo ?? null;
if (!$equipo) {
    header('Location: /proyecto/equipos');
    exit;
}

$rol = $_SESSION['rol'] ?? 'usuario';

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-industry text-primary me-2"></i><?= htmlspecialchars($equipo['nombre_equipo'] ?? '') ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Información completa del equipo
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/proyecto/equipos" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>

            <?php if (in_array($rol, ['admin', 'supervisor'])): ?>
                <a href="/proyecto/mantenimiento/equipo/<?= (int)$equipo['id_equipo'] ?>" class="btn btn-info text-white">
                    <i class="fas fa-tools me-1"></i> Mantenimiento
                </a>
                <a href="/proyecto/equipos/editar/<?= (int)$equipo['id_equipo'] ?>" class="btn btn-warning">
                    <i class="fas fa-edit me-1"></i> Editar
                </a>
            <?php endif; ?>
        </div>
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

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-info-circle text-primary me-2"></i>Información General
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Código</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($equipo['codigo'] ?? 'N/A') ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Nombre</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($equipo['nombre_equipo'] ?? '') ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Marca / Modelo</label>
                                <p class="fw-semibold mb-0">
                                    <?= htmlspecialchars($equipo['marca'] ?? 'N/A') ?>
                                    <?php if (!empty($equipo['modelo'])): ?>
                                        / <?= htmlspecialchars($equipo['modelo']) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Serie</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($equipo['serie'] ?? 'N/A') ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Fabricante</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($equipo['fabricante'] ?? 'N/A') ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Planta</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($equipo['nombre_planta'] ?? 'N/A') ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Área</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($equipo['nombre_area'] ?? 'N/A') ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Año Fabricación</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($equipo['año_fabricacion'] ?? 'N/A') ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Fecha Instalación</label>
                                <p class="fw-semibold mb-0">
                                    <?= !empty($equipo['fecha_instalacion']) ? date('d/m/Y', strtotime($equipo['fecha_instalacion'])) : 'N/A' ?>
                                </p>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Riesgo</label>
                                <p class="fw-semibold mb-0"><?= htmlspecialchars($equipo['ries'] ?? 'N/A') ?></p>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <label class="text-muted small fw-semibold text-uppercase">Estado</label>
                            <p>
                                <span class="badge bg-<?= ($equipo['estado'] ?? 'activo') === 'activo' ? 'success' : 'secondary' ?>">
                                    <?= ucfirst($equipo['estado'] ?? 'activo') ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-semibold text-uppercase">Estado Operativo</label>
                            <p>
                                <?php
                                $estadoOp = $equipo['estado_operativo'] ?? 'Operativo';
                                $color = 'success';
                                if ($estadoOp === 'En Mantenimiento') $color = 'warning';
                                elseif ($estadoOp === 'Averiado') $color = 'danger';
                                elseif ($estadoOp === 'Fuera de Servicio') $color = 'secondary';
                                ?>
                                <span class="badge bg-<?= $color ?>">
                                    <?= htmlspecialchars($estadoOp) ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($equipo['descripcion']) || !empty($equipo['descripcion_tec'])): ?>
                <div class="card border-0 mt-4">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h5 class="mb-0 fw-semibold">
                            <i class="fas fa-align-left text-primary me-2"></i>Descripciones
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($equipo['descripcion'])): ?>
                            <div class="mb-3">
                                <label class="text-muted small fw-semibold text-uppercase">Descripción General</label>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($equipo['descripcion'])) ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($equipo['descripcion_tec'])): ?>
                            <div>
                                <label class="text-muted small fw-semibold text-uppercase">Descripción Técnica</label>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($equipo['descripcion_tec'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <!-- Información de Horómetro -->
            <div class="card border-0 mb-4">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-tachometer-alt text-info me-2"></i> Horómetro
                    </h5>
                </div>
                <div class="card-body text-center">
                    <div class="h1 fw-bold text-primary mb-0">
                        <?= number_format($equipo['horometro_actual'] ?? 0) ?>
                    </div>
                    <div class="text-muted">Horas de uso</div>

                    <?php if (!empty($equipo['horometro_ultima_lectura'])): ?>
                        <div class="mt-2 small text-muted">
                            Última lectura: <?= date('d/m/Y H:i', strtotime($equipo['horometro_ultima_lectura'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Acciones -->
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-cogs text-primary me-2"></i>Acciones
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if (in_array($rol, ['admin', 'supervisor'])): ?>
                            <a href="/proyecto/mantenimiento/equipo/<?= (int)$equipo['id_equipo'] ?>" class="btn btn-info text-white">
                                <i class="fas fa-tools me-2"></i> Plan de Mantenimiento
                            </a>
                            <a href="/proyecto/equipos/editar/<?= (int)$equipo['id_equipo'] ?>" class="btn btn-warning">
                                <i class="fas fa-edit me-2"></i> Editar Equipo
                            </a>
                        <?php endif; ?>

                        <?php if (in_array($rol, ['admin', 'operador'])): ?>
                            <a href="/proyecto/ordenes/crear" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i> Crear Orden
                            </a>
                        <?php endif; ?>

                        <?php if ($rol === 'admin'): ?>
                            <button class="btn btn-outline-danger" onclick="confirmarEliminar()">
                                <i class="fas fa-trash me-2"></i> Eliminar
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
</style>

<script>
function confirmarEliminar() {
    if (confirm('¿Estás seguro de eliminar este equipo?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/proyecto/equipos/eliminar/<?= (int)$equipo['id_equipo'] ?>';
        form.innerHTML = '<input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>