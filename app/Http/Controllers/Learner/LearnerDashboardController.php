<?php

namespace App\Http\Controllers\Learner;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;

class LearnerDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return view('learner.dashboard', [
            'inProgress' => $user->enrollments()
                ->where('status', EnrollmentStatus::Validee)
                ->with('formation.lessons')
                ->get(),
            'pending' => $user->enrollments()
                ->where('status', EnrollmentStatus::Paiement)
                ->with('formation')
                ->get(),
            'events' => Event::upcoming()->take(3)->get(),
        ]);
    }
}
