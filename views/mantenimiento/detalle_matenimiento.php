<?php
// views/mantenimiento/detalle_mantenimiento.php
// Detalle completo de un mantenimiento realizado
// VERSIÓN FINAL - Con botones de edición y textos legibles en modo oscuro

if (!isset($seccion)) $seccion = 'mantenimiento';
if (!isset($titulo)) $titulo = 'Detalle del Mantenimiento';

$mantenimiento = $mantenimiento ?? null;
$equipo = $equipo ?? null;
$plan = $plan ?? null;
$checklist = $checklist ?? [];

if (!$mantenimiento) {
    header('Location: /proyecto/mantenimiento/panel');
    exit;
}

$puede_editar = in_array($_SESSION['rol'] ?? '', ['admin', 'supervisor']);

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-check text-primary me-2"></i>Detalle del Mantenimiento
                <span class="badge bg-primary ms-2">#<?= $mantenimiento['id'] ?></span>
                <?php if (!empty($mantenimiento['es_retroactivo'])): ?>
                    <span class="badge bg-warning text-dark ms-1">
                        <i class="fas fa-clock-rotate-left me-1"></i>Retroactivo
                    </span>
                <?php endif; ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-calendar-alt me-1"></i> 
                <?= date('d/m/Y H:i:s', strtotime($mantenimiento['fecha_mantenimiento'])) ?>
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="/proyecto/mantenimiento/equipo/<?= $mantenimiento['id_equipo'] ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver al Equipo
            </a>
            
            <?php if ($puede_editar): ?>
                <a href="/proyecto/mantenimiento/editar-mantenimiento/<?= $mantenimiento['id'] ?>" 
                   class="btn btn-warning">
                    <i class="fas fa-edit me-1"></i> Editar
                </a>
                <a href="/proyecto/mantenimiento/editar-checklist/<?= $mantenimiento['id'] ?>" 
                   class="btn btn-outline-warning">
                    <i class="fas fa-tasks me-1"></i> Editar Checklist
                </a>
            <?php endif; ?>
            
            <button onclick="window.print()" class="btn btn-outline-primary">
                <i class="fas fa-print me-1"></i> Imprimir
            </button>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?= $_SESSION['mensaje_tipo'] ?? 'success' ?> alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['mensaje'] ?>
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

    <!-- Tarjetas resumen -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern">
                        <i class="fas fa-industry fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Equipo</span>
                        <span class="stat-number-modern" style="font-size: 0.95rem;">
                            <?= htmlspecialchars(substr($equipo['nombre_equipo'] ?? 'N/A', 0, 20)) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern">
                        <i class="fas fa-tachometer-alt fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Horómetro</span>
                        <span class="stat-number-modern"><?= number_format($mantenimiento['horas_equipo'] ?? 0) ?> h</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern">
                        <i class="fas fa-money-bill-wave fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Costo</span>
                        <span class="stat-number-modern">S/ <?= number_format($mantenimiento['costo'] ?? 0, 2) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern">
                        <i class="fas fa-tasks fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Pasos Cumplidos</span>
                        <span class="stat-number-modern">
                            <?= count(array_filter($checklist, fn($p) => !empty($p['cumplido']))) ?>/<?= count($checklist) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Información principal -->
        <div class="col-lg-8">
            <div class="card border-0 mb-4">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold text-primary">
                        <i class="fas fa-info-circle me-2"></i>Información del Mantenimiento
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <label class="info-label">Equipo</label>
                                <p class="info-value"><?= htmlspecialchars($equipo['nombre_equipo'] ?? 'N/A') ?></p>
                                <small class="info-sub">Código: <?= htmlspecialchars($equipo['codigo'] ?? 'N/A') ?></small>
                            </div>
                            <div class="info-item mb-3">
                                <label class="info-label">Tipo de Mantenimiento</label>
                                <p class="info-value">
                                    <span class="badge bg-info bg-opacity-10 text-info px-3 py-2">
                                        <i class="fas fa-tag me-1"></i>
                                        <?= htmlspecialchars($mantenimiento['tipo'] ?? 'N/A') ?>
                                    </span>
                                </p>
                            </div>
                            <div class="info-item mb-3">
                                <label class="info-label">Descripción</label>
                                <p class="info-value">
                                    <?= nl2br(htmlspecialchars($mantenimiento['descripcion'] ?? 'Sin descripción')) ?>
                                </p>
                            </div>
                            <?php if (!empty($mantenimiento['repuestos_usados'])): ?>
                                <div class="info-item mb-3">
                                    <label class="info-label">Repuestos Usados</label>
                                    <div class="repuestos-box">
                                        <i class="fas fa-tools me-2"></i>
                                        <span><?= nl2br(htmlspecialchars($mantenimiento['repuestos_usados'])) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item mb-3">
                                <label class="info-label">Fecha del Mantenimiento</label>
                                <p class="info-value">
                                    <?= date('d/m/Y', strtotime($mantenimiento['fecha_mantenimiento'])) ?>
                                    <small class="info-sub ms-2"><?= date('H:i:s', strtotime($mantenimiento['fecha_mantenimiento'])) ?></small>
                                </p>
                                <?php if (!empty($mantenimiento['es_retroactivo'])): ?>
                                    <small class="badge bg-warning bg-opacity-10 text-warning">
                                        <i class="fas fa-clock-rotate-left me-1"></i>
                                        Registro retroactivo
                                    </small>
                                <?php endif; ?>
                            </div>
                            <div class="info-item mb-3">
                                <label class="info-label">Horómetro al Momento</label>
                                <p class="info-value">
                                    <span class="badge bg-primary fs-6 px-3 py-2">
                                        <i class="fas fa-tachometer-alt me-1"></i>
                                        <?= number_format($mantenimiento['horas_equipo'] ?? 0) ?> horas
                                    </span>
                                </p>
                            </div>
                            <?php if (!empty($mantenimiento['tecnico_nombre'])): ?>
                                <div class="info-item mb-3">
                                    <label class="info-label">Técnico Ejecutor</label>
                                    <p class="info-value">
                                        <i class="fas fa-user-cog text-primary me-1"></i>
                                        <?= htmlspecialchars($mantenimiento['tecnico_nombre']) ?>
                                    </p>
                                    <?php if (!empty($mantenimiento['tecnico_especialidad'])): ?>
                                        <small class="info-sub d-block">
                                            Especialidad: <?= htmlspecialchars($mantenimiento['tecnico_especialidad']) ?>
                                        </small>
                                    <?php endif; ?>
                                    <?php if (!empty($mantenimiento['tecnico_tarifa'])): ?>
                                        <small class="info-sub d-block">
                                            Tarifa: S/ <?= number_format($mantenimiento['tecnico_tarifa'], 2) ?>/h
                                        </small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($mantenimiento['proveedor_nombre'])): ?>
                                <div class="info-item mb-3">
                                    <label class="info-label">Proveedor Externo</label>
                                    <p class="info-value">
                                        <i class="fas fa-truck text-warning me-1"></i>
                                        <?= htmlspecialchars($mantenimiento['proveedor_nombre']) ?>
                                    </p>
                                    <?php if (!empty($mantenimiento['proveedor_contacto'])): ?>
                                        <small class="info-sub d-block">
                                            Contacto: <?= htmlspecialchars($mantenimiento['proveedor_contacto']) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($mantenimiento['nombre_tarea'])): ?>
                                <div class="info-item mb-3">
                                    <label class="info-label">Tarea del Plan</label>
                                    <p class="info-value"><?= htmlspecialchars($mantenimiento['nombre_tarea']) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($mantenimiento['observaciones'])): ?>
                        <hr>
                        <div class="info-item">
                            <label class="info-label">Observaciones</label>
                            <p class="info-value mb-0">
                                <?= nl2br(htmlspecialchars($mantenimiento['observaciones'])) ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Checklist ejecutado -->
            <?php if (!empty($checklist)): ?>
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0 fw-semibold text-primary">
                        <i class="fas fa-tasks me-2"></i>Checklist Ejecutado
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <?php 
                        $cumplidos = count(array_filter($checklist, fn($p) => !empty($p['cumplido'])));
                        $total = count($checklist);
                        $porcentaje = $total > 0 ? round(($cumplidos / $total) * 100) : 0;
                        $badgeColor = $porcentaje == 100 ? 'success' : ($porcentaje >= 50 ? 'warning' : 'danger');
                        ?>
                        <span class="badge bg-<?= $badgeColor ?>">
                            <?= $cumplidos ?>/<?= $total ?> pasos (<?= $porcentaje ?>%)
                        </span>
                        <?php if ($puede_editar): ?>
                            <a href="/proyecto/mantenimiento/editar-checklist/<?= $mantenimiento['id'] ?>" 
                               class="btn btn-sm btn-outline-warning">
                                <i class="fas fa-edit me-1"></i> Editar
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="progress mb-4" style="height: 10px;">
                        <div class="progress-bar bg-<?= $badgeColor ?>" style="width: <?= $porcentaje ?>%;"></div>
                    </div>
                    <div class="checklist-container">
                        <?php foreach ($checklist as $index => $paso): ?>
                            <div class="checklist-item-detail <?= !empty($paso['cumplido']) ? 'completed' : 'pending' ?>">
                                <div class="checklist-icon">
                                    <?php if (!empty($paso['cumplido'])): ?>
                                        <span class="badge bg-success rounded-circle">
                                            <i class="fas fa-check"></i>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger rounded-circle">
                                            <i class="fas fa-times"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="checklist-content">
                                    <div class="checklist-title">
                                        <span class="badge bg-primary me-2">
                                            <?= $paso['paso_numero'] ?? ($index + 1) ?>
                                        </span>
                                        <?= htmlspecialchars($paso['descripcion'] ?? $paso['paso_descripcion'] ?? 'N/A') ?>
                                    </div>
                                    <?php if (!empty($paso['observaciones'])): ?>
                                        <div class="checklist-observation">
                                            <i class="fas fa-comment me-1"></i>
                                            <?= htmlspecialchars($paso['observaciones']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($paso['foto_evidencia'])): ?>
                                        <div class="mt-2">
                                            <a href="/proyecto/uploads/mantenimiento/<?= $paso['foto_evidencia'] ?>" 
                                               target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-image me-1"></i> Ver evidencia
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card border-0">
                <div class="card-body text-center py-5">
                    <i class="fas fa-clipboard fa-4x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">Sin checklist registrado</h5>
                    <p class="text-muted mb-0">Este mantenimiento no tiene un protocolo de checklist ejecutado.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Columna lateral -->
        <div class="col-lg-4">
            <!-- Costos -->
            <div class="card border-0 mb-4">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold text-primary">
                        <i class="fas fa-money-bill-wave me-2"></i>Costos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <span class="info-label mb-0">Costo Total</span>
                        <span class="fw-bold text-primary" style="font-size: 1.5rem;">
                            S/ <?= number_format($mantenimiento['costo'] ?? 0, 2) ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($plan['costo_estimado'])): 
                        $costoReal = (float)($mantenimiento['costo'] ?? 0);
                        $costoEstimado = (float)$plan['costo_estimado'];
                        $diferencia = $costoReal - $costoEstimado;
                    ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="info-label mb-0">Costo Estimado</span>
                            <span class="info-value-sm">S/ <?= number_format($costoEstimado, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="info-label mb-0">Diferencia</span>
                            <span class="info-value-sm <?= $diferencia > 0 ? 'text-danger' : ($diferencia < 0 ? 'text-success' : 'text-muted') ?>">
                                <?= $diferencia > 0 ? '+' : '' ?>S/ <?= number_format($diferencia, 2) ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info del equipo -->
            <?php if ($equipo): ?>
            <div class="card border-0 mb-4">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold text-primary">
                        <i class="fas fa-industry me-2"></i>Equipo
                    </h5>
                </div>
                <div class="card-body">
                    <div class="info-item mb-3">
                        <label class="info-label">Nombre</label>
                        <p class="info-value"><?= htmlspecialchars($equipo['nombre_equipo'] ?? 'N/A') ?></p>
                    </div>
                    <?php if (!empty($equipo['marca'])): ?>
                        <div class="info-item mb-3">
                            <label class="info-label">Marca / Modelo</label>
                            <p class="info-value">
                                <?= htmlspecialchars($equipo['marca']) ?>
                                <?php if (!empty($equipo['modelo'])): ?>
                                    / <?= htmlspecialchars($equipo['modelo']) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    <div class="info-item mb-0">
                        <label class="info-label">Horómetro Actual</label>
                        <p class="info-value mb-0">
                            <span class="badge bg-primary">
                                <?= number_format($equipo['horometro_actual'] ?? 0) ?> h
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Acciones -->
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold text-primary">
                        <i class="fas fa-cogs me-2"></i>Acciones
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($puede_editar): ?>
                            <a href="/proyecto/mantenimiento/editar-mantenimiento/<?= $mantenimiento['id'] ?>" 
                               class="btn btn-warning">
                                <i class="fas fa-edit me-2"></i> Editar Mantenimiento
                            </a>
                            <a href="/proyecto/mantenimiento/editar-checklist/<?= $mantenimiento['id'] ?>" 
                               class="btn btn-outline-warning">
                                <i class="fas fa-tasks me-2"></i> Editar Checklist
                            </a>
                        <?php endif; ?>
                        <a href="/proyecto/mantenimiento/equipo/<?= $mantenimiento['id_equipo'] ?>" 
                           class="btn btn-primary">
                            <i class="fas fa-industry me-2"></i> Ver Plan del Equipo
                        </a>
                        <button onclick="window.print()" class="btn btn-outline-secondary">
                            <i class="fas fa-print me-2"></i> Imprimir Detalle
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ========================================== -->
<!-- ESTILOS - CORREGIDOS PARA MODO OSCURO -->
<!-- ========================================== -->
<style>
/* ========================================== */
/* TARJETAS DE RESUMEN (stats) */
/* ========================================== */
.stat-card-modern {
    background: var(--bg-card) !important;
    border: 1px solid var(--border-color) !important;
    border-radius: 16px !important;
    padding: 20px 24px !important;
    box-shadow: 0 2px 12px var(--shadow-color) !important;
    transition: all 0.3s ease !important;
    height: 100% !important;
}
.stat-card-modern:hover {
    transform: translateY(-4px) !important;
    box-shadow: 0 8px 30px var(--shadow-hover) !important;
    border-color: rgba(59, 130, 246, 0.4) !important;
}
.stat-card-modern .stat-icon-modern {
    width: 56px !important;
    height: 56px !important;
    border-radius: 14px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(96, 165, 250, 0.1)) !important;
    color: #3b82f6 !important;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.15) !important;
}
.stat-card-modern .stat-label-modern {
    font-size: 0.7rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    color: var(--text-secondary) !important;
    font-weight: 600 !important;
    display: block !important;
}
.stat-card-modern .stat-number-modern {
    font-size: 1.6rem !important;
    font-weight: 700 !important;
    color: var(--text-primary) !important;
    display: block !important;
    line-height: 1.2 !important;
}

/* ========================================== */
/* INFO ITEMS - TEXTOS LEGIBLES EN MODO OSCURO */
/* ========================================== */
.info-item {
    padding: 0;
}

.info-label {
    display: block;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 4px;
}

.info-value {
    color: var(--text-primary) !important;
    font-weight: 500;
    font-size: 0.95rem;
    margin-bottom: 0;
    line-height: 1.5;
}

.info-value-sm {
    color: var(--text-primary) !important;
    font-weight: 600;
    font-size: 0.9rem;
}

.info-sub {
    color: var(--text-muted) !important;
    font-size: 0.75rem;
}

/* Modo oscuro: reforzar los colores */
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
/* CAJA DE REPUESTOS */
/* ========================================== */
.repuestos-box {
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.25);
    border-radius: 10px;
    padding: 12px 16px;
    color: var(--text-primary) !important;
    font-size: 0.9rem;
}

[data-theme="dark"] .repuestos-box {
    background: rgba(59, 130, 246, 0.12);
    color: #ffffff !important;
}

/* ========================================== */
/* CHECKLIST CONTAINER */
/* ========================================== */
.checklist-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.checklist-item-detail {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 16px;
    border-radius: 12px;
    border: 1px solid var(--border-color);
    background: var(--bg-card);
    transition: all 0.3s ease;
}

.checklist-item-detail.completed {
    border-left: 4px solid #22c55e;
}

.checklist-item-detail.pending {
    border-left: 4px solid #ef4444;
}

.checklist-item-detail:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px var(--shadow-hover);
}

.checklist-icon {
    flex-shrink: 0;
}

.checklist-icon .badge {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

.checklist-content {
    flex-grow: 1;
}

.checklist-title {
    color: var(--text-primary) !important;
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 4px;
}

[data-theme="dark"] .checklist-title {
    color: #ffffff !important;
}

.checklist-observation {
    background: rgba(255, 193, 7, 0.1);
    border-left: 3px solid #ffc107;
    border-radius: 6px;
    padding: 8px 12px;
    margin-top: 8px;
    font-size: 0.85rem;
    color: var(--text-primary) !important;
}

[data-theme="dark"] .checklist-observation {
    background: rgba(234, 179, 8, 0.12);
    color: #facc15 !important;
}

/* ========================================== */
/* CARDS GENERALES */
/* ========================================== */
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
}

/* Textos dentro de las cards en modo oscuro */
[data-theme="dark"] .card-body p,
[data-theme="dark"] .card-body div:not([class*="badge"]):not([class*="alert"]):not([class*="progress"]):not([class*="checklist-"]):not([class*="repuestos-"]) {
    color: #ffffff;
}

[data-theme="dark"] .card-body strong,
[data-theme="dark"] .card-body b,
[data-theme="dark"] .card-body .fw-semibold,
[data-theme="dark"] .card-body .fw-bold {
    color: #ffffff !important;
}

[data-theme="dark"] .card-body small,
[data-theme="dark"] .card-body .text-muted {
    color: #8a8aa8 !important;
}

[data-theme="dark"] .card-body label:not(.form-label) {
    color: #8a8aa8 !important;
}

/* ========================================== */
/* PROGRESS BAR */
/* ========================================== */
.progress {
    border-radius: 10px;
    background: var(--bg-input-disabled);
    overflow: hidden;
}

[data-theme="dark"] .progress {
    background: #1a1a2e !important;
}

.progress-bar {
    transition: width 0.6s ease;
}

[data-theme="dark"] .progress-bar.bg-success {
    background: linear-gradient(90deg, #22c55e, #4ade80) !important;
    box-shadow: 0 0 10px rgba(34, 197, 94, 0.5);
}

[data-theme="dark"] .progress-bar.bg-warning {
    background: linear-gradient(90deg, #eab308, #facc15) !important;
    box-shadow: 0 0 10px rgba(234, 179, 8, 0.5);
}

[data-theme="dark"] .progress-bar.bg-danger {
    background: linear-gradient(90deg, #ef4444, #f87171) !important;
    box-shadow: 0 0 10px rgba(239, 68, 68, 0.5);
}

/* ========================================== */
/* BADGES MEJORADOS EN MODO OSCURO */
/* ========================================== */
[data-theme="dark"] .badge.bg-info {
    background: rgba(6, 182, 212, 0.2) !important;
    color: #22d3ee !important;
    border: 1px solid rgba(6, 182, 212, 0.3) !important;
}

[data-theme="dark"] .badge.bg-primary {
    background: rgba(59, 130, 246, 0.2) !important;
    color: #93c5fd !important;
    border: 1px solid rgba(59, 130, 246, 0.3) !important;
}

[data-theme="dark"] .badge.bg-success {
    background: rgba(34, 197, 94, 0.2) !important;
    color: #4ade80 !important;
    border: 1px solid rgba(34, 197, 94, 0.3) !important;
}

[data-theme="dark"] .badge.bg-danger {
    background: rgba(239, 68, 68, 0.2) !important;
    color: #f87171 !important;
    border: 1px solid rgba(239, 68, 68, 0.3) !important;
}

[data-theme="dark"] .badge.bg-warning {
    background: rgba(234, 179, 8, 0.2) !important;
    color: #facc15 !important;
    border: 1px solid rgba(234, 179, 8, 0.3) !important;
}

/* ========================================== */
/* SEPARADORES */
/* ========================================== */
[data-theme="dark"] hr {
    border-color: #1f1f35 !important;
    opacity: 1 !important;
}

/* ========================================== */
/* ESTILOS PARA IMPRESIÓN */
/* ========================================== */
@media print {
    .btn, .sidebar, .topbar, .stat-card-modern, .d-flex.gap-2 {
        display: none !important;
    }
    .main-content .content {
        padding: 0 !important;
    }
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        page-break-inside: avoid;
    }
    body {
        background: white !important;
        color: black !important;
    }
    .info-value, .checklist-title {
        color: black !important;
    }
    .info-label {
        color: #666 !important;
    }
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>