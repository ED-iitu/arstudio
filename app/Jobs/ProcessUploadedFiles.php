<?php

namespace App\Jobs;

use App\Models\ArGroup;
use App\Models\User;
use App\Services\FileUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Ar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessUploadedFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $title;
    protected array $filePath;
    protected int $arGroupId;
    protected int $userId;

    protected int $width;

    protected int $height;

    public function __construct($title, $filesPath, $arGroupId, $userId, $width, $height)
    {
        $this->title     = $title;
        $this->filePath  = $filesPath;
        $this->arGroupId = $arGroupId;
        $this->userId    = $userId;
        $this->width     = $width;
        $this->height    = $height;
    }

    public function handle()
    {
        $disk      = new FileUpload();
        $localData = [];
        $user = User::where('id', $this->userId)->first();

        foreach ($this->filePath as $key => $filePath) {
            if ($user->revival_count <= 0) {
                Log::info($user->id . " - у пользователя недостаточно кол-во оживлений пропускаем загрузку файлов" );
                continue;
            }

            Log::info($key);
            Log::info($filePath);
            $response = $disk->execute($filePath, $key, $this->title, $this->userId);

            Log::info("response", $response);

            $localData[$key] = $response['file_url'];
        }

        Log::info('Данные успешно загружены на диск', [
            'response' => $localData
        ]);

        Log::info('Прошло оживление для пользователя' . $this->userId, [
            'file_url' => $localData,
        ]);

        $user->revival_count = $user->revival_count - 1;
        $user->save();

        Ar::create([
            'group_id'       => $this->arGroupId,
            'user_id'        => $this->userId,
            'file_path'      => $localData['image'],
            'video_path'     => $localData['video'],
            'mind_file_path' => $localData['mind'] ?? null,
            'width'          => $this->width,
            'height'         => $this->height,
            'status'         => 1
        ]);
    }
}
