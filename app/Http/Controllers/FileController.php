<?php

namespace App\Http\Controllers;

use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FileController extends Controller
{
    protected $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048', // max 2MB
            'type' => 'required|in:store,product,listing',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Geçersiz dosya formatı veya boyutu',
                'errors' => $validator->errors()
            ], 422);
        }

        $type = $request->type;
        $path = "images/{$type}s"; // stores, products veya listings

        try {
            $imagePath = $this->fileService->uploadImage(
                $request->file('image'),
                $path,
                800 // varsayılan genişlik
            );

            return response()->json([
                'message' => 'Resim başarıyla yüklendi',
                'path' => $imagePath,
                'url' => asset('storage/' . $imagePath)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Resim yüklenirken bir hata oluştu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function uploadMultipleImages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'images' => 'required|array|max:5', // maksimum 5 resim
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
            'type' => 'required|in:store,product,listing',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Geçersiz dosya formatı veya boyutu',
                'errors' => $validator->errors()
            ], 422);
        }

        $type = $request->type;
        $path = "images/{$type}s";

        try {
            $imagePaths = $this->fileService->uploadMultipleImages(
                $request->file('images'),
                $path,
                800
            );

            $urls = array_map(function($path) {
                return asset('storage/' . $path);
            }, $imagePaths);

            return response()->json([
                'message' => 'Resimler başarıyla yüklendi',
                'paths' => $imagePaths,
                'urls' => $urls
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Resimler yüklenirken bir hata oluştu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Geçersiz dosya yolu',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $deleted = $this->fileService->deleteImage($request->path);

            if ($deleted) {
                return response()->json([
                    'message' => 'Resim başarıyla silindi'
                ]);
            }

            return response()->json([
                'message' => 'Resim bulunamadı'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Resim silinirken bir hata oluştu',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
