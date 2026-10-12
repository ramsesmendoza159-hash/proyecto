<?php
// views/checklists/mis_checklists.php

if (!isset($seccion)) $seccion = 'checklists';
if (!isset($titulo)) $titulo = 'Mis Checklists';

$tipos = $tipos ?? [];
$vencidos = $vencidos ?? [];
$fecha = $fecha ?? date('Y-m-d');
$rol_actual = $_SESSION['rol'] ?? '';

if (empty($fecha) || !strtotime($fecha)) {
    $fecha = date('Y-m-d');
}

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-tasks text-primary me-2"></i>Mis Checklists
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-calendar me-1"></i> <?= date('d/m/Y', strtotime($fecha)) ?>
            </p>
        </div>
        <a href="/proyecto/checklists" class="btn btn-secondary btn-header-action">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Mensajes -->
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

    <!-- ========================================== -->
    <!-- SECCIÓN 1: CHECKLISTS VENCIDOS (si hay)     -->
    <!-- ========================================== -->
    <?php if (!empty($vencidos)): ?>
        <div class="card border-0 mb-4 card-vencidos">
            <div class="card-header bg-danger text-white border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Pendientes de días anteriores
                    </h5>
                    <span class="badge bg-white text-danger fs-6"><?= count($vencidos) ?></span>
                </div>
                <small class="text-white-50">
                    <i class="fas fa-info-circle me-1"></i>
                    Estos checklists no se completaron en la fecha asignada. Complétalos o repórtalos.
                </small>
            </div>
            <div class="card-body p-3">
                <div class="row g-3">
                    <?php foreach ($vencidos as $v): 
                        $dias = (int)($v['dias_atraso'] ?? 0);
                    ?>
                        <div class="col-xl-6">
                            <div class="card border-start border-danger border-4 shadow-sm h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <span class="badge bg-danger mb-2">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= $dias ?> día<?= $dias > 1 ? 's' : '' ?> de atraso
                                            </span>
                                            <br>
                                            <span class="badge bg-primary bg-opacity-10 text-primary">
                                                <?= htmlspecialchars($v['tipo_codigo'] ?? '') ?>
                                            </span>
                                            <h6 class="fw-semibold mt-2 mb-1">
                                                <?= htmlspecialchars($v['tipo_nombre'] ?? '') ?>
                                            </h6>
                                        </div>
                                    </div>

                                    <p class="text-muted small mb-2">
                                        <i class="fas fa-calendar-times me-1"></i>
                                        Fecha asignada: <strong><?= date('d/m/Y', strtotime($v['fecha'])) ?></strong>
                                        <?php if (!empty($v['turno'])): ?>
                                            · Turno <span class="badge bg-dark"><?= htmlspecialchars($v['turno']) ?></span>
                                        <?php endif; ?>
                                    </p>

                                    <?php if (!empty($v['asignado_por_nombre'])): ?>
                                        <p class="text-muted small mb-3">
                                            <i class="fas fa-user-cog me-1"></i>
                                            Asignado por: <strong><?= htmlspecialchars($v['asignado_por_nombre']) ?></strong>
                                        </p>
                                    <?php endif; ?>

                                    <div class="d-flex gap-2">
                                        <a href="/proyecto/checklists/llenar/<?= (int)$v['id'] ?>" 
                                           class="btn btn-sm btn-danger flex-grow-1">
                                            <i class="fas fa-edit me-1"></i> Completar ahora
                                        </a>
                                        <?php if (in_array($rol_actual, ['admin', 'supervisor'], true)): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-secondary"
                                                    onclick="omitirVencido(<?= (int)$v['id'] ?>, '<?= htmlspecialchars($v['tipo_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>')"
                                                    title="Marcar como omitido">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ========================================== -->
    <!-- SECCIÓN 2: CHECKLISTS DE HOY                -->
    <!-- ========================================== -->
    <div class="d-flex align-items-center mb-3">
        <h5 class="mb-0 fw-semibold">
            <i class="fas fa-calendar-check text-success me-2"></i>
            Checklists de Hoy
        </h5>
        <span class="badge bg-success bg-opacity-10 text-success ms-2">
            <?= count($tipos) ?> disponible<?= count($tipos) !== 1 ? 's' : '' ?>
        </span>
    </div>

    <?php if (empty($tipos)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No tenés checklists asignados para hoy.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($tipos as $t):
                $reg = $t['registro_hoy'] ?? null;
                $es_asignado_a_mi = !empty($t['es_asignado_a_mi']);

                $estado_badge = 'secondary';
                $estado_label = 'Sin iniciar';
                $accion_label = 'Iniciar';
                $accion_url   = '/proyecto/checklists/iniciar/' . (int)$t['id'];
                $accion_icono = 'fa-play-circle';
                $accion_color = 'primary';

                if ($reg) {
                    $estado_badge = match($reg['estado']) {
                        'BORRADOR'  => 'warning',
                        'CERRADO'   => 'info',
                        'FIRMADO'   => 'success',
                        'RECHAZADO' => 'danger',
                        default     => 'secondary'
                    };
                    $estado_label = $reg['estado'];

                    if ($reg['estado'] === 'BORRADOR') {
                        $accion_label = 'Continuar llenando';
                        $accion_url   = '/proyecto/checklists/llenar/' . (int)$reg['id'];
                        $accion_icono = 'fa-edit';
                        $accion_color = 'warning';
                    } elseif ($reg['estado'] === 'CERRADO') {
                        $accion_label = 'Ver / Firmar';
                        $accion_url   = '/proyecto/checklists/firmar/' . (int)$reg['id'];
                        $accion_icono = 'fa-signature';
                        $accion_color = 'info';
                    } else {
                        $accion_label = 'Ver';
                        $accion_url   = '/proyecto/checklists/ver/' . (int)$reg['id'];
                        $accion_icono = 'fa-eye';
                        $accion_color = 'secondary';
                    }
                }

                $descripcion = $t['descripcion'] ?? '';
                $descripcion_corta = mb_substr($descripcion, 0, 100);
                if (mb_strlen($descripcion) > 100) {
                    $descripcion_corta .= '...';
                }
            ?>
                <div class="col-xl-4 col-lg-6">
                    <div class="card border-0 h-100 <?= $es_asignado_a_mi ? 'card-asignado' : '' ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="badge bg-primary bg-opacity-10 text-primary mb-2">
                                        <?= htmlspecialchars($t['codigo'] ?? '') ?>
                                    </span>
                                    <?php if ($es_asignado_a_mi): ?>
                                        <span class="badge bg-warning text-dark mb-2 ms-1">
                                            <i class="fas fa-user-tag me-1"></i>ASIGNADO A TI
                                        </span>
                                    <?php endif; ?>
                                    <h6 class="fw-semibold mb-1"><?= htmlspecialchars($t['nombre'] ?? '') ?></h6>
                                </div>
                                <span class="badge bg-<?= $estado_badge ?>">
                                    <?= htmlspecialchars($estado_label) ?>
                                </span>
                            </div>

                            <p class="text-muted small mb-2">
                                <i class="fas fa-info-circle me-1"></i>
                                <?= htmlspecialchars($descripcion_corta) ?>
                            </p>

                            <?php if ($reg && !empty($reg['asignado_por_nombre'])): ?>
                                <p class="text-muted small mb-2">
                                    <i class="fas fa-user-cog me-1"></i>
                                    Asignado por: <strong><?= htmlspecialchars($reg['asignado_por_nombre']) ?></strong>
                                </p>
                            <?php endif; ?>

                            <?php if ($reg && !empty($reg['fecha'])): ?>
                                <p class="text-muted small mb-3">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    <?= date('d/m/Y', strtotime($reg['fecha'])) ?>
                                    <?php if (!empty($reg['turno'])): ?>
                                        · Turno <span class="badge bg-dark"><?= htmlspecialchars($reg['turno']) ?></span>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>

                            <a href="<?= $accion_url ?>" class="btn btn-<?= $accion_color ?> w-100">
                                <i class="fas <?= $accion_icono ?> me-1"></i> <?= $accion_label ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- Modal para omitir vencido -->
<div class="modal fade" id="modalOmitir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/proyecto/checklists/omitir" id="formOmitir">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                <input type="hidden" name="registro_id" id="omitirRegistroId" value="">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title"><i class="fas fa-ban me-2"></i>Omitir Checklist</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Omitir el checklist <strong id="omitirNombre"></strong>?</p>
                    <div class="alert alert-warning small mb-3">
                        <i class="fas fa-info-circle me-1"></i>
                        El checklist quedará marcado como <strong>OMITIDO</strong> y se guardará en el historial.
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Motivo de la omisión <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="motivo" rows="3" required
                                  placeholder="Explica por qué se omite este checklist..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-ban me-1"></i> Confirmar Omisión
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px var(--shadow-color);
}
.card-asignado {
    border-left: 4px solid #ffc107 !important;
    box-shadow: 0 2px 12px rgba(255, 193, 7, 0.25) !important;
}
.card-asignado:hover {
    box-shadow: 0 4px 20px rgba(255, 193, 7, 0.35) !important;
}
.card-vencidos {
    border-left: 4px solid #dc3545 !important;
    box-shadow: 0 4px 20px rgba(220, 53, 69, 0.15) !important;
}
.card-vencidos .card-header {
    border-radius: 16px 16px 0 0 !important;
}
[data-theme="dark"] .card-asignado {
    box-shadow: 0 2px 12px rgba(234, 179, 8, 0.3) !important;
}
[data-theme="dark"] .card-vencidos {
    box-shadow: 0 4px 20px rgba(239, 68, 68, 0.2) !important;
}
</style>

<script>
function omitirVencido(registroId, nombre) {
    document.getElementById('omitirRegistroId').value = registroId;
    document.getElementById('omitirNombre').textContent = nombre;
    new bootstrap.Modal(document.getElementById('modalOmitir')).show();
}
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>