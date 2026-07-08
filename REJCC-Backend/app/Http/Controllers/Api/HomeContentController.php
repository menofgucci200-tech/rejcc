<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomeBenefit;
use App\Models\HomeStat;
use App\Models\HomeValue;
use App\Models\MembershipStep;

class HomeContentController extends Controller
{
    public function index()
    {
        return response()->json([
            'ok' => true,
            'stats' => HomeStat::orderBy('ordre')->get(),
            'values' => HomeValue::orderBy('ordre')->get(),
            'benefits' => HomeBenefit::orderBy('ordre')->get(),
            'membership_steps' => MembershipStep::orderBy('ordre')->get(),
        ]);
    }
}
