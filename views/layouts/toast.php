<?php
// views/layouts/toast.php
// Componente visual de notificaciones toast
// ✅ FIX CRÍTICO: showToast() ahora escapa HTML del mensaje y título (antes XSS)

if (!class_exists('ToastHelper')) {
    require_once __DIR__ . '/../../helpers/ToastHelper.php';
}

ToastHelper::migrarMensajesAntiguos();
$toasts = ToastHelper::getAndClear();
?>

<div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer" style="z-index: 9999;">
    <?php foreach ($toasts as $toast): ?>
        <div class="toast toast-custom toast-<?= htmlspecialchars($toast['tipo']) ?>"
             role="alert"
             aria-live="assertive"
             aria-atomic="true"
             data-bs-autohide="true"
             data-bs-delay="<?= (int)$toast['duracion'] ?>"
             id="<?= htmlspecialchars($toast['id']) ?>">

            <div class="toast-header">
                <div class="toast-icon">
                    <i class="fas <?= match($toast['tipo']) {
                        'success' => 'fa-check-circle',
                        'error'   => 'fa-times-circle',
                        'warning' => 'fa-exclamation-triangle',
                        'info'    => 'fa-info-circle',
                        default   => 'fa-bell'
                    } ?>"></i>
                </div>
                <strong class="me-auto"><?= htmlspecialchars($toast['titulo']) ?></strong>
                <small class="text-muted">ahora</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
            <div class="toast-body">
                <?= nl2br(htmlspecialchars($toast['mensaje'])) ?>
            </div>
            <div class="toast-progress"></div>
        </div>
    <?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toastElements = document.querySelectorAll('.toast-custom');
    toastElements.forEach(function(toastEl) {
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', function() {
            toastEl.remove();
        });
    });
});

/**
 * ✅ FIX: escapar HTML antes de insertar en el DOM
 */
function escapeHtmlToast(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function showToast(tipo, mensaje, titulo = null, duracion = 5000) {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    if (titulo === null) {
        const titulos = {
            'success': '¡Éxito!',
            'error':   'Error',
            'warning': 'Advertencia',
            'info':    'Información'
        };
        titulo = titulos[tipo] || 'Notificación';
    }

    const iconos = {
        'success': 'fa-check-circle',
        'error':   'fa-times-circle',
        'warning': 'fa-exclamation-triangle',
        'info':    'fa-info-circle'
    };
    const icono = iconos[tipo] || 'fa-bell';

    const tipoSafe    = escapeHtmlToast(tipo);
    const tituloSafe  = escapeHtmlToast(titulo);
    const mensajeSafe = escapeHtmlToast(mensaje).replace(/\n/g, '<br>');

    const id = 'toast_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

    const toastHTML = `
        <div class="toast toast-custom toast-${tipoSafe}"
             role="alert"
             aria-live="assertive"
             aria-atomic="true"
             data-bs-autohide="true"
             data-bs-delay="${parseInt(duracion, 10) || 5000}"
             id="${id}">
            <div class="toast-header">
                <div class="toast-icon">
                    <i class="fas ${icono}"></i>
                </div>
                <strong class="me-auto">${tituloSafe}</strong>
                <small class="text-muted">ahora</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
            <div class="toast-body">
                ${mensajeSafe}
            </div>
            <div class="toast-progress"></div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', toastHTML);

    const toastEl = document.getElementById(id);
    const toast = new bootstrap.Toast(toastEl);
    toast.show();

    toastEl.addEventListener('hidden.bs.toast', function() {
        toastEl.remove();
    });
}

window.toastSuccess = (msg, title) => showToast('success', msg, title);
window.toastError   = (msg, title) => showToast('error', msg, title, 7000);
window.toastWarning = (msg, title) => showToast('warning', msg, title, 6000);
window.toastInfo    = (msg, title) => showToast('info', msg, title);
</script>