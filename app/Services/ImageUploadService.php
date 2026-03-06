<?php
// app/Services/ImageUploadService.php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Log;

class ImageUploadService
{
    private array $allowedMimeTypes = [
        'image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'
    ];

    private int $maxSize = 2048; // KB

    public function upload(UploadedFile $file, string $path = 'products'): array
    {
        try {
            // Log upload attempt
            Log::info('Starting file upload', [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType()
            ]);

            // Validate file
            $this->validateFile($file);

            // Generate unique filename
            $filename = $this->generateFilename($file);
            
            // Create destination directory path
            $destinationDir = public_path('uploads/' . $path);
            
            // Create directory if it doesn't exist
            if (!file_exists($destinationDir)) {
                mkdir($destinationDir, 0755, true);
                Log::info('Created directory', ['path' => $destinationDir]);
            }

            // Move the file to destination
            $file->move($destinationDir, $filename);

            // Construct the full path for database
            $fullPath = 'uploads/' . $path . '/' . $filename;
            
            // Get the file size after upload
            $uploadedFilePath = $destinationDir . '/' . $filename;
            $fileSize = file_exists($uploadedFilePath) ? filesize($uploadedFilePath) : 0;

            Log::info('File uploaded successfully', [
                'filename' => $filename,
                'path' => $fullPath,
                'size' => $fileSize
            ]);

            return [
                'path' => $fullPath,
                'filename' => $filename,
                'mime_type' => $file->getClientMimeType(),
                'size' => $fileSize,
            ];

        } catch (\Exception $e) {
            Log::error('Upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function uploadMultiple(array $files, string $path = 'products'): array
    {
        $uploaded = [];
        $errors = [];
        
        foreach ($files as $index => $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                try {
                    $uploaded[] = $this->upload($file, $path);
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'file' => $file->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ];
                }
            } else {
                $errors[] = [
                    'index' => $index,
                    'error' => 'Invalid file or upload failed'
                ];
            }
        }
        
        // If there are errors but some files uploaded successfully, log them
        if (!empty($errors)) {
            Log::warning('Some files failed to upload', ['errors' => $errors]);
        }
        
        return $uploaded;
    }

    private function validateFile(UploadedFile $file): void
    {
        // Check if file is valid
        if (!$file->isValid()) {
            throw new \InvalidArgumentException(
                'File upload failed: ' . $file->getErrorMessage()
            );
        }

        // Check file type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            throw new \InvalidArgumentException(
                'Invalid file type. Allowed: ' . implode(', ', $this->allowedMimeTypes)
            );
        }

        // Check file size
        if ($file->getSize() > $this->maxSize * 1024) {
            throw new \InvalidArgumentException(
                "File size must not exceed {$this->maxSize}KB"
            );
        }
    }

    private function generateFilename(UploadedFile $file): string
    {
        return Uuid::uuid4()->toString() . '.' . $file->getClientOriginalExtension();
    }
}