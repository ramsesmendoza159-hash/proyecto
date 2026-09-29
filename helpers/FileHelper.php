<?php
// helpers/FileHelper.php
// Ubicación: C:\xampp\htdocs\proyecto\helpers\FileHelper.php

class FileHelper
{
    /**
     * Subir un archivo
     */
    public static function upload(array $file, string $destination, array $allowedTypes = [], int $maxSize = 5242880): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'filename' => '',
            'path' => ''
        ];

        // Verificar que no haya errores
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $result['message'] = 'Error al subir el archivo: ' . $file['error'];
            return $result;
        }

        // Verificar tamaño
        if ($file['size'] > $maxSize) {
            $result['message'] = 'El archivo excede el tamaño máximo permitido (' . self::formatSize($maxSize) . ')';
            return $result;
        }

        // Verificar tipo de archivo
        $fileInfo = pathinfo($file['name']);
        $extension = strtolower($fileInfo['extension'] ?? '');

        if (!empty($allowedTypes) && !in_array($extension, $allowedTypes)) {
            $result['message'] = 'Tipo de archivo no permitido. Permitidos: ' . implode(', ', $allowedTypes);
            return $result;
        }

        // Generar nombre único
        $filename = uniqid() . '.' . $extension;
        $fullPath = rtrim($destination, '/') . '/' . $filename;

        // Crear directorio si no existe
        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
        }

        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            $result['success'] = true;
            $result['message'] = 'Archivo subido correctamente';
            $result['filename'] = $filename;
            $result['path'] = $fullPath;
        } else {
            $result['message'] = 'Error al mover el archivo';
        }

        return $result;
    }

    /**
     * Eliminar un archivo
     */
    public static function delete(string $filepath): bool
    {
        if (file_exists($filepath) && is_file($filepath)) {
            return unlink($filepath);
        }
        return false;
    }

    /**
     * Obtener información de un archivo
     */
    public static function getInfo(string $filepath): array
    {
        if (!file_exists($filepath) || !is_file($filepath)) {
            return [];
        }

        $info = pathinfo($filepath);
        return [
            'name' => $info['basename'] ?? '',
            'filename' => $info['filename'] ?? '',
            'extension' => $info['extension'] ?? '',
            'dirname' => $info['dirname'] ?? '',
            'size' => filesize($filepath),
            'size_formatted' => self::formatSize(filesize($filepath)),
            'mime' => mime_content_type($filepath),
            'modified' => date('Y-m-d H:i:s', filemtime($filepath))
        ];
    }

    /**
     * Formatear tamaño de archivo
     */
    public static function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Validar que un archivo sea una imagen
     */
    public static function isImage(string $filepath): bool
    {
        if (!file_exists($filepath)) {
            return false;
        }
        $mime = mime_content_type($filepath);
        return strpos($mime, 'image/') === 0;
    }

    /**
     * Redimensionar imagen (requiere GD)
     */
    public static function resizeImage(string $source, string $destination, int $width, int $height): bool
    {
        if (!function_exists('imagecreatefromjpeg')) {
            return false;
        }

        $info = getimagesize($source);
        if (!$info) {
            return false;
        }

        list($srcWidth, $srcHeight, $type) = $info;

        switch ($type) {
            case IMAGETYPE_JPEG:
                $srcImage = imagecreatefromjpeg($source);
                break;
            case IMAGETYPE_PNG:
                $srcImage = imagecreatefrompng($source);
                break;
            case IMAGETYPE_GIF:
                $srcImage = imagecreatefromgif($source);
                break;
            default:
                return false;
        }

        $dstImage = imagecreatetruecolor($width, $height);
        imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight);

        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($dstImage, $destination, 90);
                break;
            case IMAGETYPE_PNG:
                imagepng($dstImage, $destination, 9);
                break;
            case IMAGETYPE_GIF:
                imagegif($dstImage, $destination);
                break;
            default:
                return false;
        }

        imagedestroy($srcImage);
        imagedestroy($dstImage);

        return true;
    }

    /**
     * Obtener extensión permitida para imágenes
     */
    public static function getAllowedImageExtensions(): array
    {
        return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    }

    /**
     * Obtener extensión permitida para documentos
     */
    public static function getAllowedDocumentExtensions(): array
    {
        return ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv'];
    }
}