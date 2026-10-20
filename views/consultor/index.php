<?php
// views/consultor/index.php
// Dashboard del Consultor - VERSIÓN COMPLETA

if (!isset($seccion)) {
    $seccion = 'consultor';
}
if (!isset($titulo)) {
    $titulo = 'Panel de Consultor';
}

if (!isset($estadisticas)) {
    $estadisticas = [
        'total'       => 0,
        'pendientes'  => 0,
        'en_proceso'  => 0,
        'ejecutadas'  => 0,
        'cerradas'    => 0,
        'canceladas'  => 0,
        'aprobadas'   => 0,
        'rechazadas'  => 0,
        'devueltas'   => 0
    ];
}

if (!isset($ordenes_recientes)) {
    $ordenes_recientes = [];
}

$nombre_usuario = $_SESSION['nombre'] ?? 'Consultor';
$total_ordenes = (int)($estadisticas['total'] ?? 0);
$completadas = (int)($estadisticas['cerradas'] ?? 0) + (int)($estadisticas['aprobadas'] ?? 0);
$eficiencia = $total_ordenes > 0
    ? round(($completadas / $total_ordenes) * 100, 1)
    : 0;

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-user-tie text-primary me-2"></i>Panel de Consultor
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i>
                Bienvenido, <strong><?= htmlspecialchars($nombre_usuario) ?></strong>
                <span class="mx-2">|</span>
                <i class="fas fa-calendar-alt me-1"></i> <?= date('d/m/Y H:i') ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/proyecto/consultor/ordenes" class="btn btn-primary btn-header-action">
                <i class="fas fa-clipboard-list"></i> Ver Órdenes
            </a>
            <a href="/proyecto/reportes/financieros" class="btn btn-outline-info btn-header-action">
                <i class="fas fa-chart-line"></i> Reportes Financieros
            </a>
        </div>
    </div>

    <!-- Banner informativo de solo lectura -->
    <div class="alert alert-info d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="fas fa-eye fa-lg"></i>
        <div class="small mb-0">
            <strong>Modo solo lectura:</strong> Como consultor podés visualizar datos y generar reportes, pero no modificar la información del sistema.
        </div>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(13, 110, 253, 0.15); color: #0d6efd;">
                        <i class="fas fa-clipboard-list fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Total Órdenes</span>
                        <span class="stat-number-modern"><?= number_format($total_ordenes) ?></span>
                    </div>
                </div>
                <div class="mt-2 d-flex gap-1 flex-wrap">
                    <span class="badge bg-warning bg-opacity-10 text-warning small">
                        <i class="fas fa-clock me-1" style="font-size:8px;"></i>
                        <?= (int)($estadisticas['pendientes'] ?? 0) ?> pend.
                    </span>
                    <span class="badge bg-info bg-opacity-10 text-info small">
                        <i class="fas fa-play-circle me-1" style="font-size:8px;"></i>
                        <?= (int)($estadisticas['en_proceso'] ?? 0) ?> prog.
                    </span>
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
                        <span class="stat-label-modern">Completadas</span>
                        <span class="stat-number-modern"><?= number_format($completadas) ?></span>
                    </div>
                </div>
                <div class="mt-2 d-flex gap-1 flex-wrap">
                    <span class="badge bg-success bg-opacity-10 text-success small">
                        <i class="fas fa-check me-1" style="font-size:8px;"></i>
                        <?= (int)($estadisticas['cerradas'] ?? 0) ?> cerradas
                    </span>
                    <span class="badge bg-primary bg-opacity-10 text-primary small">
                        <i class="fas fa-thumbs-up me-1" style="font-size:8px;"></i>
                        <?= (int)($estadisticas['aprobadas'] ?? 0) ?> aprobadas
                    </span>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(255, 193, 7, 0.15); color: #ffc107;">
                        <i class="fas fa-chart-line fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Eficiencia</span>
                        <span class="stat-number-modern"><?= number_format($eficiencia, 1) ?>%</span>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-warning"
                             style="width: <?= min(100, $eficiencia) ?>%;"
                             role="progressbar"
                             aria-valuenow="<?= $eficiencia ?>"
                             aria-valuemin="0"
                             aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(220, 53, 69, 0.15); color: #dc3545;">
                        <i class="fas fa-times-circle fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Canceladas / Rechazadas</span>
                        <span class="stat-number-modern">
                            <?= (int)($estadisticas['canceladas'] ?? 0) + (int)($estadisticas['rechazadas'] ?? 0) ?>
                        </span>
                    </div>
                </div>
                <div class="mt-2 d-flex gap-1 flex-wrap">
                    <span class="badge bg-danger bg-opacity-10 text-danger small">
                        <i class="fas fa-ban me-1" style="font-size:8px;"></i>
                        <?= (int)($estadisticas['canceladas'] ?? 0) ?> canc.
                    </span>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary small">
                        <i class="fas fa-undo me-1" style="font-size:8px;"></i>
                        <?= (int)($estadisticas['devueltas'] ?? 0) ?> devueltas
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimas órdenes -->
    <div class="card border-0">
        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pt-3">
            <h5 class="mb-0 fw-semibold">
                <i class="fas fa-clock text-info me-2"></i> Últimas Órdenes Registradas
            </h5>
            <a href="/proyecto/consultor/ordenes" class="btn btn-sm btn-primary">
                Ver todas <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($ordenes_recientes)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                    <h6 class="text-muted">No hay órdenes registradas</h6>
                    <p class="small text-muted mb-0">Cuando existan órdenes, aparecerán acá.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:60px;">#</th>
                                <th>Título</th>
                                <th>Área</th>
                                <th>Técnico</th>
                                <th style="width:130px;">Estado</th>
                                <th style="width:110px;">Prioridad</th>
                                <th style="width:110px;">Fecha</th>
                                <th class="text-center" style="width:100px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ordenes_recientes as $orden):
                                // ✅ FIX: blindar TODOS los accesos con ?? default
                                $orden_id       = (int)($orden['id'] ?? 0);
                                $titulo_orden   = $orden['titulo'] ?? 'Sin título';
                                $num_om         = $orden['num_om'] ?? '';
                                $area_orden     = $orden['nombre_area'] ?? 'N/A';
                                $tecnico_orden  = $orden['tecnico_nombre'] ?? 'Sin asignar';
                                $status_orden   = $orden['status'] ?? 'PENDIENTE';
                                $prioridad      = $orden['prioridad'] ?? 'Media';
                                $fecha_creacion = $orden['fecha_creacion'] ?? null;

                                $estadoRaw = strtoupper($status_orden);
                                $estadoColor = match($estadoRaw) {
                                    'PENDIENTE'  => 'warning',
                                    'EN_PROCESO' => 'info',
                                    'EJECUTADA'  => 'primary',
                                    'CERRADA', 'APROBADA'   => 'success',
                                    'CANCELADA', 'RECHAZADA' => 'danger',
                                    'DEVUELTA'               => 'secondary',
                                    default                  => 'secondary'
                                };

                                $prioridadColor = match($prioridad) {
                                    'Baja'    => 'success',
                                    'Media'   => 'info',
                                    'Alta'    => 'warning',
                                    'Urgente' => 'danger',
                                    default   => 'secondary'
                                };

                                // ✅ FIX: validar strtotime antes de date()
                                $fecha_valida = !empty($fecha_creacion) && strtotime($fecha_creacion) !== false;
                                $fecha_formateada = $fecha_valida
                                    ? date('d/m/Y', strtotime($fecha_creacion))
                                    : 'N/A';
                            ?>
                                <tr>
                                    <td><span class="fw-semibold">#<?= $orden_id ?></span></td>
                                    <td>
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($titulo_orden) ?>
                                        </div>
                                        <?php if (!empty($num_om)): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-hashtag me-1" style="font-size:9px;"></i>
                                                <?= htmlspecialchars($num_om) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($area_orden) ?></td>
                                    <td><?= htmlspecialchars($tecnico_orden) ?></td>
                                    <td>
                                        <span class="badge-status bg-<?= $estadoColor ?> bg-opacity-10 text-<?= $estadoColor ?>">
                                            <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                            <?= htmlspecialchars($estadoRaw) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $prioridadColor ?> bg-opacity-10 text-<?= $prioridadColor ?>">
                                            <?= htmlspecialchars($prioridad) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?= $fecha_formateada ?></small>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="/proyecto/consultor/ver_orden/<?= $orden_id ?>"
                                               class="btn btn-sm btn-outline-info btn-icon"
                                               title="Ver detalle"
                                               aria-label="Ver detalle de orden">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-transparent">
                    <span class="text-muted small">
                        <i class="fas fa-list me-1"></i>
                        Mostrando <?= count($ordenes_recientes) ?> orden(es) reciente(s)
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

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
.badge-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.75rem;
    display: inline-flex;
    align-items: center;
}
[data-theme="dark"] .alert-info {
    background: #0c2340 !important;
    color: #93c5fd !important;
    border-left: 4px solid #3b82f6 !important;
}
[data-theme="dark"] .alert-info strong {
    color: #ffffff !important;
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>