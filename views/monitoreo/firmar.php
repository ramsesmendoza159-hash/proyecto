<?php
// views/monitoreo/firmar.php
// Panel de firmas del registro de monitoreo

if (!isset($seccion)) $seccion = 'monitoreo';
if (!isset($titulo)) $titulo = 'Firmar Registro';

$registro = $registro ?? null;
$paso_actual = $paso_actual ?? null;
$puede_firmar = $puede_firmar ?? false;

if (!$registro) {
    header('Location: /proyecto/monitoreo');
    exit;
}

$firmas = $registro['firmas'] ?? [];
$resumen = $registro['resumen'] ?? ['total' => 0, 'firmados' => 0, 'porcentaje' => 0, 'completa' => false];
$unidad = $registro['unidad_medida'] ?? '%';
$turno_reg = $registro['turno'] ?? 'DIA';

include_once __DIR__ . '/../layouts/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-signature text-primary me-2"></i>Firmar Registro
            </h4>
            <p class="text-muted small mb-0">
                <?= htmlspecialchars($registro['ruta_nombre'] ?? '') ?>
                <span class="mx-2">|</span>
                <i class="fas fa-calendar me-1"></i><?= date('d/m/Y', strtotime($registro['fecha'])) ?>
                <span class="mx-2">|</span>
                <span class="badge bg-dark"><?= htmlspecialchars($turno_reg) ?></span>
            </p>
        </div>
        <a href="/proyecto/monitoreo/ver/<?= (int)$registro['id'] ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver al Registro
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

    <!-- Progreso -->
    <div class="card border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <span>
                    <strong><?= (int)$resumen['firmados'] ?></strong> de
                    <strong><?= (int)$resumen['total'] ?></strong> firmas completadas
                </span>
                <span class="badge bg-<?= $resumen['completa'] ? 'success' : 'warning' ?>">
                    <?= (int)$resumen['porcentaje'] ?>%
                </span>
            </div>
            <div class="progress" style="height: 10px; border-radius: 10px;">
                <div class="progress-bar bg-success"
                     style="width: <?= (int)$resumen['porcentaje'] ?>%; border-radius: 10px;"></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Timeline de firmas -->
        <div class="col-lg-7">
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-list-ol text-primary me-2"></i>Secuencia de Firmas
                    </h5>
                </div>
                <div class="card-body">
                    <?php foreach ($firmas as $f): ?>
                        <?php
                        $es_actual = $paso_actual && (int)$paso_actual['id'] === (int)$f['id'];
                        $badge = match($f['estado']) {
                            'FIRMADO'   => 'success',
                            'RECHAZADO' => 'danger',
                            'PENDIENTE' => $es_actual ? 'warning' : 'secondary',
                            default     => 'secondary'
                        };
                        ?>
                        <div class="d-flex align-items-start mb-3 p-3 rounded-3 border border-<?= $badge ?>"
                             style="<?= $es_actual ? 'background:rgba(255,193,7,0.08); border-width:2px !important;' : '' ?>">
                            <div class="me-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center"
                                     style="width:50px;height:50px;background:rgba(13,110,253,0.1);color:#0d6efd;">
                                    <i class="fas fa-signature"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                    <span class="badge bg-dark">Paso <?= (int)$f['paso'] ?></span>
                                    <strong class="text-capitalize"><?= htmlspecialchars($f['rol_firmante']) ?></strong>
                                    <span class="badge bg-<?= $badge ?> ms-auto"><?= htmlspecialchars($f['estado']) ?></span>
                                </div>
                                <?php if ($f['estado'] === 'FIRMADO'): ?>
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i><?= htmlspecialchars($f['usuario_nombre'] ?? 'N/A') ?>
                                        <span class="mx-2">|</span>
                                        <i class="fas fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($f['fecha_firma'])) ?>
                                    </small>
                                <?php elseif ($f['estado'] === 'RECHAZADO'): ?>
                                    <small class="text-danger">
                                        <i class="fas fa-times-circle me-1"></i>
                                        <strong>Rechazado:</strong> <?= htmlspecialchars($f['motivo_rechazo'] ?? '') ?>
                                    </small>
                                <?php elseif ($es_actual): ?>
                                    <small class="text-warning fw-semibold">
                                        <i class="fas fa-hourglass-half me-1"></i>Esperando firma
                                    </small>
                                <?php else: ?>
                                    <small class="text-muted">
                                        <i class="fas fa-lock me-1"></i>Pendiente de pasos anteriores
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Formulario de firma -->
        <div class="col-lg-5">
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-pen-fancy text-primary me-2"></i>Tu Firma
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!$paso_actual): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            Todas las firmas están completas.
                        </div>
                    <?php elseif (!$puede_firmar): ?>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-lock me-2"></i>
                            El paso actual requiere el rol: <strong><?= htmlspecialchars($paso_actual['rol_firmante']) ?></strong>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="/proyecto/monitoreo/procesar-firma" id="formFirma">
                            <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                            <input type="hidden" name="registro_id" value="<?= (int)$registro['id'] ?>">
                            <input type="hidden" name="paso_id" value="<?= (int)$paso_actual['id'] ?>">
                            <input type="hidden" name="firma_svg" id="firma_svg_input" value="">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Firma manuscrita <span class="text-danger">*</span></label>
                                <div class="border rounded" style="background:#fff;">
                                    <canvas id="canvasFirma" style="width:100%;height:180px;cursor:crosshair;touch-action:none;display:block;"></canvas>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="limpiarFirma()">
                                    <i class="fas fa-eraser me-1"></i> Limpiar
                                </button>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Comentario (opcional)</label>
                                <textarea class="form-control" name="comentario" rows="2"
                                          placeholder="Observaciones..."></textarea>
                            </div>

                            <hr>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-success flex-grow-1" id="btnFirmar">
                                    <i class="fas fa-check-circle me-1"></i> Firmar
                                </button>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalRechazar">
                                    <i class="fas fa-times-circle me-1"></i> Rechazar
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Rechazar -->
<?php if ($paso_actual && $puede_firmar): ?>
<div class="modal fade" id="modalRechazar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/proyecto/monitoreo/rechazar">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                <input type="hidden" name="registro_id" value="<?= (int)$registro['id'] ?>">
                <input type="hidden" name="paso_id" value="<?= (int)$paso_actual['id'] ?>">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Rechazar Registro</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Motivo del rechazo <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="motivo" rows="3" required
                                  placeholder="Explicá el motivo..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Confirmar Rechazo</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
let signaturePadFirma = null;

document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('canvasFirma');
    if (!canvas) return;

    if (typeof SignaturePad === 'undefined') {
        alert('Error: SignaturePad no se cargó. Recargá la página.');
        return;
    }

    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * ratio;
        canvas.height = 180 * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        if (signaturePadFirma) signaturePadFirma.clear();
    }

    signaturePadFirma = new SignaturePad(canvas, {
        backgroundColor: 'rgba(255,255,255,0)',
        penColor: 'rgb(0,0,0)',
        minWidth: 1.5,
        maxWidth: 2.5
    });

    setTimeout(resizeCanvas, 100);
    window.addEventListener('resize', resizeCanvas);

    const form = document.getElementById('formFirma');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!signaturePadFirma || signaturePadFirma.isEmpty()) {
                e.preventDefault();
                alert('⚠️ Debés dibujar tu firma antes de continuar');
                return false;
            }
            const svg = signaturePadFirma.toSVG();
            if (svg.length > 204800) {
                e.preventDefault();
                alert('⚠️ La firma es demasiado grande (' + Math.round(svg.length/1024) + ' KB)');
                return false;
            }
            document.getElementById('firma_svg_input').value = svg;
            const btn = document.getElementById('btnFirmar');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Firmando...';
        });
    }
});

function limpiarFirma() {
    if (signaturePadFirma) signaturePadFirma.clear();
    const input = document.getElementById('firma_svg_input');
    if (input) input.value = '';
}
</script>

<style>
.card { border-radius: 16px; box-shadow: 0 2px 12px var(--shadow-color); }
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>