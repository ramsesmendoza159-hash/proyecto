<?php
// views/monitoreo/mis_rutas.php
// Rutas disponibles para el operador/técnico

if (!isset($seccion)) $seccion = 'monitoreo';
if (!isset($titulo)) $titulo = 'Mis Rutas de Monitoreo';

$rutas         = $rutas ?? [];
$turno_actual  = $turno_actual ?? 'DIA';
$fecha         = $_GET['fecha'] ?? date('Y-m-d');
$monitoreo_pendientes = $monitoreo_pendientes ?? 0;

$turno_label = match($turno_actual) {
    'DIA'   => 'Turno DÍA (8am – 4pm)',
    'TARDE' => 'Turno TARDE (4pm – 12am)',
    'NOCHE' => 'Turno NOCHE (12am – 8am)',
    default => 'Turno ' . $turno_actual
};

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-play-circle text-primary me-2"></i>Mis Rutas de Monitoreo
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-clock me-1"></i> <?= htmlspecialchars($turno_label) ?>
                <span class="mx-2">|</span>
                <i class="fas fa-calendar me-1"></i> <?= date('d/m/Y', strtotime($fecha)) ?>
            </p>
        </div>
        <a href="/proyecto/monitoreo" class="btn btn-secondary btn-header-action">
            <i class="fas fa-arrow-left"></i> Volver
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

    <!-- Fecha y turno selector -->
    <div class="card border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="/proyecto/monitoreo/mis-rutas" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold small">Fecha</label>
                    <input type="date" name="fecha" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($fecha) ?>"
                           max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small">Turno</label>
                    <select name="turno" class="form-select form-select-sm" disabled>
                        <option selected><?= htmlspecialchars($turno_label) ?></option>
                    </select>
                    <small class="text-muted">Detectado automáticamente</small>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-search me-1"></i> Consultar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Aviso de turnos pendientes -->
    <?php if ($monitoreo_pendientes > 0): ?>
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
            <i class="fas fa-exclamation-triangle fa-2x"></i>
            <div>
                <strong>Tenés <?= (int)$monitoreo_pendientes ?> turno(s) pendiente(s) o vencido(s).</strong>
                <div class="small mb-0">Revisá las rutas de abajo y completá las lecturas del turno actual.</div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Listado de rutas -->
    <?php if (empty($rutas)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>No hay rutas de monitoreo configuradas.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($rutas as $r): ?>
                <?php
                $reg = $r['registro_actual'] ?? null;
                $estado_badge = 'secondary';
                $estado_label = 'Sin iniciar';
                $accion_label = 'Iniciar Turno';
                $accion_url   = '/proyecto/monitoreo/iniciar/' . (int)$r['id']
                              . '?fecha=' . urlencode($fecha)
                              . '&turno=' . urlencode($turno_actual);

                if ($reg) {
                    $estado_badge = match($reg['estado'] ?? 'BORRADOR') {
                        'BORRADOR'  => 'warning',
                        'CERRADO'   => 'info',
                        'FIRMADO'   => 'success',
                        'RECHAZADO' => 'danger',
                        'VENCIDO'   => 'dark',
                        default     => 'secondary'
                    };
                    $estado_label = ucfirst(strtolower($reg['estado']));

                    if ($reg['estado'] === 'BORRADOR') {
                        $accion_label = 'Continuar';
                        $accion_url   = '/proyecto/monitoreo/llenar/' . (int)$reg['id'];
                    } elseif ($reg['estado'] === 'VENCIDO') {
                        $accion_label = 'Ver vencido';
                        $accion_url   = '/proyecto/monitoreo/ver/' . (int)$reg['id'];
                    } else {
                        $accion_label = 'Ver';
                        $accion_url   = '/proyecto/monitoreo/ver/' . (int)$reg['id'];
                    }
                }
                ?>
                <div class="col-xl-4 col-lg-6">
                    <div class="card border-0 h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <span class="badge bg-primary bg-opacity-10 text-primary mb-2">
                                        <?= htmlspecialchars($r['codigo'] ?? '') ?>
                                    </span>
                                    <h6 class="fw-semibold mb-1"><?= htmlspecialchars($r['nombre'] ?? '') ?></h6>
                                </div>
                                <span class="badge bg-<?= $estado_badge ?>">
                                    <?= htmlspecialchars($estado_label) ?>
                                </span>
                            </div>

                            <p class="text-muted small mb-2">
                                <i class="fas fa-industry me-1"></i>
                                <?= (int)($r['total_equipos'] ?? 0) ?> equipos
                                <span class="mx-2">|</span>
                                <i class="fas fa-clock me-1"></i>
                                <?= (int)($r['total_horarios'] ?? 0) ?> horarios
                            </p>

                            <p class="text-muted small mb-3">
                                <?= htmlspecialchars(substr($r['descripcion'] ?? '', 0, 100)) ?>
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