<?php

namespace App\Http\Controllers;

use App\Models\TrainingQuiz;
use Illuminate\Http\Request;

class TrainingQuizController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'training_id' => 'required|exists:trainings,id',
            'question' => 'required|string',
            'type' => 'required|in:mcq,true_false',
            'options' => 'nullable|array',
            'correct_answer' => 'required|string',
        ]);

        $quiz = new TrainingQuiz();
        $quiz->training_id = $request->training_id;
        $quiz->question = $request->question;
        $quiz->type = $request->type;
        $quiz->options = is_array($request->options) ? json_encode($request->options) : null;
        $quiz->correct_answer = $request->correct_answer;
        $quiz->save();

        return back()->with('message', 'Quiz question added successfully.');
    }

    public function destroy($id)
    {
        $quiz = TrainingQuiz::findOrFail($id);
        $quiz->delete();

        return back()->with('message', 'Quiz question deleted successfully.');
    }
}
