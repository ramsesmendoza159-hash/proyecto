<?php
// views/firmas/firmar.php
// Formulario para firmar un paso - CON SIGNATUREPAD SVG VECTORIAL
// ✅ FIX: agregado require_once FirmaSecuenciaHelper

require_once __DIR__ . '/../../helpers/FirmaSecuenciaHelper.php';

if (!isset($orden) || !isset($pasoActual)) {
    header('Location: /proyecto/ordenes');
    exit;
}

$color = FirmaSecuenciaHelper::obtenerColorPaso($pasoActual['rol_firmante'] ?? '');
$icono = FirmaSecuenciaHelper::obtenerIconoPaso($pasoActual['rol_firmante'] ?? '');

include_once __DIR__ . '/../layouts/header.php';
?>

<!-- Signature Pad library -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-signature text-primary me-2"></i>Firmar Paso <?= $pasoActual['paso'] ?>
            </h4>
            <p class="text-muted small mb-0">
                <?= htmlspecialchars($pasoActual['nombre_paso'] ?? '') ?>
            </p>
        </div>
        <a href="/proyecto/firmas/orden/<?= $orden['id'] ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="row g-4">
        <!-- Info del paso -->
        <div class="col-lg-4">
            <div class="card border-0" style="border-left: 4px solid <?= $color ?> !important;">
                <div class="card-body text-center">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                         style="width: 80px; height: 80px; background: <?= $color ?>20;">
                        <i class="fas <?= $icono ?> fa-2x" style="color: <?= $color ?>;"></i>
                    </div>
                    <h5 class="fw-bold">Paso <?= $pasoActual['paso'] ?></h5>
                    <p class="text-muted"><?= htmlspecialchars($pasoActual['nombre_paso'] ?? '') ?></p>
                    <hr>
                    <div class="text-start small">
                        <p class="mb-1"><strong>Rol:</strong> <?= ucfirst($pasoActual['rol_firmante']) ?></p>
                        <p class="mb-1"><strong>Orden:</strong> #<?= htmlspecialchars($orden['num_om'] ?? $orden['id']) ?></p>
                        <p class="mb-0"><strong>Título:</strong> <?= htmlspecialchars($orden['titulo'] ?? '') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de firma -->
        <div class="col-lg-8">
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-pen-fancy text-primary me-2"></i>Tu Firma
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="/proyecto/firmas/procesar" id="formFirma">
                        <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                        <input type="hidden" name="orden_id" value="<?= $orden['id'] ?>">
                        <input type="hidden" name="paso_id" value="<?= $pasoActual['id'] ?>">
                        <input type="hidden" name="firma_svg" id="firma_svg_input" value="">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Firma manuscrita <span class="text-danger">*</span></label>
                            <div class="border rounded" style="background: #fff;">
                                <canvas id="canvasFirma" style="width: 100%; height: 200px; cursor: crosshair; touch-action: none; display: block;"></canvas>
                            </div>
                            <div class="d-flex gap-2 mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="limpiarFirma()">
                                    <i class="fas fa-eraser me-1"></i> Limpiar
                                </button>
                                <small class="text-muted align-self-center">
                                    <i class="fas fa-info-circle me-1"></i>Dibuja tu firma con el mouse o dedo
                                </small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Comentario (opcional)</label>
                            <textarea class="form-control" name="comentario" rows="3" 
                                      placeholder="Observaciones o comentarios..."></textarea>
                        </div>

                        <hr>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-success btn-lg" id="btnFirmar">
                                <i class="fas fa-check-circle me-2"></i> Firmar
                            </button>
                            <button type="button" class="btn btn-danger btn-lg" data-bs-toggle="modal" data-bs-target="#modalRechazar">
                                <i class="fas fa-times-circle me-2"></i> Rechazar
                            </button>
                            <a href="/proyecto/firmas/orden/<?= $orden['id'] ?>" class="btn btn-secondary btn-lg">
                                <i class="fas fa-arrow-left me-2"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal Rechazar -->
<div class="modal fade" id="modalRechazar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/proyecto/firmas/rechazar">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                <input type="hidden" name="orden_id" value="<?= $orden['id'] ?>">
                <input type="hidden" name="paso_id" value="<?= $pasoActual['id'] ?>">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Rechazar Paso</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de rechazar este paso?</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Motivo del rechazo <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="motivo" rows="3" required
                                  placeholder="Explica por qué rechazas este paso..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-1"></i> Confirmar Rechazo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let signaturePadFirma = null;

document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('canvasFirma');
    const firmaInput = document.getElementById('firma_svg_input');

    if (typeof SignaturePad === 'undefined') {
        alert('Error: La librería SignaturePad no se cargó. Recarga la página.');
        return;
    }

    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * ratio;
        canvas.height = 200 * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        if (signaturePadFirma) {
            signaturePadFirma.clear();
        }
    }

    signaturePadFirma = new SignaturePad(canvas, {
        backgroundColor: 'rgba(255, 255, 255, 0)',
        penColor: 'rgb(0, 0, 0)',
        minWidth: 1.5,
        maxWidth: 2.5
    });

    setTimeout(resizeCanvas, 100);
    window.addEventListener('resize', resizeCanvas);

    document.getElementById('formFirma').addEventListener('submit', function(e) {
        if (!signaturePadFirma || signaturePadFirma.isEmpty()) {
            e.preventDefault();
            alert('⚠️ Debes dibujar tu firma antes de continuar');
            return false;
        }

        const svg = signaturePadFirma.toSVG();

        if (svg.length > 200000) {
            e.preventDefault();
            alert('⚠️ La firma es demasiado grande (' + Math.round(svg.length / 1024) + ' KB). Intenta dibujar más simple.');
            return false;
        }

        firmaInput.value = svg;

        const btn = document.getElementById('btnFirmar');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Firmando...';
    });
});

function limpiarFirma() {
    if (signaturePadFirma) {
        signaturePadFirma.clear();
    }
    document.getElementById('firma_svg_input').value = '';
}
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>