<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;

class ActivityController extends Controller
{
    public function index()
    {
        return response()->json(['ok' => true, 'activities' => Activity::orderBy('ordre')->get()]);
    }
}
