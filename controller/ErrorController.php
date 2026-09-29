<?php
// controller/ErrorController.php
// Controlador de errores

class ErrorController {
    
    public function error404() {
        http_response_code(404);
        $titulo = 'Página no encontrada';
        $seccion = 'error';
        require_once __DIR__ . '/../views/error/404.php';
    }
    
    public function error500() {
        http_response_code(500);
        $titulo = 'Error del servidor';
        $seccion = 'error';
        require_once __DIR__ . '/../views/error/500.php';
    }
}
?>
