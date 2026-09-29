<?php
// helpers/ValidationHelper.php
// Validaciones genéricas

class ValidationHelper {

    public static function sanitize($input)
    {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
    }

    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function validateDate(string $date, string $format = 'Y-m-d'): bool
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    public static function validateNumber($value, $min = null, $max = null): bool
    {
        if (!is_numeric($value)) return false;
        if ($min !== null && $value < $min) return false;
        if ($max !== null && $value > $max) return false;
        return true;
    }

    public static function validateLength(string $input, int $min, int $max): bool
    {
        $length = mb_strlen(trim($input));
        return $length >= $min && $length <= $max;
    }

    public static function validateRequired(array $data, array $fields): array
    {
        $errors = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || ($data[$field] === '' && $data[$field] !== '0')) {
                $errors[$field] = "El campo '$field' es requerido";
            }
        }
        return $errors;
    }
}