<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Partner;

class PartnerController extends Controller
{
    public function index()
    {
        return response()->json(['ok' => true, 'partners' => Partner::orderBy('ordre')->get()]);
    }
}
