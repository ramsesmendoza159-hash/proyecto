<?php
// views/checklists/llenar.php
// Renderiza dinámicamente las secciones según layout_tipo
// ✅ FIX: usa $seccion_data (NO $seccion) para no sobreescribir el sidebar
// ✅ FIX: pasa $matriz, $registro, $puede_editar a los layouts
// ✅ FIX: validación de path traversal en $layout
// ✅ FIX: guardarLectura() con response.ok check
// ✅ FIX: warning visible si falta layout

if (!isset($seccion)) $seccion = 'checklists';
if (!isset($titulo)) $titulo = 'Llenar Checklist';

$registro = $registro ?? null;
if (!$registro) {
    header('Location: /proyecto/checklists');
    exit;
}

$secciones = $registro['secciones'] ?? [];
$matriz = $registro['matriz'] ?? [];
$estado = $registro['estado'] ?? 'BORRADOR';
$puede_editar = ($estado === 'BORRADOR');
$csrf = SecurityHelper::generateCSRFToken();

// ✅ FIX: NO sobreescribir $seccion global. Guardar el valor original.
$seccion_original = $seccion;

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-clipboard-check text-primary me-2"></i>
                <?= htmlspecialchars($registro['tipo_nombre'] ?? 'Checklist') ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-calendar me-1"></i>
                <?= date('d/m/Y', strtotime($registro['fecha'] ?? 'now')) ?>
                <?php if (!empty($registro['turno'])): ?>
                    <span class="mx-2">|</span>
                    <span class="badge bg-dark"><?= htmlspecialchars($registro['turno']) ?></span>
                <?php endif; ?>
                <span class="mx-2">|</span>
                <span class="badge bg-<?= $estado === 'BORRADOR' ? 'warning' : 'info' ?>">
                    <?= htmlspecialchars($estado) ?>
                </span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/proyecto/checklists/mis-checklists" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <?php if ($puede_editar): ?>
                <form method="POST" action="/proyecto/checklists/cerrar/<?= (int)$registro['id'] ?>"
                      id="formCerrar" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check-circle me-1"></i> Cerrar
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?= $_SESSION['mensaje_tipo'] ?? 'success' ?> alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_SESSION['mensaje']) ?>
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

    <?php if (!$puede_editar): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Este registro está en estado <strong><?= htmlspecialchars($estado) ?></strong> y no puede editarse.
            <a href="/proyecto/checklists/ver/<?= (int)$registro['id'] ?>">Ver detalle</a>.
        </div>
    <?php endif; ?>

    <!-- Renderizado dinámico de secciones -->
    <?php if (empty($secciones)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Este checklist no tiene secciones configuradas.
        </div>
    <?php else: ?>
        <?php foreach ($secciones as $sec): ?>
            <?php
                $layout = $sec['layout_tipo'] ?? '';

                // ✅ FIX: validar path traversal
                if (!preg_match('/^[a-z_]+$/', $layout)) {
                    error_log("Layout inválido en checklist: {$layout}");
                    echo '<div class="alert alert-danger">Layout inválido: ' . htmlspecialchars($layout) . '</div>';
                    continue;
                }

                $layout_file = __DIR__ . '/layouts/' . $layout . '.php';

                // ✅ FIX: usar $seccion_data (NO $seccion) para no romper el sidebar
                $seccion_data = $sec;

                if (file_exists($layout_file)) {
                    // ✅ FIX: pasar variables necesarias al layout
                    // $seccion_data, $matriz, $registro, $puede_editar están disponibles
                    include $layout_file;
                } else {
                    error_log("Layout no encontrado: {$layout_file}");
                    echo '<div class="alert alert-warning">Layout no encontrado: ' . htmlspecialchars($layout) . '</div>';
                }
            ?>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<!-- Variables globales para JS -->
<input type="hidden" id="csrf_token_global" value="<?= $csrf ?>">
<input type="hidden" id="registro_id_global" value="<?= (int)$registro['id'] ?>">

<style>
.card { border-radius: 16px; box-shadow: 0 2px 12px var(--shadow-color); }
.grilla-check input.form-control-sm {
    padding: 4px 6px;
    font-size: 0.8rem;
    border-radius: 6px;
}
.grilla-check input.input-readonly {
    background: var(--bg-input-disabled);
    color: var(--text-muted);
    cursor: not-allowed;
}
.batch-row {
    border-bottom: 1px solid var(--border-color);
    padding: 8px 0;
}
</style>

<script>
window.checklistCtx = {
    csrfToken: document.getElementById('csrf_token_global').value,
    registroId: parseInt(document.getElementById('registro_id_global').value)
};

/**
 * Guarda una lectura en el servidor.
 * @param {Object} payload
 * @returns {Promise<Object>}
 */
function guardarLectura(payload) {
    const body = new URLSearchParams();
    body.append('csrf_token', window.checklistCtx.csrfToken);
    body.append('registro_id', payload.registro_id);
    body.append('seccion_id', payload.seccion_id);
    if (payload.id_equipo !== null && payload.id_equipo !== undefined) body.append('id_equipo', payload.id_equipo);
    if (payload.hora) body.append('hora', payload.hora);
    if (payload.campo_id) body.append('campo_id', payload.campo_id);
    if (payload.batch_num) body.append('batch_num', payload.batch_num);
    body.append('valor', payload.valor ?? '');
    body.append('observacion', payload.observacion ?? '');

    return fetch('/proyecto/checklists/guardar-lectura', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body.toString()
    })
    .then(function(response) {
        if (!response.ok) {
            throw new Error('HTTP ' + response.status + ': ' + response.statusText);
        }
        return response.json();
    })
    .catch(function(error) {
        console.error('Error en guardarLectura:', error);
        return {
            success: false,
            mensaje: 'Error de conexión: ' + error.message
        };
    });
}

// Confirmar antes de cerrar
document.addEventListener('DOMContentLoaded', function() {
    const formCerrar = document.getElementById('formCerrar');
    if (formCerrar) {
        formCerrar.addEventListener('submit', function(e) {
            if (!confirm('¿Confirmas cerrar el checklist? Después deberás firmarlo.')) {
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>