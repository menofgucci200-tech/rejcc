<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sector;

class SectorController extends Controller
{
    public function index()
    {
        return response()->json(['ok' => true, 'sectors' => Sector::orderBy('ordre')->get()]);
    }
}
