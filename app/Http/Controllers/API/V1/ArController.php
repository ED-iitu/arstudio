<?php
namespace App\Http\Controllers\API\V1;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessUploadedFiles;
use App\Models\Ar;
use App\Models\ArGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ArController extends Controller
{
    public function create(Request $request)
    {
        $title = $request->title; // Получаем все данные из запроса

        // Получаем загруженные файлы из запроса
        $files = $request->allFiles();
        $userId = Auth::user()->id;

        $arGroup = ArGroup::where('name', $title)->where('user_id', $userId)->first();

        if (!$arGroup) {
            $arGroup = ArGroup::create([
                'name'    => $title,
                'user_id' => $userId,
            ]);
        }

        $filesArray = $files['data'];
        $mindFile   = $files['mind'];

        foreach ($filesArray as $file) {
            $hash           = Str::random(40);
            $imageExtension = $file['image']->getClientOriginalExtension();
            $videoExtension = $file['video']->getClientOriginalExtension();
            $mindExtension  = $mindFile->getClientOriginalExtension();

            $imagePath = $file['image']->storeAs(
                'uploads', $hash . '.' . $imageExtension
            );

            $videoPath = $file['video']->storeAs(
                'uploads', $hash . '.' . $videoExtension
            );

            $mindPath = $mindFile->storeAs(
                'uploads', $hash . '.' . $mindExtension
            );

            $filesPath = [
                'image' => $imagePath,
                'video' => $videoPath,
                'mind'  => $mindPath,
            ];

            Log::info($file);

            // Диспетчируем задачу на обработку данных в очередь
            ProcessUploadedFiles::dispatch($title, $filesPath, $arGroup->id, $userId);
        }

        // Возвращаем ответ клиенту без задержки
        return response()->json([
            'status'  => 'ok',
            'message' => 'Данные успешно загружены и поставлены в очередь на обработку',
            'data'    => [
                'arGroupId' => $arGroup->id,
            ],
        ]);
    }

    public function getByGroupId(Request $request): \Illuminate\Http\JsonResponse
    {
        $groupId = $request->get('groupId');
        $arList  = Ar::where('group_id', $groupId)->get();
        $group   = ArGroup::where('id', $groupId)->first();

        if ($arList->count() == 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Данные не найдены'
            ], 404);
        }

        return response()->json([
            'status' => 'ok',
            'group'  => $group,
            'data'   => $arList
        ]);
    }

    public function getGallery(Request $request): \Illuminate\Http\JsonResponse
    {
        $arList  = ArGroup::where('user_id', Auth::user()->id)->with('ar')->get();

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

    public function getArByGroupId(Request $request)
    {
        $groupId = $request->get('groupId');
        $list    = Ar::where('group_id', $groupId)->get();

        if ($list->count() == 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Данные не найдены'
            ], 404);
        }

        return response()->json([
            'status' => 'ok',
            'data'   => $list
        ]);
    }
}
