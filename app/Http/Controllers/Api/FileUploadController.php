<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadController extends Controller
{
    /**
     * Upload an image file
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max
            'folder' => 'nullable|string|max:255', // Optional folder name
        ]);

        if (!$request->hasFile('image')) {
            return response()->json([
                'success' => false,
                'message' => 'No image file provided',
            ], 400);
        }

        $file = $request->file('image');
        $folder = $request->input('folder', 'uploads');
        
        // Generate unique filename
        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        
        // Store file in public storage
        $path = $file->storeAs($folder, $filename, 'public');

        // Get the full URL
        $url = asset('storage/' . $path);

        return response()->json([
            'success' => true,
            'message' => 'Image uploaded successfully',
            'data' => [
                'url' => $url,
                'path' => $path,
                'filename' => $filename,
            ],
        ]);
    }

    /**
     * Delete an uploaded image
     */
    public function deleteImage(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');

        // Remove /storage/ prefix if present
        $path = str_replace('/storage/', '', $path);
        $path = str_replace('storage/', '', $path);

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            
            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Image not found',
        ], 404);
    }
}
