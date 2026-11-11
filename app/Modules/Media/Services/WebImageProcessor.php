<?php

namespace App\Modules\Media\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Imagick;
use ImagickException;

class WebImageProcessor
{
    public function process(UploadedFile $file): ProcessedImage
    {
        $targetPath = tempnam(sys_get_temp_dir(), 'maracuja-web-image-');

        if ($targetPath === false) {
            throw ValidationException::withMessages(['file' => 'Impossible de préparer la conversion de cette image.']);
        }

        if (class_exists(Imagick::class)) {
            $this->processWithImagick($file, $targetPath);
        } else {
            $this->processWithGd($file, $targetPath);
        }

        return new ProcessedImage(new UploadedFile($targetPath, Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 220, '').'.webp', 'image/webp', null, true), $targetPath);
    }

    private function processWithImagick(UploadedFile $file, string $targetPath): void
    {
        try {
            $image = new Imagick($file->getRealPath());
            if (! in_array('WEBP', array_map('strtoupper', $image->queryFormats()), true)) {
                throw new ImagickException('WebP is not available.');
            }
            $image->autoOrient();
            $image->setIteratorIndex(0);
            $image->stripImage();
            $image->setImageFormat('webp');
            $image->setImageCompressionQuality((int) config('maracuja.media.image_webp_quality', 82));
            $image->setOption('webp:method', '6');
            $image->thumbnailImage((int) config('maracuja.media.image_max_width', 1600), 0, true, true);
            $image->writeImage($targetPath);
            $image->clear();
            $image->destroy();
        } catch (ImagickException) {
            @unlink($targetPath);
            throw ValidationException::withMessages(['file' => 'Cette image ne peut pas être convertie. Vérifiez que le fichier HEIC n’est pas endommagé puis réessayez.']);
        }
    }

    private function processWithGd(UploadedFile $file, string $targetPath): void
    {
        if (! function_exists('imagewebp')) {
            @unlink($targetPath);
            throw ValidationException::withMessages(['file' => 'Le serveur ne dispose pas du composant nécessaire pour convertir cette image. Contactez-nous avant de réessayer.']);
        }
        $info = @getimagesize($file->getRealPath());
        $source = match ($info[2] ?? null) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($file->getRealPath()),
            IMAGETYPE_PNG => imagecreatefrompng($file->getRealPath()),
            IMAGETYPE_WEBP => imagecreatefromwebp($file->getRealPath()),
            default => false,
        };
        if ($source === false) {
            @unlink($targetPath);
            throw ValidationException::withMessages(['file' => 'La conversion HEIC nécessite le composant ImageMagick sur le serveur. Contactez-nous avant de réessayer.']);
        }
        $width = imagesx($source);
        $height = imagesy($source);
        $targetWidth = min($width, (int) config('maracuja.media.image_max_width', 1600));
        $targetHeight = (int) round($height * $targetWidth / $width);
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagefill($target, 0, 0, imagecolorallocatealpha($target, 0, 0, 0, 127));
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        $written = imagewebp($target, $targetPath, (int) config('maracuja.media.image_webp_quality', 82));
        imagedestroy($source);
        imagedestroy($target);
        if (! $written) {
            @unlink($targetPath);
            throw ValidationException::withMessages(['file' => 'Cette image ne peut pas être convertie.']);
        }
    }
}
