<?php
// views/ordenes/index.php
// Listado unificado de órdenes - VERSIÓN ESTANDARIZADA
// ✅ FIX: contenedor de acciones usa .table-actions (d-flex justify-content-center gap-1)
// ✅ FIX: botones de acciones usan btn-icon (cuadraditos 32×32)
// ✅ FIX: TODOS los botones tienen aria-label para accesibilidad

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /proyecto/auth/login');
    exit;
}

if (!isset($ordenes)) {
    $ordenes = [];
}
if (!isset($estadisticas)) {
    $estadisticas = [
        'total' => 0, 'pendientes' => 0, 'en_proceso' => 0,
        'ejecutadas' => 0, 'cerradas' => 0, 'canceladas' => 0,
        'aprobadas' => 0, 'rechazadas' => 0, 'devueltas' => 0
    ];
}

$rol = $_SESSION['rol'] ?? 'usuario';
$titulo = $titulo ?? 'Gestión de Órdenes';
$seccion = 'ordenes';

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Mensajes -->
    <?php if (isset($_SESSION['mensaje']) && !empty($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?php echo $_SESSION['mensaje_tipo'] ?? 'success'; ?> alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['mensaje']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-list text-primary me-2"></i>Gestión de Órdenes
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Todas las órdenes de trabajo del sistema
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if (in_array($rol, ['admin', 'operador'])): ?>
                <a href="/proyecto/ordenes/crear" class="btn btn-primary btn-header-action">
                    <i class="fas fa-plus-circle"></i> Nueva Orden
                </a>
            <?php endif; ?>
            <a href="/proyecto/ordenes/estadisticas" class="btn btn-outline-info btn-header-action">
                <i class="fas fa-chart-bar"></i> Estadísticas
            </a>
        </div>
    </div>

    <!-- Tarjetas de Estadísticas -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(13,110,253,0.1);color:#0d6efd;">
                        <i class="fas fa-list"></i>
                    </div>
                    <div>
                        <div class="stat-label">Total</div>
                        <div class="stat-number-mini"><?= number_format($estadisticas['total'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(255,193,7,0.1);color:#ffc107;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <div class="stat-label">Pendientes</div>
                        <div class="stat-number-mini"><?= number_format($estadisticas['pendientes'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(13,202,240,0.1);color:#0dcaf0;">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div>
                        <div class="stat-label">En Proceso</div>
                        <div class="stat-number-mini"><?= number_format($estadisticas['en_proceso'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(25,135,84,0.1);color:#198754;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-label">Cerradas</div>
                        <div class="stat-number-mini"><?= number_format($estadisticas['cerradas'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(13,110,253,0.1);color:#0d6efd;">
                        <i class="fas fa-thumbs-up"></i>
                    </div>
                    <div>
                        <div class="stat-label">Aprobadas</div>
                        <div class="stat-number-mini"><?= number_format($estadisticas['aprobadas'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-lg-4 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background:rgba(108,117,125,0.1);color:#6c757d;">
                        <i class="fas fa-undo"></i>
                    </div>
                    <div>
                        <div class="stat-label">Devueltas</div>
                        <div class="stat-number-mini"><?= number_format($estadisticas['devueltas'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Órdenes -->
    <div class="card border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Área</th>
                            <th>Técnico</th>
                            <th>Estado</th>
                            <th>Prioridad</th>
                            <th>Fecha</th>
                            <th class="text-center" style="width:180px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ordenes)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-3x d-block mb-3"></i>
                                    <h5>No hay órdenes registradas</h5>
                                    <p class="small">Crea la primera orden de trabajo</p>
                                    <?php if (in_array($rol, ['admin', 'operador'])): ?>
                                        <a href="/proyecto/ordenes/crear" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus-circle me-1"></i> Nueva Orden
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ordenes as $orden): ?>
                                <tr>
                                    <td><span class="fw-semibold">#<?php echo $orden['id']; ?></span></td>
                                    <td><?php echo htmlspecialchars($orden['titulo'] ?? 'Sin título'); ?></td>
                                    <td><?php echo htmlspecialchars($orden['nombre_area'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($orden['tecnico_nombre'] ?? 'Sin asignar'); ?></td>
                                    <td>
                                        <?php
                                        $estadoColor = match($orden['status'] ?? 'PENDIENTE') {
                                            'PENDIENTE' => 'warning',
                                            'EN_PROCESO' => 'info',
                                            'EJECUTADA' => 'primary',
                                            'CERRADA' => 'success',
                                            'APROBADA' => 'success',
                                            'CANCELADA' => 'danger',
                                            'RECHAZADA' => 'danger',
                                            'DEVUELTA' => 'secondary',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge-status bg-<?php echo $estadoColor; ?> bg-opacity-10 text-<?php echo $estadoColor; ?>">
                                            <i class="fas fa-circle me-1" style="font-size:6px;"></i>
                                            <?php echo $orden['status'] ?? 'PENDIENTE'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $prioridadColor = match($orden['prioridad'] ?? 'Media') {
                                            'Baja' => 'success',
                                            'Media' => 'info',
                                            'Alta' => 'warning',
                                            'Urgente' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $prioridadColor; ?> bg-opacity-10 text-<?php echo $prioridadColor; ?>">
                                            <?php echo $orden['prioridad'] ?? 'Media'; ?>
                                        </span>
                                    </td>
                                    <td><small><?php echo date('d/m/Y', strtotime($orden['fecha_creacion'] ?? 'now')); ?></small></td>
                                    <td>
                                        <div class="table-actions">
                                            <!-- Ver -->
                                            <a href="/proyecto/ordenes/ver/<?php echo $orden['id']; ?>" 
                                               class="btn btn-sm btn-outline-info btn-icon" 
                                               title="Ver"
                                               aria-label="Ver orden">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <!-- Editar (solo PENDIENTES y admin/operador) -->
                                            <?php if (($orden['status'] ?? '') === 'PENDIENTE' && in_array($rol, ['admin', 'operador'])): ?>
                                                <a href="/proyecto/ordenes/editar/<?php echo $orden['id']; ?>" 
                                                   class="btn btn-sm btn-outline-warning btn-icon" 
                                                   title="Editar"
                                                   aria-label="Editar orden">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <!-- Cerrar (EN_PROCESO o EJECUTADA) -->
                                            <?php if (in_array($orden['status'] ?? '', ['EN_PROCESO', 'EJECUTADA'])): ?>
                                                <a href="/proyecto/ordenes/cerrar/<?php echo $orden['id']; ?>" 
                                                   class="btn btn-sm btn-outline-success btn-icon" 
                                                   title="Cerrar"
                                                   aria-label="Cerrar orden">
                                                    <i class="fas fa-check-circle"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <!-- Eliminar (solo PENDIENTES y admin) -->
                                            <?php if (($orden['status'] ?? '') === 'PENDIENTE' && $rol === 'admin'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-danger btn-icon" 
                                                        onclick="confirmarEliminar(<?php echo $orden['id']; ?>, '<?php echo htmlspecialchars($orden['num_om'] ?? '', ENT_QUOTES, 'UTF-8'); ?>')" 
                                                        title="Eliminar"
                                                        aria-label="Eliminar orden">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (!empty($ordenes)): ?>
            <div class="card-footer bg-transparent">
                <span class="text-muted small">
                    <i class="fas fa-list me-1"></i> Mostrando <?php echo count($ordenes); ?> orden(es)
                </span>
            </div>
        <?php endif; ?>
    </div>

</div>

<style>
.stat-card-mini {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 16px 20px;
    transition: all 0.3s ease;
}
.stat-card-mini:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px var(--shadow-hover);
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
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.2;
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
</style>

<script>
function confirmarEliminar(id, numOm) {
    if (confirm(`¿Estás seguro de eliminar la orden "${numOm}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/proyecto/ordenes/eliminar/${id}`;
        form.innerHTML = `<input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">`;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>