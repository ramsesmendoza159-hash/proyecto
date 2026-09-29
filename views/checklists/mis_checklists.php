<?php
// views/checklists/mis_checklists.php
// ✅ FIX: substr seguro sobre $t['descripcion']
// ✅ FIX: manejo de $fecha null

if (!isset($seccion)) $seccion = 'checklists';
if (!isset($titulo)) $titulo = 'Mis Checklists';

$tipos = $tipos ?? [];
$fecha = $fecha ?? date('Y-m-d');

// ✅ FIX: validar fecha
if (empty($fecha) || !strtotime($fecha)) {
    $fecha = date('Y-m-d');
}

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-tasks text-primary me-2"></i>Mis Checklists
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-calendar me-1"></i> <?= date('d/m/Y', strtotime($fecha)) ?>
            </p>
        </div>
        <a href="/proyecto/checklists" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
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

    <?php if (empty($tipos)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No tenés checklists asignados.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($tipos as $t): 
                $reg = $t['registro_hoy'] ?? null;
                $estado_badge = 'secondary';
                $estado_label = 'Sin iniciar';
                $accion_label = 'Iniciar';
                $accion_url   = '/proyecto/checklists/iniciar/' . (int)$t['id'];

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
                        $accion_label = 'Continuar';
                        $accion_url   = '/proyecto/checklists/llenar/' . (int)$reg['id'];
                    } else {
                        $accion_label = 'Ver';
                        $accion_url   = '/proyecto/checklists/ver/' . (int)$reg['id'];
                    }
                }
                
                // ✅ FIX: substr seguro
                $descripcion = $t['descripcion'] ?? '';
                $descripcion_corta = mb_substr($descripcion, 0, 100);
            ?>
                <div class="col-xl-4 col-lg-6">
                    <div class="card border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="badge bg-primary bg-opacity-10 text-primary mb-2">
                                        <?= htmlspecialchars($t['codigo'] ?? '') ?>
                                    </span>
                                    <h6 class="fw-semibold mb-1"><?= htmlspecialchars($t['nombre'] ?? '') ?></h6>
                                </div>
                                <span class="badge bg-<?= $estado_badge ?>">
                                    <?= htmlspecialchars($estado_label) ?>
                                </span>
                            </div>

                            <p class="text-muted small mb-3">
                                <i class="fas fa-info-circle me-1"></i>
                                <?= htmlspecialchars($descripcion_corta) ?>
                            </p>

                            <a href="<?= $accion_url ?>" class="btn btn-primary w-100">
                                <i class="fas fa-play-circle me-1"></i> <?= $accion_label ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<style>
.card { border-radius: 16px; box-shadow: 0 2px 12px var(--shadow-color); }
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>
