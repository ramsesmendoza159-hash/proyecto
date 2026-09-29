<?php
// views/ordenes/editar.php
// Editar orden de trabajo - VERSIÓN CORREGIDA

// Incluir helpers necesarios
require_once __DIR__ . '/../../helpers/SecurityHelper.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /proyecto/auth/login');
    exit;
}

// Verificar que la orden exista
if (!isset($orden) || !$orden) {
    $_SESSION['error'] = 'Orden no encontrada';
    header('Location: /proyecto/ordenes');
    exit;
}

// Verificar que la orden esté en estado PENDIENTE
if (($orden['status'] ?? '') !== 'PENDIENTE') {
    $_SESSION['error'] = 'Solo se pueden editar órdenes en estado PENDIENTE';
    header('Location: /proyecto/ordenes/ver/' . $orden['id']);
    exit;
}

$seccion = 'ordenes';
$titulo = 'Editar Orden de Trabajo - ' . htmlspecialchars($orden['num_om'] ?? '');

// Asegurar que las variables existan
$tecnicos = $tecnicos ?? [];
$supervisores = $supervisores ?? [];
$plantas = $plantas ?? [];
$areas = $areas ?? [];
$equipos = $equipos ?? [];
$componentes = $componentes ?? [];

include_once __DIR__ . '/../layouts/header.php';
// ✅ ELIMINADO: include_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-edit text-warning me-2"></i>
                Editar Orden: <?php echo htmlspecialchars($orden['num_om'] ?? 'N/A'); ?>
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Modifica los datos de la orden de trabajo
            </p>
        </div>
        <a href="/proyecto/ordenes/ver/<?php echo $orden['id']; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <!-- Mostrar errores -->
    <?php if (isset($_SESSION['errores']) && !empty($_SESSION['errores'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i>
            <ul class="mb-0">
                <?php foreach ($_SESSION['errores'] as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['errores']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Formulario -->
    <div class="card border-0">
        <div class="card-body">
            <form method="POST" action="/proyecto/ordenes/actualizar/<?php echo $orden['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::generateCSRFToken(); ?>">
                
                <div class="row g-4">
                    <!-- Título -->
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="titulo" class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="titulo" name="titulo" 
                                   placeholder="Título de la orden" required
                                   value="<?php echo htmlspecialchars($orden['titulo'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Planta, Área, Equipo, Componente -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="id_planta" class="form-label fw-semibold">Planta</label>
                            <select class="form-select" id="id_planta" name="id_planta">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($plantas as $planta): ?>
                                    <option value="<?php echo $planta['id_planta']; ?>" 
                                            <?php echo ($orden['id_planta'] ?? '') == $planta['id_planta'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($planta['nombre_planta']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="id_area" class="form-label fw-semibold">Área</label>
                            <select class="form-select" id="id_area" name="id_area">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($areas as $area): ?>
                                    <option value="<?php echo $area['id_area']; ?>" 
                                            <?php echo ($orden['id_area'] ?? '') == $area['id_area'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($area['nombre_area']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="id_equipo" class="form-label fw-semibold">Equipo</label>
                            <select class="form-select" id="id_equipo" name="id_equipo">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($equipos as $equipo): ?>
                                    <option value="<?php echo $equipo['id_equipo']; ?>" 
                                            <?php echo ($orden['id_equipo'] ?? '') == $equipo['id_equipo'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($equipo['nombre_equipo']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="id_componente" class="form-label fw-semibold">Componente</label>
                            <select class="form-select" id="id_componente" name="id_componente">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($componentes as $componente): ?>
                                    <option value="<?php echo $componente['id_componente']; ?>" 
                                            <?php echo ($orden['id_componente'] ?? '') == $componente['id_componente'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($componente['nombre_componente']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Técnico, Supervisor, Prioridad -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="tecnico_id" class="form-label fw-semibold">Técnico</label>
                            <select class="form-select" id="tecnico_id" name="tecnico_id">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($tecnicos as $tecnico): ?>
                                    <option value="<?php echo $tecnico['id']; ?>" 
                                            <?php echo ($orden['tecnico_id'] ?? '') == $tecnico['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tecnico['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="id_supervisor" class="form-label fw-semibold">Supervisor</label>
                            <select class="form-select" id="id_supervisor" name="id_supervisor">
                                <option value="">Seleccionar...</option>
                                <?php foreach ($supervisores as $supervisor): ?>
                                    <option value="<?php echo $supervisor['id']; ?>" 
                                            <?php echo ($orden['id_supervisor'] ?? '') == $supervisor['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($supervisor['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="prioridad" class="form-label fw-semibold">Prioridad <span class="text-danger">*</span></label>
                            <select class="form-select" id="prioridad" name="prioridad" required>
                                <option value="Baja" <?php echo ($orden['prioridad'] ?? '') === 'Baja' ? 'selected' : ''; ?>>Baja</option>
                                <option value="Media" <?php echo ($orden['prioridad'] ?? '') === 'Media' ? 'selected' : ''; ?>>Media</option>
                                <option value="Alta" <?php echo ($orden['prioridad'] ?? '') === 'Alta' ? 'selected' : ''; ?>>Alta</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tipo Mantenimiento, Tipo Actividad, Solicitante -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="tipo_mantenimiento" class="form-label fw-semibold">Tipo Mantenimiento</label>
                            <select class="form-select" id="tipo_mantenimiento" name="tipo_mantenimiento">
                                <option value="CORRECTIVO" <?php echo ($orden['tipo_mantenimiento'] ?? '') === 'CORRECTIVO' ? 'selected' : ''; ?>>Correctivo</option>
                                <option value="PREVENTIVO" <?php echo ($orden['tipo_mantenimiento'] ?? '') === 'PREVENTIVO' ? 'selected' : ''; ?>>Preventivo</option>
                                <option value="PREDICTIVO" <?php echo ($orden['tipo_mantenimiento'] ?? '') === 'PREDICTIVO' ? 'selected' : ''; ?>>Predictivo</option>
                                <option value="FABRICACION" <?php echo ($orden['tipo_mantenimiento'] ?? '') === 'FABRICACION' ? 'selected' : ''; ?>>Fabricación</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="tipo_actividad" class="form-label fw-semibold">Tipo Actividad</label>
                            <select class="form-select" id="tipo_actividad" name="tipo_actividad">
                                <option value="">Seleccionar...</option>
                                <option value="MECANICA" <?php echo ($orden['tipo_actividad'] ?? '') === 'MECANICA' ? 'selected' : ''; ?>>Mecánica</option>
                                <option value="ELECTRICA" <?php echo ($orden['tipo_actividad'] ?? '') === 'ELECTRICA' ? 'selected' : ''; ?>>Eléctrica</option>
                                <option value="REFRIGERACION" <?php echo ($orden['tipo_actividad'] ?? '') === 'REFRIGERACION' ? 'selected' : ''; ?>>Refrigeración</option>
                                <option value="GENERAL" <?php echo ($orden['tipo_actividad'] ?? '') === 'GENERAL' ? 'selected' : ''; ?>>General</option>
                                <option value="METALMECANICA" <?php echo ($orden['tipo_actividad'] ?? '') === 'METALMECANICA' ? 'selected' : ''; ?>>Metalmecánica</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="solicitante" class="form-label fw-semibold">Solicitante</label>
                            <input type="text" class="form-control" id="solicitante" name="solicitante" 
                                   placeholder="Nombre del solicitante"
                                   value="<?php echo htmlspecialchars($orden['solicitante'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Fechas -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="fecha_inicio" class="form-label fw-semibold">Fecha Inicio</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                                   value="<?php echo htmlspecialchars($orden['fecha_inicio'] ?? date('Y-m-d')); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="fecha_estimada" class="form-label fw-semibold">Fecha Estimada</label>
                            <input type="date" class="form-control" id="fecha_estimada" name="fecha_estimada" 
                                   value="<?php echo htmlspecialchars($orden['fecha_estimada'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="horas_duracion" class="form-label fw-semibold">Horas Duración</label>
                            <input type="number" step="0.5" class="form-control" id="horas_duracion" name="horas_duracion" 
                                   placeholder="0" min="0" max="24"
                                   value="<?php echo htmlspecialchars($orden['horas_duracion'] ?? 0); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="supervisor_solicitante" class="form-label fw-semibold">Supervisor Solicitante</label>
                            <input type="text" class="form-control" id="supervisor_solicitante" name="supervisor_solicitante" 
                                   placeholder="Nombre del supervisor que solicita"
                                   value="<?php echo htmlspecialchars($orden['supervisor_solicitante'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="descripcion_mantenimiento" class="form-label fw-semibold">Descripción del Mantenimiento <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="descripcion_mantenimiento" name="descripcion_mantenimiento" 
                                      rows="5" placeholder="Describir el mantenimiento a realizar..." required><?php echo htmlspecialchars($orden['descripcion_mantenimiento'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="observaciones_tecnico" class="form-label fw-semibold">Observaciones del Técnico</label>
                            <textarea class="form-control" id="observaciones_tecnico" name="observaciones_tecnico" 
                                      rows="3" placeholder="Observaciones del técnico..."><?php echo htmlspecialchars($orden['observaciones_tecnico'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="observaciones_cierre" class="form-label fw-semibold">Observaciones de Cierre</label>
                            <textarea class="form-control" id="observaciones_cierre" name="observaciones_cierre" 
                                      rows="3" placeholder="Observaciones al cerrar la orden..."><?php echo htmlspecialchars($orden['observaciones_cierre'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Costos y estado -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="tarifa_tecnico" class="form-label fw-semibold">Tarifa Técnico (S/)</label>
                            <input type="number" step="0.01" class="form-control" id="tarifa_tecnico" name="tarifa_tecnico" 
                                   placeholder="0.00" min="0"
                                   value="<?php echo htmlspecialchars($orden['tarifa_tecnico'] ?? 0); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="horas_trabajadas" class="form-label fw-semibold">Horas Trabajadas</label>
                            <input type="number" step="0.5" class="form-control" id="horas_trabajadas" name="horas_trabajadas" 
                                   placeholder="0" min="0" max="24"
                                   value="<?php echo htmlspecialchars($orden['horas_trabajadas'] ?? 0); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="costo_repuestos" class="form-label fw-semibold">Costo Repuestos (S/)</label>
                            <input type="number" step="0.01" class="form-control" id="costo_repuestos" name="costo_repuestos" 
                                   placeholder="0.00" min="0"
                                   value="<?php echo htmlspecialchars($orden['costo_repuestos'] ?? 0); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status" class="form-label fw-semibold">Estado</label>
                            <select class="form-select" id="status" name="status">
                                <option value="PENDIENTE" <?php echo ($orden['status'] ?? '') === 'PENDIENTE' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="EN_PROCESO" <?php echo ($orden['status'] ?? '') === 'EN_PROCESO' ? 'selected' : ''; ?>>En Proceso</option>
                                <option value="EJECUTADA" <?php echo ($orden['status'] ?? '') === 'EJECUTADA' ? 'selected' : ''; ?>>Ejecutada</option>
                            </select>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="d-flex gap-2">
                    <a href="/proyecto/ordenes/ver/<?php echo $orden['id']; ?>" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Actualizar Orden
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- Estilos -->
<style>
.form-group {
    margin-bottom: 0;
}
.form-label {
    font-size: 0.85rem;
    margin-bottom: 0.4rem;
}
.form-control, .form-select {
    border-radius: 10px;
    padding: 10px 14px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}
.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
}
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
</style>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>