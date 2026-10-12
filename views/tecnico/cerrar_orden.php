<?php
// views/tecnico/cerrar_orden.php
// VERSIÓN CORREGIDA

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'tecnico') {
    header('Location: /proyecto/auth/login');
    exit();
}

// ✅ FIX: validar ANTES de incluir header (evita output antes de redirect)
$orden = $orden ?? null;
if (!$orden) {
    header('Location: /proyecto/tecnico/mis_ordenes');
    exit();
}

require_once __DIR__ . '/../../helpers/SecurityHelper.php';

$titulo = "Cerrar Orden de Trabajo";
$seccion = "tecnico";
include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-check-circle text-success me-2"></i>
                Cerrar Orden #<?= (int)$orden['id'] ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Completa el trabajo realizado en esta orden
            </p>
        </div>
        <a href="/proyecto/tecnico/mis_ordenes" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-clipboard-list text-success me-2"></i> Completar Trabajo
                    </h5>
                </div>
                <div class="card-body">
                    <form action="/proyecto/tecnico/cerrar/<?= (int)$orden['id'] ?>"
                          method="POST"
                          enctype="multipart/form-data"
                          id="formCerrar">
                        <?php /* ✅ FIX CRÍTICO: CSRF token */ ?>
                        <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">

                        <div class="mb-3">
                            <label for="descripcion_cierre" class="form-label fw-semibold">
                                Descripción detallada del trabajo <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="descripcion_cierre"
                                      name="descripcion_cierre"
                                      rows="5" required
                                      placeholder="Explica qué se hizo, qué se reparó, qué se reemplazó, etc."></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="evidencias" class="form-label fw-semibold">
                                Evidencias (Fotos del trabajo)
                            </label>
                            <input type="file" class="form-control" id="evidencias"
                                   name="evidencias[]" accept="image/*" multiple>
                            <div class="form-text text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Sube fotos del antes y después, o del trabajo en proceso
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tiempo_invertido" class="form-label fw-semibold">
                                        Tiempo invertido (horas) <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" class="form-control" id="tiempo_invertido"
                                           name="tiempo_invertido" step="0.5" min="0" max="720" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="repuestos_utilizados" class="form-label fw-semibold">
                                        Repuestos utilizados
                                    </label>
                                    <input type="text" class="form-control" id="repuestos_utilizados"
                                           name="repuestos_utilizados"
                                           placeholder="Ej: Filtro de aceite x1, Correa x1">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="satisfactorio"
                                   name="satisfactorio" checked>
                            <label class="form-check-label" for="satisfactorio">
                                El trabajo se realizó de manera satisfactoria
                            </label>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="/proyecto/tecnico/mis_ordenes" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-success" id="btnCerrar">
                                <i class="fas fa-check-circle me-1"></i> Cerrar Orden
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="mb-0 fw-semibold">
                        <i class="fas fa-info-circle text-primary me-2"></i> Información de la Orden
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Título</label>
                        <p class="fw-semibold mb-0">
                            <?= htmlspecialchars($orden['titulo'] ?? 'Sin título') ?>
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Descripción</label>
                        <p class="small mb-0">
                            <?= nl2br(htmlspecialchars(substr($orden['descripcion_mantenimiento'] ?? 'Sin descripción', 0, 200))) ?>
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small fw-semibold text-uppercase">Área</label>
                        <p class="fw-semibold mb-0">
                            <?= htmlspecialchars($orden['nombre_area'] ?? 'N/A') ?>
                        </p>
                    </div>
                    <div class="mb-0">
                        <label class="text-muted small fw-semibold text-uppercase">Prioridad</label>
                        <p class="mb-0">
                            <?php
                            $prioridad = $orden['prioridad'] ?? 'Media';
                            $color = match($prioridad) {
                                'Urgente' => 'danger',
                                'Alta'    => 'warning',
                                'Media'   => 'info',
                                'Baja'    => 'success',
                                default   => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $color ?> bg-opacity-10 text-<?= $color ?>">
                                <?= htmlspecialchars($prioridad) ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card { border-radius: 16px; box-shadow: 0 2px 12px var(--shadow-color); }
.form-control {
    border-radius: 10px; padding: 10px 14px;
    border: 2px solid var(--border-color);
    background: var(--bg-input);
    color: var(--text-primary);
    transition: all 0.3s ease;
}
.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formCerrar');
    if (form) {
        form.addEventListener('submit', function() {
            const btn = document.getElementById('btnCerrar');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Cerrando...';
        });
    }
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>