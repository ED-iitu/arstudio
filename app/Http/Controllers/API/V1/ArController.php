<?php
namespace App\Http\Controllers\API\V1;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessUploadedFiles;
use App\Models\Ar;
use App\Models\ArGroup;
use App\Services\YandexUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ArController extends Controller
{
    public function create(Request $request)
    {
        $title = $request->title; // Получаем все данные из запроса

        // Получаем загруженные файлы из запроса
        $files = $request->allFiles();

        $arGroup = ArGroup::where('name', $title)->first();

        if (!$arGroup) {
            $arGroup = ArGroup::create([
                'name' => $title
            ]);
        }

        $filesPath = [];

        foreach ($files['data'] as $file) {
            $imagePath = $file['image']->store('uploads');
            $videoPath = $file['video']->store('uploads');
            $mindPath  = $file['mind']->store('uploads');

            $filesPath = [
                'image' => $imagePath,
                'video' => $videoPath,
                'mind'  => $mindPath
            ];

            // Диспетчируем задачу на обработку данных в очередь
            ProcessUploadedFiles::dispatch($title, $filesPath, $arGroup->id, Auth::user()->id);
        }

        // Возвращаем ответ клиенту без задержки
        return response()->json([
            'status'  => 'ok',
            'message' => 'Данные успешно загружены и поставлены в очередь на обработку'
        ]);
    }

    public function getByGroupId(Request $request): \Illuminate\Http\JsonResponse
    {
        $groupId = $request->get('groupId');

        $arList = Ar::where('group_id', $groupId)->get();

        if ($arList->count() == 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Данные не найдены'
            ], 404);
        }

        return response()->json([
            'status'  => 'ok',
            'data' => $arList
        ]);
    }
}
