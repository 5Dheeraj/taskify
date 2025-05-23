<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\TrainingSlide;
use Illuminate\Http\Request;

class TrainingSlideController extends Controller
{
    public function show($id)
    {
        $training = Training::findOrFail($id);
        $slides = TrainingSlide::where('training_id', $id)->orderBy('order')->get();

        return view('training.slides.show', compact('training', 'slides'));
    }
}