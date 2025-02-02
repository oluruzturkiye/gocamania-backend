<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Image;

class FileService
{
    public function uploadImage(UploadedFile $file, string $path, int $width = 800, int $height = null): string
    {
        // Benzersiz dosya adı oluştur
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $fullPath = $path . '/' . $fileName;

        // Resmi işle ve optimize et
        $image = Image::make($file)
            ->orientate() // EXIF verilerine göre otomatik döndürme
            ->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->encode($file->getClientOriginalExtension(), 80); // %80 kalite ile sıkıştırma

        // Storage'a kaydet
        Storage::put('public/' . $fullPath, $image->stream());

        return $fullPath;
    }

    public function deleteImage(string $path): bool
    {
        if (Storage::exists('public/' . $path)) {
            return Storage::delete('public/' . $path);
        }
        return false;
    }

    public function deleteMultipleImages(array $paths): void
    {
        foreach ($paths as $path) {
            $this->deleteImage($path);
        }
    }

    public function uploadMultipleImages(array $files, string $path, int $width = 800, int $height = null): array
    {
        $uploadedPaths = [];

        foreach ($files as $file) {
            $uploadedPaths[] = $this->uploadImage($file, $path, $width, $height);
        }

        return $uploadedPaths;
    }
}
