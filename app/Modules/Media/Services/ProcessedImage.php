<?php

namespace App\Modules\Media\Services;

use Illuminate\Http\UploadedFile;

class ProcessedImage
{
    public function __construct(public readonly UploadedFile $file, private readonly ?string $temporaryPath = null) {}

    public function cleanup(): void
    {
        if ($this->temporaryPath !== null && is_file($this->temporaryPath)) {
            @unlink($this->temporaryPath);
        }
    }
}
