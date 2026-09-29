<!-- views/auth/login.php -->
<!-- Login - VERSIÓN CORREGIDA (SIN credenciales de prueba) -->
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../helpers/SecurityHelper.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Sistema de Mantenimiento</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            padding: 20px;
        }
        .login-box {
            background: #fff;
            border-radius: 24px;
            padding: 48px 40px;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 30px 80px rgba(0,0,0,0.3);
        }
        .login-box .login-logo {
            text-align: center;
            margin-bottom: 32px;
        }
        .login-box .login-logo .icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #fff;
            margin-bottom: 16px;
        }
        .login-box .login-logo h2 {
            font-weight: 800;
            color: #1a1a2e;
        }
        .login-box .login-logo p {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .login-box .form-control {
            padding: 12px 16px;
            border-radius: 12px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .login-box .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
        }
        .login-box .btn-primary {
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
            border: none;
        }
        .login-box .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(13, 110, 253, 0.3);
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-box">
            <div class="login-logo">
                <div class="icon"><i class="fas fa-tools"></i></div>
                <h2>Sistema de Mantenimiento</h2>
                <p>Gestión de órdenes de trabajo</p>
            </div>
            
            <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
                <?php $login_error = $_SESSION['error']; unset($_SESSION['error']); ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> 
                    <?= htmlspecialchars($login_error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form action="/proyecto/auth/authenticate" method="POST">
                <input type="hidden" name="csrf_token" value="<?= SecurityHelper::generateCSRFToken() ?>">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-envelope text-muted"></i></span>
                        <input type="email" name="email" class="form-control border-start-0" 
                               placeholder="correo@ejemplo.com" required autofocus
                               autocomplete="username"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-lock text-muted"></i></span>
                        <input type="password" name="password" class="form-control border-start-0" 
                               placeholder="Ingresa tu contraseña" required
                               autocomplete="current-password">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 py-2">
                    <i class="fas fa-sign-in-alt me-2"></i> Iniciar Sesión
                </button>
            </form>
            
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
