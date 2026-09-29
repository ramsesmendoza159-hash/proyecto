<?php
// views/supervisores/index.php
// Lista de supervisores - VERSIÓN CORREGIDA CON ASTEROADMIN

// Incluir helpers necesarios
require_once __DIR__ . '/../../helpers/SecurityHelper.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: /proyecto/auth/login');
    exit;
}

$seccion = 'supervisores';
$titulo = 'Gestión de Supervisores';

// Asegurar que las variables existan
$supervisores = $supervisores ?? [];
$estadisticas = $estadisticas ?? ['total' => 0, 'activos' => 0, 'inactivos' => 0, 'areas' => 0];
$filtros = $filtros ?? [];

include_once __DIR__ . '/../layouts/header.php';
// ❌ ELIMINAR: include_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid px-0">

    <!-- ✅ Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-user-tie text-primary me-2"></i>Gestión de Supervisores
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-users me-1"></i> 
                <?php echo count($supervisores); ?> supervisores registrados
            </p>
        </div>
        <div>
            <a href="/proyecto/supervisores/crear" class="btn btn-primary">
                <i class="fas fa-plus-circle me-2"></i>Nuevo Supervisor
            </a>
        </div>
    </div>

    <!-- ✅ Mensajes -->
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

    <!-- ✅ Tarjetas de Estadísticas -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div>
                        <div class="stat-label">Total Supervisores</div>
                        <div class="stat-number-mini"><?php echo number_format($estadisticas['total'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div>
                        <div class="stat-label">Activos</div>
                        <div class="stat-number-mini"><?php echo number_format($estadisticas['activos'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="badge bg-success bg-opacity-10 text-success">
                        <i class="fas fa-circle me-1" style="font-size:8px;"></i> Activos
                    </span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background: rgba(220, 53, 69, 0.1); color: #dc3545;">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <div>
                        <div class="stat-label">Inactivos</div>
                        <div class="stat-number-mini"><?php echo number_format($estadisticas['inactivos'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="badge bg-danger bg-opacity-10 text-danger">
                        <i class="fas fa-circle me-1" style="font-size:8px;"></i> Inactivos
                    </span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-mini">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-mini" style="background: rgba(13, 202, 240, 0.1); color: #0dcaf0;">
                        <i class="fas fa-building"></i>
                    </div>
                    <div>
                        <div class="stat-label">Áreas</div>
                        <div class="stat-number-mini"><?php echo number_format($estadisticas['areas'] ?? 0); ?></div>
                    </div>
                </div>
                <div class="mt-2">
                    <span class="badge bg-info bg-opacity-10 text-info">
                        <i class="fas fa-circle me-1" style="font-size:8px;"></i> Áreas distintas
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ Filtros -->
    <div class="card border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="/proyecto/supervisores" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" name="buscar" class="form-control border-start-0" 
                               placeholder="Buscar supervisor..." 
                               value="<?php echo htmlspecialchars($filtros['buscar'] ?? ''); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="activo" <?php echo ($filtros['estado'] ?? '') === 'activo' ? 'selected' : ''; ?>>Activos</option>
                        <option value="inactivo" <?php echo ($filtros['estado'] ?? '') === 'inactivo' ? 'selected' : ''; ?>>Inactivos</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="/proyecto/supervisores" class="btn btn-secondary w-100">
                        <i class="fas fa-undo me-1"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- ✅ Tabla -->
    <div class="card border-0">
        <div class="card-body p-0">
            <?php if (empty($supervisores)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-user-tie fa-4x text-muted mb-3"></i>
                    <h5>No hay supervisores registrados</h5>
                    <p class="text-muted">Haz clic en "Nuevo Supervisor" para agregar uno.</p>
                    <a href="/proyecto/supervisores/crear" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i>Crear Primer Supervisor
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:60px;">#</th>
                                <th>Supervisor</th>
                                <th>Email</th>
                                <th>Área</th>
                                <th>Teléfono</th>
                                <th style="width:120px;">Estado</th>
                                <th style="width:180px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($supervisores as $supervisor): ?>
                                <tr>
                                    <td><span class="fw-semibold">#<?php echo $supervisor['id']; ?></span></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-sm" style="width:36px;height:36px;border-radius:50%;background:#667eea;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:0.85rem;">
                                                <?php echo strtoupper(substr($supervisor['nombre'] ?? 'S', 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($supervisor['nombre'] ?? 'N/A'); ?></div>
                                                <small class="text-muted">ID: <?php echo $supervisor['id']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($supervisor['email'] ?? ''); ?>" class="text-decoration-none">
                                            <i class="fas fa-envelope me-1 text-muted"></i>
                                            <?php echo htmlspecialchars($supervisor['email'] ?? 'N/A'); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info px-3 py-2">
                                            <?php echo htmlspecialchars($supervisor['area'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($supervisor['telefono'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if (($supervisor['estado'] ?? 'activo') === 'activo'): ?>
                                            <span class="badge-status bg-success bg-opacity-10 text-success">
                                                <i class="fas fa-circle me-1" style="font-size:6px;"></i> Activo
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-status bg-danger bg-opacity-10 text-danger">
                                                <i class="fas fa-circle me-1" style="font-size:6px;"></i> Inactivo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="/proyecto/supervisores/editar/<?php echo $supervisor['id']; ?>" 
                                               class="btn btn-outline-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-outline-primary" title="Cambiar Contraseña"
                                                    onclick="cambiarPassword(<?php echo $supervisor['id']; ?>, '<?php echo htmlspecialchars($supervisor['nombre']); ?>')">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <button class="btn <?php echo ($supervisor['estado'] ?? 'activo') === 'activo' ? 'btn-outline-secondary' : 'btn-outline-success'; ?>" 
                                                    title="<?php echo ($supervisor['estado'] ?? 'activo') === 'activo' ? 'Desactivar' : 'Activar'; ?>"
                                                    onclick="cambiarEstado(<?php echo $supervisor['id']; ?>, '<?php echo ($supervisor['estado'] ?? 'activo') === 'activo' ? 'inactivo' : 'activo'; ?>')">
                                                <i class="fas fa-<?php echo ($supervisor['estado'] ?? 'activo') === 'activo' ? 'pause' : 'play'; ?>"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" title="Eliminar"
                                                    onclick="confirmarEliminar(<?php echo $supervisor['id']; ?>, '<?php echo htmlspecialchars($supervisor['nombre']); ?>')">
                                                <i class="fas fa-trash"></i>
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
        <?php if (!empty($supervisores)): ?>
            <div class="card-footer bg-transparent">
                <span class="text-muted small">
                    <i class="fas fa-list me-1"></i> Mostrando <?php echo count($supervisores); ?> supervisor(es)
                </span>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- ✅ Modales -->
<!-- Modal Cambiar Password -->
<div class="modal fade" id="modalPassword" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formPassword" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key text-primary me-2"></i> Cambiar Contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Supervisor: <strong id="passwordNombre"></strong></p>
                    <input type="hidden" name="id" id="passwordId">
                    <div class="mb-3">
                        <label for="new_password" class="form-label fw-semibold">Nueva Contraseña <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="new_password" name="password" required minlength="6" placeholder="Mínimo 6 caracteres">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label fw-semibold">Confirmar Contraseña <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirmar_password" required placeholder="Repite la contraseña">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Cambiar Estado -->
<div class="modal fade" id="modalEstado" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEstado" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="estadoTitulo">Cambiar Estado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="estadoMensaje"></p>
                    <input type="hidden" name="estado" id="estadoValor">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="estadoBoton">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash me-2"></i> Eliminar Supervisor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de eliminar a <strong id="eliminarNombre"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i> Esta acción no se puede deshacer.
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> Solo se puede eliminar si no tiene órdenes asignadas.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formEliminar" method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::generateCSRFToken(); ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i> Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ✅ Scripts -->
<script>
function cambiarPassword(id, nombre) {
    document.getElementById('passwordId').value = id;
    document.getElementById('passwordNombre').textContent = nombre;
    document.getElementById('formPassword').action = '/proyecto/supervisores/cambiarPassword/' + id;
    new bootstrap.Modal(document.getElementById('modalPassword')).show();
}

document.getElementById('formPassword').addEventListener('submit', function(e) {
    var password = document.getElementById('new_password').value;
    var confirmar = document.getElementById('confirm_password').value;
    if (password !== confirmar) {
        e.preventDefault();
        alert('❌ Las contraseñas no coinciden.');
        return false;
    }
    if (password.length < 6) {
        e.preventDefault();
        alert('❌ La contraseña debe tener al menos 6 caracteres.');
        return false;
    }
});

function cambiarEstado(id, estado) {
    var modal = new bootstrap.Modal(document.getElementById('modalEstado'));
    document.getElementById('formEstado').action = '/proyecto/supervisores/cambiarEstado/' + id;
    document.getElementById('estadoValor').value = estado;
    if (estado === 'activo') {
        document.getElementById('estadoTitulo').textContent = '✅ Activar Supervisor';
        document.getElementById('estadoMensaje').innerHTML = '¿Activar este supervisor?';
        document.getElementById('estadoBoton').className = 'btn btn-success';
        document.getElementById('estadoBoton').textContent = '✅ Activar';
    } else {
        document.getElementById('estadoTitulo').textContent = '⛔ Desactivar Supervisor';
        document.getElementById('estadoMensaje').innerHTML = '¿Desactivar este supervisor?';
        document.getElementById('estadoBoton').className = 'btn btn-danger';
        document.getElementById('estadoBoton').textContent = '⛔ Desactivar';
    }
    modal.show();
}

function confirmarEliminar(id, nombre) {
    document.getElementById('eliminarNombre').textContent = nombre;
    document.getElementById('formEliminar').action = '/proyecto/supervisores/eliminar/' + id;
    new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}
</script>

<!-- ✅ Estilos adicionales -->
<style>
.stat-card-mini {
    background: #fff;
    border-radius: 12px;
    padding: 16px 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: all 0.3s ease;
}
.stat-card-mini:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
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
    color: #6c757d;
    font-weight: 600;
}
.stat-card-mini .stat-number-mini {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a1a2e;
    line-height: 1.2;
}
.badge-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.75rem;
    display: inline-flex;
    align-items: center;
}
.avatar-sm {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.85rem;
    flex-shrink: 0;
}
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
.btn-group .btn {
    border-radius: 6px;
    padding: 0.25rem 0.6rem;
}
.table th {
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>