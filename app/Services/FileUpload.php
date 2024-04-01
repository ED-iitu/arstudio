<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FileUpload
{
    public function execute($file, string $type, string $directory, int $userId)
    {
        // Получаем путь к загружаемому файлу
        $filePath = Storage::path($file);
        $name     =  pathinfo($filePath, PATHINFO_BASENAME);
        // Генерируем уникальное имя файла
        $fileName = uniqid() . '_' . $name;
        $directory = $userId . '/' . $directory . '/' . $fileName;

        Storage::disk('ps')->put($directory, file_get_contents($filePath));

        $url = Storage::disk('ps')->url($directory);

        Log::info($url);

        return [
            'file_url' => $url
        ];
    }
}
