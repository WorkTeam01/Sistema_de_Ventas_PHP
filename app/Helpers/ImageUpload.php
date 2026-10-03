<?php

namespace App\Helpers;

class ImageUpload
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_BYTES = 2 * 1024 * 1024;

    /**
     * Valida una entrada de $_FILES. Devuelve el mensaje de error o null si es válida.
     * El código de error de PHP se mira primero: cuando la subida falla, tmp_name viene vacío.
     * El MIME se lee del contenido real, no del que declara el cliente.
     */
    public static function validate(array $file): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return 'No se pudo subir la imagen.';
        }

        $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));

        if (
            !in_array($extension, self::ALLOWED_EXTENSIONS, true)
            || (int)($file['size'] ?? 0) > self::MAX_BYTES
            || !in_array(mime_content_type((string)($file['tmp_name'] ?? '')), self::ALLOWED_MIMES, true)
        ) {
            return 'Imagen no válida. Solo se permiten JPG, PNG o WEBP de hasta 2MB.';
        }

        return null;
    }
}
