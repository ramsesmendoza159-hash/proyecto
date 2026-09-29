<?php

if (!isset($registro) || empty($registro)) {
    die('<div class="alert alert-danger m-4">Error: Registro no disponible. <a href="/proyecto/auditoria">Volver</a></div>');
}

if (!isset($seccion)) {
    $seccion = 'auditoria';
}
if (!isset($titulo)) {
    $titulo = 'Detalle de Auditoría';
}

include_once __DIR__ . '/../../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-list text-primary me-2"></i>Detalle de Auditoría
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Registro #<?php echo $registro['id']; ?>
            </p>
        </div>
        <a href="/proyecto/auditoria" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <!-- Mensajes de error -->
    <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="info-label">Usuario</label>
                                <p class="info-value"><?php echo htmlspecialchars($registro['usuario_nombre']); ?></p>
                                <small class="info-sub">ID: <?php echo $registro['usuario_id']; ?></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="info-label">Rol</label>
                                <p>
                                    <span class="badge bg-<?php echo $registro['usuario_rol'] === 'admin' ? 'danger' : ($registro['usuario_rol'] === 'supervisor' ? 'warning' : ($registro['usuario_rol'] === 'almacen' ? 'info' : 'secondary')); ?>">
                                        <?php echo ucfirst($registro['usuario_rol']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="info-label">Acción</label>
                                <p>
                                    <span class="badge bg-<?php echo $registro['accion'] === 'login' ? 'success' : ($registro['accion'] === 'logout' ? 'secondary' : ($registro['accion'] === 'crear' ? 'primary' : ($registro['accion'] === 'actualizar' ? 'warning' : 'danger'))); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $registro['accion'])); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="info-label">Tabla</label>
                                <p class="info-value"><?php echo htmlspecialchars($registro['tabla']); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="info-label">Registro ID</label>
                                <p class="info-value"><?php echo $registro['registro_id'] ?? 'N/A'; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="info-label">IP</label>
                                <p class="info-value"><?php echo htmlspecialchars($registro['ip'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="info-label">Fecha</label>
                                <p class="info-value"><?php echo date('d/m/Y H:i:s', strtotime($registro['fecha_creacion'])); ?></p>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($registro['user_agent'])): ?>
                        <div class="mb-3">
                            <label class="info-label">User Agent</label>
                            <p class="info-value small mb-0"><?php echo htmlspecialchars($registro['user_agent']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Datos Anteriores -->
            <?php if (!empty($registro['datos_anteriores']) && $registro['datos_anteriores'] !== 'null'): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header border-0 bg-transparent pt-3">
                        <h6 class="mb-0 fw-semibold text-warning">
                            <i class="fas fa-arrow-left me-1"></i> Datos Anteriores
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php 
                            $datosAnteriores = json_decode($registro['datos_anteriores'], true);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                $datosAnteriores = null;
                            }
                            if ($datosAnteriores):
                        ?>
                            <pre class="json-viewer"><?php 
                                echo htmlspecialchars(
                                    json_encode($datosAnteriores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); 
                            ?></pre>
                        <?php else: ?>
                            <p class="info-value small mb-0">No hay datos anteriores</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Datos Nuevos -->
            <?php if (!empty($registro['datos_nuevos']) && $registro['datos_nuevos'] !== 'null'): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 bg-transparent pt-3">
                        <h6 class="mb-0 fw-semibold text-success">
                            <i class="fas fa-arrow-right me-1"></i> Datos Nuevos
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php 
                            $datosNuevos = json_decode($registro['datos_nuevos'], true);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                $datosNuevos = null;
                            }
                            if ($datosNuevos):
                        ?>
                            <pre class="json-viewer"><?php 
                                echo htmlspecialchars(
                                    json_encode($datosNuevos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); 
                            ?></pre>
                        <?php else: ?>
                            <p class="info-value small mb-0">No hay datos nuevos</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<style>
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
}

/* ========================================== */
/* INFO LABELS Y VALUES - ADAPTABLES AL TEMA */
/* ========================================== */

.info-label {
    display: block;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    color: var(--text-secondary) !important;
    margin-bottom: 4px;
}

.info-value {
    color: var(--text-primary) !important;
    font-weight: 500;
    font-size: 0.95rem;
    margin-bottom: 0;
    line-height: 1.5;
}

.info-sub {
    color: var(--text-muted) !important;
    font-size: 0.75rem;
}

/* Modo oscuro - reforzar */
[data-theme="dark"] .info-value {
    color: #ffffff !important;
}

[data-theme="dark"] .info-label {
    color: #8a8aa8 !important;
}

[data-theme="dark"] .info-sub {
    color: #8a8aa8 !important;
}

/* ========================================== */
/* VISOR JSON - ADAPTABLE AL TEMA */
/* ========================================== */

.json-viewer {
    background: var(--bg-input) !important;
    color: var(--text-primary) !important;
    border: 1px solid var(--border-color) !important;
    padding: 16px;
    border-radius: 8px;
    font-size: 0.75rem;
    margin: 0;
    max-height: 300px;
    overflow-y: auto;
    font-family: 'Courier New', monospace;
    white-space: pre-wrap;
    word-break: break-word;
}

[data-theme="dark"] .json-viewer {
    background: #0a0a0f !important;
    color: #93c5fd !important;
    border-color: #2a2a45 !important;
}

.json-viewer::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.json-viewer::-webkit-scrollbar-track {
    background: transparent;
}

.json-viewer::-webkit-scrollbar-thumb {
    background: var(--border-color);
    border-radius: 3px;
}

[data-theme="dark"] .json-viewer::-webkit-scrollbar-thumb {
    background: #2a2a45;
}

[data-theme="dark"] .json-viewer::-webkit-scrollbar-thumb:hover {
    background: #3b82f6;
}
</style>

<?php include_once __DIR__ . '/../../layouts/footer.php'; ?>