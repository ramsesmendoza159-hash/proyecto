<?php
// views/perfil/index.php
// Ver perfil - CON CANVAS DE FIRMA MEJORADO + SANITIZACIÓN SVG CENTRALIZADA
// ✅ FIX: usar SVGSanitizer en vez de función inline

if (!isset($usuario) || empty($usuario)) {
    $_SESSION['error'] = 'No se pudo cargar la información del usuario.';
    header('Location: /proyecto/dashboard');
    exit;
}

$titulo = 'Mi Perfil';
$seccion = 'perfil';

// ✅ FIX: usar el helper centralizado
require_once __DIR__ . '/../../helpers/SVGSanitizer.php';

$firma_actual = $usuario['firma_svg'] ?? null;

// Validar si la firma SVG está bien formada
$firma_es_valida = false;
$firma_esta_truncada = false;

if (!empty($firma_actual)) {
    $firma_actual = trim($firma_actual);
    $tiene_inicio = (stripos($firma_actual, '<svg') !== false);
    $tiene_fin = (stripos($firma_actual, '</svg>') !== false);

    if ($tiene_inicio && $tiene_fin) {
        $firma_es_valida = true;
        // ✅ Sanitizar con el helper centralizado
        $firma_actual = SVGSanitizer::sanitize($firma_actual);
        if (empty($firma_actual)) {
            // Si la sanitización devolvió vacío, la firma no es recuperable
            $firma_es_valida = false;
        }
    } else {
        if ($tiene_inicio && !$tiene_fin) {
            $firma_esta_truncada = true;
        }
    }
}

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-user-circle text-primary me-2"></i>Mi Perfil
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Información de tu cuenta
            </p>
        </div>
        <a href="/proyecto/dashboard" class="btn btn-secondary btn-header-action">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <?php if (isset($_SESSION['mensaje']) && !empty($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?= $_SESSION['mensaje_tipo'] ?? 'success' ?> alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_SESSION['mensaje']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 text-center p-4">
                <div class="avatar-lg mx-auto mb-3" style="width:100px;height:100px;border-radius:50%;background:linear-gradient(135deg, var(--accent-primary), var(--accent-hover));color:#fff;display:flex;align-items:center;justify-content:center;font-size:3rem;font-weight:700;">
                    <?= strtoupper(substr($usuario['nombre'] ?? 'U', 0, 1)) ?>
                </div>
                <h5 class="fw-bold"><?= htmlspecialchars($usuario['nombre'] ?? '') ?></h5>
                <p class="text-muted small"><?= htmlspecialchars($usuario['rol'] ?? 'usuario') ?></p>
                <div class="d-grid gap-2 mt-3">
                    <a href="/proyecto/perfil/editar" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i> Editar Perfil
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0">
                <div class="card-body">
                    <h5 class="fw-bold mb-4">Información Personal</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small fw-semibold text-uppercase">Nombre</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($usuario['nombre'] ?? '') ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-semibold text-uppercase">Email</label>
                            <p class="fw-semibold mb-0"><?= htmlspecialchars($usuario['email'] ?? '') ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-semibold text-uppercase">Rol</label>
                            <p class="mb-0"><span class="badge bg-info"><?= htmlspecialchars($usuario['rol'] ?? 'usuario') ?></span></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small fw-semibold text-uppercase">Estado</label>
                            <p class="mb-0">
                                <span class="badge <?= ($usuario['estado'] ?? 'activo') === 'activo' ? 'bg-success' : 'bg-danger' ?>">
                                    <?= htmlspecialchars($usuario['estado'] ?? 'activo') ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    <hr>
                    <div class="mt-3">
                        <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalPassword">
                            <i class="fas fa-key me-2"></i> Cambiar Contraseña
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN DE FIRMA -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 firma-card">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-signature text-primary me-2"></i>
                        Mi Firma Digital
                    </h5>
                    <p class="text-muted small mb-0 mt-1">
                        Esta firma se usará automáticamente al firmar órdenes
                    </p>
                </div>
                <div class="card-body">

                    <?php if (!empty($firma_actual) && $firma_es_valida): ?>
                        <div class="alert alert-success mb-3">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Firma guardada:</strong> <?= number_format(strlen($firma_actual)) ?> bytes
                            <?php if (strlen($firma_actual) < 30000): ?>
                                <span class="badge bg-success ms-2">✓ Óptimo</span>
                            <?php elseif (strlen($firma_actual) < 65535): ?>
                                <span class="badge bg-warning text-dark ms-2">⚠️ Grande</span>
                            <?php else: ?>
                                <span class="badge bg-danger ms-2">⚠️ Muy grande</span>
                            <?php endif; ?>
                        </div>
                        <div class="mb-4">
                            <label class="text-muted small fw-semibold text-uppercase d-block mb-2">Firma actual</label>
                            <div class="firma-preview-box">
                                <div style="max-height: 120px; overflow: hidden;">
                                    <?php echo $firma_actual; ?>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($firma_esta_truncada): ?>
                        <div class="alert alert-danger mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>⚠️ Firma TRUNCADA.</strong>
                            La firma guardada está incompleta (no cierra con <code>&lt;/svg&gt;</code>).
                            Pesa <?= number_format(strlen($firma_actual)) ?> bytes.
                            Dibuja una nueva abajo para reemplazarla.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>No tienes una firma guardada.</strong> Dibújala abajo.
                        </div>
                    <?php endif; ?>

                    <h6 class="fw-semibold mt-4 mb-3">
                        <i class="fas fa-pen-fancy text-primary me-2"></i>
                        <?= !empty($firma_es_valida) ? 'Reemplazar con nueva firma' : 'Dibujar nueva firma' ?>
                    </h6>

                    <div class="mb-3">
                        <div class="canvas-container">
                            <canvas id="canvasFirmaPerfil" style="width: 100%; height: 200px; cursor: crosshair; touch-action: none; display: block;"></canvas>
                        </div>
                        <div class="d-flex gap-2 mt-2 flex-wrap align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="limpiarFirmaPerfil()">
                                <i class="fas fa-eraser me-1"></i> Limpiar
                            </button>
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>Dibuja con el mouse o el dedo
                            </small>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary" id="btnGuardarFirma" onclick="guardarFirmaPerfil()">
                        <i class="fas fa-save me-2"></i> Guardar Firma
                    </button>
                    <span id="mensajeFirma" class="ms-3"></span>

                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal Cambiar Contraseña -->
<div class="modal fade" id="modalPassword" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/proyecto/perfil/cambiar_password" method="POST">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key text-primary me-2"></i> Cambiar Contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Contraseña Actual</label>
                        <input type="password" class="form-control" name="password_actual" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nueva Contraseña</label>
                        <input type="password" class="form-control" name="password_nueva" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Confirmar Nueva Contraseña</label>
                        <input type="password" class="form-control" name="password_confirmar" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar Contraseña</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.avatar-lg {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    font-weight: 700;
}
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
.canvas-container {
    background: #ffffff;
    border: 2px solid var(--border-color, #e2e8f0);
    border-radius: 12px;
    padding: 8px;
    transition: border-color 0.3s ease;
}
.canvas-container:focus-within {
    border-color: var(--accent-primary, #0d6efd);
}
#canvasFirmaPerfil {
    background: #ffffff;
    border-radius: 8px;
    display: block;
}
.firma-preview-box {
    background: #ffffff;
    border: 1px solid var(--border-color, #e2e8f0);
    border-radius: 12px;
    padding: 16px;
    max-width: 400px;
}
[data-theme="dark"] .canvas-container {
    background: #ffffff !important;
    border-color: #3b82f6 !important;
    box-shadow: 0 0 20px rgba(59, 130, 246, 0.15) !important;
}
[data-theme="dark"] #canvasFirmaPerfil {
    background: #ffffff !important;
}
[data-theme="dark"] .firma-preview-box {
    background: #ffffff !important;
    border-color: #2a2a45 !important;
}
[data-theme="dark"] .firma-card .card-header h5 {
    color: #ffffff !important;
}
[data-theme="dark"] .firma-card .card-header p {
    color: #8a8aa8 !important;
}
[data-theme="dark"] .firma-card h6 {
    color: #ffffff !important;
}
[data-theme="dark"] .text-muted {
    color: #8a8aa8 !important;
}
[data-theme="dark"] .fw-semibold,
[data-theme="dark"] .fw-bold {
    color: #ffffff !important;
}
[data-theme="dark"] label.text-muted {
    color: #8a8aa8 !important;
}
[data-theme="dark"] p {
    color: #e2e8f0;
}
[data-theme="dark"] h5 {
    color: #ffffff;
}
[data-theme="dark"] .alert-success {
    background: rgba(34, 197, 94, 0.1) !important;
    color: #4ade80 !important;
    border-left: 4px solid #22c55e !important;
}
[data-theme="dark"] .alert-warning {
    background: rgba(234, 179, 8, 0.1) !important;
    color: #facc15 !important;
    border-left: 4px solid #eab308 !important;
}
[data-theme="dark"] .alert-danger {
    background: rgba(239, 68, 68, 0.1) !important;
    color: #f87171 !important;
    border-left: 4px solid #ef4444 !important;
}
[data-theme="dark"] .badge.bg-info {
    background: rgba(6, 182, 212, 0.2) !important;
    color: #22d3ee !important;
}
[data-theme="dark"] .badge.bg-success {
    background: rgba(34, 197, 94, 0.2) !important;
    color: #4ade80 !important;
}
[data-theme="dark"] .badge.bg-warning {
    background: rgba(234, 179, 8, 0.2) !important;
    color: #facc15 !important;
}
[data-theme="dark"] .badge.bg-danger {
    background: rgba(239, 68, 68, 0.2) !important;
    color: #f87171 !important;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
let signaturePadPerfil = null;

document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('canvasFirmaPerfil');
    if (!canvas) return;

    if (typeof SignaturePad === 'undefined') {
        console.error('SignaturePad NO se cargó. Verifica la conexión a internet o el CDN.');
        alert('Error: La librería SignaturePad no se cargó. Recarga la página.');
        return;
    }

    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * ratio;
        canvas.height = 200 * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        if (signaturePadPerfil) {
            signaturePadPerfil.clear();
        }
    }

    signaturePadPerfil = new SignaturePad(canvas, {
        backgroundColor: 'rgba(255, 255, 255, 0)',
        penColor: 'rgb(0, 0, 0)',
        minWidth: 1.5,
        maxWidth: 2.5
    });

    setTimeout(resizeCanvas, 100);
    window.addEventListener('resize', resizeCanvas);
});

function limpiarFirmaPerfil() {
    if (signaturePadPerfil) {
        signaturePadPerfil.clear();
    }
}

function guardarFirmaPerfil() {
    const mensaje = document.getElementById('mensajeFirma');
    const btn = document.getElementById('btnGuardarFirma');

    if (!signaturePadPerfil || signaturePadPerfil.isEmpty()) {
        mensaje.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Debes dibujar tu firma primero</span>';
        return;
    }

    const svg = signaturePadPerfil.toSVG();

    if (svg.length > 204800) {
        mensaje.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>La firma es demasiado grande (' + Math.round(svg.length/1024) + ' KB, máx 200 KB). Intenta dibujar más simple.</span>';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Guardando...';
    mensaje.innerHTML = '';

    fetch('/proyecto/perfil/guardar-firma', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'firma_svg=' + encodeURIComponent(svg) + '&csrf_token=' + encodeURIComponent('<?= SecurityHelper::generateCSRFToken() ?>')
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error HTTP: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            mensaje.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>' + (data.mensaje || 'Firma guardada') + '</span>';
            setTimeout(() => window.location.reload(), 1500);
        } else {
            mensaje.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>' + (data.mensaje || 'Error al guardar') + '</span>';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-2"></i> Guardar Firma';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mensaje.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Error: ' + error.message + '</span>';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-2"></i> Guardar Firma';
    });
}
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>