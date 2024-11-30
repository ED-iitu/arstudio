<?php
namespace App\Jobs;
use App\Models\Ar;
use App\Models\ArGroup;
use App\Services\FileUpload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessUpdateArFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $groupId;
    protected array $data;
    protected int $userId;

    public function __construct(int $groupId, int $userId, array $data)
    {
        $this->groupId = $groupId;
        $this->userId  = $userId;
        $this->data    = $data;
    }

    public function handle()
    {
        Log::info("Начинаем обноалять данные АР");
        $group = ArGroup::where('id', $this->groupId)->first();
        $disk  = new FileUpload();

        foreach ($this->data as $key => $data) {
            Log::info($data);

            if ($data['id'] === 0) {
                $this->createNewRecord($disk, $data, $group);

                continue;
            }

            $arObject = Ar::where('id', $data['id'])->first();

            if (!empty($data['imagePath'])) {
                $response = $disk->execute($data['imagePath'], 'image', $group->name, $this->userId);

                $arObject->file_path = $response['file_url'];
                $arObject->save();
            }

            if (!empty($data['videoPath'])) {
                $response = $disk->execute($data['videoPath'], 'video', $group->name, $this->userId);

                $arObject->video_path = $response['file_url'];
                $arObject->save();
            }

            if (!empty($data['mindPath'])) {
                $response = $disk->execute($data['mindPath'], 'mind', $group->name, $this->userId);

                $arObject->mind_file_path = $response['file_url'];
                $arObject->save();
            }
        }
    }

    protected function createNewRecord($disk, $ArData, $group)
    {
        $data = [];

        if (!empty($ArData['imagePath'])) {
            $response = $disk->execute($ArData['imagePath'], 'image', $group->name, $this->userId);

            $data['image'] = $response['file_url'];
        }

        if (!empty($ArData['videoPath'])) {
            $response = $disk->execute($ArData['videoPath'], 'video', $group->name, $this->userId);

            $data['video'] = $response['file_url'];
        }

        if (!empty($ArData['mindPath'])) {
            $response = $disk->execute($ArData['mindPath'], 'mind', $group->name, $this->userId);

            $data['mind'] = $response['file_url'];
        }

        Ar::create([
            'group_id'       => $group->id,
            'user_id'        => $this->userId,
            'file_path'      => $data['image'],
            'video_path'     => $data['video'] ?? '',
            'mind_file_path' => $data['mind'] ?? null,
            'width'          => 800,
            'height'         => 400,
            'status'         => 1
        ]);
    }
}
