<?php
// views/error/500.php
// Página de error 500 - VERSIÓN CORREGIDA
// ✅ FIX: Bootstrap 5.3.3 (consistente con el proyecto)
// ✅ FIX: meta noindex para evitar indexación
// ✅ FIX: type="button" en el botón de reintentar
// ✅ FIX: link dinámico según sesión (dashboard o login)
// ✅ FIX: fallback inline si Bootstrap no carga

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Si hay sesión activa, ofrecer volver al dashboard; si no, al login
$esta_logueado = isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
$url_volver = $esta_logueado ? '/proyecto/dashboard' : '/proyecto/login';
$texto_volver = $esta_logueado ? 'Volver al Dashboard' : 'Ir al Login';
$icono_volver = $esta_logueado ? 'fa-home' : 'fa-sign-in-alt';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>500 - Error del servidor | PROYECTO</title>

    <!-- Bootstrap 5.3.3 (misma versión que el resto del sistema) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-container {
            background: #ffffff;
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }

        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 16px;
        }

        .error-number {
            font-size: 8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #dc3545, #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
        }

        .error-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-top: 16px;
            margin-bottom: 8px;
            color: #1a1a2e;
        }

        .error-text {
            color: #6c757d;
            margin-bottom: 24px;
        }

        .btn-primary {
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            border: none;
            color: #fff;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(13, 110, 253, 0.3);
            color: #fff;
        }

        .btn-secondary {
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            border: 2px solid #e9ecef;
            background: #fff;
            color: #6c757d;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
            color: #495057;
        }

        .error-footer {
            margin-top: 24px;
            color: #6c757d;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <div class="error-number">500</div>
        <h1 class="error-title">Error del servidor</h1>
        <p class="error-text">
            Ha ocurrido un error interno. Por favor, intenta más tarde.
        </p>
        <div class="d-flex flex-column gap-2">
            <a href="<?= htmlspecialchars($url_volver) ?>" class="btn-primary">
                <i class="fas <?= htmlspecialchars($icono_volver) ?> me-2"></i> <?= htmlspecialchars($texto_volver) ?>
            </a>
            <button type="button" onclick="location.reload()" class="btn-secondary">
                <i class="fas fa-sync-alt me-2"></i> Reintentar
            </button>
        </div>
        <div class="error-footer">
            <i class="fas fa-info-circle me-1"></i>
            Si el problema persiste, contacta al administrador.
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>