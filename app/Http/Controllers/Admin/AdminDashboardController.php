<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Formation;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $toVerify = Payment::toVerify()->latest('submitted_at')->get();

        return view('admin.dashboard', [
            'activeMembers' => User::where('status', 'actif')->count(),
            'publishedFormations' => Formation::published()->count(),
            'totalFormations' => Formation::count(),
            'paymentsToVerify' => $toVerify,
            'revenue' => Payment::where('status', 'confirme')->sum('amount')
                + Order::whereIn('status', [OrderStatus::Validee, OrderStatus::Expediee])
                    ->whereDoesntHave('payment')->sum('amount'),
        ]);
    }
}
