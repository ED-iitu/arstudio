<?php
namespace App\Http\Controllers\API\V1;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessUpdateArFiles;
use App\Jobs\ProcessUploadedFiles;
use App\Models\Ar;
use App\Models\ArGroup;
use App\Models\ArInfoImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class ArController extends Controller
{
    public function create(Request $request)
    {
        $title = $request->title; // Получаем все данные из запроса

        // Получаем загруженные файлы из запроса
        $files  = $request->allFiles();
        $user   = Auth::user();
        $userId = $user->id;
        $source = $request->source;

        Log::info("source");
        Log::info($source);

        if ($user->revival_count <= 0) {
            // Возвращаем ответ клиенту без задержки
            return response()->json([
                'status'  => 'error',
                'message' => 'Недостаточно кол-во оживлений',
            ]);
        }

        $arGroup = ArGroup::where('name', $title)->where('user_id', $userId)->first();

        if (!$arGroup) {
            // Если записи нет, создаем с оригинальным названием
            $arGroup = ArGroup::create([
                'name'    => $title,
                'user_id' => $userId,
                'source'  => $source,
            ]);
        } else {
            // Ищем все записи с похожими названиями
            $similarGroups = ArGroup::where('name', 'like', $title . '%')
                ->where('user_id', $userId)
                ->pluck('name')
                ->toArray();

            // Определяем максимальный суффикс
            $maxSuffix = 0;
            foreach ($similarGroups as $groupName) {
                if (preg_match('/\((\d+)\)$/', $groupName, $matches)) {
                    $maxSuffix = max($maxSuffix, (int) $matches[1]);
                }
            }

            $newTitle = $title . '(' . ($maxSuffix + 1) . ')';

            $arGroup = ArGroup::create([
                'name'    => $newTitle,
                'user_id' => $userId,
                'source'  => $source,
            ]);
        }

        $filesArray = $files['data'];
        $mindFile   = $files['mind'] ?? null;

        foreach ($filesArray as $file) {
            $hash           = Str::random(40);
            $imageExtension = $file['image']->getClientOriginalExtension();
            $videoExtension = $file['video']->getClientOriginalExtension();

            if ($source == 'web') {
                $mindExtension  = $mindFile->getClientOriginalExtension();
            }

            $imagePath = $file['image']->storeAs(
                'uploads', $hash . '.' . $imageExtension
            );

            $image  = Image::make($file['image']);
            $height = $image->height();
            $width  = $image->width();

            if ($width > 2048 || $height > 2048) {
                Log::info("Картинка не подходит по размерам - уменьшаем" , [
                    'height' => $height,
                    'width'  => $width,
                ]);

                $maxSize = 2048;
                $k = $maxSize / max($width, $height);
                $k -= 0.03; // Уменьшение коэффициента

                $newWidth = intval($width * $k);
                $newHeight = intval($height * $k);

                $image->resize($newWidth, $newHeight, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                // Сохраняем измененное изображение во временный файл
                $tempPath = sys_get_temp_dir() . '/' . $hash . '.' . $imageExtension;
                $image->save($tempPath);

                // Обновляем путь к изображению для загрузки
                $newImage  = new \Illuminate\Http\File($tempPath);
                // Сохранение файла
                $imagePath = Storage::putFileAs(
                    'uploads', $newImage, $hash . '.' . $imageExtension
                );

                $width     = $newWidth;
                $height    = $newHeight;

                Log::info("Новый путь файла" , [
                    'path' => $imagePath,
                    'height' => $height,
                    'width'  => $width,
                ]);
            }

            $videoPath = $file['video']->storeAs(
                'uploads', $hash . '.' . $videoExtension
            );

            if ($source == 'web') {
                $mindPath = $mindFile->storeAs(
                    'uploads', $hash . '.' . $mindExtension
                );
            }

            if ($source == 'web') {
                $filesPath = [
                    'image' => $imagePath,
                    'video' => $videoPath,
                    'mind'  => $mindPath,
                ];
            } else {
                $filesPath = [
                    'image' => $imagePath,
                    'video' => $videoPath,
                ];
            }

            Log::info($file);

            // Диспетчируем задачу на обработку данных в очередь
            ProcessUploadedFiles::dispatch($title, $filesPath, $arGroup->id, $userId, $width, $height);
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

    public function update(Request $request)
    {
        $groupId = $request->get('groupId');
        // Валидация данных
        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'source'         => 'required|string|max:255',
            'data'           => 'array',
            'data.*.image'   => 'file|mimes:jpeg,png,jpg,gif',
            'data.*.video'   => 'file|mimes:mp4,avi,mov,wmv',
        ]);

        $title  = $validated['title'];
        $source = $validated['source'];
        $rowIds = $validated['data'];

        $rows = [];

        foreach ($rowIds as $key => $id) {
            $rows[$key] = $id;
        }

        if (empty($groupId)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Не переданы группа',
            ], 400);
        }

        $files         = $request->allFiles();
        $userId        = Auth::user()->id;
        $hash          = Str::random(40);

        Log::info($rowIds);
        Log::info("айдишки", $rows);
        Log::info("files" , $files);

        if (!empty($rows) && empty($files['data'])) {
            foreach ($rows as $row) {
                Ar::where('id', $row)->delete();
            }

            return response()->json([
                'status'  => 'ok',
                'message' => 'Данные удалены',
            ], 200);
        }

        if (!isset($files['data'])) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Не переданы данные',
            ], 400);
        }

        $filesArray    = $files['data'];
        $mindFile      = $files['mind'] ?? null;
        $mindExtension = $mindFile?->getClientOriginalExtension();
        $mindPath      = $mindFile?->storeAs(
            'uploads', $hash . '.' . $mindExtension
        );
        $imagePath  = '';
        $videoPath  = '';
        $rowId      = array_key_first($rowIds) ?? 0;
        $data       = [];

        foreach ($filesArray as $file) {
            $hash = Str::random(40);

            if (isset($file['image'])) {
                $imageExtension = $file['image']->getClientOriginalExtension();
                $imagePath      = $file['image']->storeAs(
                    'uploads', $hash . '.' . $imageExtension
                );
            }

            if (isset($file['video'])) {
                $videoExtension = $file['video']->getClientOriginalExtension();
                $videoPath      = $file['video']->storeAs(
                    'uploads', $hash . '.' . $videoExtension
                );
            }

            if (isset($file['id'])) {
                $rowId = $data['id'];
            }

            $data[] = [
                'id'        => $rowId,
                'imagePath' => $imagePath,
                'videoPath' => $videoPath,
                'mindPath'  => $mindPath,
            ];

        }

        // Диспетчируем задачу на обработку данных в очередь
        ProcessUpdateArFiles::dispatch($groupId, $userId, $data);

        // Возвращаем ответ клиенту без задержки
        return response()->json([
            'status'  => 'ok',
            'message' => 'Данные успешно загружены и поставлены в очередь на обработку',
            'data'    => [
                'arGroupId' => $groupId,
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
                'message' => 'Данные не найдены',
            ], 404);
        }

        return response()->json([
            'status' => 'ok',
            'group'  => $group,
            'data'   => $arList,
        ]);
    }

    public function getGallery(Request $request): \Illuminate\Http\JsonResponse
    {
        $arList  = ArGroup::where('user_id', Auth::user()->id)->with('ar')->get();

        if ($arList->count() == 0) {
            return response()->json([
                'status'  => 'error',
                'data' => [],
            ], 404);
        }

        return response()->json([
            'status'  => 'ok',
            'data' => $arList,
        ]);
    }

    public function getArByGroupId(Request $request)
    {
        $groupId = $request->get('groupId');
        $list    = Ar::where('group_id', $groupId)->get();

        if ($list->count() == 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Данные не найдены',
            ], 404);
        }

        return response()->json([
            'status' => 'ok',
            'data'   => $list,
        ]);
    }

    public function getInfoImages(Request $request): \Illuminate\Http\JsonResponse
    {
        $images = ArInfoImage::all();

        return response()->json([
            'status' => 'ok',
            'data'   => $images,
        ]);
    }
}
