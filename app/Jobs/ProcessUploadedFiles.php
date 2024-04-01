<?php

namespace App\Jobs;

use App\Models\ArGroup;
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

    public function __construct($title, $filesPath, $arGroupId, $userId)
    {
        $this->title     = $title;
        $this->filePath  = $filesPath;
        $this->arGroupId = $arGroupId;
        $this->userId    = $userId;
    }

    public function handle()
    {
        $disk      = new FileUpload();
        $localData = [];

        foreach ($this->filePath as $key => $filePath) {
            Log::info($key);
            Log::info($filePath);
            $response = $disk->execute($filePath, $key, $this->title, $this->userId);

            Log::info("response", $response);

            $localData[$key] = $response['file_url'];
        }

        Log::info('Данные успешно загружены на яндекс диск', [
            'response' => $localData
        ]);

        Ar::create([
            'group_id'       => $this->arGroupId,
            'user_id'        => $this->userId,
            'file_path'      => $localData['image'],
            'video_path'     => $localData['video'],
            'mind_file_path' => $localData['mind'],
            'status'         => 1
        ]);
    }
}
