<?php
// views/mantenimiento/editar_checklist.php
// Formulario para editar el checklist ejecutado

if (!isset($seccion)) $seccion = 'mantenimiento';
if (!isset($titulo)) $titulo = 'Editar Checklist';

$mantenimiento = $mantenimiento ?? null;
$checklist = $checklist ?? [];

if (!$mantenimiento) {
    header('Location: /proyecto/mantenimiento/panel');
    exit;
}

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-tasks text-warning me-2"></i>Editar Checklist
            </h4>
            <p class="text-muted small mb-0">
                Mantenimiento #<?= $mantenimiento['id'] ?> - <?= htmlspecialchars($mantenimiento['descripcion'] ?? '') ?>
            </p>
        </div>
        <a href="/proyecto/mantenimiento/detalle-mantenimiento/<?= $mantenimiento['id'] ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <?php if (empty($checklist)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            No hay checklist asociado a este mantenimiento.
        </div>
    <?php else: ?>
        <form method="POST" action="/proyecto/mantenimiento/actualizar-checklist/<?= $mantenimiento['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
            
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold text-primary">
                        <i class="fas fa-list-check me-2"></i>Pasos del Checklist
                    </h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="marcarTodos(true)">
                            <i class="fas fa-check-double me-1"></i> Marcar todos
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="marcarTodos(false)">
                            <i class="fas fa-times me-1"></i> Desmarcar todos
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php foreach ($checklist as $index => $paso): 
                        $paso_id = $paso['id'] ?? $paso['paso_id'] ?? ($index + 1);
                        $esta_cumplido = !empty($paso['cumplido']);
                        $descripcion = $paso['descripcion'] ?? $paso['paso_descripcion'] ?? 'Paso ' . ($index + 1);
                    ?>
                        <div class="checklist-edit-item mb-3 p-3 rounded-3 border">
                            <div class="d-flex align-items-start gap-3">
                                <div class="form-check">
                                    <input class="form-check-input paso-checkbox" 
                                           type="checkbox" 
                                           name="pasos[<?= $paso_id ?>][cumplido]" 
                                           value="1"
                                           id="paso_<?= $paso_id ?>"
                                           <?= $esta_cumplido ? 'checked' : '' ?>>
                                </div>
                                <div class="flex-grow-1">
                                    <label class="form-check-label fw-semibold mb-2 d-block" for="paso_<?= $paso_id ?>">
                                        <span class="badge bg-primary me-2"><?= $paso['paso_numero'] ?? ($index + 1) ?></span>
                                        <?= htmlspecialchars($descripcion) ?>
                                    </label>
                                    
                                    <input type="text" 
                                           class="form-control form-control-sm" 
                                           name="pasos[<?= $paso_id ?>][observaciones]" 
                                           value="<?= htmlspecialchars($paso['observaciones'] ?? '') ?>"
                                           placeholder="Observaciones (opcional)">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="card-footer bg-transparent border-0 pt-3 d-flex justify-content-end gap-2">
                    <a href="/proyecto/mantenimiento/detalle-mantenimiento/<?= $mantenimiento['id'] ?>" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-2"></i> Guardar Checklist
                    </button>
                </div>
            </div>
        </form>
    <?php endif; ?>

</div>

<style>
.checklist-edit-item {
    background: var(--bg-card);
    border-color: var(--border-color) !important;
    transition: all 0.3s ease;
}
.checklist-edit-item:hover {
    border-color: rgba(59, 130, 246, 0.4) !important;
    box-shadow: 0 4px 12px var(--shadow-hover);
}
.checklist-edit-item .form-check-input:checked ~ .form-check-label,
.checklist-edit-item .form-check-input:checked + .form-check-label {
    color: #22c55e;
}
</style>

<script>
function marcarTodos(marcar) {
    document.querySelectorAll('.paso-checkbox').forEach(cb => {
        cb.checked = marcar;
    });
}
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>