<?php
namespace App\Http\Controllers\API\V1;
use App\Http\Controllers\Controller;
use App\Models\UserQuestion;
use Illuminate\Http\Request;

class UserQuestionController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->all();

        UserQuestion::create($data);

        return response()->json(
            [
                'status'  => 'ok',
                'message' => 'Запрос отправлен'
            ], 200
        );
    }
}
