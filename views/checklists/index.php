<?php
// views/checklists/index.php
// Listado de tipos de checklist - VERSIÓN ESTANDARIZADA
// ✅ FIX NUEVO: contenedor .table-actions + btn-icon + btn-header-action

if (!isset($seccion)) $seccion = 'checklists';
if (!isset($titulo)) $titulo = 'Checklists Operativos';

$tipos = $tipos ?? [];

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-check text-primary me-2"></i>Checklists Operativos
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Formularios de inspección periódica
            </p>
        </div>
        <a href="/proyecto/checklists/mis-checklists" class="btn btn-primary btn-header-action">
            <i class="fas fa-play-circle"></i> Mis Checklists
        </a>
    </div>

    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?= $_SESSION['mensaje_tipo'] ?? 'success' ?> alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_SESSION['mensaje']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); ?>
    <?php endif; ?>

    <div class="card border-0">
        <div class="card-body p-0">
            <?php if (empty($tipos)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <h5>No hay checklists configurados</h5>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Nombre</th>
                                <th>Área</th>
                                <th>Frecuencia</th>
                                <th>Secciones</th>
                                <th class="text-center" style="width:120px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tipos as $t): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($t['codigo']) ?></code></td>
                                    <td><strong><?= htmlspecialchars($t['nombre']) ?></strong></td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            <?= htmlspecialchars($t['area']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary bg-opacity-10 text-primary">
                                            <?= htmlspecialchars($t['frecuencia']) ?>
                                        </span>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= (int)$t['total_secciones'] ?></span></td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="/proyecto/checklists/ver/<?= (int)$t['id'] ?>" 
                                               class="btn btn-sm btn-outline-info btn-icon" 
                                               title="Ver"
                                               aria-label="Ver checklist">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="/proyecto/checklists/iniciar/<?= (int)$t['id'] ?>" 
                                               class="btn btn-sm btn-outline-success btn-icon" 
                                               title="Iniciar"
                                               aria-label="Iniciar checklist">
                                                <i class="fas fa-play"></i>
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
.card { border-radius: 16px; box-shadow: 0 2px 12px var(--shadow-color); }
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>