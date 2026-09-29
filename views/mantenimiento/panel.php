<?php
// views/mantenimiento/panel.php
// Panel de control del mantenimiento preventivo
// ✅ FIX: botones en outline-* según la regla

if (!isset($seccion)) $seccion = 'mantenimiento';
if (!isset($titulo)) $titulo = 'Panel de Mantenimiento Preventivo';

$alertas = $alertas ?? [];
$resumen = $resumen ?? ['vencidos_horas' => 0, 'vencidos_fecha' => 0, 'proximos_horas' => 0, 'proximos_fecha' => 0, 'total_alertas' => 0];
$estadisticas = $estadisticas ?? ['total_equipos' => 0, 'total_planes' => 0, 'mantenimientos_mes' => 0, 'costo_mes' => 0];
$historial_reciente = $historial_reciente ?? [];

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-tools text-primary me-2"></i>Panel de Mantenimiento Preventivo
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Control de mantenimiento por horas de uso
                <?php if ($resumen['total_alertas'] > 0): ?>
                    <span class="badge bg-danger ms-2"><?= $resumen['total_alertas'] ?> alertas</span>
                <?php endif; ?>
            </p>
        </div>
        <a href="/proyecto/equipos" class="btn btn-secondary">
            <i class="fas fa-industry me-1"></i> Ver Equipos
        </a>
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

    <!-- Tarjetas de Estadísticas -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(13, 110, 253, 0.15); color: #0d6efd;">
                        <i class="fas fa-industry fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Equipos Activos</span>
                        <span class="stat-number-modern"><?= number_format($estadisticas['total_equipos'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(255, 193, 7, 0.15); color: #ffc107;">
                        <i class="fas fa-clipboard-list fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Planes Activos</span>
                        <span class="stat-number-modern"><?= number_format($estadisticas['total_planes'] ?? 0) ?></span>
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
                        <span class="stat-label-modern">Mant. este Mes</span>
                        <span class="stat-number-modern"><?= number_format($estadisticas['mantenimientos_mes'] ?? 0) ?></span>
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
                        <span class="stat-label-modern">Alertas</span>
                        <span class="stat-number-modern"><?= number_format($resumen['total_alertas'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen de Alertas -->
    <?php if ($resumen['total_alertas'] > 0): ?>
    <div class="row g-3 mb-4">
        <?php if ($resumen['vencidos_horas'] > 0): ?>
        <div class="col-md-3">
            <div class="alert alert-danger mb-0">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-clock fa-2x"></i>
                    <div>
                        <div class="small">Vencidos por Horas</div>
                        <div class="h4 mb-0"><?= $resumen['vencidos_horas'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($resumen['vencidos_fecha'] > 0): ?>
        <div class="col-md-3">
            <div class="alert alert-warning mb-0">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-calendar-times fa-2x"></i>
                    <div>
                        <div class="small">Vencidos por Fecha</div>
                        <div class="h4 mb-0"><?= $resumen['vencidos_fecha'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($resumen['proximos_horas'] > 0): ?>
        <div class="col-md-3">
            <div class="alert alert-info mb-0">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-clock fa-2x"></i>
                    <div>
                        <div class="small">Próximos (≤100h)</div>
                        <div class="h4 mb-0"><?= $resumen['proximos_horas'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($resumen['proximos_fecha'] > 0): ?>
        <div class="col-md-3">
            <div class="alert alert-secondary mb-0">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-calendar-alt fa-2x"></i>
                    <div>
                        <div class="small">Próximos (≤30 días)</div>
                        <div class="h4 mb-0"><?= $resumen['proximos_fecha'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Alertas de Mantenimiento -->
    <div class="card border-0 mb-4">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-bell text-danger me-2"></i> Alertas de Mantenimiento
                <?php if (count($alertas) > 0): ?>
                    <span class="badge bg-danger ms-2"><?= count($alertas) ?></span>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($alertas)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                    <h5>¡Todo al día!</h5>
                    <p class="text-muted">No hay mantenimientos pendientes</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Equipo</th>
                                <th>Tarea</th>
                                <th>Frecuencia</th>
                                <th>Estado</th>
                                <th>Faltante</th>
                                <th>Responsable</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alertas as $alerta): 
                                $estado_colores = [
                                    'VENCIDO_POR_HORAS' => ['danger', 'fa-exclamation-triangle', 'Vencido (Horas)'],
                                    'VENCIDO_POR_FECHA' => ['danger', 'fa-calendar-times', 'Vencido (Fecha)'],
                                    'PROXIMO_POR_HORAS' => ['warning', 'fa-clock', 'Próximo (Horas)'],
                                    'PROXIMO_POR_FECHA' => ['info', 'fa-calendar-alt', 'Próximo (Fecha)'],
                                ];
                                $estado_info = $estado_colores[$alerta['estado_alerta']] ?? ['secondary', 'fa-info-circle', 'Al día'];
                                list($color, $icono, $texto) = $estado_info;
                            ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div style="width:36px;height:36px;border-radius:8px;background:rgba(13,110,253,0.1);display:flex;align-items:center;justify-content:center;color:#0d6efd;">
                                                <i class="fas fa-industry"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($alerta['nombre_equipo']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($alerta['codigo'] ?? 'N/A') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($alerta['nombre_tarea']) ?></td>
                                    <td>
                                        <?php if (!empty($alerta['frecuencia_horas'])): ?>
                                            <span class="badge bg-primary"><?= $alerta['frecuencia_horas'] ?> h</span>
                                        <?php endif; ?>
                                        <?php if (!empty($alerta['frecuencia_dias'])): ?>
                                            <span class="badge bg-info"><?= $alerta['frecuencia_dias'] ?> días</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?> px-3 py-2">
                                            <i class="fas <?= $icono ?> me-1"></i><?= $texto ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($alerta['horas_restantes'] !== null): ?>
                                            <div class="fw-semibold <?= $alerta['horas_restantes'] <= 0 ? 'text-danger' : 'text-warning' ?>">
                                                <?= number_format($alerta['horas_restantes']) ?> h
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($alerta['dias_restantes'] !== null): ?>
                                            <div class="small <?= $alerta['dias_restantes'] <= 0 ? 'text-danger' : 'text-muted' ?>">
                                                <?= $alerta['dias_restantes'] ?> días
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($alerta['responsable'] === 'PROVEEDOR_EXTERNO'): ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning">
                                                <i class="fas fa-truck me-1"></i><?= htmlspecialchars($alerta['proveedor_nombre'] ?? 'Proveedor') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-info bg-opacity-10 text-info">
                                                <i class="fas fa-user-cog me-1"></i>Técnico Interno
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="/proyecto/mantenimiento/equipo/<?= (int)$alerta['id_equipo'] ?>" 
                                               class="btn btn-sm btn-outline-info" title="Ver Equipo">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-success" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalCompletar<?= (int)$alerta['id'] ?>"
                                                    title="Registrar Mantenimiento">
                                                <i class="fas fa-check"></i>
                                            </button>
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

    <!-- Historial Reciente -->
    <div class="card border-0">
        <div class="card-header bg-transparent border-0 pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-history text-primary me-2"></i> Historial Reciente
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($historial_reciente)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                    No hay mantenimientos registrados
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Equipo</th>
                                <th>Tipo</th>
                                <th>Descripción</th>
                                <th>Horas</th>
                                <th>Costo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historial_reciente as $mant): ?>
                                <tr>
                                    <td><small><?= date('d/m/Y H:i', strtotime($mant['fecha_mantenimiento'])) ?></small></td>
                                    <td><?= htmlspecialchars($mant['nombre_equipo']) ?></td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            <?= htmlspecialchars($mant['tipo'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars(substr($mant['descripcion'] ?? '', 0, 50)) ?></td>
                                    <td><?= number_format($mant['horas_equipo'] ?? 0) ?> h</td>
                                    <td>S/ <?= number_format($mant['costo'] ?? 0, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Modales de Completar -->
<?php foreach ($alertas as $alerta): ?>
<div class="modal fade" id="modalCompletar<?= (int)$alerta['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/proyecto/mantenimiento/completar/<?= (int)$alerta['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle me-2"></i>Registrar Mantenimiento
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Equipo:</strong> <?= htmlspecialchars($alerta['nombre_equipo']) ?><br>
                        <strong>Tarea:</strong> <?= htmlspecialchars($alerta['nombre_tarea']) ?><br>
                        <strong>Horómetro actual:</strong> <?= number_format($alerta['horometro_actual']) ?> h
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descripción del trabajo realizado</label>
                        <textarea class="form-control" name="descripcion" rows="3" 
                                  placeholder="Detalles del mantenimiento..."><?= htmlspecialchars($alerta['nombre_tarea']) ?></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Costo (S/)</label>
                            <input type="number" step="0.01" class="form-control" 
                                   name="costo" min="0" value="<?= $alerta['costo_estimado'] ?? 0 ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Repuestos usados</label>
                            <input type="text" class="form-control" name="repuestos_usados" 
                                   placeholder="Ej: 2 filtros, 1L aceite">
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="form-label fw-semibold">Observaciones</label>
                        <textarea class="form-control" name="observaciones" rows="2" 
                                  placeholder="Observaciones adicionales..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-1"></i> Confirmar Mantenimiento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<style>
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
}
.stat-card-modern .stat-icon-modern {
    width: 56px !important;
    height: 56px !important;
    border-radius: 14px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
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
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>