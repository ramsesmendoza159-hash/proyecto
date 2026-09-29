<?php
// views/tecnicos/index.php
// Gestión de Técnicos - VERSIÓN ESTANDARIZADA

// Verificar sesión
if (!isset($_SESSION['usuario_id']) || ($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: /proyecto/auth/login');
    exit;
}

$titulo = "Gestión de Técnicos";
$seccion = "tecnicos";

// Asegurar que las variables existan
$tecnicos = $tecnicos ?? [];
$estadisticas = $estadisticas ?? ['total' => 0, 'activos' => 0, 'inactivos' => 0];
$especialidades = $especialidades ?? [];
$tarifa_promedio = $tarifa_promedio ?? 0;
$total_ordenes = $total_ordenes ?? 0;

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-users-cog text-primary me-2"></i>Gestión de Técnicos
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-calendar-alt me-1"></i> <?= date('d/m/Y H:i') ?>
                <span class="mx-2">|</span>
                <i class="fas fa-list me-1"></i> <?= $estadisticas['total'] ?? 0 ?> técnicos registrados
            </p>
        </div>
        <a href="/proyecto/tecnicos/crear" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>Nuevo Técnico
        </a>
    </div>

    <!-- Tarjetas de Estadísticas - ESTILO ESTÁNDAR -->
    <div class="row g-3 mb-4">

        <!-- 1. Total Técnicos -->
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-modern border-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(102, 126, 234, 0.15); color: #667eea;">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Total Técnicos</span>
                        <span class="stat-number-modern"><?= $estadisticas['total'] ?? 0 ?></span>
                    </div>
                </div>
                <div class="stat-footer-modern mt-2">
                    <span class="badge bg-success bg-opacity-10 text-success">
                        <i class="fas fa-circle me-1" style="font-size:6px;"></i> <?= $estadisticas['activos'] ?? 0 ?> activos
                    </span>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary">
                        <i class="fas fa-circle me-1" style="font-size:6px;"></i> <?= $estadisticas['inactivos'] ?? 0 ?> inact.
                    </span>
                </div>
            </div>
        </div>

        <!-- 2. Especialidades -->
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-modern border-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(46, 213, 115, 0.15); color: #2ed573;">
                        <i class="fas fa-cog fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Especialidades</span>
                        <span class="stat-number-modern"><?= count($especialidades ?? []) ?></span>
                    </div>
                </div>
                <div class="stat-footer-modern mt-2">
                    <span class="badge bg-info bg-opacity-10 text-info">
                        <i class="fas fa-circle me-1" style="font-size:6px;"></i> Áreas distintas
                    </span>
                </div>
            </div>
        </div>

        <!-- 3. Tarifa Promedio -->
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-modern border-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(255, 193, 7, 0.15); color: #ffc107;">
                        <i class="fas fa-money-bill-wave fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Tarifa Promedio</span>
                        <span class="stat-number-modern">S/ <?= number_format($tarifa_promedio ?? 0, 2) ?></span>
                    </div>
                </div>
                <div class="stat-footer-modern mt-2">
                    <span class="badge bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-circle me-1" style="font-size:6px;"></i> Por hora
                    </span>
                </div>
            </div>
        </div>

        <!-- 4. Órdenes Asignadas -->
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="stat-card-modern border-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon-modern" style="background: rgba(220, 53, 69, 0.15); color: #dc3545;">
                        <i class="fas fa-clipboard-list fa-2x"></i>
                    </div>
                    <div>
                        <span class="stat-label-modern">Órdenes Asignadas</span>
                        <span class="stat-number-modern"><?= $total_ordenes ?? 0 ?></span>
                    </div>
                </div>
                <div class="stat-footer-modern mt-2">
                    <span class="badge bg-danger bg-opacity-10 text-danger">
                        <i class="fas fa-circle me-1" style="font-size:6px;"></i> En total
                    </span>
                </div>
            </div>
        </div>

    </div>

    <!-- Filtros -->
    <div class="card border-0 mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Buscar</label>
                    <input type="text" name="buscar" class="form-control form-control-sm" 
                           placeholder="Buscar técnico..." value="<?= htmlspecialchars($_GET['buscar'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Especialidad</label>
                    <select name="especialidad" class="form-select form-select-sm">
                        <option value="">Todas las especialidades</option>
                        <?php foreach ($especialidades ?? [] as $esp): ?>
                            <option value="<?= htmlspecialchars($esp) ?>" <?= ($_GET['especialidad'] ?? '') === $esp ? 'selected' : '' ?>>
                                <?= htmlspecialchars($esp) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Estado</label>
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">Todos los estados</option>
                        <option value="activo" <?= ($_GET['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="inactivo" <?= ($_GET['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search me-1"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Técnicos -->
    <div class="card border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px;">#</th>
                            <th>Técnico</th>
                            <th>Email</th>
                            <th>Especialidad</th>
                            <th>Tarifa</th>
                            <th>Estado</th>
                            <th style="width:120px;" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tecnicos)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                    No hay técnicos registrados
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tecnicos as $index => $tecnico): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-sm" style="width:36px;height:36px;border-radius:50%;background:#0d6efd;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:0.85rem;">
                                                <?= strtoupper(substr($tecnico['nombre'] ?? 'U', 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($tecnico['nombre'] ?? '') ?></div>
                                                <small class="text-muted">ID: <?= $tecnico['id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="mailto:<?= htmlspecialchars($tecnico['email'] ?? '') ?>" class="text-decoration-none">
                                            <i class="fas fa-envelope me-1 text-muted"></i>
                                            <?= htmlspecialchars($tecnico['email'] ?? '') ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            <?= htmlspecialchars($tecnico['especialidad'] ?? 'Sin especialidad') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong>S/ <?= number_format($tecnico['tarifa'] ?? 0, 2) ?></strong>
                                    </td>
                                    <td>
                                        <?php if (($tecnico['estado'] ?? '') === 'activo'): ?>
                                            <span class="badge-status bg-success bg-opacity-10 text-success">
                                                <i class="fas fa-circle me-1" style="font-size:8px;"></i> Activo
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-status bg-secondary bg-opacity-10 text-secondary">
                                                <i class="fas fa-circle me-1" style="font-size:8px;"></i> Inactivo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="/proyecto/tecnicos/editar/<?= $tecnico['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="confirmarEliminar(<?= $tecnico['id'] ?>, '<?= htmlspecialchars($tecnico['nombre'] ?? '') ?>')" 
                                                    title="Eliminar">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3 text-muted small">
                <i class="fas fa-list me-1"></i> Mostrando <?= count($tecnicos) ?> técnico(s)
            </div>
        </div>
    </div>

</div>

<!-- Estilos -->
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
.stat-card-modern .stat-footer-modern {
    margin-top: 8px !important;
}
.stat-card-modern .stat-footer-modern .badge {
    font-size: 0.65rem !important;
    padding: 4px 12px !important;
    border-radius: 20px !important;
    font-weight: 500 !important;
}
.badge-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.75rem;
    display: inline-flex;
    align-items: center;
    gap: 4px;
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
</style>

<script>
function confirmarEliminar(id, nombre) {
    if (confirm(`¿Estás seguro de eliminar al técnico "${nombre}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/proyecto/tecnicos/eliminar/${id}`;
        form.innerHTML = `<input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">`;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>