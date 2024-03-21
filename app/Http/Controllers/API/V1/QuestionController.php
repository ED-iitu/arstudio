<?php
namespace App\Http\Controllers\API\V1;
use App\Http\Controllers\Controller;
use App\Models\Question;

class QuestionController extends Controller
{
    public function getAll(): \Illuminate\Http\JsonResponse
    {
        $questions = Question::all();

        if ($questions->count() === 0) {
            return response()->json([
                'status'  => 'error',
                'message' => "Данные не найдены"
            ], 404);
        }

        return response()->json([
            'status' => 'ok',
            'data'   => $questions
        ]);
    }
}
