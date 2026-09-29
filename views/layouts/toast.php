<?php
// views/layouts/toast.php
// Componente visual de notificaciones toast

// Incluir el helper si no existe
if (!class_exists('ToastHelper')) {
    require_once __DIR__ . '/../../helpers/ToastHelper.php';
}

// Migrar mensajes antiguos automáticamente
ToastHelper::migrarMensajesAntiguos();

// Obtener toasts pendientes
$toasts = ToastHelper::getAndClear();
?>

<!-- Contenedor de toasts -->
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
                        'error' => 'fa-times-circle',
                        'warning' => 'fa-exclamation-triangle',
                        'info' => 'fa-info-circle',
                        default => 'fa-bell'
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
// Inicializar toasts al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar todos los toasts existentes
    const toastElements = document.querySelectorAll('.toast-custom');
    toastElements.forEach(function(toastEl) {
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
        
        // Auto-eliminar del DOM al ocultarse
        toastEl.addEventListener('hidden.bs.toast', function() {
            toastEl.remove();
        });
    });
});

/**
 * Función global para crear toasts desde JavaScript
 * Uso: showToast('success', 'Mensaje', 'Título opcional', 5000)
 */
function showToast(tipo, mensaje, titulo = null, duracion = 5000) {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    
    // Título por defecto
    if (titulo === null) {
        const titulos = {
            'success': '¡Éxito!',
            'error': 'Error',
            'warning': 'Advertencia',
            'info': 'Información'
        };
        titulo = titulos[tipo] || 'Notificación';
    }
    
    // Iconos
    const iconos = {
        'success': 'fa-check-circle',
        'error': 'fa-times-circle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    };
    const icono = iconos[tipo] || 'fa-bell';
    
    // Crear ID único
    const id = 'toast_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    
    // Crear HTML del toast
    const toastHTML = `
        <div class="toast toast-custom toast-${tipo}" 
             role="alert" 
             aria-live="assertive" 
             aria-atomic="true"
             data-bs-autohide="true"
             data-bs-delay="${duracion}"
             id="${id}">
            <div class="toast-header">
                <div class="toast-icon">
                    <i class="fas ${icono}"></i>
                </div>
                <strong class="me-auto">${titulo}</strong>
                <small class="text-muted">ahora</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
            <div class="toast-body">
                ${mensaje.replace(/\n/g, '<br>')}
            </div>
            <div class="toast-progress"></div>
        </div>
    `;
    
    // Insertar
    container.insertAdjacentHTML('beforeend', toastHTML);
    
    // Mostrar
    const toastEl = document.getElementById(id);
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
    
    // Eliminar del DOM al ocultarse
    toastEl.addEventListener('hidden.bs.toast', function() {
        toastEl.remove();
    });
}

// Atajos globales
window.toastSuccess = (msg, title) => showToast('success', msg, title);
window.toastError = (msg, title) => showToast('error', msg, title, 7000);
window.toastWarning = (msg, title) => showToast('warning', msg, title, 6000);
window.toastInfo = (msg, title) => showToast('info', msg, title);
</script>