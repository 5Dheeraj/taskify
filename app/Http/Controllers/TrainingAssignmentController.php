<?php

namespace App\Http\Controllers;

use App\Models\Training;
use Illuminate\Http\Request;

class TrainingAssignmentController extends Controller
{
    public function assign(Request $request)
    {
        $request->validate([
            'training_id' => 'required|exists:trainings,id',
            'role_id' => 'required|exists:roles,id',
            'start_date' => 'required|date',
            'completion_deadline' => 'required|date|after_or_equal:start_date',
        ]);

        $training = Training::findOrFail($request->training_id);
        $training->update([
            'assigned_role_id' => $request->role_id,
            'start_date' => $request->start_date,
            'completion_deadline' => $request->completion_deadline,
            'status' => 'active',
        ]);

        return back()->with('message', 'Training assigned to selected role.');
    }
}
