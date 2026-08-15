<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardData;

class DashboardController extends Controller
{
    public function __invoke(DashboardData $dashboardData)
    {
        return view('dashboard', $dashboardData->blade());
    }
}
