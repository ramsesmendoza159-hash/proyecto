<?php
// views/error/404.php
// Página de error 404 - VERSIÓN DEFINITIVA CORREGIDA
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página no encontrada</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* ========================================== */
        /* ESTILOS DE ERROR 404 */
        /* ========================================== */
        
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
            color: #6c757d;
            margin-bottom: 16px;
        }
        
        .error-number {
            font-size: 8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #0d6efd, #0dcaf0);
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
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="error-number">404</div>
        <h1 class="error-title">Página no encontrada</h1>
        <p class="error-text">
            Lo sentimos, la página que buscas no existe o ha sido movida.
        </p>
        <a href="/proyecto/dashboard" class="btn-primary">
            <i class="fas fa-home me-2"></i> Volver al Dashboard
        </a>
        <div class="error-footer">
            <i class="fas fa-info-circle me-1"></i> 
            Si crees que esto es un error, contacta al administrador.
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>