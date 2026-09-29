<?php
// views/mantenimiento/detalle.php
// Detalle completo de un mantenimiento realizado
// ✅ Visible para admin, supervisor, ingeniero y consultor

if (!isset($seccion)) $seccion = 'mantenimiento';
if (!isset($titulo)) $titulo = 'Detalle del Mantenimiento';

$mantenimiento = $mantenimiento ?? null;
$checklist = $checklist ?? [];
$estadisticas_checklist = $estadisticas_checklist ?? [
    'total' => 0, 'cumplidos' => 0, 'con_foto' => 0, 'porcentaje' => 0
];

if (!$mantenimiento) {
    header('Location: /proyecto/mantenimiento/panel');
    exit;
}

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-check text-success me-2"></i>Detalle del Mantenimiento
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-calendar-alt me-1"></i>
                <?= date('d/m/Y H:i', strtotime($mantenimiento['fecha_mantenimiento'])) ?>
                <span class="mx-2">|</span>
                <i class="fas fa-industry me-1"></i>
                <strong><?= htmlspecialchars($mantenimiento['nombre_equipo']) ?></strong>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/proyecto/mantenimiento/equipo/<?= $mantenimiento['id_equipo'] ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver al Equipo
            </a>
            <?php if (($rol = $_SESSION['rol'] ?? '') === 'admin' || $rol === 'supervisor'): ?>
                <button onclick="window.print()" class="btn btn-outline-primary">
                    <i class="fas fa-print me-1"></i> Imprimir
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tarjetas de resumen -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(25,135,84,0.1);color:#198754;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-label">Estado</div>
                        <div class="stat-number-mini" style="font-size:1rem;">
                            <?php 
                            $cumplidos = $estadisticas_checklist['cumplidos'];
                            $total = $estadisticas_checklist['total'];
                            if ($total > 0 && $cumplidos == $total) {
                                echo '<span class="badge bg-success">Completado</span>';
                            } elseif ($cumplidos > 0) {
                                echo '<span class="badge bg-warning">Parcial</span>';
                            } else {
                                echo '<span class="badge bg-secondary">Sin datos</span>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(13,110,253,0.1);color:#0d6efd;">
                        <i class="fas fa-list-check"></i>
                    </div>
                    <div>
                        <div class="stat-label">Checklist</div>
                        <div class="stat-number-mini" style="font-size:1rem;">
                            <?= $cumplidos ?>/<?= $total ?> (<?= $estadisticas_checklist['porcentaje'] ?>%)
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(255,193,7,0.1);color:#ffc107;">
                        <i class="fas fa-camera"></i>
                    </div>
                    <div>
                        <div class="stat-label">Fotos de Evidencia</div>
                        <div class="stat-number-mini" style="font-size:1rem;">
                            <?= $estadisticas_checklist['con_foto'] ?> foto(s)
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(220,53,69,0.1);color:#dc3545;">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div>
                        <div class="stat-label">Costo</div>
                        <div class="stat-number-mini" style="font-size:1rem;">
                            S/ <?= number_format($mantenimiento['costo'] ?? 0, 2) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Columna principal: Checklist con fotos -->
        <div class="col-lg-8">
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-clipboard-list text-primary me-2"></i> Checklist Ejecutado
                    </h5>
                    <p class="text-muted small mb-0 mt-1">
                        Evidencia del trabajo realizado por el técnico
                    </p>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($checklist)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-clipboard fa-3x d-block mb-3"></i>
                            <h5>No hay checklist registrado</h5>
                            <p>Este mantenimiento no tiene checklist asociado</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($checklist as $index => $paso): ?>
                            <div class="checklist-item-detail p-3 border-bottom" style="background: var(--bg-card);">
                                <div class="d-flex align-items-start gap-3">
                                    <!-- Número y estado -->
                                    <div class="checklist-number">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width:40px;height:40px;background:<?= $paso['cumplido'] ? 'rgba(25,135,84,0.15)' : 'rgba(220,53,69,0.15)' ?>;color:<?= $paso['cumplido'] ? '#198754' : '#dc3545' ?>;font-weight:700;">
                                            <?php if ($paso['cumplido']): ?>
                                                <i class="fas fa-check"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times"></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Contenido -->
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="fw-semibold mb-0">
                                                <span class="badge bg-primary me-2"><?= $paso['paso_numero'] ?></span>
                                                <?= htmlspecialchars($paso['paso_descripcion']) ?>
                                            </h6>
                                            <?php if ($paso['requiere_foto']): ?>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-camera me-1"></i>Foto requerida
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($paso['cumplido']): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success mb-2">
                                                <i class="fas fa-check-circle me-1"></i>Completado
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger mb-2">
                                                <i class="fas fa-times-circle me-1"></i>No completado
                                            </span>
                                        <?php endif; ?>
                                        
                                        <!-- Observaciones del técnico -->
                                        <?php if (!empty($paso['observaciones'])): ?>
                                            <div class="mt-2 p-2 rounded" style="background: var(--bg-card-hover); border-left: 3px solid #0d6efd;">
                                                <small class="text-muted fw-semibold">
                                                    <i class="fas fa-comment me-1"></i>Observación del técnico:
                                                </small>
                                                <p class="mb-0 small"><?= nl2br(htmlspecialchars($paso['observaciones'])) ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Foto de evidencia -->
                                        <?php if (!empty($paso['foto_evidencia'])): ?>
                                            <div class="mt-2">
                                                <small class="text-muted fw-semibold">
                                                    <i class="fas fa-camera text-primary me-1"></i>Foto de evidencia:
                                                </small>
                                                <div class="mt-1">
                                                    <a href="/proyecto/<?= htmlspecialchars($paso['foto_evidencia']) ?>" 
                                                       target="_blank"
                                                       data-bs-toggle="tooltip" 
                                                       title="Clic para ver tamaño completo">
                                                        <img src="/proyecto/<?= htmlspecialchars($paso['foto_evidencia']) ?>" 
                                                             alt="Evidencia del paso <?= $paso['paso_numero'] ?>"
                                                             class="img-thumbnail foto-evidencia"
                                                             style="max-height: 200px; max-width: 100%; cursor: pointer;"
                                                             onerror="this.parentElement.innerHTML='<div class=&quot;alert alert-warning mb-0 small&quot;><i class=&quot;fas fa-exclamation-triangle me-1&quot;></i>No se pudo cargar la imagen</div>'">
                                                    </a>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <?php if ($paso['requiere_foto']): ?>
                                                <div class="mt-2 alert alert-warning py-2 px-3 mb-0 small">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    Este paso requería foto pero no se subió ninguna
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Columna lateral: Info del mantenimiento -->
        <div class="col-lg-4">
            <!-- Info del mantenimiento -->
            <div class="card border-0 mb-4">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-info-circle text-primary me-2"></i> Información
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Equipo</label>
                        <p class="fw-semibold mb-0"><?= htmlspecialchars($mantenimiento['nombre_equipo']) ?></p>
                        <small class="text-muted">
                            Código: <?= htmlspecialchars($mantenimiento['codigo'] ?? 'N/A') ?>
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Ubicación</label>
                        <p class="fw-semibold mb-0">
                            <?= htmlspecialchars($mantenimiento['nombre_planta'] ?? 'N/A') ?>
                            <?php if (!empty($mantenimiento['nombre_area'])): ?>
                                → <?= htmlspecialchars($mantenimiento['nombre_area']) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Tipo de Mantenimiento</label>
                        <p class="mb-0">
                            <span class="badge bg-info bg-opacity-10 text-info">
                                <?= htmlspecialchars($mantenimiento['tipo'] ?? $mantenimiento['tipo_mantenimiento'] ?? 'N/A') ?>
                            </span>
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Fecha de Realización</label>
                        <p class="fw-semibold mb-0">
                            <?= date('d/m/Y H:i', strtotime($mantenimiento['fecha_mantenimiento'])) ?>
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Horas del Equipo</label>
                        <p class="fw-semibold mb-0">
                            <?= number_format($mantenimiento['horas_equipo'] ?? 0) ?> h
                        </p>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Técnico</label>
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar-sm" style="width:32px;height:32px;border-radius:50%;background:#0d6efd;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:0.75rem;">
                                <?= strtoupper(substr($mantenimiento['tecnico_nombre'] ?? 'T', 0, 1)) ?>
                            </div>
                            <div>
                                <p class="fw-semibold mb-0 small">
                                    <?= htmlspecialchars($mantenimiento['tecnico_nombre'] ?? 'Sin asignar') ?>
                                </p>
                                <?php if (!empty($mantenimiento['tecnico_especialidad'])): ?>
                                    <small class="text-muted"><?= htmlspecialchars($mantenimiento['tecnico_especialidad']) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Descripción y observaciones -->
            <?php if (!empty($mantenimiento['descripcion']) || !empty($mantenimiento['observaciones']) || !empty($mantenimiento['repuestos_usados'])): ?>
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-align-left text-primary me-2"></i> Detalles del Trabajo
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($mantenimiento['descripcion'])): ?>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Descripción</label>
                            <p class="mb-0 small"><?= nl2br(htmlspecialchars($mantenimiento['descripcion'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($mantenimiento['repuestos_usados'])): ?>
                        <div class="mb-3">
                            <label class="text-muted small fw-semibold text-uppercase">Repuestos Usados</label>
                            <p class="mb-0 small"><?= nl2br(htmlspecialchars($mantenimiento['repuestos_usados'])) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($mantenimiento['observaciones'])): ?>
                        <div class="mb-0">
                            <label class="text-muted small fw-semibold text-uppercase">Observaciones</label>
                            <p class="mb-0 small"><?= nl2br(htmlspecialchars($mantenimiento['observaciones'])) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<style>
.checklist-item-detail {
    transition: background 0.2s ease;
}
.checklist-item-detail:hover {
    background: var(--bg-card-hover) !important;
}
.foto-evidencia {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-radius: 8px;
}
.foto-evidencia:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}
.stat-card-mini {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 16px 20px;
    transition: all 0.3s ease;
}
.stat-card-mini:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px var(--shadow-hover);
}
.stat-card-mini .stat-icon-mini {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}
.stat-card-mini .stat-label {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
    font-weight: 600;
}
.stat-card-mini .stat-number-mini {
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.2;
}
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
}
</style>

<script>
// Inicializar tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>