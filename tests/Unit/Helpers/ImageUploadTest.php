<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ImageUpload;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ImageUploadTest extends TestCase
{
    private const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    /** @var string[] */
    private array $tmpFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $path) {
            @unlink($path);
        }
    }

    private function tmpFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'imgtest');
        file_put_contents($path, $contents);
        $this->tmpFiles[] = $path;

        return $path;
    }

    private function upload(string $name, string $contents, ?int $size = null, int $error = UPLOAD_ERR_OK): array
    {
        $path = $this->tmpFile($contents);

        return [
            'name'     => $name,
            'tmp_name' => $path,
            'size'     => $size ?? strlen($contents),
            'error'    => $error,
        ];
    }

    public function testAcceptsValidPng(): void
    {
        $this->assertNull(ImageUpload::validate($this->upload('foto.png', base64_decode(self::PNG_1X1))));
    }

    public function testExtensionIsCaseInsensitive(): void
    {
        $this->assertNull(ImageUpload::validate($this->upload('FOTO.PNG', base64_decode(self::PNG_1X1))));
    }

    public static function uploadErrors(): array
    {
        return [
            'excede upload_max_filesize' => [UPLOAD_ERR_INI_SIZE],
            'excede MAX_FILE_SIZE'       => [UPLOAD_ERR_FORM_SIZE],
            'parcial'                    => [UPLOAD_ERR_PARTIAL],
            'sin archivo'                => [UPLOAD_ERR_NO_FILE],
            'sin directorio temporal'    => [UPLOAD_ERR_NO_TMP_DIR],
            'no se pudo escribir'        => [UPLOAD_ERR_CANT_WRITE],
        ];
    }

    #[DataProvider('uploadErrors')]
    public function testRejectsUploadErrorCodeBeforeLookingAtTheFile(int $code): void
    {
        // En un fallo de subida tmp_name viene vacío: no debe tocarse el archivo
        $file = ['name' => 'foto.png', 'tmp_name' => '', 'size' => 0, 'error' => $code];

        $this->assertSame('No se pudo subir la imagen.', ImageUpload::validate($file));
    }

    public function testRejectsDisallowedExtension(): void
    {
        $this->assertNotNull(ImageUpload::validate($this->upload('foto.gif', base64_decode(self::PNG_1X1))));
        $this->assertNotNull(ImageUpload::validate($this->upload('shell.php', base64_decode(self::PNG_1X1))));
        $this->assertNotNull(ImageUpload::validate($this->upload('sin_extension', base64_decode(self::PNG_1X1))));
    }

    public function testRejectsDoubleExtensionThatEndsInPhp(): void
    {
        $this->assertNotNull(ImageUpload::validate($this->upload('foto.png.php', base64_decode(self::PNG_1X1))));
    }

    public function testRejectsFakeMimeBehindImageExtension(): void
    {
        $result = ImageUpload::validate($this->upload('foto.png', '<?php echo "x";'));

        $this->assertSame('Imagen no válida. Solo se permiten JPG, PNG o WEBP de hasta 2MB.', $result);
    }

    public function testRejectsRealMimeOutsideTheAllowedList(): void
    {
        // GIF real con extensión .jpg
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        $this->assertNotNull(ImageUpload::validate($this->upload('foto.jpg', $gif)));
    }

    public function testSizeLimitIsTwoMegabytesInclusive(): void
    {
        $png = base64_decode(self::PNG_1X1);

        $this->assertNull(ImageUpload::validate($this->upload('foto.png', $png, 2 * 1024 * 1024)));
        $this->assertNotNull(ImageUpload::validate($this->upload('foto.png', $png, 2 * 1024 * 1024 + 1)));
    }
}
