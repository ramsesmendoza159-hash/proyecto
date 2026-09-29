<?php
// views/mantenimiento/mis_tareas.php
// Listado de tareas asignadas al técnico
// ✅ FIX: botones en outline-* según la regla

if (!isset($seccion)) $seccion = 'mis_tareas';
if (!isset($titulo)) $titulo = 'Mis Mantenimientos';

$tareas = $tareas ?? [];
$estadisticas = $estadisticas ?? ['total' => 0, 'vencidas' => 0, 'proximas' => 0, 'al_dia' => 0];

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-tasks text-primary me-2"></i>Mis Mantenimientos
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Tareas de mantenimiento asignadas a ti
            </p>
        </div>
        <a href="/proyecto/tecnico" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

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

    <!-- Estadísticas -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(13, 110, 253, 0.15); color: #0d6efd;">
                        <i class="fas fa-tasks fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Total Asignadas</span>
                        <span class="stat-number-modern"><?= number_format($estadisticas['total'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(220, 53, 69, 0.15); color: #dc3545;">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Vencidas</span>
                        <span class="stat-number-modern"><?= number_format($estadisticas['vencidas'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(255, 193, 7, 0.15); color: #ffc107;">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Próximas</span>
                        <span class="stat-number-modern"><?= number_format($estadisticas['proximas'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(25, 135, 84, 0.15); color: #198754;">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Al Día</span>
                        <span class="stat-number-modern"><?= number_format($estadisticas['al_dia'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Listado de Tareas -->
    <div class="card border-0">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-list-check text-primary me-2"></i> Tareas Asignadas
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($tareas)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                    <h5>¡No tienes tareas pendientes!</h5>
                    <p class="text-muted">Todas tus tareas están completadas o no tienes asignaciones.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Equipo</th>
                                <th>Tarea</th>
                                <th>Estado</th>
                                <th>Alerta</th>
                                <th>Horas Rest.</th>
                                <th>Días Rest.</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tareas as $tarea):
                                $estado_alerta = $tarea['estado_alerta'] ?? 'AL_DIA';
                                $color = 'success';
                                $icono = 'fa-check-circle';
                                $texto = 'Al Día';

                                if ($estado_alerta === 'VENCIDO') {
                                    $color = 'danger'; $icono = 'fa-exclamation-triangle'; $texto = 'Vencido';
                                } elseif ($estado_alerta === 'PROXIMO') {
                                    $color = 'warning'; $icono = 'fa-clock'; $texto = 'Próximo';
                                }
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($tarea['nombre_equipo'] ?? 'N/A') ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($tarea['codigo'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($tarea['nombre_tarea'] ?? 'N/A') ?></div>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary small">
                                            <?= htmlspecialchars($tarea['tipo_mantenimiento'] ?? 'PREVENTIVO') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            <?= htmlspecialchars($tarea['estado_asignacion'] ?? 'ASIGNADO') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?> px-3 py-2">
                                            <i class="fas <?= $icono ?> me-1"></i><?= $texto ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (isset($tarea['horas_restantes'])): ?>
                                            <span class="fw-semibold <?= $tarea['horas_restantes'] <= 0 ? 'text-danger' : ($tarea['horas_restantes'] <= 100 ? 'text-warning' : 'text-success') ?>">
                                                <?= number_format($tarea['horas_restantes']) ?> h
                                            </span>
                                        <?php else: ?>-<?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($tarea['dias_restantes'])): ?>
                                            <span class="fw-semibold <?= $tarea['dias_restantes'] <= 0 ? 'text-danger' : ($tarea['dias_restantes'] <= 30 ? 'text-warning' : 'text-success') ?>">
                                                <?= $tarea['dias_restantes'] ?> días
                                            </span>
                                        <?php else: ?>-<?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="/proyecto/mantenimiento/ejecutar/<?= (int)$tarea['id'] ?>" 
                                               class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-play me-1"></i> Ejecutar
                                            </a>
                                            <a href="/proyecto/mantenimiento/equipo/<?= (int)$tarea['id_equipo'] ?>" 
                                               class="btn btn-sm btn-outline-info" title="Ver equipo">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<style>
.stat-card-modern {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 20px 24px;
    height: 100%;
}
.stat-card-modern .stat-icon-modern {
    width: 56px; height: 56px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.stat-card-modern .stat-label-modern {
    font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;
    color: var(--text-secondary); font-weight: 600; display: block;
}
.stat-card-modern .stat-number-modern {
    font-size: 1.6rem; font-weight: 700;
    color: var(--text-primary); display: block; line-height: 1.2;
}
.card { border-radius: 16px; box-shadow: 0 2px 12px var(--shadow-color); }
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>